<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Response;
use App\Core\Validator;
use App\Exceptions\DomainException;
use App\Repositories\HotelRepository;
use App\Services\AuditService;
use App\Services\ImageService;

/**
 * Otel ekleme sihirbazı (7 adım): Temel Bilgiler → Konum ve Konsept → Fotoğraflar → Odalar →
 * Politikalar → Fiyat ve Kontenjan → Önizleme ve Yayına Alma. Her adım taslak olarak kaydedilir.
 */
final class HotelController extends AdminController
{
    public const STEPS = [1 => 'Temel Bilgiler', 2 => 'Konum ve Konsept', 3 => 'Fotoğraflar', 4 => 'Odalar', 5 => 'Politikalar', 6 => 'Fiyat ve Kontenjan', 7 => 'Önizleme ve Yayın'];

    public function index(): Response
    {
        $q = $this->request->query;
        $where = ['1=1'];
        $p = [];
        if (!empty($q['ara'])) {
            $where[] = 'h.name LIKE :q';
            $p['q'] = '%' . $q['ara'] . '%';
        }
        if (!empty($q['bolge'])) {
            $where[] = '(h.region_id = :r OR rg.parent_id = :r2)';
            $p['r'] = $p['r2'] = (int) $q['bolge'];
        }
        if (!empty($q['durum'])) {
            $where[] = 'h.status = :s';
            $p['s'] = (string) $q['durum'];
        }
        $data = $this->paginate(
            'h.id, h.name, h.slug, h.stars, h.status, h.booking_mode, h.is_contracted, h.is_featured, h.wizard_step, h.cover_image_id, h.updated_at, rg.name AS region_name,
             (SELECT COUNT(*) FROM rooms r WHERE r.hotel_id = h.id) AS room_count, (SELECT COUNT(*) FROM hotel_images i WHERE i.hotel_id = h.id) AS image_count',
            'FROM hotels h LEFT JOIN regions rg ON rg.id = h.region_id WHERE ' . implode(' AND ', $where),
            $p,
            'h.updated_at DESC',
        );
        return $this->admin('hotels', $data + ['title' => 'Oteller', 'f' => $q, 'query' => $this->queryWithout(), 'regions' => $this->regionOptions()]);
    }

    private function regionOptions(): array
    {
        $o = [];
        foreach ($this->db()->fetchAll('SELECT id, name, parent_id FROM regions ORDER BY sort') as $r) {
            $o[$r['id']] = ($r['parent_id'] ? '— ' : '') . $r['name'];
        }
        return $o;
    }

    private function find(int $id): array
    {
        $h = (new HotelRepository($this->db()))->find($id);
        $this->notFoundUnless($h);
        return $h;
    }

    public function create(): Response
    {
        return $this->admin('hotel_step', ['title' => 'Yeni otel', 'hotel' => null, 'step' => 1] + $this->stepData(null, 1));
    }

    private function step1Rules(): array
    {
        return [
            'name' => 'required|min:3|max:200', 'slug' => 'required|slug|max:220', 'stars' => 'nullable|int|max:5',
            'short_description' => 'nullable|max:500', 'description' => 'nullable|max:20000', 'booking_mode' => 'required|in:instant,request,offer',
            'is_contracted' => 'bool', 'contract_valid_until' => 'nullable|date', 'is_featured' => 'bool', 'featured_sort' => 'nullable|int', 'admin_notes' => 'nullable|max:5000',
        ];
    }

    public function store(): Response
    {
        $d = $this->validate($this->step1Rules(), ['stars' => 'Yıldız', 'short_description' => 'Kısa açıklama', 'booking_mode' => 'Rezervasyon modu']);
        if ($this->db()->value('SELECT 1 FROM hotels WHERE slug = ?', [$d['slug']])) {
            throw new \App\Exceptions\ValidationException(['slug' => 'Bu kısa adres başka bir otelde kullanılıyor.']);
        }
        $d['featured_sort'] = (int) ($d['featured_sort'] ?? 0);
        $id = $this->db()->insert('hotels', $d + ['status' => 'draft', 'wizard_step' => 2, 'created_by' => $this->uid()]);
        AuditService::log('hotel.create', 'hotel', $id, null, $d);
        $this->flash('success', 'Otel taslak olarak oluşturuldu. Konum ve konsept bilgilerini girin.');
        return $this->redirect('/yonetim/oteller/' . $id . '/adim/2');
    }

