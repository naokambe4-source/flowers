<?php
declare(strict_types=1);

namespace App\DTO;

final class RoomOccupancy
{
    /** @param int[] $childAges */
    public function __construct(public readonly int $adults, public readonly array $childAges = [])
    {
    }

    public function children(): int
    {
        return count($this->childAges);
    }

    public function total(): int
    {
        return $this->adults + $this->children();
    }

    public function agesKey(): string
    {
        $a = $this->childAges;
        sort($a);
        return implode(',', $a);
    }

    public function toArray(): array
    {
        return ['adults' => $this->adults, 'ages' => array_values($this->childAges)];
    }
}
