<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Exceptions\DomainException;
use App\Exceptions\ProviderException;
use App\Providers\Adapters\AbstractProvider;
use App\Providers\Adapters\LiteApiProvider;
use App\Providers\Contracts\HotelCatalogInterface;
use App\Providers\Http\HttpClient;
use App\Providers\ProviderRegistry;

/**
 * Gerçek otelleri harici kataloglardan (OpenStreetMap, LiteAPI) bölge bölge içe aktarır.
 * - Aynı kaynak kaydı tekrar içe aktarılırsa yeni otel oluşturulmaz, temel bilgiler güncellenir.
 * - LiteAPI oteli, daha önce OpenStreetMap'ten gelen aynı otelle (benzer ad + 300 m içinde) eşleşirse birleştirilir.
 * - Uydurma fiyat, puan veya görsel eklenmez. Fiyatlar yalnız LiteAPI'den canlı olarak (arama anında) gelir.
 */
final class HotelImportService
{
    private const AMENITY_KEYWORDS = [
        'Ücretsiz Wi-Fi' => ['wifi', 'wi-fi', 'internet', 'wlan'],
        'Açık havuz' => ['outdoor pool', 'outdoor swimming', 'swimming pool', 'pool'],
        'Kapalı havuz' => ['indoor pool', 'indoor swimming'],
        'Spa & Wellness' => ['spa', 'wellness', 'massage'],
        'Hamam' => ['turkish bath', 'hammam', 'hamam'],
        'Fitness merkezi' => ['fitness', 'gym'],
        'Otopark' => ['parking'],
        'Restoran' => ['restaurant'],
        'Özel plaj' => ['private beach', 'beach'],
        'Havalimanı transferi' => ['airport shuttle', 'airport transfer'],
        'Toplantı salonu' => ['meeting', 'conference', 'business centre', 'business center'],
        'Çocuk kulübü' => ['kids club', "kids' club", 'children club', 'mini club'],
        'Engelli dostu' => ['wheelchair', 'accessible', 'disabled'],
        'Aquapark' => ['water park', 'aquapark', 'water slide'],
        'Animasyon' => ['entertainment', 'animation'],
    ];

    /** @var string[] işlem sırasında oluşan uyarılar */
    public array $warnings = [];

    public function __construct(private readonly Database $db)
    {
    }

    public static function radiusFor(array $region): int
    {
        return $region['parent_id'] === null ? 9000 : 5000;
    }

