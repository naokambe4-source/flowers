<a class="btn btn-ghost btn-sm" href="<?= e(url('/giris')) ?>"><?= icon('chevron-left', 'icon-s') ?> GERİ DÖN</a>
<h1 style="margin-top:12px">Hesap oluştur</h1>
<p class="muted">Bilgilerinizi girin; hesabınız hemen açılır ve kurumunuza özel fiyatlarla otel aramaya başlayabilirsiniz.</p>
<?php if ($domains): ?><div class="alert alert-info"><?= icon('info') ?><div>Kayıt yalnız şu alan adlarındaki e-posta adresleriyle yapılabilir: <strong><?= e(implode(', ', $domains)) ?></strong></div></div><?php endif; ?>
<form method="post" action="<?= e(url('/kayit-ol')) ?>" novalidate>
    <?= csrf_field() ?>
    <div style="position:absolute;left:-9999px" aria-hidden="true"><label>Web sitesi <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
    <div class="form-grid">
        <div class="field"><label for="first_name">Ad</label><input id="first_name" name="first_name" value="<?= e(old('first_name')) ?>" autocomplete="given-name" required<?= aria_error('first_name') ?>><?= field_error('first_name') ?></div>
        <div class="field"><label for="last_name">Soyad</label><input id="last_name" name="last_name" value="<?= e(old('last_name')) ?>" autocomplete="family-name" required<?= aria_error('last_name') ?>><?= field_error('last_name') ?></div>
    </div>
    <div class="field"><label for="email">E-posta</label><input type="email" id="email" name="email" value="<?= e(old('email')) ?>" autocomplete="email" required<?= aria_error('email') ?>><?= field_error('email') ?></div>
    <div class="field"><label for="phone">Telefon</label><input type="tel" id="phone" name="phone" value="<?= e(old('phone')) ?>" autocomplete="tel" placeholder="05xx xxx xx xx" required<?= aria_error('phone') ?>><?= field_error('phone') ?></div>
    <div class="field">
        <label for="institution_id">Kurum<?= $requireInstitution ? '' : ' <span class="muted">(isteğe bağlı)</span>' ?></label>
        <select id="institution_id" name="institution_id"<?= aria_error('institution_id') ?>>
            <option value=""><?= $requireInstitution ? 'Kurumunuzu seçin' : 'Kurum seçmeden devam et' ?></option>
            <?php foreach ($institutions as $i): ?><option value="<?= (int) $i['id'] ?>"<?= (string) old('institution_id') === (string) $i['id'] ? ' selected' : '' ?>><?= e($i['name']) ?></option><?php endforeach; ?>
        </select>
        <?= field_error('institution_id') ?>
    </div>
    <div class="field"><label for="department">Birim / departman <span class="muted">(isteğe bağlı)</span></label><input id="department" name="department" value="<?= e(old('department')) ?>"></div>
    <div class="form-grid">
        <div class="field"><label for="password">Parola</label><input type="password" id="password" name="password" autocomplete="new-password" required<?= aria_error('password') ?>><p class="hint">En az 10 karakter; harf ve rakam içermeli.</p><?= field_error('password') ?></div>
        <div class="field"><label for="password_confirmation">Parola tekrarı</label><input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required<?= aria_error('password_confirmation') ?>><?= field_error('password_confirmation') ?></div>
    </div>
    <label class="check"><input type="checkbox" name="kvkk" value="1"<?= old('kvkk') ? ' checked' : '' ?><?= aria_error('kvkk') ?>><span><a href="<?= e(url('/kvkk')) ?>" target="_blank">KVKK Aydınlatma Metni</a>’ni okudum; üyelik için verilerimin işlenmesini kabul ediyorum.</span></label>
    <?= field_error('kvkk') ?>
    <button type="submit" class="btn btn-lg btn-block" style="margin-top:12px">HESABIMI OLUŞTUR</button>
</form>
<p class="muted small" style="margin-top:14px">Zaten hesabınız var mı? <a href="<?= e(url('/giris')) ?>">Giriş yapın</a></p>
