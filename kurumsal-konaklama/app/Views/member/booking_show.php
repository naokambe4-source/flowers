<?php $canCancel = $canModify && in_array($b['status'], ['requested', 'pending', 'confirmed'], true) && $b['check_in'] > date('Y-m-d'); ?>
<div class="container">
    <div class="page-head row-between">
        <div><nav aria-label="Konum"><ol class="breadcrumb"><li><a href="<?= e(url('/rezervasyonlarim')) ?>">Rezervasyonlarım</a></li><li aria-current="page"><?= e($b['code']) ?></li></ol></nav>
        <h1><?= e($b['hotel_name']) ?></h1><p>Rezervasyon kodu: <strong><?= e($b['code']) ?></strong> · <?= App\Core\View::partial('partials/status', ['status' => $b['status']]) ?></p></div>
        <?php if (in_array($b['status'], ['confirmed', 'completed'], true)): ?><a class="btn btn-lg" href="<?= e(url('/rezervasyonlarim/' . $b['code'] . '/voucher')) ?>"><?= icon('download') ?> VOUCHER İNDİR (PDF)</a><?php endif; ?>
    </div>
    <?php if ($b['status'] === 'requested'): ?><div class="alert alert-info"><?= icon('clock') ?><div>Talebiniz alındı ve otele iletilmek üzere ekibimize ulaştı. Otel teyidi alındığında onay bildirimi alacaksınız.</div></div><?php endif; ?>
    <?php if ($b['status'] === 'pending'): ?><div class="alert alert-warning"><?= icon('clock') ?><div>Otel teyidi bekleniyor. Teyit numarası alındığında rezervasyonunuz onaylanacaktır.</div></div><?php endif; ?>
    <?php if ($b['status'] === 'cancelled'): ?><div class="alert alert-error"><?= icon('alert') ?><div>Bu rezervasyon <?= e(tr_datetime($b['cancelled_at'])) ?> tarihinde iptal edildi.<?= $b['cancel_reason'] ? ' Neden: ' . e($b['cancel_reason']) : '' ?></div></div><?php endif; ?>
    <div class="detail-layout" style="margin-top:8px">
        <div class="stack">
            <div class="card"><div class="card-body">
                <h2 style="font-size:1.15rem">Konaklama</h2>
                <dl class="kv">
                    <dt>Otel</dt><dd><a href="<?= e(url('/oteller/' . $b['slug'])) ?>"><?= e($b['hotel_name']) ?></a></dd>
                    <dt>Adres</dt><dd><?= e($b['address'] ?: '—') ?></dd>
                    <dt>Giriş</dt><dd><?= e(tr_date($b['check_in'], true)) ?><?= $b['check_in_time'] ? ' · ' . e($b['check_in_time']) . ' itibarıyla' : '' ?></dd>
                    <dt>Çıkış</dt><dd><?= e(tr_date($b['check_out'], true)) ?><?= $b['check_out_time'] ? ' · ' . e($b['check_out_time']) . '’e kadar' : '' ?></dd>
                    <dt>Konuklar</dt><dd><?= e(guest_summary((int) $b['adults'], (int) $b['children'], (int) $b['rooms_count'])) ?></dd>
                    <?php foreach ($rooms as $i => $r): ?><dt><?= $i + 1 ?>. oda</dt><dd><?= e($r['room_name']) ?><?= $r['concept_name'] ? ' · ' . e($r['concept_name']) : '' ?></dd><?php endforeach; ?>
                    <?php if ($b['hotel_confirmation_no']): ?><dt>Otel teyit no</dt><dd><?= e($b['hotel_confirmation_no']) ?></dd><?php endif; ?>
                    <dt>Ödeme</dt><dd><?= e(['unpaid' => 'Ödenmedi', 'pay_at_hotel' => 'Otelde ödeme', 'invoiced' => 'Kurum faturası', 'paid' => 'Ödendi', 'refunded' => 'İade edildi'][$b['payment_status']] ?? $b['payment_status']) ?></dd>
                </dl>
            </div></div>
            <div class="card"><div class="card-body"><h2 style="font-size:1.15rem">Misafirler</h2>
                <ul><?php foreach ($guests as $g): ?><li><?= e($g['first_name'] . ' ' . $g['last_name']) ?><?= $g['is_child'] ? ' (' . (int) $g['age'] . ' yaş)' : '' ?></li><?php endforeach; ?></ul>
                <p class="small muted">İletişim: <?= e($b['contact_phone']) ?> · <?= e($b['contact_email']) ?></p>
                <?php if ($b['notes']): ?><p class="small">Not: <?= e($b['notes']) ?></p><?php endif; ?>
            </div></div>
            <div class="card"><div class="card-body"><h2 style="font-size:1.15rem">Fiyat dökümü</h2><?= App\Core\View::partial('partials/breakdown', ['breakdown' => $breakdown, 'b' => $b]) ?></div></div>
            <div class="card"><div class="card-body"><h2 style="font-size:1.15rem">Koşullar (rezervasyon anındaki kopya)</h2>
                <h3>İptal</h3><div class="prose"><?= nl2p($terms['cancellation_policy'] ?? '') ?: '<p>' . e($terms['cancellation_summary'] ?? '—') . '</p>' ?></div>
                <h3>Ödeme</h3><div class="prose"><?= nl2p($terms['payment_terms'] ?? '') ?: '<p>—</p>' ?></div>
            </div></div>
        </div>
        <aside class="stack">
            <div class="card"><div class="card-body"><h2 style="font-size:1.1rem">Durum geçmişi</h2>
                <ol class="timeline"><?php foreach ($history as $hst): if ($hst['to_status'] === 'draft') continue; ?><li><strong><?= e(status_label($hst['to_status'])) ?></strong><br><time><?= e(tr_datetime($hst['created_at'])) ?></time><?php if ($hst['note']): ?><div class="small"><?= e($hst['note']) ?></div><?php endif; ?></li><?php endforeach; ?></ol>
            </div></div>
            <?php if ($canCancel): ?>
            <div class="card"><div class="card-body">
                <h2 style="font-size:1.1rem">Rezervasyonu iptal et</h2>
                <p class="small muted">İptal koşulları: <?= e($terms['cancellation_summary'] ?? 'yukarıdaki koşullar geçerlidir') ?></p>
                <form method="post" action="<?= e(url('/rezervasyonlarim/' . $b['code'] . '/iptal')) ?>" data-confirm="Rezervasyonu iptal etmek istediğinize emin misiniz? Bu işlem geri alınamaz.">
                    <?= csrf_field() ?>
                    <div class="field"><label for="neden">İptal nedeni <span class="muted">(isteğe bağlı)</span></label><textarea id="neden" name="neden" rows="2"></textarea></div>
                    <button class="btn btn-danger btn-block" type="submit">REZERVASYONU İPTAL ET</button>
                </form>
            </div></div>
            <?php endif; ?>
            <div class="card"><div class="card-body stack-s"><h2 style="font-size:1.1rem">Yardım</h2><?= App\Core\View::partial('partials/support_contacts') ?></div></div>
        </aside>
    </div>
</div>
