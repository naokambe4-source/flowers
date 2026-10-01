<!doctype html>
<html lang="tr">
<head><?= App\Core\View::partial('partials/head', get_defined_vars()) ?></head>
<body>
<a class="skip-link" href="#icerik">İçeriğe geç</a>
<?php if (!empty($split)): $img = site_image('login.image', 'login'); ?>
<div class="guest-shell">
    <div class="guest-main">
        <div class="guest-top">
            <?= App\Core\View::partial('partials/brand') ?>
            <a class="btn btn-ghost btn-sm" href="<?= e(url('/iletisim')) ?>"><?= icon('phone', 'icon-s') ?> İletişim</a>
        </div>
        <main id="icerik" class="guest-content">
            <div class="guest-mobile-visual"><img src="<?= e($img['url']) ?>" alt="" loading="eager"></div>
            <?= App\Core\View::partial('partials/flash') ?>
            <?= $content ?>
        </main>
        <nav class="guest-foot" aria-label="Yasal bağlantılar">
            <a href="<?= e(url('/kvkk')) ?>">KVKK</a><a href="<?= e(url('/gizlilik')) ?>">Gizlilik</a>
            <a href="<?= e(url('/kullanim-kosullari')) ?>">Kullanım Koşulları</a><a href="<?= e(url('/cerez-politikasi')) ?>">Çerezler</a>
            <a href="<?= e(url('/iletisim')) ?>">İletişim</a>
        </nav>
    </div>
    <aside class="guest-visual" aria-hidden="true">
        <img src="<?= e($img['url']) ?>" alt="">
        <div class="guest-visual-copy">
            <span class="eyebrow" style="color:#e9cf8f">Antalya ve ilçeleri</span>
            <h2><?= e(setting('login.title', 'Kurumunuza özel Antalya konaklaması')) ?></h2>
            <p><?= e(setting('login.text')) ?></p>
            <ul class="trust-list"><li><?= icon('shield', 'icon-s') ?> Yalnız yetkili kurum üyeleri</li><li><?= icon('check', 'icon-s') ?> Doğrulanmış fiyat bilgisi</li><li><?= icon('support', 'icon-s') ?> Rezervasyon desteği</li></ul>
        </div>
        <?php if ($img['representative']): ?><span class="media-note">Temsili illüstrasyon</span><?php endif; ?>
    </aside>
</div>
<?php else: ?>
<header class="site-header"><div class="container"><?= App\Core\View::partial('partials/brand') ?>
    <div class="header-actions"><?php if (auth_user()): ?><a class="btn btn-secondary btn-sm" href="<?= e(url('/panel')) ?>">Panele dön</a><?php else: ?><a class="btn btn-sm" href="<?= e(url('/giris')) ?>">GİRİŞ YAP</a><?php endif; ?></div>
</div></header>
<main id="icerik" class="container" style="padding-top:12px">
    <?= App\Core\View::partial('partials/flash') ?>
    <?= $content ?>
</main>
<footer class="site-footer"><div class="container"><div class="footer-bottom" style="margin-top:0;border:0;padding-top:0">
    <span>© <?= date('Y') ?> <?= e(setting('site.name', 'Kurumsal Konaklama')) ?></span>
    <span><a href="<?= e(url('/kvkk')) ?>">KVKK</a> · <a href="<?= e(url('/gizlilik')) ?>">Gizlilik</a> · <a href="<?= e(url('/kullanim-kosullari')) ?>">Kullanım Koşulları</a> · <a href="<?= e(url('/cerez-politikasi')) ?>">Çerezler</a></span>
</div></div></footer>
<?php endif; ?>
<div id="live-region" class="sr-only" aria-live="polite"></div>
<script src="<?= e(asset_url('js/app.js')) ?>" defer></script>
</body>
</html>
