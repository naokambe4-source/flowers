<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Exceptions\DomainException;

/**
 * Takvim tabanlı stok: günlük oda adedi, satış açık/kapalı, stop sale, min/max konaklama.
 * Rezervasyonda satır kilidi + atomik güncelleme ile double booking engellenir.
 */
final class InventoryService
{
    public function __construct(private readonly Database $db)
    {
    }

    /**
     * Önceden yüklenmiş verilerle müsaitlik kontrolü.
     * @param array<string,array> $inventoryByDate
     * @return array{ok:bool, managed:bool, reason:?string, min_units:int}
     */
    public static function evaluate(array $inventoryByDate, array $stopSales, int $roomId, array $dates, int $units, int $nights): array
    {
        foreach ($stopSales as $s) {
            if (($s['room_id'] === null || (int) $s['room_id'] === $roomId)) {
                foreach ($dates as $d) {
                    if ($d >= $s['date_from'] && $d <= $s['date_to']) {
                        return ['ok' => false, 'managed' => true, 'reason' => 'Seçilen tarihlerde satış durdurulmuştur.', 'min_units' => 0];
                    }
                }
            }
        }
        if (!$inventoryByDate) {
            return ['ok' => false, 'managed' => false, 'reason' => 'Bu tarihler için kontenjan tanımlı değil.', 'min_units' => 0];
        }
        $min = PHP_INT_MAX;
        foreach ($dates as $i => $d) {
            $iv = $inventoryByDate[$d] ?? null;
            if ($iv === null) {
                return ['ok' => false, 'managed' => true, 'reason' => 'Seçilen tarihlerin bir kısmı için kontenjan tanımlı değil.', 'min_units' => 0];
            }
            if ((int) $iv['is_open'] !== 1) {
                return ['ok' => false, 'managed' => true, 'reason' => 'Seçilen tarihlerde satış kapalıdır.', 'min_units' => 0];
            }
            if ($i === 0) {
                if ($iv['min_stay'] !== null && $nights < (int) $iv['min_stay']) {
                    return ['ok' => false, 'managed' => true, 'reason' => 'Bu giriş tarihinde en az ' . (int) $iv['min_stay'] . ' gece konaklama gereklidir.', 'min_units' => 0];
                }
                if ($iv['max_stay'] !== null && $nights > (int) $iv['max_stay']) {
                    return ['ok' => false, 'managed' => true, 'reason' => 'Bu giriş tarihinde en fazla ' . (int) $iv['max_stay'] . ' gece konaklanabilir.', 'min_units' => 0];
                }
            }
            $free = (int) $iv['total_units'] - (int) $iv['booked_units'];
            $min = min($min, $free);
        }
        if ($min < $units) {
            return ['ok' => false, 'managed' => true, 'reason' => $min > 0 ? "Bu oda tipinden yalnız $min adet müsait." : 'Seçilen tarihlerde bu oda dolu.', 'min_units' => max(0, $min)];
        }
        return ['ok' => true, 'managed' => true, 'reason' => null, 'min_units' => $min];
    }

