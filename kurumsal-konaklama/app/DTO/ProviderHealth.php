<?php
declare(strict_types=1);

namespace App\DTO;

final class ProviderHealth
{
    public function __construct(
        public readonly bool $ok,
        public readonly string $message,
        public readonly ?int $quotaRemaining = null,
        public readonly ?int $latencyMs = null,
    ) {
    }
}
