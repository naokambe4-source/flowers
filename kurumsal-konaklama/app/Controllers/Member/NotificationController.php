<?php
declare(strict_types=1);

namespace App\Controllers\Member;

use App\Controllers\Controller;
use App\Core\App;
use App\Core\Response;

final class NotificationController extends Controller
{
    public function index(): Response
    {
        $u = $this->user();
        $rows = App::db()->fetchAll('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 100', [$u['id']]);
        return $this->view('member/notifications', ['title' => 'Bildirimler', 'rows' => $rows]);
    }

    public function markRead(): Response
    {
        $u = $this->user();
        $id = $this->request->int('id');
        if ($id > 0) {
            App::db()->query('UPDATE notifications SET read_at = NOW() WHERE id = ? AND user_id = ? AND read_at IS NULL', [$id, $u['id']]);
            $link = App::db()->value('SELECT link FROM notifications WHERE id = ? AND user_id = ?', [$id, $u['id']]);
            if (is_string($link) && str_starts_with($link, '/')) {
                return $this->redirect($link);
            }
        } else {
            App::db()->query('UPDATE notifications SET read_at = NOW() WHERE user_id = ? AND read_at IS NULL', [$u['id']]);
            $this->flash('success', 'Tüm bildirimler okundu olarak işaretlendi.');
        }
        return $this->redirect('/bildirimler');
    }
}
