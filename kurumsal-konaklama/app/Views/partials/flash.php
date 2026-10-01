<?php foreach (flashes() as $f): $t = in_array($f['type'], ['success', 'error', 'warning', 'info'], true) ? $f['type'] : 'info'; ?>
<div class="alert alert-<?= e($t) ?>" role="<?= $t === 'error' ? 'alert' : 'status' ?>">
    <?= icon(['success' => 'check-circle', 'error' => 'alert', 'warning' => 'alert', 'info' => 'info'][$t]) ?>
    <div><?= e($f['message']) ?></div>
</div>
<?php endforeach; ?>
<?php $errs = App\Core\View::shared('_errors', []); if (is_array($errs) && count($errs) > 1): ?>
<div class="alert alert-error" role="alert"><?= icon('alert') ?><div><strong>Lütfen şu alanları kontrol edin:</strong><ul style="margin:6px 0 0;padding-left:18px"><?php foreach ($errs as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul></div></div>
<?php endif; ?>
