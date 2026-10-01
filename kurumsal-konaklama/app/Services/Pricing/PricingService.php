<?php
declare(strict_types=1);

namespace App\Services\Pricing;

use App\Core\Money;
use App\DTO\PriceQuote;
use App\DTO\RoomOccupancy;
use App\DTO\StayCriteria;
use App\Services\InventoryService;
use App\Services\SettingsService;

/**
 * FİYAT MOTORU
 *
 * Kaynaklar ayrı tutulur:
 *  - Anlaşmalı fiyat (rate_plans + rates, source=contract) → kesin fiyat yalnız is_bookable=1 ve otel anlaşması geçerliyse
 *  - Kampanya fiyatı (rate_plans source=campaign veya kampanyaya bağlı kural)
 *  - Manuel doğrulanmış referans fiyat (reference_prices source=manual_reference) → yalnız "onaya bağlı hedef teklif"
 *  - Harici sağlayıcı fiyatı (reference_prices source=provider) → yetkili ve rezervasyon destekli ise kesin, değilse hedef teklif
 *
 * Kural uygulama sırası (her gece için ayrı hesaplanır, kuruş bazında):
 *  1. Kaynak gecelik fiyat (oda bazlı konuk dağılımına göre)
 *  2. Uygulanabilir fiyat kuralları öncelik sırasıyla (yüksek öncelik önce):
 *     - "Birlikte uygulanamaz" (stackable=0) kurallardan YALNIZ en yüksek öncelikli olanı (eşitlikte en avantajlısı) uygulanır
 *     - "Birlikte uygulanabilir" kuralların tamamı uygulanır; her biri bir önceki sonucun üzerine hesaplanır
 *  3. Üye indirimi (kurum → fiyat grubu → genel ayar); fiyat planı "net kurumsal fiyat" ise uygulanmaz
 *  4. Toplam indirim, kaynak fiyatın "azami indirim" ayarını aşamaz (aşan kısım kırpılır)
 *  5. Promosyon kodu (rezervasyon toplamına) — yine azami indirim sınırı içinde
 *  6. Vergi: fiyat planı vergiler hariçse KDV ve konaklama vergisi eklenir; dahilse içindeki pay gösterilir
 * Hesap dökümü PriceQuote::breakdown içinde saklanır ve rezervasyonda anlık kopya olarak kaydedilir.
 */
final class PricingService
{
    private int $maxDiscountBp;
    private int $vatBp;
    private int $accTaxBp;

    public function __construct()
    {
        $this->maxDiscountBp = SettingsService::int('pricing.max_discount_bp', 3000);
        $this->vatBp = SettingsService::int('pricing.vat_bp', 1000);
        $this->accTaxBp = SettingsService::int('pricing.accommodation_tax_bp', 200);
    }

    /** Odanın tüm konuk dağılımını karşılayıp karşılamadığı. */
    public static function fits(array $room, RoomOccupancy $occ): bool
    {
        return $occ->adults <= (int) $room['max_adults']
            && $occ->children() <= (int) $room['max_children']
            && $occ->total() <= (int) $room['max_occupancy'];
    }

    /**
     * Otel için tüm oda/plan kombinasyonlarını fiyatlar.
     * @return PriceQuote[]
     */
    public function quotesForHotel(PricingContext $ctx, PricingProfile $profile, int $hotelId, ?array $promo = null): array
    {
        $out = [];
        foreach ($ctx->rooms[$hotelId] ?? [] as $roomId => $room) {
            $plans = $ctx->plans[$roomId] ?? [];
            if (!$plans) {
                $out[] = $this->noPlanQuote($ctx, $profile, $hotelId, $room);
                continue;
            }
            foreach ($plans as $planId => $_) {
                $out[] = $this->quote($ctx, $profile, $hotelId, $roomId, $planId, $promo);
            }
        }
        return $out;
    }

