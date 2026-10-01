<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Exceptions\DomainException;

/**
 * Demo modu: sistemi canlıya almadan önce denemek için örnek otel, oda, fiyat, kontenjan ve görsel yükler.
 * Tüm demo kayıtları is_demo = 1 ile işaretlenir; "Canlı moda geç" bunları (ve demo otellere yapılan deneme
 * rezervasyonlarını) görselleriyle birlikte siler. Gerçek kayıtlara dokunulmaz.
 *
 * Demo oteller kurgusaldır; isimler gerçek işletmeleri temsil etmez. Görseller temsili illüstrasyonlardır.
 */
final class DemoDataService
{
    public const CAPTION_SUFFIX = ' (temsili görsel)';
    /** @var array<int, array{0:string,1:string}> hata durumunda geri silinecek görseller */
    private array $stored = [];
    private const SITE_KEYS = ['home.hero_image' => 'site-hero.jpg', 'login.image' => 'site-login.jpg', 'support.image' => 'site-support.jpg'];

    public function __construct(private readonly Database $db, private ?string $dataDir = null)
    {
        $this->dataDir ??= APP_ROOT . '/database/demo';
    }

    public function available(): bool
    {
        return is_file($this->dataDir . '/hotels.json');
    }

    public function isActive(): bool
    {
        return (int) $this->db->value('SELECT COUNT(*) FROM hotels WHERE is_demo = 1') > 0;
    }

    /** @return array{hotels:int, rooms:int, images:int, bookings:int} */
    public function stats(): array
    {
        return [
            'hotels' => (int) $this->db->value('SELECT COUNT(*) FROM hotels WHERE is_demo = 1'),
            'rooms' => (int) $this->db->value('SELECT COUNT(*) FROM rooms r JOIN hotels h ON h.id = r.hotel_id WHERE h.is_demo = 1'),
            'images' => (int) $this->db->value('SELECT COUNT(*) FROM hotel_images i JOIN hotels h ON h.id = i.hotel_id WHERE h.is_demo = 1'),
            'bookings' => (int) $this->db->value('SELECT COUNT(*) FROM bookings b JOIN hotels h ON h.id = b.hotel_id WHERE h.is_demo = 1'),
        ];
    }

    /** @return int yüklenen otel sayısı */
    public function install(?int $userId = null, int $days = 365): int
    {
        if (!$this->available()) {
            throw new DomainException('Demo veri dosyaları bulunamadı (database/demo).');
        }
        if ($this->isActive()) {
            throw new DomainException('Demo veriler zaten yüklü. Yeniden yüklemek için önce demo verileri kaldırın.');
        }
        @set_time_limit(300);
        $data = json_decode((string) file_get_contents($this->dataDir . '/hotels.json'), true, 512, JSON_THROW_ON_ERROR);
        $regions = $this->map('SELECT slug, id FROM regions');
        $concepts = $this->map('SELECT code, id FROM concepts');
        $amenities = $this->map('SELECT name, id FROM amenities');
        $roomAmenities = $this->db->column("SELECT id FROM amenities WHERE scope IN ('room','both') AND is_active = 1");
        $this->stored = [];
        $stored = &$this->stored;

        try {
            // Görseller önce işlenir (dosya sistemi işlemi); veritabanı tek transaction içinde yazılır.
            $roomImages = [];
            foreach ($data['room_images'] as $i => $_) {
                $roomImages[$i + 1] = $this->image('room-' . ($i + 1) . '.jpg', 'rooms', $stored);
            }
            $hotelImages = [];
            foreach ($data['hotels'] as $h) {
                foreach ($h['images'] as $i => [$scene, $tone, $seed, $caption]) {
                    $hotelImages[$h['slug']][] = $this->image($h['slug'] . '-' . ($i + 1) . '.jpg', 'hotels', $stored) + ['caption' => $caption . self::CAPTION_SUFFIX];
                }
            }
            $siteImages = [];
            foreach (self::SITE_KEYS as $key => $file) {
                if (SettingsService::get($key) === '' && is_file($this->dataDir . '/images/' . $file)) {
                    $siteImages[$key] = $this->image($file, 'site', $stored)['key'];
                }
            }

            $count = $this->db->transaction(function (Database $db) use ($data, $regions, $concepts, $amenities, $roomAmenities, $roomImages, $hotelImages, $userId, $days): int {
                $n = 0;
                foreach ($data['hotels'] as $idx => $h) {
                    $this->hotel($db, $h, $idx, $regions, $concepts, $amenities, $roomAmenities, $roomImages, $hotelImages[$h['slug']] ?? [], $userId, $days);
                    $n++;
                }
                // Örnek kural: erken rezervasyon (giriş tarihine 30+ gün) ek %5 indirim — yalnız ilk demo otelde
                $firstId = (int) $db->value('SELECT id FROM hotels WHERE is_demo = 1 ORDER BY id LIMIT 1');
                if ($firstId) {
                    $db->insert('rate_rules', [
                        'name' => 'Demo: Erken rezervasyon %5', 'kind' => 'early_booking', 'adjustment' => 'discount_percent', 'value' => 500,
                        'hotel_id' => $firstId, 'min_lead_days' => 30, 'priority' => 50, 'stackable' => 1, 'is_active' => 1, 'is_demo' => 1, 'created_by' => $userId,
                    ]);
                }
                return $n;
            });
            foreach ($roomImages as $ri) {
                ImageService::delete('rooms', $ri['key']); // şablon kopyalar; odalar kendi kopyalarını kullanır
            }
            $this->stored = []; // veritabanı kaydı tamamlandı; görseller artık kayıtlara bağlı
            foreach ($siteImages as $key => $imgKey) {
                SettingsService::set($key, $imgKey, false, $userId);
                SettingsService::set('demo.site_image.' . $key, $imgKey, false, $userId);
            }
            SettingsService::set('demo.active', '1', false, $userId);
            SettingsService::set('demo.loaded_at', date('Y-m-d H:i:s'), false, $userId);
            AuditService::log('demo.install', 'demo', null, null, ['hotels' => $count]);
            return $count;
        } catch (\Throwable $e) {
            foreach ($this->stored as [$kind, $key]) {
                ImageService::delete($kind, $key);
            }
            throw $e;
        }
    }

