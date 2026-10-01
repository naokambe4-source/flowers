<div class="container" style="max-width:860px">
    <div class="page-head row-between"><div><h1>Bildirimler</h1></div>
        <?php if ($rows): ?><form method="post" action="<?= e(url('/bildirimler/okundu')) ?>"><?= csrf_field() ?><button class="btn btn-secondary" type="submit">Tümünü okundu işaretle</button></form><?php endif; ?></div>
    <?php if (!$rows): ?><div class="empty" style="margin-top:16px"><?= icon('bell', 'icon-l') ?><h3>Bildiriminiz yok</h3><p>Rezervasyon ve teklif güncellemeleri burada görünür.</p></div>
    <?php else: ?><div class="card" style="margin-top:16px">
        <?php foreach ($rows as $n): ?>
            <form method="post" action="<?= e(url('/bildirimler/okundu')) ?>" style="margin:0"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $n['id'] ?>">
                <button type="submit" class="notif-item<?= $n['read_at'] ? '' : ' unread' ?>" style="width:100%;border:0;border-bottom:1px solid var(--c-line);text-align:left;font:inherit;cursor:pointer">
                    <span class="tile-icon" style="width:40px;height:40px;border-radius:12px;display:grid;place-items:center;background:var(--c-teal-soft);color:var(--c-teal-strong);flex:none"><?= icon($n['type'] === 'offer' ? 'tag' : ($n['type'] === 'booking' ? 'calendar' : 'bell')) ?></span>
                    <span><strong><?= e($n['title']) ?></strong><?php if (!$n['read_at']): ?> <span class="badge badge-teal">Yeni</span><?php endif; ?><br><span class="small muted"><?= e(mb_strimwidth((string) $n['body'], 0, 200, '…')) ?></span><br><time class="small muted"><?= e(tr_datetime($n['created_at'])) ?></time></span>
                </button>
            </form>
        <?php endforeach; ?>
    </div><?php endif; ?>
</div>
