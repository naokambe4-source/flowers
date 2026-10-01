<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Response;
use App\Exceptions\DomainException;
use App\Exceptions\ValidationException;
use App\Services\AuditService;
use App\Services\Pricing\PricingContext;
use App\Services\Pricing\PricingProfile;
use App\Services\Pricing\PricingService;
use App\Validators\StayCriteriaValidator;

/**
 * Fiyat kaynakları ayrı yönetilir:
 *  - Fiyat planları + gecelik anlaşmalı/kampanya fiyatları (rates)
 *  - Manuel doğrulanmış referans fiyatlar (reference_prices) — yalnız hedef teklif ve tasarruf karşılaştırması için
 */
final class PricingController extends AdminController
{
    public function index(): Response
    {
        $db = $this->db();
        $hotelId = $this->request->int('otel');
        $hotel = $hotelId ? $db->fetch('SELECT id, name, is_contracted, contract_valid_until FROM hotels WHERE id = ?', [$hotelId]) : null;
        $data = ['title' => 'Fiyatlar', 'hotel' => $hotel, 'hotels' => options($db->fetchAll('SELECT id, name FROM hotels ORDER BY name'))];
        if ($hotel) {
            $data['rooms'] = options($db->fetchAll('SELECT id, name FROM rooms WHERE hotel_id = ? ORDER BY sort', [$hotelId]));
            $data['concepts'] = options($db->fetchAll('SELECT id, name FROM concepts ORDER BY sort'));
            $data['plans'] = $db->fetchAll('SELECT rp.*, r.name AS room_name, c.name AS concept_name FROM rate_plans rp JOIN rooms r ON r.id = rp.room_id LEFT JOIN concepts c ON c.id = rp.concept_id WHERE rp.hotel_id = ? ORDER BY r.sort, rp.id', [$hotelId]);
            $planId = $this->request->int('plan') ?: (int) ($data['plans'][0]['id'] ?? 0);
            $data['planId'] = $planId;
            $data['editPlan'] = $this->request->int('plan_duzenle') ? $db->fetch('SELECT * FROM rate_plans WHERE id = ? AND hotel_id = ?', [$this->request->int('plan_duzenle'), $hotelId]) : null;
            $from = (string) ($this->request->query['bas'] ?? date('Y-m-d'));
            $from = preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) ? $from : date('Y-m-d');
            $data['from'] = $from;
            $data['calendar'] = $planId ? $db->fetchAll('SELECT * FROM rates WHERE rate_plan_id = ? AND stay_date BETWEEN ? AND DATE_ADD(?, INTERVAL 41 DAY) ORDER BY stay_date', [$planId, $from, $from]) : [];
            $data['references'] = $db->fetchAll('SELECT rp.*, r.name AS room_label, c.name AS concept_name, pr.name AS provider_name FROM reference_prices rp LEFT JOIN rooms r ON r.id = rp.room_id LEFT JOIN concepts c ON c.id = rp.concept_id LEFT JOIN providers pr ON pr.id = rp.provider_id WHERE rp.hotel_id = ? ORDER BY rp.valid_until DESC LIMIT 50', [$hotelId]);
        }
        return $this->admin('pricing', $data);
    }

    public function savePlan(): Response
    {
        $d = $this->validate([
            'hotel_id' => 'required|int', 'room_id' => 'required|int', 'name' => 'required|max:150', 'concept_id' => 'nullable|int',
            'source' => 'required|in:contract,campaign', 'currency' => 'required|in:TRY,EUR,USD', 'tax_included' => 'bool',
            'base_adults' => 'required|int|min:1|max:6', 'free_child_max_age' => 'required|int|min:0|max:17', 'child_max_age' => 'required|int|min:1|max:18',
            'refundable' => 'bool', 'free_cancel_days' => 'nullable|int|max:365', 'cancellation_policy' => 'nullable|max:3000', 'payment_terms' => 'nullable|max:3000',
            'is_bookable' => 'bool', 'member_discount_applies' => 'bool', 'contract_reference' => 'nullable|max:120',
            'valid_from' => 'nullable|date', 'valid_to' => 'nullable|date', 'is_active' => 'bool', 'notes' => 'nullable|max:2000',
        ], ['room_id' => 'Oda', 'name' => 'Plan adı', 'base_adults' => 'Fiyata dahil yetişkin', 'free_child_max_age' => 'Ücretsiz çocuk yaşı', 'child_max_age' => 'Çocuk üst yaşı']);
        if ((int) $this->db()->value('SELECT hotel_id FROM rooms WHERE id = ?', [$d['room_id']]) !== (int) $d['hotel_id']) {
            throw new ValidationException(['room_id' => 'Oda bu otele ait değil.']);
        }
        if ($d['free_child_max_age'] >= $d['child_max_age']) {
            throw new ValidationException(['free_child_max_age' => 'Ücretsiz çocuk yaşı, çocuk üst yaşından küçük olmalıdır.']);
        }
        if ($d['is_bookable'] && !(int) $this->db()->value('SELECT is_contracted FROM hotels WHERE id = ?', [$d['hotel_id']])) {
            throw new ValidationException(['is_bookable' => 'Kesin satış yetkisi yalnız anlaşmalı otellerde verilebilir. Önce otelde “Anlaşmalı otel” işaretleyin.']);
        }
        $d['verified_by'] = $this->uid();
        $d['verified_at'] = date('Y-m-d H:i:s');
        $id = $this->request->int('id');
        if ($id) {
            $old = $this->db()->fetch('SELECT * FROM rate_plans WHERE id = ?', [$id]);
            $this->notFoundUnless($old);
            $this->db()->update('rate_plans', $d, ['id' => $id]);
            AuditService::log('rate_plan.update', 'rate_plan', $id, $old, $d);
        } else {
            $id = $this->db()->insert('rate_plans', $d);
            AuditService::log('rate_plan.create', 'rate_plan', $id, null, $d);
        }
        $this->flash('success', 'Fiyat planı kaydedildi.');
        return $this->redirect('/yonetim/fiyatlar', ['otel' => $d['hotel_id'], 'plan' => $id]);
    }

    /** Tarih aralığı + gün filtresiyle toplu gecelik fiyat girişi. */
    public function saveRates(): Response
    {
        $d = $this->validate([
            'plan_id' => 'required|int', 'from' => 'required|date', 'to' => 'required|date',
            'price' => 'required|money', 'single' => 'nullable|money', 'extra_adult' => 'nullable|money', 'child' => 'nullable|money',
        ], ['from' => 'Başlangıç', 'to' => 'Bitiş', 'price' => 'Gecelik fiyat', 'single' => 'Tek kişi fiyatı', 'extra_adult' => 'İlave yetişkin', 'child' => 'Çocuk']);
        $plan = $this->db()->fetch('SELECT * FROM rate_plans WHERE id = ?', [$d['plan_id']]);
        $this->notFoundUnless($plan);
        if ($d['to'] < $d['from'] || (strtotime($d['to']) - strtotime($d['from'])) / 86400 > 731) {
            throw new ValidationException(['to' => 'Tarih aralığı geçersiz (en fazla 2 yıl).']);
        }
        if ((int) $d['price'] <= 0) {
            throw new ValidationException(['price' => 'Gecelik fiyat sıfırdan büyük olmalıdır.']);
        }
        $days = array_map('intval', $this->request->arr('weekdays'));
        $count = 0;
        $this->db()->transaction(function ($db) use ($d, $days, &$count): void {
            $cur = new \DateTimeImmutable($d['from']);
            $end = new \DateTimeImmutable($d['to']);
            while ($cur <= $end) {
                if (!$days || in_array((int) $cur->format('N'), $days, true)) {
                    $db->query(
                        'INSERT INTO rates (rate_plan_id, stay_date, price_minor, single_minor, extra_adult_minor, child_minor, updated_by)
                         VALUES (:p, :d, :pr, :s, :e, :c, :u)
                         ON DUPLICATE KEY UPDATE price_minor = VALUES(price_minor), single_minor = VALUES(single_minor), extra_adult_minor = VALUES(extra_adult_minor), child_minor = VALUES(child_minor), updated_by = VALUES(updated_by)',
                        ['p' => $d['plan_id'], 'd' => $cur->format('Y-m-d'), 'pr' => $d['price'], 's' => $d['single'], 'e' => (int) ($d['extra_adult'] ?? 0), 'c' => (int) ($d['child'] ?? 0), 'u' => $this->uid()],
                    );
                    $count++;
                }
                $cur = $cur->modify('+1 day');
            }
        });
        AuditService::log('rates.bulk', 'rate_plan', (int) $d['plan_id'], null, ['from' => $d['from'], 'to' => $d['to'], 'days' => $count, 'price_minor' => $d['price']]);
        $this->flash('success', "$count gün için fiyat kaydedildi.");
        return $this->redirect('/yonetim/fiyatlar', ['otel' => $plan['hotel_id'], 'plan' => $plan['id'], 'bas' => $d['from']]);
    }

    /** Manuel doğrulanmış referans fiyat: satış yetkisi VERMEZ; hedef teklif ve karşılaştırma içindir. */
    public function saveReference(): Response
    {
        $d = $this->validate([
            'hotel_id' => 'required|int', 'room_id' => 'nullable|int', 'room_name' => 'nullable|max:150', 'source_label' => 'required|max:150',
            'total' => 'required|money', 'currency' => 'required|in:TRY', 'tax_included' => 'bool', 'check_in' => 'required|date', 'check_out' => 'required|date',
            'adults' => 'required|int|min:1|max:30', 'children_ages' => 'nullable|max:60', 'rooms_count' => 'required|int|min:1|max:10',
            'concept_id' => 'nullable|int', 'refundable' => 'nullable|in:0,1', 'cancellation_summary' => 'nullable|max:500',
            'captured_at' => 'required|datetime', 'valid_until' => 'required|datetime', 'notes' => 'nullable|max:500', 'verified' => 'accepted',
        ], ['source_label' => 'Kaynak', 'total' => 'Toplam', 'captured_at' => 'Sorgu zamanı', 'valid_until' => 'Geçerlilik', 'verified' => 'Doğrulama onayı', 'adults' => 'Yetişkin', 'rooms_count' => 'Oda sayısı']);
        if ($d['check_out'] <= $d['check_in']) {
            throw new ValidationException(['check_out' => 'Çıkış girişten sonra olmalıdır.']);
        }
        if ($d['valid_until'] <= $d['captured_at']) {
            throw new ValidationException(['valid_until' => 'Geçerlilik, sorgu zamanından sonra olmalıdır.']);
        }
        $ages = array_values(array_filter(array_map('trim', preg_split('/[\s,;-]+/', (string) $d['children_ages']) ?: []), static fn ($a) => $a !== ''));
        foreach ($ages as $a) {
            if (!ctype_digit($a) || (int) $a > 17) {
                throw new ValidationException(['children_ages' => 'Çocuk yaşlarını virgülle girin (0–17).']);
            }
        }
        $ages = array_map('intval', $ages);
        sort($ages);
        $row = [
            'hotel_id' => $d['hotel_id'], 'room_id' => $d['room_id'], 'room_name' => $d['room_name'], 'source' => 'manual_reference',
            'source_label' => $d['source_label'], 'currency' => 'TRY', 'total_minor' => $d['total'], 'tax_included' => $d['tax_included'],
            'check_in' => $d['check_in'], 'check_out' => $d['check_out'], 'adults' => $d['adults'], 'children_ages' => implode(',', $ages),
            'rooms_count' => $d['rooms_count'], 'concept_id' => $d['concept_id'], 'refundable' => $d['refundable'] === null ? null : (int) $d['refundable'],
            'cancellation_summary' => $d['cancellation_summary'], 'captured_at' => $d['captured_at'], 'valid_until' => $d['valid_until'],
            'verified_by' => $this->uid(), 'verified_at' => date('Y-m-d H:i:s'), 'notes' => $d['notes'],
        ];
        $id = $this->db()->insert('reference_prices', $row);
        AuditService::log('reference_price.create', 'reference_price', $id, null, $row);
        $this->flash('success', 'Doğrulanmış referans fiyat kaydedildi. Bu kayıt satış yetkisi vermez; yalnız hedef teklif ve karşılaştırmada kullanılır.');
        return $this->redirect('/yonetim/fiyatlar', ['otel' => $d['hotel_id']]);
    }

    public function deleteReference(string $id): Response
    {
        $r = $this->db()->fetch('SELECT * FROM reference_prices WHERE id = ?', [(int) $id]);
        $this->notFoundUnless($r);
        if ($this->db()->value('SELECT 1 FROM bookings WHERE reference_price_id = ?', [$r['id']])) {
            $this->db()->update('reference_prices', ['valid_until' => date('Y-m-d H:i:s')], ['id' => $r['id']]);
            $this->flash('success', 'Kayıt bir rezervasyonda kullanıldığı için silinmedi; geçerliliği sonlandırıldı.');
        } else {
            $this->db()->delete('reference_prices', ['id' => $r['id']]);
            $this->flash('success', 'Referans fiyat silindi.');
        }
        AuditService::log('reference_price.delete', 'reference_price', (int) $r['id'], $r, null);
        return $this->redirect('/yonetim/fiyatlar', ['otel' => $r['hotel_id']]);
    }

    /** Fiyat simülasyonu: seçilen kurum için hesap dökümünü gösterir. */
    public function simulate(): Response
    {
        $db = $this->db();
        $hotelId = $this->request->int('otel');
        $instId = $this->request->int('kurum') ?: null;
        $quotes = [];
        $criteria = null;
        $error = null;
        if ($hotelId) {
            try {
                $in = $this->request->query;
                $ages = array_filter(preg_split('/[\s,;]+/', (string) ($in['yaslar'] ?? '')) ?: [], static fn ($a) => $a !== '');
                $in['cocuk'] = count($ages);
                $criteria = StayCriteriaValidator::fromInput($in, true);
                $ctx = PricingContext::load($db, [$hotelId], $criteria);
                $profile = PricingProfile::forUser($db, ['id' => 0, 'institution_id' => $instId]);
                $svc = new PricingService();
                $quotes = $svc->quotesForHotel($ctx, $profile, $hotelId);
                if ($ref = $svc->referenceQuote($ctx, $profile, $hotelId, null)) {
                    $quotes[] = $ref;
                }
            } catch (ValidationException $e) {
                $error = $e->getMessage();
            }
        }
        return $this->admin('pricing_simulate', [
            'title' => 'Fiyat hesaplama', 'quotes' => $quotes, 'criteria' => $criteria, 'error' => $error, 'hotelId' => $hotelId, 'instId' => $instId,
            'hotels' => options($db->fetchAll('SELECT id, name FROM hotels ORDER BY name')),
            'institutions' => options($db->fetchAll('SELECT id, name FROM institutions ORDER BY name')),
        ]);
    }
}
