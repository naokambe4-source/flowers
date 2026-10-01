<?php
declare(strict_types=1);

namespace App\DTO;

final class ProviderBookingResult
{
    public function __construct(
        public readonly string $reference,
        public readonly string $status,
        public readonly ?int $totalMinor = null,
        public readonly ?string $currency = null,
        public readonly array $raw = [],
    ) {
    }
}
