<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Kurulum yolundan bağımsız URL üretimi.
 * Ana domain (/), tek alt klasör (/oteller) ve iç içe klasör (/kurumsal/konaklama) desteklenir.
 */
final class Url
{
    private static ?string $basePath = null;
    private static ?string $assetVersion = null;

    public static function reset(): void
    {
        self::$basePath = null;
    }

    public static function setBasePath(string $path): void
    {
        self::$basePath = rtrim($path, '/');
    }

    /** "/kurumsal/konaklama" veya kök kurulumda "". */
    public static function basePath(): string
    {
        if (self::$basePath !== null) {
            return self::$basePath;
        }
        $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
        $dir = str_replace('\\', '/', dirname($script));
        $dir = ($dir === '/' || $dir === '.') ? '' : rtrim($dir, '/');
        return self::$basePath = $dir;
    }

    public static function isHttps(array $server): bool
    {
        if (!empty($server['HTTPS']) && strtolower((string) $server['HTTPS']) !== 'off') {
            return true;
        }
        if ((string) ($server['SERVER_PORT'] ?? '') === '443') {
            return true;
        }
        if (Config::get('app.behind_proxy', false)) {
            return strtolower((string) ($server['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
        }
        return false;
    }

    /** Uygulama kökünün mutlak adresi. Kurulumda kaydedilen adres önceliklidir (Host header enjeksiyonuna karşı). */
    public static function origin(): string
    {
        $configured = (string) Config::get('app.url', '');
        if ($configured !== '') {
            $parts = parse_url($configured);
            if (!empty($parts['host'])) {
                $scheme = $parts['scheme'] ?? 'https';
                $port = isset($parts['port']) ? ':' . $parts['port'] : '';
                return $scheme . '://' . $parts['host'] . $port;
            }
        }
        $scheme = self::isHttps($_SERVER) ? 'https' : 'http';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost');
        $host = preg_replace('/[^A-Za-z0-9\.\-:\[\]]/', '', $host) ?: 'localhost';
        return $scheme . '://' . $host;
    }

    /** Site içi yol: "/oteller/x" → "/kurumsal/konaklama/oteller/x". */
    public static function to(string $path = '/', array $query = []): string
    {
        $path = '/' . ltrim($path, '/');
        $url = self::basePath() . ($path === '/' ? '/' : $path);
        $query = array_filter($query, static fn ($v) => $v !== null && $v !== '' && $v !== []);
        if ($query) {
            $url .= '?' . http_build_query($query);
        }
        return $url;
    }

    /** Mutlak adres (e-posta, QR, voucher için). */
    public static function absolute(string $path = '/', array $query = []): string
    {
        return self::origin() . self::to($path, $query);
    }

    public static function asset(string $path): string
    {
        self::$assetVersion ??= self::computeAssetVersion();
        return self::basePath() . '/assets/' . ltrim($path, '/') . '?v=' . self::$assetVersion;
    }

    private static function computeAssetVersion(): string
    {
        $f = APP_ROOT . '/assets/css/app.css';
        return is_file($f) ? substr(md5((string) filemtime($f) . App::VERSION), 0, 8) : App::VERSION;
    }

    /** Açık yönlendirmeyi engellemek için yalnız site içi yolları kabul eder. */
    public static function safeRedirectTarget(?string $target, string $fallback = '/panel'): string
    {
        if (!is_string($target) || $target === '' || !str_starts_with($target, '/') || str_starts_with($target, '//') || str_contains($target, '\\')) {
            return self::to($fallback);
        }
        $base = self::basePath();
        if ($base !== '' && !str_starts_with($target, $base . '/') && $target !== $base) {
            return self::to($fallback);
        }
        return $target;
    }
}
