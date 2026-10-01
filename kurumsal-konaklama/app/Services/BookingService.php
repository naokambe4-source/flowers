<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Crypto;
use App\Core\Database;
use App\Core\Logger;
use App\DTO\PriceQuote;
use App\DTO\StayCriteria;
use App\Exceptions\DomainException;
use App\Exceptions\PriceChangedException;
use App\Exceptions\ProviderException;
use App\Providers\Contracts\Capability;
use App\Providers\ProviderRegistry;
use App\Services\Pricing\PricingContext;
use App\Services\Pricing\PricingProfile;
use App\Services\Pricing\PricingService;

/**
 * Rezervasyon akışı (en fazla 4 adım):
 *  1. Otel ve oda seçimi → taslak (status=draft)
 *  2. Gerekli misafir bilgileri
 *  3. Fiyat ve koşul kontrolü (fiyat yeniden hesaplanır; değiştiyse yeni toplam kabul edilmelidir)
 *  4. Onay / talep gönderme (transaction + satır kilidi + atomik stok + idempotency)
 */
final class BookingService
{
    /** Yönetim panelinde izin verilen durum geçişleri */
    public const TRANSITIONS = [
        'requested' => ['pending', 'confirmed', 'cancelled'],
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['completed', 'cancelled'],
        'completed' => [],
        'cancelled' => [],
        'draft' => [],
    ];

    public function __construct(private readonly Database $db)
    {
    }

    public static function newCode(string $prefix): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $s = '';
        for ($i = 0; $i < 8; $i++) {
            $s .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        return $prefix . '-' . $s;
    }

    public function criteriaOf(array $booking): StayCriteria
    {
        $rooms = $this->db->fetchAll('SELECT adults, children_ages FROM booking_rooms WHERE booking_id = ? ORDER BY id', [$booking['id']]);
        return StayCriteria::fromArray([
            'check_in' => $booking['check_in'],
            'check_out' => $booking['check_out'],
            'rooms' => array_map(static fn ($r) => ['adults' => (int) $r['adults'], 'ages' => $r['children_ages'] === '' ? [] : array_map('intval', explode(',', $r['children_ages']))], $rooms),
        ]);
    }

    public function quoteFor(array $user, int $hotelId, int $roomId, ?int $planId, StayCriteria $c, ?array $promo): PriceQuote
    {
        $ctx = PricingContext::load($this->db, [$hotelId], $c, SettingsService::int('pricing.reference_max_age_hours', 24));
        $pricing = new PricingService();
        $profile = PricingProfile::forUser($this->db, $user);
        if ($planId !== null) {
            return $pricing->quote($ctx, $profile, $hotelId, $roomId, $planId, $promo);
        }
        return $pricing->referenceQuote($ctx, $profile, $hotelId, $roomId)
            ?? new PriceQuote('request', $hotelId, $roomId, message: 'Bu oda için güncel fiyat bulunamadı.');
    }

    /** Adım 1: taslak oluşturur. Aynı idempotency anahtarıyla tekrar çağrılırsa aynı taslağı döndürür. */
    public function startDraft(array $user, int $hotelId, int $roomId, ?int $planId, StayCriteria $c, ?string $promoCode, string $idemKey): array
    {
        if ($idemKey !== '' && ($existing = $this->db->fetch('SELECT * FROM bookings WHERE user_id = ? AND idempotency_key = ?', [$user['id'], $idemKey]))) {
            return $existing;
        }
        $hotel = $this->db->fetch("SELECT * FROM hotels WHERE id = ? AND status = 'published'", [$hotelId]);
        if (!$hotel) {
            throw new DomainException('Otel bulunamadı veya yayında değil.');
        }
        if ($hotel['booking_mode'] === 'offer') {
            throw new DomainException('Bu otel için rezervasyon teklif usulü yapılır. Lütfen “TEKLİF İSTE” seçeneğini kullanın.');
        }
        $promo = null;
        if ($promoCode !== null && $promoCode !== '') {
            $promo = (new PromoService($this->db))->resolve($promoCode, $user, $hotelId, $c->nights());
        }
        $quote = $this->quoteFor($user, $hotelId, $roomId, $planId, $c, $promo);
        if (!$quote->isBookable()) {
            throw new DomainException($quote->kind === 'target'
                ? 'Bu fiyat onaya bağlı hedef tekliftir. Rezervasyon yerine teklif isteyebilirsiniz.'
                : ($quote->message ?? 'Seçilen oda bu tarihlerde rezerve edilemiyor.'));
        }
        $plan = $planId !== null ? $this->db->fetch('SELECT * FROM rate_plans WHERE id = ?', [$planId]) : null;
        $terms = $this->termsSnapshot($hotel, $plan, $quote);

        try {
            return $this->insertDraft($user, $hotel, $roomId, $planId, $c, $quote, $terms, $idemKey);
        } catch (\PDOException $e) {
            // Aynı anahtarla eşzamanlı ikinci istek: ilk oluşturulan taslağı döndür
            if ($e->getCode() === '23000' && $idemKey !== '' && ($existing = $this->db->fetch('SELECT * FROM bookings WHERE user_id = ? AND idempotency_key = ?', [$user['id'], $idemKey]))) {
                return $existing;
            }
            throw $e;
        }
    }

