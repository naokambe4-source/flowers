<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Config;
use App\Core\Money;
use App\Core\Response;
use App\Exceptions\ValidationException;
use App\Services\AuditService;
use App\Services\SettingsService;

/** Sistem ayarları: fiyat, vergi, önbellek, güvenlik, rezervasyon, e-posta (SMTP), harita. */
final class SettingsController extends AdminController
{
    /** anahtar => [etiket, tür, ek] ; tür: percent | int | text | bool | secret | select */
    public const FIELDS = [
        'Fiyatlandırma' => [
            'pricing.default_discount_bp' => ['Varsayılan üye indirimi (%)', 'percent', 'Kurum veya fiyat grubu indirimi yoksa uygulanır.'],
            'pricing.max_discount_bp' => ['Azami toplam indirim (%)', 'percent', 'Kurallar + üye + promosyon toplamı bu oranı aşamaz.'],
            'pricing.vat_bp' => ['KDV oranı (%)', 'percent', 'Otelde ayrı oran girilmemişse kullanılır.'],
            'pricing.accommodation_tax_bp' => ['Konaklama vergisi (%)', 'percent', ''],
            'pricing.reference_max_age_hours' => ['Referans fiyat azami yaşı (saat)', 'int', 'Bu süreden eski doğrulanmış fiyatlar karşılaştırmada kullanılmaz.'],
        ],
        'Rezervasyon' => [
            'booking.max_nights' => ['En fazla gece', 'int', ''],
            'booking.max_rooms' => ['Tek seferde en fazla oda', 'int', ''],
            'booking.draft_ttl_minutes' => ['Tamamlanmamış taslak saklama süresi (dk)', 'int', ''],
            'offer.default_validity_hours' => ['Varsayılan teklif geçerliliği (saat)', 'int', ''],
        ],
        'Önbellek ve API' => [
            'cache.content_ttl' => ['Otel içeriği önbellek süresi (sn)', 'int', ''],
            'cache.rates_ttl' => ['Fiyat / müsaitlik önbellek süresi (sn)', 'int', 'Kısa tutun (örn. 300–900).'],
            'cache.destination_ttl' => ['Destinasyon önbellek süresi (sn)', 'int', ''],
        ],
        'Güvenlik' => [
            'security.session_idle_minutes' => ['Hareketsiz oturum süresi (dk)', 'int', ''],
            'security.login_max_attempts' => ['Hatalı giriş sınırı', 'int', ''],
            'security.login_decay_minutes' => ['Giriş kilit süresi (dk)', 'int', ''],
        ],
        'E-posta (SMTP)' => [
            'mail.enabled' => ['E-posta gönderimi etkin', 'bool', ''],
            'mail.host' => ['SMTP sunucusu', 'text', 'örn. mail.alanadi.com'],
            'mail.port' => ['Port', 'int', '587 (TLS) veya 465 (SSL)'],
            'mail.encryption' => ['Şifreleme', 'select', ['tls' => 'STARTTLS', 'ssl' => 'SSL', 'none' => 'Yok']],
            'mail.username' => ['Kullanıcı adı', 'text', ''],
            'mail.password' => ['Parola', 'secret', 'Kayıtlı parola gösterilmez; değiştirmek için yazın.'],
            'mail.from_email' => ['Gönderen e-posta', 'text', ''],
            'mail.from_name' => ['Gönderen adı', 'text', ''],
            'mail.admin_notify_email' => ['Yönetici bildirim e-postası', 'text', 'Yeni talep/başvuru bildirimleri'],
        ],
        'Harita' => [
            'map.tile_url' => ['Harita katman adresi', 'text', 'Varsayılan OpenStreetMap. Yoğun kullanımda OSM kullanım politikası gereği ticari bir karo sağlayıcısı kullanın.'],
            'map.attribution' => ['Harita kaynak gösterimi (zorunlu)', 'text', ''],
        ],
    ];

