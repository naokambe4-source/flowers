<?php
declare(strict_types=1);

// Gerçek otelleri içe aktarır (komut satırı; süre sınırı yoktur):
//   php bin/import-hotels.php osm all              → tüm bölgeler, OpenStreetMap (anahtarsız)
//   php bin/import-hotels.php liteapi lara,kundu   → seçili bölgeler, LiteAPI (yönetimde anahtar kayıtlı olmalı)
//   php bin/import-hotels.php osm all --adet=50    → bölge başına en fazla 50 yeni otel

require __DIR__ . '/bootstrap.php';

use App\Core\App;
use App\Services\HotelImportService;

$source = $argv[1] ?? '';
$which = $argv[2] ?? 'all';
$max = 30;
foreach ($argv as $a) {
    if (preg_match('/^--adet=(\d+)$/', $a, $m)) {
        $max = (int) $m[1];
    }
}
if (!in_array($source, ['osm', 'liteapi'], true)) {
    fwrite(STDERR, "Kullanım: php bin/import-hotels.php osm|liteapi all|bolge-slug[,bolge-slug] [--adet=30]\n");
    exit(1);
}
$db = App::db();
$provider = $db->fetch('SELECT id, is_enabled FROM providers WHERE code = ?', [$source]);
if (!$provider || (int) $provider['is_enabled'] !== 1) {
    fwrite(STDERR, "Sağlayıcı etkin değil. LiteAPI için önce Yönetim → Canlı Otel Verisi sayfasında anahtarı kaydedin.\n");
    exit(1);
}
$regions = $which === 'all'
    ? $db->fetchAll('SELECT id, name FROM regions WHERE is_active = 1 AND latitude IS NOT NULL ORDER BY sort')
    : $db->fetchAll('SELECT id, name FROM regions WHERE slug IN (' . implode(',', array_fill(0, count(explode(',', $which)), '?')) . ')', explode(',', $which));
$svc = new HotelImportService($db);
foreach ($regions as $r) {
    try {
        $res = $svc->importRegion((int) $provider['id'], (int) $r['id'], ['max_new' => $max]);
        printf("%-16s bulunan %3d · yeni %3d · güncellenen %3d · birleştirilen %3d · foto %3d\n", $r['name'], $res['found'], $res['created'], $res['updated'], $res['merged'], $res['images']);
    } catch (\Throwable $e) {
        printf("%-16s HATA: %s\n", $r['name'], $e->getMessage());
    }
    if ($source === 'osm') {
        sleep(2);
    }
}
foreach (array_slice($svc->warnings, 0, 20) as $w) {
    echo "  uyarı: $w\n";
}
