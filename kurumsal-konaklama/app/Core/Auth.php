<?php
declare(strict_types=1);

namespace App\Core;

use App\Services\SettingsService;

/**
 * Oturumdaki kullanıcı ve izinleri. Her istekte hesap durumu veritabanından doğrulanır;
 * pasifleştirilen / askıya alınan kullanıcının oturumu anında düşer.
 */
final class Auth
{
    private static ?array $user = null;
    private static bool $resolved = false;
    private static array $permissions = [];

    public static function reset(): void
    {
        self::$user = null;
        self::$resolved = false;
        self::$permissions = [];
    }

    public static function user(): ?array
    {
        if (self::$resolved) {
            return self::$user;
        }
        self::$resolved = true;
        $uid = Session::get('uid');
        if (!is_int($uid)) {
            return null;
        }
        $idle = SettingsService::int('security.session_idle_minutes', 60) * 60;
        $last = (int) Session::get('last_activity', 0);
        if ($idle > 0 && $last > 0 && time() - $last > $idle) {
            Session::destroy();
            Session::start(Url::isHttps($_SERVER));
            Session::flash('info', 'Uzun süre işlem yapılmadığı için oturumunuz kapatıldı. Lütfen tekrar giriş yapın.');
            return null;
        }
        $row = App::db()->fetch(
            'SELECT u.*, r.slug AS role_slug, r.name AS role_name, r.is_staff, i.name AS institution_name, i.is_active AS institution_active
             FROM users u JOIN roles r ON r.id = u.role_id LEFT JOIN institutions i ON i.id = u.institution_id WHERE u.id = ?',
            [$uid],
        );
        if (!$row || $row['status'] !== 'active' || ($row['institution_id'] !== null && (int) $row['institution_active'] !== 1 && $row['role_slug'] !== 'super_admin')) {
            Session::destroy();
            return null;
        }
        if ((string) Session::get('pwd_stamp', '') !== (string) $row['password_changed_at']) {
            Session::destroy();
            return null;
        }
        Session::put('last_activity', time());
        self::$user = $row;
        self::$permissions = array_flip(App::db()->column(
            'SELECT p.slug FROM role_permission rp JOIN permissions p ON p.id = rp.permission_id WHERE rp.role_id = ?',
            [$row['role_id']],
        ));
        return self::$user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        $u = self::user();
        return $u ? (int) $u['id'] : null;
    }

    public static function isSuperAdmin(): bool
    {
        return (self::user()['role_slug'] ?? null) === 'super_admin';
    }

    public static function can(string $permission): bool
    {
        if (!self::check()) {
            return false;
        }
        return self::isSuperAdmin() || isset(self::$permissions[$permission]);
    }

    public static function canAny(array $permissions): bool
    {
        foreach ($permissions as $p) {
            if (self::can($p)) {
                return true;
            }
        }
        return false;
    }

    public static function login(array $user): void
    {
        Session::regenerate();
        Session::put('uid', (int) $user['id']);
        Session::put('pwd_stamp', (string) $user['password_changed_at']);
        Session::put('last_activity', time());
        self::reset();
    }

    public static function logout(): void
    {
        Session::destroy();
        self::reset();
    }

    /** Testler için: belirli kullanıcıyla oturum aç. */
    public static function actingAs(array $user): void
    {
        Session::put('uid', (int) $user['id']);
        Session::put('pwd_stamp', (string) $user['password_changed_at']);
        Session::put('last_activity', time());
        self::reset();
    }
}