    /** Kartta gösterilecek en iyi seçenek: en ucuz kesin fiyat → en ucuz hedef teklif → teklif iste → müsait değil. */
    public function bestForHotel(PricingContext $ctx, PricingProfile $profile, int $hotelId): PriceQuote
    {
        $quotes = $this->quotesForHotel($ctx, $profile, $hotelId);
        $quotes[] = $this->referenceQuote($ctx, $profile, $hotelId, null);
        foreach (['firm', 'target'] as $kind) {
            $cands = array_filter($quotes, static fn (?PriceQuote $q) => $q && $q->kind === $kind);
            if ($cands) {
                usort($cands, static fn (PriceQuote $a, PriceQuote $b) => $a->total <=> $b->total);
                return $cands[0];
            }
        }
        foreach ($quotes as $q) {
            if ($q && $q->kind === 'request') {
                return $q;
            }
        }
        $first = array_values(array_filter($quotes))[0] ?? null;
        return $first ?? new PriceQuote('request', $hotelId, message: 'Bu otel için fiyat bilgisi henüz tanımlı değil. Teklif isteyebilirsiniz.', nights: $ctx->criteria->nights());
    }

    /** Tek bir oda + fiyat planı için kesin fiyat hesabı. */
    public function quote(PricingContext $ctx, PricingProfile $profile, int $hotelId, int $roomId, int $planId, ?array $promo = null): PriceQuote
    {
        $c = $ctx->criteria;
        $hotel = $ctx->hotels[$hotelId] ?? null;
        $room = $ctx->rooms[$hotelId][$roomId] ?? null;
        $plan = $ctx->plans[$roomId][$planId] ?? null;
        $nights = $c->nights();
        if (!$hotel || !$room || !$plan) {
            return new PriceQuote('unavailable', $hotelId, $roomId, $planId, message: 'Seçilen oda veya fiyat planı artık geçerli değil.', nights: $nights);
        }
        $base = [
            'hotelId' => $hotelId, 'roomId' => $roomId, 'ratePlanId' => $planId, 'roomName' => $room['name'],
            'conceptName' => $plan['concept_name'], 'refundable' => (bool) $plan['refundable'],
            'cancellationSummary' => self::cancellationSummary($plan), 'nights' => $nights, 'currency' => $plan['currency'],
        ];
        foreach ($c->rooms as $occ) {
            if (!self::fits($room, $occ)) {
                return new PriceQuote('unavailable', ...$base + ['message' => 'Bu oda seçilen konuk sayısı için uygun değil.']);
            }
        }
        $dates = $c->dates();
        $avail = InventoryService::evaluate($ctx->inventory[$roomId] ?? [], $ctx->stopSales[$hotelId] ?? [], $roomId, $dates, $c->roomCount(), $nights);
        if (!$avail['ok'] && $avail['managed']) {
            return new PriceQuote('unavailable', ...$base + ['message' => $avail['reason']]);
        }

        // 1) Kaynak gecelik fiyatlar
        $nightly = [];
        foreach ($dates as $d) {
            $rate = $ctx->rates[$planId][$d] ?? null;
            if ($rate === null) {
                return new PriceQuote('request', ...$base + ['message' => 'Seçilen tarihlerin tamamı için fiyat tanımlı değil. Teklif isteyebilirsiniz.']);
            }
            $sum = 0;
            foreach ($c->rooms as $occ) {
                $sum += self::nightlyRoomPrice($rate, $plan, $occ);
            }
            $nightly[$d] = $sum;
        }

        $applyMember = (int) $plan['member_discount_applies'] === 1;
        [$totalAfter, $lines, $nightsDetail] = $this->applyRules($ctx, $profile, $hotelId, $roomId, $nightly, $applyMember);
        $sourceTotal = array_sum($nightly);

        // 5) Promosyon
        $promoId = null;
        if ($promo) {
            $promoDiscount = $promo['adjustment'] === 'discount_percent'
                ? Money::percentOf($totalAfter, (int) $promo['value'])
                : min((int) $promo['value'], $totalAfter);
            $maxTotalDiscount = Money::percentOf($sourceTotal, $this->maxDiscountBp);
            $already = $sourceTotal - $totalAfter;
            $promoDiscount = max(0, min($promoDiscount, $maxTotalDiscount - $already));
            if ($promoDiscount > 0) {
                $totalAfter -= $promoDiscount;
                $lines[] = ['label' => 'Promosyon kodu (' . $promo['code'] . ')', 'amount' => -$promoDiscount, 'type' => 'promo'];
            }
            $promoId = (int) $promo['id'];
        }

        // 6) Vergi
        $hotelVat = $hotel['vat_bp'] !== null ? (int) $hotel['vat_bp'] : $this->vatBp;
        $hotelAcc = $hotel['accommodation_tax_bp'] !== null ? (int) $hotel['accommodation_tax_bp'] : $this->accTaxBp;
        $taxIncluded = (int) $plan['tax_included'] === 1;
        [$grandTotal, $taxTotal] = self::taxes($totalAfter, $taxIncluded, $hotelVat, $hotelAcc);
        if (!$taxIncluded) {
            $lines[] = ['label' => 'Vergiler (KDV ' . Money::percent($hotelVat) . ' + konaklama vergisi ' . Money::percent($hotelAcc) . ')', 'amount' => $taxTotal, 'type' => 'tax'];
        }

        $discount = $sourceTotal - $totalAfter;
        $kind = $this->firmAllowed($hotel, $plan, $avail) ? 'firm' : 'target';
        $message = null;
        if ($kind === 'target') {
            $message = !$avail['managed']
                ? 'Kontenjan tanımlı olmadığından fiyat otel onayına bağlıdır.'
                : 'Bu fiyat için otelle kesin satış yetkisi tanımlı değil; onaya bağlı hedef tekliftir.';
        }

        $breakdown = [
            'lines' => array_merge([['label' => 'Kaynak fiyat (' . $nights . ' gece, ' . $c->roomCount() . ' oda)', 'amount' => $sourceTotal, 'type' => 'source']], $lines),
            'nights' => $nightsDetail,
            'member_discount_bp' => $applyMember ? $profile->memberDiscountBp : 0,
            'member_discount_source' => $applyMember ? $profile->discountSource : 'Net kurumsal fiyat (üye indirimi uygulanmaz)',
            'tax_included' => $taxIncluded, 'vat_bp' => $hotelVat, 'accommodation_tax_bp' => $hotelAcc,
            'price_source' => $plan['source'] === 'campaign' ? 'Kampanya fiyatı' : 'Anlaşmalı fiyat',
            'rate_plan' => $plan['name'], 'contract_reference' => $plan['contract_reference'],
            'calculated_at' => date('c'),
        ];

        [$refId, $refTotal, $savings] = $this->verifiedSavings($ctx, $hotelId, $roomId, $plan, $grandTotal);

        return new PriceQuote(
            $kind, $hotelId, $roomId, $planId, $plan['currency'], $sourceTotal, $discount, $grandTotal, $taxTotal, true,
            $breakdown, $refId, $refTotal, $savings, $plan['concept_name'], $room['name'], (bool) $plan['refundable'],
            self::cancellationSummary($plan), $plan['source'] === 'campaign' ? 'contract' : 'contract', null, $message, $promoId, $nights,
        );
    }