    /**
     * @param array{max_new?:int, with_details?:bool, max_images?:int, radius?:int, publish?:bool, user_id?:?int} $opt
     * @return array{found:int, created:int, updated:int, merged:int, images:int, skipped:int}
     */
    public function importRegion(int $providerId, int $regionId, array $opt = []): array
    {
        $provider = ProviderRegistry::find($providerId);
        if (!$provider instanceof HotelCatalogInterface) {
            throw new DomainException('Bu sağlayıcı otel kataloğu sunmuyor.');
        }
        $region = $this->db->fetch('SELECT * FROM regions WHERE id = ? AND is_active = 1', [$regionId]);
        if (!$region || $region['latitude'] === null) {
            throw new DomainException('Bölge bulunamadı veya bölgenin koordinatı tanımlı değil.');
        }
        $maxNew = max(1, min(200, (int) ($opt['max_new'] ?? 40)));
        $radius = (int) ($opt['radius'] ?? self::radiusFor($region));
        $stubs = $provider->listHotels((float) $region['latitude'], (float) $region['longitude'], $radius, min(200, $maxNew * 3));
        $res = ['found' => count($stubs), 'created' => 0, 'updated' => 0, 'merged' => 0, 'images' => 0, 'skipped' => 0];
        $isLite = $provider instanceof LiteApiProvider;
        $now = date('Y-m-d H:i:s');
        foreach ($stubs as $stub) {
            $mapped = $this->db->fetch('SELECT hotel_id FROM provider_hotel_map WHERE provider_id = ? AND external_hotel_id = ?', [$providerId, $stub['external_id']]);
            if ($mapped) {
                $this->db->update('hotels', array_filter([
                    'stars' => $stub['stars'], 'latitude' => $stub['latitude'], 'longitude' => $stub['longitude'],
                    'phone' => $stub['phone'] ?? null, 'website' => $stub['website'] ?? null,
                ], static fn ($v) => $v !== null) + ['last_synced_at' => $now], ['id' => (int) $mapped['hotel_id']]);
                $res['updated']++;
                continue;
            }
            if ($res['created'] + $res['merged'] >= $maxNew) {
                $res['skipped']++;
                continue;
            }
            $details = [];
            if ($isLite && ($opt['with_details'] ?? true)) {
                try {
                    $details = $provider->getHotel($stub['external_id']);
                } catch (ProviderException $e) {
                    $this->warnings[] = $stub['name'] . ': ayrıntı alınamadı (' . $e->getMessage() . ')';
                }
            }
            $data = $this->normalize($stub, $details);
            $existing = $isLite ? $this->findSameHotel($data) : null;
            $hotelId = $this->db->transaction(function (Database $db) use ($existing, $data, $region, $provider, $providerId, $stub, $isLite, $now, $opt): int {
                if ($existing) {
                    // OpenStreetMap kaydını LiteAPI verisiyle zenginleştir: canlı fiyat + rezervasyon
                    $db->update('hotels', array_filter([
                        'data_source' => 'liteapi', 'booking_mode' => 'instant', 'source_attribution' => $provider->attribution() . ' · Konum/iletişim: © OpenStreetMap katkıcıları',
                        'description' => $data['description'] ?: null, 'short_description' => $data['short'] ?: null,
                        'stars' => $data['stars'], 'check_in_time' => $data['check_in'], 'check_out_time' => $data['check_out'], 'last_synced_at' => $now,
                        'cancellation_policy' => 'İptal koşulları seçilen fiyata göre değişir; her fiyatın yanında ve rezervasyon özetinde gösterilir.',
                        'payment_policy' => 'Ödeme koşulları seçilen fiyata göre değişir; rezervasyon özetinde gösterilir.',
                    ], static fn ($v) => $v !== null), ['id' => (int) $existing['id']]);
                    $id = (int) $existing['id'];
                } else {
                    $id = $db->insert('hotels', [
                        'name' => $data['name'], 'slug' => $this->uniqueSlug($data['name']), 'stars' => $data['stars'], 'region_id' => (int) $region['id'],
                        'district' => null, 'address' => $data['address'], 'phone' => $data['phone'], 'website' => $data['website'],
                        'latitude' => $data['latitude'], 'longitude' => $data['longitude'],
                        'short_description' => $data['short'], 'description' => $data['description'],
                        'check_in_time' => $data['check_in'] ?? '14:00', 'check_out_time' => $data['check_out'] ?? '12:00',
                        'cancellation_policy' => $isLite ? 'İptal koşulları seçilen fiyata göre değişir; her fiyatın yanında ve rezervasyon özetinde gösterilir.' : 'İptal ve ödeme koşulları otelden alınan teklifle birlikte bildirilir.',
                        'payment_policy' => $isLite ? 'Ödeme koşulları seçilen fiyata göre değişir; rezervasyon özetinde gösterilir.' : 'Teklif ile birlikte bildirilir.',
                        'booking_mode' => $isLite ? 'instant' : 'request', 'is_contracted' => 0,
                        'status' => ($opt['publish'] ?? true) ? 'published' : 'draft', 'published_at' => ($opt['publish'] ?? true) ? $now : null,
                        'is_demo' => 0, 'data_source' => $isLite ? 'liteapi' : 'osm', 'source_attribution' => $provider->attribution(),
                        'last_synced_at' => $now, 'wizard_step' => 7, 'created_by' => $opt['user_id'] ?? null,
                        'admin_notes' => 'İçe aktarıldı: ' . $provider->attribution() . ' (' . $stub['external_id'] . ')',
                    ]);
                }
                $db->query('INSERT INTO provider_hotel_map (provider_id, hotel_id, external_hotel_id, external_name, verified_by, verified_at) VALUES (?, ?, ?, ?, ?, ?)
                            ON DUPLICATE KEY UPDATE external_hotel_id = VALUES(external_hotel_id), external_name = VALUES(external_name), verified_at = VALUES(verified_at)',
                    [$providerId, $id, $stub['external_id'], mb_substr($data['name'], 0, 200), $opt['user_id'] ?? null, $now]);
                foreach ($this->amenityIds($data['facilities']) as $aid) {
                    $db->query('INSERT IGNORE INTO hotel_amenity (hotel_id, amenity_id) VALUES (?, ?)', [$id, $aid]);
                }
                return $id;
            });
            $existing ? $res['merged']++ : $res['created']++;
            if ($data['photo_urls'] && (int) $this->db->value('SELECT COUNT(*) FROM hotel_images WHERE hotel_id = ?', [$hotelId]) === 0) {
                $res['images'] += $this->downloadImages($provider, $hotelId, $data['photo_urls'], max(0, min(10, (int) ($opt['max_images'] ?? 6))), $data['name']);
            }
        }
        AuditService::log('hotels.import', 'region', $regionId, null, ['provider' => $provider->code()] + $res);
        return $res;
    }

