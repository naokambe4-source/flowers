<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Crypto;
use App\Core\Response;
use App\Exceptions\DomainException;
use App\Exceptions\ProviderException;
use App\Services\AuditService;
use App\Services\HotelImportService;
use App\Services\ProviderRateService;
use App\Services\SettingsService;
use App\Providers\ProviderRegistry;

/**
 * Canlı otel verisi: gerçek otelleri ücretsiz kaynaklardan içe aktarma ve canlı fiyat bağlantısı.
 *  - OpenStreetMap: anahtarsız; otel adı, yıldız, konum, adres, telefon, web (fiyat yok → teklif akışı)
 *  - LiteAPI: ücretsiz hesap anahtarı; gerçek otel içeriği + fotoğraflar + canlı fiyat + rezervasyon
 */
final class LiveDataController extends AdminController
{
    private const NET_HINT = ' — Sunucunuz dış adreslere HTTPS bağlantısı kuramıyor olabilir. Hosting firmanızdan cURL ile overpass-api.de, api.liteapi.travel ve book.liteapi.travel adreslerine çıkış izni isteyin.';

    private static function hint(string $msg): string
    {
        return str_contains($msg, 'Bağlantı hatası') || str_contains($msg, 'Could not resolve') || str_contains($msg, 'timed out') ? $msg . self::NET_HINT : $msg;
    }

    private function provider(string $code): array
    {
        $p = $this->db()->fetch('SELECT * FROM providers WHERE code = ?', [$code]);
        if (!$p) {
            throw new DomainException('Sağlayıcı kaydı bulunamadı. Yönetim → Sistem Güncellemesi sayfasından güncellemeleri uygulayın.');
        }
        return $p;
    }

    public function index(): Response
    {
        $db = $this->db();
        $osm = $db->fetch("SELECT * FROM providers WHERE code = 'osm'");
        $lite = $db->fetch("SELECT * FROM providers WHERE code = 'liteapi'");
        $liteKey = $lite ? $db->fetch("SELECT last4, updated_at FROM provider_credentials WHERE provider_id = ? AND key_name = 'api_key'", [$lite['id']]) : null;
        $regions = $db->fetchAll(
            "SELECT r.id, r.name, r.parent_id, r.latitude,
                    (SELECT COUNT(*) FROM hotels h WHERE h.region_id = r.id AND h.data_source = 'osm' AND h.status = 'published') AS osm_count,
                    (SELECT COUNT(*) FROM hotels h WHERE h.region_id = r.id AND h.data_source = 'liteapi' AND h.status = 'published') AS lite_count,
                    (SELECT COUNT(*) FROM hotels h WHERE h.region_id = r.id AND h.data_source = 'manual' AND h.is_demo = 0 AND h.status = 'published') AS manual_count
             FROM regions r WHERE r.is_active = 1 ORDER BY r.sort",
        );
        return $this->admin('live_data', [
            'title' => 'Canlı otel verisi',
            'osm' => $osm, 'lite' => $lite, 'liteKey' => $liteKey,
            'liteSandbox' => $liteKey !== null && $lite && $this->isSandbox((int) $lite['id']),
            'regions' => $regions,
            'liveSearch' => SettingsService::bool('providers.live_search'),
            'demoActive' => (int) $db->value('SELECT COUNT(*) FROM hotels WHERE is_demo = 1') > 0,
            'totals' => [
                'osm' => (int) $db->value("SELECT COUNT(*) FROM hotels WHERE data_source = 'osm' AND status = 'published'"),
                'liteapi' => (int) $db->value("SELECT COUNT(*) FROM hotels WHERE data_source = 'liteapi' AND status = 'published'"),
            ],
        ]);
    }

    private function isSandbox(int $providerId): bool
    {
        try {
            $a = ProviderRegistry::find($providerId);
            return $a instanceof \App\Providers\Adapters\LiteApiProvider && $a->isSandbox();
        } catch (\Throwable) {
            return false;
        }
    }

    /** LiteAPI anahtarını kaydeder, sağlayıcıyı etkinleştirir ve canlı aramayı açar. */
    public function saveLiteApi(): Response
    {
        $p = $this->provider('liteapi');
        $key = trim((string) ($this->request->post['api_key'] ?? ''));
        $hasKey = (bool) $this->db()->value("SELECT 1 FROM provider_credentials WHERE provider_id = ? AND key_name = 'api_key'", [$p['id']]);
        if ($key === '' && !$hasKey) {
            throw new \App\Exceptions\ValidationException(['api_key' => 'LiteAPI anahtarını girin (dashboard.liteapi.travel → API Keys).']);
        }
        if ($key !== '' && !preg_match('/^[A-Za-z0-9_\-]{16,120}$/', $key)) {
            throw new \App\Exceptions\ValidationException(['api_key' => 'Anahtar biçimi geçersiz görünüyor.']);
        }
        if ($key !== '') {
            $this->db()->query(
                'INSERT INTO provider_credentials (provider_id, key_name, value_encrypted, last4, updated_by) VALUES (?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE value_encrypted = VALUES(value_encrypted), last4 = VALUES(last4), updated_by = VALUES(updated_by)',
                [$p['id'], 'api_key', Crypto::encrypt($key), mb_substr($key, -4), $this->uid()],
            );
        }
        $booking = $this->request->bool('booking_authorized');
        $this->db()->update('providers', ['is_enabled' => 1, 'display_authorized' => 1, 'booking_authorized' => $booking ? 1 : 0, 'status' => 'unknown'], ['id' => $p['id']]);
        SettingsService::set('providers.live_search', '1', false, $this->uid());
        ProviderRegistry::reset();
        AuditService::log('liteapi.configure', 'provider', (int) $p['id'], null, ['key_changed' => $key !== '', 'booking_authorized' => $booking]);
        $t = ProviderRateService::test((int) $p['id']);
        $this->flash($t['ok'] ? 'success' : 'error', ($t['ok'] ? 'LiteAPI bağlandı: ' . $t['message'] : 'LiteAPI bağlantı testi başarısız: ' . self::hint($t['message'])));
        return $this->redirect('/yonetim/canli-veri');
    }

