<?php
declare(strict_types=1);

namespace App\Providers\Adapters;

use App\DTO\ProviderBookingResult;
use App\DTO\ProviderHealth;
use App\DTO\ProviderRate;
use App\DTO\StayCriteria;
use App\Exceptions\ProviderException;
use App\Providers\Contracts\Capability;
use App\Providers\Contracts\HotelCatalogInterface;

/**
 * LiteAPI (Nuitee) v3.0 adaptörü — gerçek otel içeriği, canlı oda fiyatı ve rezervasyon.
 *
 * Ücretsiz hesap: https://dashboard.liteapi.travel → API anahtarı. "sand_" ile başlayan sandbox anahtarı gerçek otel
 * içeriği ve test fiyatları döndürür; sandbox rezervasyonları gerçek değildir. Canlı (production) anahtar ile
 * fiyatlar gerçek ve rezervasyonlar bağlayıcıdır — ödeme yöntemi hesabınızın sözleşmesine göre ayarlanmalıdır.
 *
 * Uç noktalar (resmi SDK liteapi-node-sdk 4.3.2 ile doğrulandı):
 *   GET  {api}/data/hotels?countryCode&latitude&longitude&radius&limit   — otel listesi
 *   GET  {api}/data/hotel?hotelId                                          — otel detayı (görseller, olanaklar)
 *   POST {api}/hotels/rates                                                — tam fiyat/müsaitlik (offerId)
 *   POST {book}/rates/prebook   {offerId}                                  — fiyat yeniden doğrulama (prebookId)
 *   POST {book}/rates/book      {prebookId, holder, payment, guests}       — rezervasyon
 *   GET  {book}/bookings/{id}   ·  PUT {book}/bookings/{id}                — sorgulama · iptal
 * Kimlik doğrulama: X-API-Key başlığı.
 */
final class LiteApiProvider extends AbstractProvider implements HotelCatalogInterface
{
    private const API = 'https://api.liteapi.travel/v3.0';
    private const BOOK = 'https://book.liteapi.travel/v3.0';

    /** LiteAPI pansiyon kodları → yerel konsept kodları */
    private const BOARD = ['RO' => 'RO', 'BB' => 'BB', 'HB' => 'HB', 'FB' => 'FB', 'AI' => 'AI', 'UAI' => 'UAI', 'AL' => 'AI'];

    public static function credentialFields(): array
    {
        return ['api_key' => 'API anahtarı (sand_… veya canlı anahtar)'];
    }

    public function capabilities(): array
    {
        return [Capability::SEARCH, Capability::CONTENT, Capability::AVAILABILITY, Capability::RATES, Capability::CHECK_RATE, Capability::BOOKING, Capability::BOOKING_LOOKUP, Capability::CANCEL, Capability::HEALTH];
    }

    public function attribution(): string
    {
        return 'Otel içeriği ve görseller: LiteAPI';
    }

    public function isSandbox(): bool
    {
        try {
            return str_starts_with($this->credential('api_key'), 'sand_');
        } catch (ProviderException) {
            return false;
        }
    }

    private function url(string $which, string $path, array $query = []): string
    {
        $base = $which === 'book' ? (string) $this->setting('book_url', self::BOOK) : (string) $this->setting('base_url', self::API);
        $base = rtrim($base, '/');
        if (!str_starts_with($base, 'https://')) {
            throw new ProviderException('Sağlayıcı adresi HTTPS olmalıdır.', $this->code());
        }
        return $base . $path . ($query ? '?' . http_build_query($query) : '');
    }

