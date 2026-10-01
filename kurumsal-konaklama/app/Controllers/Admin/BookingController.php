<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Response;
use App\Services\AuditService;
use App\Services\BookingService;
use App\Services\VoucherService;

final class BookingController extends AdminController
{
    public function index(): Response
    {
        $q = $this->request->query;
        $where = ["b.status <> 'draft'"];
        $p = [];
        if (!empty($q['durum'])) {
            $where[] = 'b.status = :st';
            $p['st'] = (string) $q['durum'];
        }
        if (!empty($q['otel'])) {
            $where[] = 'b.hotel_id = :h';
            $p['h'] = (int) $q['otel'];
        }
        if (!empty($q['kurum'])) {
            $where[] = 'b.institution_id = :i';
            $p['i'] = (int) $q['kurum'];
        }
        if (!empty($q['bas'])) {
            $where[] = 'b.check_in >= :f';
            $p['f'] = (string) $q['bas'];
        }
        if (!empty($q['bit'])) {
            $where[] = 'b.check_in <= :t';
            $p['t'] = (string) $q['bit'];
        }
        if (!empty($q['ara'])) {
            $where[] = '(b.code LIKE :q OR b.contact_name LIKE :q2 OR b.contact_email LIKE :q3)';
            $p['q'] = $p['q2'] = $p['q3'] = '%' . $q['ara'] . '%';
        }
        $data = $this->paginate(
            "b.*, h.name AS hotel_name, i.name AS institution_name, CONCAT(u.first_name, ' ', u.last_name) AS member",
            'FROM bookings b JOIN hotels h ON h.id = b.hotel_id JOIN users u ON u.id = b.user_id LEFT JOIN institutions i ON i.id = b.institution_id WHERE ' . implode(' AND ', $where),
            $p,
            "FIELD(b.status, 'requested', 'pending') DESC, b.created_at DESC",
        );
        return $this->admin('bookings', $data + [
            'title' => 'Rezervasyonlar', 'f' => $q, 'query' => $this->queryWithout(),
            'hotels' => options($this->db()->fetchAll('SELECT id, name FROM hotels ORDER BY name')),
            'institutions' => options($this->db()->fetchAll('SELECT id, name FROM institutions ORDER BY name')),
        ]);
    }

    private function find(string $id): array
    {
        $b = $this->db()->fetch(
            "SELECT b.*, h.name AS hotel_name, h.slug, i.name AS institution_name, CONCAT(u.first_name, ' ', u.last_name) AS member, u.email AS member_email, u.phone AS member_phone, pr.name AS provider_name
             FROM bookings b JOIN hotels h ON h.id = b.hotel_id JOIN users u ON u.id = b.user_id LEFT JOIN institutions i ON i.id = b.institution_id LEFT JOIN providers pr ON pr.id = b.provider_id WHERE b.id = ?",
            [(int) $id],
        );
        $this->notFoundUnless($b && $b['status'] !== 'draft');
        return $b;
    }

    public function show(string $id): Response
    {
        $b = $this->find($id);
        $db = $this->db();
        return $this->admin('booking_show', [
            'title' => 'Rezervasyon ' . $b['code'], 'b' => $b,
            'rooms' => $db->fetchAll('SELECT * FROM booking_rooms WHERE booking_id = ?', [$b['id']]),
            'guests' => $db->fetchAll('SELECT * FROM booking_guests WHERE booking_id = ? ORDER BY booking_room_id, is_lead DESC', [$b['id']]),
            'history' => $db->fetchAll("SELECT h.*, CONCAT(u.first_name, ' ', u.last_name) AS who FROM booking_status_history h LEFT JOIN users u ON u.id = h.changed_by WHERE h.booking_id = ? ORDER BY h.id", [$b['id']]),
            'breakdown' => json_decode((string) $b['price_breakdown'], true) ?: [],
            'terms' => json_decode((string) $b['terms_snapshot'], true) ?: [],
            'transitions' => BookingService::TRANSITIONS[$b['status']] ?? [],
        ]);
    }

    public function updateStatus(string $id): Response
    {
        $b = $this->find($id);
        $to = (string) ($this->request->post['durum'] ?? '');
        (new BookingService($this->db()))->changeStatus($b, $to, $this->uid(), mb_substr(trim((string) ($this->request->post['not'] ?? '')), 0, 1000), trim((string) ($this->request->post['otel_no'] ?? '')) ?: null);
        $this->flash('success', 'Rezervasyon durumu güncellendi: ' . status_label($to));
        return $this->redirect('/yonetim/rezervasyonlar/' . $b['id']);
    }

    public function updateInfo(string $id): Response
    {
        $b = $this->find($id);
        $d = $this->validate(['payment_status' => 'required|in:unpaid,pay_at_hotel,invoiced,paid,refunded', 'hotel_confirmation_no' => 'nullable|max:120', 'admin_notes' => 'nullable|max:5000']);
        $new = ['payment_status' => $d['payment_status'], 'hotel_confirmation_no' => $d['hotel_confirmation_no'] ?: null, 'admin_notes' => $d['admin_notes'] ?: null];
        $this->db()->update('bookings', $new, ['id' => $b['id']]);
        AuditService::log('booking.update', 'booking', (int) $b['id'], $b, $new);
        if ($b['hotel_confirmation_no'] !== $new['hotel_confirmation_no'] && in_array($b['status'], ['confirmed'], true)) {
            (new BookingService($this->db()))->notifyStatus($b, 'changed', 'Otel teyit numarası güncellendi.');
        }
        $this->flash('success', 'Rezervasyon bilgileri kaydedildi.');
        return $this->redirect('/yonetim/rezervasyonlar/' . $b['id']);
    }

    public function voucher(string $id): Response
    {
        $b = $this->find($id);
        return Response::download(VoucherService::render((int) $b['id']), 'voucher-' . $b['code'] . '.pdf', 'application/pdf');
    }
}
