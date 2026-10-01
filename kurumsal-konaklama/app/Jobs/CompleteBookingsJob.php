<?php
declare(strict_types=1);

namespace App\Jobs;

use App\Core\App;

/** Çıkış tarihi geçen onaylı rezervasyonları "Tamamlandı" yapar. */
final class CompleteBookingsJob implements JobInterface
{
    public function handle(array $payload): void
    {
        $db = App::db();
        $rows = $db->fetchAll("SELECT id FROM bookings WHERE status = 'confirmed' AND check_out < CURDATE() LIMIT 500");
        foreach ($rows as $r) {
            $db->transaction(static function ($db) use ($r): void {
                $n = $db->query("UPDATE bookings SET status = 'completed' WHERE id = ? AND status = 'confirmed'", [$r['id']])->rowCount();
                if ($n === 1) {
                    $db->insert('booking_status_history', ['booking_id' => $r['id'], 'from_status' => 'confirmed', 'to_status' => 'completed', 'note' => 'Çıkış tarihi geçti (otomatik)']);
                }
            });
        }
    }
}