    /** Kesin fiyat şartları: otel anlaşmalı + anlaşma süresi geçerli + plan satış yetkili + stok yönetiliyor ve müsait. */
    private function firmAllowed(array $hotel, array $plan, array $avail): bool
    {
        if ((int) $hotel['is_contracted'] !== 1 || (int) $plan['is_bookable'] !== 1) {
            return false;
        }
        if ($hotel['contract_valid_until'] !== null && $hotel['contract_valid_until'] < date('Y-m-d')) {
            return false;
        }
        return $avail['ok'] && $avail['managed'];
    }

    public static function nightlyRoomPrice(array $rate, array $plan, RoomOccupancy $occ): int
    {
        $childMax = (int) $plan['child_max_age'];
        $freeMax = (int) $plan['free_child_max_age'];
        $adultsPriced = $occ->adults;
        $paidChildren = 0;
        foreach ($occ->childAges as $age) {
            if ($age >= $childMax) {
                $adultsPriced++;
            } elseif ($age > $freeMax) {
                $paidChildren++;
            }
        }
        $price = ($adultsPriced === 1 && $rate['single_minor'] !== null) ? (int) $rate['single_minor'] : (int) $rate['price_minor'];
        $extra = max(0, $adultsPriced - (int) $plan['base_adults']) * (int) $rate['extra_adult_minor'];
        return $price + $extra + $paidChildren * (int) $rate['child_minor'];
    }

