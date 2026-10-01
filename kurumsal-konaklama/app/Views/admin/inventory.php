<div class="admin-title"><div><h1>Kontenjan</h1><p>Günlük oda adedi, satış durumu ve konaklama kuralları. Rezervasyonlar stoğu atomik olarak düşer.</p></div></div>
<form class="toolbar card card-body" method="get"><?= f_select('otel', 'Otel', $hotels, $hotelId ?: '', 'Otel seçin') ?><?php if ($rooms): ?><?= f_select('oda', 'Oda', $rooms, $roomId) ?><?php endif; ?><?= f_input('bas', 'Başlangıç', $from, 'date') ?><div class="field" style="flex:0 0 auto"><span class="label">&nbsp;</span><button class="btn">Göster</button></div></form>
<?php if (!$hotelId): ?><div class="empty"><?= icon('layers', 'icon-l') ?><h3>Otel seçin</h3></div><?php elseif (!$rooms): ?><div class="empty"><h3>Bu otelde oda yok</h3></div><?php else: ?>
<div class="grid-2">
    <div class="card"><div class="card-head"><h2>Toplu güncelle — <?= e($rooms[$roomId] ?? '') ?></h2></div><div class="card-body">
        <form method="post" action="<?= e(url('/yonetim/kontenjan')) ?>"><?= csrf_field() ?><input type="hidden" name="room_id" value="<?= (int) $roomId ?>">
            <div class="form-grid">
                <?= f_input('from', 'Başlangıç', $from, 'date', ['required' => true]) ?>
                <?= f_input('to', 'Bitiş', date('Y-m-d', strtotime($from . ' +30 days')), 'date', ['required' => true]) ?>
                <?= f_input('total_units', 'Satılabilir oda adedi', '', 'number', ['min' => 0], 'Boş bırakılırsa değişmez.') ?>
                <?= f_select('is_open', 'Satış durumu', ['1' => 'Açık', '0' => 'Kapalı'], '', 'Değiştirme') ?>
                <?= f_input('min_stay', 'Minimum konaklama (gece)', '', 'number', ['min' => 1]) ?>
                <?= f_input('max_stay', 'Maksimum konaklama (gece)', '', 'number', ['min' => 1]) ?>
                <?= f_input('note', 'Not / özel dönem', '') ?>
            </div>
            <label class="check"><input type="checkbox" name="clear_min_stay" value="1"> Minimum konaklamayı kaldır</label>
            <label class="check"><input type="checkbox" name="clear_max_stay" value="1"> Maksimum konaklamayı kaldır</label>
            <?= App\Core\View::partial('admin/partials_weekdays') ?>
            <button class="btn" type="submit">KONTENJANI KAYDET</button>
        </form>
    </div></div>
    <div class="card"><div class="card-head"><h2>Stop sale</h2></div><div class="card-body">
        <?php foreach ($stopSales as $s): ?><div class="panel row-between" style="margin-bottom:8px"><div><strong><?= e(tr_date_short($s['date_from'])) ?> – <?= e(tr_date_short($s['date_to'])) ?></strong> · <?= e($s['room'] ?: 'Tüm odalar') ?><div class="small muted"><?= e($s['reason']) ?></div></div><form method="post" action="<?= e(url('/yonetim/kontenjan/stop-sale/' . $s['id'] . '/sil')) ?>" data-confirm="Stop sale kaldırılsın mı?"><?= csrf_field() ?><button class="btn btn-danger btn-sm">Kaldır</button></form></div><?php endforeach; ?>
        <form method="post" action="<?= e(url('/yonetim/kontenjan/stop-sale')) ?>"><?= csrf_field() ?><input type="hidden" name="hotel_id" value="<?= (int) $hotelId ?>">
            <div class="form-grid"><?= f_select('room_id', 'Oda', $rooms, '', 'Tüm odalar') ?><?= f_input('reason', 'Neden', '') ?><?= f_input('date_from', 'Başlangıç', '', 'date', ['required' => true]) ?><?= f_input('date_to', 'Bitiş', '', 'date', ['required' => true]) ?></div>
            <button class="btn btn-danger" type="submit">STOP SALE EKLE</button>
        </form>
    </div></div>
</div>
<div class="card" style="margin-top:20px"><div class="card-head"><h2>Takvim (42 gün)</h2><span class="small muted">Boş / toplam · <span class="badge badge-danger">kapalı</span> <span class="badge badge-warning">dolu</span></span></div><div class="card-body">
    <?php $by = []; foreach ($days as $d) { $by[$d['stay_date']] = $d; } $start = new DateTimeImmutable($from); ?>
    <div class="cal-grid"><?php foreach (['Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz'] as $dn): ?><div class="small muted text-center"><?= $dn ?></div><?php endforeach; ?>
    <?php for ($i = 0; $i < (int) $start->format('N') - 1; $i++): ?><div></div><?php endfor; ?>
    <?php for ($i = 0; $i < 42; $i++): $dt = $start->modify("+$i day"); $row = $by[$dt->format('Y-m-d')] ?? null; $free = $row ? (int) $row['total_units'] - (int) $row['booked_units'] : 0; ?>
        <div class="cal-cell<?= !$row ? ' none' : (!(int) $row['is_open'] ? ' closed' : ($free <= 0 ? ' full' : '')) ?>"><div class="d"><?= $dt->format('j') ?> <?= TR_MONTHS_SHORT[(int) $dt->format('n')] ?></div>
            <?php if ($row): ?><?= $free ?> / <?= (int) $row['total_units'] ?><?= !(int) $row['is_open'] ? '<br>Kapalı' : '' ?><?= $row['min_stay'] ? '<br>min ' . (int) $row['min_stay'] : '' ?><?php else: ?>tanımsız<?php endif; ?></div>
    <?php endfor; ?></div>
</div></div>
<?php endif; ?>
