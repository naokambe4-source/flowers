<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Crypto;
use App\Core\Response;
use App\Core\Session;
use App\Exceptions\DomainException;
use App\Exceptions\ProviderException;
use App\Providers\Contracts\Capability;
use App\Providers\ProviderCache;
use App\Providers\ProviderRegistry;
use App\Services\AuditService;
use App\Services\ProviderRateService;
use App\Services\SettingsService;

/**
 * API sağlayıcı yönetimi. Anahtarlar şifreli saklanır, ekranda tekrar gösterilmez, loglarda maskelenir.
 */
final class ProviderController extends AdminController
{
    public function index(): Response
    {
        $db = $this->db();
        $rows = $db->fetchAll('SELECT p.*, (SELECT COUNT(*) FROM api_request_logs l WHERE l.provider_id = p.id AND l.created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)) AS calls24, (SELECT COUNT(*) FROM api_request_logs l WHERE l.provider_id = p.id AND l.success = 0 AND l.created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)) AS errors24 FROM providers p ORDER BY p.id');
        $caps = [];
        foreach ($rows as $r) {
            try {
                $caps[$r['id']] = ProviderRegistry::make($r)->capabilities();
            } catch (\Throwable) {
                $caps[$r['id']] = [];
            }
        }
        return $this->admin('providers', [
            'title' => 'API Yönetimi', 'rows' => $rows, 'caps' => $caps,
            'liveSearch' => SettingsService::bool('providers.live_search'),
            'cache' => $db->fetchAll('SELECT kind, COUNT(*) AS c, SUM(expires_at > NOW()) AS valid FROM api_cache GROUP BY kind'),
            'logs' => $db->fetchAll('SELECT l.*, p.name AS provider FROM api_request_logs l LEFT JOIN providers p ON p.id = l.provider_id ORDER BY l.id DESC LIMIT 30'),
        ]);
    }

    private function find(string $id): array
    {
        $p = ProviderRegistry::row((int) $id);
        $this->notFoundUnless($p);
        return $p;
    }

    public function show(string $id): Response
    {
        $p = $this->find($id);
        $adapter = ProviderRegistry::make($p);
        $db = $this->db();
        $stored = [];
        foreach ($db->fetchAll('SELECT key_name, last4, updated_at FROM provider_credentials WHERE provider_id = ?', [$p['id']]) as $c) {
            $stored[$c['key_name']] = $c;
        }
        return $this->admin('provider_show', [
            'title' => $p['name'], 'p' => $p, 'adapter' => $adapter, 'fields' => $adapter::credentialFields(), 'stored' => $stored,
            'settings' => json_decode((string) $p['settings_json'], true) ?: [],
            'regions' => $db->fetchAll('SELECT r.id, r.name, r.parent_id, r.latitude, r.longitude, d.external_id, d.external_name, d.country_code, d.verified_at FROM regions r LEFT JOIN provider_destinations d ON d.region_id = r.id AND d.provider_id = ? ORDER BY r.sort', [$p['id']]),
            'hotelMaps' => $db->fetchAll('SELECT h.id, h.name, m.external_hotel_id, m.external_name, m.verified_at FROM hotels h LEFT JOIN provider_hotel_map m ON m.hotel_id = h.id AND m.provider_id = ? ORDER BY h.name', [$p['id']]),
            'destResults' => Session::pull('dest_results_' . $p['id'], null),
            'logs' => $db->fetchAll('SELECT * FROM api_request_logs WHERE provider_id = ? ORDER BY id DESC LIMIT 20', [$p['id']]),
        ]);
    }

