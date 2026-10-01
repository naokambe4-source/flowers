<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Response;

final class AuditController extends AdminController
{
    public function index(): Response
    {
        $f = $this->request->query;
        $where = ['1=1'];
        $p = [];
        if (!empty($f['islem'])) {
            $where[] = 'a.action LIKE :a';
            $p['a'] = '%' . $f['islem'] . '%';
        }
        if (!empty($f['kullanici'])) {
            $where[] = 'a.user_id = :u';
            $p['u'] = (int) $f['kullanici'];
        }
        if (!empty($f['varlik'])) {
            $where[] = 'a.entity_type = :e';
            $p['e'] = (string) $f['varlik'];
        }
        $data = $this->paginate("a.*, CONCAT(u.first_name, ' ', u.last_name) AS who", 'FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id WHERE ' . implode(' AND ', $where), $p, 'a.id DESC', 50);
        return $this->admin('audit', $data + ['title' => 'Audit Log', 'f' => $f, 'query' => $this->queryWithout(), 'entities' => array_combine($e = $this->db()->column('SELECT DISTINCT entity_type FROM audit_logs WHERE entity_type IS NOT NULL ORDER BY entity_type'), $e)]);
    }
}
