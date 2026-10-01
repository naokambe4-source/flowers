<?php $n = (int) ($stars ?? 0); if ($n > 0): ?>
<span class="stars" role="img" aria-label="<?= $n ?> yıldızlı"><?php for ($i = 0; $i < $n; $i++): ?><?= icon('star') ?><?php endfor; ?></span>
<?php endif; ?>
