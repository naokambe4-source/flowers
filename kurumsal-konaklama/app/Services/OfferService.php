<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\DTO\StayCriteria;
use App\Exceptions\DomainException;

/**
 * Manuel akış: Üye talebi → Yönetici fiyat teklifi (sürümlü) → Üye kabulü → Otel teyidi → Onay.
 * Teklif kabulü otomatik otel teyidi sayılmaz: rezervasyon "Onay Bekliyor" durumunda oluşur,
 * yönetici otelden alınan rezervasyon numarasını girerek onaylar.
 */
final class OfferService
{
    public function __construct(private readonly Database $db)
    {
    }

    public function createRequest(array $user, StayCriteria $c, array $data, string $idemKey): array
    {
        if ($idemKey !== '' && ($existing = $this->db->fetch('SELECT * FROM accommodation_requests WHERE user_id = ? AND idempotency_key = ?', [$user['id'], $idemKey]))) {
            return $existing;
        }
        $hotelId = !empty($data['hotel_id']) ? (int) $data['hotel_id'] : null;
        if ($hotelId !== null && !$this->db->value("SELECT 1 FROM hotels WHERE id = ? AND status = 'published'", [$hotelId])) {
            throw new DomainException('Seçilen otel bulunamadı.');
        }
        $roomId = !empty($data['room_id']) ? (int) $data['room_id'] : null;
        if ($roomId !== null && (int) $this->db->value('SELECT hotel_id FROM rooms WHERE id = ?', [$roomId]) !== $hotelId) {
            $roomId = null;
        }
        $code = null;
        for ($i = 0; $i < 10 && $code === null; $i++) {
            $try = BookingService::newCode('TL');
            if (!$this->db->value('SELECT 1 FROM accommodation_requests WHERE code = ?', [$try])) {
                $code = $try;
            }
        }
        try {
            $id = $this->db->insert('accommodation_requests', [
                'code' => $code, 'user_id' => $user['id'], 'institution_id' => $user['institution_id'],
                'hotel_id' => $hotelId, 'region_id' => !empty($data['region_id']) ? (int) $data['region_id'] : null,
                'room_id' => $roomId, 'concept_id' => !empty($data['concept_id']) ? (int) $data['concept_id'] : null,
                'check_in' => $c->checkIn, 'check_out' => $c->checkOut,
                'rooms_json' => json_encode($c->toArray()['rooms']), 'adults' => $c->adults(), 'children' => $c->children(),
                'target_minor' => $data['target_minor'] ?? null, 'target_reference_id' => $data['target_reference_id'] ?? null,
                'notes' => mb_substr(trim((string) ($data['notes'] ?? '')), 0, 2000) ?: null,
                'contact_phone' => mb_substr(trim((string) ($data['phone'] ?? $user['phone'] ?? '')), 0, 30) ?: null,
                'status' => 'new', 'idempotency_key' => $idemKey !== '' ? $idemKey : null,
            ]);
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000' && $idemKey !== '' && ($existing = $this->db->fetch('SELECT * FROM accommodation_requests WHERE user_id = ? AND idempotency_key = ?', [$user['id'], $idemKey]))) {
                return $existing;
            }
            throw $e;
        }
        $req = $this->db->fetch('SELECT * FROM accommodation_requests WHERE id = ?', [$id]);
        NotificationService::notifyUser((int) $user['id'], 'request', 'request_received', ['kod' => $code], '/tekliflerim/' . $code);
        NotificationService::notifyStaff('requests.manage', 'Yeni konaklama talebi: ' . $code, trim($user['first_name'] . ' ' . $user['last_name']) . ' · ' . tr_date($c->checkIn) . ' – ' . tr_date($c->checkOut), '/yonetim/talepler/' . $id);
        return $req;
    }

    /** Yeni teklif sürümü: önceki geçerli teklifler "Eski Sürüm" olur. */
    public function sendOffer(array $request, int $adminId, array $d): array
    {
        if (!in_array($request['status'], ['new', 'offered', 'expired', 'declined'], true)) {
            throw new DomainException('Bu talep için yeni teklif gönderilemez (durum: ' . status_label($request['status']) . ').');
        }
        if ((int) $d['total_minor'] <= 0) {
            throw new DomainException('Teklif tutarı sıfırdan büyük olmalıdır.');
        }
        if (strtotime((string) $d['valid_until']) <= time()) {
            throw new DomainException('Geçerlilik süresi ileri bir tarih olmalıdır.');
        }
        $offer = $this->db->transaction(function (Database $db) use ($request, $adminId, $d) {
            $db->fetch('SELECT id FROM accommodation_requests WHERE id = ? FOR UPDATE', [$request['id']]);
            $version = (int) $db->value('SELECT COALESCE(MAX(version), 0) FROM offers WHERE request_id = ?', [$request['id']]) + 1;
            $db->query("UPDATE offers SET status = 'superseded' WHERE request_id = ? AND status = 'sent'", [$request['id']]);
            $roomName = trim((string) $d['room_name']);
            if ($roomName === '' && !empty($d['room_id'])) {
                $roomName = (string) $db->value('SELECT name FROM rooms WHERE id = ?', [$d['room_id']]);
            }
            $id = $db->insert('offers', [
                'request_id' => $request['id'], 'version' => $version, 'hotel_id' => (int) $d['hotel_id'],
                'room_id' => !empty($d['room_id']) ? (int) $d['room_id'] : null, 'room_name' => mb_substr($roomName !== '' ? $roomName : 'Standart oda', 0, 150),
                'concept_id' => !empty($d['concept_id']) ? (int) $d['concept_id'] : null,
                'check_in' => $d['check_in'], 'check_out' => $d['check_out'], 'total_minor' => (int) $d['total_minor'], 'currency' => 'TRY',
                'payment_terms' => (string) $d['payment_terms'], 'cancellation_terms' => (string) $d['cancellation_terms'],
                'valid_until' => $d['valid_until'], 'status' => 'sent', 'note' => mb_substr((string) ($d['note'] ?? ''), 0, 1000) ?: null,
                'created_by' => $adminId,
            ]);
            $db->update('accommodation_requests', ['status' => 'offered', 'assigned_to' => $adminId], ['id' => $request['id']]);
            return $db->fetch('SELECT * FROM offers WHERE id = ?', [$id]);
        });
        AuditService::log('offer.sent', 'accommodation_request', (int) $request['id'], null, ['version' => $offer['version'], 'total_minor' => $offer['total_minor'], 'valid_until' => $offer['valid_until']]);
        NotificationService::notifyUser((int) $request['user_id'], 'offer', 'offer_sent', [
            'kod' => $request['code'], 'tutar' => money((int) $offer['total_minor']), 'gecerlilik' => tr_datetime($offer['valid_until']),
        ], '/tekliflerim/' . $request['code']);
        return $offer;
    }

    /**
     * Üye kabulü: yalnız en güncel sürüm, süresi dolmamış ve "Geçerli Teklif" durumunda kabul edilebilir.
     * Sonuç: "Onay Bekliyor" durumunda rezervasyon (otel teyidi bekler).
     */
    public function accept(array $request, array $user, int $offerId, int $version): array
    {
        $result = $this->db->transaction(function (Database $db) use ($request, $user, $offerId, $version) {
            $req = $db->fetch('SELECT * FROM accommodation_requests WHERE id = ? FOR UPDATE', [$request['id']]);
            if ((int) $req['user_id'] !== (int) $user['id']) {
                throw new DomainException('Bu teklif size ait değil.');
            }
            $offer = $db->fetch('SELECT * FROM offers WHERE id = ? AND request_id = ? FOR UPDATE', [$offerId, $req['id']]);
            if (!$offer) {
                throw new DomainException('Teklif bulunamadı.');
            }
            if ($offer['status'] === 'accepted' && $offer['booking_id']) {
                return [$db->fetch('SELECT * FROM bookings WHERE id = ?', [$offer['booking_id']]), false];
            }
            $latest = (int) $db->value('SELECT MAX(version) FROM offers WHERE request_id = ?', [$req['id']]);
            if ((int) $offer['version'] !== $version || (int) $offer['version'] !== $latest || $offer['status'] === 'superseded') {
                throw new DomainException('Bu teklifin daha yeni bir sürümü var. Lütfen güncel teklifi inceleyin.');
            }
            if ($offer['status'] !== 'sent') {
                throw new DomainException('Bu teklif artık kabul edilemez (' . status_label($offer['status']) . ').');
            }
            if (strtotime((string) $offer['valid_until']) < time()) {
                $db->update('offers', ['status' => 'expired'], ['id' => $offer['id']]);
                throw new DomainException('Teklifin geçerlilik süresi dolmuştur. Yeni teklif isteyebilirsiniz.');
            }
            $hotel = $db->fetch('SELECT * FROM hotels WHERE id = ?', [$offer['hotel_id']]);
            $rooms = json_decode((string) $req['rooms_json'], true) ?: [['adults' => (int) $req['adults'], 'ages' => []]];
            $c = StayCriteria::fromArray(['check_in' => $offer['check_in'], 'check_out' => $offer['check_out'], 'rooms' => $rooms]);
            $concept = $offer['concept_id'] ? $db->value('SELECT name FROM concepts WHERE id = ?', [$offer['concept_id']]) : null;
            $code = null;
            for ($i = 0; $i < 10 && $code === null; $i++) {
                $try = BookingService::newCode('KK');
                if (!$db->value('SELECT 1 FROM bookings WHERE code = ?', [$try])) {
                    $code = $try;
                }
            }
            $terms = [
                'hotel_name' => $hotel['name'], 'address' => $hotel['address'], 'check_in_time' => $hotel['check_in_time'], 'check_out_time' => $hotel['check_out_time'],
                'cancellation_policy' => $offer['cancellation_terms'], 'payment_terms' => $offer['payment_terms'], 'child_policy' => $hotel['child_policy'],
                'important_info' => $hotel['important_info'], 'offer_version' => (int) $offer['version'], 'offer_valid_until' => $offer['valid_until'],
                'platform_terms' => SettingsService::get('booking.terms_text'), 'captured_at' => date('c'),
            ];
            $bookingId = $db->insert('bookings', [
                'code' => $code, 'user_id' => $user['id'], 'institution_id' => $req['institution_id'], 'hotel_id' => $offer['hotel_id'],
                'check_in' => $offer['check_in'], 'check_out' => $offer['check_out'], 'nights' => $c->nights(), 'rooms_count' => $c->roomCount(),
                'adults' => $c->adults(), 'children' => $c->children(), 'status' => 'pending', 'mode' => 'offer', 'source' => 'offer',
                'offer_id' => $offer['id'], 'currency' => $offer['currency'], 'source_total_minor' => (int) $offer['total_minor'],
                'discount_minor' => 0, 'tax_minor' => 0, 'total_minor' => (int) $offer['total_minor'], 'tax_included' => 1,
                'price_breakdown' => json_encode(['lines' => [['label' => 'Teklif tutarı (sürüm ' . $offer['version'] . ', vergiler dahil)', 'amount' => (int) $offer['total_minor'], 'type' => 'source']], 'price_source' => 'Yönetici teklifi'], JSON_UNESCAPED_UNICODE),
                'terms_snapshot' => json_encode($terms, JSON_UNESCAPED_UNICODE), 'contact_name' => trim($user['first_name'] . ' ' . $user['last_name']),
                'contact_phone' => $req['contact_phone'] ?: $user['phone'], 'contact_email' => $user['email'], 'notes' => $req['notes'],
                'submitted_at' => date('Y-m-d H:i:s'),
            ]);
            foreach ($c->rooms as $occ) {
                $brId = $db->insert('booking_rooms', [
                    'booking_id' => $bookingId, 'room_id' => $offer['room_id'], 'rate_plan_id' => null, 'room_name' => $offer['room_name'],
                    'concept_name' => $concept, 'adults' => $occ->adults, 'children_ages' => implode(',', $occ->childAges),
                    'total_minor' => intdiv((int) $offer['total_minor'], max(1, $c->roomCount())),
                ]);
                if ($brId && !$db->value('SELECT 1 FROM booking_guests WHERE booking_id = ? AND is_lead = 1', [$bookingId])) {
                    $db->insert('booking_guests', ['booking_id' => $bookingId, 'booking_room_id' => $brId, 'first_name' => $user['first_name'], 'last_name' => $user['last_name'], 'is_lead' => 1]);
                }
            }
            // Oda kontenjanı tanımlı ve müsaitse stok ayrılır; değilse otel teyidi ile yönetilir.
            if ($offer['room_id'] !== null) {
                $count = (int) $db->value('SELECT COUNT(*) FROM inventory WHERE room_id = ? AND stay_date >= ? AND stay_date < ?', [$offer['room_id'], $offer['check_in'], $offer['check_out']]);
                if ($count === $c->nights()) {
                    $db->pdo()->exec('SAVEPOINT offer_stock');
                    try {
                        (new InventoryService($db))->reserve($bookingId, (int) $offer['hotel_id'], (int) $offer['room_id'], $c->dates(), $c->roomCount());
                        $db->update('bookings', ['stock_managed' => 1], ['id' => $bookingId]);
                    } catch (DomainException) {
                        $db->pdo()->exec('ROLLBACK TO SAVEPOINT offer_stock');
                        $db->update('bookings', ['admin_notes' => 'Kabul anında sistem kontenjanı yetersizdi; otel teyidi ile kontrol edin.'], ['id' => $bookingId]);
                    }
                }
            }
            $db->update('offers', ['status' => 'accepted', 'accepted_at' => date('Y-m-d H:i:s'), 'booking_id' => $bookingId], ['id' => $offer['id']]);
            $db->update('accommodation_requests', ['status' => 'converted'], ['id' => $req['id']]);
            $db->insert('booking_status_history', ['booking_id' => $bookingId, 'from_status' => null, 'to_status' => 'pending', 'note' => 'Teklif (sürüm ' . $offer['version'] . ') üye tarafından kabul edildi; otel teyidi bekleniyor.', 'changed_by' => $user['id']]);
            return [$db->fetch('SELECT * FROM bookings WHERE id = ?', [$bookingId]), true];
        });
        [$booking, $created] = $result;
        if ($created) {
            NotificationService::notifyStaff('bookings.manage', 'Teklif kabul edildi: ' . $booking['code'], 'Otel teyidi alınmalı ve rezervasyon numarası girilmelidir.', '/yonetim/rezervasyonlar/' . $booking['id']);
            (new BookingService($this->db))->notifyStatus($booking, 'pending', 'Teklif kabulünüz alındı; otel teyidi bekleniyor.');
        }
        return $booking;
    }

    public function decline(array $request, array $user, int $offerId): void
    {
        $this->db->transaction(function (Database $db) use ($request, $user, $offerId): void {
            $req = $db->fetch('SELECT * FROM accommodation_requests WHERE id = ? FOR UPDATE', [$request['id']]);
            if ((int) $req['user_id'] !== (int) $user['id'] || $req['status'] !== 'offered') {
                throw new DomainException('Bu teklif reddedilemez.');
            }
            $n = $db->query("UPDATE offers SET status = 'declined' WHERE id = ? AND request_id = ? AND status = 'sent'", [$offerId, $req['id']])->rowCount();
            if ($n !== 1) {
                throw new DomainException('Teklif artık geçerli değil.');
            }
            $db->update('accommodation_requests', ['status' => 'declined'], ['id' => $req['id']]);
        });
    }

    public function cancelRequest(array $request, array $user): void
    {
        if ((int) $request['user_id'] !== (int) $user['id'] || !in_array($request['status'], ['new', 'offered'], true)) {
            throw new DomainException('Bu talep iptal edilemez.');
        }
        $this->db->transaction(function (Database $db) use ($request): void {
            $db->query("UPDATE offers SET status = 'withdrawn' WHERE request_id = ? AND status = 'sent'", [$request['id']]);
            $db->update('accommodation_requests', ['status' => 'cancelled'], ['id' => $request['id']]);
        });
    }
}
