<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Response;
use App\Core\Session;
use App\Exceptions\DomainException;
use App\Exceptions\ValidationException;
use App\Services\AuditService;
use App\Services\AuthService;

final class UserController extends AdminController
{
    public const TYPES = ['personel' => 'Kurum personeli', 'uye' => 'Dernek / kurum üyesi', 'yonetici' => 'Kurum yöneticisi', 'misafir_kurum' => 'Yetkilendirilmiş diğer'];

    public function index(): Response
    {
        $f = $this->request->query;
        $where = ['1=1'];
        $p = [];
        if (!empty($f['ara'])) {
            $where[] = "(CONCAT(u.first_name, ' ', u.last_name) LIKE :q OR u.email LIKE :q2 OR u.phone LIKE :q3)";
            $p['q'] = $p['q2'] = $p['q3'] = '%' . $f['ara'] . '%';
        }
        foreach (['durum' => 'u.status', 'rol' => 'u.role_id', 'kurum' => 'u.institution_id'] as $k => $col) {
            if (!empty($f[$k])) {
                $where[] = "$col = :$k";
                $p[$k] = $f[$k];
            }
        }
        $data = $this->paginate(
            'u.*, r.name AS role_name, i.name AS institution_name',
            'FROM users u JOIN roles r ON r.id = u.role_id LEFT JOIN institutions i ON i.id = u.institution_id WHERE ' . implode(' AND ', $where),
            $p,
            'u.created_at DESC',
        );
        return $this->admin('users', $data + ['title' => 'Üyeler', 'f' => $f, 'query' => $this->queryWithout()] + $this->opts());
    }

    private function opts(): array
    {
        $roles = $this->db()->fetchAll('SELECT id, slug, name FROM roles ORDER BY sort');
        if (!Auth::isSuperAdmin()) {
            $roles = array_filter($roles, static fn ($r) => $r['slug'] !== 'super_admin');
        }
        return [
            'roles' => options($roles),
            'institutions' => options($this->db()->fetchAll('SELECT id, name FROM institutions ORDER BY name')),
        ];
    }

    public function create(): Response
    {
        return $this->admin('user_form', ['title' => 'Yeni üye', 'u' => null, 'defaultInst' => $this->request->int('kurum') ?: null] + $this->opts());
    }

    public function edit(string $id): Response
    {
        $u = $this->db()->fetch('SELECT u.*, r.slug AS role_slug FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ?', [(int) $id]);
        $this->notFoundUnless($u);
        $bookings = $this->db()->fetchAll("SELECT b.id, b.code, b.status, b.check_in, h.name AS hotel_name FROM bookings b JOIN hotels h ON h.id = b.hotel_id WHERE b.user_id = ? AND b.status <> 'draft' ORDER BY b.created_at DESC LIMIT 20", [$u['id']]);
        return $this->admin('user_form', ['title' => $u['first_name'] . ' ' . $u['last_name'], 'u' => $u, 'bookings' => $bookings, 'resetLink' => Session::pull('reset_link_' . $u['id'])] + $this->opts());
    }

