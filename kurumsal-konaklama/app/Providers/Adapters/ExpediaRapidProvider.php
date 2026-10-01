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
 * Expedia Partner Solutions — Rapid API adaptörü.
 * Yalnız EPS ile yetkili sözleşme ve onaylı test sonrası etkinleştirilmelidir.
 * Kimlik doğrulama: "EAN APIKey=..,Signature=SHA512(apiKey+secret+timestamp),timestamp=.."
 * Rezervasyon için ödeme tipi (ör. affiliate_collect / invoice) sözleşmeye göre ayarlardan belirlenmelidir;
 * tanımlı değilse rezervasyon yeteneği kapalıdır.
 */
final class ExpediaRapidProvider extends AbstractProvider
{
    public static function credentialFields(): array
    {
        return ['api_key' => 'API Key', 'secret' => 'Shared Secret'];
    }

    public function capabilities(): array
    {
        $caps = [Capability::DESTINATIONS, Capability::CONTENT, Capability::AVAILABILITY, Capability::RATES, Capability::CHECK_RATE, Capability::HEALTH];
        if ((string) $this->setting('payment_type', '') !== '') {
            array_push($caps, Capability::BOOKING, Capability::BOOKING_LOOKUP, Capability::CANCEL);
        }
        return $caps;
    }

    private function headers(): array
    {
        $key = $this->credential('api_key');
        $ts = time();
        $sig = hash('sha512', $key . $this->credential('secret') . $ts);
        $h = ['Authorization' => "EAN APIKey=$key,Signature=$sig,timestamp=$ts"];
        $ip = (string) $this->setting('customer_ip', '');
        if ($ip !== '') {
            $h['Customer-Ip'] = $ip;
        }
        return $h;
    }

    private function call(string $op, string $method, string $pathOrUrl, ?array $body = null, array $query = []): array
    {
        $url = str_starts_with($pathOrUrl, '/') ? $this->baseUrl() . $pathOrUrl : $pathOrUrl;
        if (!str_starts_with($url, $this->baseUrl())) {
            throw new ProviderException('Beklenmeyen sağlayıcı bağlantısı.', $this->code());
        }
        if ($query) {
            $url .= (str_contains($url, '?') ? '&' : '?') . self::buildQuery($query);
        }
        $res = $this->http()->request($op, $method, $url, $this->headers(), $body, $op === 'booking' ? 0 : 2);
        return is_array($res['json']) ? $res['json'] : [];
    }

    /** Rapid tekrar eden parametreleri (occupancy=2&occupancy=1-5) bekler. */
    private static function buildQuery(array $query): string
    {
        $parts = [];
        foreach ($query as $k => $v) {
            foreach ((array) $v as $item) {
                $parts[] = rawurlencode($k) . '=' . rawurlencode((string) $item);
            }
        }
        return implode('&', $parts);
    }

    private static function occupancy(StayCriteria $c): array
    {
        return array_map(static fn ($r) => $r->adults . ($r->childAges ? '-' . implode(',', $r->childAges) : ''), $c->rooms);
    }

    public function searchDestinations(string $query): array
    {
        $json = $this->call('destinations', 'GET', '/v3/regions', null, ['language' => 'tr-TR', 'include' => 'standard', 'area' => '150,36.8969,30.7133', 'supply_source' => 'expedia']);
        $q = mb_strtolower($query);
        $out = [];
        foreach ($json as $r) {
            $name = (string) ($r['name'] ?? '');
            if (!str_contains(mb_strtolower($name), $q)) {
                continue;
            }
            $out[] = [
                'external_id' => (string) $r['id'], 'name' => $name, 'type' => $r['type'] ?? null, 'country_code' => $r['country_code'] ?? null,
                'latitude' => isset($r['coordinates']['center_latitude']) ? (float) $r['coordinates']['center_latitude'] : null,
                'longitude' => isset($r['coordinates']['center_longitude']) ? (float) $r['coordinates']['center_longitude'] : null,
                'label' => (string) ($r['name_full'] ?? $name),
            ];
        }
        return $out;
    }

    public function getHotel(string $externalHotelId): array
    {
        $json = $this->call('content', 'GET', '/v3/properties/content', null, ['language' => 'tr-TR', 'supply_source' => 'expedia', 'property_id' => $externalHotelId]);
        return $json[$externalHotelId] ?? [];
    }

    public function getAvailability(string $externalHotelId, StayCriteria $criteria): array
    {
        $rates = $this->getRates([$externalHotelId], $criteria);
        return ['available' => $rates !== [], 'message' => $rates ? null : 'Sağlayıcıda müsait oda bulunamadı.'];
    }

    public function getRates(array $externalHotelIds, StayCriteria $criteria): array
    {
        $json = $this->call('rates', 'GET', '/v3/properties/availability', null, [
            'checkin' => $criteria->checkIn, 'checkout' => $criteria->checkOut, 'currency' => 'TRY', 'country_code' => 'TR',
            'language' => 'tr-TR', 'occupancy' => self::occupancy($criteria), 'property_id' => array_map('strval', $externalHotelIds),
            'rate_plan_count' => 3, 'sales_channel' => 'website', 'sales_environment' => 'hotel_only',
        ]);
        $out = [];
        foreach ($json as $prop) {
            foreach ($prop['rooms'] ?? [] as $room) {
                foreach ($room['rates'] ?? [] as $rate) {
                    $out[] = $this->mapRate((string) $prop['property_id'], (string) ($room['room_name'] ?? ''), $rate);
                }
            }
        }
        return $out;
    }

