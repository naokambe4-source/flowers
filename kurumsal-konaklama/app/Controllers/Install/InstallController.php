<?php
declare(strict_types=1);

namespace App\Controllers\Install;

use App\Controllers\Controller;
use App\Core\App;
use App\Core\Config;
use App\Core\Crypto;
use App\Core\Database;
use App\Core\Migrator;
use App\Core\Response;
use App\Core\Session;
use App\Core\Url;
use App\Core\Validator;
use App\Core\View;
use App\Exceptions\ValidationException;

/**
 * Kurulum sihirbazı: ortam kontrolleri (PHP, uzantılar, yazma izni, HTTPS, rewrite, kurulum yolu),
 * veritabanı bağlantısı, migration, ilk Super Admin ve kurulum kilidi.
 * Rewrite çalışmıyorsa kurulum tamamlanmaz (tüm sayfaların 404 vermesini önlemek için).
 */
final class InstallController extends Controller
{
    private function nonce(): string
    {
        $seed = Session::get('install_seed');
        if (!is_string($seed)) {
            $seed = bin2hex(random_bytes(16));
            Session::put('install_seed', $seed);
        }
        return hash_hmac('sha256', 'rewrite-ok', $seed);
    }

    /** Yalnız temiz adres (rewrite) çalışıyorsa ulaşılabilir. */
    public function rewriteTest(): Response
    {
        $uri = (string) ($this->request->server['REQUEST_URI'] ?? '');
        if (str_contains($uri, '/index.php')) {
            return Response::json(['ok' => false], 404);
        }
        return Response::json(['ok' => true, 'nonce' => App::installed() ? null : $this->nonce()]);
    }

    public static function checks(): array
    {
        $ext = static fn (string $e) => extension_loaded($e);
        $writable = static function (string $dir): bool {
            if (!is_dir($dir)) {
                @mkdir($dir, 0750, true);
            }
            return is_dir($dir) && is_writable($dir);
        };
        $https = Url::isHttps($_SERVER);
        return [
            ['PHP 8.3 veya üzeri (mevcut: ' . PHP_VERSION . ')', PHP_VERSION_ID >= 80300, true],
            ['PDO MySQL uzantısı', $ext('pdo_mysql'), true],
            ['mbstring uzantısı', $ext('mbstring'), true],
            ['Şifreleme desteği (sodium veya OpenSSL AES-256-GCM)', \App\Core\Crypto::available(), true],
            ['sodium uzantısı (önerilir; yoksa OpenSSL kullanılır)', $ext('sodium'), false],
            ['fileinfo uzantısı (dosya türü doğrulama)', $ext('fileinfo'), true],
            ['DOM / XML uzantıları (PDF voucher)', $ext('dom') && $ext('xml'), true],
            ['openssl uzantısı', $ext('openssl'), $ext('sodium') ? false : true],
            ['cURL uzantısı (API sağlayıcıları)', $ext('curl'), false],
            ['GD uzantısı (görsel boyutlandırma, QR)', $ext('gd'), false],
            ['zip uzantısı (XLSX aktarım)', class_exists(\ZipArchive::class), false],
            ['config/ klasörü yazılabilir', $writable(APP_ROOT . '/config'), true],
            ['storage/ klasörleri yazılabilir', $writable(APP_ROOT . '/storage/private') && $writable(APP_ROOT . '/storage/tmp') && $writable(APP_ROOT . '/storage/logs') && $writable(APP_ROOT . '/storage/cache'), true],
            ['HTTPS bağlantısı' . ($https ? '' : ' (canlı kullanımda SSL sertifikası zorunludur)'), $https, false],
        ];
    }

    public function index(): Response
    {
        $detected = Url::origin() . Url::basePath();
        return Response::html(View::render('install/index', [
            'title' => 'Kurulum',
            'checks' => self::checks(),
            'basePath' => Url::basePath() === '' ? '/' : Url::basePath() . '/',
            'appUrl' => $detected,
            'rewriteUrl' => Url::to('/kurulum/rewrite-test'),
            'formAction' => Url::basePath() . '/index.php/kurulum',
        ], 'install'));
    }