    /**
     * Stok düşer. Transaction içinde çağrılmalıdır. Satırlar tarih sırasıyla kilitlenir (deadlock riskini azaltır),
     * ardından koşullu UPDATE ile atomik artış yapılır.
     * @throws DomainException stok yetersizse
     */
    public function reserve(int $bookingId, int $hotelId, int $roomId, array $dates, int $units): void
    {
        if (!$this->db->inTransaction()) {
            throw new \LogicException('Stok rezervasyonu transaction içinde yapılmalıdır.');
        }
        $first = $dates[0];
        $last = $dates[count($dates) - 1];
        $rows = $this->db->fetchAll(
            'SELECT stay_date, total_units, booked_units, is_open FROM inventory WHERE room_id = ? AND stay_date BETWEEN ? AND ? ORDER BY stay_date FOR UPDATE',
            [$roomId, $first, $last],
        );
        $stop = (int) $this->db->value(
            'SELECT COUNT(*) FROM stop_sales WHERE hotel_id = ? AND (room_id IS NULL OR room_id = ?) AND date_from <= ? AND date_to >= ?',
            [$hotelId, $roomId, $last, $first],
        );
        if ($stop > 0 || count($rows) !== count($dates)) {
            throw new DomainException('Seçilen oda bu tarihlerde artık satışta değil. Lütfen başka bir oda veya tarih seçin.');
        }
        foreach ($rows as $r) {
            if ((int) $r['is_open'] !== 1 || (int) $r['total_units'] - (int) $r['booked_units'] < $units) {
                throw new DomainException('Üzgünüz, seçilen oda bu tarihlerde az önce doldu. Lütfen başka bir oda seçin.');
            }
        }
        $affected = $this->db->query(
            'UPDATE inventory SET booked_units = booked_units + :u
             WHERE room_id = :r AND stay_date BETWEEN :f AND :l AND is_open = 1 AND booked_units + :u2 <= total_units',
            ['u' => $units, 'u2' => $units, 'r' => $roomId, 'f' => $first, 'l' => $last],
        )->rowCount();
        if ($affected !== count($dates)) {
            throw new DomainException('Üzgünüz, seçilen oda bu tarihlerde az önce doldu.');
        }
        foreach ($dates as $d) {
            $this->db->query(
                'INSERT INTO booking_inventory (booking_id, room_id, stay_date, units) VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE units = units + VALUES(units)',
                [$bookingId, $roomId, $d, $units],
            );
        }
    }

    /** Rezervasyona ait stoğu geri bırakır (iptal / red). Tekrar çağrılması güvenlidir. */
    public function release(int $bookingId): void
    {
        $this->db->transaction(function (Database $db) use ($bookingId): void {
            $rows = $db->fetchAll('SELECT room_id, stay_date, units FROM booking_inventory WHERE booking_id = ? ORDER BY room_id, stay_date FOR UPDATE', [$bookingId]);
            foreach ($rows as $r) {
                $db->query(
                    'UPDATE inventory SET booked_units = GREATEST(0, CAST(booked_units AS SIGNED) - ?) WHERE room_id = ? AND stay_date = ?',
                    [(int) $r['units'], (int) $r['room_id'], $r['stay_date']],
                );
            }
            $db->delete('booking_inventory', ['booking_id' => $bookingId]);
        });
    }

    /**
     * Toplu kontenjan güncelleme (tarih aralığı + gün filtresi).
     * Dolu adetten düşük toplam girilemez.
     * @return array{updated:int, skipped:array}
     */
    public function bulkUpsert(int $roomId, string $from, string $to, array $weekdays, array $fields, ?int $userId): array
    {
        $updated = 0;
        $skipped = [];
        $d = new \DateTimeImmutable($from);
        $end = new \DateTimeImmutable($to);
        if ($end < $d || $d->diff($end)->days > 731) {
            throw new DomainException('Tarih aralığı geçersiz (en fazla 2 yıl).');
        }
        $this->db->transaction(function (Database $db) use (&$d, $end, $weekdays, $roomId, $fields, $userId, &$updated, &$skipped): void {
            while ($d <= $end) {
                if ($weekdays && !in_array((int) $d->format('N'), $weekdays, true)) {
                    $d = $d->modify('+1 day');
                    continue;
                }
                $date = $d->format('Y-m-d');
                $row = $db->fetch('SELECT * FROM inventory WHERE room_id = ? AND stay_date = ? FOR UPDATE', [$roomId, $date]);
                $data = [];
                foreach (['total_units', 'is_open', 'min_stay', 'max_stay', 'note'] as $f) {
                    if (array_key_exists($f, $fields)) {
                        $data[$f] = $fields[$f];
                    }
                }
                if ($row && isset($data['total_units']) && (int) $data['total_units'] < (int) $row['booked_units']) {
                    $skipped[] = $date;
                    unset($data['total_units']);
                }
                $data['updated_by'] = $userId;
                if ($row) {
                    $db->update('inventory', $data, ['id' => $row['id']]);
                } else {
                    $data += ['room_id' => $roomId, 'stay_date' => $date, 'booked_units' => 0];
                    $data['total_units'] ??= 0;
                    $db->insert('inventory', $data);
                }
                $updated++;
                $d = $d->modify('+1 day');
            }
        });
        return ['updated' => $updated, 'skipped' => $skipped];
    }
}
