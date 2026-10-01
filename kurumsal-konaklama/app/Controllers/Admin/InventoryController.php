<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Response;
use App\Exceptions\ValidationException;
use App\Services\AuditService;
use App\Services\InventoryService;

/** Takvim tabanlı kontenjan: günlük oda adedi, satış açık/kapalı, stop sale, min/max konaklama. */
final class InventoryController extends AdminController
{
    public function index(): Response
    {
        $db = $this->db();
        $hotelId = $this->request->int('otel');
        $rooms = $hotelId ? options($db->fetchAll('SELECT id, name FROM rooms WHERE hotel_id = ? ORDER BY sort', [$hotelId])) : [];
        $roomId = $this->request->int('oda') ?: (int) (array_key_first($rooms) ?? 0);
        $from = (string) ($this->request->query['bas'] ?? date('Y-m-d'));
        $from = preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) ? $from : date('Y-m-d');
        return $this->admin('inventory', [
            'title' => 'Kontenjan',
            'hotels' => options($db->fetchAll('SELECT id, name FROM hotels ORDER BY name')),
            'hotelId' => $hotelId, 'rooms' => $rooms, 'roomId' => $roomId, 'from' => $from,
            'days' => $roomId ? $db->fetchAll('SELECT * FROM inventory WHERE room_id = ? AND stay_date BETWEEN ? AND DATE_ADD(?, INTERVAL 41 DAY) ORDER BY stay_date', [$roomId, $from, $from]) : [],
            'stopSales' => $hotelId ? $db->fetchAll('SELECT s.*, r.name AS room FROM stop_sales s LEFT JOIN rooms r ON r.id = s.room_id WHERE s.hotel_id = ? AND s.date_to >= CURDATE() ORDER BY s.date_from', [$hotelId]) : [],
        ]);
    }

    public function save(): Response
    {
        $d = $this->validate([
            'room_id' => 'required|int', 'from' => 'required|date', 'to' => 'required|date', 'total_units' => 'nullable|int|max:2000',
            'is_open' => 'nullable|in:0,1', 'min_stay' => 'nullable|int|max:60', 'max_stay' => 'nullable|int|max:365', 'note' => 'nullable|max:255',
        ], ['room_id' => 'Oda', 'from' => 'Başlangıç', 'to' => 'Bitiş', 'total_units' => 'Oda adedi']);
        $room = $this->db()->fetch('SELECT * FROM rooms WHERE id = ?', [$d['room_id']]);
        $this->notFoundUnless($room);
        if ($d['to'] < $d['from']) {
            throw new ValidationException(['to' => 'Bitiş başlangıçtan önce olamaz.']);
        }
        $fields = [];
        if ($d['total_units'] !== null) {
            $fields['total_units'] = (int) $d['total_units'];
        }
        if ($d['is_open'] !== null) {
            $fields['is_open'] = (int) $d['is_open'];
        }
        foreach (['min_stay', 'max_stay'] as $k) {
            if ($this->request->bool('clear_' . $k)) {
                $fields[$k] = null;
            } elseif ($d[$k] !== null) {
                $fields[$k] = (int) $d[$k];
            }
        }
        if ($d['note'] !== null) {
            $fields['note'] = $d['note'];
        }
        if (!$fields) {
            throw new ValidationException(['total_units' => 'Güncellenecek en az bir alan girin.']);
        }
        $days = array_map('intval', $this->request->arr('weekdays'));
        $r = (new InventoryService($this->db()))->bulkUpsert((int) $room['id'], $d['from'], $d['to'], $days, $fields, $this->uid());
        AuditService::log('inventory.bulk', 'room', (int) $room['id'], null, $fields + ['from' => $d['from'], 'to' => $d['to'], 'updated' => $r['updated']]);
        $this->flash('success', $r['updated'] . ' gün güncellendi.');
        if ($r['skipped']) {
            $this->flash('warning', 'Satılmış oda sayısından düşük adet girilemediği için şu günlerde adet değiştirilmedi: ' . implode(', ', array_map('tr_date_short', $r['skipped'])));
        }
        return $this->redirect('/yonetim/kontenjan', ['otel' => $room['hotel_id'], 'oda' => $room['id'], 'bas' => $d['from']]);
    }

    public function stopSale(): Response
    {
        $d = $this->validate(['hotel_id' => 'required|int', 'room_id' => 'nullable|int', 'date_from' => 'required|date', 'date_to' => 'required|date', 'reason' => 'nullable|max:255'], ['date_from' => 'Başlangıç', 'date_to' => 'Bitiş']);
        if ($d['date_to'] < $d['date_from']) {
            throw new ValidationException(['date_to' => 'Bitiş başlangıçtan önce olamaz.']);
        }
        $id = $this->db()->insert('stop_sales', $d + ['created_by' => $this->uid()]);
        AuditService::log('stop_sale.create', 'stop_sale', $id, null, $d);
        $this->flash('success', 'Stop sale eklendi. Bu tarihlerde yeni rezervasyon alınmaz; mevcut rezervasyonlar etkilenmez.');
        return $this->redirect('/yonetim/kontenjan', ['otel' => $d['hotel_id']]);
    }

    public function deleteStopSale(string $id): Response
    {
        $s = $this->db()->fetch('SELECT * FROM stop_sales WHERE id = ?', [(int) $id]);
        $this->notFoundUnless($s);
        $this->db()->delete('stop_sales', ['id' => $s['id']]);
        AuditService::log('stop_sale.delete', 'stop_sale', (int) $s['id'], $s, null);
        $this->flash('success', 'Stop sale kaldırıldı.');
        return $this->redirect('/yonetim/kontenjan', ['otel' => $s['hotel_id']]);
    }
}
