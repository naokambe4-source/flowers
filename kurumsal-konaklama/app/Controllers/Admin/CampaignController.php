<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Response;
use App\Exceptions\ValidationException;
use App\Services\AuditService;
use App\Services\ImageService;

/** Kampanyalar: yalnız geçerlilik tarihleri içindeyken ve bağlı fiyat kuralı aktifken üyelere gösterilir. */
final class CampaignController extends AdminController
{
    public function index(): Response
    {
        $db = $this->db();
        $editId = $this->request->int('duzenle');
        return $this->admin('campaigns', [
            'title' => 'Kampanyalar',
            'rows' => $db->fetchAll('SELECT c.*, h.name AS hotel, r.name AS region, rr.name AS rule_name, rr.is_active AS rule_active FROM campaigns c LEFT JOIN hotels h ON h.id = c.hotel_id LEFT JOIN regions r ON r.id = c.region_id LEFT JOIN rate_rules rr ON rr.id = c.rate_rule_id ORDER BY c.valid_to DESC'),
            'edit' => $editId ? $db->fetch('SELECT * FROM campaigns WHERE id = ?', [$editId]) : null,
            'hotels' => options($db->fetchAll('SELECT id, name FROM hotels ORDER BY name')),
            'regions' => options($db->fetchAll('SELECT id, name FROM regions ORDER BY sort')),
            'rules' => options($db->fetchAll("SELECT id, name FROM rate_rules ORDER BY name")),
        ]);
    }

    public function save(): Response
    {
        $d = $this->validate([
            'title' => 'required|max:200', 'summary' => 'nullable|max:500', 'description' => 'nullable|max:5000', 'hotel_id' => 'nullable|int', 'region_id' => 'nullable|int',
            'rate_rule_id' => 'nullable|int', 'valid_from' => 'required|datetime', 'valid_to' => 'required|datetime', 'stay_from' => 'nullable|date', 'stay_to' => 'nullable|date',
            'is_active' => 'bool', 'show_on_home' => 'bool', 'sort' => 'nullable|int',
        ], ['valid_from' => 'Başlangıç', 'valid_to' => 'Bitiş', 'rate_rule_id' => 'Fiyat kuralı']);
        if ($d['valid_to'] <= $d['valid_from']) {
            throw new ValidationException(['valid_to' => 'Bitiş başlangıçtan sonra olmalıdır.']);
        }
        $d['sort'] = (int) ($d['sort'] ?? 0);
        if ($f = $this->request->file('image')) {
            $d['image_path'] = ImageService::store($f, 'campaigns')['key'];
        }
        $id = $this->request->int('id');
        if ($id) {
            $old = $this->db()->fetch('SELECT * FROM campaigns WHERE id = ?', [$id]);
            $this->notFoundUnless($old);
            $this->db()->update('campaigns', $d, ['id' => $id]);
            AuditService::log('campaign.update', 'campaign', $id, $old, $d);
        } else {
            $id = $this->db()->insert('campaigns', $d);
            AuditService::log('campaign.create', 'campaign', $id, null, $d);
        }
        $this->flash('success', 'Kampanya kaydedildi.');
        return $this->redirect('/yonetim/kampanyalar');
    }

    public function delete(string $id): Response
    {
        $old = $this->db()->fetch('SELECT * FROM campaigns WHERE id = ?', [(int) $id]);
        $this->notFoundUnless($old);
        $this->db()->delete('campaigns', ['id' => (int) $id]);
        if ($old['image_path']) {
            ImageService::delete('campaigns', $old['image_path']);
        }
        AuditService::log('campaign.delete', 'campaign', (int) $id, $old, null);
        $this->flash('success', 'Kampanya silindi.');
        return $this->redirect('/yonetim/kampanyalar');
    }
}
