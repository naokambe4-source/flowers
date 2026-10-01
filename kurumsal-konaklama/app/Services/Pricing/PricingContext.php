<?php
declare(strict_types=1);

namespace App\Services\Pricing;

use App\Core\Database;
use App\DTO\StayCriteria;

/**
 * Bir arama için gereken tüm fiyat/stok verisini toplu yükler (N+1 önleme).
 * Otel sayısından bağımsız olarak sabit sayıda sorgu çalışır.
 */
final class PricingContext
{
    public array $hotels = [];
    /** @var array<int, array<int, array>> hotel_id => room_id => room */
    public array $rooms = [];
    /** @var array<int, array<int, array>> room_id => plan_id => plan */
    public array $plans = [];
    /** @var array<int, array<string, array>> plan_id => date => rate */
    public array $rates = [];
    /** @var array<int, array<string, array>> room_id => date => inventory */
    public array $inventory = [];
    /** @var array<int, array> hotel_id => stop sale satırları */
    public array $stopSales = [];
    public array $rules = [];
    public array $seasons = [];
    /** @var array<int, array> hotel_id => referans fiyatlar */
    public array $references = [];
    public array $concepts = [];
    public array $providers = [];

    public function __construct(public readonly StayCriteria $criteria)
    {
    }

    public static function load(Database $db, array $hotelIds, StayCriteria $c, int $referenceMaxAgeHours = 24): self
    {
        $ctx = new self($c);
        $hotelIds = array_values(array_unique(array_map('intval', $hotelIds)));
        if (!$hotelIds) {
            return $ctx;
        }
        $nights = $c->dates();
        $first = $nights[0];
        $last = $nights[count($nights) - 1];

        [$in, $p] = Database::in($hotelIds, 'h');
        foreach ($db->fetchAll("SELECT id, name, booking_mode, vat_bp, accommodation_tax_bp, is_contracted, contract_valid_until, status FROM hotels WHERE id IN $in", $p) as $h) {
            $ctx->hotels[(int) $h['id']] = $h;
        }
        foreach ($db->fetchAll("SELECT * FROM rooms WHERE is_active = 1 AND hotel_id IN $in ORDER BY sort, id", $p) as $r) {
            $ctx->rooms[(int) $r['hotel_id']][(int) $r['id']] = $r;
        }
        $planRows = $db->fetchAll(
            "SELECT rp.*, c.name AS concept_name, c.code AS concept_code FROM rate_plans rp
             LEFT JOIN concepts c ON c.id = rp.concept_id
             WHERE rp.is_active = 1 AND rp.hotel_id IN $in
               AND (rp.valid_from IS NULL OR rp.valid_from <= :first)
               AND (rp.valid_to IS NULL OR rp.valid_to >= :last)",
            $p + ['first' => $first, 'last' => $last],
        );
        $planIds = [];
        foreach ($planRows as $pl) {
            $ctx->plans[(int) $pl['room_id']][(int) $pl['id']] = $pl;
            $planIds[] = (int) $pl['id'];
        }
        if ($planIds) {
            [$pin, $pp] = Database::in($planIds, 'p');
            foreach ($db->fetchAll("SELECT * FROM rates WHERE rate_plan_id IN $pin AND stay_date BETWEEN :f AND :l", $pp + ['f' => $first, 'l' => $last]) as $r) {
                $ctx->rates[(int) $r['rate_plan_id']][$r['stay_date']] = $r;
            }
        }
        $roomIds = [];
        foreach ($ctx->rooms as $rs) {
            foreach ($rs as $id => $_) {
                $roomIds[] = $id;
            }
        }
        if ($roomIds) {
            [$rin, $rp] = Database::in($roomIds, 'r');
            foreach ($db->fetchAll("SELECT * FROM inventory WHERE room_id IN $rin AND stay_date BETWEEN :f AND :l", $rp + ['f' => $first, 'l' => $last]) as $iv) {
                $ctx->inventory[(int) $iv['room_id']][$iv['stay_date']] = $iv;
            }
        }
        foreach ($db->fetchAll("SELECT * FROM stop_sales WHERE hotel_id IN $in AND date_from <= :l AND date_to >= :f", $p + ['f' => $first, 'l' => $last]) as $s) {
            $ctx->stopSales[(int) $s['hotel_id']][] = $s;
        }
        $ctx->rules = $db->fetchAll(
            "SELECT * FROM rate_rules WHERE is_active = 1 AND (hotel_id IS NULL OR hotel_id IN $in)
               AND (valid_from IS NULL OR valid_from <= NOW()) AND (valid_to IS NULL OR valid_to >= NOW())
             ORDER BY priority DESC, id",
            $p,
        );
        foreach ($db->fetchAll('SELECT * FROM seasons WHERE is_active = 1 AND date_from <= :l AND date_to >= :f', ['f' => $first, 'l' => $last]) as $s) {
            $ctx->seasons[(int) $s['id']] = $s;
        }
        $agesKey = implode(',', $c->allChildAges());
        $refs = $db->fetchAll(
            "SELECT * FROM reference_prices WHERE hotel_id IN $in AND check_in = :ci AND check_out = :co
               AND adults = :ad AND children_ages = :ages AND rooms_count = :rc
               AND valid_until > NOW() AND captured_at >= :minCaptured
             ORDER BY captured_at DESC",
            $p + [
                'ci' => $c->checkIn, 'co' => $c->checkOut, 'ad' => $c->adults(), 'ages' => $agesKey,
                'rc' => $c->roomCount(), 'minCaptured' => date('Y-m-d H:i:s', time() - $referenceMaxAgeHours * 3600),
            ],
        );
        foreach ($refs as $r) {
            $ctx->references[(int) $r['hotel_id']][] = $r;
        }
        foreach ($db->fetchAll('SELECT id, name, code FROM concepts') as $co) {
            $ctx->concepts[(int) $co['id']] = $co;
        }
        foreach ($db->fetchAll('SELECT id, code, name, is_enabled, booking_authorized, display_authorized, settings_json FROM providers') as $pr) {
            $ctx->providers[(int) $pr['id']] = $pr;
        }
        return $ctx;
    }
}
