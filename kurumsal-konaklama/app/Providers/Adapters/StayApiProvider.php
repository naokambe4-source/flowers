<?php
declare(strict_types=1);

namespace App\Providers\Adapters;

use App\DTO\ProviderHealth;
use App\DTO\ProviderRate;
use App\DTO\StayCriteria;
use App\Exceptions\ProviderException;
use App\Providers\Contracts\Capability;

/**
 * StayAPI (https://stayapi.com) veri sağlayıcı adaptörü.
 *
 * ÖNEMLİ:
 *  - StayAPI bir veri servisidir; rezervasyon OLUŞTURMAZ. createBooking/checkRate/cancel desteklenmez.
 *  - "Resmi Booking.com partner API" değildir. Üyelere fiyat gösterimi, ticari kullanım izni
 *    StayAPI ve ilgili kaynaklarla sözleşmeyle doğrulanana kadar yönetimde kapalı tutulmalıdır
 *    (providers.display_authorized). Bu fiyatlar hiçbir zaman "kesin fiyat" olarak sunulmaz.
 *  - Meta eşleştirme (meta/search, match) sonucu fiyat veya müsaitlik olarak KULLANILMAZ.
 *    Fiyat yalnız tarihli arama uç noktasından alınır.
 *  - Uç nokta yolları yönetimden düzenlenebilir; varsayılanlar: /v1/booking/destinations, /v1/booking/search.
 *    Kimlik doğrulama: "x-api-key" başlığı.
 *  - Canlı API ile test, geçerli anahtar girildikten sonra yönetimdeki "Bağlantı testi" ile yapılmalıdır.
 */
final class StayApiProvider extends AbstractProvider
{
    public static function credentialFields(): array
    {
        return ['api_key' => 'API anahtarı (x-api-key)'];
    }

    public function capabilities(): array
    {
        return [Capability::DESTINATIONS, Capability::SEARCH, Capability::RATES, Capability::CONTENT, Capability::HEALTH];
    }

    private function get(string $operation, string $pathKey, array $query): array
    {
        $path = (string) $this->setting($pathKey, '');
        if ($path === '' || !str_starts_with($path, '/')) {
            throw new ProviderException('Uç nokta yolu tanımlı değil: ' . $pathKey, $this->code());
        }
        $url = $this->baseUrl() . $path . ($query ? '?' . http_build_query($query) : '');
        $res = $this->http()->request($operation, 'GET', $url, ['x-api-key' => $this->credential('api_key')]);
        if (!is_array($res['json'])) {
            throw new ProviderException('Sağlayıcı yanıtı okunamadı.', $this->code());
        }
        if (isset($res['headers']['x-ratelimit-remaining']) && is_numeric($res['headers']['x-ratelimit-remaining'])) {
            \App\Core\App::db()->update('providers', ['quota_remaining' => (int) $res['headers']['x-ratelimit-remaining']], ['id' => $this->id()]);
        }
        return $res['json'];
    }

    /** Yanıt gövdesindeki kayıt listesini bulur (data / results / hotels / destinations). */
    private static function items(array $json): array
    {
        foreach (['data', 'results', 'hotels', 'destinations', 'items'] as $k) {
            if (isset($json[$k]) && is_array($json[$k])) {
                $v = $json[$k];
                if (isset($v['hotels']) && is_array($v['hotels'])) {
                    return $v['hotels'];
                }
                if (isset($v['results']) && is_array($v['results'])) {
                    return $v['results'];
                }
                return array_is_list($v) ? $v : [$v];
            }
        }
        return array_is_list($json) ? $json : [];
    }

    private static function pick(array $row, array $keys): mixed
    {
        foreach ($keys as $k) {
            $cur = $row;
            foreach (explode('.', $k) as $seg) {
                if (!is_array($cur) || !array_key_exists($seg, $cur)) {
                    $cur = null;
                    break;
                }
                $cur = $cur[$seg];
            }
            if ($cur !== null && $cur !== '') {
                return $cur;
            }
        }
        return null;
    }

    public function searchDestinations(string $query): array
    {
        $json = $this->get('destinations', 'path_destinations', ['query' => $query, 'name' => $query]);
        $out = [];
        foreach (self::items($json) as $d) {
            if (!is_array($d)) {
                continue;
            }
            $id = self::pick($d, ['dest_id', 'id', 'destination_id']);
            if ($id === null) {
                continue;
            }
            $name = (string) self::pick($d, ['name', 'label', 'city_name']);
            $country = self::pick($d, ['cc1', 'country_code', 'country']);
            $out[] = [
                'external_id' => (string) $id,
                'name' => $name,
                'type' => self::pick($d, ['dest_type', 'type', 'search_type']),
                'country_code' => is_string($country) ? strtoupper(substr($country, 0, 2)) : null,
                'latitude' => is_numeric(self::pick($d, ['latitude', 'lat'])) ? (float) self::pick($d, ['latitude', 'lat']) : null,
                'longitude' => is_numeric(self::pick($d, ['longitude', 'lng', 'lon'])) ? (float) self::pick($d, ['longitude', 'lng', 'lon']) : null,
                'label' => (string) (self::pick($d, ['label', 'name']) ?? $name),
            ];
        }
        return $out;
    }

