<?php
declare(strict_types=1);

/** Eşzamanlılık testi için ayrı süreç: verilen taslağı onaylamaya çalışır. */
define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/vendor/autoload.php';
require __DIR__ . '/TestCase.php';

use App\Core\App;
use App\Core\Database;
use App\Services\BookingService;

TestEnv::boot();
$db = new Database(TestEnv::config()['db']);
App::setDb($db);
$id = (int) ($argv[1] ?? 0);
$b = $db->fetch('SELECT * FROM bookings WHERE id = ?', [$id]);
$u = $db->fetch('SELECT * FROM users WHERE id = ?', [$b['user_id']]);
usleep(random_int(0, 20000));
try {
    $r = (new BookingService($db))->confirm($b, $u, (string) $b['quote_hash']);
    echo $r['status'] === 'confirmed' ? 'OK' : 'STATUS:' . $r['status'];
} catch (\App\Exceptions\DomainException $e) {
    echo 'FULL';
} catch (\Throwable $e) {
    echo 'ERR:' . $e->getMessage();
}