    private function call(string $op, string $method, string $url, ?array $body = null): array
    {
        // Rezervasyon ve iptal tekrar denenmez (mükerrer işlem riskine karşı)
        $retries = in_array($op, ['booking', 'cancel'], true) ? 0 : 2;
        $res = $this->http()->request($op, $method, $url, ['X-API-Key' => $this->credential('api_key'), 'Accept' => 'application/json'], $body, $retries);
        if (!is_array($res['json'])) {
            throw new ProviderException('LiteAPI yanıtı okunamadı.', $this->code());
        }
        if (isset($res['json']['error']) && !isset($res['json']['data'])) {
            $msg = is_array($res['json']['error']) ? (string) ($res['json']['error']['message'] ?? json_encode($res['json']['error'])) : (string) $res['json']['error'];
            throw new ProviderException('LiteAPI: ' . mb_substr($msg, 0, 200), $this->code());
        }
        return $res['json'];
    }

    private function currency(): string
    {
        return strtoupper((string) $this->setting('currency', 'TRY'));
    }

    private static function occupancies(StayCriteria $c): array
    {
        return array_map(static fn ($r) => ['adults' => $r->adults, 'children' => array_values(array_map('intval', $r->childAges))], $c->rooms);
    }

    // ---------------------------------------------------------------- Katalog

    public function listHotels(float $latitude, float $longitude, int $radiusMeters, int $limit): array
    {
        $json = $this->call('search', 'GET', $this->url('api', '/data/hotels', [
            'countryCode' => 'TR', 'latitude' => $latitude, 'longitude' => $longitude,
            'radius' => max(1000, $radiusMeters), 'limit' => max(1, min(200, $limit)), 'language' => 'tr',
        ]));
        $out = [];
        foreach ((array) ($json['data'] ?? []) as $h) {
            if (empty($h['id']) || empty($h['name'])) {
                continue;
            }
            $photo = (string) ($h['main_photo'] ?? $h['thumbnail'] ?? '');
            $out[] = [
                'external_id' => (string) $h['id'], 'name' => trim((string) $h['name']),
                'stars' => isset($h['stars']) && (float) $h['stars'] > 0 ? (int) round((float) $h['stars']) : null,
                'latitude' => isset($h['latitude']) ? (float) $h['latitude'] : null, 'longitude' => isset($h['longitude']) ? (float) $h['longitude'] : null,
                'address' => isset($h['address']) ? (string) $h['address'] : null, 'city' => isset($h['city']) ? (string) $h['city'] : null,
                'description' => isset($h['hotelDescription']) ? self::plain((string) $h['hotelDescription']) : null,
                'photo_urls' => $photo !== '' ? [$photo] : [], 'website' => null, 'phone' => null, 'facilities' => [],
            ];
        }
        return $out;
    }

    public function getHotel(string $externalHotelId): array
    {
        $json = $this->call('content', 'GET', $this->url('api', '/data/hotel', ['hotelId' => $externalHotelId, 'language' => 'tr']));
        $h = (array) ($json['data'] ?? []);
        $images = [];
        foreach ((array) ($h['hotelImages'] ?? []) as $img) {
            $u = (string) ($img['urlHd'] ?? $img['url'] ?? '');
            if ($u !== '') {
                $images[] = $u;
            }
        }
        $facilities = [];
        foreach ((array) ($h['hotelFacilities'] ?? []) as $f) {
            $facilities[] = is_array($f) ? (string) ($f['name'] ?? '') : (string) $f;
        }
        $times = (array) ($h['checkinCheckoutTimes'] ?? []);
        return [
            'external_id' => $externalHotelId, 'name' => (string) ($h['name'] ?? ''),
            'description' => self::plain((string) ($h['hotelDescription'] ?? '')),
            'stars' => isset($h['starRating']) && (float) $h['starRating'] > 0 ? (int) round((float) $h['starRating']) : null,
            'address' => isset($h['address']) ? (string) $h['address'] : null,
            'latitude' => isset($h['location']['latitude']) ? (float) $h['location']['latitude'] : null,
            'longitude' => isset($h['location']['longitude']) ? (float) $h['location']['longitude'] : null,
            'photo_urls' => array_values(array_unique($images)), 'facilities' => array_values(array_filter($facilities)),
            'check_in' => self::time((string) ($times['checkin_start'] ?? $times['checkin'] ?? '')),
            'check_out' => self::time((string) ($times['checkout'] ?? '')),
        ];
    }

