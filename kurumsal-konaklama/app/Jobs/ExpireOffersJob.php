<?php
declare(strict_types=1);

namespace App\Jobs;

use App\Core\App;

/** Süresi dolan teklifleri ve taslak rezervasyonları kapatır. */
final class ExpireOffersJob implements JobInterface
{
    public function handle(array $payload): void
    {
        $db = App::db();
        $db->query("UPDATE offers SET status = 'expired' WHERE status = 'sent' AND valid_until < NOW()");
        $db->query(
            "UPDATE accommodation_requests ar SET status = 'expired'
             WHERE ar.status = 'offered' AND NOT EXISTS (SELECT 1 FROM offers o WHERE o.request_id = ar.id AND o.status = 'sent')"
        );
        $ttl = max(10, (int) \App\Services\SettingsService::int('booking.draft_ttl_minutes', 60));
        $db->query("DELETE FROM bookings WHERE status = 'draft' AND created_at < DATE_SUB(NOW(), INTERVAL $ttl MINUTE)");
    }
}
