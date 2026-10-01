<?php $allRequired = !array_filter($checks, static fn ($c) => $c[2] && !$c[1]); ?>
<h1>Kurulum</h1>
<p class="muted">Bu sihirbaz ortamı kontrol eder, veritabanını oluşturur ve ilk yönetici hesabını açar. Kurulum tamamlandığında kilitlenir.</p>
<div class="card" style="margin:16px 0"><div class="card-head"><h2>1. Ortam kontrolleri</h2></div><div class="card-body">
    <ul class="check-list">
        <?php foreach ($checks as [$label, $ok, $required]): ?><li><span class="<?= $ok ? 'ok' : ($required ? 'fail' : 'warn') ?>" aria-hidden="true"><?= icon($ok ? 'check-circle' : 'alert') ?></span><span><?= e($label) ?> — <strong><?= $ok ? 'Uygun' : ($required ? 'Eksik (zorunlu)' : 'Önerilir') ?></strong></span></li><?php endforeach; ?>
        <li data-rewrite-test="<?= e($rewriteUrl) ?>"><span aria-hidden="true"><?= icon('globe') ?></span><span>Temiz adres (mod_rewrite) testi: <strong data-rewrite-status aria-live="polite">kontrol ediliyor… (JavaScript kapalıysa sunucu tarafında kontrol edilir)</strong></span></li>
        <li><span aria-hidden="true"><?= icon('pin') ?></span><span>Algılanan kurulum yolu: <span class="code"><?= e($basePath) ?></span></span></li>
    </ul>
    <?= field_error('checks') ?><?= field_error('rewrite') ?>
    <?php if (!$allRequired): ?><div class="alert alert-error" style="margin-top:12px"><?= icon('alert') ?><div>Zorunlu gereksinimler karşılanmadan kurulum yapılamaz. cPanel → “Select PHP Version” bölümünden PHP 8.3 ve eksik uzantıları etkinleştirin; klasör izinlerini 755/750 yapın.</div></div><?php endif; ?>
</div></div>
<form method="post" action="<?= e($formAction) ?>" class="card" novalidate><div class="card-head"><h2>2. Veritabanı ve yönetici</h2></div><div class="card-body">
    <?= csrf_field() ?><input type="hidden" name="rewrite_nonce" value="">
    <p class="small muted">cPanel → MySQL Databases bölümünden boş bir veritabanı ve kullanıcı oluşturup kullanıcıya “ALL PRIVILEGES” yetkisi verin.</p>
    <div class="form-grid">
        <?= f_input('db_host', 'Veritabanı sunucusu', 'localhost', 'text', ['required' => true]) ?>
        <?= f_input('db_port', 'Port', '3306', 'number', ['required' => true]) ?>
        <?= f_input('db_name', 'Veritabanı adı', '', 'text', ['required' => true, 'autocomplete' => 'off']) ?>
        <?= f_input('db_user', 'Veritabanı kullanıcısı', '', 'text', ['required' => true, 'autocomplete' => 'off']) ?>
        <div class="field"><label for="db_pass">Veritabanı parolası</label><input type="password" id="db_pass" name="db_pass" autocomplete="new-password"></div>
        <?= f_input('app_url', 'Site adresi (kurulum yolu dahil)', $appUrl, 'url', ['required' => true], 'Örn. https://alanadi.com/oteller — e-posta, QR ve voucher bağlantılarında kullanılır.') ?>
        <?= f_input('site_name', 'Site adı', 'Kurumsal Konaklama', 'text', ['required' => true]) ?>
    </div>
    <h3>İlk yönetici (Super Admin)</h3>
    <div class="form-grid">
        <?= f_input('first_name', 'Ad', '', 'text', ['required' => true]) ?>
        <?= f_input('last_name', 'Soyad', '', 'text', ['required' => true]) ?>
        <?= f_input('email', 'E-posta', '', 'email', ['required' => true]) ?>
        <div></div>
        <div class="field"><label for="password">Parola (en az 10 karakter, harf ve rakam)</label><input type="password" id="password" name="password" autocomplete="new-password" required<?= aria_error('password') ?>><?= field_error('password') ?></div>
        <div class="field"><label for="password_confirmation">Parola tekrarı</label><input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required<?= aria_error('password_confirmation') ?>><?= field_error('password_confirmation') ?></div>
    </div>
    <p class="small muted">Demo seçeneği işaretlenmezse kurulum örnek otel, sahte fiyat veya test kullanıcısı oluşturmaz. Bölgeler, konseptler, roller ve varsayılan ayarlar (üye indirimi %10) yüklenir.</p>
    <h2 style="margin-top:20px">Başlangıç seçenekleri</h2>
    <?= f_select('registration_mode', 'Üyelik kaydı', ['application' => 'Erişim talebi — yönetici onayıyla (önerilen)', 'open' => 'Açık kayıt — kullanıcı hesabını hemen açar', 'closed' => 'Kapalı — hesapları yalnız yönetim açar'], 'application', '', 'Sonradan Yönetim → Sistem Ayarları → Üyelik bölümünden değiştirilebilir.') ?>
    <label class="check"><input type="checkbox" name="demo" value="1"<?= old('demo') ? ' checked' : '' ?>><span><strong>Demo otelleri yükle</strong> — sistemi denemek için 14 kurgusal otel, fiyat, kontenjan ve temsili görsel ekler. Canlıya geçerken tek tıkla silinir.</span></label>
    <button class="btn btn-lg" type="submit"<?= $allRequired ? '' : ' disabled' ?> style="margin-top:14px">KURULUMU TAMAMLA</button>
</div></form>
