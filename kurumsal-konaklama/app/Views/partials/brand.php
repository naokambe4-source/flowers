<?php $logoKey = setting('site.logo'); $siteName = setting('site.name', 'Kurumsal Konaklama'); ?>
<a class="brand" href="<?= e(url(auth_user() ? '/panel' : '/giris')) ?>" aria-label="<?= e($siteName) ?> ana sayfa">
    <?php if ($logoKey !== ''): ?>
        <img src="<?= e(url('/medya/site/site_logo')) ?>" alt="<?= e($siteName) ?>" height="38">
    <?php else: ?>
        <span class="brand-mark"><?= icon('building') ?></span>
        <span class="brand-text"><span><?= e($siteName) ?></span><small>Antalya · Kurumsal</small></span>
    <?php endif; ?>
</a>