    public function index(): Response
    {
        $values = [];
        foreach (self::FIELDS as $group) {
            foreach ($group as $k => $f) {
                $values[$k] = $f[1] === 'secret' ? (SettingsService::hasSecret($k) ? '__set__' : '') : SettingsService::get($k);
            }
        }
        $cronToken = (string) Config::get('app.cron_token', '');
        return $this->admin('settings', ['title' => 'Sistem Ayarları', 'values' => $values, 'cronUrl' => strlen($cronToken) >= 32 ? base_url('/cron/' . $cronToken) : null, 'phpBinary' => PHP_BINARY]);
    }

    public function save(): Response
    {
        $errors = [];
        $changes = [];
        foreach (self::FIELDS as $group) {
            foreach ($group as $k => [$label, $type]) {
                $field = str_replace('.', '__', $k);
                $raw = $this->request->post[$field] ?? null;
                if ($type === 'bool') {
                    $v = in_array($raw, ['1', 'on'], true) ? '1' : '0';
                } elseif ($raw === null) {
                    continue;
                } else {
                    $raw = trim((string) $raw);
                    if ($type === 'secret') {
                        if ($raw === '') {
                            continue;
                        }
                        SettingsService::set($k, $raw, true, $this->uid());
                        $changes[$k] = '[gizli değiştirildi]';
                        continue;
                    }
                    if ($type === 'percent') {
                        $bp = Money::parsePercent($raw);
                        if ($bp === null || $bp > 10000) {
                            $errors[$field] = "$label 0–100 arasında olmalıdır.";
                            continue;
                        }
                        $v = (string) $bp;
                    } elseif ($type === 'int') {
                        if (!ctype_digit($raw)) {
                            $errors[$field] = "$label pozitif tam sayı olmalıdır.";
                            continue;
                        }
                        $v = $raw;
                    } elseif ($type === 'select') {
                        $opts = self::FIELDS['E-posta (SMTP)'][$k][2] ?? [];
                        $v = array_key_exists($raw, $opts) ? $raw : (string) array_key_first($opts);
                    } else {
                        $v = mb_substr($raw, 0, 500);
                    }
                }
                if (SettingsService::get($k) !== $v) {
                    $changes[$k] = $v;
                }
                SettingsService::set($k, $v, false, $this->uid());
            }
        }
        if ((int) SettingsService::get('pricing.default_discount_bp') > (int) SettingsService::get('pricing.max_discount_bp')) {
            $errors['pricing__default_discount_bp'] = 'Varsayılan indirim, azami indirimden büyük olamaz.';
        }
        if (str_contains(SettingsService::get('map.tile_url'), '{z}') === false) {
            $errors['map__tile_url'] = 'Harita adresi {z}/{x}/{y} değişkenlerini içermelidir.';
        }
        AuditService::log('settings.update', 'settings', null, null, $changes);
        if ($errors) {
            throw new ValidationException($errors, 'Bazı ayarlar kaydedilemedi.');
        }
        $this->flash('success', 'Ayarlar kaydedildi.');
        return $this->redirect('/yonetim/ayarlar');
    }

    /** Veriyi silmeden bekleyen migration'ları gösterir / uygular (SSH erişimi olmayan cPanel kullanıcıları için). */
    public function updates(): Response
    {
        $m = new \App\Core\Migrator($this->db(), APP_ROOT . '/database/migrations');
        return $this->admin('updates', ['title' => 'Sistem güncellemesi', 'applied' => $m->applied(), 'pending' => array_map('basename', $m->pending()), 'version' => \App\Core\App::VERSION]);
    }

    public function runUpdates(): Response
    {
        $done = (new \App\Core\Migrator($this->db(), APP_ROOT . '/database/migrations'))->migrate();
        AuditService::log('system.migrate', 'migrations', null, null, ['applied' => $done]);
        $this->flash('success', $done ? 'Uygulanan güncellemeler: ' . implode(', ', $done) : 'Bekleyen güncelleme yok.');
        return $this->redirect('/yonetim/guncelleme');
    }
}
