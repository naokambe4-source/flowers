<div class="container">
    <div class="page-head row-between"><div><h1><?= e($inst['name']) ?></h1><p><?= e($inst['type_name'] ?: 'Kurum') ?> · Kurum yönetimi</p></div><a class="btn btn-secondary" href="<?= e(url('/kurumum/rezervasyonlar')) ?>"><?= icon('calendar') ?> Kurum rezervasyonları</a></div>
    <div class="tiles" style="margin:16px 0">
        <div class="tile"><span class="tile-label">Üye sayısı</span><span class="tile-value"><?= count($members) ?></span></div>
        <div class="tile"><span class="tile-label">Rezervasyon</span><span class="tile-value"><?= (int) $stats['c'] ?></span></div>
        <div class="tile"><span class="tile-label">Onaylı toplam</span><span class="tile-value"><?= e(money((int) $stats['t'])) ?></span></div>
        <div class="tile"><span class="tile-label">Bekleyen başvuru</span><span class="tile-value"><?= count($applications) ?></span></div>
    </div>
    <?php if ($applications): ?>
    <div class="card" style="margin-bottom:16px"><div class="card-head"><h2>Bekleyen başvurular</h2></div><div class="card-body stack-s">
        <?php foreach ($applications as $a): ?>
            <div class="panel row-between"><div><strong><?= e($a['first_name'] . ' ' . $a['last_name']) ?></strong><div class="small muted"><?= e($a['email']) ?> · <?= e($a['phone']) ?><?= $a['department'] ? ' · ' . e($a['department']) : '' ?></div></div>
                <div class="row">
                    <form method="post" action="<?= e(url('/kurumum/basvurular/' . $a['id'])) ?>"><?= csrf_field() ?><input type="hidden" name="karar" value="onayla"><button class="btn btn-sm" type="submit">ONAYLA</button></form>
                    <form method="post" action="<?= e(url('/kurumum/basvurular/' . $a['id'])) ?>" data-confirm="Başvuru reddedilsin mi?"><?= csrf_field() ?><input type="hidden" name="karar" value="reddet"><button class="btn btn-danger btn-sm" type="submit">REDDET</button></form>
                </div>
            </div>
        <?php endforeach; ?>
    </div></div>
    <?php endif; ?>
    <div class="table-wrap"><table class="table responsive"><thead><tr><th>Ad soyad</th><th>E-posta</th><th>Departman</th><th>Durum</th><th>Son giriş</th></tr></thead><tbody>
        <?php foreach ($members as $m): ?><tr><td data-label="Ad"><?= e($m['first_name'] . ' ' . $m['last_name']) ?></td><td data-label="E-posta"><?= e($m['email']) ?></td><td data-label="Departman"><?= e($m['department'] ?: '—') ?></td><td data-label="Durum"><?= App\Core\View::partial('partials/status', ['status' => $m['status']]) ?></td><td data-label="Son giriş"><?= e(tr_datetime($m['last_login_at'])) ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
</div>
