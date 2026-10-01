<?php
declare(strict_types=1);

namespace App\Controllers\Member;

use App\Controllers\Controller;
use App\Core\App;
use App\Core\Response;
use App\Exceptions\HttpException;
use App\Policies\BookingPolicy;
use App\Services\BookingService;
use App\Services\VoucherService;

final class BookingController extends Controller
{
    public function index(): Response
    {
        $u = $this->user();
        $tab = (string) ($this->request->query['durum'] ?? 'aktif');
        $where = match ($tab) {
            'gecmis' => "b.status IN ('completed') OR (b.status = 'confirmed' AND b.check_out < CURDATE())",
            'iptal' => "b.status = 'cancelled'",
            default => "b.status IN ('requested','pending','confirmed') AND b.check_out >= CURDATE()",
        };
        $per = 10;
        $page = $this->page();
        $total = (int) App::db()->value("SELECT COUNT(*) FROM bookings b WHERE b.user_id = ? AND ($where)", [$u['id']]);
        $rows = App::db()->fetchAll(
            "SELECT b.*, h.name AS hotel_name, h.slug, h.cover_image_id, r.name AS region_name FROM bookings b JOIN hotels h ON h.id = b.hotel_id LEFT JOIN regions r ON r.id = h.region_id
             WHERE b.user_id = ? AND ($where) ORDER BY b.check_in DESC LIMIT $per OFFSET " . (($page - 1) * $per),
            [$u['id']],
        );
        $drafts = App::db()->fetchAll("SELECT b.code, b.created_at, h.name AS hotel_name FROM bookings b JOIN hotels h ON h.id = b.hotel_id WHERE b.user_id = ? AND b.status = 'draft' ORDER BY b.created_at DESC LIMIT 3", [$u['id']]);
        return $this->view('member/bookings', [
            'title' => 'Rezervasyonlarım', 'rows' => $rows, 'tab' => $tab, 'drafts' => $drafts,
            'page' => ['current' => $page, 'pages' => max(1, (int) ceil($total / $per)), 'total' => $total],
        ]);
    }

    private function find(string $code): array
    {
        $u = $this->user();
        $b = App::db()->fetch('SELECT b.*, h.name AS hotel_name, h.slug, h.address, h.cover_image_id, h.check_in_time, h.check_out_time, r.name AS region_name FROM bookings b JOIN hotels h ON h.id = b.hotel_id LEFT JOIN regions r ON r.id = h.region_id WHERE b.code = ?', [$code]);
        if (!$b || $b['status'] === 'draft' && (int) $b['user_id'] !== (int) $u['id'] || !BookingPolicy::canView($u, $b)) {
            throw new HttpException(404);
        }
        return $b;
    }

    public function show(string $code): Response
    {
        $b = $this->find($code);
        if ($b['status'] === 'draft') {
            return $this->redirect('/rezervasyon/' . $b['code'] . '/misafirler');
        }
        $db = App::db();
        return $this->view('member/booking_show', [
            'title' => 'Rezervasyon ' . $b['code'],
            'b' => $b,
            'rooms' => $db->fetchAll('SELECT * FROM booking_rooms WHERE booking_id = ? ORDER BY id', [$b['id']]),
            'guests' => $db->fetchAll('SELECT * FROM booking_guests WHERE booking_id = ? ORDER BY booking_room_id, is_lead DESC, id', [$b['id']]),
            'history' => $db->fetchAll('SELECT * FROM booking_status_history WHERE booking_id = ? ORDER BY id', [$b['id']]),
            'breakdown' => json_decode((string) $b['price_breakdown'], true) ?: [],
            'terms' => json_decode((string) $b['terms_snapshot'], true) ?: [],
            'canModify' => BookingPolicy::canModify($this->user(), $b),
        ]);
    }

    public function cancel(string $code): Response
    {
        $b = $this->find($code);
        if (!BookingPolicy::canModify($this->user(), $b)) {
            throw new HttpException(403);
        }
        (new BookingService(App::db()))->cancelByUser($b, $this->user(), mb_substr(trim((string) ($this->request->post['neden'] ?? '')), 0, 500));
        $this->flash('success', 'Rezervasyonunuz iptal edildi.');
        return $this->redirect('/rezervasyonlarim/' . $b['code']);
    }

    public function voucher(string $code): Response
    {
        $b = $this->find($code);
        $pdf = VoucherService::render((int) $b['id']);
        return Response::download($pdf, 'voucher-' . $b['code'] . '.pdf', 'application/pdf');
    }
}