    /**
     * Kuralları ve üye indirimini gece bazında uygular.
     * @return array{0:int, 1:array, 2:array}
     */
    private function applyRules(PricingContext $ctx, PricingProfile $profile, int $hotelId, int $roomId, array $nightly, bool $applyMember): array
    {
        $c = $ctx->criteria;
        $totals = [];
        $detail = [];
        $total = 0;
        foreach ($nightly as $date => $source) {
            $amount = $source;
            $adjust = [];
            $applicable = array_values(array_filter($ctx->rules, fn ($r) => $this->ruleApplies($r, $ctx, $profile, $hotelId, $roomId, $date, $c)));
            $exclusive = array_values(array_filter($applicable, static fn ($r) => (int) $r['stackable'] === 0));
            $stackable = array_values(array_filter($applicable, static fn ($r) => (int) $r['stackable'] === 1));
            $chosen = [];
            if ($exclusive) {
                usort($exclusive, static fn ($a, $b) => [(int) $b['priority'], (int) $b['value']] <=> [(int) $a['priority'], (int) $a['value']]);
                $chosen[] = $exclusive[0];
            }
            $chosen = array_merge($chosen, $stackable);
            usort($chosen, static fn ($a, $b) => (int) $b['priority'] <=> (int) $a['priority']);
            foreach ($chosen as $r) {
                $delta = match ($r['adjustment']) {
                    'discount_percent' => -Money::percentOf($amount, (int) $r['value']),
                    'discount_per_night' => -min((int) $r['value'] * $c->roomCount(), $amount),
                    'surcharge_percent' => Money::percentOf($amount, (int) $r['value']),
                    'surcharge_per_night' => (int) $r['value'] * $c->roomCount(),
                    default => 0,
                };
                if ($delta !== 0) {
                    $amount += $delta;
                    $adjust[] = ['rule_id' => (int) $r['id'], 'label' => $r['name'], 'amount' => $delta];
                }
            }
            if ($applyMember && $profile->memberDiscountBp > 0) {
                $delta = -Money::percentOf($amount, $profile->memberDiscountBp);
                $amount += $delta;
                $adjust[] = ['rule_id' => null, 'label' => $profile->discountSource . ' ' . Money::percent($profile->memberDiscountBp), 'amount' => $delta];
            }
            // 4) Azami indirim sınırı
            $minAllowed = $source - Money::percentOf($source, $this->maxDiscountBp);
            if ($amount < $minAllowed) {
                $adjust[] = ['rule_id' => null, 'label' => 'Azami indirim sınırı (' . Money::percent($this->maxDiscountBp) . ')', 'amount' => $minAllowed - $amount];
                $amount = $minAllowed;
            }
            foreach ($adjust as $a) {
                $totals[$a['label']] = ($totals[$a['label']] ?? 0) + $a['amount'];
            }
            $detail[] = ['date' => $date, 'source' => $source, 'adjustments' => $adjust, 'net' => $amount];
            $total += $amount;
        }
        $lines = [];
        foreach ($totals as $label => $amt) {
            $lines[] = ['label' => $label, 'amount' => $amt, 'type' => $amt < 0 ? 'discount' : 'surcharge'];
        }
        return [$total, $lines, $detail];
    }

