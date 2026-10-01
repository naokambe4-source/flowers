<?php
declare(strict_types=1);

/**
 * Kurumsal Konaklama Platformu — front controller.
 * Tüm istekler buradan geçer; kurulum yolu (/, /oteller/, /kurumsal/konaklama/) otomatik algılanır.
 */

if (PHP_VERSION_ID < 80300) {
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    echo '<h1>PHP 8.3 veya üzeri gerekli</h1><p>Sunucunuzda çalışan sürüm: ' . htmlspecialchars(PHP_VERSION) . '. cPanel &gt; "Select PHP Version" / "MultiPHP Manager" üzerinden PHP 8.3 seçin.</p>';
    exit;
}

define('APP_ROOT', __DIR__);

if (!is_file(APP_ROOT . '/vendor/autoload.php')) {
    http_response_code(500);
    echo 'vendor klasörü eksik. Lütfen teslim paketindeki tüm dosyaları yükleyin.';
    exit;
}

require APP_ROOT . '/vendor/autoload.php';

$app = new App\Core\App(APP_ROOT);
$app->run();
