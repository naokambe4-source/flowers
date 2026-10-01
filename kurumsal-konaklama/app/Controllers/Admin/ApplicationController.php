<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Response;
use App\Core\Session;
use App\Services\MembershipService;

final class ApplicationController extends AdminController
{
    public function index(): Response
    {
        $st = (string) ($this->request->query['durum'] ?? 'pending');
        $data = $this->paginate(
            "m.*, i.name AS institution_name, CONCAT(u.first_name, ' ', u.last_name) AS reviewer",
            'FROM membership_requests m LEFT JOIN institutions i ON i.id = m.institution_id LEFT JOIN users u ON u.id = m.reviewed_by WHERE m.status = :st',
            ['st' => in_array($st, ['pending', 'approved', 'rejected'], true) ? $st : 'pending'],
            'm.created_at ' . ($st === 'pending' ? 'ASC' : 'DESC'),
        );
        return $this->admin('applications', $data + ['title' => 'Başvurular', 'st' => $st, 'query' => $this->queryWithout(), 'institutions' => options($this->db()->fetchAll('SELECT id, name FROM institutions WHERE is_active = 1 ORDER BY name')), 'links' => Session::pull('approval_links', [])]);
    }

    public function decide(string $id): Response
    {
        $svc = new MembershipService($this->db());
        if (($this->request->post['karar'] ?? '') === 'onayla') {
            $r = $svc->approve((int) $id, $this->uid(), $this->request->int('institution_id') ?: null, mb_substr(trim((string) ($this->request->post['not'] ?? '')), 0, 500));
            if (!$r['link']['sent']) {
                Session::put('approval_links', [$r['link']['link']]);
            }
            $this->flash('success', $r['link']['sent'] ? 'Başvuru onaylandı; parola oluşturma bağlantısı e-postayla gönderildi.' : 'Başvuru onaylandı. E-posta yapılandırılmadığından bağlantı aşağıda gösteriliyor.');
        } else {
            $svc->reject((int) $id, $this->uid(), mb_substr(trim((string) ($this->request->post['not'] ?? '')), 0, 500));
            $this->flash('success', 'Başvuru reddedildi.');
        }
        return $this->redirect('/yonetim/basvurular');
    }
}
