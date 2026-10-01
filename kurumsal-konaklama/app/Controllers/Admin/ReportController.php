<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Response;
use App\Services\AuditService;
use App\Services\ExportService;
use App\Services\ReportService;

final class ReportController extends AdminController
{
    private function filters(): array
    {
        $q = $this->request->query;
        $date = static fn ($v) => is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : null;
        return [
            'from' => $date($q['bas'] ?? null) ?? date('Y-m-01'), 'to' => $date($q['bit'] ?? null) ?? date('Y-m-t'),
            'date_type' => ($q['tarih'] ?? 'created') === 'checkin' ? 'checkin' : 'created',
            'hotel_id' => (int) ($q['otel'] ?? 0) ?: null, 'institution_id' => (int) ($q['kurum'] ?? 0) ?: null,
            'region_id' => (int) ($q['bolge'] ?? 0) ?: null, 'user_id' => (int) ($q['uye'] ?? 0) ?: null,
            'status' => in_array($q['durum'] ?? '', ['requested', 'pending', 'confirmed', 'completed', 'cancelled'], true) ? $q['durum'] : null,
        ];
    }

    public function index(): Response
    {
        $f = $this->filters();
        $group = array_key_exists($this->request->query['grup'] ?? '', ReportService::GROUPS) ? $this->request->query['grup'] : 'hotel';
        $svc = new ReportService($this->db());
        return $this->admin('reports', [
            'title' => 'Raporlar', 'f' => $f, 'group' => $group, 'summary' => $svc->summary($f), 'grouped' => $svc->grouped($f, $group),
            'hotels' => options($this->db()->fetchAll('SELECT id, name FROM hotels ORDER BY name')),
            'institutions' => options($this->db()->fetchAll('SELECT id, name FROM institutions ORDER BY name')),
            'regions' => options($this->db()->fetchAll('SELECT id, name FROM regions ORDER BY sort')),
            'query' => $this->request->query,
        ]);
    }

    public function export(): Response
    {
        $f = $this->filters();
        $rows = (new ReportService($this->db()))->rows($f);
        $headers = ['Kod', 'Durum', 'Oluşturma', 'Giriş', 'Çıkış', 'Gece', 'Oda', 'Yetişkin', 'Çocuk', 'Otel', 'Bölge', 'Kurum', 'Üye', 'Kaynak toplam (TL)', 'İndirim (TL)', 'Toplam (TL)', 'Doğrulanmış tasarruf (TL)', 'Para birimi', 'Otel teyit no', 'İptal nedeni'];
        $fmt = static fn ($m) => $m === null ? '' : number_format(intdiv((int) $m, 100), 0, '', '') . ',' . str_pad((string) (abs((int) $m) % 100), 2, '0', STR_PAD_LEFT);
        $data = array_map(static fn ($r) => [
            $r['code'], status_label($r['status']), $r['created_at'], $r['check_in'], $r['check_out'], (int) $r['nights'], (int) $r['rooms_count'], (int) $r['adults'], (int) $r['children'],
            $r['hotel'], $r['region'], $r['institution'], $r['member'], $fmt($r['source_total_minor']), $fmt($r['discount_minor']), $fmt($r['total_minor']), $fmt($r['verified_savings_minor']),
            $r['currency'], $r['hotel_confirmation_no'], $r['cancel_reason'],
        ], $rows);
        $format = ($this->request->query['format'] ?? 'csv') === 'xlsx' ? 'xlsx' : 'csv';
        AuditService::log('report.export', 'report', null, null, $f + ['format' => $format, 'rows' => count($data)]);
        $name = 'rezervasyon-raporu-' . $f['from'] . '_' . $f['to'] . '.' . $format;
        return $format === 'xlsx'
            ? Response::download(ExportService::xlsx($headers, $data), $name, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            : Response::download(ExportService::csv($headers, $data), $name, 'text/csv; charset=utf-8');
    }
}
