<?php
declare(strict_types=1);

/**
 * cPanel cron: her 5 dakikada bir çalıştırın.
 *   */5 * * * * /usr/local/bin/php /home/KULLANICI/public_html/oteller/bin/cron.php >/dev/null 2>&1
 * Periyodik görevleri kuyruğa ekler ve iş kuyruğunu işler.
 */
require __DIR__ . '/bootstrap.php';

$lock = fopen(APP_ROOT . '/storage/tmp/cron.lock', 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
    exit(0); // önceki çalışma sürüyor
}
App\Services\QueueService::schedulePeriodic();
$r = App\Services\QueueService::work(50, 240);
echo date('c') . " tamamlanan: {$r['done']}, başarısız: {$r['failed']}\n";
flock($lock, LOCK_UN);
