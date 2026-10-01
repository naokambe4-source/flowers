<?php
declare(strict_types=1);

namespace App\DTO;

/** Tarih ve oda bazlı konuk dağılımı. Doğrulama Validators\StayCriteriaValidator ile yapılır. */
final class StayCriteria
{
    /** @param RoomOccupancy[] $rooms */
    public function __construct(
        public readonly string $checkIn,
        public readonly string $checkOut,
        public readonly array $rooms,
    ) {
    }

    public function nights(): int
    {
        return (int) ((strtotime($this->checkOut) - strtotime($this->checkIn)) / 86400);
    }

    /** @return string[] Konaklanan geceler (çıkış günü hariç) */
    public function dates(): array
    {
        $out = [];
        $d = new \DateTimeImmutable($this->checkIn);
        $end = new \DateTimeImmutable($this->checkOut);
        while ($d < $end) {
            $out[] = $d->format('Y-m-d');
            $d = $d->modify('+1 day');
        }
        return $out;
    }

    public function adults(): int
    {
        return array_sum(array_map(static fn (RoomOccupancy $r) => $r->adults, $this->rooms));
    }

    public function children(): int
    {
        return array_sum(array_map(static fn (RoomOccupancy $r) => $r->children(), $this->rooms));
    }

    public function allChildAges(): array
    {
        $ages = [];
        foreach ($this->rooms as $r) {
            array_push($ages, ...$r->childAges);
        }
        sort($ages);
        return $ages;
    }

    public function roomCount(): int
    {
        return count($this->rooms);
    }

    public function leadDays(): int
    {
        return (int) floor((strtotime($this->checkIn) - strtotime(date('Y-m-d'))) / 86400);
    }

    public function summary(): string
    {
        return guest_summary($this->adults(), $this->children(), $this->roomCount());
    }

    /** Arama formu / bağlantılar için sorgu dizisi. */
    public function toQuery(): array
    {
        $q = ['giris' => $this->checkIn, 'cikis' => $this->checkOut, 'oda' => []];
        foreach ($this->rooms as $i => $r) {
            $q['oda'][$i] = ['y' => $r->adults, 'c' => implode('-', $r->childAges)];
        }
        return $q;
    }

    public function toArray(): array
    {
        return [
            'check_in' => $this->checkIn,
            'check_out' => $this->checkOut,
            'rooms' => array_map(static fn (RoomOccupancy $r) => $r->toArray(), $this->rooms),
        ];
    }

    public static function fromArray(array $a): self
    {
        $rooms = [];
        foreach ((array) ($a['rooms'] ?? []) as $r) {
            $rooms[] = new RoomOccupancy((int) $r['adults'], array_map('intval', (array) ($r['ages'] ?? [])));
        }
        return new self((string) $a['check_in'], (string) $a['check_out'], $rooms);
    }

    public function cacheKey(): string
    {
        return $this->checkIn . '|' . $this->checkOut . '|' . implode(';', array_map(static fn (RoomOccupancy $r) => $r->adults . ':' . $r->agesKey(), $this->rooms));
    }
}
