<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\DTO\PriceQuote;
use App\DTO\StayCriteria;
use App\Repositories\HotelRepository;
use App\Services\Pricing\PricingContext;
use App\Services\Pricing\PricingProfile;
use App\Services\Pricing\PricingService;

/**
 * Otel arama: veritabanı filtreleri → (varsa) sağlayıcı fiyatları → toplu fiyat hesabı → fiyat filtresi → sıralama → sayfalama.
 */
final class SearchService
{
    public const SORTS = [
        'onerilen' => 'Önerilen',
        'fiyat-artan' => 'Fiyat (artan)',
        'fiyat-azalan' => 'Fiyat (azalan)',
        'yildiz' => 'Yıldız',
        'avantaj' => 'Doğrulanmış avantaj oranı',
    ];

    public array $notices = [];

    public function __construct(private readonly Database $db)
    {
    }

    public function search(array $user, ?StayCriteria $criteria, array $filters, string $sort, int $page, int $perPage = 12): array
    {
        $repo = new HotelRepository($this->db);
        $hotels = $repo->publishedCandidates($filters);
        $ids = array_map(static fn ($h) => (int) $h['id'], $hotels);
        $quotes = [];
        if ($criteria !== null && $ids) {
            $providerService = new ProviderRateService($this->db);
            $providerService->refresh($ids, $criteria);
            $this->notices = $providerService->notices;
            $ctx = PricingContext::load($this->db, $ids, $criteria, SettingsService::int('pricing.reference_max_age_hours', 24));
            $pricing = new PricingService();
            $profile = PricingProfile::forUser($this->db, $user);
            foreach ($ids as $id) {
                $quotes[$id] = $pricing->bestForHotel($ctx, $profile, $id);
            }
        }

        // Fiyata bağlı filtreler yalnız tarih seçildiğinde uygulanabilir
        if ($criteria !== null) {
            $min = isset($filters['price_min']) && $filters['price_min'] !== null ? (int) $filters['price_min'] : null;
            $max = isset($filters['price_max']) && $filters['price_max'] !== null ? (int) $filters['price_max'] : null;
            $hotels = array_values(array_filter($hotels, static function ($h) use ($quotes, $min, $max, $filters) {
                $q = $quotes[(int) $h['id']] ?? null;
                if (!empty($filters['free_cancel']) && (!$q || !in_array($q->kind, ['firm', 'target'], true) || !$q->refundable)) {
                    return false;
                }
                if ($min === null && $max === null) {
                    return true;
                }
                if (!$q || !in_array($q->kind, ['firm', 'target'], true)) {
                    return false;
                }
                return ($min === null || $q->total >= $min) && ($max === null || $q->total <= $max);
            }));
        }

        $priceOf = static fn ($h) => isset($quotes[(int) $h['id']]) && in_array($quotes[(int) $h['id']]->kind, ['firm', 'target'], true) ? $quotes[(int) $h['id']]->total : null;
        $kindRank = static fn ($h) => match ($quotes[(int) $h['id']]->kind ?? 'none') { 'firm' => 0, 'target' => 1, 'request' => 2, default => 3 };
        usort($hotels, static function ($a, $b) use ($sort, $priceOf, $kindRank, $quotes) {
            switch ($sort) {
                case 'fiyat-artan':
                case 'fiyat-azalan':
                    $pa = $priceOf($a);
                    $pb = $priceOf($b);
                    if ($pa === null || $pb === null) {
                        return ($pa === null) <=> ($pb === null);
                    }
                    return $sort === 'fiyat-artan' ? $pa <=> $pb : $pb <=> $pa;
                case 'yildiz':
                    return [(int) $b['stars'], $a['name']] <=> [(int) $a['stars'], $b['name']];
                case 'avantaj':
                    $sa = ($quotes[(int) $a['id']] ?? null)?->savingsPercentBp() ?? -1;
                    $sb = ($quotes[(int) $b['id']] ?? null)?->savingsPercentBp() ?? -1;
                    return $sb <=> $sa;
                default:
                    return [$kindRank($a), -(int) $a['is_featured'], (int) $a['featured_sort'], $a['name']] <=> [$kindRank($b), -(int) $b['is_featured'], (int) $b['featured_sort'], $b['name']];
            }
        });

        $total = count($hotels);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $slice = array_slice($hotels, ($page - 1) * $perPage, $perPage);
        $sliceIds = array_map(static fn ($h) => (int) $h['id'], $slice);
        $amenities = $repo->amenityNames($sliceIds);
        $favs = $repo->favoriteIds((int) $user['id'], $sliceIds);
        foreach ($slice as &$h) {
            $h['quote'] = $quotes[(int) $h['id']] ?? null;
            $h['amenities'] = $amenities[(int) $h['id']] ?? [];
            $h['is_favorite'] = isset($favs[(int) $h['id']]);
        }
        unset($h);

        return [
            'items' => $slice,
            'all' => $hotels,
            'quotes' => $quotes,
            'page' => ['current' => $page, 'pages' => $pages, 'total' => $total, 'per_page' => $perPage],
        ];
    }

    /** Harita için hafif veri. */
    public static function mapItems(array $hotels, array $quotes): array
    {
        $out = [];
        foreach ($hotels as $h) {
            if ($h['latitude'] === null || $h['longitude'] === null) {
                continue;
            }
            /** @var PriceQuote|null $q */
            $q = $quotes[(int) $h['id']] ?? null;
            $out[] = [
                'name' => $h['name'],
                'url' => url('/oteller/' . $h['slug']),
                'lat' => (float) $h['latitude'],
                'lng' => (float) $h['longitude'],
                'stars' => (int) $h['stars'],
                'region' => $h['region_name'],
                'price' => $q && in_array($q->kind, ['firm', 'target'], true) ? money($q->total, $q->currency) : null,
                'kind' => $q?->kind,
            ];
        }
        return $out;
    }
}
