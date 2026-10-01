<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Money;
use App\Core\Response;
use App\Exceptions\DomainException;
use App\Exceptions\ValidationException;
use App\Services\AuditService;
use App\Services\ImageService;

/**
 * Basit tanım tabloları için yapılandırma tabanlı yönetim:
 * Özellikler, Bölgeler, Konseptler, Kurum Tipleri, Fiyat Grupları, Sezonlar.
 */
final class LookupController extends AdminController
{
    private function config(): array
    {
        $seg = explode('/', trim($this->request->path, '/'))[1] ?? '';
        $regions = [];
        foreach ($this->db()->fetchAll('SELECT id, name FROM regions WHERE parent_id IS NULL ORDER BY sort') as $r) {
            $regions[$r['id']] = $r['name'];
        }
        $illus = ['coast' => 'Sahil (gün batımı)', 'bay' => 'Koy ve tekneler', 'oldtown' => 'Tarihi liman', 'beach' => 'Kumsal', 'mountain' => 'Dağ ve orman', 'cove' => 'Kayalık koy'];
        $all = [
            'ozellikler' => ['table' => 'amenities', 'title' => 'Özellikler', 'singular' => 'özellik', 'order' => 'sort, name', 'fields' => [
                'name' => ['Ad', 'text', 'required|max:120'],
                'scope' => ['Kapsam', 'select', 'required|in:hotel,room,both', ['hotel' => 'Otel', 'room' => 'Oda', 'both' => 'Otel ve oda']],
                'filter_key' => ['Arama filtresi', 'select', 'nullable|in:beach,sea_front,pool,aquapark,spa,kids,accessible', ['beach' => 'Plaj', 'sea_front' => 'Denize sıfır', 'pool' => 'Havuz', 'aquapark' => 'Aquapark', 'spa' => 'Spa', 'kids' => 'Çocuk dostu', 'accessible' => 'Engelli dostu']],
                'icon' => ['İkon', 'select', 'nullable|max:40', array_combine(\App\Support\Icons::names(), \App\Support\Icons::names())],
                'sort' => ['Sıra', 'number', 'nullable|int'], 'is_active' => ['Aktif', 'check', 'bool'],
            ]],
            'bolgeler' => ['table' => 'regions', 'title' => 'Bölgeler', 'singular' => 'bölge', 'order' => 'COALESCE(parent_id, id), parent_id IS NOT NULL, sort', 'image' => 'regions', 'fields' => [
                'name' => ['Ad', 'text', 'required|max:120'], 'slug' => ['Kısa ad', 'text', 'required|slug|max:140'],
                'parent_id' => ['Üst bölge', 'select', 'nullable|int', $regions],
                'description' => ['Açıklama', 'text', 'nullable|max:500'],
                'latitude' => ['Enlem', 'text', 'nullable|decimal_coord'], 'longitude' => ['Boylam', 'text', 'nullable|decimal_coord'],
                'illustration' => ['Temsili illüstrasyon (görsel yoksa)', 'select', 'nullable|in:coast,bay,oldtown,beach,mountain,cove', $illus],
                'sort' => ['Sıra', 'number', 'nullable|int'], 'is_active' => ['Aktif', 'check', 'bool'], 'show_on_home' => ['Ana sayfada göster', 'check', 'bool'],
            ]],
            'konseptler' => ['table' => 'concepts', 'title' => 'Konseptler', 'singular' => 'konsept', 'order' => 'sort', 'fields' => [
                'name' => ['Ad', 'text', 'required|max:100'], 'code' => ['Kod (AI, BB…)', 'text', 'required|max:10'],
                'description' => ['Açıklama', 'text', 'nullable|max:255'], 'sort' => ['Sıra', 'number', 'nullable|int'], 'is_active' => ['Aktif', 'check', 'bool'],
            ]],
            'kurum-tipleri' => ['table' => 'institution_types', 'title' => 'Kurum Tipleri', 'singular' => 'kurum tipi', 'order' => 'sort, name', 'fields' => [
                'name' => ['Ad', 'text', 'required|max:120'], 'sort' => ['Sıra', 'number', 'nullable|int'], 'is_active' => ['Aktif', 'check', 'bool'],
            ]],
            'fiyat-gruplari' => ['table' => 'price_groups', 'title' => 'Fiyat Grupları', 'singular' => 'fiyat grubu', 'order' => 'sort, name', 'fields' => [
                'name' => ['Ad', 'text', 'required|max:120'], 'description' => ['Açıklama', 'text', 'nullable|max:255'],
                'discount_bp' => ['Grup indirimi (%) — boşsa genel indirim', 'percent', 'nullable|percent'],
                'sort' => ['Sıra', 'number', 'nullable|int'], 'is_active' => ['Aktif', 'check', 'bool'],
            ]],
            'sezonlar' => ['table' => 'seasons', 'title' => 'Sezonlar ve Özel Dönemler', 'singular' => 'dönem', 'order' => 'date_from', 'fields' => [
                'name' => ['Ad', 'text', 'required|max:120'], 'kind' => ['Tür', 'select', 'required|in:season,holiday,special', ['season' => 'Sezon', 'holiday' => 'Bayram / tatil', 'special' => 'Özel dönem']],
                'date_from' => ['Başlangıç', 'date', 'required|date'], 'date_to' => ['Bitiş', 'date', 'required|date'], 'is_active' => ['Aktif', 'check', 'bool'],
            ]],
        ];
        $cfg = $all[$seg] ?? null;
        $this->notFoundUnless($cfg);
        return $cfg + ['seg' => $seg];
    }