    public function update(string $id): Response
    {
        $p = $this->find($id);
        $adapter = ProviderRegistry::make($p);
        $d = $this->validate(['is_enabled' => 'bool', 'booking_authorized' => 'bool', 'display_authorized' => 'bool', 'rate_limit_per_minute' => 'required|int|min:1|max:600', 'notes' => 'nullable|max:3000']);
        if ($p['code'] === 'manual') {
            $d['booking_authorized'] = 1;
            $d['display_authorized'] = 1;
            $d['is_enabled'] = 1;
        }
        if ($d['booking_authorized'] && !$adapter->supports(Capability::BOOKING)) {
            throw new DomainException('Bu sağlayıcı rezervasyon oluşturmayı desteklemiyor; rezervasyon yetkisi verilemez.');
        }
        // Ayarlar (gizli olmayan)
        $settings = json_decode((string) $p['settings_json'], true) ?: [];
        foreach ($this->request->arr('settings') as $k => $v) {
            if (!preg_match('/^[a-z_]{2,40}$/', (string) $k)) {
                continue;
            }
            $v = trim((string) $v);
            if ($k === 'base_url' && $v !== '' && !preg_match('#^https://[a-z0-9.-]+(:\d+)?(/.*)?$#i', $v)) {
                throw new DomainException('Servis adresi https:// ile başlamalıdır.');
            }
            if (str_starts_with((string) $k, 'path_') && $v !== '' && !preg_match('#^/[A-Za-z0-9/_\-.]*$#', $v)) {
                throw new DomainException('Uç nokta yolu geçersiz: ' . $k);
            }
            $settings[$k] = in_array($k, ['timeout'], true) ? max(2, min(60, (int) $v)) : ($k === 'apply_member_discount' ? $v === '1' : $v);
        }
        $d['settings_json'] = json_encode($settings, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (!$d['is_enabled']) {
            $d['status'] = 'disabled';
        } elseif ($p['status'] === 'disabled') {
            $d['status'] = 'unknown';
        }
        $this->db()->update('providers', $d, ['id' => $p['id']]);
        // Kimlik bilgileri: boş bırakılırsa mevcut değer korunur; asla geri gösterilmez
        $changedKeys = [];
        foreach ($adapter::credentialFields() as $key => $_) {
            $val = trim((string) ($this->request->post['cred'][$key] ?? ''));
            if ($val === '') {
                continue;
            }
            $this->db()->query(
                'INSERT INTO provider_credentials (provider_id, key_name, value_encrypted, last4, updated_by) VALUES (?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE value_encrypted = VALUES(value_encrypted), last4 = VALUES(last4), updated_by = VALUES(updated_by)',
                [$p['id'], $key, Crypto::encrypt($val), mb_substr($val, -4), $this->uid()],
            );
            $changedKeys[] = $key;
        }
        ProviderRegistry::reset();
        AuditService::log('provider.update', 'provider', (int) $p['id'], array_intersect_key($p, $d), $d + ['credentials_changed' => $changedKeys]);
        if ($this->request->has('live_search')) {
            SettingsService::set('providers.live_search', $this->request->bool('live_search') ? '1' : '0', false, $this->uid());
        }
        $this->flash('success', 'Sağlayıcı ayarları kaydedildi.' . ($changedKeys ? ' Güncellenen anahtar: ' . implode(', ', $changedKeys) . '.' : ''));
        return $this->redirect('/yonetim/api/' . $p['id']);
    }

    public function test(string $id): Response
    {
        $p = $this->find($id);
        $r = ProviderRateService::test((int) $p['id']);
        AuditService::log('provider.test', 'provider', (int) $p['id'], null, ['ok' => $r['ok']]);
        $this->flash($r['ok'] ? 'success' : 'error', ($r['ok'] ? 'Bağlantı başarılı: ' : 'Bağlantı başarısız: ') . $r['message']);
        return $this->back('/yonetim/api/' . $p['id']);
    }

    public function searchDestination(string $id): Response
    {
        $p = $this->find($id);
        $q = mb_substr(trim((string) ($this->request->post['q'] ?? '')), 0, 80);
        try {
            $results = ProviderRegistry::make($p)->searchDestinations($q);
            Session::put('dest_results_' . $p['id'], ['q' => $q, 'region_id' => $this->request->int('region_id'), 'items' => array_slice($results, 0, 30)]);
        } catch (ProviderException $e) {
            $this->flash('error', 'Destinasyon araması başarısız: ' . $e->getMessage());
        }
        return $this->redirect('/yonetim/api/' . $p['id'] . '#destinasyon');
    }

    /** Bölgeyi sağlayıcı destinasyon ID’si ile eşler. Türkiye dışı sonuçlar ek onay olmadan kabul edilmez. */
    public function mapDestination(string $id): Response
    {
        $p = $this->find($id);
        $d = $this->validate(['region_id' => 'required|int', 'external_id' => 'required|max:100', 'external_name' => 'required|max:200', 'country_code' => 'nullable|max:2', 'external_type' => 'nullable|max:40']);
        $cc = strtoupper((string) $d['country_code']);
        if ($cc !== 'TR' && !$this->request->bool('confirm_foreign')) {
            throw new DomainException('Seçilen sonuç Türkiye’de görünmüyor (' . ($cc ?: 'ülke bilinmiyor') . '). Antalya dışı aynı isimli yerleri yanlışlıkla eşlememek için ek onay kutusunu işaretleyin veya doğru sonucu seçin.');
        }
        $this->db()->query(
            'INSERT INTO provider_destinations (provider_id, region_id, external_id, external_name, external_type, country_code, verified_by, verified_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE external_id = VALUES(external_id), external_name = VALUES(external_name), external_type = VALUES(external_type), country_code = VALUES(country_code), verified_by = VALUES(verified_by), verified_at = NOW()',
            [$p['id'], $d['region_id'], $d['external_id'], $d['external_name'], $d['external_type'], $cc ?: null, $this->uid()],
        );
        AuditService::log('provider.destination', 'provider', (int) $p['id'], null, $d);
        $this->flash('success', 'Bölge eşleştirildi: ' . $d['external_name']);
        return $this->redirect('/yonetim/api/' . $p['id'] . '#destinasyon');
    }

    public function mapHotel(string $id): Response
    {
        $p = $this->find($id);
        $hotelId = $this->request->int('hotel_id');
        $ext = trim((string) ($this->request->post['external_hotel_id'] ?? ''));
        $this->notFoundUnless($this->db()->value('SELECT 1 FROM hotels WHERE id = ?', [$hotelId]));
        if ($ext === '') {
            $this->db()->delete('provider_hotel_map', ['provider_id' => $p['id'], 'hotel_id' => $hotelId]);
            $this->flash('success', 'Otel eşleştirmesi kaldırıldı.');
        } else {
            if (!preg_match('/^[A-Za-z0-9_\-:.]{1,100}$/', $ext)) {
                throw new DomainException('Dış otel kimliği geçersiz.');
            }
            $this->db()->query(
                'INSERT INTO provider_hotel_map (provider_id, hotel_id, external_hotel_id, external_name, verified_by, verified_at) VALUES (?, ?, ?, ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE external_hotel_id = VALUES(external_hotel_id), external_name = VALUES(external_name), verified_by = VALUES(verified_by), verified_at = NOW()',
                [$p['id'], $hotelId, $ext, mb_substr(trim((string) ($this->request->post['external_name'] ?? '')), 0, 200) ?: null, $this->uid()],
            );
            $this->flash('success', 'Otel eşleştirildi.');
        }
        AuditService::log('provider.hotel_map', 'provider', (int) $p['id'], null, ['hotel_id' => $hotelId, 'external' => $ext]);
        return $this->redirect('/yonetim/api/' . $p['id'] . '#oteller');
    }

    public function clearCache(): Response
    {
        $n = ProviderCache::clear($this->request->int('provider_id') ?: null, in_array($this->request->post['kind'] ?? '', ['content', 'rates', 'destination'], true) ? $this->request->post['kind'] : null);
        AuditService::log('provider.cache_clear', 'api_cache', null, null, ['deleted' => $n]);
        $this->flash('success', "$n önbellek kaydı silindi.");
        return $this->redirect('/yonetim/api');
    }
}
