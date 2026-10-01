<?php
declare(strict_types=1);

namespace App\Providers\Contracts;

use App\DTO\ProviderBookingResult;
use App\DTO\ProviderHealth;
use App\DTO\ProviderRate;
use App\DTO\StayCriteria;

/**
 * Tüm otel sağlayıcıları bu sözleşmeyi uygular. Yeni sağlayıcı tek bir adaptör sınıfı ile eklenir.
 * Desteklenmeyen işlemler UnsupportedCapabilityException fırlatır; asla başarılıymış gibi davranmaz.
 */
interface HotelProviderInterface
{
    public function code(): string;

    /** @return string[] Capability sabitleri */
    public function capabilities(): array;

    public function supports(string $capability): bool;

    /** @return array<int, array{external_id:string, name:string, type:?string, country_code:?string, latitude:?float, longitude:?float, label:string}> */
    public function searchDestinations(string $query): array;

    /** Tarihli otel arama (fiyat içerebilir). @return array<int, array{external_id:string, name:string, stars:?int, latitude:?float, longitude:?float, min_total_minor:?int, currency:?string}> */
    public function searchHotels(string $destinationId, StayCriteria $criteria): array;

    /** Otel içeriği (fiyat değil). */
    public function getHotel(string $externalHotelId): array;

    /** @return array{available:bool, message:?string} */
    public function getAvailability(string $externalHotelId, StayCriteria $criteria): array;

    /** @param string[] $externalHotelIds @return ProviderRate[] */
    public function getRates(array $externalHotelIds, StayCriteria $criteria): array;

    /** Rezervasyon öncesi fiyat ve müsaitliğin yeniden doğrulanması. */
    public function checkRate(string $rateKey, StayCriteria $criteria): ProviderRate;

    public function createBooking(string $rateKey, StayCriteria $criteria, array $holder, array $guests, string $clientReference): ProviderBookingResult;

    public function getBooking(string $reference, array $context = []): ProviderBookingResult;

    public function cancelBooking(string $reference, array $context = []): ProviderBookingResult;

    public function healthCheck(): \App\DTO\ProviderHealth;
}
