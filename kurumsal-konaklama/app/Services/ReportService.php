<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/** Gerçek rezervasyon verisinden raporlar. Uydurma/örnek sayı üretilmez. */
final class ReportService
{
    public const GROUPS = ['hotel' => 'Otel', 'institution' => 'Kurum', 'region' => 'Bölge', 'user' => 'Kullanıcı', 'month' => 'Ay', 'status' => 'Durum'];

    public function __construct(private readonly Database $db)
    {
    }

    /** @return array{0:string,1:array} */
    private function where(array $f): array
    {
        $w = ["b.status <> 'draft'"];
        $p = [];
        $dateCol = ($f['date_type'] ?? 'created') === 'checkin' ? 'b.check_in' : 'DATE(b.created_at)';
        if (!empty($f['from'])) {
            $w[] = "$dateCol >= :from";
            $p['from'] = $f['from'];
        }
        if (!empty($f['to'])) {
            $w[] = "$dateCol <= :to";
            $p['to'] = $f['to'];
        }
        foreach (['hotel_id' => 'b.hotel_id', 'institution_id' => 'b.institution_id', 'user_id' => 'b.user_id'] as $k => $col) {
            if (!empty($f[$k])) {
                $w[] = "$col = :$k";
                $p[$k] = (int) $f[$k];
            }
        }
        if (!empty($f['region_id'])) {
            $w[] = '(h.region_id = :rid OR rg.parent_id = :rid2)';
            $p['rid'] = (int) $f['region_id'];
            $p['rid2'] = (int) $f['region_id'];
        }
        if (!empty($f['status'])) {
            $w[] = 'b.status = :st';
            $p['st'] = $f['status'];
        }
        return [implode(' AND ', $w), $p];
    }

    public function summary(array $f): array
    {
        [$w, $p] = $this->where($f);
        return $this->db->fetch(
            "SELECT COUNT(*) AS bookings,
                    SUM(b.status = 'cancelled') AS cancelled,
                    SUM(CASE WHEN b.status <> 'cancelled' THEN b.nights * b.rooms_count ELSE 0 END) AS room_nights,
                    SUM(CASE WHEN b.status <> 'cancelled' THEN b.total_minor ELSE 0 END) AS total_minor,
                    SUM(CASE WHEN b.status <> 'cancelled' THEN b.discount_minor ELSE 0 END) AS discount_minor,
                    SUM(CASE WHEN b.status <> 'cancelled' THEN COALESCE(b.verified_savings_minor, 0) ELSE 0 END) AS savings_minor
             FROM bookings b JOIN hotels h ON h.id = b.hotel_id LEFT JOIN regions rg ON rg.id = h.region_id WHERE $w",
            $p,
        ) ?? [];
    }

    public function grouped(array $f, string $group): array
    {
        [$w, $p] = $this->where($f);
        [$key, $label] = match ($group) {
            'institution' => ['b.institution_id', "COALESCE(i.name, 'Kurumsuz')"],
            'region' => ['h.region_id', "COALESCE(rg.name, 'Bölgesiz')"],
            'user' => ['b.user_id', "CONCAT(u.first_name, ' ', u.last_name)"],
            'month' => ["DATE_FORMAT(b.check_in, '%Y-%m')", "DATE_FORMAT(b.check_in, '%Y-%m')"],
            'status' => ['b.status', 'b.status'],
            default => ['b.hotel_id', 'h.name'],
        };
        return $this->db->fetchAll(
            "SELECT $key AS gkey, MAX($label) AS label, COUNT(*) AS bookings,
                    SUM(b.status = 'cancelled') AS cancelled,
                    SUM(CASE WHEN b.status <> 'cancelled' THEN b.nights * b.rooms_count ELSE 0 END) AS room_nights,
                    SUM(CASE WHEN b.status <> 'cancelled' THEN b.total_minor ELSE 0 END) AS total_minor,
                    SUM(CASE WHEN b.status <> 'cancelled' THEN b.discount_minor ELSE 0 END) AS discount_minor,
                    SUM(CASE WHEN b.status <> 'cancelled' THEN COALESCE(b.verified_savings_minor, 0) ELSE 0 END) AS savings_minor
             FROM bookings b JOIN hotels h ON h.id = b.hotel_id LEFT JOIN regions rg ON rg.id = h.region_id
             LEFT JOIN institutions i ON i.id = b.institution_id JOIN users u ON u.id = b.user_id
             WHERE $w GROUP BY gkey ORDER BY total_minor DESC LIMIT 500",
            $p,
        );
    }

    public function rows(array $f, int $limit = 5000): array
    {
        [$w, $p] = $this->where($f);
        return $this->db->fetchAll(
            "SELECT b.code, b.status, b.created_at, b.check_in, b.check_out, b.nights, b.rooms_count, b.adults, b.children,
                    h.name AS hotel, rg.name AS region, i.name AS institution, CONCAT(u.first_name, ' ', u.last_name) AS member,
                    b.source_total_minor, b.discount_minor, b.total_minor, b.verified_savings_minor, b.currency, b.hotel_confirmation_no, b.cancel_reason
             FROM bookings b JOIN hotels h ON h.id = b.hotel_id LEFT JOIN regions rg ON rg.id = h.region_id
             LEFT JOIN institutions i ON i.id = b.institution_id JOIN users u ON u.id = b.user_id
             WHERE $w ORDER BY b.created_at DESC LIMIT " . max(1, $limit),
            $p,
        );
    }
}
