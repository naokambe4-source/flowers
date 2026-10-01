<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\App;
use App\Core\Crypto;

/** Yönetimden düzenlenen ayarlar (settings tablosu). Gizli değerler şifreli saklanır. */
final class SettingsService
{
    private static ?array $cache = null;

    public static function flush(): void
    {
        self::$cache = null;
    }

    private static function all(): array
    {
        if (self::$cache === null) {
            self::$cache = [];
            foreach (App::db()->fetchAll('SELECT `key`, `value`, is_secret FROM settings') as $row) {
                self::$cache[$row['key']] = $row;
            }
        }
        return self::$cache;
    }

    public static function get(string $key, string $default = ''): string
    {
        $row = self::all()[$key] ?? null;
        if ($row === null || $row['value'] === null) {
            return $default;
        }
        if ((int) $row['is_secret'] === 1) {
            return $row['value'] === '' ? '' : (Crypto::decrypt((string) $row['value']) ?? '');
        }
        return (string) $row['value'];
    }

    public static function int(string $key, int $default = 0): int
    {
        $v = self::get($key, (string) $default);
        return is_numeric($v) ? (int) $v : $default;
    }

    public static function bool(string $key): bool
    {
        return self::get($key) === '1';
    }

    public static function hasSecret(string $key): bool
    {
        $row = self::all()[$key] ?? null;
        return $row !== null && (string) $row['value'] !== '';
    }

    public static function set(string $key, string $value, bool $secret = false, ?int $userId = null): void
    {
        $stored = $secret && $value !== '' ? Crypto::encrypt($value) : $value;
        App::db()->query(
            'INSERT INTO settings (`key`, `value`, is_secret, updated_by) VALUES (:k, :v, :s, :u)
             ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), is_secret = VALUES(is_secret), updated_by = VALUES(updated_by)',
            ['k' => $key, 'v' => $stored, 's' => $secret ? 1 : 0, 'u' => $userId],
        );
        self::flush();
    }

    /** Gizli olmayan ayarların denetim kaydı için anlık görüntüsü. */
    public static function snapshot(array $keys): array
    {
        $out = [];
        foreach ($keys as $k) {
            $row = self::all()[$k] ?? null;
            $out[$k] = $row && (int) $row['is_secret'] === 1 ? '[gizli]' : self::get($k);
        }
        return $out;
    }
}
