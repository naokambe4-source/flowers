<?php
declare(strict_types=1);

/**
 * YALNIZ TEST ORTAMI İÇİN örnek veri. Üretim paketine dahil edilmez.
 * Kullanım: KK_CONFIG=/yol/test-config.php php tests/fixtures/seed_test_data.php
 */
define('APP_ROOT', dirname(__DIR__, 2));
require APP_ROOT . '/vendor/autoload.php';
require __DIR__ . '/fixtures.php';
App\Core\Config::load();
date_default_timezone_set('Europe/Istanbul');
if (App\Core\Config::get('app.env') !== 'test') {
    fwrite(STDERR, "Bu betik yalnız app.env=test iken çalışır.\n");
    exit(1);
}
seed_fixtures(App\Core\App::db());
echo "Test verisi yüklendi.\n";
