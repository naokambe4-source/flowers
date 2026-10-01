<?php
declare(strict_types=1);

namespace App\Controllers\Member;

use App\Controllers\Controller;
use App\Core\App;
use App\Core\Response;
use App\Exceptions\HttpException;
use App\Services\MembershipService;

/** Kurum yöneticisi: yalnız kendi kurumunun üyeleri, rezervasyonları ve başvuruları. */
final class InstitutionController extends Controller
{
    private function institutionId(): int
    {
        $u = $this->user();
        if ($u['institution_id'] === null) {
            throw new HttpException(403, 'Hesabınız bir kuruma bağlı değil.');
        }
        return (int) $u['institution_id'];
    }

    public function index(): Response
    {
        $iid = $this->institutionId();
        $db = App::db();
        return $this->view('member/institution', [
            'title' => 'Kurumum',
            'inst' => $db->fetch('SELECT i.*, t.name AS type_name FROM institutions i LEFT JOIN institution_types t ON t.id = i.institution_type_id WHERE i.id = ?', [$iid]),
            'members' => $db->fetchAll('SELECT id, first_name, last_name, email, department, status, last_login_at FROM users WHERE institution_id = ? ORDER BY first_name, last_name LIMIT 500', [$iid]),
            'applications' => can('institution.own.applications') ? $db->fetchAll("SELECT * FROM membership_requests WHERE institution_id = ? AND status = 'pending' ORDER BY created_at", [$iid]) : [],
            'stats' => $db->fetch("SELECT COUNT(*) AS c, SUM(CASE WHEN status IN ('confirmed','completed') THEN total_minor ELSE 0 END) AS t FROM bookings WHERE institution_id = ? AND status <> 'draft'", [$iid]),
        ]);
    }

    public function bookings(): Response
    {
        $iid = $this->institutionId();
        $rows = App::db()->fetchAll(
            "SELECT b.code, b.status, b.check_in, b.check_out, b.nights, b.total_minor, b.currency, h.name AS hotel_name, CONCAT(u.first_name, ' ', u.last_name) AS member
             FROM bookings b JOIN hotels h ON h.id = b.hotel_id JOIN users u ON u.id = b.user_id
             WHERE b.institution_id = ? AND b.status <> 'draft' ORDER BY b.check_in DESC LIMIT 300",
            [$iid],
        );
        return $this->view('member/institution_bookings', ['title' => 'Kurum rezervasyonları', 'rows' => $rows]);
    }

    public function decideApplication(string $id): Response
    {
        $iid = $this->institutionId();
        $app = App::db()->fetch('SELECT * FROM membership_requests WHERE id = ?', [(int) $id]);
        if (!$app || (int) $app['institution_id'] !== $iid) {
            throw new HttpException(404);
        }
        $svc = new MembershipService(App::db());
        $u = $this->user();
        if (($this->request->post['karar'] ?? '') === 'onayla') {
            $r = $svc->approve((int) $id, (int) $u['id'], $iid);
            $this->flash('success', $r['link']['sent'] ? 'Başvuru onaylandı; parola oluşturma bağlantısı e-postayla gönderildi.' : 'Başvuru onaylandı. E-posta yapılandırılmadığından bağlantıyı üyeye iletin: ' . $r['link']['link']);
        } else {
            $svc->reject((int) $id, (int) $u['id'], mb_substr(trim((string) ($this->request->post['not'] ?? '')), 0, 500));
            $this->flash('success', 'Başvuru reddedildi.');
        }
        return $this->redirect('/kurumum');
    }
}
