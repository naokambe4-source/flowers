<div class="admin-title"><div><h1>Bildirimler</h1><p>Sistem içi bildirimler her zaman oluşturulur; e-posta yalnız SMTP yapılandırıldığında cron ile gönderilir.</p></div><form method="post" action="<?= e(url('/yonetim/bildirimler/test-eposta')) ?>"><?= csrf_field() ?><button class="btn btn-secondary"<?= $mailOk ? '' : ' disabled' ?>>Test e-postası gönder</button></form></div>
<div class="tiles" style="margin-bottom:16px">
    <div class="tile"><span class="tile-label">E-posta</span><span class="tile-value" style="font-size:1.1rem"><?= $mailOk ? 'Yapılandırıldı' : 'Yapılandırılmadı' ?></span><a class="tile-sub" href="<?= e(url('/yonetim/ayarlar')) ?>">SMTP ayarları</a></div>
    <div class="tile"><span class="tile-label">Son cron</span><span class="tile-value" style="font-size:1.1rem"><?= e($lastCron ? tr_datetime($lastCron) : 'Hiç çalışmadı') ?></span></div>
    <?php foreach ($stats as $s): ?><div class="tile"><span class="tile-label">İş: <?= e(['queued' => 'Kuyrukta', 'running' => 'Çalışıyor', 'done' => 'Tamamlandı', 'failed' => 'Başarısız'][$s['status']]) ?></span><span class="tile-value"><?= (int) $s['c'] ?></span></div><?php endforeach; ?>
</div>
<?php if ($jobs): ?><div class="card" style="margin-bottom:20px"><div class="card-head"><h2>Bekleyen ve başarısız işler</h2></div><div class="table-wrap" style="border:0"><table class="table responsive"><thead><tr><th>#</th><th>Tür</th><th>Durum</th><th>Deneme</th><th>Hata</th><th></th></tr></thead><tbody>
<?php foreach ($jobs as $j): ?><tr><td data-label="#"><?= (int) $j['id'] ?></td><td data-label="Tür"><?= e($j['type']) ?></td><td data-label="Durum"><span class="badge badge-<?= $j['status'] === 'failed' ? 'danger' : 'warning' ?>"><?= e($j['status']) ?></span></td><td data-label="Deneme"><?= (int) $j['attempts'] ?>/<?= (int) $j['max_attempts'] ?></td><td data-label="Hata" class="small"><?= e($j['last_error']) ?></td><td class="actions"><?php if ($j['status'] === 'failed'): ?><form method="post" action="<?= e(url('/yonetim/bildirimler/is/' . $j['id'] . '/tekrar')) ?>"><?= csrf_field() ?><button class="btn btn-secondary btn-sm">Tekrar dene</button></form><?php endif; ?></td></tr><?php endforeach; ?>
</tbody></table></div></div><?php endif; ?>
<h2 style="font-size:1.2rem">Bildirim şablonları</h2>
<p class="muted small">Kullanılabilir değişkenler: {ad}, {site}, {kod}, {otel}, {giris}, {cikis}, {tutar}, {gecerlilik}, {baglanti}, {sure}, {not}, {baslik}</p>
<div class="grid-2">
<?php foreach ($templates as $t): ?>
<form method="post" action="<?= e(url('/yonetim/bildirimler/sablon')) ?>" class="card"><div class="card-head"><h2><?= e($t['name']) ?></h2><span class="code"><?= e($t['key']) ?></span></div><div class="card-body"><?= csrf_field() ?><input type="hidden" name="key" value="<?= e($t['key']) ?>">
    <div class="field"><label for="s_<?= e($t['key']) ?>">Konu</label><input id="s_<?= e($t['key']) ?>" name="subject" value="<?= e($t['subject']) ?>"></div>
    <div class="field"><label for="b_<?= e($t['key']) ?>">Metin</label><textarea id="b_<?= e($t['key']) ?>" name="body" rows="5"><?= e($t['body']) ?></textarea></div>
    <input type="hidden" name="send_email" value="0"><label class="check"><input type="checkbox" name="send_email" value="1"<?= $t['send_email'] ? ' checked' : '' ?>> E-posta da gönder</label>
    <button class="btn btn-secondary btn-sm">Kaydet</button>
</div></form>
<?php endforeach; ?>
</div>
