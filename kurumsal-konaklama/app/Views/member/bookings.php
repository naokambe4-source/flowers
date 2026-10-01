<div class="container">
    <div class="page-head"><h1>Rezervasyonlarım</h1><p>Rezervasyon durumları, voucher ve iptal işlemleri</p></div>
    <div class="seg" role="tablist" aria-label="Rezervasyon filtreleri" style="margin:16px 0">
        <?php foreach (['aktif' => 'Aktif', 'gecmis' => 'Geçmiş', 'iptal' => 'İptal edilen'] as $k => $l): ?><a href="<?= e(url('/rezervasyonlarim', ['durum' => $k])) ?>" aria-current="<?= $tab === $k ? 'true' : 'false' ?>"><?= e($l) ?></a><?php endforeach; ?>
    </div>
    <?php if ($drafts && $tab === 'aktif'): ?>
        <div class="alert alert-info"><?= icon('info') ?><div><strong>Tamamlanmamış rezervasyonlarınız var:</strong> <?php foreach ($drafts as $i => $d): ?><?= $i ? ', ' : '' ?><a href="<?= e(url('/rezervasyon/' . $d['code'] . '/misafirler')) ?>"><?= e($d['hotel_name']) ?></a><?php endforeach; ?></div></div>
    <?php endif; ?>
    <?php if (!$rows): ?>
        <div class="empty"><?= icon('calendar', 'icon-l') ?><h3>Bu listede rezervasyon yok</h3><p>Anlaşmalı otelleri arayarak rezervasyon yapabilir veya teklif isteyebilirsiniz.</p><a class="btn" href="<?= e(url('/oteller')) ?>">OTEL ARA</a></div>
    <?php else: ?>
        <div class="stack">
        <?php foreach ($rows as $b): ?>
            <a class="card" href="<?= e(url('/rezervasyonlarim/' . $b['code'])) ?>" style="display:block;text-decoration:none;color:inherit"><div class="card-body row-between">
                <div class="row" style="gap:14px;flex-wrap:nowrap;min-width:0">
                    <?php if ($b['cover_image_id']): ?><img src="<?= e(hotel_image_url((int) $b['cover_image_id'], 'thumb')) ?>" alt="" style="width:96px;height:72px;object-fit:cover;border-radius:10px;flex:none" loading="lazy"><?php endif; ?>
                    <div style="min-width:0"><strong><?= e($b['hotel_name']) ?></strong><div class="muted small"><?= e($b['region_name']) ?> · <?= e(tr_date($b['check_in'])) ?> – <?= e(tr_date($b['check_out'])) ?> · <?= (int) $b['nights'] ?> gece</div><div class="small">Kod: <strong><?= e($b['code']) ?></strong></div></div>
                </div>
                <div style="text-align:right"><?= App\Core\View::partial('partials/status', ['status' => $b['status']]) ?><div style="font-weight:800;margin-top:6px"><?= e(money((int) $b['total_minor'], $b['currency'])) ?></div></div>
            </div></a>
        <?php endforeach; ?>
        </div>
        <?= pagination_links($page, ['durum' => $tab]) ?>
    <?php endif; ?>
</div>
