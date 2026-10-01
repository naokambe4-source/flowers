<div class="admin-title"><div><h1>Destek talepleri</h1></div></div>
<div class="seg" style="margin-bottom:16px"><?php foreach (['open' => 'Açık', 'answered' => 'Yanıtlandı', 'closed' => 'Kapandı'] as $k => $l): ?><a href="<?= e(url('/yonetim/destek', ['durum' => $k])) ?>" aria-current="<?= $st === $k ? 'true' : 'false' ?>"><?= $l ?></a><?php endforeach; ?></div>
<?php if (!$rows): ?><div class="empty"><?= icon('support', 'icon-l') ?><h3>Bu listede talep yok</h3></div><?php endif; ?>
<div class="stack"><?php foreach ($rows as $s): ?>
<div class="card"><div class="card-body">
    <div class="row-between"><div><strong><?= e($s['subject']) ?></strong><div class="small muted"><?= e($s['name']) ?> · <?= e($s['email']) ?> · <?= e($s['phone']) ?><?= $s['booking_code'] ? ' · ' . e($s['booking_code']) : '' ?> · <?= e(tr_datetime($s['created_at'])) ?><?= $s['user_id'] ? '' : ' · misafir' ?></div></div></div>
    <p style="margin:10px 0"><?= nl2br(e($s['message'])) ?></p>
    <form method="post" action="<?= e(url('/yonetim/destek/' . $s['id'])) ?>"><?= csrf_field() ?>
        <div class="field"><label for="r<?= (int) $s['id'] ?>">Yanıt</label><textarea id="r<?= (int) $s['id'] ?>" name="reply" rows="3"><?= e($s['reply']) ?></textarea></div>
        <div class="row"><select name="status" aria-label="Durum" style="width:auto"><option value="answered">Yanıtlandı</option><option value="closed">Kapat</option><option value="open">Açık bırak</option></select><button class="btn btn-sm">Kaydet</button></div>
    </form>
</div></div>
<?php endforeach; ?></div>
<?= pagination_links($page, $query) ?>
