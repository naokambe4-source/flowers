<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\App;

/** Veritabanı tabanlı sabit pencere hız sınırlayıcı (giriş, form ve sağlayıcı çağrıları). */
final class RateLimiter
{
    public static function tooManyAttempts(string $key, int $max): bool
    {
        $row = App::db()->fetch('SELECT hits, reset_at FROM rate_limits WHERE bucket = ?', [hash('sha256', $key)]);
        if (!$row || strtotime((string) $row['reset_at']) <= time()) {
            return false;
        }
        return (int) $row['hits'] >= $max;
    }

    public static function hit(string $key, int $decaySeconds): int
    {
        $bucket = hash('sha256', $key);
        $reset = date('Y-m-d H:i:s', time() + $decaySeconds);
        App::db()->query(
            'INSERT INTO rate_limits (bucket, hits, reset_at) VALUES (:b, 1, :r)
             ON DUPLICATE KEY UPDATE
                hits = IF(reset_at <= NOW(), 1, hits + 1),
                reset_at = IF(reset_at <= NOW(), VALUES(reset_at), reset_at)',
            ['b' => $bucket, 'r' => $reset],
        );
        return (int) App::db()->value('SELECT hits FROM rate_limits WHERE bucket = ?', [$bucket]);
    }

    /** Sınır aşılmadıysa sayar ve true döner. */
    public static function attempt(string $key, int $max, int $decaySeconds): bool
    {
        if (self::tooManyAttempts($key, $max)) {
            return false;
        }
        self::hit($key, $decaySeconds);
        return true;
    }

    public static function clear(string $key): void
    {
        App::db()->delete('rate_limits', ['bucket' => hash('sha256', $key)]);
    }

    public static function availableIn(string $key): int
    {
        $reset = App::db()->value('SELECT reset_at FROM rate_limits WHERE bucket = ?', [hash('sha256', $key)]);
        return $reset ? max(0, strtotime((string) $reset) - time()) : 0;
    }
}
