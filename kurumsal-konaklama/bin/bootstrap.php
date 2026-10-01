<?php
declare(strict_types=1);

// CLI betikleri için ortak başlangıç
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}
define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/vendor/autoload.php';

if (!App\Core\Config::load()) {
    fwrite(STDERR, "config/config.php bulunamadı. Önce web kurulum sihirbazını çalıştırın.\n");
    exit(1);
}
date_default_timezone_set((string) App\Core\Config::get('app.timezone', 'Europe/Istanbul'));
mb_internal_encoding('UTF-8');
App\Core\App::detectInstalled();
App\Core\Session::useArrayStorage(); // CLI: tarayıcı oturumu yok
