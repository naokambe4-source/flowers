<?php
declare(strict_types=1);

namespace App\Controllers\Member;

use App\Controllers\Controller;
use App\Core\App;
use App\Core\Money;
use App\Core\Response;
use App\Exceptions\ValidationException;
use App\Repositories\HotelRepository;
use App\Services\Pricing\PricingContext;
use App\Services\Pricing\PricingProfile;
use App\Services\Pricing\PricingService;
use App\Services\ProviderRateService;
use App\Services\SearchService;
use App\Services\SettingsService;
use App\Validators\StayCriteriaValidator;

final class HotelController extends Controller
{
    private function criteria(): ?\App\DTO\StayCriteria
    {
        try {
            return StayCriteriaValidator::fromInput($this->request->query, false);
        } catch (ValidationException $e) {
            \App\Core\View::share('_errors', $e->errors);
            $this->flash('error', $e->getMessage());
            return null;
        }
    }

    private function filters(): array
    {
        $q = $this->request->query;
        $stars = array_values(array_filter(array_map('intval', (array) ($q['yildiz'] ?? [])), static fn ($s) => $s >= 1 && $s <= 5));
        $features = array_values(array_intersect((array) ($q['ozellik'] ?? []), array_keys(HotelRepository::FILTER_KEYS)));
        $min = isset($q['fiyat_min']) && $q['fiyat_min'] !== '' ? Money::parse((string) $q['fiyat_min']) : null;
        $max = isset($q['fiyat_max']) && $q['fiyat_max'] !== '' ? Money::parse((string) $q['fiyat_max']) : null;
        return [
            'region_id' => (int) ($q['bolge'] ?? 0) ?: null,
            'stars' => $stars,
            'concept_id' => (int) ($q['konsept'] ?? 0) ?: null,
            'features' => $features,
            'breakfast' => !empty($q['kahvalti']),
            'all_inclusive' => !empty($q['her_sey_dahil']),
            'free_cancel' => !empty($q['ucretsiz_iptal']),
            'price_min' => $min,
            'price_max' => $max,
            'q' => mb_substr(trim((string) ($q['ara'] ?? '')), 0, 80) ?: null,
        ];
    }

    public function index(): Response
    {
        $db = App::db();
        $criteria = $this->criteria();
        $filters = $this->filters();
        $sort = (string) ($this->request->query['sirala'] ?? 'onerilen');
        if (!isset(SearchService::SORTS[$sort])) {
            $sort = 'onerilen';
        }
        $service = new SearchService($db);
        $result = $service->search($this->user(), $criteria, $filters, $sort, $this->page());
        $view = ($this->request->query['gorunum'] ?? '') === 'harita' ? 'harita' : 'liste';
        $active = count($filters['stars']) + count($filters['features']) + (int) (bool) $filters['concept_id'] + (int) $filters['breakfast'] + (int) $filters['all_inclusive'] + (int) $filters['free_cancel'] + (int) ($filters['price_min'] !== null) + (int) ($filters['price_max'] !== null);
        $query = array_filter($this->request->query, static fn ($k) => $k !== 'sayfa', ARRAY_FILTER_USE_KEY);
        return $this->view('member/hotels', [
            'title' => 'Otel Ara',
            'criteria' => $criteria,
            'filters' => $filters,
            'sort' => $sort,
            'result' => $result,
            'notices' => $service->notices,
            'regions' => $db->fetchAll('SELECT id, name, parent_id FROM regions WHERE is_active = 1 ORDER BY sort'),
            'concepts' => $db->fetchAll('SELECT id, name, code FROM concepts WHERE is_active = 1 ORDER BY sort'),
            'activeCount' => $active,
            'viewMode' => $view,
            'query' => $query,
            'detailQuery' => $criteria ? $criteria->toQuery() : [],
            'mapItems' => $view === 'harita' ? SearchService::mapItems($result['all'], $result['quotes']) : [],
            'withMap' => $view === 'harita',
        ]);
    }