    private function normalize(array $stub, array $details): array
    {
        $desc = trim((string) ($details['description'] ?? $stub['description'] ?? ''));
        // Kısa açıklama yalnız uzun metinlerde üretilir (detay sayfasında aynı metin iki kez görünmesin)
        $short = mb_strlen($desc) > 260 ? mb_strimwidth((string) preg_replace('/\s+/', ' ', $desc), 0, 220, '…') : null;
        return [
            'name' => mb_substr(trim((string) ($details['name'] ?? '') ?: $stub['name']), 0, 200),
            'stars' => $details['stars'] ?? $stub['stars'] ?? null,
            'latitude' => $details['latitude'] ?? $stub['latitude'] ?? null,
            'longitude' => $details['longitude'] ?? $stub['longitude'] ?? null,
            'address' => isset($details['address']) || isset($stub['address']) ? mb_substr((string) ($details['address'] ?? $stub['address']), 0, 500) : null,
            'phone' => $stub['phone'] ?? null, 'website' => $stub['website'] ?? null,
            'description' => $desc !== '' ? mb_substr($desc, 0, 8000) : null, 'short' => $short,
            'check_in' => $details['check_in'] ?? null, 'check_out' => $details['check_out'] ?? null,
            'photo_urls' => array_values(array_unique(array_merge($details['photo_urls'] ?? [], $stub['photo_urls'] ?? []))),
            'facilities' => array_merge($details['facilities'] ?? [], $stub['facilities'] ?? []),
        ];
    }

    /** OpenStreetMap'ten gelmiş aynı otel: normalleştirilmiş ad benzerliği + 300 m mesafe. */
    private function findSameHotel(array $d): ?array
    {
        if ($d['latitude'] === null || $d['longitude'] === null) {
            return null;
        }
        $cands = $this->db->fetchAll(
            "SELECT id, name, latitude, longitude FROM hotels WHERE data_source = 'osm' AND latitude BETWEEN ? AND ? AND longitude BETWEEN ? AND ?",
            [$d['latitude'] - 0.004, $d['latitude'] + 0.004, $d['longitude'] - 0.005, $d['longitude'] + 0.005],
        );
        $norm = static fn (string $n) => trim((string) preg_replace('/\b(hotel|otel|resort|spa|beach|club|suites?|&|and|ve)\b/u', ' ', mb_strtolower(preg_replace('/[^\p{L}\p{N} ]+/u', ' ', $n) ?? '')));
        $target = preg_replace('/\s+/', ' ', $norm($d['name']));
        foreach ($cands as $c) {
            $cn = preg_replace('/\s+/', ' ', $norm($c['name']));
            if ($cn === '' || $target === '') {
                continue;
            }
            similar_text($cn, $target, $pct);
            if ($pct >= 80 || str_contains($cn, $target) || str_contains($target, $cn)) {
                $dist = self::distanceM((float) $c['latitude'], (float) $c['longitude'], (float) $d['latitude'], (float) $d['longitude']);
                if ($dist <= 300) {
                    return $c;
                }
            }
        }
        return null;
    }

    public static function distanceM(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return 2 * $r * asin(min(1, sqrt($a)));
    }

    private function amenityIds(array $facilities): array
    {
        if (!$facilities) {
            return [];
        }
        $text = ' ' . mb_strtolower(implode(' | ', $facilities)) . ' ';
        $byName = [];
        foreach ($this->db->fetchAll("SELECT id, name, filter_key FROM amenities WHERE is_active = 1 AND scope IN ('hotel','both')") as $a) {
            $byName[$a['name']] = (int) $a['id'];
            if ($a['filter_key']) {
                $byName['key:' . $a['filter_key']] = (int) $a['id'];
            }
        }
        $ids = [];
        foreach (self::AMENITY_KEYWORDS as $name => $words) {
            foreach ($words as $w) {
                if (str_contains($text, $w) && isset($byName[$name])) {
                    $ids[] = $byName[$name];
                    break;
                }
            }
        }
        // OpenStreetMap kısa anahtarları
        foreach (['wifi' => 'Ücretsiz Wi-Fi', 'pool' => 'Açık havuz', 'accessible' => 'Engelli dostu'] as $k => $name) {
            if (in_array($k, $facilities, true) && isset($byName[$name])) {
                $ids[] = $byName[$name];
            }
        }
        return array_values(array_unique($ids));
    }

