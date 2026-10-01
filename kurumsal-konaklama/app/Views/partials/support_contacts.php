<?php $phone = setting('contact.phone'); $wa = setting('contact.whatsapp'); $mail = setting('contact.email'); $hours = setting('contact.hours'); ?>
<?php if ($phone || $wa || $mail): ?>
<div class="row" style="gap:10px">
    <?php if ($phone): ?><a class="btn btn-secondary" href="<?= e(tel_link($phone)) ?>"><?= icon('phone') ?> <?= e($phone) ?></a><?php endif; ?>
    <?php if ($wa): ?><a class="btn" href="<?= e(wa_link($wa)) ?>" target="_blank" rel="noopener"><?= icon('whatsapp') ?> WhatsApp</a><?php endif; ?>
    <?php if ($mail): ?><a class="btn btn-ghost" href="mailto:<?= e($mail) ?>"><?= icon('mail') ?> <?= e($mail) ?></a><?php endif; ?>
</div>
<?php if ($hours): ?><p class="muted small" style="margin-top:8px"><?= icon('clock', 'icon-s') ?> <?= e($hours) ?></p><?php endif; ?>
<?php else: ?>
<p class="muted">Destek iletişim bilgileri henüz yönetim tarafından girilmedi. <a href="<?= e(url('/destek')) ?>">Destek talebi oluşturabilirsiniz.</a></p>
<?php endif; ?>