    private function insertDraft(array $user, array $hotel, int $roomId, ?int $planId, StayCriteria $c, PriceQuote $quote, array $terms, string $idemKey): array
    {
        return $this->db->transaction(function (Database $db) use ($user, $hotel, $roomId, $planId, $c, $quote, $terms, $idemKey) {
            $code = $this->uniqueCode('bookings', 'KK');
            $id = $db->insert('bookings', [
                'code' => $code, 'user_id' => $user['id'], 'institution_id' => $user['institution_id'], 'hotel_id' => $hotel['id'],
                'check_in' => $c->checkIn, 'check_out' => $c->checkOut, 'nights' => $c->nights(), 'rooms_count' => $c->roomCount(),
                'adults' => $c->adults(), 'children' => $c->children(), 'status' => 'draft',
                'mode' => $hotel['booking_mode'] === 'instant' && $quote->source === 'contract' ? 'instant' : 'request',
                'source' => $quote->source === 'provider' ? 'provider' : 'contract', 'provider_id' => $quote->providerId,
                'currency' => $quote->currency, 'source_total_minor' => $quote->sourceTotal, 'discount_minor' => $quote->discountTotal,
                'tax_minor' => $quote->taxTotal, 'total_minor' => $quote->total, 'tax_included' => 1,
                'verified_savings_minor' => $quote->verifiedSavings, 'reference_price_id' => $quote->referencePriceId,
                'price_breakdown' => json_encode($quote->breakdown, JSON_UNESCAPED_UNICODE), 'quote_hash' => $quote->hash(),
                'terms_snapshot' => json_encode($terms, JSON_UNESCAPED_UNICODE), 'promo_code_id' => $quote->promoCodeId,
                'contact_name' => trim($user['first_name'] . ' ' . $user['last_name']), 'contact_phone' => $user['phone'], 'contact_email' => $user['email'],
                'idempotency_key' => $idemKey !== '' ? $idemKey : null,
            ]);
            $perRoom = intdiv($quote->total, max(1, $c->roomCount()));
            foreach ($c->rooms as $i => $occ) {
                $db->insert('booking_rooms', [
                    'booking_id' => $id, 'room_id' => $roomId, 'rate_plan_id' => $planId, 'room_name' => (string) $quote->roomName,
                    'concept_name' => $quote->conceptName, 'adults' => $occ->adults, 'children_ages' => implode(',', $occ->childAges),
                    'total_minor' => $i === 0 ? $quote->total - $perRoom * ($c->roomCount() - 1) : $perRoom,
                ]);
            }
            $db->insert('booking_status_history', ['booking_id' => $id, 'from_status' => null, 'to_status' => 'draft', 'note' => 'Oda seçildi', 'changed_by' => $user['id']]);
            return $db->fetch('SELECT * FROM bookings WHERE id = ?', [$id]);
        });
    }

