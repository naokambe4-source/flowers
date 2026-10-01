<?php
$u = auth_user();
$unread = $u ? App\Services\NotificationService::unreadCount((int) $u['id']) : 0;
$nav = [
    ['/panel', 'Ana Sayfa', 'home'],
    ['/oteller', 'Otel Ara', 'search'],
    ['/rezervasyonlarim', 'Rezervasyonlarım', 'calendar'],
    ['/tekliflerim', 'Tekliflerim', 'document'],
    ['/favorilerim', 'Favorilerim', 'heart'],
];
$iconOpen = e(icon('menu'));
$iconClose = e(icon('close'));
?>
<!doctype html>
<html lang="tr">
<head><?= App\Core\View::partial('partials/head', get_defined_vars()) ?></head>
<body class="<?= !empty($bodyClass) ? e($bodyClass) : '' ?>">
<a class="skip-link" href="#icerik">İçeriğe geç</a>
<header class="site-header">
    <div class="container">
        <?= App\Core\View::partial('partials/brand') ?>
        <nav class="main-nav" aria-label="Ana menü">
            <?php foreach ($nav as [$href, $label]): if ($href !== '/panel' && $href !== '/rezervasyonlarim' && $href !== '/tekliflerim' && !can('member.book')) continue; ?>
                <a href="<?= e(url($href)) ?>"<?= aria_current($href, $href === '/panel') ?>><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="header-actions">
            <a class="icon-btn" href="<?= e(url('/bildirimler')) ?>" aria-label="Bildirimler<?= $unread ? ' (' . $unread . ' okunmamış)' : '' ?>"><?= icon('bell') ?><?php if ($unread): ?><span class="count"><?= $unread > 9 ? '9+' : $unread ?></span><?php endif; ?></a>
            <div class="user-menu">
                <button type="button" class="user-trigger" data-dropdown aria-controls="user-dropdown" aria-expanded="false" aria-label="Hesap menüsü">
                    <span class="avatar" aria-hidden="true"><?= e(initials($u['first_name'], $u['last_name'])) ?></span>
                    <span class="name"><?= e($u['first_name']) ?></span><?= icon('chevron-down', 'icon-s') ?>
                </button>
                <div class="dropdown" id="user-dropdown">
                    <div class="dropdown-head"><strong><?= e($u['first_name'] . ' ' . $u['last_name']) ?></strong><span><?= e($u['institution_name'] ?? $u['role_name']) ?></span></div>
                    <a href="<?= e(url('/profilim')) ?>"><?= icon('user') ?> Profilim</a>
                    <a href="<?= e(url('/sifre-degistir')) ?>"><?= icon('key') ?> Şifre Değiştir</a>
                    <a href="<?= e(url('/bildirimler')) ?>"><?= icon('bell') ?> Bildirimler</a>
                    <a href="<?= e(url('/destek')) ?>"><?= icon('support') ?> Destek</a>
                    <?php if (can('institution.own')): ?><a href="<?= e(url('/kurumum')) ?>"><?= icon('building') ?> Kurumum</a><?php endif; ?>
                    <?php if (can('admin.access')): ?><a href="<?= e(url('/yonetim')) ?>"><?= icon('settings') ?> Yönetim Paneli</a><?php endif; ?>
                    <form method="post" action="<?= e(url('/cikis')) ?>"><?= csrf_field() ?><button type="submit" class="menu-item"><?= icon('logout') ?> Çıkış Yap</button></form>
                </div>
            </div>
            <button type="button" class="icon-btn menu-toggle" data-menu-toggle aria-controls="mobile-panel" aria-expanded="false" aria-label="Menüyü aç" data-icon-open="<?= $iconOpen ?>" data-icon-close="<?= $iconClose ?>"><?= icon('menu') ?></button>
        </div>
    </div>
