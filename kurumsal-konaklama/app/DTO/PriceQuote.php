<?php
declare(strict_types=1);

namespace App\DTO;

/**
 * Fiyat motorunun sonucu.
 * kind:
 *  firm        — geçerli otel anlaşması veya yetkili sağlayıcı rate planı ile kesin fiyat
 *  target      — onaya bağlı hedef teklif (referans fiyattan hesaplanır, satış yetkisi yoktur)
 *  request     — fiyat yok; manuel teklif talebi
 *  unavailable — müsait değil / satış kapalı
 */
final class PriceQuote
{
    public function __construct(
        public readonly string $kind,
        public readonly int $hotelId,
        public readonly ?int $roomId = null,
        public readonly ?int $ratePlanId = null,
        public readonly string $currency = 'TRY',
        public readonly int $sourceTotal = 0,
        public readonly int $discountTotal = 0,
        public readonly int $total = 0,
        public readonly int $taxTotal = 0,
        public readonly bool $taxIncluded = true,
        public readonly array $breakdown = [],
        public readonly ?int $referencePriceId = null,
        public readonly ?int $referenceTotal = null,
        public readonly ?int $verifiedSavings = null,
        public readonly ?string $conceptName = null,
        public readonly ?string $roomName = null,
        public readonly bool $refundable = false,
        public readonly ?string $cancellationSummary = null,
        public readonly string $source = 'contract',
        public readonly ?int $providerId = null,
        public readonly ?string $message = null,
        public readonly ?int $promoCodeId = null,
        public readonly int $nights = 0,
    ) {
    }

    public function isBookable(): bool
    {
        return $this->kind === 'firm';
    }

    public function perNight(): int
    {
        return $this->nights > 0 ? intdiv($this->total, $this->nights) : $this->total;
    }

    /** Fiyat değişikliğini tespit etmek için deterministik özet. */
    public function hash(): string
    {
        return hash('sha256', implode('|', [
            $this->kind, $this->hotelId, $this->roomId, $this->ratePlanId, $this->currency,
            $this->sourceTotal, $this->discountTotal, $this->total, $this->taxTotal, (int) $this->taxIncluded, $this->promoCodeId,
        ]));
    }

    public function savingsPercentBp(): ?int
    {
        if ($this->verifiedSavings === null || !$this->referenceTotal) {
            return null;
        }
        return intdiv($this->verifiedSavings * 10000, $this->referenceTotal);
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