    public function termsSnapshot(array $hotel, ?array $plan, ?PriceQuote $quote): array
    {
        return [
            'hotel_name' => $hotel['name'],
            'address' => $hotel['address'],
            'check_in_time' => $hotel['check_in_time'],
            'check_out_time' => $hotel['check_out_time'],
            'cancellation_policy' => trim((string) ($plan['cancellation_policy'] ?? '')) ?: (string) $hotel['cancellation_policy'],
            'cancellation_summary' => $quote?->cancellationSummary,
            'payment_terms' => trim((string) ($plan['payment_terms'] ?? '')) ?: (string) $hotel['payment_policy'],
            'child_policy' => $hotel['child_policy'],
            'important_info' => $hotel['important_info'],
            'refundable' => $quote?->refundable,
            'rate_plan' => $plan['name'] ?? null,
            'platform_terms' => SettingsService::get('booking.terms_text'),
            'captured_at' => date('c'),
        ];
    }

    private function uniqueCode(string $table, string $prefix): string
    {
        for ($i = 0; $i < 10; $i++) {
            $code = self::newCode($prefix);
            if (!$this->db->value("SELECT 1 FROM $table WHERE code = ?", [$code])) {
                return $code;
            }
        }
        throw new \RuntimeException('Benzersiz kod üretilemedi.');
    }

    public function findDraftForUser(string $code, array $user): array
    {
        $b = $this->db->fetch('SELECT * FROM bookings WHERE code = ? AND user_id = ?', [$code, $user['id']]);
        if (!$b) {
            throw new \App\Exceptions\HttpException(404);
        }
        return $b;
    }

    /** Adım 2: misafir bilgileri (oda başına sorumlu misafir zorunlu; diğer adlar isteğe bağlı). */
    public function saveGuests(array $booking, array $input): void
    {
        if ($booking['status'] !== 'draft') {
            throw new DomainException('Bu rezervasyon zaten gönderildi.');
        }
        $rooms = $this->db->fetchAll('SELECT * FROM booking_rooms WHERE booking_id = ? ORDER BY id', [$booking['id']]);
        $errors = [];
        $guests = [];
        foreach ($rooms as $i => $room) {
            $g = $input['misafir'][$i] ?? [];
            $first = trim((string) ($g['ad'] ?? ''));
            $last = trim((string) ($g['soyad'] ?? ''));
            if (mb_strlen($first) < 2 || mb_strlen($last) < 2) {
                $errors["misafir.$i"] = ($i + 1) . '. oda için sorumlu misafirin adı ve soyadı zorunludur.';
                continue;
            }
            $guests[] = ['booking_room_id' => $room['id'], 'first_name' => mb_substr($first, 0, 80), 'last_name' => mb_substr($last, 0, 80), 'is_child' => 0, 'age' => null, 'is_lead' => 1];
            foreach ((array) ($g['diger'] ?? []) as $other) {
                $parts = preg_split('/\s+/', trim((string) $other), 2) ?: [];
                if (count($parts) === 2) {
                    $guests[] = ['booking_room_id' => $room['id'], 'first_name' => mb_substr($parts[0], 0, 80), 'last_name' => mb_substr($parts[1], 0, 80), 'is_child' => 0, 'age' => null, 'is_lead' => 0];
                }
            }
            foreach ($room['children_ages'] === '' ? [] : explode(',', $room['children_ages']) as $j => $age) {
                $name = trim((string) ($g['cocuk'][$j] ?? ''));
                $parts = preg_split('/\s+/', $name, 2) ?: [];
                $guests[] = ['booking_room_id' => $room['id'], 'first_name' => mb_substr($parts[0] ?? 'Çocuk', 0, 80) ?: 'Çocuk', 'last_name' => mb_substr($parts[1] ?? '', 0, 80), 'is_child' => 1, 'age' => (int) $age, 'is_lead' => 0];
            }
        }
        $phone = trim((string) ($input['iletisim_telefon'] ?? ''));
        $email = mb_strtolower(trim((string) ($input['iletisim_eposta'] ?? '')));
        if (strlen(preg_replace('/\D+/', '', $phone) ?? '') < 10) {
            $errors['iletisim_telefon'] = 'Geçerli bir iletişim telefonu girin.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['iletisim_eposta'] = 'Geçerli bir e-posta adresi girin.';
        }
        if ($errors) {
            throw new \App\Exceptions\ValidationException($errors);
        }
        $this->db->transaction(function (Database $db) use ($booking, $guests, $phone, $email, $input): void {
            $db->delete('booking_guests', ['booking_id' => $booking['id']]);
            foreach ($guests as $g) {
                $db->insert('booking_guests', $g + ['booking_id' => $booking['id']]);
            }
            $lead = $guests[0];
            $db->update('bookings', [
                'contact_name' => $lead['first_name'] . ' ' . $lead['last_name'],
                'contact_phone' => mb_substr($phone, 0, 30), 'contact_email' => mb_substr($email, 0, 190),
                'notes' => mb_substr(trim((string) ($input['not'] ?? '')), 0, 2000) ?: null,
            ], ['id' => $booking['id']]);
        });
    }

