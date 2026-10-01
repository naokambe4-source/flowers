<a class="btn btn-ghost btn-sm" href="<?= e(url('/giris')) ?>"><?= icon('chevron-left', 'icon-s') ?> GERİ DÖN</a>
<h1 style="margin-top:12px">Şifremi unuttum</h1>
<p class="muted">Hesabınıza kayıtlı e-posta adresini yazın. Hesabınız aktifse parola sıfırlama bağlantısı gönderilecektir.</p>
<form method="post" action="<?= e(url('/sifremi-unuttum')) ?>" novalidate>
    <?= csrf_field() ?>
    <div class="field">
        <label for="email">E-posta adresi</label>
        <input type="email" id="email" name="email" value="<?= e(old('email')) ?>" autocomplete="email" required<?= aria_error('email') ?>>
        <?= field_error('email') ?>
    </div>
    <button type="submit" class="btn btn-lg btn-block">BAĞLANTI GÖNDER</button>
</form>