    private function uniqueSlug(string $name): string
    {
        $map = ['ç' => 'c', 'ğ' => 'g', 'ı' => 'i', 'ö' => 'o', 'ş' => 's', 'ü' => 'u', 'Ç' => 'c', 'Ğ' => 'g', 'İ' => 'i', 'Ö' => 'o', 'Ş' => 's', 'Ü' => 'u'];
        $base = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower(strtr($name, $map))), '-') ?: 'otel';
        $base = substr($base, 0, 180);
        $slug = $base;
        $i = 2;
        while ($this->db->value('SELECT 1 FROM hotels WHERE slug = ?', [$slug])) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    /** Sağlayıcının verdiği görselleri indirir, doğrular ve yeniden kodlayarak saklar. */
    private function downloadImages(AbstractProvider $provider, int $hotelId, array $urls, int $max, string $hotelName): int
    {
        $http = new HttpClient($provider->id(), 20, 600);
        $n = 0;
        $tmpDir = APP_ROOT . '/storage/tmp';
        foreach ($urls as $url) {
            if ($n >= $max) {
                break;
            }
            if (!preg_match('#^https://#i', $url)) {
                continue;
            }
            $tmp = tempnam($tmpDir, 'img');
            try {
                $res = $http->request('image', 'GET', $url, [], null, 1);
                if (strlen($res['body']) < 2000 || strlen($res['body']) > ImageService::MAX_BYTES) {
                    continue;
                }
                file_put_contents($tmp, $res['body']);
                $img = ImageService::storeLocal($tmp, 'hotels', basename((string) parse_url($url, PHP_URL_PATH)) ?: 'foto.jpg');
                $id = $this->db->insert('hotel_images', [
                    'hotel_id' => $hotelId, 'storage_key' => $img['key'], 'original_name' => mb_substr($img['original'], 0, 255), 'mime' => $img['mime'],
                    'width' => $img['width'], 'height' => $img['height'], 'bytes' => $img['bytes'], 'caption' => mb_substr($hotelName, 0, 160) . ' — kaynak: LiteAPI', 'sort' => $n + 1,
                ]);
                if ($n === 0) {
                    $this->db->query('UPDATE hotels SET cover_image_id = ? WHERE id = ? AND cover_image_id IS NULL', [$id, $hotelId]);
                }
                $n++;
            } catch (\Throwable $e) {
                $this->warnings[] = $hotelName . ': görsel alınamadı';
            } finally {
                if (is_file($tmp)) {
                    @unlink($tmp);
                }
            }
        }
        return $n;
    }

    /**
     * Bir kaynaktan gelen otelleri kaldırır. Rezervasyon/teklif geçmişi olanlar silinmez, yayından kaldırılır.
     * @return array{deleted:int, unpublished:int}
     */
    public function removeSource(string $source): array
    {
        if (!in_array($source, ['osm', 'liteapi'], true)) {
            throw new DomainException('Geçersiz kaynak.');
        }
        $out = ['deleted' => 0, 'unpublished' => 0];
        foreach ($this->db->fetchAll('SELECT id FROM hotels WHERE data_source = ? AND is_demo = 0', [$source]) as $h) {
            $id = (int) $h['id'];
            $used = $this->db->value('SELECT 1 FROM bookings WHERE hotel_id = ? LIMIT 1', [$id]) || $this->db->value('SELECT 1 FROM offers WHERE hotel_id = ? LIMIT 1', [$id]);
            if ($used) {
                $this->db->update('hotels', ['status' => 'unpublished'], ['id' => $id]);
                $out['unpublished']++;
                continue;
            }
            $keys = $this->db->column('SELECT storage_key FROM hotel_images WHERE hotel_id = ?', [$id]);
            $this->db->query('UPDATE hotels SET cover_image_id = NULL WHERE id = ?', [$id]);
            $this->db->query('DELETE FROM accommodation_requests WHERE hotel_id = ?', [$id]);
            $this->db->query('DELETE FROM hotels WHERE id = ?', [$id]);
            foreach ($keys as $k) {
                ImageService::delete('hotels', (string) $k);
            }
            $out['deleted']++;
        }
        AuditService::log('hotels.import.remove', 'hotels', null, null, ['source' => $source] + $out);
        return $out;
    }
}