    private static function plain(string $html): string
    {
        $t = html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim((string) preg_replace("/\n{3,}/", "\n\n", preg_replace('/[ \t]+/', ' ', $t) ?? ''));
    }

    private static function time(string $t): ?string
    {
        return preg_match('/^(\d{1,2}):(\d{2})/', trim($t), $m) ? sprintf('%02d:%s', (int) $m[1], $m[2]) : null;
    }

    // ---------------------------------------------------------------- Fiyat ve müsaitlik

    public function searchHotels(string $destinationId, StayCriteria $criteria): array
    {
        $rates = $this->getRates(array_filter(explode(',', $destinationId)), $criteria);
        $min = [];
        foreach ($rates as $r) {
            if (!isset($min[$r->externalHotelId]) || $r->totalMinor < $min[$r->externalHotelId]['min_total_minor']) {
                $min[$r->externalHotelId] = ['external_id' => $r->externalHotelId, 'name' => '', 'stars' => null, 'latitude' => null, 'longitude' => null, 'min_total_minor' => $r->totalMinor, 'currency' => $r->currency];
            }
        }
        return array_values($min);
    }

    public function getAvailability(string $externalHotelId, StayCriteria $criteria): array
    {
        $rates = $this->getRates([$externalHotelId], $criteria);
        return ['available' => $rates !== [], 'message' => $rates ? null : 'Seçilen tarihlerde müsait oda bulunamadı.'];
    }

    public function getRates(array $externalHotelIds, StayCriteria $criteria): array
    {
        $ids = array_values(array_unique(array_filter(array_map('strval', $externalHotelIds))));
        if (!$ids) {
            return [];
        }
        $out = [];
        foreach (array_chunk($ids, 50) as $chunk) {
            $json = $this->call('rates', 'POST', $this->url('api', '/hotels/rates'), [
                'hotelIds' => $chunk, 'checkin' => $criteria->checkIn, 'checkout' => $criteria->checkOut,
                'currency' => $this->currency(), 'guestNationality' => strtoupper((string) $this->setting('nationality', 'TR')),
                'occupancies' => self::occupancies($criteria), 'timeout' => (int) $this->setting('supplier_timeout', 8),
            ]);
            foreach ((array) ($json['data'] ?? []) as $hotel) {
                $hid = (string) ($hotel['hotelId'] ?? '');
                foreach ((array) ($hotel['roomTypes'] ?? []) as $rt) {
                    $rate = $this->mapOffer($hid, $rt);
                    if ($rate !== null) {
                        $out[] = $rate;
                    }
                }
            }
        }
        return $out;
    }