    public function save(): Response
    {
        $d = $this->validate([
            'first_name' => 'required|min:2|max:80', 'last_name' => 'required|min:2|max:80', 'email' => 'required|email|max:190', 'phone' => 'nullable|phone',
            'institution_id' => 'nullable|int', 'department' => 'nullable|max:150', 'membership_type' => 'required|in:' . implode(',', array_keys(self::TYPES)),
            'role_id' => 'required|int', 'status' => 'required|in:pending,active,passive,suspended,rejected',
        ], ['role_id' => 'Rol', 'membership_type' => 'Üyelik tipi', 'department' => 'Departman', 'institution_id' => 'Kurum']);
        $role = $this->db()->fetch('SELECT * FROM roles WHERE id = ?', [$d['role_id']]);
        if (!$role || ($role['slug'] === 'super_admin' && !Auth::isSuperAdmin())) {
            throw new ValidationException(['role_id' => 'Bu rolü atama yetkiniz yok.']);
        }
        if (in_array($role['slug'], ['member', 'institution_manager'], true) && empty($d['institution_id'])) {
            throw new ValidationException(['institution_id' => 'Üye ve kurum yöneticisi bir kuruma bağlı olmalıdır.']);
        }
        $id = $this->request->int('id');
        try {
            if ($id) {
                $old = $this->db()->fetch('SELECT u.*, r.slug AS role_slug FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ?', [$id]);
                $this->notFoundUnless($old);
                if ($old['role_slug'] === 'super_admin' && !Auth::isSuperAdmin()) {
                    throw new DomainException('Super Admin hesabını yalnız Super Admin düzenleyebilir.');
                }
                if ($id === $this->uid() && ($d['status'] !== 'active' || (int) $d['role_id'] !== (int) $old['role_id'])) {
                    throw new DomainException('Kendi hesabınızın rolünü veya durumunu değiştiremezsiniz.');
                }
                if ($old['role_slug'] === 'super_admin' && $role['slug'] !== 'super_admin' && (int) $this->db()->value("SELECT COUNT(*) FROM users u JOIN roles r ON r.id = u.role_id WHERE r.slug = 'super_admin' AND u.status = 'active'") <= 1) {
                    throw new DomainException('Son aktif Super Admin hesabının rolü değiştirilemez.');
                }
                $this->db()->update('users', $d, ['id' => $id]);
                AuditService::log('user.update', 'user', $id, $old, $d);
                $this->flash('success', 'Üye güncellendi.');
            } else {
                $id = $this->db()->insert('users', $d + ['created_by' => $this->uid(), 'password_changed_at' => date('Y-m-d H:i:s')]);
                AuditService::log('user.create', 'user', $id, null, $d);
                if ($d['status'] === 'active' && $this->request->bool('send_invite')) {
                    $r = AuthService::sendPasswordLink(['id' => $id] + $d, 'invite');
                    if (!$r['sent']) {
                        Session::put('reset_link_' . $id, $r['link']);
                    }
                    $this->flash('success', $r['sent'] ? 'Üye oluşturuldu; davet e-postası kuyruğa alındı.' : 'Üye oluşturuldu. E-posta yapılandırılmadığı için parola bağlantısını aşağıdan kopyalayıp güvenli kanaldan iletin.');
                } else {
                    $this->flash('success', 'Üye oluşturuldu.');
                }
            }
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new ValidationException(['email' => 'Bu e-posta adresi başka bir hesapta kayıtlı.']);
            }
            throw $e;
        }
        return $this->redirect('/yonetim/uyeler/' . $id);
    }

    public function status(string $id): Response
    {
        $u = $this->db()->fetch('SELECT u.*, r.slug AS role_slug FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ?', [(int) $id]);
        $this->notFoundUnless($u);
        $to = (string) ($this->request->post['durum'] ?? '');
        if (!in_array($to, ['active', 'passive', 'suspended'], true)) {
            throw new DomainException('Geçersiz durum.');
        }
        if ((int) $u['id'] === $this->uid()) {
            throw new DomainException('Kendi hesabınızın durumunu değiştiremezsiniz.');
        }
        if ($u['role_slug'] === 'super_admin' && !Auth::isSuperAdmin()) {
            throw new DomainException('Super Admin hesabının durumunu yalnız Super Admin değiştirebilir.');
        }
        $this->db()->update('users', ['status' => $to], ['id' => $u['id']]);
        AuditService::log('user.status', 'user', (int) $u['id'], ['status' => $u['status']], ['status' => $to]);
        $this->flash('success', 'Hesap durumu: ' . status_label($to) . ($to !== 'active' ? '. Kullanıcının açık oturumları bir sonraki istekte sonlandırılır.' : ''));
        return $this->redirect('/yonetim/uyeler/' . $u['id']);
    }

    public function sendReset(string $id): Response
    {
        $u = $this->db()->fetch('SELECT * FROM users WHERE id = ?', [(int) $id]);
        $this->notFoundUnless($u);
        if ($u['status'] !== 'active') {
            throw new DomainException('Parola bağlantısı yalnız aktif hesaplara gönderilir.');
        }
        $r = AuthService::sendPasswordLink($u, $u['password_hash'] ? 'reset' : 'invite');
        AuditService::log('user.password_link', 'user', (int) $u['id'], null, ['sent' => $r['sent']]);
        if (!$r['sent']) {
            Session::put('reset_link_' . $u['id'], $r['link']);
        }
        $this->flash('success', $r['sent'] ? 'Parola bağlantısı e-posta kuyruğuna alındı.' : 'E-posta yapılandırılmadığı için bağlantı aşağıda gösteriliyor. Yalnız kişinin kendisine güvenli kanaldan iletin.');
        return $this->redirect('/yonetim/uyeler/' . $u['id']);
    }
}