    /** Adım 3: güncel fiyatı yeniden hesaplar. */
    public function requote(array $booking, array $user): PriceQuote
    {
        $room = $this->db->fetch('SELECT room_id, rate_plan_id FROM booking_rooms WHERE booking_id = ? ORDER BY id LIMIT 1', [$booking['id']]);
        $promo = null;
        if ($booking['promo_code_id'] !== null) {
            $p = $this->db->fetch('SELECT * FROM promo_codes WHERE id = ?', [$booking['promo_code_id']]);
            if ($p) {
                try {
                    $promo = (new PromoService($this->db))->resolve($p['code'], $user, (int) $booking['hotel_id'], (int) $booking['nights']);
                } catch (DomainException) {
                    $promo = null;
                }
            }
        }
        return $this->quoteFor($user, (int) $booking['hotel_id'], (int) $room['room_id'], $room['rate_plan_id'] !== null ? (int) $room['rate_plan_id'] : null, $this->criteriaOf($booking), $promo);
    }

    /** Taslaktaki fiyatı yeni teklifle günceller (kullanıcı yeniden kabul etmelidir). */
    public function refreshDraftPrice(array $booking, PriceQuote $q): void
    {
        $this->db->update('bookings', [
            'source_total_minor' => $q->sourceTotal, 'discount_minor' => $q->discountTotal, 'tax_minor' => $q->taxTotal,
            'total_minor' => $q->total, 'verified_savings_minor' => $q->verifiedSavings, 'reference_price_id' => $q->referencePriceId,
            'price_breakdown' => json_encode($q->breakdown, JSON_UNESCAPED_UNICODE), 'quote_hash' => $q->hash(), 'promo_code_id' => $q->promoCodeId,
        ], ['id' => $booking['id'], 'status' => 'draft']);
    }

    /**
     * Adım 4: onay. Mükerrer gönderimde ilk sonucu döndürür.
     * @throws PriceChangedException fiyat değiştiyse (taslak yeni fiyatla güncellenir)
     */
    public function confirm(array $booking, array $user, string $acceptedHash): array
    {
        $booking = $this->db->fetch('SELECT * FROM bookings WHERE id = ?', [$booking['id']]) ?? $booking;
        if ($booking['status'] !== 'draft') {
            return $booking; // tekrarlanan POST: mevcut sonucu göster
        }
        if ((int) $this->db->value('SELECT COUNT(*) FROM booking_guests WHERE booking_id = ? AND is_lead = 1', [$booking['id']]) < (int) $booking['rooms_count']) {
            throw new DomainException('Lütfen önce misafir bilgilerini tamamlayın.');
        }
        $quote = $this->requote($booking, $user);
        if (!$quote->isBookable()) {
            // Eşzamanlı ikinci istek ilk isteğin stoğunu görmüş olabilir: kayıt artık taslak değilse sonucu döndür
            $current = $this->db->fetch('SELECT * FROM bookings WHERE id = ?', [$booking['id']]);
            if ($current && $current['status'] !== 'draft') {
                return $current;
            }
            throw new DomainException($quote->message ?? 'Seçilen oda artık rezerve edilemiyor.');
        }
        if (!hash_equals((string) $booking['quote_hash'], $acceptedHash) || $quote->hash() !== $acceptedHash) {
            $this->refreshDraftPrice($booking, $quote);
            throw new PriceChangedException((int) $booking['total_minor'], $quote->total);
        }

        // Sağlayıcı rezervasyonu: dış çağrı transaction dışında, rezervasyona özel kilit altında yapılır.
        $providerRef = null;
        $lockName = 'kk_booking_' . $booking['id'];
        if ($booking['source'] === 'provider') {
            if ((int) $this->db->value('SELECT GET_LOCK(?, 20)', [$lockName]) !== 1) {
                throw new DomainException('Rezervasyon işleniyor, lütfen birkaç saniye sonra sayfayı yenileyin.');
            }
            try {
                $current = $this->db->fetch('SELECT * FROM bookings WHERE id = ?', [$booking['id']]);
                if ($current['status'] !== 'draft') {
                    return $current;
                }
                $providerRef = $this->createProviderBooking($booking, $user, $quote);
            } catch (\Throwable $e) {
                $this->db->value('SELECT RELEASE_LOCK(?)', [$lockName]);
                throw $e;
            }
        }

        try {
            [$result, $transitioned] = $this->finalize($booking, $user, $quote, $providerRef);
        } finally {
            if ($booking['source'] === 'provider') {
                $this->db->value('SELECT RELEASE_LOCK(?)', [$lockName]);
            }
        }

        if ($transitioned) {
            $this->notifyStatus($result, $result['status']);
            NotificationService::notifyStaff('bookings.view', 'Yeni rezervasyon: ' . $result['code'], $result['contact_name'] . ' · ' . tr_date($result['check_in']) . ' – ' . tr_date($result['check_out']), '/yonetim/rezervasyonlar/' . $result['id']);
        }
        return $result;
    }

