<?php
declare(strict_types=1);

namespace App\Providers\Adapters;

use App\Core\App;
use App\DTO\ProviderBookingResult;
use App\DTO\ProviderHealth;
use App\DTO\ProviderRate;
use App\DTO\StayCriteria;
use App\Exceptions\ProviderException;
use App\Providers\Contracts\Capability;
use App\Services\InventoryService;
use App\Services\Pricing\PricingContext;
use App\Services\Pricing\PricingProfile;
use App\Services\Pricing\PricingService;

/**
 * Yerel anlaşmalı veriler: oteller, odalar, anlaşmalı fiyatlar ve kontenjan.
 * Rezervasyon yerel stokta BookingService tarafından transaction ile oluşturulur;
 * bu adaptör yerel referansı doğrular, dış sistem çağrısı yapmaz.
 */
final class ManualHotelProvider extends AbstractProvider
{
    public static function credentialFields(): array
    {
        return [];
    }

    public function capabilities(): array
    {
        return [Capability::SEARCH, Capability::CONTENT, Capability::AVAILABILITY, Capability::RATES, Capability::CHECK_RATE, Capability::BOOKING, Capability::BOOKING_LOOKUP, Capability::CANCEL, Capability::HEALTH];
    }

    public function searchDestinations(string $query): array
    {
        $rows = App::db()->fetchAll("SELECT id, name, latitude, longitude FROM regions WHERE is_active = 1 AND name LIKE ? ORDER BY sort LIMIT 20", ['%' . $query . '%']);
        return array_map(static fn ($r) => [
            'external_id' => (string) $r['id'], 'name' => $r['name'], 'type' => 'region', 'country_code' => 'TR',
            'latitude' => $r['latitude'] !== null ? (float) $r['latitude'] : null, 'longitude' => $r['longitude'] !== null ? (float) $r['longitude'] : null,
            'label' => $r['name'] . ', Antalya',
        ], $rows);
    }

    public function searchHotels(string $destinationId, StayCriteria $criteria): array
    {
        $rows = App::db()->fetchAll(
            "SELECT h.id, h.name, h.stars, h.latitude, h.longitude FROM hotels h JOIN regions r ON r.id = h.region_id
             WHERE h.status = 'published' AND (r.id = :r OR r.parent_id = :r2)",
            ['r' => (int) $destinationId, 'r2' => (int) $destinationId],
        );
        $ids = array_map(static fn ($r) => (int) $r['id'], $rows);
        $ctx = PricingContext::load(App::db(), $ids, $criteria);
        $pricing = new PricingService();
        $profile = PricingProfile::forUser(App::db(), null);
        $out = [];
        foreach ($rows as $r) {
            $q = $pricing->bestForHotel($ctx, $profile, (int) $r['id']);
            $out[] = [
                'external_id' => (string) $r['id'], 'name' => $r['name'], 'stars' => $r['stars'] !== null ? (int) $r['stars'] : null,
                'latitude' => $r['latitude'] !== null ? (float) $r['latitude'] : null, 'longitude' => $r['longitude'] !== null ? (float) $r['longitude'] : null,
                'min_total_minor' => in_array($q->kind, ['firm', 'target'], true) ? $q->total : null, 'currency' => $q->currency,
            ];
        }
        return $out;
    }

    public function getHotel(string $externalHotelId): array
    {
        $h = App::db()->fetch('SELECT * FROM hotels WHERE id = ?', [(int) $externalHotelId]);
        if (!$h) {
            throw new ProviderException('Otel bulunamadı.', 'manual');
        }
        return $h;
    }

    public function getAvailability(string $externalHotelId, StayCriteria $criteria): array
    {
        $ctx = PricingContext::load(App::db(), [(int) $externalHotelId], $criteria);
        foreach ($ctx->rooms[(int) $externalHotelId] ?? [] as $roomId => $room) {
            $ev = InventoryService::evaluate($ctx->inventory[$roomId] ?? [], $ctx->stopSales[(int) $externalHotelId] ?? [], $roomId, $criteria->dates(), $criteria->roomCount(), $criteria->nights());
            if ($ev['ok']) {
                return ['available' => true, 'message' => null];
            }
        }
        return ['available' => false, 'message' => 'Seçilen tarihlerde müsait oda bulunamadı.'];
    }

    public function getRates(array $externalHotelIds, StayCriteria $criteria): array
    {
        $ids = array_map('intval', $externalHotelIds);
        $ctx = PricingContext::load(App::db(), $ids, $criteria);
        $pricing = new PricingService();
        $profile = PricingProfile::forUser(App::db(), null);
        $out = [];
        foreach ($ids as $id) {
            foreach ($pricing->quotesForHotel($ctx, $profile, $id) as $q) {
                if ($q->kind !== 'firm' && $q->kind !== 'target') {
                    continue;
                }
                $out[] = new ProviderRate((string) $id, (string) $q->roomName, $q->sourceTotal, $q->currency, true, $q->conceptName, $q->refundable, $q->cancellationSummary, $q->roomId . ':' . $q->ratePlanId, $q->kind === 'firm');
            }
        }
        return $out;
    }

    public function checkRate(string $rateKey, StayCriteria $criteria): ProviderRate
    {
        [$roomId, $planId] = array_map('intval', explode(':', $rateKey) + [0, 0]);
        $hotelId = (int) App::db()->value('SELECT hotel_id FROM rooms WHERE id = ?', [$roomId]);
        $ctx = PricingContext::load(App::db(), [$hotelId], $criteria);
        $q = (new PricingService())->quote($ctx, PricingProfile::forUser(App::db(), null), $hotelId, $roomId, $planId);
        if ($q->kind === 'unavailable' || $q->kind === 'request') {
            throw new ProviderException((string) $q->message, 'manual');
        }
        return new ProviderRate((string) $hotelId, (string) $q->roomName, $q->sourceTotal, $q->currency, true, $q->conceptName, $q->refundable, $q->cancellationSummary, $rateKey, $q->kind === 'firm');
    }

    public function createBooking(string $rateKey, StayCriteria $criteria, array $holder, array $guests, string $clientReference): ProviderBookingResult
    {
        $row = App::db()->fetch('SELECT code, status FROM bookings WHERE code = ?', [$clientReference]);
        if (!$row) {
            throw new ProviderException('Yerel rezervasyon kaydı bulunamadı.', 'manual');
        }
        return new ProviderBookingResult($row['code'], $row['status']);
    }

    public function getBooking(string $reference, array $context = []): ProviderBookingResult
    {
        $row = App::db()->fetch('SELECT code, status, total_minor, currency FROM bookings WHERE code = ?', [$reference]);
        if (!$row) {
            throw new ProviderException('Rezervasyon bulunamadı.', 'manual');
        }
        return new ProviderBookingResult($row['code'], $row['status'], (int) $row['total_minor'], $row['currency']);
    }

    public function cancelBooking(string $reference, array $context = []): ProviderBookingResult
    {
        // Yerel stok iadesi BookingService::cancel içinde yapılır.
        return $this->getBooking($reference);
    }

    public function healthCheck(): ProviderHealth
    {
        $start = microtime(true);
        $count = (int) App::db()->value("SELECT COUNT(*) FROM hotels WHERE status = 'published'");
        return new ProviderHealth(true, "Yerel veritabanı erişilebilir. Yayındaki otel: $count", null, (int) ((microtime(true) - $start) * 1000));
    }
}
