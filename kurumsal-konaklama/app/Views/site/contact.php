<div class="legal-page" style="max-width:960px">
    <h1>İletişim</h1>
    <?php if ($page && trim((string) $page['body']) !== ''): ?><div class="prose"><?= nl2p($page['body']) ?></div><?php endif; ?>
    <div class="grid-2" style="margin-top:16px">
        <div class="card"><div class="card-body stack">
            <h2 style="font-size:1.2rem">Destek kanalları</h2>
            <?= App\Core\View::partial('partials/support_contacts') ?>
            <?php if ($a = setting('contact.address')): ?><p><?= icon('pin', 'icon-s') ?> <?= e($a) ?></p><?php endif; ?>
        </div></div>
        <div class="card"><div class="card-body">
            <h2 style="font-size:1.2rem">Mesaj gönderin</h2>
            <form method="post" action="<?= e(url('/iletisim')) ?>" novalidate>
                <?= csrf_field() ?>
                <div style="position:absolute;left:-9999px" aria-hidden="true"><label>Web sitesi <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
                <div class="field"><label for="name">Ad soyad</label><input id="name" name="name" value="<?= e(old('name', auth_user() ? auth_user()['first_name'] . ' ' . auth_user()['last_name'] : '')) ?>" required<?= aria_error('name') ?>><?= field_error('name') ?></div>
                <div class="form-grid">
                    <div class="field"><label for="email">E-posta</label><input type="email" id="email" name="email" value="<?= e(old('email', auth_user()['email'] ?? '')) ?>" required<?= aria_error('email') ?>><?= field_error('email') ?></div>
                    <div class="field"><label for="phone">Telefon <span class="muted">(isteğe bağlı)</span></label><input type="tel" id="phone" name="phone" value="<?= e(old('phone')) ?>"<?= aria_error('phone') ?>><?= field_error('phone') ?></div>
                </div>
                <div class="field"><label for="subject">Konu</label><input id="subject" name="subject" value="<?= e(old('subject')) ?>" required<?= aria_error('subject') ?>><?= field_error('subject') ?></div>
                <div class="field"><label for="message">Mesajınız</label><textarea id="message" name="message" rows="5" required<?= aria_error('message') ?>><?= e(old('message')) ?></textarea><?= field_error('message') ?></div>
                <label class="check"><input type="checkbox" name="kvkk" value="1"><span><a href="<?= e(url('/kvkk')) ?>" target="_blank">KVKK Aydınlatma Metni</a>’ni okudum, kabul ediyorum.</span></label><?= field_error('kvkk') ?>
                <button class="btn btn-block" type="submit">MESAJI GÖNDER</button>
            </form>
        </div></div>
    </div>
</div>