    public function mapData(): Response
    {
        $criteria = null;
        try {
            $criteria = StayCriteriaValidator::fromInput($this->request->query, false);
        } catch (ValidationException) {
        }
        $result = (new SearchService(App::db()))->search($this->user(), $criteria, $this->filters(), 'onerilen', 1, 2000);
        return $this->json(['items' => SearchService::mapItems($result['all'], $result['quotes'])]);
    }

    public function show(string $slug): Response
    {
        $db = App::db();
        $repo = new HotelRepository($db);
        $hotel = $repo->findPublishedBySlug($slug);
        $this->notFoundUnless($hotel);
        return $this->renderDetail($hotel, false);
    }

    /** Yönetim önizlemesi de bu metodu kullanır. */
    public function renderDetail(array $hotel, bool $preview): Response
    {
        $db = App::db();
        $repo = new HotelRepository($db);
        $user = $this->user();
        $criteria = $this->criteria();
        $hotelId = (int) $hotel['id'];
        $rooms = $repo->rooms($hotelId);
        $roomIds = array_map(static fn ($r) => (int) $r['id'], $rooms);
        $quotesByRoom = [];
        $best = null;
        $notices = [];
        if ($criteria) {
            $prs = new ProviderRateService($db);
            $prs->refresh([$hotelId], $criteria);
            $notices = $prs->notices;
            $ctx = PricingContext::load($db, [$hotelId], $criteria, SettingsService::int('pricing.reference_max_age_hours', 24));
            if ($preview) {
                $ctx->hotels[$hotelId]['status'] = 'published';
            }
            $pricing = new PricingService();
            $profile = PricingProfile::forUser($db, $user);
            foreach ($pricing->quotesForHotel($ctx, $profile, $hotelId) as $q) {
                $quotesByRoom[(int) $q->roomId][] = $q;
            }
            $best = $pricing->bestForHotel($ctx, $profile, $hotelId);
            if ($best->kind === 'target' && $best->roomId === null) {
                $quotesByRoom[0][] = $best;
            }
        }
        $isFav = (bool) $db->value('SELECT 1 FROM favorites WHERE user_id = ? AND hotel_id = ?', [$user['id'], $hotelId]);
        return $this->view('member/hotel', [
            'title' => $hotel['name'],
            'hotel' => $hotel,
            'images' => $repo->images($hotelId),
            'amenities' => $repo->amenities($hotelId),
            'rooms' => $rooms,
            'roomImages' => $repo->roomImages($roomIds),
            'roomAmenities' => $repo->roomAmenities($roomIds),
            'criteria' => $criteria,
            'quotesByRoom' => $quotesByRoom,
            'best' => $best,
            'notices' => $notices,
            'isFav' => $isFav,
            'preview' => $preview,
            'regions' => $db->fetchAll('SELECT id, name, parent_id FROM regions WHERE is_active = 1 ORDER BY sort'),
            'withMap' => $hotel['latitude'] !== null,
            'bodyClass' => 'has-mobile-bar',
            'idemKey' => bin2hex(random_bytes(16)),
        ]);
    }

    public function rates(string $slug): Response
    {
        $hotel = (new HotelRepository(App::db()))->findPublishedBySlug($slug);
        $this->notFoundUnless($hotel);
        $criteria = StayCriteriaValidator::fromInput($this->request->query, true);
        $ctx = PricingContext::load(App::db(), [(int) $hotel['id']], $criteria);
        $out = [];
        foreach ((new PricingService())->quotesForHotel($ctx, PricingProfile::forUser(App::db(), $this->user()), (int) $hotel['id']) as $q) {
            $out[] = ['room' => $q->roomName, 'kind' => $q->kind, 'total' => $q->total, 'total_text' => money($q->total, $q->currency), 'message' => $q->message];
        }
        return $this->json(['ok' => true, 'rates' => $out]);
    }
}