    public function step(string $id, string $step): Response
    {
        $h = $this->find((int) $id);
        $s = (int) $step;
        return $this->admin('hotel_step', ['title' => $h['name'] . ' · ' . self::STEPS[$s], 'hotel' => $h, 'step' => $s, 'withMap' => $s === 2] + $this->stepData($h, $s));
    }

    private function stepData(?array $h, int $step): array
    {
        $db = $this->db();
        $data = ['steps' => self::STEPS, 'missing' => $h ? (new HotelRepository($db))->missingForPublish($h) : []];
        switch ($step) {
            case 2:
                $data['regions'] = $this->regionOptions();
                $data['concepts'] = options($db->fetchAll('SELECT id, name FROM concepts WHERE is_active = 1 ORDER BY sort'));
                $data['amenities'] = $db->fetchAll("SELECT * FROM amenities WHERE is_active = 1 AND scope IN ('hotel','both') ORDER BY sort");
                $data['selected'] = $h ? array_map('intval', $db->column('SELECT amenity_id FROM hotel_amenity WHERE hotel_id = ?', [$h['id']])) : [];
                break;
            case 3:
                $data['images'] = $db->fetchAll('SELECT * FROM hotel_images WHERE hotel_id = ? ORDER BY sort, id', [$h['id']]);
                break;
            case 4:
                $rooms = $db->fetchAll('SELECT * FROM rooms WHERE hotel_id = ? ORDER BY sort, id', [$h['id']]);
                $ids = array_map(static fn ($r) => (int) $r['id'], $rooms);
                $data['rooms'] = $rooms;
                $data['roomImages'] = (new HotelRepository($db))->roomImages($ids);
                $data['roomAmenityIds'] = [];
                foreach ($ids ? $db->fetchAll('SELECT room_id, amenity_id FROM room_amenity WHERE room_id IN (' . implode(',', $ids) . ')') : [] as $ra) {
                    $data['roomAmenityIds'][(int) $ra['room_id']][] = (int) $ra['amenity_id'];
                }
                $data['roomAmenities'] = $db->fetchAll("SELECT * FROM amenities WHERE is_active = 1 AND scope IN ('room','both') ORDER BY sort");
                break;
            case 6:
                $data['plans'] = $db->fetchAll(
                    "SELECT rp.*, r.name AS room_name, c.name AS concept_name,
                        (SELECT COUNT(*) FROM rates x WHERE x.rate_plan_id = rp.id AND x.stay_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)) AS rate_days
                     FROM rate_plans rp JOIN rooms r ON r.id = rp.room_id LEFT JOIN concepts c ON c.id = rp.concept_id WHERE rp.hotel_id = ? ORDER BY r.sort, rp.id",
                    [$h['id']],
                );
                $data['inv'] = $db->fetchAll(
                    "SELECT r.id, r.name, (SELECT COUNT(*) FROM inventory i WHERE i.room_id = r.id AND i.stay_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)) AS days,
                        (SELECT COALESCE(SUM(i.total_units - i.booked_units), 0) FROM inventory i WHERE i.room_id = r.id AND i.stay_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND i.is_open = 1) AS free30
                     FROM rooms r WHERE r.hotel_id = ? ORDER BY r.sort",
                    [$h['id']],
                );
                break;
        }
        return $data;
    }

    public function saveStep(string $id, string $step): Response
    {
        $h = $this->find((int) $id);
        $s = (int) $step;
        $db = $this->db();
        $labels = ['region_id' => 'Bölge', 'latitude' => 'Enlem', 'longitude' => 'Boylam', 'address' => 'Adres', 'video_url' => 'Video bağlantısı', 'check_in_time' => 'Giriş saati', 'check_out_time' => 'Çıkış saati', 'vat_bp' => 'KDV oranı', 'accommodation_tax_bp' => 'Konaklama vergisi'];
        $update = [];
        switch ($s) {
            case 1:
                $update = $this->validate($this->step1Rules(), $labels);
                if ($db->value('SELECT 1 FROM hotels WHERE slug = ? AND id <> ?', [$update['slug'], $h['id']])) {
                    throw new \App\Exceptions\ValidationException(['slug' => 'Bu kısa adres başka bir otelde kullanılıyor.']);
                }
                $update['featured_sort'] = (int) ($update['featured_sort'] ?? 0);
                break;
            case 2:
                $update = $this->validate([
                    'region_id' => 'required|int', 'district' => 'nullable|max:100', 'neighborhood' => 'nullable|max:120', 'address' => 'nullable|max:500',
                    'latitude' => 'nullable|decimal_coord', 'longitude' => 'nullable|decimal_coord', 'concept_id' => 'nullable|int',
                    'beach_type' => 'nullable|in:kum,cakil,kum_cakil,iskele,platform,yok', 'beach_info' => 'nullable|max:500',
                    'sea_distance_m' => 'nullable|int|max:100000', 'airport_distance_km' => 'nullable|numeric', 'center_distance_km' => 'nullable|numeric',
                    'video_url' => 'nullable|url|max:255',
                ], $labels);
                foreach (['airport_distance_km', 'center_distance_km'] as $k) {
                    $update[$k] = $update[$k] === null ? null : str_replace(',', '.', (string) $update[$k]);
                }
                if (($update['latitude'] === null) !== ($update['longitude'] === null)) {
                    throw new \App\Exceptions\ValidationException(['latitude' => 'Enlem ve boylam birlikte girilmelidir.']);
                }
                if ($update['latitude'] !== null && ((float) $update['latitude'] < 35.5 || (float) $update['latitude'] > 37.6 || (float) $update['longitude'] < 29.2 || (float) $update['longitude'] > 32.7)) {
                    throw new \App\Exceptions\ValidationException(['latitude' => 'Koordinatlar Antalya il sınırları dışında görünüyor. Lütfen kontrol edin.']);
                }
                $amen = array_map('intval', $this->request->arr('amenities'));
                $db->transaction(function ($db) use ($h, $amen): void {
                    $db->delete('hotel_amenity', ['hotel_id' => $h['id']]);
                    foreach (array_unique($amen) as $a) {
                        $db->query('INSERT IGNORE INTO hotel_amenity (hotel_id, amenity_id) SELECT ?, id FROM amenities WHERE id = ?', [$h['id'], $a]);
                    }
                });
                break;
            case 3:
                foreach ((array) $this->request->arr('images') as $imgId => $vals) {
                    $db->update('hotel_images', ['caption' => mb_substr(trim((string) ($vals['caption'] ?? '')), 0, 200) ?: null, 'sort' => (int) ($vals['sort'] ?? 0)], ['id' => (int) $imgId, 'hotel_id' => $h['id']]);
                }
                $cover = $this->request->int('cover');
                if ($cover && $db->value('SELECT 1 FROM hotel_images WHERE id = ? AND hotel_id = ?', [$cover, $h['id']])) {
                    $update['cover_image_id'] = $cover;
                }
                break;
            case 5:
                $update = $this->validate([
                    'check_in_time' => 'nullable|time', 'check_out_time' => 'nullable|time', 'child_policy' => 'nullable|max:5000', 'pet_policy' => 'nullable|max:500',
                    'cancellation_policy' => 'nullable|max:5000', 'payment_policy' => 'nullable|max:5000', 'important_info' => 'nullable|max:5000',
                    'vat_bp' => 'nullable|percent', 'accommodation_tax_bp' => 'nullable|percent',
                ], $labels);
                break;
        }
        $update['wizard_step'] = max((int) $h['wizard_step'], min(7, $s + 1));
        $db->update('hotels', $update, ['id' => $h['id']]);
        AuditService::log('hotel.update.step' . $s, 'hotel', (int) $h['id'], $h, $update);
        $next = $this->request->str('next') === '1' ? min(7, $s + 1) : $s;
        $this->flash('success', self::STEPS[$s] . ' kaydedildi.');
        return $this->redirect('/yonetim/oteller/' . $h['id'] . '/adim/' . $next);
    }

    public function uploadImages(string $id): Response
    {
        $h = $this->find((int) $id);
        $files = $this->request->fileList('photos');
        if (!$files) {
            throw new DomainException('Yüklenecek fotoğraf seçin.');
        }
        if (!$this->request->bool('owner_confirm')) {
            throw new DomainException('Fotoğrafların bu tesise ait ve kullanım hakkına sahip olduğunuzu onaylayın.');
        }
        $sort = (int) $this->db()->value('SELECT COALESCE(MAX(sort), 0) FROM hotel_images WHERE hotel_id = ?', [$h['id']]);
        $ok = 0;
        $errors = [];
        foreach (array_slice($files, 0, 20) as $f) {
            try {
                $img = ImageService::store($f, 'hotels');
                $imgId = $this->db()->insert('hotel_images', ['hotel_id' => $h['id'], 'storage_key' => $img['key'], 'original_name' => $img['original'], 'mime' => $img['mime'], 'width' => $img['width'], 'height' => $img['height'], 'bytes' => $img['bytes'], 'sort' => ++$sort]);
                if (!$h['cover_image_id']) {
                    $this->db()->update('hotels', ['cover_image_id' => $imgId], ['id' => $h['id']]);
                    $h['cover_image_id'] = $imgId;
                }
                $ok++;
            } catch (DomainException $e) {
                $errors[] = $f['name'] . ': ' . $e->getMessage();
            }
        }
        AuditService::log('hotel.images.upload', 'hotel', (int) $h['id'], null, ['count' => $ok]);
        if ($ok) {
            $this->flash('success', "$ok fotoğraf yüklendi.");
        }
        foreach ($errors as $e) {
            $this->flash('error', $e);
        }
        return $this->redirect('/yonetim/oteller/' . $h['id'] . '/adim/3');
    }

    public function updateImage(string $id, string $imageId): Response
    {
        $h = $this->find((int) $id);
        $img = $this->db()->fetch('SELECT * FROM hotel_images WHERE id = ? AND hotel_id = ?', [(int) $imageId, $h['id']]);
        $this->notFoundUnless($img);
        if (($this->request->post['islem'] ?? '') === 'sil') {
            $this->db()->transaction(function ($db) use ($img, $h): void {
                if ((int) $h['cover_image_id'] === (int) $img['id']) {
                    $next = $db->value('SELECT id FROM hotel_images WHERE hotel_id = ? AND id <> ? ORDER BY sort LIMIT 1', [$h['id'], $img['id']]);
                    $db->update('hotels', ['cover_image_id' => $next ?: null], ['id' => $h['id']]);
                }
                $db->delete('hotel_images', ['id' => $img['id']]);
            });
            ImageService::delete('hotels', $img['storage_key']);
            AuditService::log('hotel.images.delete', 'hotel', (int) $h['id'], ['image' => $img['id']], null);
            $this->flash('success', 'Fotoğraf silindi.');
        }
        return $this->redirect('/yonetim/oteller/' . $h['id'] . '/adim/3');
    }

    public function publish(string $id): Response
    {
        $h = $this->find((int) $id);
        $action = (string) ($this->request->post['islem'] ?? '');
        $repo = new HotelRepository($this->db());
        if ($action === 'yayinla') {
            $missing = $repo->missingForPublish($h);
            if ($missing) {
                throw new DomainException('Yayına almadan önce tamamlayın: ' . implode(', ', $missing));
            }
            $this->db()->update('hotels', ['status' => 'published', 'published_at' => $h['published_at'] ?: date('Y-m-d H:i:s'), 'wizard_step' => 7], ['id' => $h['id']]);
            $this->flash('success', 'Otel yayına alındı ve üyelere görünür.');
        } elseif ($action === 'kaldir') {
            $this->db()->update('hotels', ['status' => 'unpublished'], ['id' => $h['id']]);
            $this->flash('success', 'Otel yayından kaldırıldı. Mevcut rezervasyonlar etkilenmez.');
        } else {
            throw new DomainException('Geçersiz işlem.');
        }
        AuditService::log('hotel.' . $action, 'hotel', (int) $h['id'], ['status' => $h['status']], null);
        return $this->back('/yonetim/oteller');
    }

    public function preview(string $id): Response
    {
        $h = $this->find((int) $id);
        return (new \App\Controllers\Member\HotelController($this->request))->renderDetail($h, true);
    }
}
