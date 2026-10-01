<div class="admin-title"><div><h1>Dashboard</h1><p><?= e(tr_date(date('Y-m-d'), true)) ?> · Gerçek verilerden özet</p></div></div>
<?php if ($checklist): ?>
<div class="alert alert-warning"><?= icon('alert') ?><div><strong>Yayın öncesi tamamlanması gerekenler</strong><ul style="margin:6px 0 0;padding-left:18px"><?php foreach ($checklist as [$t, $l]): ?><li><a href="<?= e(url($l)) ?>"><?= e($t) ?></a></li><?php endforeach; ?></ul></div></div>
<?php endif; ?>
<div class="tiles">
    <a class="tile" href="<?= e(url('/yonetim/uyeler')) ?>"><span class="tile-icon"><?= icon('users') ?></span><span class="tile-label">Aktif üye</span><span class="tile-value"><?= $stats['members'] ?></span><span class="tile-sub"><?= $stats['applications'] ?> bekleyen başvuru</span></a>
    <a class="tile" href="<?= e(url('/yonetim/kurumlar')) ?>"><span class="tile-icon"><?= icon('briefcase') ?></span><span class="tile-label">Aktif kurum</span><span class="tile-value"><?= $stats['institutions'] ?></span></a>
    <a class="tile" href="<?= e(url('/yonetim/oteller')) ?>"><span class="tile-icon"><?= icon('building') ?></span><span class="tile-label">Yayındaki otel</span><span class="tile-value"><?= $stats['hotels'] ?></span><span class="tile-sub"><?= $stats['hotels_draft'] ?> taslak</span></a>
    <a class="tile" href="<?= e(url('/yonetim/talepler')) ?>"><span class="tile-icon"><?= icon('document') ?></span><span class="tile-label">Bekleyen talep</span><span class="tile-value"><?= $stats['pending_requests'] ?></span><span class="tile-sub"><?= $stats['awaiting'] ?> rezervasyon teyit bekliyor</span></a>
    <a class="tile" href="<?= e(url('/yonetim/rezervasyonlar', ['durum' => 'confirmed'])) ?>"><span class="tile-icon"><?= icon('check-circle') ?></span><span class="tile-label">Onaylı (yaklaşan)</span><span class="tile-value"><?= $stats['confirmed'] ?></span></a>
    <div class="tile"><span class="tile-icon"><?= icon('bed') ?></span><span class="tile-label">Bu ay oda-gece</span><span class="tile-value"><?= $stats['month_nights'] ?></span><span class="tile-sub">Onaylı ve tamamlanan</span></div>
    <div class="tile"><span class="tile-icon"><?= icon('tag') ?></span><span class="tile-label">Bu ay rezervasyon toplamı</span><span class="tile-value"><?= e(money($stats['month_total'])) ?></span></div>
    <a class="tile" href="<?= e(url('/yonetim/api')) ?>"><span class="tile-icon"><?= icon('api') ?></span><span class="tile-label">API durumu</span><span class="tile-value" style="font-size:1rem"><?php foreach ($providers as $p): if (!$p['is_enabled']) continue; ?><span class="row" style="gap:6px"><span class="status-dot <?= e($p['status']) ?>"></span><?= e($p['name']) ?></span><?php endforeach; ?></span></a>
</div>
<div class="grid-2" style="margin-top:20px">
    <div class="card"><div class="card-head"><h2>Son 6 ay (onaylı rezervasyon)</h2></div><div class="card-body">
        <?php if (!$months): ?><p class="muted">Henüz onaylı rezervasyon verisi yok.</p><?php else: $max = max(array_map(static fn ($m) => (int) $m['t'], $months)) ?: 1; ?>
        <div class="chart-bars" role="table" aria-label="Aylık rezervasyon toplamları"><?php foreach ($months as $m): ?>
            <div class="cb-row" role="row"><span role="cell"><?= e(TR_MONTHS_SHORT[(int) substr($m['ym'], 5)] . ' ' . substr($m['ym'], 0, 4)) ?></span><div class="bar" role="cell" aria-hidden="true"><span style="width:<?= max(2, (int) round((int) $m['t'] * 100 / $max)) ?>%"></span></div><span role="cell" class="num"><?= e(money((int) $m['t'])) ?> · <?= (int) $m['c'] ?></span></div>
        <?php endforeach; ?></div><?php endif; ?>
    </div></div>
    <div class="card"><div class="card-head"><h2>Son rezervasyonlar</h2><a href="<?= e(url('/yonetim/rezervasyonlar')) ?>">Tümü</a></div>
        <?php if (!$recent): ?><div class="card-body"><p class="muted">Henüz rezervasyon yok.</p></div><?php else: ?>
        <div class="table-wrap" style="border:0;border-radius:0"><table class="table responsive"><tbody><?php foreach ($recent as $b): ?><tr>
            <td data-label="Kod"><a href="<?= e(url('/yonetim/rezervasyonlar/' . $b['id'])) ?>"><strong><?= e($b['code']) ?></strong></a><div class="small muted"><?= e($b['member']) ?></div></td>
            <td data-label="Otel"><?= e($b['hotel_name']) ?><div class="small muted"><?= e(tr_date_short($b['check_in'])) ?></div></td>
            <td data-label="Durum"><?= App\Core\View::partial('partials/status', ['status' => $b['status']]) ?></td>
            <td data-label="Tutar" class="num"><?= e(money((int) $b['total_minor'], $b['currency'])) ?></td>
        </tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
    </div>
</div>
<p class="muted small" style="margin-top:16px">Son cron çalışması: <?= e($lastCron ? tr_datetime($lastCron) : 'hiç çalışmadı') ?></p>
