<?php use App\Providers\Contracts\Capability; ?>
<div class="admin-title"><div><h1>API yönetimi</h1><p>Sağlayıcı durumu, yetenekler, kota, önbellek ve istek kayıtları.</p></div></div>
<div class="alert alert-info"><?= icon('shield') ?><div>Harici fiyatlar ancak sağlayıcının <strong>ticari gösterim izni</strong> doğrulandıktan sonra üyelere gösterilir ve rezervasyon yetkisi yoksa yalnız <em>onaya bağlı hedef teklif</em> olarak sunulur. Anahtarlar şifreli saklanır, ekranda tekrar gösterilmez. Geri dönüş zinciri: sağlayıcı → geçerli önbellek → yerel anlaşmalı veri → manuel teklif talebi.</div></div>
<p>Canlı sağlayıcı araması: <strong><?= $liveSearch ? 'AÇIK' : 'KAPALI' ?></strong> <span class="muted small">(sağlayıcı detayından değiştirilebilir)</span></p>
<div class="grid-2">
<?php foreach ($rows as $p): ?>
    <div class="card"><div class="card-head"><h2><span class="status-dot <?= e($p['status']) ?>" aria-hidden="true"></span> <?= e($p['name']) ?></h2><span class="badge badge-<?= $p['is_enabled'] ? 'success' : 'neutral' ?>"><?= $p['is_enabled'] ? 'Etkin' : 'Kapalı' ?></span></div><div class="card-body stack-s">
        <dl class="kv"><dt>Durum</dt><dd><?= e(['unknown' => 'Bilinmiyor', 'ok' => 'Çalışıyor', 'degraded' => 'Kısmi sorun', 'error' => 'Hata', 'disabled' => 'Kapalı'][$p['status']]) ?></dd>
        <dt>Son kontrol</dt><dd><?= e(tr_datetime($p['last_check_at'])) ?></dd>
        <?php if ($p['quota_remaining'] !== null): ?><dt>Kalan kota</dt><dd><?= (int) $p['quota_remaining'] ?></dd><?php endif; ?>
        <dt>Son 24 saat</dt><dd><?= (int) $p['calls24'] ?> istek, <?= (int) $p['errors24'] ?> hata</dd>
        <dt>Gösterim izni</dt><dd><?= $p['display_authorized'] ? 'Doğrulandı' : 'Doğrulanmadı' ?></dd>
        <dt>Rezervasyon yetkisi</dt><dd><?= $p['booking_authorized'] ? 'Var' : 'Yok' ?></dd>
        <?php if ($p['last_error']): ?><dt>Son hata</dt><dd style="color:var(--c-danger)"><?= e($p['last_error']) ?></dd><?php endif; ?></dl>
        <div class="row" style="gap:4px"><?php foreach (Capability::LABELS as $k => $l): ?><span class="badge <?= in_array($k, $caps[$p['id']] ?? [], true) ? 'badge-teal' : 'badge-neutral' ?>" title="<?= in_array($k, $caps[$p['id']] ?? [], true) ? 'Destekleniyor' : 'Desteklenmiyor' ?>"><?= in_array($k, $caps[$p['id']] ?? [], true) ? '✓' : '✕' ?> <?= e($l) ?></span><?php endforeach; ?></div>
        <div class="row"><a class="btn btn-secondary btn-sm" href="<?= e(url('/yonetim/api/' . $p['id'])) ?>">Yönet</a><form method="post" action="<?= e(url('/yonetim/api/' . $p['id'] . '/test')) ?>"><?= csrf_field() ?><button class="btn btn-sm" type="submit">Bağlantı testi</button></form></div>
    </div></div>
<?php endforeach; ?>
</div>
<div class="grid-2" style="margin-top:20px">
<div class="card"><div class="card-head"><h2>Önbellek</h2></div><div class="card-body">
    <p class="small muted">İçerik TTL: <?= (int) setting('cache.content_ttl') ?> sn · Fiyat/müsaitlik TTL: <?= (int) setting('cache.rates_ttl') ?> sn (Sistem Ayarları’ndan değiştirilebilir)</p>
    <?php foreach ($cache as $c): ?><p><?= e($c['kind']) ?>: <?= (int) $c['valid'] ?> geçerli / <?= (int) $c['c'] ?> toplam</p><?php endforeach; ?>
    <form method="post" action="<?= e(url('/yonetim/api/onbellek-temizle')) ?>" data-confirm="Önbellek temizlensin mi?"><?= csrf_field() ?><select name="kind" aria-label="Tür" style="width:auto"><option value="">Tümü</option><option value="rates">Fiyat</option><option value="content">İçerik</option><option value="destination">Destinasyon</option></select> <button class="btn btn-secondary btn-sm">Temizle</button></form>
</div></div>
<div class="card"><div class="card-head"><h2>Son istekler</h2></div><div class="table-wrap" style="border:0"><table class="table"><tbody>
<?php foreach ($logs as $l): ?><tr><td class="small"><?= e(tr_datetime($l['created_at'])) ?><br><span class="muted"><?= e($l['provider']) ?> · <?= e($l['operation']) ?></span></td><td class="small"><?= e($l['method']) ?> <?= e($l['endpoint']) ?></td><td><span class="badge badge-<?= $l['success'] ? 'success' : 'danger' ?>"><?= e($l['status_code'] ?? 'ağ') ?></span><div class="small muted"><?= (int) $l['duration_ms'] ?> ms</div></td></tr><?php endforeach; ?>
<?php if (!$logs): ?><tr><td class="muted">Kayıt yok.</td></tr><?php endif; ?></tbody></table></div></div>
</div>