    /** Bir oda tipi teklifini (tüm odalar için toplam) normalleştirir. */
    private function mapOffer(string $hotelId, array $rt): ?ProviderRate
    {
        $offerId = (string) ($rt['offerId'] ?? '');
        $first = (array) (($rt['rates'] ?? [])[0] ?? []);
        if ($offerId === '' || !$first) {
            return null;
        }
        // Toplam: offerRetailRate (tüm odalar) yoksa oranların retailRate.total toplamı
        $total = null;
        $currency = null;
        if (isset($rt['offerRetailRate']['amount'])) {
            $total = self::toMinor($rt['offerRetailRate']['amount']);
            $currency = (string) ($rt['offerRetailRate']['currency'] ?? '');
        } else {
            $sum = 0;
            foreach ((array) $rt['rates'] as $r) {
                $t = $r['retailRate']['total'][0] ?? $r['retailRate']['total'] ?? null;
                $sum += (int) self::toMinor(is_array($t) ? ($t['amount'] ?? null) : $t);
                $currency ??= is_array($t) ? (string) ($t['currency'] ?? '') : null;
            }
            $total = $sum ?: null;
        }
        if (!$total) {
            return null;
        }
        $taxIncluded = true;
        foreach ((array) ($rt['rates'] ?? []) as $r) {
            foreach ((array) ($r['retailRate']['taxesAndFees'] ?? []) as $tax) {
                if (is_array($tax) && array_key_exists('included', $tax) && !$tax['included']) {
                    $taxIncluded = false; // otelde ödenecek ek vergi/ücret var
                }
            }
        }
        $cp = (array) ($first['cancellationPolicies'] ?? []);
        $refTag = strtoupper((string) ($cp['refundableTag'] ?? ''));
        $refundable = $refTag === '' ? null : $refTag === 'RFN';
        $summary = null;
        if ($refundable === false) {
            $summary = 'İade edilemez fiyat';
        } elseif (!empty($cp['cancelPolicyInfos'][0]['cancelTime'])) {
            $ts = strtotime((string) $cp['cancelPolicyInfos'][0]['cancelTime']);
            $summary = $ts ? 'Ücretsiz iptal: ' . date('d.m.Y H:i', $ts) . ' tarihine kadar' : 'İptal koşulları sağlayıcıya göre değişir';
        }
        $board = strtoupper((string) ($first['boardType'] ?? ''));
        $boardName = mb_strtolower((string) ($first['boardName'] ?? ''));
        $code = self::BOARD[$board] ?? match (true) {
            str_contains($boardName, 'ultra') => 'UAI',
            str_contains($boardName, 'all inclusive') || str_contains($boardName, 'her şey dahil') => 'AI',
            str_contains($boardName, 'full board') || str_contains($boardName, 'tam pansiyon') => 'FB',
            str_contains($boardName, 'half board') || str_contains($boardName, 'yarım pansiyon') => 'HB',
            str_contains($boardName, 'breakfast') || str_contains($boardName, 'kahvaltı') => 'BB',
            str_contains($boardName, 'room only') || str_contains($boardName, 'sadece oda') => 'RO',
            default => null,
        };
        $name = trim((string) ($first['name'] ?? 'Oda'));
        if ($code === null && !empty($first['boardName'])) {
            $name .= ' · ' . $first['boardName']; // pansiyon tipi yerel konsepte eşlenemediyse adla birlikte gösterilir
        }
        return new ProviderRate($hotelId, mb_substr($name, 0, 150), (int) $total, strtoupper($currency ?: $this->currency()), $taxIncluded, $code, $refundable, $summary, $offerId, true, ['supplier' => $rt['supplier'] ?? null]);
    }

    public function checkRate(string $rateKey, StayCriteria $criteria): ProviderRate
    {
        $json = $this->call('check_rate', 'POST', $this->url('book', '/rates/prebook'), ['offerId' => $rateKey, 'usePaymentSdk' => false]);
        $d = (array) ($json['data'] ?? []);
        $prebookId = (string) ($d['prebookId'] ?? '');
        if ($prebookId === '' || !isset($d['price'])) {
            throw new ProviderException('Fiyat artık geçerli değil. Lütfen aramayı yenileyin.', $this->code());
        }
        $rt = (array) (($d['roomTypes'] ?? [])[0] ?? []);
        $mapped = $rt ? $this->mapOffer((string) ($d['hotelId'] ?? ''), $rt + ['offerId' => $rateKey]) : null;
        return new ProviderRate(
            (string) ($d['hotelId'] ?? ''), $mapped?->roomName ?? 'Oda', (int) self::toMinor($d['price']), strtoupper((string) ($d['currency'] ?? $this->currency())),
            $mapped?->taxIncluded ?? true, $mapped?->boardCode, $mapped?->refundable, $mapped?->cancellationSummary, $prebookId, true,
            ['cancellationChanged' => $d['cancellationChanged'] ?? null, 'boardChanged' => $d['boardChanged'] ?? null],
        );
    }

