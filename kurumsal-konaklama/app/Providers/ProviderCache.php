<?php
declare(strict_types=1);

namespace App\Providers;

use App\Core\App;

/**
 * Sağlayıcı yanıt önbelleği (api_cache tablosu).
 * İçerik uzun, fiyat/müsaitlik kısa süre tutulur; süreler yönetimden ayarlanır.
 */
final class ProviderCache
{
    public static function key(int $providerId, string $kind, string $signature): string
    {
        return hash('sha256', $providerId . '|' . $kind . '|' . $signature);
    }

    /** Süresi geçmemiş kayıt. */
    public static function get(string $key): mixed
    {
        $row = App::db()->fetch('SELECT payload FROM api_cache WHERE cache_key = ? AND expires_at > NOW()', [$key]);
        return $row ? json_decode((string) $row['payload'], true) : null;
    }

    public static function put(string $key, int $providerId, string $kind, mixed $value, int $ttl): void
    {
        App::db()->query(
            'INSERT INTO api_cache (cache_key, provider_id, kind, payload, created_at, expires_at) VALUES (:k, :p, :kind, :v, NOW(), :e)
             ON DUPLICATE KEY UPDATE payload = VALUES(payload), created_at = NOW(), expires_at = VALUES(expires_at)',
            ['k' => $key, 'p' => $providerId, 'kind' => $kind, 'v' => json_encode($value, JSON_UNESCAPED_UNICODE), 'e' => date('Y-m-d H:i:s', time() + max(1, $ttl))],
        );
    }

    /**
     * Önbellekte varsa döner; yoksa üretir. Üretim hata verirse hata yukarı taşınır
     * (geri dönüş zinciri ProviderRateService içinde yönetilir).
     */
    public static function remember(int $providerId, string $kind, string $signature, int $ttl, callable $fn): array
    {
        $key = self::key($providerId, $kind, $signature);
        $hit = self::get($key);
        if ($hit !== null) {
            return ['value' => $hit, 'from_cache' => true];
        }
        $value = $fn();
        self::put($key, $providerId, $kind, $value, $ttl);
        return ['value' => $value, 'from_cache' => false];
    }

    public static function purgeExpired(): int
    {
        return App::db()->query('DELETE FROM api_cache WHERE expires_at < NOW()')->rowCount();
    }

    public static function clear(?int $providerId = null, ?string $kind = null): int
    {
        $sql = 'DELETE FROM api_cache WHERE 1=1';
        $p = [];
        if ($providerId !== null) {
            $sql .= ' AND provider_id = :p';
            $p['p'] = $providerId;
        }
        if ($kind !== null) {
            $sql .= ' AND kind = :k';
            $p['k'] = $kind;
        }
        return App::db()->query($sql, $p)->rowCount();
    }
}
