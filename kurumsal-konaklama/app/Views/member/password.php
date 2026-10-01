<div class="container" style="max-width:560px">
    <div class="page-head"><h1>Şifre değiştir</h1><p>Parola en az 10 karakter olmalı; harf ve rakam içermelidir.</p></div>
    <div class="card" style="margin-top:16px"><div class="card-body">
        <form method="post" action="<?= e(url('/sifre-degistir')) ?>" novalidate><?= csrf_field() ?>
            <div class="field"><label for="current_password">Mevcut parola</label><input type="password" id="current_password" name="current_password" autocomplete="current-password" required<?= aria_error('current_password') ?>><?= field_error('current_password') ?></div>
            <div class="field"><label for="password">Yeni parola</label><input type="password" id="password" name="password" autocomplete="new-password" required<?= aria_error('password') ?>><?= field_error('password') ?></div>
            <div class="field"><label for="password_confirmation">Yeni parola (tekrar)</label><input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required<?= aria_error('password_confirmation') ?>><?= field_error('password_confirmation') ?></div>
            <button class="btn btn-block" type="submit">PAROLAYI DEĞİŞTİR</button>
        </form>
    </div></div>
</div>
