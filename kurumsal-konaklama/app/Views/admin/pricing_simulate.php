<div class="admin-title"><div><p><a href="<?= e(url('/yonetim/fiyatlar', $hotelId ? ['otel' => $hotelId] : [])) ?>">← Fiyatlar</a></p><h1>Fiyat hesaplama</h1><p>Seçilen kurum için kural önceliği, indirimler ve vergilerle tam hesap dökümü.</p></div></div>
<form class="card card-body" method="get">
    <div class="form-grid form-grid-3">
        <?= f_select('otel', 'Otel', $hotels, $hotelId ?: '', 'Seçiniz', '', true) ?>
        <?= f_select('kurum', 'Kurum (boşsa genel üye)', $institutions, $instId ?: '', 'Genel üye') ?>
        <?= f_input('giris', 'Giriş', $criteria?->checkIn ?? date('Y-m-d', strtotime('+14 days')), 'date') ?>
        <?= f_input('cikis', 'Çıkış', $criteria?->checkOut ?? date('Y-m-d', strtotime('+17 days')), 'date') ?>
        <?= f_input('yetiskin', 'Yetişkin', $criteria ? $criteria->adults() : 2, 'number', ['min' => 1]) ?>
        <?= f_input('yaslar', 'Çocuk yaşları', $criteria ? implode(',', $criteria->allChildAges()) : '') ?>
        <input type="hidden" name="cocuk" value="<?= $criteria ? $criteria->children() : 0 ?>">
    </div>
    <p class="hint">Not: “Çocuk yaşları” girildiğinde çocuk sayısı otomatik hesaplanır.</p>
    <button class="btn" type="submit">HESAPLA</button>
</form>
<?php if ($error): ?><div class="alert alert-error" style="margin-top:12px"><?= icon('alert') ?><div><?= e($error) ?></div></div><?php endif; ?>
<?php foreach ($quotes as $q): ?>
<div class="card" style="margin-top:16px"><div class="card-head"><h2><?= e($q->roomName ?: 'Otel geneli') ?> · <?= e($q->conceptName ?: '—') ?></h2><span class="badge badge-<?= $q->kind === 'firm' ? 'success' : ($q->kind === 'target' ? 'teal' : 'warning') ?>"><?= e(['firm' => 'Kesin fiyat', 'target' => 'Onaya bağlı hedef teklif', 'request' => 'Fiyat yok', 'unavailable' => 'Müsait değil'][$q->kind]) ?></span></div><div class="card-body">
    <?php if ($q->message): ?><p class="muted"><?= e($q->message) ?></p><?php endif; ?>
    <?php if (in_array($q->kind, ['firm', 'target'], true)): ?>
        <?= App\Core\View::partial('partials/breakdown', ['breakdown' => $q->breakdown, 'b' => ['currency' => $q->currency, 'total_minor' => $q->total, 'tax_minor' => $q->taxTotal, 'verified_savings_minor' => $q->kind === 'firm' ? $q->verifiedSavings : null]]) ?>
        <?php if (!empty($q->breakdown['nights'])): ?><details><summary>Gece bazlı döküm</summary><table class="price-breakdown"><?php foreach ($q->breakdown['nights'] as $n): ?><tr><td><?= e(tr_date($n['date'])) ?> — kaynak <?= e(money((int) $n['source'])) ?><?php foreach ($n['adjustments'] as $a): ?><br><small class="muted"><?= e($a['label']) ?>: <?= e(money((int) $a['amount'])) ?></small><?php endforeach; ?></td><td><?= e(money((int) $n['net'])) ?></td></tr><?php endforeach; ?></table></details><?php endif; ?>
    <?php endif; ?>
</div></div>
<?php endforeach; ?>