    public function createBooking(string $rateKey, StayCriteria $criteria, array $holder, array $guests, string $clientReference): ProviderBookingResult
    {
        // LiteAPI her oda (occupancyNumber) için bir sorumlu misafir ister
        $perRoom = [];
        foreach ($guests as $g) {
            $room = max(1, (int) ($g['room'] ?? 1));
            if (!isset($perRoom[$room]) && empty($g['is_child'])) {
                $perRoom[$room] = ['occupancyNumber' => $room, 'firstName' => (string) $g['first_name'], 'lastName' => (string) $g['last_name'], 'email' => (string) $holder['email']];
            }
        }
        for ($i = 1; $i <= max(1, $criteria->roomCount()); $i++) {
            $perRoom[$i] ??= ['occupancyNumber' => $i, 'firstName' => (string) $holder['first_name'], 'lastName' => (string) $holder['last_name'], 'email' => (string) $holder['email']];
        }
        ksort($perRoom);
        $json = $this->call('booking', 'POST', $this->url('book', '/rates/book'), [
            'prebookId' => $rateKey,
            'clientReference' => mb_substr($clientReference, 0, 50),
            'holder' => ['firstName' => $holder['first_name'], 'lastName' => $holder['last_name'], 'email' => $holder['email'], 'phone' => (string) ($holder['phone'] ?? '')],
            'payment' => ['method' => (string) $this->setting('payment_method', 'ACC_CREDIT_CARD')],
            'guests' => array_values($perRoom),
        ]);
        $d = (array) ($json['data'] ?? []);
        if (empty($d['bookingId'])) {
            throw new ProviderException('LiteAPI rezervasyon numarası dönmedi.', $this->code());
        }
        $status = strtolower((string) ($d['status'] ?? 'confirmed'));
        if (!in_array($status, ['confirmed', 'pending'], true)) {
            throw new ProviderException('LiteAPI rezervasyon durumu: ' . $status, $this->code());
        }
        return new ProviderBookingResult((string) $d['bookingId'], $status, self::toMinor($d['price'] ?? null), $d['currency'] ?? null, ['hotelConfirmationCode' => $d['hotelConfirmationCode'] ?? null, 'sandbox' => $this->isSandbox()]);
    }

    public function getBooking(string $reference, array $context = []): ProviderBookingResult
    {
        $json = $this->call('booking_lookup', 'GET', $this->url('book', '/bookings/' . rawurlencode($reference)));
        $d = (array) ($json['data'] ?? []);
        return new ProviderBookingResult($reference, strtolower((string) ($d['status'] ?? 'unknown')), self::toMinor($d['price'] ?? null), $d['currency'] ?? null, ['hotelConfirmationCode' => $d['hotelConfirmationCode'] ?? null]);
    }

    public function cancelBooking(string $reference, array $context = []): ProviderBookingResult
    {
        $json = $this->call('cancel', 'PUT', $this->url('book', '/bookings/' . rawurlencode($reference)));
        $d = (array) ($json['data'] ?? []);
        $status = strtolower((string) ($d['status'] ?? ''));
        if ($status === '' || !str_contains($status, 'cancel')) {
            throw new ProviderException('LiteAPI iptali onaylamadı' . ($status !== '' ? ' (durum: ' . $status . ')' : '') . '.', $this->code());
        }
        return new ProviderBookingResult($reference, 'cancelled', null, $d['currency'] ?? null, ['cancellation_fee' => $d['cancellation_fee'] ?? null, 'refund_amount' => $d['refund_amount'] ?? null]);
    }

    public function healthCheck(): ProviderHealth
    {
        $start = microtime(true);
        $json = $this->call('health', 'GET', $this->url('api', '/data/currencies'));
        $ok = isset($json['data']) && is_array($json['data']);
        return new ProviderHealth($ok, ($ok ? 'LiteAPI bağlantısı başarılı' : 'LiteAPI beklenmeyen yanıt verdi') . ($this->isSandbox() ? ' (SANDBOX anahtarı — test fiyatları, gerçek rezervasyon oluşmaz)' : ' (canlı anahtar)'), null, (int) ((microtime(true) - $start) * 1000));
    }
}
