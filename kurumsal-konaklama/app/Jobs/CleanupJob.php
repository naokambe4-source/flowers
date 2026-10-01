<?php
declare(strict_types=1);

namespace App\Jobs;

use App\Core\App;
use App\Providers\ProviderCache;

/** Eski kayıt temizliği: süresi geçen önbellek, hız sınırı, kullanılmış tokenlar, eski API logları ve geçici dosyalar. */
final class CleanupJob implements JobInterface
{
    public function handle(array $payload): void
    {
        $db = App::db();
        ProviderCache::purgeExpired();
        $db->query('DELETE FROM rate_limits WHERE reset_at < NOW()');
        $db->query('DELETE FROM password_tokens WHERE expires_at < DATE_SUB(NOW(), INTERVAL 7 DAY)');
        $db->query('DELETE FROM api_request_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)');
        $db->query("DELETE FROM jobs WHERE status = 'done' AND completed_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $db->query('DELETE FROM reference_prices WHERE valid_until < DATE_SUB(NOW(), INTERVAL 180 DAY) AND id NOT IN (SELECT reference_price_id FROM (SELECT reference_price_id FROM bookings WHERE reference_price_id IS NOT NULL) x)');
        foreach (glob(APP_ROOT . '/storage/tmp/*') ?: [] as $f) {
            if (is_file($f) && filemtime($f) < time() - 86400 && basename($f) !== '.htaccess') {
                @unlink($f);
            }
        }
    }
}