    public function install(): Response
    {
        if (App::installed() || is_file(App::lockFile())) {
            return Response::to('/giris');
        }
        $in = $this->request->post;
        $d = Validator::make($in, [
            'db_host' => 'required|max:190', 'db_port' => 'required|int', 'db_name' => 'required|max:64', 'db_user' => 'required|max:80', 'db_pass' => 'nullable|max:200',
            'app_url' => 'required|url|max:255', 'site_name' => 'required|max:120',
            'first_name' => 'required|min:2|max:80', 'last_name' => 'required|min:2|max:80', 'email' => 'required|email|max:190',
            'password' => 'required|password|max:200', 'password_confirmation' => 'required|same:password',
        ], ['db_host' => 'Veritabanı sunucusu', 'db_port' => 'Port', 'db_name' => 'Veritabanı adı', 'db_user' => 'Veritabanı kullanıcısı', 'app_url' => 'Site adresi', 'site_name' => 'Site adı', 'password_confirmation' => 'Parola tekrarı'])->validate();

        foreach (self::checks() as [$label, $ok, $required]) {
            if ($required && !$ok) {
                throw new ValidationException(['checks' => 'Zorunlu gereksinim karşılanmıyor: ' . $label]);
            }
        }
        // Rewrite doğrulaması: tarayıcı testi (nonce) veya sunucu tarafı istek
        $nonceOk = hash_equals($this->nonce(), (string) ($in['rewrite_nonce'] ?? ''));
        if (!$nonceOk && !$this->serverRewriteCheck((string) $d['app_url'])) {
            throw new ValidationException(['rewrite' => 'Temiz adresler (mod_rewrite / .htaccess) çalışmıyor. .htaccess dosyasının yüklendiğinden ve hosting’de AllowOverride ayarının açık olduğundan emin olun. Bu düzeltilmeden kurulum tamamlanamaz.']);
        }
        $appUrl = rtrim((string) $d['app_url'], '/');
        $dbCfg = ['host' => $d['db_host'], 'port' => (int) $d['db_port'], 'name' => $d['db_name'], 'user' => $d['db_user'], 'pass' => (string) ($in['db_pass'] ?? ''), 'charset' => 'utf8mb4'];
        try {
            $db = new Database($dbCfg);
        } catch (\PDOException $e) {
            throw new ValidationException(['db_host' => 'Veritabanına bağlanılamadı: ' . preg_replace('/SQLSTATE\[\w+\] \[\d+\] /', '', $e->getMessage())]);
        }
        $version = (string) $db->value('SELECT VERSION()');
        if (stripos($version, 'mariadb') === false && version_compare($version, '8.0.0', '<')) {
            throw new ValidationException(['db_host' => 'MySQL 8 veya MariaDB 10.6+ gereklidir (mevcut: ' . $version . ').']);
        }
        $tables = $db->column('SHOW TABLES');
        if (in_array('migrations', $tables, true)) {
            throw new ValidationException(['db_name' => 'Bu veritabanında mevcut bir kurulum var. Veriyi korumak için yeni kurulum yapılmaz; güncelleme rehberini uygulayın (php bin/migrate.php).']);
        }
        if ($tables) {
            throw new ValidationException(['db_name' => 'Veritabanı boş değil. Lütfen boş bir veritabanı kullanın.']);
        }

        $config = [
            'app' => [
                'url' => $appUrl, 'key' => Crypto::generateKey(), 'env' => 'production', 'debug' => false,
                'timezone' => 'Europe/Istanbul', 'force_https' => str_starts_with($appUrl, 'https://'),
                'behind_proxy' => false, 'trusted_proxies' => [], 'cron_token' => bin2hex(random_bytes(24)),
            ],
            'db' => $dbCfg,
            'session' => ['name' => 'kk_session'],
        ];
        Config::set($config);
        App::setDb($db);
        (new Migrator($db, APP_ROOT . '/database/migrations'))->migrate();
        $roleId = (int) $db->value("SELECT id FROM roles WHERE slug = 'super_admin'");
        $db->insert('users', [
            'first_name' => $d['first_name'], 'last_name' => $d['last_name'], 'email' => $d['email'], 'role_id' => $roleId,
            'status' => 'active', 'password_hash' => password_hash((string) $in['password'], PASSWORD_DEFAULT), 'password_changed_at' => date('Y-m-d H:i:s'),
            'membership_type' => 'personel',
        ]);
        \App\Services\SettingsService::set('site.name', (string) $d['site_name']);
        $mode = (string) ($in['registration_mode'] ?? 'application');
        \App\Services\SettingsService::set('membership.registration_mode', in_array($mode, ['application', 'open', 'closed'], true) ? $mode : 'application');
        Config::write($config);
        file_put_contents(App::lockFile(), 'installed=' . date('c') . "\n");
        Session::forget('install_seed');
        $demoNote = '';
        if (!empty($in['demo'])) {
            try {
                $n = (new \App\Services\DemoDataService($db))->install();
                $demoNote = " $n demo otel yüklendi; canlıya geçmeden önce Yönetim → Demo / Canlı Mod sayfasından kaldırabilirsiniz.";
            } catch (\Throwable $e) {
                $demoNote = ' Demo veriler yüklenemedi (' . $e->getMessage() . '); daha sonra Yönetim → Demo / Canlı Mod sayfasından tekrar deneyebilirsiniz.';
            }
        }
        Session::flash('success', 'Kurulum tamamlandı. Yönetici hesabınızla giriş yapın.' . $demoNote);
        return Response::redirect(Url::basePath() . '/giris');
    }

    private function serverRewriteCheck(string $appUrl): bool
    {
        if (!function_exists('curl_init')) {
            return false;
        }
        $ch = curl_init(rtrim($appUrl, '/') . '/kurulum/rewrite-test');
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 5, CURLOPT_CONNECTTIMEOUT => 3, CURLOPT_FOLLOWLOCATION => false]);
        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        return $code === 200 && is_string($body) && str_contains($body, '"ok":true');
    }
}
