<div class="verify-card card"><div class="card-body stack">
    <h1 style="font-size:1.5rem">Voucher doğrulama</h1>
    <?php if (!$b): ?>
        <div class="alert alert-error"><?= icon('alert') ?><div>Bu doğrulama kodu geçersiz.</div></div>
    <?php else: $valid = in_array($b['status'], ['confirmed', 'completed'], true); ?>
        <div class="alert <?= $valid ? 'alert-success' : 'alert-error' ?>"><?= icon($valid ? 'check-circle' : 'alert') ?><div><strong><?= $valid ? 'Geçerli rezervasyon' : 'Bu rezervasyon geçerli değil' ?></strong> — <?= e(status_label($b['status'])) ?></div></div>
        <dl class="kv">
            <dt>Rezervasyon kodu</dt><dd><?= e($b['code']) ?></dd>
            <dt>Giriş</dt><dd><?= e(tr_date($b['check_in'])) ?></dd>
            <dt>Çıkış</dt><dd><?= e(tr_date($b['check_out'])) ?> (<?= (int) $b['nights'] ?> gece)</dd>
            <dt>Oda / konuk</dt><dd><?= e(guest_summary((int) $b['adults'], (int) $b['children'], (int) $b['rooms_count'])) ?></dd>
            <dt>Misafir</dt><dd><?= e($masked) ?></dd>
        </dl>
        <p class="muted small">Ayrıntılı bilgiler yalnız rezervasyon sahibine ve yetkili personele gösterilir.</p>
    <?php endif; ?>
</div></div>