</header>
<?php if (setting('demo.active') === '1'): ?><div class="demo-banner" role="note"><div><?= icon('info', 'icon-s') ?> <strong>DEMO MODU</strong><span class="demo-more"> — Oteller kurgusal, fiyatlar örnek, görseller temsilidir.</span><?php if (can('settings.manage')): ?> <a href="<?= e(url('/yonetim/demo-mod')) ?>">Canlı moda geç</a><?php endif; ?></div></div><?php endif; ?>
<div class="mobile-panel" id="mobile-panel">
    <div class="mobile-panel-inner">
        <nav aria-label="Mobil menü">
            <?php foreach ($nav as [$href, $label, $ic]): if ($href !== '/panel' && $href !== '/rezervasyonlarim' && $href !== '/tekliflerim' && !can('member.book')) continue; ?>
                <a href="<?= e(url($href)) ?>"<?= aria_current($href, $href === '/panel') ?>><?= icon($ic) ?> <?= e($label) ?></a>
            <?php endforeach; ?>
            <button type="button" class="menu-item submenu-toggle" aria-expanded="false" aria-controls="mobile-account"><span class="row" style="gap:12px"><?= icon('user') ?> Hesabım</span><?= icon('chevron-down') ?></button>
            <div class="submenu" id="mobile-account">
                <a href="<?= e(url('/profilim')) ?>">Profilim</a>
                <a href="<?= e(url('/sifre-degistir')) ?>">Şifre Değiştir</a>
                <a href="<?= e(url('/bildirimler')) ?>">Bildirimler<?= $unread ? ' (' . $unread . ')' : '' ?></a>
                <?php if (can('institution.own')): ?><a href="<?= e(url('/kurumum')) ?>">Kurumum</a><?php endif; ?>
            </div>
            <a href="<?= e(url('/destek')) ?>"<?= aria_current('/destek') ?>><?= icon('support') ?> Destek</a>
            <?php if (can('admin.access')): ?><a href="<?= e(url('/yonetim')) ?>"><?= icon('settings') ?> Yönetim Paneli</a><?php endif; ?>
            <form method="post" action="<?= e(url('/cikis')) ?>"><?= csrf_field() ?><button type="submit" class="menu-item"><?= icon('logout') ?> Çıkış Yap</button></form>
        </nav>
    </div>
</div>
<main id="icerik">
    <?php if (empty($fullBleed)): ?><div class="container" style="padding-top:16px"><?= App\Core\View::partial('partials/flash') ?></div><?php endif; ?>
    <?= $content ?>
</main>
<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <?= App\Core\View::partial('partials/brand') ?>
                <p style="margin-top:12px;max-width:420px"><?= e(setting('site.tagline')) ?></p>
            </div>
            <div>
                <h3>Destek</h3>
                <ul class="footer-links">
                    <?php if ($p = setting('contact.phone')): ?><li><a href="<?= e(tel_link($p)) ?>"><?= icon('phone', 'icon-s') ?> <?= e($p) ?></a></li><?php endif; ?>
                    <?php if ($w = setting('contact.whatsapp')): ?><li><a href="<?= e(wa_link($w)) ?>" target="_blank" rel="noopener"><?= icon('whatsapp', 'icon-s') ?> WhatsApp destek</a></li><?php endif; ?>
                    <?php if ($m = setting('contact.email')): ?><li><a href="mailto:<?= e($m) ?>"><?= icon('mail', 'icon-s') ?> <?= e($m) ?></a></li><?php endif; ?>
                    <li><a href="<?= e(url('/destek')) ?>"><?= icon('support', 'icon-s') ?> Destek talebi oluştur</a></li>
                    <?php if ($h = setting('contact.hours')): ?><li><span><?= icon('clock', 'icon-s') ?> <?= e($h) ?></span></li><?php endif; ?>
                </ul>
            </div>
            <div>
                <h3>Bilgilendirme</h3>
                <ul class="footer-links">
                    <li><a href="<?= e(url('/kvkk')) ?>">KVKK Aydınlatma Metni</a></li>
                    <li><a href="<?= e(url('/gizlilik')) ?>">Gizlilik Politikası</a></li>
                    <li><a href="<?= e(url('/kullanim-kosullari')) ?>">Kullanım Koşulları</a></li>
                    <li><a href="<?= e(url('/cerez-politikasi')) ?>">Çerez Politikası</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <span>© <?= date('Y') ?> <?= e(setting('site.name', 'Kurumsal Konaklama')) ?></span>
            <?php if ($addr = setting('contact.address')): ?><span><?= e($addr) ?></span><?php endif; ?>
        </div>
    </div>
</footer>
<div id="live-region" class="sr-only" aria-live="polite"></div>
<?php if (!empty($withMap)): ?><script src="<?= e(asset_url('vendor/leaflet/leaflet.js')) ?>" defer></script><?php endif; ?>
<script src="<?= e(asset_url('js/app.js')) ?>" defer></script>
</body>
</html>
