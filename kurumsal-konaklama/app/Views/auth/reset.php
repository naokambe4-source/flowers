<?php if (!$valid): ?>
<h1>Bağlantı geçersiz</h1>
<div class="alert alert-warning"><?= icon('alert') ?><div>Bu bağlantının süresi dolmuş veya daha önce kullanılmış olabilir.</div></div>
<a class="btn btn-block" href="<?= e(url('/sifremi-unuttum')) ?>">YENİ BAĞLANTI İSTE</a>
<?php else: ?>
<h1><?= $purpose === 'invite' ? 'Parolanızı oluşturun' : 'Yeni parola belirleyin' ?></h1>
<p class="muted">Parolanız en az 10 karakter olmalı; harf ve rakam içermelidir.</p>
<form method="post" action="<?= e(url('/sifre-olustur/' . $token)) ?>" novalidate>
    <?= csrf_field() ?>
    <div class="field">
        <label for="password">Yeni parola</label>
        <input type="password" id="password" name="password" autocomplete="new-password" required minlength="10"<?= aria_error('password') ?>>
        <?= field_error('password') ?>
    </div>
    <div class="field">
        <label for="password_confirmation">Yeni parola (tekrar)</label>
        <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required<?= aria_error('password_confirmation') ?>>
        <?= field_error('password_confirmation') ?>
    </div>
    <button type="submit" class="btn btn-lg btn-block">PAROLAYI KAYDET</button>
</form>
<?php endif; ?>
