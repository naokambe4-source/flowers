<?php
declare(strict_types=1);

namespace App\Validators;

use App\DTO\RoomOccupancy;
use App\DTO\StayCriteria;
use App\Exceptions\ValidationException;
use App\Services\SettingsService;

/**
 * Arama / rezervasyon kriterlerini sunucu tarafında doğrular.
 * İki biçimi kabul eder:
 *  - Oda bazlı: oda[0][y]=2, oda[0][c]=5-9
 *  - Basit (JS kapalı): yetiskin=3, cocuk=1, yaslar=7, oda_sayisi=2 → odalara dengeli dağıtılır
 */
final class StayCriteriaValidator
{
    public const MAX_ADULTS_PER_ROOM = 6;
    public const MAX_CHILDREN_PER_ROOM = 4;

    /** Tarih verilmediyse null döner (arama formu boş açılabilir). */
    public static function fromInput(array $in, bool $required = true): ?StayCriteria
    {
        $checkIn = self::date($in['giris'] ?? $in['check_in'] ?? null);
        $checkOut = self::date($in['cikis'] ?? $in['check_out'] ?? null);
        if ($checkIn === null && $checkOut === null && !$required) {
            return null;
        }
        $errors = [];
        $today = date('Y-m-d');
        if ($checkIn === null) {
            $errors['giris'] = 'Giriş tarihini seçin.';
        } elseif ($checkIn < $today) {
            $errors['giris'] = 'Geçmiş bir tarih seçilemez.';
        }
        if ($checkOut === null) {
            $errors['cikis'] = 'Çıkış tarihini seçin.';
        } elseif ($checkIn !== null && $checkOut <= $checkIn) {
            $errors['cikis'] = 'Çıkış tarihi girişten en az bir gün sonra olmalıdır.';
        }
        $maxNights = SettingsService::int('booking.max_nights', 30);
        if ($checkIn && $checkOut && $checkOut > $checkIn && (strtotime($checkOut) - strtotime($checkIn)) / 86400 > $maxNights) {
            $errors['cikis'] = "En fazla $maxNights gece seçilebilir.";
        }
        if ($checkIn && $checkIn > date('Y-m-d', strtotime('+18 months'))) {
            $errors['giris'] = 'En fazla 18 ay sonrası için arama yapılabilir.';
        }

        $rooms = self::rooms($in, $errors);
        if ($errors) {
            throw new ValidationException($errors, reset($errors));
        }
        return new StayCriteria((string) $checkIn, (string) $checkOut, $rooms);
    }

    private static function date(mixed $v): ?string
    {
        if (!is_string($v) || $v === '') {
            return null;
        }
        if (preg_match('/^(\d{2})\.(\d{2})\.(\d{4})$/', $v, $m)) {
            $v = "$m[3]-$m[2]-$m[1]";
        }
        $d = \DateTimeImmutable::createFromFormat('!Y-m-d', $v);
        return $d && $d->format('Y-m-d') === $v ? $v : null;
    }

    /** @return RoomOccupancy[] */
    private static function rooms(array $in, array &$errors): array
    {
        $maxRooms = SettingsService::int('booking.max_rooms', 5);
        $rooms = [];
        if (isset($in['oda']) && is_array($in['oda'])) {
            foreach (array_slice($in['oda'], 0, $maxRooms + 1) as $r) {
                if (!is_array($r)) {
                    continue;
                }
                $adults = (int) ($r['y'] ?? $r['adults'] ?? 0);
                $agesRaw = $r['c'] ?? $r['ages'] ?? '';
                $ages = self::ages($agesRaw);
                if ($ages === null) {
                    $errors['oda'] = 'Çocuk yaşları 0 ile 17 arasında olmalıdır.';
                    return [];
                }
                $rooms[] = [$adults, $ages];
            }
        } else {
            $adults = (int) ($in['yetiskin'] ?? 2);
            $children = (int) ($in['cocuk'] ?? 0);
            $count = max(1, (int) ($in['oda_sayisi'] ?? 1));
            $ages = self::ages($in['yaslar'] ?? '') ?? [];
            if ($children > 0 && count($ages) !== $children) {
                $errors['yaslar'] = 'Her çocuk için yaş girin (örn. 5, 9).';
                return [];
            }
            if ($count > $adults) {
                $errors['oda_sayisi'] = 'Her odada en az bir yetişkin bulunmalıdır.';
                return [];
            }
            for ($i = 0; $i < $count; $i++) {
                $rooms[$i] = [intdiv($adults, $count) + ($i < $adults % $count ? 1 : 0), []];
            }
            foreach ($ages as $i => $age) {
                $rooms[$i % $count][1][] = $age;
            }
        }
        if (!$rooms) {
            $rooms = [[2, []]];
        }
        if (count($rooms) > $maxRooms) {
            $errors['oda'] = "Tek seferde en fazla $maxRooms oda seçilebilir.";
            return [];
        }
        $out = [];
        foreach ($rooms as $i => [$adults, $ages]) {
            if ($adults < 1) {
                $errors['oda'] = ($i + 1) . '. odada en az bir yetişkin olmalıdır.';
                return [];
            }
            if ($adults > self::MAX_ADULTS_PER_ROOM || count($ages) > self::MAX_CHILDREN_PER_ROOM) {
                $errors['oda'] = ($i + 1) . '. oda için konuk sayısı fazla. Lütfen oda ekleyin.';
                return [];
            }
            $out[] = new RoomOccupancy($adults, $ages);
        }
        return $out;
    }

    /** @return int[]|null */
    private static function ages(mixed $raw): ?array
    {
        if (is_array($raw)) {
            $parts = $raw;
        } else {
            $raw = trim((string) $raw);
            $parts = $raw === '' ? [] : preg_split('/[\s,;\-]+/', $raw);
        }
        $ages = [];
        foreach ((array) $parts as $p) {
            if ($p === '' || $p === null) {
                continue;
            }
            if (!is_numeric($p) || (int) $p < 0 || (int) $p > 17) {
                return null;
            }
            $ages[] = (int) $p;
        }
        return $ages;
    }
}
