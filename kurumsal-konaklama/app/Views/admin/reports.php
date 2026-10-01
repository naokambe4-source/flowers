<?php $s = $summary; ?>
<div class="admin-title"><div><h1>Raporlar</h1><p>Taslaklar hariç gerçek rezervasyon verisi. İptal edilenler sayıya dahil, tutarlara dahil değildir.</p></div>
<?php if (can('reports.export')): ?><div class="row"><a class="btn btn-secondary" href="<?= e(url('/yonetim/raporlar/disa-aktar', $query + ['format' => 'csv'])) ?>"><?= icon('download') ?> CSV</a><a class="btn btn-secondary" href="<?= e(url('/yonetim/raporlar/disa-aktar', $query + ['format' => 'xlsx'])) ?>"><?= icon('download') ?> XLSX</a></div><?php endif; ?></div>
<form class="toolbar card card-body" method="get">
    <?= f_input('bas', 'Başlangıç', $f['from'], 'date') ?><?= f_input('bit', 'Bitiş', $f['to'], 'date') ?>
    <?= f_select('tarih', 'Tarih türü', ['created' => 'Oluşturma tarihi', 'checkin' => 'Giriş tarihi'], $f['date_type']) ?>
    <?= f_select('otel', 'Otel', $hotels, $f['hotel_id'] ?? '', 'Tümü') ?><?= f_select('kurum', 'Kurum', $institutions, $f['institution_id'] ?? '', 'Tümü') ?>
    <?= f_select('bolge', 'Bölge', $regions, $f['region_id'] ?? '', 'Tümü') ?>
    <?= f_select('durum', 'Durum', ['requested' => 'Talep Alındı', 'pending' => 'Onay Bekliyor', 'confirmed' => 'Onaylandı', 'completed' => 'Tamamlandı', 'cancelled' => 'İptal'], $f['status'] ?? '', 'Tümü') ?>
    <?= f_select('grup', 'Gruplama', App\Services\ReportService::GROUPS, $group) ?>
    <div class="field" style="flex:0 0 auto"><span class="label">&nbsp;</span><button class="btn">Uygula</button></div>
</form>
<div class="tiles" style="margin-bottom:20px">
    <div class="tile"><span class="tile-label">Rezervasyon</span><span class="tile-value"><?= (int) ($s['bookings'] ?? 0) ?></span><span class="tile-sub"><?= (int) ($s['cancelled'] ?? 0) ?> iptal</span></div>
    <div class="tile"><span class="tile-label">Oda-gece</span><span class="tile-value"><?= (int) ($s['room_nights'] ?? 0) ?></span></div>
    <div class="tile"><span class="tile-label">Rezervasyon tutarı</span><span class="tile-value"><?= e(money((int) ($s['total_minor'] ?? 0))) ?></span></div>
    <div class="tile"><span class="tile-label">İndirim / doğrulanmış tasarruf</span><span class="tile-value" style="font-size:1.15rem"><?= e(money((int) ($s['discount_minor'] ?? 0))) ?><br><?= e(money((int) ($s['savings_minor'] ?? 0))) ?></span></div>
</div>
<div class="table-wrap"><table class="table responsive"><thead><tr><th><?= e(App\Services\ReportService::GROUPS[$group]) ?></th><th class="num">Rezervasyon</th><th class="num">İptal</th><th class="num">Oda-gece</th><th class="num">Tutar</th><th class="num">İndirim</th><th class="num">Doğr. tasarruf</th></tr></thead><tbody>
<?php foreach ($grouped as $g): ?><tr><td data-label="Grup"><?= e($group === 'status' ? status_label((string) $g['label']) : $g['label']) ?></td><td data-label="Rezervasyon" class="num"><?= (int) $g['bookings'] ?></td><td data-label="İptal" class="num"><?= (int) $g['cancelled'] ?></td><td data-label="Oda-gece" class="num"><?= (int) $g['room_nights'] ?></td><td data-label="Tutar" class="num"><?= e(money((int) $g['total_minor'])) ?></td><td data-label="İndirim" class="num"><?= e(money((int) $g['discount_minor'])) ?></td><td data-label="Tasarruf" class="num"><?= e(money((int) $g['savings_minor'])) ?></td></tr><?php endforeach; ?>
<?php if (!$grouped): ?><tr><td colspan="7" class="muted">Seçilen ölçütlerde veri yok.</td></tr><?php endif; ?>
</tbody></table></div>
