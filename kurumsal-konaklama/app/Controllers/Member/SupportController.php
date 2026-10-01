<?php
declare(strict_types=1);

namespace App\Controllers\Member;

use App\Controllers\Controller;
use App\Core\App;
use App\Core\Response;
use App\Services\NotificationService;
use App\Services\RateLimiter;

final class SupportController extends Controller
{
    public function index(): Response
    {
        $u = $this->user();
        $rows = App::db()->fetchAll('SELECT * FROM support_requests WHERE user_id = ? ORDER BY created_at DESC LIMIT 50', [$u['id']]);
        $bookings = App::db()->fetchAll("SELECT code FROM bookings WHERE user_id = ? AND status <> 'draft' ORDER BY created_at DESC LIMIT 20", [$u['id']]);
        return $this->view('member/support', ['title' => 'Destek', 'rows' => $rows, 'bookings' => $bookings]);
    }

    public function store(): Response
    {
        $u = $this->user();
        if (!RateLimiter::attempt('support:' . $u['id'], 10, 3600)) {
            throw new \App\Exceptions\DomainException('Çok fazla talep gönderildi. Lütfen daha sonra tekrar deneyin.');
        }
        $d = $this->validate(['subject' => 'required|min:3|max:200', 'message' => 'required|min:10|max:3000', 'booking_code' => 'nullable|max:20']);
        if ($d['booking_code'] && !App::db()->value('SELECT 1 FROM bookings WHERE code = ? AND user_id = ?', [$d['booking_code'], $u['id']])) {
            $d['booking_code'] = null;
        }
        App::db()->insert('support_requests', [
            'user_id' => $u['id'], 'name' => $u['first_name'] . ' ' . $u['last_name'], 'email' => $u['email'], 'phone' => $u['phone'],
            'subject' => $d['subject'], 'message' => $d['message'], 'booking_code' => $d['booking_code'], 'ip' => $this->request->ip(),
        ]);
        NotificationService::notifyStaff('support.manage', 'Yeni destek talebi', $d['subject'], '/yonetim/destek');
        $this->flash('success', 'Destek talebiniz alındı.');
        return $this->redirect('/destek');
    }
}
