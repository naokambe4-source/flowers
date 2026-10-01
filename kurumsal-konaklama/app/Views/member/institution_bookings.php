<div class="container">
    <div class="page-head"><nav aria-label="Konum"><ol class="breadcrumb"><li><a href="<?= e(url('/kurumum')) ?>">Kurumum</a></li><li aria-current="page">Rezervasyonlar</li></ol></nav><h1>Kurum rezervasyonları</h1></div>
    <?php if (!$rows): ?><div class="empty"><?= icon('calendar', 'icon-l') ?><h3>Kurumunuzun rezervasyonu yok</h3></div><?php else: ?>
    <div class="table-wrap" style="margin-top:16px"><table class="table responsive"><thead><tr><th>Kod</th><th>Üye</th><th>Otel</th><th>Tarihler</th><th>Durum</th><th class="num">Tutar</th></tr></thead><tbody>
        <?php foreach ($rows as $r): ?><tr><td data-label="Kod"><a href="<?= e(url('/rezervasyonlarim/' . $r['code'])) ?>"><?= e($r['code']) ?></a></td><td data-label="Üye"><?= e($r['member']) ?></td><td data-label="Otel"><?= e($r['hotel_name']) ?></td><td data-label="Tarihler"><?= e(tr_date_short($r['check_in'])) ?> – <?= e(tr_date_short($r['check_out'])) ?></td><td data-label="Durum"><?= App\Core\View::partial('partials/status', ['status' => $r['status']]) ?></td><td data-label="Tutar" class="num"><?= e(money((int) $r['total_minor'], $r['currency'])) ?></td></tr><?php endforeach; ?>
    </tbody></table></div><?php endif; ?>
</div>
