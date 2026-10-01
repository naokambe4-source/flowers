<?php
declare(strict_types=1);

namespace App\Controllers\Member;

use App\Controllers\Controller;
use App\Core\App;
use App\Core\Response;
use App\Exceptions\HttpException;
use App\Exceptions\ValidationException;
use App\Policies\BookingPolicy;
use App\Services\OfferService;
use App\Services\Pricing\PricingContext;
use App\Services\Pricing\PricingProfile;
use App\Services\Pricing\PricingService;
use App\Validators\StayCriteriaValidator;

/** Manuel konaklama talebi ve teklif yanıtları. */
final class RequestController extends Controller
{
    public function create(): Response
    {
        $db = App::db();
        $criteria = null;
        try {
            $criteria = StayCriteriaValidator::fromInput($this->request->query, false);
        } catch (ValidationException) {
        }
        $hotelId = $this->request->int('otel') ?: null;
        $hotel = $hotelId ? $db->fetch("SELECT id, name, region_id FROM hotels WHERE id = ? AND status = 'published'", [$hotelId]) : null;
        $target = null;
        $refId = $this->request->int('hedef') ?: null;
        if ($hotel && $criteria && $refId) {
            $ctx = PricingContext::load($db, [(int) $hotel['id']], $criteria);
            $q = (new PricingService())->referenceQuote($ctx, PricingProfile::forUser($db, $this->user()), (int) $hotel['id'], null);
            if ($q && $q->kind === 'target' && $q->referencePriceId === $refId) {
                $target = $q;
            }
        }
        return $this->view('member/request_form', [
            'title' => 'Teklif iste',
            'criteria' => $criteria,
            'hotel' => $hotel,
            'roomId' => $this->request->int('oda_tipi') ?: null,
            'rooms' => $hotel ? $db->fetchAll('SELECT id, name FROM rooms WHERE hotel_id = ? AND is_active = 1 ORDER BY sort', [$hotel['id']]) : [],
            'hotels' => $db->fetchAll("SELECT id, name FROM hotels WHERE status = 'published' ORDER BY name"),
            'regions' => $db->fetchAll('SELECT id, name, parent_id FROM regions WHERE is_active = 1 ORDER BY sort'),
            'concepts' => $db->fetchAll('SELECT id, name FROM concepts WHERE is_active = 1 ORDER BY sort'),
            'regionId' => $this->request->int('bolge') ?: ($hotel['region_id'] ?? null),
            'target' => $target,
            'idem' => bin2hex(random_bytes(16)),
        ]);
    }

    public function store(): Response
    {
        $u = $this->user();
        $criteria = StayCriteriaValidator::fromInput($this->request->post, true);
        $d = $this->validate([
            'otel' => 'nullable|int', 'bolge' => 'nullable|int', 'oda_tipi' => 'nullable|int', 'konsept' => 'nullable|int',
            'notlar' => 'nullable|max:2000', 'telefon' => 'required|phone', 'hedef' => 'nullable|int',
        ], ['otel' => 'Otel', 'bolge' => 'Bölge', 'notlar' => 'Notlar', 'telefon' => 'Telefon'], $this->request->post);
        if (empty($d['otel']) && empty($d['bolge'])) {
            throw new ValidationException(['bolge' => 'Bir otel veya bölge seçin.']);
        }
        $target = null;
        if (!empty($d['hedef']) && !empty($d['otel'])) {
            $ctx = PricingContext::load(App::db(), [(int) $d['otel']], $criteria);
            $q = (new PricingService())->referenceQuote($ctx, PricingProfile::forUser(App::db(), $u), (int) $d['otel'], null);
            if ($q && $q->referencePriceId === (int) $d['hedef']) {
                $target = $q;
            }
        }
        $idem = preg_replace('/[^a-f0-9]/', '', (string) ($this->request->post['idem'] ?? '')) ?? '';
        $req = (new OfferService(App::db()))->createRequest($u, $criteria, [
            'hotel_id' => $d['otel'], 'region_id' => $d['bolge'], 'room_id' => $d['oda_tipi'], 'concept_id' => $d['konsept'],
            'notes' => $d['notlar'], 'phone' => $d['telefon'],
            'target_minor' => $target?->total, 'target_reference_id' => $target?->referencePriceId,
        ], mb_substr($idem, 0, 64));
        $this->flash('success', 'Konaklama talebiniz alındı (' . $req['code'] . '). Teklif hazırlandığında bilgilendirileceksiniz.');
        return $this->redirect('/tekliflerim/' . $req['code']);
    }

    public function index(): Response
    {
        $u = $this->user();
        $rows = App::db()->fetchAll(
            "SELECT ar.*, h.name AS hotel_name, r.name AS region_name,
                    (SELECT COUNT(*) FROM offers o WHERE o.request_id = ar.id AND o.status = 'sent') AS live_offers
             FROM accommodation_requests ar LEFT JOIN hotels h ON h.id = ar.hotel_id LEFT JOIN regions r ON r.id = ar.region_id
             WHERE ar.user_id = ? ORDER BY ar.created_at DESC LIMIT 100",
            [$u['id']],
        );
        return $this->view('member/requests', ['title' => 'Tekliflerim', 'rows' => $rows]);
    }

    private function find(string $code): array
    {
        $req = App::db()->fetch('SELECT ar.*, h.name AS hotel_name, r.name AS region_name, c.name AS concept_name FROM accommodation_requests ar LEFT JOIN hotels h ON h.id = ar.hotel_id LEFT JOIN regions r ON r.id = ar.region_id LEFT JOIN concepts c ON c.id = ar.concept_id WHERE ar.code = ?', [$code]);
        if (!$req || !BookingPolicy::canView($this->user(), $req)) {
            throw new HttpException(404);
        }
        return $req;
    }

    public function show(string $code): Response
    {
        $req = $this->find($code);
        $offers = App::db()->fetchAll('SELECT o.*, h.name AS hotel_name, c.name AS concept_name, b.code AS booking_code FROM offers o JOIN hotels h ON h.id = o.hotel_id LEFT JOIN concepts c ON c.id = o.concept_id LEFT JOIN bookings b ON b.id = o.booking_id WHERE o.request_id = ? ORDER BY o.version DESC', [$req['id']]);
        return $this->view('member/request_show', ['title' => 'Talep ' . $req['code'], 'req' => $req, 'offers' => $offers, 'canModify' => BookingPolicy::canModify($this->user(), $req)]);
    }

    public function accept(string $code): Response
    {
        $req = $this->find($code);
        if (!$this->request->bool('kosullar')) {
            throw new ValidationException(['kosullar' => 'Teklif koşullarını onaylayın.']);
        }
        $booking = (new OfferService(App::db()))->accept($req, $this->user(), $this->request->int('teklif'), $this->request->int('surum'));
        $this->flash('success', 'Teklifi kabul ettiniz. Rezervasyonunuz otel teyidi için ekibimize iletildi.');
        return $this->redirect('/rezervasyonlarim/' . $booking['code']);
    }

    public function decline(string $code): Response
    {
        $req = $this->find($code);
        (new OfferService(App::db()))->decline($req, $this->user(), $this->request->int('teklif'));
        $this->flash('success', 'Teklif reddedildi. Yeni bir talep oluşturabilirsiniz.');
        return $this->redirect('/tekliflerim/' . $req['code']);
    }

    public function cancel(string $code): Response
    {
        $req = $this->find($code);
        (new OfferService(App::db()))->cancelRequest($req, $this->user());
        $this->flash('success', 'Talebiniz iptal edildi.');
        return $this->redirect('/tekliflerim');
    }
}