    private function searchQuery(string $destinationId, StayCriteria $c): array
    {
        $ages = $c->allChildAges();
        return [
            'dest_id' => $destinationId,
            'checkin' => $c->checkIn,
            'checkout' => $c->checkOut,
            'adults' => $c->adults(),
            'children' => count($ages),
            'children_ages' => implode(',', $ages),
            'rooms' => $c->roomCount(),
            'currency' => 'TRY',
            'language' => 'tr',
        ];
    }

    public function searchHotels(string $destinationId, StayCriteria $criteria): array
    {
        $json = $this->get('search', 'path_search', array_filter($this->searchQuery($destinationId, $criteria), static fn ($v) => $v !== '' && $v !== null));
        $out = [];
        foreach (self::items($json) as $h) {
            if (!is_array($h)) {
                continue;
            }
            $id = self::pick($h, ['hotel_id', 'id', 'property_id']);
            if ($id === null) {
                continue;
            }
            $price = self::pick($h, ['price.total', 'price.gross', 'total_price', 'min_total_price', 'gross_price', 'price_breakdown.gross_price.value', 'composite_price_breakdown.gross_amount.value', 'price']);
            $out[] = [
                'external_id' => (string) $id,
                'name' => (string) self::pick($h, ['name', 'hotel_name', 'property_name']),
                'stars' => is_numeric(self::pick($h, ['stars', 'class', 'star_rating'])) ? (int) self::pick($h, ['stars', 'class', 'star_rating']) : null,
                'latitude' => is_numeric(self::pick($h, ['latitude', 'lat', 'location.latitude'])) ? (float) self::pick($h, ['latitude', 'lat', 'location.latitude']) : null,
                'longitude' => is_numeric(self::pick($h, ['longitude', 'lng', 'location.longitude'])) ? (float) self::pick($h, ['longitude', 'lng', 'location.longitude']) : null,
                'min_total_minor' => is_scalar($price) ? self::toMinor($price) : null,
                'currency' => (string) (self::pick($h, ['price.currency', 'currency', 'currencycode', 'price_breakdown.gross_price.currency']) ?? 'TRY'),
                'raw_tax_included' => self::pick($h, ['price.taxes_included', 'taxes_included']),
            ];
        }
        return $out;
    }

    /**
     * Tarihli aramadan gelen otel toplam fiyatlarını eşlenmiş oteller için döndürür.
     * Oda adı, konsept ve iptal koşulu yanıtta yoksa null kalır → karşılaştırmalı tasarruf iddiası OLUŞMAZ.
     */
    public function getRates(array $externalHotelIds, StayCriteria $criteria): array
    {
        $destinationId = (string) ($this->settings['_destination_for_rates'] ?? '');
        if ($destinationId === '') {
            throw new ProviderException('Fiyat sorgusu için eşlenmiş destinasyon gerekli.', $this->code());
        }
        $wanted = array_flip(array_map('strval', $externalHotelIds));
        $out = [];
        foreach ($this->searchHotels($destinationId, $criteria) as $h) {
            if (!isset($wanted[$h['external_id']]) || $h['min_total_minor'] === null) {
                continue;
            }
            $out[] = new ProviderRate(
                $h['external_id'], 'Sağlayıcı fiyatı (oda tipi belirtilmemiş)', $h['min_total_minor'], strtoupper($h['currency'] ?: 'TRY'),
                $h['raw_tax_included'] === true, null, null, null, null, false, [],
            );
        }
        return $out;
    }

    /** Fiyat sorgusunu tek destinasyon ile sınırlamak için. */
    public function withDestination(string $destinationId): self
    {
        $clone = clone $this;
        $clone->settings['_destination_for_rates'] = $destinationId;
        return $clone;
    }

    public function getHotel(string $externalHotelId): array
    {
        $json = $this->get('content', 'path_hotel', ['hotel_id' => $externalHotelId, 'language' => 'tr']);
        $d = $json['data'] ?? $json;
        return is_array($d) ? $d : [];
    }

    public function healthCheck(): ProviderHealth
    {
        $start = microtime(true);
        $json = $this->get('health', 'path_account', []);
        $quota = self::pick($json, ['data.credits_remaining', 'credits_remaining', 'data.remaining', 'remaining']);
        return new ProviderHealth(true, 'StayAPI hesabına erişildi.', is_numeric($quota) ? (int) $quota : null, (int) ((microtime(true) - $start) * 1000));
    }
}
