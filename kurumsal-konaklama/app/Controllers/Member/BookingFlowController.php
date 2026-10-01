<?php
declare(strict_types=1);

namespace App\Controllers\Member;

use App\Controllers\Controller;
use App\Core\App;
use App\Core\Response;
use App\Core\Session;
use App\Exceptions\DomainException;
use App\Exceptions\PriceChangedException;
use App\Services\BookingService;
use App\Services\PromoService;
use App\Validators\StayCriteriaValidator;

/** 4 adımlı rezervasyon akışı: oda seçimi → misafirler → kontrol → onay. */
final class BookingFlowController extends Controller
{
    private function service(): BookingService
    {
        return new BookingService(App::db());
    }

    public function start(): Response
    {
        $u = $this->user();
        $criteria = StayCriteriaValidator::fromInput($this->request->post, true);
        $idem = preg_replace('/[^a-zA-Z0-9-]/', '', (string) ($this->request->post['idem'] ?? '')) ?? '';
        $booking = $this->service()->startDraft(
            $u,
            $this->request->int('otel'),
            $this->request->int('oda_tipi'),
            $this->request->int('plan') ?: null,
            $criteria,
            null,
            mb_substr($idem, 0, 64),
            ($ref = (string) ($this->request->post['teklif_ref'] ?? '')) !== '' && strlen($ref) <= 3000 && preg_match('/^[A-Za-z0-9_\-.:=+\/]+$/', $ref) ? $ref : null,
        );
        if ($booking['status'] !== 'draft') {
            return $this->redirect('/rezervasyonlarim/' . $booking['code']);
        }
        return $this->redirect('/rezervasyon/' . $booking['code'] . '/misafirler');
    }

    private function draft(string $code): array
    {
        $b = $this->service()->findDraftForUser($code, $this->user());
        return $b;
    }

    private function context(array $b): array
    {
        $db = App::db();
        return [
            'booking' => $b,
            'hotel' => $db->fetch('SELECT h.id, h.name, h.slug, h.address, h.cover_image_id, h.stars, r.name AS region_name FROM hotels h LEFT JOIN regions r ON r.id = h.region_id WHERE h.id = ?', [$b['hotel_id']]),
            'rooms' => $db->fetchAll('SELECT * FROM booking_rooms WHERE booking_id = ? ORDER BY id', [$b['id']]),
            'guests' => $db->fetchAll('SELECT * FROM booking_guests WHERE booking_id = ? ORDER BY booking_room_id, is_lead DESC, id', [$b['id']]),
            'breakdown' => json_decode((string) $b['price_breakdown'], true) ?: [],
            'terms' => json_decode((string) $b['terms_snapshot'], true) ?: [],
        ];
    }

    public function guests(string $code): Response
    {
        $b = $this->draft($code);
        if ($b['status'] !== 'draft') {
            return $this->redirect('/rezervasyonlarim/' . $b['code']);
        }
        return $this->view('member/booking_guests', ['title' => 'Misafir bilgileri', 'step' => 2] + $this->context($b));
    }

    public function saveGuests(string $code): Response
    {
        $u = $this->user();
        $b = $this->draft($code);
        if ($b['status'] !== 'draft') {
            return $this->redirect('/rezervasyonlarim/' . $b['code']);
        }
        $this->service()->saveGuests($b, $this->request->post);
        $promo = mb_strtoupper(trim((string) ($this->request->post['promosyon'] ?? '')));
        if ($promo !== '') {
            $p = (new PromoService(App::db()))->resolve($promo, $u, (int) $b['hotel_id'], (int) $b['nights']);
            App::db()->update('bookings', ['promo_code_id' => $p['id']], ['id' => $b['id']]);
            $fresh = App::db()->fetch('SELECT * FROM bookings WHERE id = ?', [$b['id']]);
            $q = $this->service()->requote($fresh, $u);
            $this->service()->refreshDraftPrice($fresh, $q);
            $this->flash('success', 'Promosyon kodu uygulandı.');
        }
        return $this->redirect('/rezervasyon/' . $b['code'] . '/kontrol');
    }

    public function review(string $code): Response
    {
        $u = $this->user();
        $b = $this->draft($code);
        if ($b['status'] !== 'draft') {
            return $this->redirect('/rezervasyonlarim/' . $b['code']);
        }
        if ((int) App::db()->value('SELECT COUNT(*) FROM booking_guests WHERE booking_id = ? AND is_lead = 1', [$b['id']]) < (int) $b['rooms_count']) {
            return $this->redirect('/rezervasyon/' . $b['code'] . '/misafirler');
        }
        // Fiyat ve müsaitlik yeniden doğrulanır
        $quote = $this->service()->requote($b, $u);
        $changed = false;
        $unavailable = null;
        if (!$quote->isBookable()) {
            $unavailable = $quote->message ?? 'Seçilen oda artık rezerve edilemiyor.';
        } elseif ($quote->hash() !== $b['quote_hash']) {
            $changed = (int) $quote->total !== (int) $b['total_minor'];
            $old = (int) $b['total_minor'];
            $this->service()->refreshDraftPrice($b, $quote);
            $b = App::db()->fetch('SELECT * FROM bookings WHERE id = ?', [$b['id']]);
            if ($changed) {
                Session::put('price_changed_' . $b['id'], $old);
            }
        }
        $oldTotal = Session::pull('price_changed_' . $b['id']);
        return $this->view('member/booking_review', [
            'title' => 'Fiyat ve koşul kontrolü', 'step' => 3, 'oldTotal' => is_int($oldTotal) ? $oldTotal : null, 'unavailable' => $unavailable,
        ] + $this->context($b));
    }

    public function confirm(string $code): Response
    {
        $u = $this->user();
        $b = $this->draft($code);
        if ($b['status'] !== 'draft') {
            return $this->redirect('/rezervasyonlarim/' . $b['code']);
        }
        if (!$this->request->bool('kosullar')) {
            throw new \App\Exceptions\ValidationException(['kosullar' => 'Devam etmek için rezervasyon ve iptal koşullarını onaylayın.']);
        }
        try {
            $result = $this->service()->confirm($b, $u, (string) ($this->request->post['quote_hash'] ?? ''));
        } catch (PriceChangedException $e) {
            Session::put('price_changed_' . $b['id'], $e->oldTotal);
            $this->flash('warning', $e->getMessage());
            return $this->redirect('/rezervasyon/' . $b['code'] . '/kontrol');
        }
        $this->flash('success', $result['status'] === 'confirmed'
            ? 'Rezervasyonunuz onaylandı. Voucher’ınızı bu sayfadan indirebilirsiniz.'
            : 'Rezervasyon talebiniz alındı. Otel teyidi sonrası bilgilendirileceksiniz.');
        return $this->redirect('/rezervasyonlarim/' . $result['code']);
    }
}
