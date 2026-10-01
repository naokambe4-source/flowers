<div class="admin-title"><div><p><a href="<?= e(url('/yonetim/rezervasyonlar')) ?>">← Rezervasyonlar</a></p><h1><?= e($b['code']) ?> · <?= e($b['hotel_name']) ?></h1><p><?= App\Core\View::partial('partials/status', ['status' => $b['status']]) ?> · <?= e(['instant' => 'Doğrulanmış stokla anında', 'request' => 'Otel teyidine bağlı talep', 'offer' => 'Teklif kabulü'][$b['mode']]) ?><?= $b['provider_name'] && $b['source'] === 'provider' ? ' · Sağlayıcı: ' . e($b['provider_name']) : '' ?></p></div>
<?php if (in_array($b['status'], ['confirmed', 'completed'], true)): ?><a class="btn btn-secondary" href="<?= e(url('/yonetim/rezervasyonlar/' . $b['id'] . '/voucher')) ?>"><?= icon('download') ?> Voucher</a><?php endif; ?></div>
<?php if ($b['admin_notes']): ?><div class="alert alert-warning"><?= icon('info') ?><div><?= nl2br(e($b['admin_notes'])) ?></div></div><?php endif; ?>
<div class="grid-2">
    <div class="stack">
        <div class="card"><div class="card-head"><h2>Konaklama ve misafir</h2></div><div class="card-body">
            <dl class="kv">
                <dt>Üye</dt><dd><?= e($b['member']) ?> · <?= e($b['member_email']) ?> · <?= e($b['member_phone']) ?></dd>
                <dt>Kurum</dt><dd><?= e($b['institution_name'] ?: '—') ?></dd>
                <dt>Giriş / çıkış</dt><dd><?= e(tr_date($b['check_in'], true)) ?> – <?= e(tr_date($b['check_out'], true)) ?> (<?= (int) $b['nights'] ?> gece)</dd>
                <?php foreach ($rooms as $i => $r): ?><dt><?= $i + 1 ?>. oda</dt><dd><?= e($r['room_name']) ?><?= $r['concept_name'] ? ' · ' . e($r['concept_name']) : '' ?> — <?= (int) $r['adults'] ?> yetişkin<?= $r['children_ages'] !== '' ? ', çocuk yaşları ' . e($r['children_ages']) : '' ?></dd><?php endforeach; ?>
                <dt>Misafirler</dt><dd><?php foreach ($guests as $g): ?><?= e($g['first_name'] . ' ' . $g['last_name']) ?><?= $g['is_child'] ? ' (' . (int) $g['age'] . ')' : '' ?><?= $g['is_lead'] ? ' *' : '' ?><br><?php endforeach; ?></dd>
                <dt>İletişim</dt><dd><?= e($b['contact_name']) ?> · <?= e($b['contact_phone']) ?> · <?= e($b['contact_email']) ?></dd>
                <?php if ($b['notes']): ?><dt>Üye notu</dt><dd><?= e($b['notes']) ?></dd><?php endif; ?>
                <?php if ($b['provider_reference']): ?><dt>Sağlayıcı ref.</dt><dd><?= e($b['provider_reference']) ?></dd><?php endif; ?>
                <dt>Stok</dt><dd><?= (int) $b['stock_managed'] ? 'Kontenjandan düşüldü' : 'Sistem kontenjanı kullanılmadı' ?></dd>
            </dl>
        </div></div>
        <div class="card"><div class="card-head"><h2>Fiyat dökümü</h2></div><div class="card-body"><?= App\Core\View::partial('partials/breakdown', ['breakdown' => $breakdown, 'b' => $b]) ?>
            <?php if (!empty($breakdown['nights'])): ?><details style="margin-top:10px"><summary>Gece bazlı hesap</summary><table class="price-breakdown"><?php foreach ($breakdown['nights'] as $n): ?><tr><td><?= e(tr_date($n['date'])) ?> — kaynak <?= e(money((int) $n['source'])) ?><?php foreach ($n['adjustments'] as $a): ?><br><small class="muted"><?= e($a['label']) ?>: <?= e(money((int) $a['amount'])) ?></small><?php endforeach; ?></td><td><?= e(money((int) $n['net'])) ?></td></tr><?php endforeach; ?></table></details><?php endif; ?>
        </div></div>
        <div class="card"><div class="card-head"><h2>Koşulların anlık kopyası</h2></div><div class="card-body small">
            <p><strong>İptal:</strong> <?= nl2br(e(($terms['cancellation_policy'] ?? '') ?: ($terms['cancellation_summary'] ?? '—'))) ?></p>
            <p><strong>Ödeme:</strong> <?= nl2br(e(($terms['payment_terms'] ?? '') ?: '—')) ?></p>
            <p class="muted">Kayıt zamanı: <?= e($terms['captured_at'] ?? '—') ?></p>
        </div></div>
    </div>
    <div class="stack">
        <?php if ($transitions && can('bookings.manage')): ?>
        <div class="card"><div class="card-head"><h2>Durum değiştir</h2></div><div class="card-body">
            <?php if ($b['mode'] !== 'instant' && in_array('confirmed', $transitions, true)): ?><div class="alert alert-info"><?= icon('info') ?><div>Onaylamadan önce otelden <strong>doğrulanmış rezervasyon numarası</strong> alın ve aşağıya girin. Teklif kabulü otel teyidi sayılmaz.</div></div><?php endif; ?>
            <form method="post" action="<?= e(url('/yonetim/rezervasyonlar/' . $b['id'] . '/durum')) ?>" data-confirm="Durum değişikliği üyeye bildirilecek. Devam edilsin mi?"><?= csrf_field() ?>
                <?= f_select('durum', 'Yeni durum', array_combine($transitions, array_map('status_label', $transitions)), '', '', '', true) ?>
                <?= f_input('otel_no', 'Otel rezervasyon / teyit numarası', $b['hotel_confirmation_no'] ?? '') ?>
                <?= f_textarea('not', 'Not (üyeye bildirimde gösterilir)', '', 2, '', false) ?>
                <button class="btn" type="submit">DURUMU GÜNCELLE</button>
            </form>
        </div></div>
        <?php endif; ?>
        <?php if (can('bookings.manage')): ?>
        <div class="card"><div class="card-head"><h2>Ödeme ve notlar</h2></div><div class="card-body">
            <form method="post" action="<?= e(url('/yonetim/rezervasyonlar/' . $b['id'] . '/bilgi')) ?>"><?= csrf_field() ?>
                <?= f_select('payment_status', 'Ödeme durumu', ['pay_at_hotel' => 'Otelde ödeme', 'invoiced' => 'Kurum faturası', 'unpaid' => 'Ödenmedi', 'paid' => 'Ödendi', 'refunded' => 'İade edildi'], $b['payment_status']) ?>
                <?= f_input('hotel_confirmation_no', 'Otel teyit numarası', $b['hotel_confirmation_no']) ?>
                <?= f_textarea('admin_notes', 'Yönetici notu (üyeye gösterilmez)', $b['admin_notes'], 3, '', false) ?>
                <button class="btn btn-secondary" type="submit">KAYDET</button>
            </form>
        </div></div>
        <?php endif; ?>
        <div class="card"><div class="card-head"><h2>Durum geçmişi</h2></div><div class="card-body"><ol class="timeline"><?php foreach ($history as $h): ?><li><strong><?= e(status_label($h['to_status'])) ?></strong> <time><?= e(tr_datetime($h['created_at'])) ?></time><div class="small muted"><?= e($h['who'] ?: 'Sistem') ?><?= $h['note'] ? ' — ' . e($h['note']) : '' ?></div></li><?php endforeach; ?></ol></div></div>
    </div>
</div>