    public function index(): Response
    {
        $cfg = $this->config();
        $rows = $this->db()->fetchAll('SELECT * FROM ' . $cfg['table'] . ' ORDER BY ' . $cfg['order']);
        $editId = $this->request->int('duzenle');
        $edit = $editId ? $this->db()->fetch('SELECT * FROM ' . $cfg['table'] . ' WHERE id = ?', [$editId]) : null;
        return $this->admin('lookup', ['title' => $cfg['title'], 'cfg' => $cfg, 'rows' => $rows, 'edit' => $edit]);
    }

    public function save(): Response
    {
        $cfg = $this->config();
        $rules = [];
        $labels = [];
        foreach ($cfg['fields'] as $k => $f) {
            $rules[$k] = $f[2];
            $labels[$k] = $f[0];
        }
        $d = $this->validate($rules, $labels);
        foreach ($cfg['fields'] as $k => $f) {
            if ($f[1] === 'number') {
                $d[$k] = (int) ($d[$k] ?? 0);
            }
            if (in_array($k, ['latitude', 'longitude'], true) && $d[$k] !== null) {
                $d[$k] = str_replace(',', '.', (string) $d[$k]);
            }
        }
        if (isset($d['date_from'], $d['date_to']) && $d['date_to'] < $d['date_from']) {
            throw new ValidationException(['date_to' => 'Bitiş tarihi başlangıçtan önce olamaz.']);
        }
        if ($cfg['table'] === 'concepts') {
            $d['code'] = strtoupper($d['code']);
        }
        $id = $this->request->int('id');
        if ($id && $cfg['table'] === 'regions' && (int) ($d['parent_id'] ?? 0) === $id) {
            throw new ValidationException(['parent_id' => 'Bölge kendisinin alt bölgesi olamaz.']);
        }
        $file = $this->request->file('image');
        if (!empty($cfg['image']) && $file) {
            $img = ImageService::store($file, $cfg['image']);
            $d['image_path'] = $img['key'];
        }
        if (!empty($cfg['image']) && $this->request->bool('remove_image')) {
            $d['image_path'] = null;
        }
        try {
            if ($id) {
                $old = $this->db()->fetch('SELECT * FROM ' . $cfg['table'] . ' WHERE id = ?', [$id]);
                $this->notFoundUnless($old);
                $this->db()->update($cfg['table'], $d, ['id' => $id]);
                AuditService::log($cfg['table'] . '.update', $cfg['table'], $id, $old, $d);
            } else {
                $id = $this->db()->insert($cfg['table'], $d);
                AuditService::log($cfg['table'] . '.create', $cfg['table'], $id, null, $d);
            }
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new DomainException('Bu kayıt zaten mevcut (ad veya kısa ad benzersiz olmalıdır).');
            }
            throw $e;
        }
        $this->flash('success', ucfirst($cfg['singular']) . ' kaydedildi.');
        return $this->redirect('/yonetim/' . $cfg['seg']);
    }

    public function delete(string $id): Response
    {
        $cfg = $this->config();
        $old = $this->db()->fetch('SELECT * FROM ' . $cfg['table'] . ' WHERE id = ?', [(int) $id]);
        $this->notFoundUnless($old);
        $inUse = match ($cfg['table']) {
            'regions' => (int) $this->db()->value('SELECT COUNT(*) FROM hotels WHERE region_id = ?', [$id]) + (int) $this->db()->value('SELECT COUNT(*) FROM regions WHERE parent_id = ?', [$id]),
            'concepts' => (int) $this->db()->value('SELECT (SELECT COUNT(*) FROM hotels WHERE concept_id = ?) + (SELECT COUNT(*) FROM rate_plans WHERE concept_id = ?)', [$id, $id]),
            'institution_types' => (int) $this->db()->value('SELECT COUNT(*) FROM institutions WHERE institution_type_id = ?', [$id]),
            'price_groups' => (int) $this->db()->value('SELECT COUNT(*) FROM institutions WHERE price_group_id = ?', [$id]),
            'seasons' => (int) $this->db()->value('SELECT COUNT(*) FROM rate_rules WHERE season_id = ?', [$id]),
            default => 0,
        };
        if ($inUse > 0) {
            throw new DomainException('Bu kayıt kullanımda olduğu için silinemez. Bunun yerine pasif yapabilirsiniz.');
        }
        $this->db()->delete($cfg['table'], ['id' => (int) $id]);
        AuditService::log($cfg['table'] . '.delete', $cfg['table'], (int) $id, $old, null);
        $this->flash('success', 'Kayıt silindi.');
        return $this->redirect('/yonetim/' . $cfg['seg']);
    }

    public static function displayValue(string $type, mixed $v, array $field): string
    {
        return match ($type) {
            'check' => (int) $v ? 'Evet' : 'Hayır',
            'select' => (string) ($field[3][$v] ?? ($v ?? '—')),
            'percent' => $v === null ? '—' : Money::percent((int) $v),
            'date' => tr_date($v),
            default => (string) ($v ?? '—'),
        };
    }
}