    private function ruleApplies(array $r, PricingContext $ctx, PricingProfile $profile, int $hotelId, int $roomId, string $date, StayCriteria $c): bool
    {
        if ($r['institution_id'] !== null && (int) $r['institution_id'] !== $profile->institutionId) {
            return false;
        }
        if ($r['price_group_id'] !== null && (int) $r['price_group_id'] !== $profile->priceGroupId) {
            return false;
        }
        if ($r['hotel_id'] !== null && (int) $r['hotel_id'] !== $hotelId) {
            return false;
        }
        if ($r['room_id'] !== null && (int) $r['room_id'] !== $roomId) {
            return false;
        }
        if ($r['stay_from'] !== null && $date < $r['stay_from']) {
            return false;
        }
        if ($r['stay_to'] !== null && $date > $r['stay_to']) {
            return false;
        }
        $weekdays = trim((string) $r['weekdays']);
        if ($weekdays === '' && $r['kind'] === 'weekend') {
            $weekdays = '5,6';
        } elseif ($weekdays === '' && $r['kind'] === 'weekday') {
            $weekdays = '1,2,3,4,7';
        }
        if ($weekdays !== '' && !in_array((int) date('N', strtotime($date)), array_map('intval', explode(',', $weekdays)), true)) {
            return false;
        }
        if ($r['season_id'] !== null) {
            $s = $ctx->seasons[(int) $r['season_id']] ?? null;
            if (!$s || $date < $s['date_from'] || $date > $s['date_to']) {
                return false;
            }
        }
        if ($r['min_nights'] !== null && $c->nights() < (int) $r['min_nights']) {
            return false;
        }
        if ($r['min_lead_days'] !== null && $c->leadDays() < (int) $r['min_lead_days']) {
            return false;
        }
        if ($r['max_lead_days'] !== null && $c->leadDays() > (int) $r['max_lead_days']) {
            return false;
        }
        return true;
    }

    /** @return array{0:int,1:int} [genel toplam, vergi tutarı] */
    public static function taxes(int $amount, bool $included, int $vatBp, int $accBp): array
    {
        $factor = intdiv((10000 + $accBp) * (10000 + $vatBp), 10000); // bp cinsinden çarpan
        if ($included) {
            $net = intdiv($amount * 10000 + intdiv($factor, 2), $factor);
            return [$amount, $amount - $net];
        }
        $gross = intdiv($amount * $factor + 5000, 10000);
        return [$gross, $gross - $amount];
    }

    /**
     * Doğrulanmış karşılaştırma: aynı tarih, oda, konuk dağılımı, konsept, vergi (dahil), iptal koşulu ve para birimi.
     * Bu şartlardan biri bilinmiyor veya farklıysa tasarruf iddiası gösterilmez.
     * @return array{0:?int,1:?int,2:?int}
     */
    private function verifiedSavings(PricingContext $ctx, int $hotelId, int $roomId, array $plan, int $memberTotal): array
    {
        foreach ($ctx->references[$hotelId] ?? [] as $ref) {
            if ((int) $ref['room_id'] !== $roomId || $ref['currency'] !== $plan['currency'] || (int) $ref['tax_included'] !== 1) {
                continue;
            }
            if ($ref['concept_id'] === null || (int) $ref['concept_id'] !== (int) $plan['concept_id']) {
                continue;
            }
            if ($ref['refundable'] === null || (int) $ref['refundable'] !== (int) $plan['refundable']) {
                continue;
            }
            if ($ref['verified_at'] === null && $ref['source'] === 'manual_reference') {
                continue;
            }
            $saving = (int) $ref['total_minor'] - $memberTotal;
            return $saving > 0 ? [(int) $ref['id'], (int) $ref['total_minor'], $saving] : [null, null, null];
        }
        return [null, null, null];
    }

