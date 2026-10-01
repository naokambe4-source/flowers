<?php
declare(strict_types=1);

/**
 * Veriyi silmeden bekleyen migration'ları uygular.
 * Kullanım: php bin/migrate.php          (uygula)
 *           php bin/migrate.php --status (durum)
 */
require __DIR__ . '/bootstrap.php';

$migrator = new App\Core\Migrator(App\Core\App::db(), APP_ROOT . '/database/migrations');
if (in_array('--status', $argv, true)) {
    $applied = $migrator->applied();
    foreach ($migrator->files() as $f) {
        echo (in_array(basename($f), $applied, true) ? '[x] ' : '[ ] ') . basename($f) . PHP_EOL;
    }
    exit(0);
}
$done = $migrator->migrate();
echo $done ? 'Uygulandı: ' . implode(', ', $done) . PHP_EOL : "Bekleyen migration yok.\n";
