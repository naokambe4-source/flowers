<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Response;
use App\Services\AuditService;
use App\Services\NotificationService;

final class SupportController extends AdminController
{
    public function index(): Response
    {
        $st = in_array($this->request->query['durum'] ?? '', ['open', 'answered', 'closed'], true) ? $this->request->query['durum'] : 'open';
        $data = $this->paginate('s.*', 'FROM support_requests s WHERE s.status = :s', ['s' => $st], 's.created_at DESC');
        return $this->admin('support', $data + ['title' => 'Destek Talepleri', 'st' => $st, 'query' => $this->queryWithout()]);
    }

    public function reply(string $id): Response
    {
        $s = $this->db()->fetch('SELECT * FROM support_requests WHERE id = ?', [(int) $id]);
        $this->notFoundUnless($s);
        $d = $this->validate(['reply' => 'nullable|max:5000', 'status' => 'required|in:open,answered,closed'], ['reply' => 'Yanıt']);
        $this->db()->update('support_requests', ['reply' => $d['reply'] ?: $s['reply'], 'status' => $d['status'], 'replied_by' => $this->uid(), 'replied_at' => date('Y-m-d H:i:s')], ['id' => $s['id']]);
        AuditService::log('support.reply', 'support_request', (int) $s['id'], ['status' => $s['status']], ['status' => $d['status']]);
        if ($d['reply']) {
            if ($s['user_id']) {
                NotificationService::notifyUser((int) $s['user_id'], 'support', 'support_reply', ['not' => $d['reply']], '/destek');
            } elseif ($s['email']) {
                NotificationService::email($s['email'], 'support_reply', ['ad' => $s['name'], 'not' => $d['reply'], 'baglanti' => base_url('/iletisim')]);
            }
        }
        $this->flash('success', 'Destek talebi güncellendi.');
        return $this->redirect('/yonetim/destek', ['durum' => $d['status']]);
    }
}
