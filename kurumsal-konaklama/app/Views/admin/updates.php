<div class="admin-title"><div><h1>Sistem güncellemesi</h1><p>Sürüm <?= e($version) ?>. Güncellemeler veritabanını silmeden yalnız eksik migration’ları uygular.</p></div></div>
<div class="alert alert-warning"><?= icon('alert') ?><div>Güncelleme öncesi cPanel → Yedekleme bölümünden veritabanı yedeği alın. Yeni dosyaları yükledikten sonra bu sayfadan güncellemeyi uygulayın.</div></div>
<div class="grid-2">
<div class="card"><div class="card-head"><h2>Bekleyen (<?= count($pending) ?>)</h2></div><div class="card-body"><?php if (!$pending): ?><p class="muted">Sistem güncel.</p><?php else: ?><ul><?php foreach ($pending as $p): ?><li class="code"><?= e($p) ?></li><?php endforeach; ?></ul><form method="post" data-confirm="Yedek aldınız mı? Güncelleme uygulansın mı?"><?= csrf_field() ?><button class="btn">GÜNCELLEMEYİ UYGULA</button></form><?php endif; ?></div></div>
<div class="card"><div class="card-head"><h2>Uygulananlar</h2></div><div class="card-body"><ul><?php foreach ($applied as $a): ?><li class="code"><?= e($a) ?></li><?php endforeach; ?></ul></div></div>
</div>