    private function mapRate(string $propertyId, string $roomName, array $rate): ProviderRate
    {
        $total = 0;
        $currency = 'TRY';
        foreach ($rate['occupancy_pricing'] ?? [] as $op) {
            $inc = $op['totals']['inclusive']['request_currency'] ?? null;
            if ($inc) {
                $total += (int) self::toMinor($inc['value'] ?? '0');
                $currency = (string) ($inc['currency'] ?? $currency);
            }
        }
        $refundable = isset($rate['refundable']) ? (bool) $rate['refundable'] : null;
        return new ProviderRate(
            $propertyId, $roomName, $total, $currency, true, null, $refundable,
            $refundable === false ? 'İade edilemez' : null, self::priceCheckHref($rate),
            false, ['id' => $rate['id'] ?? null],
        );
    }

    private static function priceCheckHref(array $rate): string
    {
        $groups = $rate['bed_groups'] ?? [];
        if (!is_array($groups) || !$groups) {
            return '';
        }
        $first = reset($groups);
        return (string) ($first['links']['price_check']['href'] ?? '');
    }

    public function checkRate(string $rateKey, StayCriteria $criteria): ProviderRate
    {
        $json = $this->call('check_rate', 'GET', $rateKey);
        $status = $json['status'] ?? '';
        if ($status === 'sold_out') {
            throw new ProviderException('Oda artık müsait değil.', $this->code());
        }
        $total = 0;
        $currency = 'TRY';
        foreach ($json['occupancy_pricing'] ?? [] as $op) {
            $inc = $op['totals']['inclusive']['request_currency'] ?? null;
            if ($inc) {
                $total += (int) self::toMinor($inc['value'] ?? '0');
                $currency = (string) ($inc['currency'] ?? $currency);
            }
        }
        $bookHref = (string) ($json['links']['book']['href'] ?? '');
        return new ProviderRate('', '', $total, $currency, true, null, null, null, $bookHref, $bookHref !== '' && $this->supports(Capability::BOOKING), ['status' => $status]);
    }

    public function createBooking(string $rateKey, StayCriteria $criteria, array $holder, array $guests, string $clientReference): ProviderBookingResult
    {
        if (!$this->supports(Capability::BOOKING)) {
            $this->unsupported('rezervasyon oluşturma (ödeme tipi tanımlı değil)');
        }
        $rooms = [];
        foreach ($criteria->rooms as $i => $_) {
            $lead = array_values(array_filter($guests, static fn ($g) => (int) ($g['room'] ?? 1) === $i + 1 && empty($g['is_child'])))[0] ?? $holder;
            $rooms[] = ['given_name' => $lead['first_name'], 'family_name' => $lead['last_name'], 'smoking' => false];
        }
        $json = $this->call('booking', 'POST', $rateKey, [
            'affiliate_reference_id' => substr($clientReference, 0, 28),
            'hold' => false,
            'email' => $holder['email'] ?? '',
            'phone' => ['country_code' => '90', 'number' => preg_replace('/\D+/', '', (string) ($holder['phone'] ?? ''))],
            'rooms' => $rooms,
            'payments' => [['type' => (string) $this->setting('payment_type')]],
        ]);
        if (empty($json['itinerary_id'])) {
            throw new ProviderException('Expedia rezervasyon numarası dönmedi.', $this->code());
        }
        return new ProviderBookingResult((string) $json['itinerary_id'], 'confirmed', null, null, ['links' => $json['links'] ?? []]);
    }

    public function getBooking(string $reference, array $context = []): ProviderBookingResult
    {
        $json = $this->call('booking_lookup', 'GET', '/v3/itineraries/' . rawurlencode($reference), null, ['email' => (string) ($context['email'] ?? '')]);
        $status = 'confirmed';
        foreach ($json['rooms'] ?? [] as $r) {
            if (($r['status'] ?? '') === 'canceled') {
                $status = 'cancelled';
            }
        }
        return new ProviderBookingResult($reference, $status, null, null, ['rooms' => $json['rooms'] ?? []]);
    }

    public function cancelBooking(string $reference, array $context = []): ProviderBookingResult
    {
        $booking = $this->getBooking($reference, $context);
        foreach ($booking->raw['rooms'] ?? [] as $room) {
            $href = $room['links']['cancel']['href'] ?? null;
            if ($href) {
                $this->call('cancel', 'DELETE', $href);
            }
        }
        return new ProviderBookingResult($reference, 'cancelled');
    }

    public function healthCheck(): ProviderHealth
    {
        $start = microtime(true);
        $json = $this->call('health', 'GET', '/v3/regions', null, ['language' => 'en-US', 'include' => 'standard', 'area' => '5,36.8969,30.7133']);
        return new ProviderHealth(is_array($json), 'Expedia Rapid erişilebilir (' . count($json) . ' bölge döndü).', null, (int) ((microtime(true) - $start) * 1000));
    }
}
