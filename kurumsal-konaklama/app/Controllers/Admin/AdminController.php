<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\App;
use App\Core\Database;
use App\Core\Response;

abstract class AdminController extends Controller
{
    protected function db(): Database
    {
        return App::db();
    }

    protected function admin(string $template, array $data = []): Response
    {
        return $this->view('admin/' . $template, $data, 'admin');
    }

    /** @return array{rows:array, page:array} */
    protected function paginate(string $select, string $from, array $params, string $order, int $per = 25): array
    {
        $page = $this->page();
        $total = (int) $this->db()->value("SELECT COUNT(*) $from", $params);
        $pages = max(1, (int) ceil($total / $per));
        $page = min($page, $pages);
        $rows = $this->db()->fetchAll("SELECT $select $from ORDER BY $order LIMIT $per OFFSET " . (($page - 1) * $per), $params);
        return ['rows' => $rows, 'page' => ['current' => $page, 'pages' => $pages, 'total' => $total]];
    }

    protected function queryWithout(string ...$keys): array
    {
        $q = $this->request->query;
        foreach (array_merge($keys, ['sayfa']) as $k) {
            unset($q[$k]);
        }
        return $q;
    }

    protected function uid(): int
    {
        return (int) $this->user()['id'];
    }
}
