<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Response;
use App\Exceptions\ValidationException;
use App\Services\AuditService;

/** Fiyat kuralları: kapsam, tarih, gün, sezon, erken rezervasyon, son dakika, uzun konaklama, kampanya. */
final class RuleController extends AdminController
{
    public const KINDS = [
        'global' => 'Genel', 'institution' => 'Kurum', 'price_group' => 'Fiyat grubu', 'hotel' => 'Otel', 'room' => 'Oda',
        'date_range' => 'Tarih aralığı', 'weekday' => 'Hafta içi', 'weekend' => 'Hafta sonu', 'season' => 'Sezon', 'holiday' => 'Bayram',
        'early_booking' => 'Erken rezervasyon', 'last_minute' => 'Son dakika', 'long_stay' => 'Uzun konaklama', 'campaign' => 'Kampanya',
    ];
    public const ADJ = ['discount_percent' => '% indirim', 'discount_per_night' => 'Gecelik TL indirim (oda başı)', 'surcharge_percent' => '% ek ücret', 'surcharge_per_night' => 'Gecelik TL ek ücret (oda başı)'];

    public function index(): Response
    {
        $db = $this->db();
        $rows = $db->fetchAll('SELECT rr.*, i.name AS inst, pg.name AS pg, h.name AS hotel, r.name AS room, s.name AS season FROM rate_rules rr LEFT JOIN institutions i ON i.id = rr.institution_id LEFT JOIN price_groups pg ON pg.id = rr.price_group_id LEFT JOIN hotels h ON h.id = rr.hotel_id LEFT JOIN rooms r ON r.id = rr.room_id LEFT JOIN seasons s ON s.id = rr.season_id ORDER BY rr.is_active DESC, rr.priority DESC, rr.id');
        $editId = $this->request->int('duzenle');
        $rooms = [];
        foreach ($db->fetchAll('SELECT r.id, r.name, h.name AS hotel FROM rooms r JOIN hotels h ON h.id = r.hotel_id ORDER BY h.name, r.sort') as $r) {
            $rooms[$r['id']] = $r['hotel'] . ' — ' . $r['name'];
        }
        return $this->admin('rules', [
            'title' => 'Fiyat Kuralları', 'rows' => $rows,
            'edit' => $editId ? $db->fetch('SELECT * FROM rate_rules WHERE id = ?', [$editId]) : null,
            'institutions' => options($db->fetchAll('SELECT id, name FROM institutions ORDER BY name')),
            'groups' => options($db->fetchAll('SELECT id, name FROM price_groups ORDER BY name')),
            'hotels' => options($db->fetchAll('SELECT id, name FROM hotels ORDER BY name')),
            'rooms' => $rooms,
            'seasons' => options($db->fetchAll('SELECT id, name FROM seasons ORDER BY date_from')),
        ]);
    }

    public function save(): Response
    {
        $d = $this->validate([
            'name' => 'required|max:150', 'kind' => 'required|in:' . implode(',', array_keys(self::KINDS)), 'adjustment' => 'required|in:' . implode(',', array_keys(self::ADJ)),
            'value' => 'required|max:20', 'institution_id' => 'nullable|int', 'price_group_id' => 'nullable|int', 'hotel_id' => 'nullable|int', 'room_id' => 'nullable|int',
            'season_id' => 'nullable|int', 'stay_from' => 'nullable|date', 'stay_to' => 'nullable|date', 'min_nights' => 'nullable|int|max:365',
            'min_lead_days' => 'nullable|int|max:730', 'max_lead_days' => 'nullable|int|max:730', 'priority' => 'required|int|min:0|max:10000',
            'stackable' => 'bool', 'is_active' => 'bool', 'valid_from' => 'nullable|datetime', 'valid_to' => 'nullable|datetime',
        ], ['kind' => 'Kural türü', 'adjustment' => 'Uygulama', 'value' => 'Değer', 'priority' => 'Öncelik']);
        $isPercent = str_ends_with($d['adjustment'], 'percent');
        $val = $isPercent ? \App\Core\Money::parsePercent((string) $d['value']) : \App\Core\Money::parse((string) $d['value']);
        if ($val === null || $val <= 0 || ($isPercent && $val > 10000)) {
            throw new ValidationException(['value' => $isPercent ? 'Geçerli bir yüzde girin (0–100).' : 'Geçerli bir tutar girin.']);
        }
        $d['value'] = $val;
        $days = array_values(array_unique(array_filter(array_map('intval', $this->request->arr('weekdays')), static fn ($x) => $x >= 1 && $x <= 7)));
        sort($days);
        $d['weekdays'] = count($days) === 7 || !$days ? null : implode(',', $days);
        if ($d['stay_from'] && $d['stay_to'] && $d['stay_to'] < $d['stay_from']) {
            throw new ValidationException(['stay_to' => 'Konaklama bitişi başlangıçtan önce olamaz.']);
        }
        $id = $this->request->int('id');
        if ($id) {
            $old = $this->db()->fetch('SELECT * FROM rate_rules WHERE id = ?', [$id]);
            $this->notFoundUnless($old);
            $this->db()->update('rate_rules', $d, ['id' => $id]);
            AuditService::log('rate_rule.update', 'rate_rule', $id, $old, $d);
        } else {
            $id = $this->db()->insert('rate_rules', $d + ['created_by' => $this->uid()]);
            AuditService::log('rate_rule.create', 'rate_rule', $id, null, $d);
        }
        $this->flash('success', 'Fiyat kuralı kaydedildi.');
        return $this->redirect('/yonetim/fiyat-kurallari');
    }

    public function delete(string $id): Response
    {
        $old = $this->db()->fetch('SELECT * FROM rate_rules WHERE id = ?', [(int) $id]);
        $this->notFoundUnless($old);
        $this->db()->delete('rate_rules', ['id' => (int) $id]);
        AuditService::log('rate_rule.delete', 'rate_rule', (int) $id, $old, null);
        $this->flash('success', 'Kural silindi.');
        return $this->redirect('/yonetim/fiyat-kurallari');
    }
}
