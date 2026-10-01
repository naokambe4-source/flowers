<?php
declare(strict_types=1);

// Demo içerik yönetimi (komut satırı):
//   php bin/demo.php durum     → demo verilerin durumu
//   php bin/demo.php yukle     → kurgusal demo otelleri, fiyatları ve görselleri yükler
//   php bin/demo.php kaldir    → canlı moda geçiş: tüm demo kayıtlarını ve görsellerini siler

require __DIR__ . '/bootstrap.php';

use App\Core\App;
use App\Services\DemoDataService;

$svc = new DemoDataService(App::db());
$cmd = $argv[1] ?? 'durum';
try {
    switch ($cmd) {
        case 'yukle':
            $t = microtime(true);
            $n = $svc->install();
            printf("%d demo otel yüklendi (%.1f sn).\n", $n, microtime(true) - $t);
            break;
        case 'kaldir':
            $r = $svc->remove();
            printf("Canlı moda geçildi: %d demo otel ve %d deneme rezervasyonu silindi.\n", $r['hotels'], $r['bookings']);
            break;
        default:
            $s = $svc->stats();
            echo ($s['hotels'] ? 'DEMO MODU' : 'CANLI MOD') . sprintf(" — demo otel: %d, oda: %d, görsel: %d, deneme rezervasyonu: %d\n", $s['hotels'], $s['rooms'], $s['images'], $s['bookings']);
    }
} catch (\Throwable $e) {
    fwrite(STDERR, 'Hata: ' . $e->getMessage() . "\n");
    exit(1);
}