    /** Canlı moda geçiş: tüm demo kayıtlarını ve demo otellere yapılan deneme işlemlerini siler. */
    public function remove(?int $userId = null): array
    {
        $ids = array_map('intval', $this->db->column('SELECT id FROM hotels WHERE is_demo = 1'));
        $files = [];
        $result = ['hotels' => count($ids), 'bookings' => 0];
        if ($ids) {
            [$in, $p] = Database::in($ids, 'h');
            foreach ($this->db->fetchAll("SELECT storage_key FROM hotel_images WHERE hotel_id IN $in", $p) as $r) {
                $files[] = ['hotels', $r['storage_key']];
            }
            foreach ($this->db->fetchAll("SELECT i.storage_key FROM room_images i JOIN rooms r ON r.id = i.room_id WHERE r.hotel_id IN $in", $p) as $r) {
                $files[] = ['rooms', $r['storage_key']];
            }
            $result['bookings'] = (int) $this->db->value("SELECT COUNT(*) FROM bookings WHERE hotel_id IN $in", $p);
            $this->db->transaction(function (Database $db) use ($in, $p): void {
                $db->query("UPDATE bookings SET offer_id = NULL WHERE hotel_id IN $in", $p);
                $db->query("DELETE FROM offers WHERE hotel_id IN $in", $p);
                $db->query("DELETE FROM accommodation_requests WHERE hotel_id IN $in", $p);
                $db->query("DELETE FROM bookings WHERE hotel_id IN $in", $p);
                $db->query("UPDATE hotels SET cover_image_id = NULL WHERE id IN $in", $p);
                $db->query("DELETE FROM hotels WHERE id IN $in", $p);
            });
        }
        $this->db->query('DELETE FROM rate_rules WHERE is_demo = 1');
        $this->db->query('DELETE FROM institutions WHERE is_demo = 1');
        // Demo ile gelen site görselleri yalnız hâlâ kullanılıyorsa geri alınır (yönetici değiştirdiyse dokunulmaz)
        foreach (array_keys(self::SITE_KEYS) as $key) {
            $demoKey = SettingsService::get('demo.site_image.' . $key);
            if ($demoKey !== '') {
                if (SettingsService::get($key) === $demoKey) {
                    SettingsService::set($key, '', false, $userId);
                }
                $files[] = ['site', $demoKey];
                SettingsService::set('demo.site_image.' . $key, '', false, $userId);
            }
        }
        foreach ($files as [$kind, $key]) {
            ImageService::delete($kind, $key);
        }
        $this->db->query("DELETE FROM api_cache WHERE 1 = 1");
        SettingsService::set('demo.active', '0', false, $userId);
        SettingsService::set('demo.loaded_at', '', false, $userId);
        AuditService::log('demo.remove', 'demo', null, null, $result);
        return $result;
    }

