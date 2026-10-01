<div class="admin-title"><div><h1>Rezervasyonlar</h1><p><?= (int) $page['total'] ?> kayıt</p></div></div>
<form class="toolbar card card-body" method="get">
    <?= f_input('ara', 'Ara', $f['ara'] ?? '', 'search', ['placeholder' => 'Kod, ad, e-posta']) ?>
    <?= f_select('durum', 'Durum', ['requested' => 'Talep Alındı', 'pending' => 'Onay Bekliyor', 'confirmed' => 'Onaylandı', 'completed' => 'Tamamlandı', 'cancelled' => 'İptal Edildi'], $f['durum'] ?? '', 'Tümü') ?>
    <?= f_select('otel', 'Otel', $hotels, $f['otel'] ?? '', 'Tümü') ?>
    <?= f_select('kurum', 'Kurum', $institutions, $f['kurum'] ?? '', 'Tümü') ?>
    <?= f_input('bas', 'Giriş (başlangıç)', $f['bas'] ?? '', 'date') ?>
    <?= f_input('bit', 'Giriş (bitiş)', $f['bit'] ?? '', 'date') ?>
    <div class="field" style="flex:0 0 auto"><span class="label">&nbsp;</span><button class="btn" type="submit"><?= icon('filter') ?> Filtrele</button></div>
</form>
<?php if (!$rows): ?><div class="empty"><?= icon('calendar', 'icon-l') ?><h3>Kayıt bulunamadı</h3></div><?php else: ?>
<div class="table-wrap"><table class="table responsive">
    <thead><tr><th>Kod</th><th>Üye / kurum</th><th>Otel</th><th>Tarihler</th><th>Mod</th><th>Durum</th><th class="num">Toplam</th><th></th></tr></thead>
    <tbody><?php foreach ($rows as $b): ?><tr>
        <td data-label="Kod"><strong><?= e($b['code']) ?></strong><div class="small muted"><?= e(tr_datetime($b['created_at'])) ?></div></td>
        <td data-label="Üye"><?= e($b['member']) ?><div class="small muted"><?= e($b['institution_name'] ?: '—') ?></div></td>
        <td data-label="Otel"><?= e($b['hotel_name']) ?></td>
        <td data-label="Tarihler"><?= e(tr_date_short($b['check_in'])) ?> – <?= e(tr_date_short($b['check_out'])) ?><div class="small muted"><?= (int) $b['nights'] ?> gece · <?= (int) $b['rooms_count'] ?> oda</div></td>
        <td data-label="Mod"><?= e(['instant' => 'Anında', 'request' => 'Otel teyitli', 'offer' => 'Teklif'][$b['mode']]) ?></td>
        <td data-label="Durum"><?= App\Core\View::partial('partials/status', ['status' => $b['status']]) ?></td>
        <td data-label="Toplam" class="num"><?= e(money((int) $b['total_minor'], $b['currency'])) ?></td>
        <td class="actions"><a class="btn btn-secondary btn-sm" href="<?= e(url('/yonetim/rezervasyonlar/' . $b['id'])) ?>">Aç</a></td>
    </tr><?php endforeach; ?></tbody>
</table></div>
<?= pagination_links($page, $query) ?>
<?php endif; ?>
