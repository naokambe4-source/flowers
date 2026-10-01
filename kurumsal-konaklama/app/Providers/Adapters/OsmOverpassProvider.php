<?php
declare(strict_types=1);

namespace App\Providers\Adapters;

use App\DTO\ProviderHealth;
use App\Exceptions\ProviderException;
use App\Providers\Contracts\Capability;
use App\Providers\Contracts\HotelCatalogInterface;

/**
 * OpenStreetMap (Overpass API) — ücretsiz, anahtarsız gerçek otel kataloğu.
 * Yalnız içerik verir (ad, yıldız, konum, adres, telefon, web sitesi); FİYAT ve MÜSAİTLİK VERMEZ.
 * Bu kaynaktan gelen oteller "teklif iste" akışıyla çalışır; fiyat otelden alınıp yönetimce girilir.
 *
 * Lisans: veriler © OpenStreetMap katkıcıları, ODbL. Otel sayfalarında kaynak gösterimi zorunludur (otomatik eklenir).
 * Kullanım politikası: ortak Overpass sunucuları sınırlı kapasitelidir; içe aktarma bölge bölge ve seyrek yapılmalıdır.
 */
final class OsmOverpassProvider extends AbstractProvider implements HotelCatalogInterface
{
    private const ENDPOINT = 'https://overpass-api.de/api/interpreter';

    public static function credentialFields(): array
    {
        return [];
    }

    public function capabilities(): array
    {
        return [Capability::CONTENT, Capability::HEALTH];
    }

    public function attribution(): string
    {
        return 'Otel bilgileri © OpenStreetMap katkıcıları (ODbL)';
    }

    private function endpoint(): string
    {
        $url = (string) $this->setting('base_url', self::ENDPOINT);
        if (!str_starts_with($url, 'https://')) {
            throw new ProviderException('Overpass adresi HTTPS olmalıdır.', $this->code());
        }
        return $url;
    }

    private function query(string $op, string $ql): array
    {
        $res = $this->http()->request($op, 'GET', $this->endpoint() . '?' . http_build_query(['data' => $ql]), [], null, 1);
        if (!is_array($res['json']) || !isset($res['json']['elements'])) {
            throw new ProviderException('OpenStreetMap (Overpass) yanıtı okunamadı.', $this->code());
        }
        return $res['json']['elements'];
    }

    public function listHotels(float $latitude, float $longitude, int $radiusMeters, int $limit): array
    {
        $types = (string) $this->setting('tourism_types', 'hotel');
        if (!preg_match('/^[a-z_|]+$/', $types)) {
            $types = 'hotel';
        }
        $ql = sprintf(
            '[out:json][timeout:60];nwr["tourism"~"^(%s)$"]["name"](around:%d,%.6F,%.6F);out center tags %d;',
            $types, max(500, min(30000, $radiusMeters)), $latitude, $longitude, max(1, min(500, $limit)),
        );
        $out = [];
        foreach ($this->query('search', $ql) as $el) {
            $t = (array) ($el['tags'] ?? []);
            $name = trim((string) ($t['name:tr'] ?? $t['name'] ?? ''));
            if ($name === '' || empty($el['type']) || empty($el['id'])) {
                continue;
            }
            $lat = $el['lat'] ?? $el['center']['lat'] ?? null;
            $lng = $el['lon'] ?? $el['center']['lon'] ?? null;
            $stars = null;
            if (isset($t['stars']) && preg_match('/^(\d)/', (string) $t['stars'], $m) && (int) $m[1] >= 1 && (int) $m[1] <= 5) {
                $stars = (int) $m[1];
            }
            $addr = trim(implode(' ', array_filter([$t['addr:street'] ?? null, $t['addr:housenumber'] ?? null])));
            $district = $t['addr:district'] ?? $t['addr:suburb'] ?? $t['addr:city'] ?? null;
            $facilities = [];
            if (in_array($t['internet_access'] ?? '', ['wlan', 'yes', 'wifi'], true)) {
                $facilities[] = 'wifi';
            }
            if (($t['swimming_pool'] ?? '') === 'yes') {
                $facilities[] = 'pool';
            }
            if (($t['wheelchair'] ?? '') === 'yes') {
                $facilities[] = 'accessible';
            }
            $website = (string) ($t['website'] ?? $t['contact:website'] ?? '');
            $out[] = [
                'external_id' => $el['type'] . '/' . $el['id'], 'name' => mb_substr($name, 0, 200), 'stars' => $stars,
                'latitude' => $lat !== null ? (float) $lat : null, 'longitude' => $lng !== null ? (float) $lng : null,
                'address' => trim($addr . ($district ? ($addr !== '' ? ', ' : '') . $district : '')) ?: null,
                'city' => $t['addr:city'] ?? null, 'description' => isset($t['description']) ? mb_substr((string) $t['description'], 0, 2000) : null,
                'photo_urls' => [], 'website' => preg_match('#^https?://#i', $website) ? mb_substr($website, 0, 255) : null,
                'phone' => isset($t['phone']) || isset($t['contact:phone']) ? mb_substr((string) ($t['phone'] ?? $t['contact:phone']), 0, 40) : null,
                'facilities' => $facilities,
            ];
        }
        return $out;
    }

    public function getHotel(string $externalHotelId): array
    {
        if (!preg_match('#^(node|way|relation)/(\d+)$#', $externalHotelId, $m)) {
            throw new ProviderException('Geçersiz OpenStreetMap kimliği.', $this->code());
        }
        $els = $this->query('content', sprintf('[out:json][timeout:25];%s(%d);out center tags;', $m[1], (int) $m[2]));
        return (array) ($els[0]['tags'] ?? []);
    }

    public function healthCheck(): ProviderHealth
    {
        $start = microtime(true);
        $this->query('health', '[out:json][timeout:10];node(1);out ids;');
        return new ProviderHealth(true, 'OpenStreetMap (Overpass) bağlantısı başarılı — anahtar gerekmez; yalnız otel bilgisi verir, fiyat vermez.', null, (int) ((microtime(true) - $start) * 1000));
    }
}
