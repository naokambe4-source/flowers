<div class="legal-page">
    <h1><?= e($page['title']) ?></h1>
    <?php if (trim((string) $page['body']) === ''): ?>
        <div class="notice-box">Bu metin henüz yönetim tarafından yayımlanmadı. Sorularınız için <a href="<?= e(url('/iletisim')) ?>">iletişim</a> sayfasını kullanabilirsiniz.</div>
    <?php else: ?>
        <div class="prose"><?= nl2p($page['body']) ?></div>
        <p class="muted small">Son güncelleme: <?= e(tr_date($page['updated_at'])) ?></p>
    <?php endif; ?>
</div>
