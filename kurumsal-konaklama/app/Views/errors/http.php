<div class="container" style="padding:48px 16px;max-width:680px">
    <div class="empty">
        <?= icon($status === 404 ? 'search' : ($status === 403 ? 'lock' : 'alert'), 'icon-l') ?>
        <h1 style="font-size:1.6rem"><?= $status === 404 ? 'Sayfa bulunamadı' : ($status === 403 ? 'Erişim yetkiniz yok' : ($status === 419 ? 'Oturum süresi doldu' : 'Bir sorun oluştu')) ?></h1>
        <p><?= e($message) ?></p>
        <div class="row" style="justify-content:center">
            <a class="btn" href="<?= e(url(auth_user() ? '/panel' : '/giris')) ?>">ANA SAYFAYA DÖN</a>
            <?php if (auth_user()): ?><a class="btn btn-secondary" href="<?= e(url('/destek')) ?>">Destek</a><?php endif; ?>
        </div>
    </div>
</div>
