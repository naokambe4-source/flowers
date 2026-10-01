<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Response;
use App\Services\AuditService;
use App\Services\ImageService;

final class InstitutionController extends AdminController
{
    public function index(): Response
    {
        $q = trim((string) ($this->request->query['ara'] ?? ''));
        $p = [];
        $where = '1=1';
        if ($q !== '') {
            $where = '(i.name LIKE :q OR i.contact_name LIKE :q2 OR i.email LIKE :q3)';
            $p = ['q' => "%$q%", 'q2' => "%$q%", 'q3' => "%$q%"];
        }
        $data = $this->paginate(
            'i.*, t.name AS type_name, pg.name AS group_name, (SELECT COUNT(*) FROM users u WHERE u.institution_id = i.id) AS members',
            "FROM institutions i LEFT JOIN institution_types t ON t.id = i.institution_type_id LEFT JOIN price_groups pg ON pg.id = i.price_group_id WHERE $where",
            $p,
            'i.name',
        );
        return $this->admin('institutions', $data + ['title' => 'Kurumlar', 'q' => $q, 'query' => $this->queryWithout()] + $this->formOptions() + ['edit' => null]);
    }

    private function formOptions(): array
    {
        return [
            'types' => options($this->db()->fetchAll('SELECT id, name FROM institution_types WHERE is_active = 1 ORDER BY sort')),
            'groups' => options($this->db()->fetchAll('SELECT id, name FROM price_groups WHERE is_active = 1 ORDER BY sort')),
        ];
    }

    public function edit(string $id): Response
    {
        $i = $this->db()->fetch('SELECT * FROM institutions WHERE id = ?', [(int) $id]);
        $this->notFoundUnless($i);
        $stats = $this->db()->fetch("SELECT COUNT(*) AS c, COALESCE(SUM(CASE WHEN status IN ('confirmed','completed') THEN total_minor END), 0) AS t FROM bookings WHERE institution_id = ? AND status <> 'draft'", [$i['id']]);
        $members = $this->db()->fetchAll('SELECT u.id, u.first_name, u.last_name, u.email, u.status, r.name AS role FROM users u JOIN roles r ON r.id = u.role_id WHERE u.institution_id = ? ORDER BY u.first_name LIMIT 200', [$i['id']]);
        return $this->admin('institution_edit', ['title' => $i['name'], 'edit' => $i, 'stats' => $stats, 'members' => $members] + $this->formOptions());
    }

    public function save(): Response
    {
        $d = $this->validate([
            'name' => 'required|min:2|max:200', 'institution_type_id' => 'nullable|int', 'contact_name' => 'nullable|max:150', 'phone' => 'nullable|phone',
            'email' => 'nullable|email|max:190', 'price_group_id' => 'nullable|int', 'discount_bp' => 'nullable|percent', 'is_active' => 'bool', 'admin_notes' => 'nullable|max:5000',
        ], ['name' => 'Kurum adı', 'contact_name' => 'Yetkili kişi', 'discount_bp' => 'Kuruma özel indirim']);
        if ($f = $this->request->file('logo')) {
            $d['logo_path'] = ImageService::store($f, 'institutions')['key'];
        }
        $id = $this->request->int('id');
        try {
            if ($id) {
                $old = $this->db()->fetch('SELECT * FROM institutions WHERE id = ?', [$id]);
                $this->notFoundUnless($old);
                $this->db()->update('institutions', $d, ['id' => $id]);
                AuditService::log('institution.update', 'institution', $id, $old, $d);
            } else {
                $id = $this->db()->insert('institutions', $d);
                AuditService::log('institution.create', 'institution', $id, null, $d);
            }
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new \App\Exceptions\ValidationException(['name' => 'Bu adla bir kurum zaten kayıtlı.']);
            }
            throw $e;
        }
        $this->flash('success', 'Kurum kaydedildi.' . (!$d['is_active'] ? ' Pasif kurumun üyeleri giriş yapamaz.' : ''));
        return $this->redirect('/yonetim/kurumlar/' . $id);
    }
}
