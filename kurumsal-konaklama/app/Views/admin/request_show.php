<div class="admin-title"><div><p><a href="<?= e(url('/yonetim/talepler')) ?>">← Talepler</a></p><h1>Talep <?= e($req['code']) ?></h1><p><?= App\Core\View::partial('partials/status', ['status' => $req['status']]) ?> · <?= e($req['member']) ?> · <?= e($req['institution_name'] ?: 'Kurumsuz') ?></p></div>
<?php if (in_array($req['status'], ['new', 'offered'], true)): ?><form method="post" action="<?= e(url('/yonetim/talepler/' . $req['id'] . '/durum')) ?>" data-confirm="Talep iptal edilsin mi? Geçerli teklifler geri çekilir."><?= csrf_field() ?><input type="hidden" name="durum" value="cancelled"><button class="btn btn-danger">Talebi iptal et</button></form><?php endif; ?></div>
<div class="grid-2">
    <div class="stack">
        <div class="card"><div class="card-head"><h2>Talep</h2></div><div class="card-body"><dl class="kv">
            <dt>Üye</dt><dd><?= e($req['member']) ?> · <?= e($req['email']) ?> · <?= e($req['contact_phone'] ?: $req['user_phone']) ?></dd>
            <dt>Otel</dt><dd><?= e($req['hotel_name'] ?: 'Fark etmez') ?></dd><dt>Bölge</dt><dd><?= e($req['region_name'] ?: '—') ?></dd>
            <dt>Oda / konsept</dt><dd><?= e($req['room_name'] ?: 'Fark etmez') ?> · <?= e($req['concept_name'] ?: 'Fark etmez') ?></dd>
            <dt>Tarihler</dt><dd><?= e(tr_date($req['check_in'])) ?> – <?= e(tr_date($req['check_out'])) ?></dd>
            <dt>Odalar</dt><dd><?php foreach ($rooms as $i => $r): ?><?= $i + 1 ?>. oda: <?= (int) $r['adults'] ?> yetişkin<?= !empty($r['ages']) ? ', çocuk yaşları ' . e(implode(', ', $r['ages'])) : '' ?><br><?php endforeach; ?></dd>
            <?php if ($req['notes']): ?><dt>Not</dt><dd><?= nl2br(e($req['notes'])) ?></dd><?php endif; ?>
            <?php if ($req['target_minor']): ?><dt>Hedef teklif</dt><dd><?= e(money((int) $req['target_minor'])) ?><?php if ($ref): ?><br><small class="muted">Referans: <?= e(money((int) $ref['total_minor'])) ?> · <?= e($ref['source_label'] ?: $ref['source']) ?> · <?= e(tr_datetime($ref['captured_at'])) ?></small><?php endif; ?></dd><?php endif; ?>
        </dl></div></div>
        <div class="card"><div class="card-head"><h2>Teklif geçmişi</h2></div><div class="card-body">
            <?php if (!$offers): ?><p class="muted">Henüz teklif gönderilmedi.</p><?php endif; ?>
            <?php foreach ($offers as $o): ?><div class="panel" style="margin-bottom:8px"><div class="row-between"><strong>Sürüm <?= (int) $o['version'] ?> · <?= e(money((int) $o['total_minor'])) ?></strong><?= App\Core\View::partial('partials/status', ['status' => $o['status']]) ?></div><div class="small muted"><?= e($o['hotel_name']) ?> · <?= e($o['room_name']) ?> · geçerlilik <?= e(tr_datetime($o['valid_until'])) ?> · <?= e($o['who']) ?></div></div><?php endforeach; ?>
        </div></div>
    </div>
    <?php if (in_array($req['status'], ['new', 'offered', 'expired', 'declined'], true)): ?>
    <div class="card"><div class="card-head"><h2><?= $offers ? 'Yeni teklif sürümü gönder' : 'Teklif gönder' ?></h2></div><div class="card-body">
        <?php if ($offers): ?><p class="small muted">Yeni sürüm gönderildiğinde önceki geçerli teklif “Eski Sürüm” olur ve kabul edilemez.</p><?php endif; ?>
        <form method="get" action="<?= e(url('/yonetim/talepler/' . $req['id'])) ?>" class="row" style="align-items:flex-end"><?= f_select('otel', 'Otel seç (oda listesi için)', $hotels, $hotel['id'] ?? '', 'Seçiniz') ?><button class="btn btn-secondary" type="submit" style="margin-bottom:16px">Odaları getir</button></form>
        <form method="post" action="<?= e(url('/yonetim/talepler/' . $req['id'] . '/teklif')) ?>"><?= csrf_field() ?>
            <input type="hidden" name="hotel_id" value="<?= (int) ($hotel['id'] ?? 0) ?>">
            <?php if (!$hotel): ?><div class="alert alert-warning"><?= icon('alert') ?><div>Önce otel seçin.</div></div><?php else: ?>
            <p><strong><?= e($hotel['name']) ?></strong></p>
            <div class="form-grid">
                <?= f_select('room_id', 'Oda (sistemde varsa)', $hotelRooms, $offers[0]['room_id'] ?? ($req['room_id'] ?? ''), 'Listeden seçmeyeceğim') ?>
                <?= f_input('room_name', 'Oda adı (listede yoksa)', '') ?>
                <?= f_select('concept_id', 'Konsept', $concepts, $req['concept_id'] ?? '', 'Belirtilmemiş') ?>
                <?= f_input('total', 'Vergiler dahil toplam (TL)', '', 'text', ['required' => true, 'inputmode' => 'decimal', 'placeholder' => '12.500,00']) ?>
                <?= f_input('check_in', 'Giriş', $req['check_in'], 'date', ['required' => true]) ?>
                <?= f_input('check_out', 'Çıkış', $req['check_out'], 'date', ['required' => true]) ?>
                <?= f_input('valid_until', 'Teklif geçerlilik sonu', $validity, 'datetime-local', ['required' => true]) ?>
                <div></div>
                <?= f_textarea('payment_terms', 'Ödeme koşulları', $offers[0]['payment_terms'] ?? ($hotel['payment_policy'] ?? ''), 3) ?>
                <?= f_textarea('cancellation_terms', 'İptal koşulları', $offers[0]['cancellation_terms'] ?? ($hotel['cancellation_policy'] ?? ''), 3) ?>
                <?= f_textarea('note', 'Üyeye not', '', 2) ?>
            </div>
            <button class="btn" type="submit">TEKLİFİ GÖNDER</button>
            <?php endif; ?>
        </form>
    </div></div>
    <?php endif; ?>
</div>
