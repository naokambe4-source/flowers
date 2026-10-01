<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Response;
use App\Exceptions\DomainException;
use App\Services\AuditService;

/** Rol × izin matrisi. Super Admin her zaman tüm yetkilere sahiptir ve değiştirilemez. */
final class RoleController extends AdminController
{
    public function index(): Response
    {
        $db = $this->db();
        $map = [];
        foreach ($db->fetchAll('SELECT role_id, permission_id FROM role_permission') as $rp) {
            $map[(int) $rp['role_id']][(int) $rp['permission_id']] = true;
        }
        return $this->admin('roles', [
            'title' => 'Yetkiler', 'roles' => $db->fetchAll('SELECT * FROM roles ORDER BY sort'),
            'permissions' => $db->fetchAll('SELECT * FROM permissions ORDER BY sort'), 'map' => $map,
        ]);
    }

    public function save(): Response
    {
        $db = $this->db();
        $matrix = $this->request->arr('perm');
        $roles = $db->fetchAll("SELECT * FROM roles WHERE slug <> 'super_admin'");
        $permIds = array_map('intval', $db->column('SELECT id FROM permissions'));
        $before = [];
        foreach ($db->fetchAll('SELECT role_id, permission_id FROM role_permission') as $rp) {
            $before[$rp['role_id']][] = (int) $rp['permission_id'];
        }
        $db->transaction(function ($db) use ($roles, $matrix, $permIds): void {
            foreach ($roles as $r) {
                $db->delete('role_permission', ['role_id' => $r['id']]);
                foreach (array_map('intval', array_keys((array) ($matrix[$r['id']] ?? []))) as $pid) {
                    if (in_array($pid, $permIds, true)) {
                        $db->insert('role_permission', ['role_id' => $r['id'], 'permission_id' => $pid]);
                    }
                }
            }
            // Yönetim paneline erişimi olan en az bir rol (super admin dışında) olması zorunlu değil; super admin her zaman tam yetkili.
        });
        $staffNoAccess = (int) $db->value("SELECT COUNT(*) FROM roles r WHERE r.slug = 'system_admin' AND NOT EXISTS (SELECT 1 FROM role_permission rp JOIN permissions p ON p.id = rp.permission_id WHERE rp.role_id = r.id AND p.slug = 'admin.access')");
        AuditService::log('roles.matrix', 'roles', null, $before, $matrix);
        $this->flash('success', 'Yetki matrisi kaydedildi.' . ($staffNoAccess ? ' Uyarı: Sistem Yöneticisi rolünün yönetim paneline erişimi kapalı.' : ''));
        return $this->redirect('/yonetim/yetkiler');
    }
}
