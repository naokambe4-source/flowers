<?php
declare(strict_types=1);

namespace App\Exceptions;

/** Fiyat yeniden doğrulamada değişti; kullanıcıdan yeni toplamın kabulü istenir. */
class PriceChangedException extends DomainException
{
    public function __construct(public readonly int $oldTotal, public readonly int $newTotal)
    {
        parent::__construct('Fiyat güncellendi. Lütfen yeni toplamı kontrol edip tekrar onaylayın.');
    }
}
