<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Money;
use App\Core\Response;
use App\Exceptions\ValidationException;
use App\Services\AuditService;

final class PromoController extends AdminController
{
    public function index(): Response
    {
        $db = $this->db();
        $editId = $this->request->int('duzenle');
        return $this->admin('promos', [
            'title' => 'Promosyonlar',
            'rows' => $db->fetchAll('SELECT p.*, i.name AS inst, h.name AS hotel FROM promo_codes p LEFT JOIN institutions i ON i.id = p.institution_id LEFT JOIN hotels h ON h.id = p.hotel_id ORDER BY p.created_at DESC'),
            'edit' => $editId ? $db->fetch('SELECT * FROM promo_codes WHERE id = ?', [$editId]) : null,
            'institutions' => options($db->fetchAll('SELECT id, name FROM institutions ORDER BY name')),
            'hotels' => options($db->fetchAll('SELECT id, name FROM hotels ORDER BY name')),
        ]);
    }

    public function save(): Response
    {
        $d = $this->validate([
            'code' => 'required|min:3|max:40', 'description' => 'nullable|max:255', 'adjustment' => 'required|in:discount_percent,discount_amount', 'value' => 'required|max:20',
            'max_uses' => 'nullable|int', 'per_user_limit' => 'nullable|int', 'institution_id' => 'nullable|int', 'hotel_id' => 'nullable|int', 'min_nights' => 'nullable|int',
            'valid_from' => 'nullable|datetime', 'valid_to' => 'nullable|datetime', 'is_active' => 'bool',
        ], ['code' => 'Kod', 'value' => 'Değer']);
        $d['code'] = mb_strtoupper(preg_replace('/[^A-Za-z0-9_-]/', '', $d['code']) ?? '');
        $v = $d['adjustment'] === 'discount_percent' ? Money::parsePercent((string) $d['value']) : Money::parse((string) $d['value']);
        if ($v === null || $v <= 0 || ($d['adjustment'] === 'discount_percent' && $v > 10000)) {
            throw new ValidationException(['value' => 'Geçerli bir değer girin.']);
        }
        $d['value'] = $v;
        $id = $this->request->int('id');
        try {
            if ($id) {
                $old = $this->db()->fetch('SELECT * FROM promo_codes WHERE id = ?', [$id]);
                $this->notFoundUnless($old);
                $this->db()->update('promo_codes', $d, ['id' => $id]);
                AuditService::log('promo.update', 'promo_code', $id, $old, $d);
            } else {
                $id = $this->db()->insert('promo_codes', $d);
                AuditService::log('promo.create', 'promo_code', $id, null, $d);
            }
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new ValidationException(['code' => 'Bu kod zaten kullanılıyor.']);
            }
            throw $e;
        }
        $this->flash('success', 'Promosyon kodu kaydedildi.');
        return $this->redirect('/yonetim/promosyonlar');
    }

    public function delete(string $id): Response
    {
        $old = $this->db()->fetch('SELECT * FROM promo_codes WHERE id = ?', [(int) $id]);
        $this->notFoundUnless($old);
        if ((int) $old['used_count'] > 0) {
            $this->db()->update('promo_codes', ['is_active' => 0], ['id' => $old['id']]);
            $this->flash('success', 'Kod kullanıldığı için silinmedi, pasif yapıldı.');
        } else {
            $this->db()->delete('promo_codes', ['id' => $old['id']]);
            $this->flash('success', 'Promosyon kodu silindi.');
        }
        AuditService::log('promo.delete', 'promo_code', (int) $old['id'], $old, null);
        return $this->redirect('/yonetim/promosyonlar');
    }
}