    /** @return array{0: array, 1: bool} */
    private function finalize(array $booking, array $user, PriceQuote $quote, ?string $providerRef): array
    {
        return $this->db->transaction(function (Database $db) use ($booking, $user, $quote, $providerRef) {
            $locked = $db->fetch('SELECT * FROM bookings WHERE id = ? FOR UPDATE', [$booking['id']]);
            if (!$locked || $locked['status'] !== 'draft') {
                return [$locked, false]; // eşzamanlı ikinci istek
            }
            if ($quote->promoCodeId !== null) {
                (new PromoService($db))->consume($quote->promoCodeId);
            }
            $room = $db->fetch('SELECT room_id FROM booking_rooms WHERE booking_id = ? ORDER BY id LIMIT 1', [$booking['id']]);
            $c = $this->criteriaOf($booking);
            $stockManaged = 0;
            if ($booking['source'] === 'contract') {
                (new InventoryService($db))->reserve((int) $booking['id'], (int) $booking['hotel_id'], (int) $room['room_id'], $c->dates(), $c->roomCount());
                $stockManaged = 1;
            }
            $status = ($booking['mode'] === 'instant' || $providerRef !== null) ? 'confirmed' : 'requested';
            $db->update('bookings', [
                'status' => $status, 'stock_managed' => $stockManaged, 'submitted_at' => date('Y-m-d H:i:s'),
                'confirmed_at' => $status === 'confirmed' ? date('Y-m-d H:i:s') : null,
                'verify_token' => $status === 'confirmed' ? Crypto::token(32) : null,
                'provider_reference' => $providerRef,
            ], ['id' => $booking['id']]);
            $db->insert('booking_status_history', [
                'booking_id' => $booking['id'], 'from_status' => 'draft', 'to_status' => $status,
                'note' => $status === 'confirmed' ? 'Doğrulanmış stokla anında rezervasyon' : 'Otel teyidine bağlı rezervasyon talebi',
                'changed_by' => $user['id'],
            ]);
            return [$db->fetch('SELECT * FROM bookings WHERE id = ?', [$booking['id']]), true];
        });
    }

