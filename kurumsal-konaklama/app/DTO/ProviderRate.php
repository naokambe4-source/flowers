<?php
declare(strict_types=1);

namespace App\DTO;

/** Sağlayıcıdan gelen normalleştirilmiş oda fiyatı. */
final class ProviderRate
{
    public function __construct(
        public readonly string $externalHotelId,
        public readonly string $roomName,
        public readonly int $totalMinor,
        public readonly string $currency,
        public readonly bool $taxIncluded,
        public readonly ?string $boardCode = null,
        public readonly ?bool $refundable = null,
        public readonly ?string $cancellationSummary = null,
        public readonly ?string $rateKey = null,
        public readonly bool $bookable = false,
        public readonly array $raw = [],
    ) {
    }
}
