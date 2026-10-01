<span class="eyebrow">Kurumsal giriş</span>
<h1>Hoş geldiniz</h1>
<p class="muted">Anlaşmalı otelleri ve kurumunuza özel fiyatları görmek için hesabınızla giriş yapın.</p>
<form method="post" action="<?= e(url('/giris')) ?>" novalidate>
    <?= csrf_field() ?>
    <div class="field">
        <label for="email">E-posta adresi</label>
        <input type="email" id="email" name="email" value="<?= e(old('email')) ?>" autocomplete="username" required<?= aria_error('email') ?>>
        <?= field_error('email') ?>
    </div>
    <div class="field">
        <div class="row-between"><label for="password">Parola</label><a class="small" href="<?= e(url('/sifremi-unuttum')) ?>">Şifremi unuttum</a></div>
        <input type="password" id="password" name="password" autocomplete="current-password" required<?= aria_error('password') ?>>
        <?= field_error('password') ?>
    </div>
    <button type="submit" class="btn btn-lg btn-block">GİRİŞ YAP</button>
</form>
<?php $regMode = App\Services\MembershipService::registrationMode(); ?>
<?php if ($regMode === 'open'): ?>
<div class="divider-text">Henüz hesabınız yok mu?</div>
<a class="btn btn-secondary btn-block" href="<?= e(url('/kayit-ol')) ?>"><?= icon('user') ?> HESAP OLUŞTUR</a>
<?php elseif ($regMode === 'application'): ?>
<div class="divider-text">Henüz hesabınız yok mu?</div>
<a class="btn btn-secondary btn-block" href="<?= e(url('/erisim-talebi')) ?>"><?= icon('user') ?> ERİŞİM TALEBİ OLUŞTUR</a>
<?php else: ?>
<div class="divider-text">Hesabınız yok mu?</div>
<p class="muted small" style="text-align:center">Hesaplar kurum yöneticiniz veya platform yönetimi tarafından açılır.</p>
<?php endif; ?>
<p class="muted small" style="margin-top:16px">Bu platform yalnızca yetkilendirilmiş kurum üyelerine açıktır. Otel bilgileri ve fiyatlar giriş yapmadan görüntülenemez.</p>