    private function createProviderBooking(array $booking, array $user, PriceQuote $quote): string
    {
        $provider = ProviderRegistry::find((int) $booking['provider_id']);
        $row = ProviderRegistry::row((int) $booking['provider_id']);
        if (!$provider->supports(Capability::BOOKING) || !$provider->supports(Capability::CHECK_RATE) || (int) $row['booking_authorized'] !== 1) {
            throw new DomainException('Bu sağlayıcı üzerinden rezervasyon yetkisi bulunmuyor. Lütfen teklif isteyin.');
        }
        $rateKey = (string) ($quote->breakdown['external_rate_id'] ?? '');
        $c = $this->criteriaOf($booking);
        try {
            $checked = $provider->checkRate($rateKey, $c);
            if ($checked->totalMinor !== $quote->sourceTotal) {
                throw new DomainException('Sağlayıcı fiyatı değişti. Lütfen aramayı yenileyin.');
            }
            $guests = $this->db->fetchAll('SELECT bg.*, (SELECT COUNT(*) FROM booking_rooms br2 WHERE br2.booking_id = bg.booking_id AND br2.id <= bg.booking_room_id) AS room FROM booking_guests bg WHERE bg.booking_id = ?', [$booking['id']]);
            $holder = ['first_name' => $user['first_name'], 'last_name' => $user['last_name'], 'email' => $booking['contact_email'], 'phone' => $booking['contact_phone']];
            $res = $provider->createBooking((string) ($checked->rateKey ?: $rateKey), $c, $holder, $guests, $booking['code']);
            return $res->reference;
        } catch (ProviderException $e) {
            Logger::warning('Sağlayıcı rezervasyonu başarısız', ['booking' => $booking['code'], 'error' => $e->getMessage()]);
            throw new DomainException('Sağlayıcı rezervasyonu tamamlanamadı: ' . $e->getMessage());
        }
    }

    /** Üye iptali. */
    public function cancelByUser(array $booking, array $user, string $reason): void
    {
        if (!in_array($booking['status'], ['requested', 'pending', 'confirmed'], true)) {
            throw new DomainException('Bu rezervasyon iptal edilemez.');
        }
        if ($booking['check_in'] <= date('Y-m-d')) {
            throw new DomainException('Giriş günü gelmiş rezervasyonlar çevrimiçi iptal edilemez. Lütfen destek ile iletişime geçin.');
        }
        $this->cancel($booking, (int) $user['id'], $reason !== '' ? $reason : 'Üye tarafından iptal edildi');
    }

    /** Ortak iptal: sağlayıcı → stok → promosyon → durum geçmişi → bildirim. */
    public function cancel(array $booking, ?int $byUserId, string $reason): void
    {
        if ($booking['source'] === 'provider' && $booking['provider_reference']) {
            $provider = ProviderRegistry::find((int) $booking['provider_id']);
            if (!$provider->supports(Capability::CANCEL)) {
                throw new DomainException('Bu sağlayıcı çevrimiçi iptali desteklemiyor. Yönetim ekibi iptali sağlayıcı üzerinden yapmalıdır.');
            }
            try {
                $provider->cancelBooking((string) $booking['provider_reference'], ['email' => $booking['contact_email']]);
            } catch (ProviderException $e) {
                throw new DomainException('Sağlayıcıda iptal yapılamadı: ' . $e->getMessage());
            }
        }
        $done = $this->db->transaction(function (Database $db) use ($booking, $byUserId, $reason) {
            $locked = $db->fetch('SELECT * FROM bookings WHERE id = ? FOR UPDATE', [$booking['id']]);
            if (!$locked || in_array($locked['status'], ['cancelled', 'completed', 'draft'], true)) {
                return false;
            }
            if ((int) $locked['stock_managed'] === 1) {
                (new InventoryService($db))->release((int) $locked['id']);
            }
            if ($locked['promo_code_id'] !== null) {
                (new PromoService($db))->release((int) $locked['promo_code_id']);
            }
            $db->update('bookings', ['status' => 'cancelled', 'cancelled_at' => date('Y-m-d H:i:s'), 'cancel_reason' => mb_substr($reason, 0, 500), 'cancelled_by' => $byUserId], ['id' => $locked['id']]);
            $db->insert('booking_status_history', ['booking_id' => $locked['id'], 'from_status' => $locked['status'], 'to_status' => 'cancelled', 'note' => mb_substr($reason, 0, 1000), 'changed_by' => $byUserId]);
            if ($locked['offer_id'] !== null) {
                $req = $db->value('SELECT request_id FROM offers WHERE id = ?', [$locked['offer_id']]);
                if ($req) {
                    $db->update('accommodation_requests', ['status' => 'cancelled'], ['id' => $req]);
                }
            }
            return true;
        });
        if ($done) {
            $fresh = $this->db->fetch('SELECT * FROM bookings WHERE id = ?', [$booking['id']]);
            $this->notifyStatus($fresh, 'cancelled', $reason);
        }
    }

