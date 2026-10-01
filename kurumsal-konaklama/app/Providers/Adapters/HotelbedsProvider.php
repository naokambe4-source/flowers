<?php
declare(strict_types=1);

namespace App\Providers\Adapters;

use App\DTO\ProviderBookingResult;
use App\DTO\ProviderHealth;
use App\DTO\ProviderRate;
use App\DTO\StayCriteria;
use App\Exceptions\ProviderException;
use App\Providers\Contracts\Capability;

/**
 * Hotelbeds APItude (Booking API + Content API) adaptörü.
 * Yalnız Hotelbeds ile yetkili sözleşme, test ortamı onayı ve sertifikasyon sonrası etkinleştirilmelidir.
 * Kimlik doğrulama: Api-key + X-Signature = SHA256(apiKey + secret + unixTimestamp).
 * Rezervasyon yalnız rateType=BOOKABLE veya checkrates sonrası doğrulanan rateKey ile yapılır.
 */
final class HotelbedsProvider extends AbstractProvider
{
    public static function credentialFields(): array
    {
        return ['api_key' => 'API Key', 'secret' => 'Secret'];
    }

    public function capabilities(): array
    {
        return [Capability::DESTINATIONS, Capability::SEARCH, Capability::CONTENT, Capability::AVAILABILITY, Capability::RATES, Capability::CHECK_RATE, Capability::BOOKING, Capability::BOOKING_LOOKUP, Capability::CANCEL, Capability::HEALTH];
    }

    private function headers(): array
    {
        $key = $this->credential('api_key');
        return [
            'Api-key' => $key,
            'X-Signature' => hash('sha256', $key . $this->credential('secret') . time()),
        ];
    }

    private function call(string $op, string $method, string $path, ?array $body = null, array $query = []): array
    {
        $url = $this->baseUrl() . $path . ($query ? '?' . http_build_query($query) : '');
        // Rezervasyon oluşturma tekrar edilmez (mükerrer rezervasyon riskine karşı)
        $res = $this->http()->request($op, $method, $url, $this->headers(), $body, $op === 'booking' ? 0 : 2);
        if (!is_array($res['json'])) {
            throw new ProviderException('Hotelbeds yanıtı okunamadı.', $this->code());
        }
        return $res['json'];
    }

    private static function occupancies(StayCriteria $c): array
    {
        $occ = [];
        foreach ($c->rooms as $r) {
            $paxes = array_map(static fn (int $age) => ['type' => 'CH', 'age' => $age], $r->childAges);
            $occ[] = ['rooms' => 1, 'adults' => $r->adults, 'children' => $r->children(), 'paxes' => $paxes];
        }
        return $occ;
    }

    public function searchDestinations(string $query): array
    {
        $json = $this->call('destinations', 'GET', '/hotel-content-api/1.0/locations/destinations', null, [
            'fields' => 'all', 'countryCodes' => 'TR', 'language' => 'TUR', 'from' => 1, 'to' => 1000, 'useSecondaryLanguage' => 'false',
        ]);
        $q = mb_strtolower($query);
        $out = [];
        foreach ($json['destinations'] ?? [] as $d) {
            $name = (string) ($d['name']['content'] ?? '');
            $match = str_contains(mb_strtolower($name), $q);
            foreach ($d['zones'] ?? [] as $z) {
                $zn = (string) ($z['name'] ?? '');
                if ($match || str_contains(mb_strtolower($zn), $q)) {
                    $out[] = ['external_id' => $d['code'] . ':' . $z['zoneCode'], 'name' => $zn, 'type' => 'zone', 'country_code' => $d['countryCode'] ?? null, 'latitude' => null, 'longitude' => null, 'label' => $zn . ' (' . $name . ')'];
                }
            }
            if ($match) {
                $out[] = ['external_id' => (string) $d['code'], 'name' => $name, 'type' => 'destination', 'country_code' => $d['countryCode'] ?? null, 'latitude' => null, 'longitude' => null, 'label' => $name];
            }
        }
        return $out;
    }

    public function searchHotels(string $destinationId, StayCriteria $criteria): array
    {
        [$dest, $zone] = array_pad(explode(':', $destinationId, 2), 2, null);
        $body = [
            'stay' => ['checkIn' => $criteria->checkIn, 'checkOut' => $criteria->checkOut],
            'occupancies' => self::occupancies($criteria),
            'destination' => array_filter(['code' => $dest, 'zone' => $zone !== null ? (int) $zone : null]),
        ];
        $json = $this->call('search', 'POST', '/hotel-api/1.0/hotels', $body);
        $out = [];
        foreach ($json['hotels']['hotels'] ?? [] as $h) {
            $out[] = [
                'external_id' => (string) $h['code'], 'name' => (string) ($h['name'] ?? ''),
                'stars' => isset($h['categoryCode']) && preg_match('/(\d)/', (string) $h['categoryCode'], $m) ? (int) $m[1] : null,
                'latitude' => isset($h['latitude']) ? (float) $h['latitude'] : null, 'longitude' => isset($h['longitude']) ? (float) $h['longitude'] : null,
                'min_total_minor' => self::toMinor($h['minRate'] ?? null), 'currency' => (string) ($h['currency'] ?? 'EUR'),
            ];
        }
        return $out;
    }

    public function getHotel(string $externalHotelId): array
    {
        $json = $this->call('content', 'GET', '/hotel-content-api/1.0/hotels/' . rawurlencode($externalHotelId) . '/details', null, ['language' => 'TUR', 'useSecondaryLanguage' => 'true']);
        return $json['hotel'] ?? [];
    }