    /**
     * Anlaşmalı fiyat yoksa: doğrulanmış referans (manuel veya sağlayıcı) üzerinden ONAYA BAĞLI HEDEF TEKLİF.
     * Sağlayıcı rezervasyon yetkili ve rate rezerve edilebilir ise kesin fiyat olabilir.
     */
    public function referenceQuote(PricingContext $ctx, PricingProfile $profile, int $hotelId, ?int $roomId): ?PriceQuote
    {
        $c = $ctx->criteria;
        $best = null;
        foreach ($ctx->references[$hotelId] ?? [] as $ref) {
            if ($roomId !== null && (int) $ref['room_id'] !== $roomId) {
                continue;
            }
            if ($ref['source'] === 'manual_reference' && $ref['verified_at'] === null) {
                continue;
            }
            $provider = $ref['provider_id'] !== null ? ($ctx->providers[(int) $ref['provider_id']] ?? null) : null;
            if ($ref['source'] === 'provider' && (!$provider || (int) $provider['is_enabled'] !== 1 || (int) $provider['display_authorized'] !== 1)) {
                continue;
            }
            if ($best === null || (int) $ref['total_minor'] < (int) $best['total_minor']) {
                $best = $ref;
            }
        }
        if ($best === null) {
            return null;
        }
        $refTotal = (int) $best['total_minor'];
        $taxNote = (int) $best['tax_included'] === 1;
        $firmProvider = $best['source'] === 'provider' && (int) $best['provider_bookable'] === 1
            && (int) ($ctx->providers[(int) $best['provider_id']]['booking_authorized'] ?? 0) === 1;
        $applyMember = !$firmProvider || (bool) (json_decode((string) ($ctx->providers[(int) $best['provider_id']]['settings_json'] ?? '{}'), true)['apply_member_discount'] ?? false);
        $discount = $applyMember ? min(Money::percentOf($refTotal, $profile->memberDiscountBp), Money::percentOf($refTotal, $this->maxDiscountBp)) : 0;
        $total = $refTotal - $discount;
        $concept = $best['concept_id'] !== null ? ($ctx->concepts[(int) $best['concept_id']]['name'] ?? null) : null;
        $lines = [
            ['label' => ($best['source'] === 'provider' ? 'Sağlayıcı fiyatı' : 'Doğrulanmış referans fiyat') . ($best['source_label'] ? ' — ' . $best['source_label'] : ''), 'amount' => $refTotal, 'type' => 'source'],
        ];
        if ($discount > 0) {
            $lines[] = ['label' => $profile->discountSource . ' ' . Money::percent($profile->memberDiscountBp), 'amount' => -$discount, 'type' => 'discount'];
        }
        $breakdown = [
            'lines' => $lines, 'nights' => [], 'tax_included' => $taxNote,
            'price_source' => $best['source'] === 'provider' ? 'Harici sağlayıcı fiyatı' : 'Manuel doğrulanmış referans fiyat',
            'reference_captured_at' => $best['captured_at'], 'reference_valid_until' => $best['valid_until'],
            'member_discount_bp' => $applyMember ? $profile->memberDiscountBp : 0, 'calculated_at' => date('c'),
            'external_rate_id' => $best['external_rate_id'],
        ];
        $roomName = $best['room_name'] ?: ($best['room_id'] !== null ? ($ctx->rooms[$hotelId][(int) $best['room_id']]['name'] ?? null) : null);
        return new PriceQuote(
            $firmProvider ? 'firm' : 'target', $hotelId, $best['room_id'] !== null ? (int) $best['room_id'] : null, null, $best['currency'],
            $refTotal, $discount, $total, 0, $taxNote, $breakdown, (int) $best['id'], $refTotal, $discount > 0 ? $discount : null,
            $concept, $roomName, (bool) $best['refundable'], $best['cancellation_summary'],
            $best['source'] === 'provider' ? 'provider' : 'reference', $best['provider_id'] !== null ? (int) $best['provider_id'] : null,
            $firmProvider ? null : 'Onaya bağlı hedef teklif: ' . tr_datetime($best['captured_at']) . ' tarihinde doğrulanan fiyattan hesaplanmıştır. Kesin fiyat otel onayı ile belirlenir.',
            null, $c->nights(),
        );
    }

    private function noPlanQuote(PricingContext $ctx, PricingProfile $profile, int $hotelId, array $room): PriceQuote
    {
        foreach ($ctx->criteria->rooms as $occ) {
            if (!self::fits($room, $occ)) {
                return new PriceQuote('unavailable', $hotelId, (int) $room['id'], roomName: $room['name'], message: 'Bu oda seçilen konuk sayısı için uygun değil.', nights: $ctx->criteria->nights());
            }
        }
        $ref = $this->referenceQuote($ctx, $profile, $hotelId, (int) $room['id']);
        return $ref ?? new PriceQuote('request', $hotelId, (int) $room['id'], roomName: $room['name'], message: 'Bu oda için fiyat tanımlı değil. Teklif isteyebilirsiniz.', nights: $ctx->criteria->nights());
    }

    public static function cancellationSummary(array $plan): string
    {
        if ((int) $plan['refundable'] !== 1) {
            return 'İade edilemez fiyat';
        }
        if ($plan['free_cancel_days'] !== null) {
            $d = (int) $plan['free_cancel_days'];
            return $d === 0 ? 'Giriş gününe kadar ücretsiz iptal' : "Girişten $d gün öncesine kadar ücretsiz iptal";
        }
        return 'İptal koşulları otel politikasına tabidir';
    }
}
