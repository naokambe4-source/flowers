<div class="admin-title"><div><h1>Sistem ayarları</h1><p>Varsayılan üye indirimi dahil tüm iş kuralları buradan yönetilir; kod içine sabitlenmez.</p></div></div>
<form method="post" action="<?= e(url('/yonetim/ayarlar')) ?>" autocomplete="off"><?= csrf_field() ?>
<div class="grid-2">
<?php foreach (App\Controllers\Admin\SettingsController::FIELDS as $group => $fields): ?>
    <div class="card"<?= $group === 'Üyelik' ? ' id="uyelik"' : '' ?>><div class="card-head"><h2><?= e($group) ?></h2></div><div class="card-body">
    <?php foreach ($fields as $k => [$label, $type, $extra]): $f = str_replace('.', '__', $k); $v = $values[$k]; ?>
        <?php if ($type === 'bool'): ?><input type="hidden" name="<?= $f ?>" value="0"><label class="check"><input type="checkbox" name="<?= $f ?>" value="1"<?= $v === '1' ? ' checked' : '' ?>> <?= e($label) ?></label>
        <?php elseif ($type === 'select'): ?><?= f_select($f, $label, $extra, $v) ?>
        <?php elseif ($type === 'secret'): ?><div class="field"><label for="f_<?= $f ?>"><?= e($label) ?></label><input type="password" id="f_<?= $f ?>" name="<?= $f ?>" value="" autocomplete="new-password" placeholder="<?= $v === '__set__' ? 'Kayıtlı (gösterilmez)' : 'Girilmedi' ?>"><p class="hint"><?= e($extra) ?></p></div>
        <?php else: ?><?= f_input($f, $label, $type === 'percent' ? bp_input($v !== '' ? (int) $v : null) : $v, $type === 'int' ? 'number' : 'text', $type === 'int' ? ['min' => 0] : [], is_string($extra) ? $extra : '') ?><?php endif; ?>
    <?php endforeach; ?>
    </div></div>
<?php endforeach; ?>
<div class="card"><div class="card-head"><h2>Zamanlanmış görevler (cron)</h2></div><div class="card-body small">
    <p>cPanel → Cron Jobs bölümüne 5 dakikada bir çalışacak şu komutu ekleyin:</p>
    <p class="code">*/5 * * * * <?= e($phpBinary) ?> <?= e(APP_ROOT) ?>/bin/cron.php &gt;/dev/null 2&gt;&amp;1</p>
    <?php if ($cronUrl): ?><p>CLI kullanılamıyorsa (yalnız HTTPS ile):</p><p class="code">*/5 * * * * curl -s "<?= e($cronUrl) ?>" &gt;/dev/null</p><p class="muted">Bu adres gizli bir anahtar içerir; paylaşmayın.</p><?php endif; ?>
</div></div>
</div>
<div class="wizard-actions"><span></span><button class="btn btn-lg" type="submit">AYARLARI KAYDET</button></div>
</form>
