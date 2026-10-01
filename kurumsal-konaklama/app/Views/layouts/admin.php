<?php
$u = auth_user();
$db = App\Core\App::db();
$counts = [
    'requests' => can('requests.manage') ? (int) $db->value("SELECT COUNT(*) FROM accommodation_requests WHERE status = 'new'") : 0,
    'bookings' => can('bookings.view') ? (int) $db->value("SELECT COUNT(*) FROM bookings WHERE status IN ('requested','pending')") : 0,
    'applications' => can('applications.manage') ? (int) $db->value("SELECT COUNT(*) FROM membership_requests WHERE status = 'pending'") : 0,
    'support' => can('support.manage') ? (int) $db->value("SELECT COUNT(*) FROM support_requests WHERE status = 'open'") : 0,
];
$menu = [
    'Genel' => [
        ['/yonetim', 'Dashboard', 'chart', 'dashboard.view', null, true],
        ['/yonetim/rezervasyonlar', 'Rezervasyonlar', 'calendar', 'bookings.view', 'bookings'],
        ['/yonetim/talepler', 'Konaklama Talepleri', 'document', 'requests.manage', 'requests'],
        ['/yonetim/destek', 'Destek Talepleri', 'support', 'support.manage', 'support'],
    ],
    'Oteller' => [
        ['/yonetim/oteller', 'Oteller', 'building', 'hotels.view'],
        ['/yonetim/odalar', 'Odalar', 'bed', 'hotels.view'],
        ['/yonetim/ozellikler', 'Özellikler', 'star', 'catalog.manage'],
        ['/yonetim/bolgeler', 'Bölgeler', 'pin', 'catalog.manage'],
        ['/yonetim/konseptler', 'Konseptler', 'restaurant', 'catalog.manage'],
    ],
    'Fiyat ve Kontenjan' => [
        ['/yonetim/fiyatlar', 'Fiyatlar', 'tag', 'pricing.view'],
        ['/yonetim/fiyat-kurallari', 'Fiyat Kuralları', 'percent', 'pricing.view'],
        ['/yonetim/kampanyalar', 'Kampanyalar', 'sparkle', 'pricing.view'],
        ['/yonetim/promosyonlar', 'Promosyonlar', 'key', 'pricing.view'],
        ['/yonetim/sezonlar', 'Sezonlar', 'sun', 'pricing.manage'],
        ['/yonetim/kontenjan', 'Kontenjan', 'layers', 'inventory.manage'],
    ],
    'Kurum ve Üyeler' => [
        ['/yonetim/kurumlar', 'Kurumlar', 'briefcase', 'institutions.view'],
        ['/yonetim/kurum-tipleri', 'Kurum Tipleri', 'list', 'institutions.manage'],
        ['/yonetim/fiyat-gruplari', 'Fiyat Grupları', 'percent', 'institutions.manage'],
        ['/yonetim/uyeler', 'Üyeler', 'users', 'users.view'],
        ['/yonetim/basvurular', 'Başvurular', 'user', 'applications.manage', 'applications'],
    ],
    'Sistem' => [
        ['/yonetim/api', 'API Yönetimi', 'api', 'providers.manage'],
        ['/yonetim/bildirimler', 'Bildirimler', 'bell', 'notifications.manage'],
        ['/yonetim/raporlar', 'Raporlar', 'chart', 'reports.view'],
        ['/yonetim/icerik', 'İçerik Yönetimi', 'document', 'content.manage'],
        ['/yonetim/ayarlar', 'Sistem Ayarları', 'settings', 'settings.manage'],
        ['/yonetim/demo-mod', 'Demo / Canlı Mod', 'sparkle', 'settings.manage'],
        ['/yonetim/guncelleme', 'Sistem Güncellemesi', 'download', 'settings.manage'],
        ['/yonetim/yetkiler', 'Yetkiler', 'shield', 'roles.manage'],
        ['/yonetim/audit-log', 'Audit Log', 'history', 'audit.view'],
    ],
];
?>
<!doctype html>
<html lang="tr">
<head><?= App\Core\View::partial('partials/head', get_defined_vars()) ?></head>
<body>
<a class="skip-link" href="#icerik">İçeriğe geç</a>
<div class="admin-shell">
    <aside class="admin-sidebar" id="admin-sidebar" aria-label="Yönetim menüsü">
        <?= App\Core\View::partial('partials/brand') ?>
        <?php foreach ($menu as $group => $items): $visible = array_filter($items, static fn ($i) => can($i[3])); if (!$visible) continue; ?>
            <nav class="admin-nav-group" aria-label="<?= e($group) ?>"><h2><?= e($group) ?></h2>
                <?php foreach ($visible as $i): ?>
                    <a href="<?= e(url($i[0])) ?>"<?= aria_current($i[0], !empty($i[5])) ?>><?= icon($i[2]) ?> <?= e($i[1]) ?><?php if (!empty($i[4]) && $counts[$i[4]] > 0): ?><span class="pill" aria-label="<?= $counts[$i[4]] ?> bekleyen"><?= $counts[$i[4]] ?></span><?php endif; ?></a>
                <?php endforeach; ?>
            </nav>
        <?php endforeach; ?>
        <nav class="admin-nav-group" aria-label="Diğer"><h2>Platform</h2><a href="<?= e(url('/panel')) ?>"><?= icon('home') ?> Üye ana sayfası</a></nav>
    </aside>
    <div class="admin-backdrop"></div>
    <div class="admin-main">
        <header class="admin-top">
            <button type="button" class="icon-btn sidebar-toggle" data-sidebar-toggle aria-controls="admin-sidebar" aria-expanded="false" aria-label="Menüyü aç"><?= icon('menu') ?></button>
            <form class="admin-search" method="get" action="<?= e(url('/yonetim/ara')) ?>" role="search">
                <label for="global-q" class="sr-only">Genel arama</label>
                <?= icon('search') ?><input id="global-q" type="search" name="q" value="<?= e((string) (App\Core\App::request()?->query['q'] ?? '')) ?>" placeholder="Rezervasyon kodu, üye, kurum, otel, telefon, e-posta">
            </form>
            <div class="header-actions">
                <div class="user-menu">
                    <button type="button" class="user-trigger" data-dropdown aria-controls="admin-user" aria-expanded="false" aria-label="Hesap menüsü"><span class="avatar" aria-hidden="true"><?= e(initials($u['first_name'], $u['last_name'])) ?></span><span class="name"><?= e($u['first_name']) ?></span><?= icon('chevron-down', 'icon-s') ?></button>
                    <div class="dropdown" id="admin-user">
                        <div class="dropdown-head"><strong><?= e($u['first_name'] . ' ' . $u['last_name']) ?></strong><span><?= e($u['role_name']) ?></span></div>
                        <a href="<?= e(url('/profilim')) ?>"><?= icon('user') ?> Profilim</a>
                        <a href="<?= e(url('/sifre-degistir')) ?>"><?= icon('key') ?> Şifre Değiştir</a>
                        <form method="post" action="<?= e(url('/cikis')) ?>"><?= csrf_field() ?><button type="submit" class="menu-item"><?= icon('logout') ?> Çıkış Yap</button></form>
                    </div>
                </div>
            </div>
        </header>
        <?php if (setting('demo.active') === '1'): ?><div class="demo-banner" role="note"><div><?= icon('info', 'icon-s') ?> <strong>DEMO MODU</strong><span class="demo-more"> açık — demo oteller üyelere görünür.</span> <a href="<?= e(url('/yonetim/demo-mod')) ?>">Demo / Canlı mod</a></div></div><?php endif; ?>
        <main id="icerik" class="admin-content">
            <?= App\Core\View::partial('partials/flash') ?>
            <?= $content ?>
        </main>
    </div>
</div>
<div id="live-region" class="sr-only" aria-live="polite"></div>
<?php if (!empty($withMap)): ?><script src="<?= e(asset_url('vendor/leaflet/leaflet.js')) ?>" defer></script><?php endif; ?>
<script src="<?= e(asset_url('js/app.js')) ?>" defer></script>
</body>
</html>
