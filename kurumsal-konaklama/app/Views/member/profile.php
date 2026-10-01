<div class="container" style="max-width:860px">
    <div class="page-head"><h1>Profilim</h1><p>Yalnız gerekli kişisel bilgiler tutulur.</p></div>
    <div class="grid-2" style="margin-top:16px">
        <div class="card"><div class="card-body">
            <form method="post" action="<?= e(url('/profilim')) ?>" novalidate><?= csrf_field() ?>
                <div class="form-grid">
                    <div class="field"><label for="first_name">Ad</label><input id="first_name" name="first_name" value="<?= e(old('first_name', $u['first_name'])) ?>" required<?= aria_error('first_name') ?>><?= field_error('first_name') ?></div>
                    <div class="field"><label for="last_name">Soyad</label><input id="last_name" name="last_name" value="<?= e(old('last_name', $u['last_name'])) ?>" required<?= aria_error('last_name') ?>><?= field_error('last_name') ?></div>
                </div>
                <div class="field"><label for="phone">Telefon</label><input type="tel" id="phone" name="phone" value="<?= e(old('phone', $u['phone'])) ?>"<?= aria_error('phone') ?>><?= field_error('phone') ?></div>
                <div class="field"><label for="department">Departman</label><input id="department" name="department" value="<?= e(old('department', $u['department'])) ?>"></div>
                <button class="btn" type="submit">KAYDET</button>
            </form>
        </div></div>
        <div class="card"><div class="card-body">
            <h2 style="font-size:1.1rem">Hesap bilgileri</h2>
            <dl class="kv"><dt>E-posta</dt><dd><?= e($u['email']) ?></dd><dt>Kurum</dt><dd><?= e($u['institution_name'] ?: '—') ?></dd><dt>Rol</dt><dd><?= e($u['role_name']) ?></dd><dt>Üyelik</dt><dd><?= e(status_label($u['status'])) ?></dd><dt>Son giriş</dt><dd><?= e(tr_datetime($u['last_login_at'])) ?></dd></dl>
            <p class="small muted" style="margin-top:12px">E-posta ve kurum değişikliği için kurum yöneticinize veya destek ekibine başvurun.</p>
            <a class="btn btn-secondary" href="<?= e(url('/sifre-degistir')) ?>"><?= icon('key') ?> Şifre değiştir</a>
        </div></div>
    </div>
</div>