    public function test(string $code): Response
    {
        $p = $this->provider($code === 'osm' ? 'osm' : 'liteapi');
        $t = ProviderRateService::test((int) $p['id']);
        $this->flash($t['ok'] ? 'success' : 'error', $t['ok'] ? $t['message'] : self::hint($t['message']));
        return $this->redirect('/yonetim/canli-veri');
    }

    /** Seçilen bölgeler için içe aktarma. Paylaşımlı hostingte süre sınırına takılmamak için ~20 sn'de durur. */
    public function import(): Response
    {
        $source = (string) ($this->request->post['kaynak'] ?? '');
        if (!in_array($source, ['osm', 'liteapi'], true)) {
            throw new DomainException('Kaynak seçin.');
        }
        $p = $this->provider($source);
        if ((int) $p['is_enabled'] !== 1) {
            throw new DomainException($source === 'liteapi' ? 'Önce LiteAPI anahtarını kaydedin.' : 'OpenStreetMap kaynağı kapalı.');
        }
        $ids = array_values(array_unique(array_map('intval', (array) ($this->request->post['bolgeler'] ?? []))));
        if (!$ids) {
            throw new \App\Exceptions\ValidationException(['bolgeler' => 'En az bir bölge seçin.']);
        }
        $max = max(1, min(100, $this->request->int('adet') ?: 30));
        @set_time_limit(120);
        $svc = new HotelImportService($this->db());
        $start = microtime(true);
        $sum = ['found' => 0, 'created' => 0, 'updated' => 0, 'merged' => 0, 'images' => 0];
        $done = [];
        $left = [];
        $errors = [];
        foreach ($ids as $rid) {
            if (microtime(true) - $start > 20) {
                $left[] = $rid;
                continue;
            }
            try {
                $r = $svc->importRegion((int) $p['id'], $rid, ['max_new' => $max, 'with_details' => true, 'max_images' => 6, 'user_id' => $this->uid()]);
                foreach ($sum as $k => $_) {
                    $sum[$k] += $r[$k];
                }
                $done[] = $rid;
                if ($source === 'osm') {
                    usleep(1500000); // Overpass ortak sunucusuna nazik davran
                }
            } catch (ProviderException $e) {
                $errors[] = $e->getMessage();
                break;
            }
        }
        $names = $left ? implode(', ', $this->db()->column('SELECT name FROM regions WHERE id IN (' . implode(',', array_map('intval', $left)) . ')')) : '';
        $msg = sprintf('%d bölge işlendi: %d otel bulundu, %d yeni otel eklendi, %d mevcut otel güncellendi%s, %d fotoğraf indirildi.',
            count($done), $sum['found'], $sum['created'], $sum['updated'], $sum['merged'] ? ', ' . $sum['merged'] . ' otel OpenStreetMap kaydıyla birleştirildi' : '', $sum['images']);
        if ($left) {
            $msg .= ' Süre sınırı nedeniyle kalan bölgeler: ' . $names . ' — aynı işlemi tekrar başlatın (eklenmiş oteller tekrar eklenmez).';
        }
        $this->flash($errors ? 'error' : 'success', $msg . ($errors ? ' Hata: ' . self::hint($errors[0]) : '') . ($svc->warnings ? ' (' . count($svc->warnings) . ' uyarı: ' . mb_strimwidth(implode('; ', $svc->warnings), 0, 200, '…') . ')' : ''));
        return $this->redirect('/yonetim/canli-veri');
    }

    public function remove(): Response
    {
        $source = (string) ($this->request->post['kaynak'] ?? '');
        if (($this->request->post['onay'] ?? '') !== 'SIL') {
            throw new \App\Exceptions\ValidationException(['onay' => 'Onaylamak için SIL yazın.']);
        }
        $r = (new HotelImportService($this->db()))->removeSource($source);
        $this->flash('success', $r['deleted'] . ' otel silindi' . ($r['unpublished'] ? ', rezervasyon geçmişi olan ' . $r['unpublished'] . ' otel yayından kaldırıldı' : '') . '.');
        return $this->redirect('/yonetim/canli-veri');
    }

    public function liveSearch(): Response
    {
        $on = $this->request->bool('acik');
        SettingsService::set('providers.live_search', $on ? '1' : '0', false, $this->uid());
        $this->flash('success', $on ? 'Canlı fiyat sorgusu açıldı.' : 'Canlı fiyat sorgusu kapatıldı; yalnız anlaşmalı fiyatlar ve teklif akışı kullanılır.');
        return $this->redirect('/yonetim/canli-veri');
    }
}