    private function map(string $sql): array
    {
        $out = [];
        foreach ($this->db->fetchAll($sql) as $r) {
            $vals = array_values($r);
            $out[(string) $vals[0]] = (int) $vals[1];
        }
        return $out;
    }

    private function image(string $file, string $kind, array &$stored): array
    {
        $path = $this->dataDir . '/images/' . $file;
        if (!is_file($path)) {
            throw new DomainException('Demo görseli eksik: ' . $file);
        }
        $img = ImageService::storeLocal($path, $kind, $file);
        $stored[] = [$kind, $img['key']];
        return $img;
    }

    private function hotel(Database $db, array $h, int $idx, array $regions, array $concepts, array $amenities, array $roomAmenities, array $roomImages, array $images, ?int $userId, int $days): void
    {
        $slug = $h['slug'];
        $i = 2;
        while ($db->value('SELECT 1 FROM hotels WHERE slug = ?', [$slug])) {
            $slug = $h['slug'] . '-' . $i++;
        }
        $instant = $h['booking_mode'] === 'instant';
        $conceptId = $concepts[$h['concept']] ?? null;
        $conceptName = (string) $db->value('SELECT name FROM concepts WHERE id = ?', [$conceptId]);
        $hotelId = $db->insert('hotels', [
            'name' => $h['name'], 'slug' => $slug, 'stars' => $h['stars'], 'region_id' => $regions[$h['region']] ?? null,
            'district' => $h['district'], 'neighborhood' => null,
            'address' => 'Demo adres — ' . $h['district'] . ' / Antalya (kurgusal kayıt)',
            'latitude' => $h['lat'], 'longitude' => $h['lng'],
            'short_description' => $h['short_description'], 'description' => $h['description'],
            'concept_id' => $conceptId, 'beach_type' => $h['beach_type'],
            'beach_info' => $h['sea_distance_m'] === 0 ? 'Otel denize sıfırdır; şezlong ve şemsiye ücretsizdir.' : ('Denize uzaklık yaklaşık ' . $h['sea_distance_m'] . ' m.'),
            'sea_distance_m' => $h['sea_distance_m'], 'airport_distance_km' => $h['airport_distance_km'], 'center_distance_km' => $h['center_distance_km'],
            'check_in_time' => '14:00', 'check_out_time' => '12:00',
            'child_policy' => '0–6 yaş çocuklar ücretsiz konaklar. 7–12 yaş çocuklar indirimli ücretlendirilir; 12 yaş üstü yetişkin fiyatı uygulanır.',
            'pet_policy' => 'Evcil hayvan kabul edilmez.',
            'cancellation_policy' => 'Girişten 7 gün öncesine kadar ücretsiz iptal. Sonrasında ilk gece ücreti kesilir.',
            'payment_policy' => 'Ödeme otelde, giriş sırasında yapılır. Kurum faturası seçeneği için kurum yetkilinizle görüşün.',
            'important_info' => 'Bu kayıt DEMO amaçlıdır; otel kurgusaldır, görseller temsilidir. Canlı kullanıma geçmeden önce yönetim panelinden demo veriler kaldırılmalıdır.',
            'booking_mode' => $h['booking_mode'], 'is_contracted' => 1, 'contract_valid_until' => date('Y-m-d', strtotime('+' . ($days + 30) . ' days')),
            'is_featured' => (int) $h['is_featured'], 'featured_sort' => $idx,
            'status' => 'published', 'is_demo' => 1, 'wizard_step' => 7, 'created_by' => $userId, 'published_at' => date('Y-m-d H:i:s'),
            'admin_notes' => 'Demo kayıt — canlı moda geçişte otomatik silinir.',
        ]);
        $cover = null;
        foreach ($images as $sort => $img) {
            $id = $db->insert('hotel_images', ['hotel_id' => $hotelId, 'storage_key' => $img['key'], 'original_name' => $img['original'], 'mime' => $img['mime'], 'width' => $img['width'], 'height' => $img['height'], 'bytes' => $img['bytes'], 'caption' => $img['caption'], 'sort' => $sort + 1]);
            $cover ??= $id;
        }
        if ($cover) {
            $db->update('hotels', ['cover_image_id' => $cover], ['id' => $hotelId]);
        }
        foreach (array_unique($h['amenities']) as $name) {
            if (isset($amenities[$name])) {
                $db->query('INSERT IGNORE INTO hotel_amenity (hotel_id, amenity_id) VALUES (?, ?)', [$hotelId, $amenities[$name]]);
            }
        }
        $start = new \DateTimeImmutable('today');
        foreach ($h['rooms'] as $sort => $r) {
            $roomId = $db->insert('rooms', [
                'hotel_id' => $hotelId, 'name' => $r['name'],
                'description' => $r['name'] . '; ' . $r['size_m2'] . ' m², ' . mb_strtolower($r['view_type']) . ' manzaralı. Klima, minibar, TV, kasa ve ücretsiz Wi-Fi bulunur.',
                'size_m2' => $r['size_m2'], 'bed_type' => $r['bed_type'], 'view_type' => $r['view_type'],
                'max_adults' => $r['max_adults'], 'max_children' => $r['max_children'], 'max_occupancy' => $r['max_occupancy'],
                'is_active' => 1, 'sort' => $sort + 1,
            ]);
            $ri = $roomImages[(int) $r['image']] ?? null;
            if ($ri) {
                // Aynı kaynak görsel birden çok odada kullanılabilir; her oda kendi kopyasını saklar
                $copyKey = ImageService::duplicate('rooms', $ri['key']);
                if ($copyKey !== null) {
                    $this->stored[] = ['rooms', $copyKey];
                    $db->insert('room_images', ['room_id' => $roomId, 'storage_key' => $copyKey, 'original_name' => $ri['original'], 'mime' => $ri['mime'], 'width' => $ri['width'], 'height' => $ri['height'], 'bytes' => $ri['bytes'], 'caption' => $r['name'] . self::CAPTION_SUFFIX, 'sort' => 1]);
                }
            }
            foreach ($roomAmenities as $aid) {
                $db->query('INSERT IGNORE INTO room_amenity (room_id, amenity_id) VALUES (?, ?)', [$roomId, (int) $aid]);
            }
            $planId = $db->insert('rate_plans', [
                'hotel_id' => $hotelId, 'room_id' => $roomId, 'name' => 'Kurumsal ' . $conceptName, 'concept_id' => $conceptId,
                'source' => 'contract', 'currency' => 'TRY', 'tax_included' => 1, 'base_adults' => 2,
                'free_child_max_age' => 6, 'child_max_age' => 12, 'refundable' => 1, 'free_cancel_days' => 7,
                'cancellation_policy' => 'Girişten 7 gün öncesine kadar ücretsiz iptal.', 'payment_terms' => 'Ödeme otelde yapılır.',
                'is_bookable' => $instant ? 1 : 0, 'member_discount_applies' => 1, 'contract_reference' => 'DEMO-' . strtoupper(substr(md5($slug), 0, 6)),
                'valid_from' => $start->format('Y-m-d'), 'valid_to' => $start->modify('+' . $days . ' days')->format('Y-m-d'),
                'is_active' => 1, 'verified_by' => $userId, 'verified_at' => date('Y-m-d H:i:s'),
                'notes' => 'Demo fiyat — gerçek bir anlaşmayı temsil etmez.',
            ]);
            $rates = [];
            $inv = [];
            $base = (int) round($h['base_price'] * $r['factor']);
            for ($d = 0; $d < $days; $d++) {
                $date = $start->modify("+$d day");
                $price = (int) (round($base * self::seasonFactor($date) / 1000) * 1000);
                $rates[] = [$planId, $date->format('Y-m-d'), $price, (int) (round($price * 0.75 / 1000) * 1000), (int) (round($price * 0.3 / 1000) * 1000), (int) (round($price * 0.15 / 1000) * 1000)];
                $inv[] = [$roomId, $date->format('Y-m-d'), (int) $r['units'], 0, 1];
            }
            foreach (array_chunk($rates, 200) as $chunk) {
                $db->query('INSERT INTO rates (rate_plan_id, stay_date, price_minor, single_minor, extra_adult_minor, child_minor) VALUES ' . implode(',', array_fill(0, count($chunk), '(?,?,?,?,?,?)')), array_merge(...$chunk));
            }
            foreach (array_chunk($inv, 200) as $chunk) {
                $db->query('INSERT INTO inventory (room_id, stay_date, total_units, booked_units, is_open) VALUES ' . implode(',', array_fill(0, count($chunk), '(?,?,?,?,?)')), array_merge(...$chunk));
            }
        }
    }

    /** Antalya sezon eğrisi (Temmuz–Ağustos zirve, kış en düşük) + hafta sonu farkı. */
    public static function seasonFactor(\DateTimeImmutable $d): float
    {
        $m = (int) $d->format('n');
        $f = match ($m) {
            7, 8 => 1.0,
            6, 9 => 0.88,
            5, 10 => 0.72,
            4, 11 => 0.58,
            default => 0.5,
        };
        if (in_array((int) $d->format('N'), [5, 6], true)) {
            $f *= 1.06;
        }
        return $f;
    }
}