    public function getAvailability(string $externalHotelId, StayCriteria $criteria): array
    {
        $rates = $this->getRates([$externalHotelId], $criteria);
        return ['available' => $rates !== [], 'message' => $rates ? null : 'Sağlayıcıda müsait oda bulunamadı.'];
    }

    public function getRates(array $externalHotelIds, StayCriteria $criteria): array
    {
        $body = [
            'stay' => ['checkIn' => $criteria->checkIn, 'checkOut' => $criteria->checkOut],
            'occupancies' => self::occupancies($criteria),
            'hotels' => ['hotel' => array_map('intval', $externalHotelIds)],
        ];
        $json = $this->call('rates', 'POST', '/hotel-api/1.0/hotels', $body);
        $out = [];
        foreach ($json['hotels']['hotels'] ?? [] as $h) {
            foreach ($h['rooms'] ?? [] as $room) {
                foreach ($room['rates'] ?? [] as $rate) {
                    $out[] = $this->mapRate((string) $h['code'], (string) ($room['name'] ?? ''), (string) ($h['currency'] ?? 'EUR'), $rate);
                }
            }
        }
        return $out;
    }

    private function mapRate(string $hotelCode, string $roomName, string $currency, array $rate): ProviderRate
    {
        $policies = $rate['cancellationPolicies'] ?? [];
        $summary = null;
        if ($policies) {
            $first = $policies[0];
            $summary = 'Ücretsiz iptal son tarihi: ' . substr((string) ($first['from'] ?? ''), 0, 16);
        }
        $taxesIncluded = (bool) ($rate['taxes']['allIncluded'] ?? false);
        return new ProviderRate(
            $hotelCode, $roomName, (int) self::toMinor($rate['sellingRate'] ?? $rate['net'] ?? '0'), $currency, $taxesIncluded,
            $rate['boardCode'] ?? null, isset($rate['rateClass']) ? $rate['rateClass'] !== 'NRF' : null, $summary,
            (string) ($rate['rateKey'] ?? ''), ($rate['rateType'] ?? '') === 'BOOKABLE', ['rateType' => $rate['rateType'] ?? null],
        );
    }

    public function checkRate(string $rateKey, StayCriteria $criteria): ProviderRate
    {
        $json = $this->call('check_rate', 'POST', '/hotel-api/1.0/checkrates', ['rooms' => [['rateKey' => $rateKey]]]);
        $h = $json['hotel'] ?? null;
        $room = $h['rooms'][0] ?? null;
        $rate = $room['rates'][0] ?? null;
        if (!$h || !$rate) {
            throw new ProviderException('Fiyat artık geçerli değil.', $this->code());
        }
        $mapped = $this->mapRate((string) $h['code'], (string) ($room['name'] ?? ''), (string) ($h['currency'] ?? 'EUR'), $rate);
        return new ProviderRate($mapped->externalHotelId, $mapped->roomName, $mapped->totalMinor, $mapped->currency, $mapped->taxIncluded, $mapped->boardCode, $mapped->refundable, $mapped->cancellationSummary, $mapped->rateKey, true, $mapped->raw);
    }

    public function createBooking(string $rateKey, StayCriteria $criteria, array $holder, array $guests, string $clientReference): ProviderBookingResult
    {
        $paxes = [];
        foreach ($guests as $g) {
            $paxes[] = ['roomId' => (int) ($g['room'] ?? 1), 'type' => !empty($g['is_child']) ? 'CH' : 'AD', 'name' => $g['first_name'], 'surname' => $g['last_name']] + (!empty($g['is_child']) ? ['age' => (int) $g['age']] : []);
        }
        $json = $this->call('booking', 'POST', '/hotel-api/1.0/bookings', [
            'holder' => ['name' => $holder['first_name'], 'surname' => $holder['last_name']],
            'rooms' => [['rateKey' => $rateKey, 'paxes' => $paxes]],
            'clientReference' => substr($clientReference, 0, 20),
            'tolerance' => 0,
        ]);
        $b = $json['booking'] ?? null;
        if (!$b || empty($b['reference'])) {
            throw new ProviderException('Hotelbeds rezervasyon referansı dönmedi.', $this->code());
        }
        return new ProviderBookingResult((string) $b['reference'], strtolower((string) ($b['status'] ?? 'confirmed')), self::toMinor($b['totalSellingRate'] ?? $b['totalNet'] ?? null), $b['currency'] ?? null, ['status' => $b['status'] ?? null]);
    }

    public function getBooking(string $reference, array $context = []): ProviderBookingResult
    {
        $json = $this->call('booking_lookup', 'GET', '/hotel-api/1.0/bookings/' . rawurlencode($reference));
        $b = $json['booking'] ?? [];
        return new ProviderBookingResult($reference, strtolower((string) ($b['status'] ?? 'unknown')), self::toMinor($b['totalNet'] ?? null), $b['currency'] ?? null);
    }

    public function cancelBooking(string $reference, array $context = []): ProviderBookingResult
    {
        $json = $this->call('cancel', 'DELETE', '/hotel-api/1.0/bookings/' . rawurlencode($reference), null, ['cancellationFlag' => 'CANCELLATION']);
        $b = $json['booking'] ?? [];
        return new ProviderBookingResult($reference, strtolower((string) ($b['status'] ?? 'cancelled')));
    }

    public function healthCheck(): ProviderHealth
    {
        $start = microtime(true);
        $json = $this->call('health', 'GET', '/hotel-api/1.0/status');
        return new ProviderHealth(($json['status'] ?? '') === 'OK', 'Hotelbeds durum: ' . ($json['status'] ?? 'bilinmiyor'), null, (int) ((microtime(true) - $start) * 1000));
    }
}
