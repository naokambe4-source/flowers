<?php
// ÖRNEK AYAR DOSYASI — gerçek değer içermez.
// Kurulum sihirbazı config/config.php dosyasını otomatik oluşturur.
// Elle kurulum yapıyorsanız bu dosyayı config.php olarak kopyalayıp düzenleyin.
return [
    'app' => [
        'url' => 'https://alanadi.com/oteller',   // Kurulum adresi (sonunda / olmadan)
        'key' => '',                              // php -r "echo 'base64:'.base64_encode(random_bytes(32));"
        'env' => 'production',
        'debug' => false,
        'timezone' => 'Europe/Istanbul',
        'force_https' => true,
        'behind_proxy' => false,                  // Cloudflare vb. arkasında ise true
        'trusted_proxies' => [],
        'cron_token' => '',                       // HTTP cron için en az 32 karakter rastgele değer
    ],
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => '',
        'user' => '',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
    'session' => [
        'name' => 'kk_session',
    ],
];
