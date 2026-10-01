<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class HotelRepository
{
    /** Arama filtresinde kullanılan özellik anahtarları (yalnız bunlar sunulur). */
    public const FILTER_KEYS = [
        'beach' => 'Plaj', 'sea_front' => 'Denize sıfır', 'pool' => 'Havuz', 'aquapark' => 'Aquapark',
        'spa' => 'Spa', 'kids' => 'Çocuk dostu', 'accessible' => 'Engelli dostu',
    ];

    public function __construct(private readonly Database $db)
    {
    }

    /**
     * Yayındaki otelleri filtreler (fiyat filtresi hariç; fiyat sonrası uygulanır).
     * @return array[]
     */
    public function publishedCandidates(array $f): array
    {
        $where = ["h.status = 'published'"];
        $p = [];
        if (!empty($f['region_id'])) {
            $where[] = '(h.region_id = :rid OR r.parent_id = :rid2)';
            $p['rid'] = (int) $f['region_id'];
            $p['rid2'] = (int) $f['region_id'];
        }
        if (!empty($f['stars'])) {
            [$in, $ip] = Database::in(array_map('intval', (array) $f['stars']), 'st');
            $where[] = "h.stars IN $in";
            $p += $ip;
        }
        if (!empty($f['concept_id'])) {
            $where[] = '(h.concept_id = :cid OR EXISTS (SELECT 1 FROM rate_plans rp WHERE rp.hotel_id = h.id AND rp.is_active = 1 AND rp.concept_id = :cid2))';
            $p['cid'] = (int) $f['concept_id'];
            $p['cid2'] = (int) $f['concept_id'];
        }
        $codes = [];
        if (!empty($f['breakfast'])) {
            $codes = ['BB', 'HB', 'FB', 'AI', 'UAI'];
        }
        if (!empty($f['all_inclusive'])) {
            $codes = ['AI', 'UAI'];
        }
        if ($codes) {
            [$in, $ip] = Database::in($codes, 'cc');
            [$in2, $ip2] = Database::in($codes, 'cd');
            $where[] = "(EXISTS (SELECT 1 FROM concepts c1 WHERE c1.id = h.concept_id AND c1.code IN $in)
                        OR EXISTS (SELECT 1 FROM rate_plans rp2 JOIN concepts c2 ON c2.id = rp2.concept_id WHERE rp2.hotel_id = h.id AND rp2.is_active = 1 AND c2.code IN $in2))";
            $p += $ip + $ip2;
        }
        $keys = array_values(array_intersect((array) ($f['features'] ?? []), array_keys(self::FILTER_KEYS)));
        foreach ($keys as $i => $key) {
            $where[] = "EXISTS (SELECT 1 FROM hotel_amenity ha JOIN amenities a ON a.id = ha.amenity_id WHERE ha.hotel_id = h.id AND a.filter_key = :fk$i AND a.is_active = 1)";
            $p["fk$i"] = $key;
        }
        if (!empty($f['q'])) {
            $where[] = '(h.name LIKE :q OR h.district LIKE :q2)';
            $p['q'] = '%' . $f['q'] . '%';
            $p['q2'] = '%' . $f['q'] . '%';
        }
        if (!empty($f['ids'])) {
            [$in, $ip] = Database::in(array_map('intval', $f['ids']), 'id');
            $where[] = "h.id IN $in";
            $p += $ip;
        }
        $sql = 'SELECT h.id, h.name, h.slug, h.stars, h.district, h.region_id, h.latitude, h.longitude, h.short_description,
                       h.cover_image_id, h.is_demo, h.booking_mode, h.is_contracted, h.is_featured, h.featured_sort, h.sea_distance_m,
                       h.data_source, r.name AS region_name, r.illustration AS region_illustration, c.name AS concept_name, c.code AS concept_code
                FROM hotels h LEFT JOIN regions r ON r.id = h.region_id LEFT JOIN concepts c ON c.id = h.concept_id
                WHERE ' . implode(' AND ', $where) . ' ORDER BY h.is_featured DESC, h.featured_sort, h.name LIMIT 2000';
        return $this->db->fetchAll($sql, $p);
    }

    /** Kartlarda gösterilecek özellikler (toplu). @return array<int, string[]> */
    public function amenityNames(array $hotelIds, int $limit = 4): array
    {
        if (!$hotelIds) {
            return [];
        }
        [$in, $p] = Database::in($hotelIds, 'h');
        $out = [];
        foreach ($this->db->fetchAll("SELECT ha.hotel_id, a.name, a.icon FROM hotel_amenity ha JOIN amenities a ON a.id = ha.amenity_id WHERE a.is_active = 1 AND ha.hotel_id IN $in ORDER BY a.filter_key IS NULL, a.sort", $p) as $r) {
            if (count($out[(int) $r['hotel_id']] ?? []) < $limit) {
                $out[(int) $r['hotel_id']][] = ['name' => $r['name'], 'icon' => $r['icon']];
            }
        }
        return $out;
    }

    public function favoriteIds(int $userId, array $hotelIds): array
    {
        if (!$hotelIds) {
            return [];
        }
        [$in, $p] = Database::in($hotelIds, 'h');
        return array_flip(array_map('intval', $this->db->column("SELECT hotel_id FROM favorites WHERE user_id = :u AND hotel_id IN $in", $p + ['u' => $userId])));
    }

    public function findPublishedBySlug(string $slug): ?array
    {
        return $this->db->fetch(
            "SELECT h.*, r.name AS region_name, r.illustration AS region_illustration, c.name AS concept_name FROM hotels h
             LEFT JOIN regions r ON r.id = h.region_id LEFT JOIN concepts c ON c.id = h.concept_id
             WHERE h.slug = ? AND h.status = 'published'",
            [$slug],
        );
    }

    public function find(int $id): ?array
    {
        return $this->db->fetch(
            'SELECT h.*, r.name AS region_name, c.name AS concept_name FROM hotels h
             LEFT JOIN regions r ON r.id = h.region_id LEFT JOIN concepts c ON c.id = h.concept_id WHERE h.id = ?',
            [$id],
        );
    }

    public function images(int $hotelId): array
    {
        return $this->db->fetchAll('SELECT * FROM hotel_images WHERE hotel_id = ? ORDER BY sort, id', [$hotelId]);
    }

    public function amenities(int $hotelId): array
    {
        return $this->db->fetchAll('SELECT a.* FROM hotel_amenity ha JOIN amenities a ON a.id = ha.amenity_id WHERE ha.hotel_id = ? AND a.is_active = 1 ORDER BY a.sort', [$hotelId]);
    }

    public function rooms(int $hotelId, bool $activeOnly = true): array
    {
        return $this->db->fetchAll('SELECT * FROM rooms WHERE hotel_id = ?' . ($activeOnly ? ' AND is_active = 1' : '') . ' ORDER BY sort, id', [$hotelId]);
    }

    /** @return array<int, array> room_id => görseller */
    public function roomImages(array $roomIds): array
    {
        if (!$roomIds) {
            return [];
        }
        [$in, $p] = Database::in($roomIds, 'r');
        $out = [];
        foreach ($this->db->fetchAll("SELECT * FROM room_images WHERE room_id IN $in ORDER BY sort, id", $p) as $img) {
            $out[(int) $img['room_id']][] = $img;
        }
        return $out;
    }

    /** @return array<int, array> room_id => özellik adları */
    public function roomAmenities(array $roomIds): array
    {
        if (!$roomIds) {
            return [];
        }
        [$in, $p] = Database::in($roomIds, 'r');
        $out = [];
        foreach ($this->db->fetchAll("SELECT ra.room_id, a.name FROM room_amenity ra JOIN amenities a ON a.id = ra.amenity_id WHERE ra.room_id IN $in AND a.is_active = 1 ORDER BY a.sort", $p) as $r) {
            $out[(int) $r['room_id']][] = $r['name'];
        }
        return $out;
    }

    public function featured(int $limit = 8): array
    {
        return $this->db->fetchAll(
            "SELECT h.id, h.name, h.slug, h.stars, h.district, h.short_description, h.cover_image_id, h.is_demo, h.data_source, r.name AS region_name, r.illustration AS region_illustration, c.name AS concept_name
             FROM hotels h LEFT JOIN regions r ON r.id = h.region_id LEFT JOIN concepts c ON c.id = h.concept_id
             WHERE h.status = 'published' AND h.is_featured = 1 ORDER BY h.featured_sort, h.name LIMIT " . max(1, $limit),
        );
    }

    /** Yayın öncesi eksik bilgi listesi. */
    public function missingForPublish(array $hotel): array
    {
        $missing = [];
        foreach (['name' => 'Otel adı', 'region_id' => 'Bölge', 'address' => 'Adres', 'short_description' => 'Kısa açıklama', 'check_in_time' => 'Giriş saati', 'check_out_time' => 'Çıkış saati', 'cancellation_policy' => 'İptal politikası'] as $k => $label) {
            if (empty($hotel[$k])) {
                $missing[] = $label;
            }
        }
        if ($hotel['latitude'] === null || $hotel['longitude'] === null) {
            $missing[] = 'Harita koordinatları';
        }
        $id = (int) $hotel['id'];
        if ((int) $this->db->value('SELECT COUNT(*) FROM hotel_images WHERE hotel_id = ?', [$id]) === 0) {
            $missing[] = 'En az bir gerçek otel fotoğrafı';
        }
        if ((int) $this->db->value('SELECT COUNT(*) FROM rooms WHERE hotel_id = ? AND is_active = 1', [$id]) === 0) {
            $missing[] = 'En az bir aktif oda';
        }
        return $missing;
    }
}
