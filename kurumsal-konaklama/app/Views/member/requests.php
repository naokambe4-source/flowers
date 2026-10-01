<div class="container">
    <div class="page-head row-between"><div><h1>Tekliflerim</h1><p>Konaklama talepleriniz ve size gönderilen teklifler</p></div><a class="btn" href="<?= e(url('/teklif-iste')) ?>"><?= icon('plus') ?> YENİ TALEP</a></div>
    <?php if (!$rows): ?>
        <div class="empty" style="margin-top:16px"><?= icon('document', 'icon-l') ?><h3>Henüz talebiniz yok</h3><p>Anlık fiyatı olmayan oteller için teklif isteyebilirsiniz.</p><a class="btn" href="<?= e(url('/teklif-iste')) ?>">TEKLİF İSTE</a></div>
    <?php else: ?>
    <div class="table-wrap" style="margin-top:16px"><table class="table responsive">
        <thead><tr><th>Kod</th><th>Otel / bölge</th><th>Tarihler</th><th>Konuk</th><th>Durum</th><th></th></tr></thead>
        <tbody><?php foreach ($rows as $r): ?><tr>
            <td data-label="Kod"><strong><?= e($r['code']) ?></strong></td>
            <td data-label="Otel"><?= e($r['hotel_name'] ?: ($r['region_name'] ?: '—')) ?></td>
            <td data-label="Tarihler"><?= e(tr_date_short($r['check_in'])) ?> – <?= e(tr_date_short($r['check_out'])) ?></td>
            <td data-label="Konuk"><?= (int) $r['adults'] ?> yet.<?= (int) $r['children'] ? ' · ' . (int) $r['children'] . ' çoc.' : '' ?></td>
            <td data-label="Durum"><?= App\Core\View::partial('partials/status', ['status' => $r['status']]) ?><?php if ((int) $r['live_offers']): ?> <span class="badge badge-gold">Yanıt bekliyor</span><?php endif; ?></td>
            <td class="actions"><a class="btn btn-secondary btn-sm" href="<?= e(url('/tekliflerim/' . $r['code'])) ?>">İNCELE</a></td>
        </tr><?php endforeach; ?></tbody>
    </table></div>
    <?php endif; ?>
</div>
