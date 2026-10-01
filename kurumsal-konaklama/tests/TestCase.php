<?php
declare(strict_types=1);

/**
 * Bağımlılıksız küçük test altyapısı. Gerçek MySQL/MariaDB test veritabanı kullanır.
 * Ortam değişkenleri: KK_TEST_DB_HOST, KK_TEST_DB_NAME, KK_TEST_DB_USER, KK_TEST_DB_PASS
 */

use App\Core\App;
use App\Core\Auth;
use App\Core\Config;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Migrator;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Url;
use App\Services\SettingsService;

define('KK_TESTING', true);

final class T
{
    public static int $pass = 0;
    public static array $fail = [];
    public static string $current = '';

    public static function eq(mixed $expected, mixed $actual, string $msg = ''): void
    {
        if ($expected === $actual) {
            self::$pass++;
            return;
        }
        self::$fail[] = self::$current . ': ' . $msg . ' — beklenen ' . var_export($expected, true) . ', gelen ' . var_export($actual, true);
    }

    public static function ok(bool $cond, string $msg): void
    {
        $cond ? self::$pass++ : self::$fail[] = self::$current . ': ' . $msg;
    }

    public static function throws(string $class, callable $fn, string $msg): ?\Throwable
    {
        try {
            $fn();
        } catch (\Throwable $e) {
            if ($e instanceof $class) {
                self::$pass++;
                return $e;
            }
            self::$fail[] = self::$current . ': ' . $msg . ' — farklı hata: ' . $e::class . ' ' . $e->getMessage();
            return $e;
        }
        self::$fail[] = self::$current . ': ' . $msg . ' — hata fırlatılmadı';
        return null;
    }
}

final class TestEnv
{
    public static function config(): array
    {
        return [
            'app' => ['url' => 'https://test.local/kurumsal/konaklama', 'key' => 'base64:' . base64_encode(str_repeat('t', 32)), 'env' => 'test', 'debug' => true, 'timezone' => 'Europe/Istanbul', 'force_https' => false, 'cron_token' => str_repeat('c', 40)],
            'db' => [
                'host' => getenv('KK_TEST_DB_HOST') ?: 'localhost', 'port' => 3306,
                'name' => getenv('KK_TEST_DB_NAME') ?: 'konak_test', 'user' => getenv('KK_TEST_DB_USER') ?: 'konak',
                'pass' => getenv('KK_TEST_DB_PASS') !== false ? getenv('KK_TEST_DB_PASS') : 'konakpw', 'charset' => 'utf8mb4',
            ],
            'session' => ['name' => 'kk_test'],
        ];
    }

    /** Veritabanını sıfırdan kurar: migration + test verisi. */
    public static function freshDatabase(): Database
    {
        $cfg = self::config();
        Config::set($cfg);
        $db = new Database($cfg['db']);
        $db->pdo()->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($db->column('SHOW TABLES') as $t) {
            $db->pdo()->exec('DROP TABLE `' . $t . '`');
        }
        $db->pdo()->exec('SET FOREIGN_KEY_CHECKS = 1');
        App::setDb($db);
        (new Migrator($db, APP_ROOT . '/database/migrations'))->migrate();
        SettingsService::flush();
        return $db;
    }

    public static function boot(): void
    {
        Config::set(self::config());
        date_default_timezone_set('Europe/Istanbul');
        mb_internal_encoding('UTF-8');
        Session::useArrayStorage();
        Url::setBasePath('/kurumsal/konaklama');
        $r = new ReflectionProperty(App::class, 'installed');
        $r->setValue(null, true);
    }

    /** Uygulama üzerinden istek (gerçek router, middleware, controller, view). */
    public static function request(string $method, string $path, array $data = [], array $headers = [], bool $withCsrf = true): Response
    {
        $server = ['REQUEST_METHOD' => $method, 'REQUEST_URI' => '/kurumsal/konaklama' . $path, 'REMOTE_ADDR' => '10.0.0.' . random_int(1, 200), 'HTTP_HOST' => 'test.local', 'SCRIPT_NAME' => '/kurumsal/konaklama/index.php'];
        foreach ($headers as $k => $v) {
            $server['HTTP_' . strtoupper(str_replace('-', '_', $k))] = $v;
        }
        $query = [];
        $parts = parse_url($path);
        parse_str($parts['query'] ?? '', $query);
        $post = $method === 'POST' ? $data + ($withCsrf ? ['_token' => Csrf::token()] : []) : [];
        if ($method === 'GET') {
            $query = $data + $query;
        }
        $req = new Request($method, Request::resolvePath($server), $query, $post, [], $server, []);
        App::setRequest($req);
        Auth::reset();
        $app = new App(APP_ROOT);
        return $app->handle($req);
    }

    public static function actingAs(string $email): array
    {
        $u = App::db()->fetch('SELECT * FROM users WHERE email = ?', [$email]);
        Auth::actingAs($u);
        return Auth::user() ?? $u;
    }

    public static function logout(): void
    {
        Session::useArrayStorage();
        Auth::reset();
    }
}
