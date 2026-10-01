<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Response;
use App\Exceptions\DomainException;
use App\Services\AuditService;
use App\Services\OfferService;
use App\Services\SettingsService;

final class RequestController extends AdminController
{
    public function index(): Response
    {
        $st = (string) ($this->request->query['durum'] ?? '');
        $where = '1=1';
        $p = [];
        if ($st !== '') {
            $where = 'ar.status = :st';
            $p['st'] = $st;
        }
        $data = $this->paginate(
            "ar.*, h.name AS hotel_name, r.name AS region_name, i.name AS institution_name, CONCAT(u.first_name, ' ', u.last_name) AS member",
            "FROM accommodation_requests ar JOIN users u ON u.id = ar.user_id LEFT JOIN hotels h ON h.id = ar.hotel_id LEFT JOIN regions r ON r.id = ar.region_id LEFT JOIN institutions i ON i.id = ar.institution_id WHERE $where",
            $p,
            "FIELD(ar.status, 'new') DESC, ar.created_at DESC",
        );
        return $this->admin('requests', $data + ['title' => 'Konaklama Talepleri', 'st' => $st, 'query' => $this->queryWithout()]);
    }

    private function find(string $id): array
    {
        $r = $this->db()->fetch(
            "SELECT ar.*, h.name AS hotel_name, r.name AS region_name, c.name AS concept_name, rm.name AS room_name, i.name AS institution_name,
                    CONCAT(u.first_name, ' ', u.last_name) AS member, u.email, u.phone AS user_phone
             FROM accommodation_requests ar JOIN users u ON u.id = ar.user_id LEFT JOIN hotels h ON h.id = ar.hotel_id LEFT JOIN regions r ON r.id = ar.region_id
             LEFT JOIN concepts c ON c.id = ar.concept_id LEFT JOIN rooms rm ON rm.id = ar.room_id LEFT JOIN institutions i ON i.id = ar.institution_id WHERE ar.id = ?",
            [(int) $id],
        );
        $this->notFoundUnless($r);
        return $r;
    }

    public function show(string $id): Response
    {
        $req = $this->find($id);
        $db = $this->db();
        $hotelId = (int) ($this->request->query['otel'] ?? $req['hotel_id'] ?? 0);
        $hotel = $hotelId ? $db->fetch('SELECT * FROM hotels WHERE id = ?', [$hotelId]) : null;
        $ref = $req['target_reference_id'] ? $db->fetch('SELECT * FROM reference_prices WHERE id = ?', [$req['target_reference_id']]) : null;
        return $this->admin('request_show', [
            'title' => 'Talep ' . $req['code'], 'req' => $req, 'hotel' => $hotel, 'ref' => $ref,
            'rooms' => json_decode((string) $req['rooms_json'], true) ?: [],
            'offers' => $db->fetchAll("SELECT o.*, h.name AS hotel_name, CONCAT(u.first_name, ' ', u.last_name) AS who FROM offers o JOIN hotels h ON h.id = o.hotel_id LEFT JOIN users u ON u.id = o.created_by WHERE o.request_id = ? ORDER BY o.version DESC", [$req['id']]),
            'hotels' => options($db->fetchAll('SELECT id, name FROM hotels ORDER BY name')),
            'hotelRooms' => $hotel ? options($db->fetchAll('SELECT id, name FROM rooms WHERE hotel_id = ? ORDER BY sort', [$hotel['id']])) : [],
            'concepts' => options($db->fetchAll('SELECT id, name FROM concepts ORDER BY sort')),
            'validity' => date('Y-m-d\TH:i', time() + SettingsService::int('offer.default_validity_hours', 48) * 3600),
        ]);
    }

    public function sendOffer(string $id): Response
    {
        $req = $this->find($id);
        $d = $this->validate([
            'hotel_id' => 'required|int', 'room_id' => 'nullable|int', 'room_name' => 'nullable|max:150', 'concept_id' => 'nullable|int',
            'check_in' => 'required|date', 'check_out' => 'required|date', 'total' => 'required|money',
            'payment_terms' => 'required|min:5|max:3000', 'cancellation_terms' => 'required|min:5|max:3000',
            'valid_until' => 'required|datetime', 'note' => 'nullable|max:1000',
        ], ['hotel_id' => 'Otel', 'total' => 'Toplam tutar', 'payment_terms' => 'Ödeme koşulları', 'cancellation_terms' => 'İptal koşulları', 'valid_until' => 'Geçerlilik', 'check_in' => 'Giriş', 'check_out' => 'Çıkış']);
        if ($d['check_out'] <= $d['check_in']) {
            throw new DomainException('Çıkış tarihi girişten sonra olmalıdır.');
        }
        $d['total_minor'] = $d['total'];
        $offer = (new OfferService($this->db()))->sendOffer($req, $this->uid(), $d);
        $this->flash('success', 'Teklif (sürüm ' . $offer['version'] . ') üyeye gönderildi.');
        return $this->redirect('/yonetim/talepler/' . $req['id']);
    }

    public function updateStatus(string $id): Response
    {
        $req = $this->find($id);
        $to = (string) ($this->request->post['durum'] ?? '');
        if (!in_array($to, ['cancelled', 'new'], true) || in_array($req['status'], ['converted', 'accepted'], true)) {
            throw new DomainException('Bu talep için bu işlem yapılamaz.');
        }
        $this->db()->transaction(function ($db) use ($req, $to): void {
            if ($to === 'cancelled') {
                $db->query("UPDATE offers SET status = 'withdrawn' WHERE request_id = ? AND status = 'sent'", [$req['id']]);
            }
            $db->update('accommodation_requests', ['status' => $to], ['id' => $req['id']]);
        });
        AuditService::log('request.status', 'accommodation_request', (int) $req['id'], ['status' => $req['status']], ['status' => $to]);
        $this->flash('success', 'Talep durumu güncellendi.');
        return $this->redirect('/yonetim/talepler/' . $req['id']);
    }
}
