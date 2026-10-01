<div class="admin-title"><div><h1>Erişim başvuruları</h1><p>Akış: Bekliyor → Onaylandı veya Reddedildi. Onaylanmayan kullanıcı giriş yapamaz.</p></div></div>
<?php foreach ($links as $l): ?><div class="alert alert-warning"><?= icon('key') ?><div style="flex:1">Parola oluşturma bağlantısı (bir kez gösterilir):<div class="copy-field" style="margin-top:6px"><input id="apl" readonly value="<?= e($l) ?>"><button class="btn btn-secondary" type="button" data-copy="apl">Kopyala</button></div></div></div><?php endforeach; ?>
<div class="seg" style="margin-bottom:16px"><?php foreach (['pending' => 'Bekleyen', 'approved' => 'Onaylanan', 'rejected' => 'Reddedilen'] as $k => $l): ?><a href="<?= e(url('/yonetim/basvurular', ['durum' => $k])) ?>" aria-current="<?= $st === $k ? 'true' : 'false' ?>"><?= $l ?></a><?php endforeach; ?></div>
<?php if (!$rows): ?><div class="empty"><?= icon('user', 'icon-l') ?><h3>Bu listede başvuru yok</h3></div><?php endif; ?>
<div class="stack">
<?php foreach ($rows as $a): ?>
<div class="card"><div class="card-body">
    <div class="row-between"><div><strong><?= e($a['first_name'] . ' ' . $a['last_name']) ?></strong> · <?= e($a['email']) ?> · <?= e($a['phone']) ?><div class="small muted">Kurum: <?= e($a['institution_name'] ?: ($a['institution_text'] ? $a['institution_text'] . ' (listede yok)' : '—')) ?><?= $a['department'] ? ' · ' . e($a['department']) : '' ?> · <?= e(tr_datetime($a['created_at'])) ?> · KVKK onayı: <?= e(tr_datetime($a['kvkk_accepted_at'])) ?></div><?php if ($a['note']): ?><p class="small" style="margin:6px 0 0"><?= e($a['note']) ?></p><?php endif; ?></div>
    <?php if ($a['status'] !== 'pending'): ?><span class="small muted"><?= e(status_label($a['status'])) ?> · <?= e($a['reviewer']) ?> · <?= e(tr_datetime($a['reviewed_at'])) ?></span><?php endif; ?></div>
    <?php if ($a['status'] === 'pending'): ?>
    <div class="grid-2" style="margin-top:12px">
        <form method="post" action="<?= e(url('/yonetim/basvurular/' . $a['id'])) ?>" class="panel"><?= csrf_field() ?><input type="hidden" name="karar" value="onayla">
            <?= f_select('institution_id', 'Kurum', $institutions, $a['institution_id'] ?? '', 'Seçiniz', '', true) ?>
            <button class="btn btn-sm" type="submit">ONAYLA</button>
        </form>
        <form method="post" action="<?= e(url('/yonetim/basvurular/' . $a['id'])) ?>" class="panel" data-confirm="Başvuru reddedilsin mi?"><?= csrf_field() ?><input type="hidden" name="karar" value="reddet">
            <div class="field"><label for="not<?= (int) $a['id'] ?>">Red açıklaması (kişiye iletilir)</label><input id="not<?= (int) $a['id'] ?>" name="not"></div>
            <button class="btn btn-danger btn-sm" type="submit">REDDET</button>
        </form>
    </div>
    <?php endif; ?>
</div></div>
<?php endforeach; ?>
</div>
<?= pagination_links($page, $query) ?>
