<div class="container">
    <div class="page-head"><h1>Destek</h1><p>Rezervasyon, teklif veya hesabınızla ilgili yardım alın.</p></div>
    <div class="grid-2" style="margin-top:16px">
        <div class="card"><div class="card-body stack">
            <h2 style="font-size:1.15rem">Bize ulaşın</h2>
            <?= App\Core\View::partial('partials/support_contacts') ?>
            <h2 style="font-size:1.15rem">Destek talebi oluştur</h2>
            <form method="post" action="<?= e(url('/destek')) ?>" novalidate><?= csrf_field() ?>
                <div class="field"><label for="subject">Konu</label><input id="subject" name="subject" value="<?= e(old('subject')) ?>" required<?= aria_error('subject') ?>><?= field_error('subject') ?></div>
                <?php if ($bookings): ?><div class="field"><label for="booking_code">İlgili rezervasyon <span class="muted">(isteğe bağlı)</span></label><select id="booking_code" name="booking_code"><option value="">—</option><?php foreach ($bookings as $b): ?><option><?= e($b['code']) ?></option><?php endforeach; ?></select></div><?php endif; ?>
                <div class="field"><label for="message">Mesajınız</label><textarea id="message" name="message" rows="5" required<?= aria_error('message') ?>><?= e(old('message')) ?></textarea><?= field_error('message') ?></div>
                <button class="btn" type="submit">TALEBİ GÖNDER</button>
            </form>
        </div></div>
        <div class="card"><div class="card-body">
            <h2 style="font-size:1.15rem">Taleplerim</h2>
            <?php if (!$rows): ?><p class="muted">Henüz destek talebiniz yok.</p><?php endif; ?>
            <?php foreach ($rows as $r): ?>
                <div class="panel" style="margin-bottom:10px"><div class="row-between"><strong><?= e($r['subject']) ?></strong><?= App\Core\View::partial('partials/status', ['status' => $r['status']]) ?></div>
                    <p class="small muted" style="margin:4px 0"><?= e(tr_datetime($r['created_at'])) ?><?= $r['booking_code'] ? ' · ' . e($r['booking_code']) : '' ?></p>
                    <p class="small" style="margin:0"><?= nl2br(e($r['message'])) ?></p>
                    <?php if ($r['reply']): ?><div class="alert alert-info" style="margin:10px 0 0"><?= icon('support') ?><div><strong>Yanıt:</strong> <?= nl2br(e($r['reply'])) ?></div></div><?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div></div>
    </div>
</div>
