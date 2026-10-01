<?php
declare(strict_types=1);

namespace App\Core;

/** Oturum yönetimi: HttpOnly, SameSite=Lax, HTTPS'te Secure; kurulum yoluna göre cookie path. */
final class Session
{
    private static bool $started = false;
    private static array $fake = [];
    private static bool $testing = false;

    public static function useArrayStorage(): void
    {
        self::$testing = true;
        self::$started = true;
        self::$fake = [];
    }

    public static function start(bool $secure): void
    {
        if (self::$started) {
            return;
        }
        $path = Url::basePath() === '' ? '/' : Url::basePath() . '/';
        $dir = APP_ROOT . '/storage/sessions';
        if (is_dir($dir) && is_writable($dir)) {
            session_save_path($dir);
        }
        session_name((string) Config::get('session.name', 'kk_session'));
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => $path,
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.gc_maxlifetime', '86400');
        session_start();
        self::$started = true;
    }

    private static function &store(): array
    {
        if (self::$testing) {
            return self::$fake;
        }
        return $_SESSION;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $s = &self::store();
        return $s[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        $s = &self::store();
        $s[$key] = $value;
    }

    public static function forget(string $key): void
    {
        $s = &self::store();
        unset($s[$key]);
    }

    public static function pull(string $key, mixed $default = null): mixed
    {
        $v = self::get($key, $default);
        self::forget($key);
        return $v;
    }

    public static function regenerate(): void
    {
        if (!self::$testing && session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        self::forget('_csrf');
    }

    public static function destroy(): void
    {
        if (self::$testing) {
            self::$fake = [];
            return;
        }
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $p['path'], 'secure' => $p['secure'], 'httponly' => true, 'samesite' => 'Lax']);
            session_destroy();
        }
    }

    public static function flash(string $type, string $message): void
    {
        $f = self::get('_flash', []);
        $f[] = ['type' => $type, 'message' => $message];
        self::put('_flash', $f);
    }

    public static function takeFlashes(): array
    {
        return (array) self::pull('_flash', []);
    }

    public static function flashInput(array $input, array $errors = []): void
    {
        unset($input['password'], $input['password_confirmation'], $input['_token'], $input['current_password']);
        self::put('_old', $input);
        self::put('_errors', $errors);
    }
}