    /** Yönetim: durum değişikliği. Otel teyidi gerektiren onaylarda otel rezervasyon numarası zorunludur. */
    public function changeStatus(array $booking, string $to, int $adminId, string $note, ?string $hotelConfirmationNo): void
    {
        $from = $booking['status'];
        if (!in_array($to, self::TRANSITIONS[$from] ?? [], true)) {
            throw new DomainException('Bu durum geçişine izin verilmiyor: ' . status_label($from) . ' → ' . status_label($to));
        }
        if ($to === 'cancelled') {
            $this->cancel($booking, $adminId, $note !== '' ? $note : 'Yönetim tarafından iptal edildi');
            return;
        }
        if ($to === 'confirmed' && $booking['mode'] !== 'instant' && trim((string) ($hotelConfirmationNo ?: $booking['hotel_confirmation_no'])) === '') {
            throw new DomainException('Onay için otelden alınan doğrulanmış rezervasyon numarasını girin.');
        }
        $this->db->transaction(function (Database $db) use ($booking, $from, $to, $adminId, $note, $hotelConfirmationNo): void {
            $locked = $db->fetch('SELECT * FROM bookings WHERE id = ? FOR UPDATE', [$booking['id']]);
            if ($locked['status'] !== $from) {
                throw new DomainException('Rezervasyon başka bir kullanıcı tarafından güncellendi. Sayfayı yenileyin.');
            }
            $data = ['status' => $to];
            if ($hotelConfirmationNo !== null && trim($hotelConfirmationNo) !== '') {
                $data['hotel_confirmation_no'] = mb_substr(trim($hotelConfirmationNo), 0, 120);
            }
            if ($to === 'confirmed') {
                $data['confirmed_at'] = date('Y-m-d H:i:s');
                $data['verify_token'] = $locked['verify_token'] ?: Crypto::token(32);
            }
            $db->update('bookings', $data, ['id' => $booking['id']]);
            $db->insert('booking_status_history', ['booking_id' => $booking['id'], 'from_status' => $from, 'to_status' => $to, 'note' => $note !== '' ? mb_substr($note, 0, 1000) : null, 'changed_by' => $adminId]);
        });
        AuditService::log('booking.status', 'booking', (int) $booking['id'], ['status' => $from], ['status' => $to, 'hotel_confirmation_no' => $hotelConfirmationNo]);
        if (in_array($to, ['confirmed', 'pending'], true)) {
            $fresh = $this->db->fetch('SELECT * FROM bookings WHERE id = ?', [$booking['id']]);
            $this->notifyStatus($fresh, $to === 'pending' ? 'changed' : 'confirmed', $to === 'pending' ? 'Talebiniz otele iletildi, teyit bekleniyor.' : $note);
        }
    }

    public function notifyStatus(array $b, string $event, string $note = ''): void
    {
        $hotel = (string) $this->db->value('SELECT name FROM hotels WHERE id = ?', [$b['hotel_id']]);
        $vars = ['kod' => $b['code'], 'otel' => $hotel, 'giris' => tr_date($b['check_in']), 'cikis' => tr_date($b['check_out']), 'tutar' => money((int) $b['total_minor'], $b['currency']), 'not' => $note];
        $map = [
            'requested' => ['booking_requested', 'Rezervasyon talebiniz alındı'],
            'confirmed' => ['booking_confirmed', 'Rezervasyonunuz onaylandı'],
            'pending' => ['booking_changed', 'Rezervasyonunuz otel onayı bekliyor'],
            'changed' => ['booking_changed', 'Rezervasyonunuz güncellendi'],
            'cancelled' => ['booking_cancelled', 'Rezervasyonunuz iptal edildi'],
        ];
        if (!isset($map[$event])) {
            return;
        }
        NotificationService::notifyUser((int) $b['user_id'], 'booking', $map[$event][0], $vars, '/rezervasyonlarim/' . $b['code']);
    }
}
