<!doctype html>
<html lang="tr"><head><meta charset="utf-8"><style>
@page { margin: 28px 32px; }
body { font-family: "DejaVu Sans", sans-serif; font-size: 11px; color: #0f2240; }
.head { border-bottom: 3px solid #0b7f86; padding-bottom: 10px; margin-bottom: 14px; }
.head table { width: 100%; }
.brand { font-size: 18px; font-weight: bold; }
.code { font-size: 20px; font-weight: bold; color: #0b7f86; }
h2 { font-size: 13px; margin: 14px 0 6px; color: #0f2240; border-bottom: 1px solid #e1e7ef; padding-bottom: 4px; }
table.kv { width: 100%; border-collapse: collapse; }
table.kv td { padding: 4px 6px; vertical-align: top; border-bottom: 1px solid #eef2f6; }
table.kv td.k { width: 32%; color: #5a6b84; }
.badge { display: inline-block; padding: 3px 10px; border-radius: 10px; background: #e5f4ec; color: #17734a; font-weight: bold; }
.small { font-size: 9px; color: #5a6b84; }
.terms { font-size: 9.5px; color: #2f4363; }
</style></head><body>
<div class="head"><table><tr>
    <td><?php if ($logo): ?><img src="<?= e($logo) ?>" style="height:40px"><br><?php endif; ?><span class="brand"><?= e($siteName) ?></span><br><span class="small">Konaklama voucher’ı</span></td>
    <td style="text-align:right"><span class="small">Rezervasyon kodu</span><br><span class="code"><?= e($b['code']) ?></span><br><span class="badge"><?= e(status_label($b['status'])) ?></span></td>
</tr></table></div>
<table style="width:100%"><tr><td style="vertical-align:top;padding-right:16px">
    <h2>Otel</h2>
    <table class="kv">
        <tr><td class="k">Otel</td><td><strong><?= e($b['hotel_name']) ?></strong></td></tr>
        <tr><td class="k">Adres</td><td><?= e($b['address'] ?: '—') ?><?= $b['region_name'] ? ' (' . e($b['region_name']) . ')' : '' ?></td></tr>
        <?php if ($b['hotel_confirmation_no']): ?><tr><td class="k">Otel teyit no</td><td><strong><?= e($b['hotel_confirmation_no']) ?></strong></td></tr><?php endif; ?>
    </table>
    <h2>Konaklama</h2>
    <table class="kv">
        <tr><td class="k">Giriş</td><td><?= e(tr_date($b['check_in'], true)) ?><?= $b['check_in_time'] ? ' · ' . e($b['check_in_time']) : '' ?></td></tr>
        <tr><td class="k">Çıkış</td><td><?= e(tr_date($b['check_out'], true)) ?><?= $b['check_out_time'] ? ' · ' . e($b['check_out_time']) : '' ?></td></tr>
        <tr><td class="k">Süre</td><td><?= (int) $b['nights'] ?> gece</td></tr>
        <?php foreach ($rooms as $i => $r): ?><tr><td class="k"><?= $i + 1 ?>. oda</td><td><?= e($r['room_name']) ?><?= $r['concept_name'] ? ' · ' . e($r['concept_name']) : '' ?> — <?= (int) $r['adults'] ?> yetişkin<?= $r['children_ages'] !== '' ? ', çocuk yaşları: ' . e($r['children_ages']) : '' ?></td></tr><?php endforeach; ?>
    </table>
</td><td style="width:150px;vertical-align:top;text-align:center">
    <img src="<?= e($qr) ?>" style="width:140px;height:140px"><br><span class="small">Doğrulama için QR kodu okutun</span>
</td></tr></table>
<h2>Konuklar</h2>
<table class="kv"><?php foreach ($guests as $g): ?><tr><td class="k"><?= $g['is_lead'] ? 'Sorumlu misafir' : ($g['is_child'] ? 'Çocuk' : 'Misafir') ?></td><td><?= e(trim($g['first_name'] . ' ' . $g['last_name'])) ?><?= $g['is_child'] ? ' (' . (int) $g['age'] . ' yaş)' : '' ?></td></tr><?php endforeach; ?></table>
<h2>Ödeme</h2>
<table class="kv">
    <tr><td class="k">Toplam (vergiler dahil)</td><td><strong><?= e(money((int) $b['total_minor'], $b['currency'])) ?></strong></td></tr>
    <tr><td class="k">Ödeme durumu</td><td><?= e(['unpaid' => 'Ödenmedi', 'pay_at_hotel' => 'Otelde ödeme', 'invoiced' => 'Kurum faturası', 'paid' => 'Ödendi', 'refunded' => 'İade edildi'][$b['payment_status']] ?? $b['payment_status']) ?></td></tr>
</table>
<h2>Koşullar</h2>
<div class="terms">
    <p><strong>İptal:</strong> <?= nl2br(e(($terms['cancellation_policy'] ?? '') ?: ($terms['cancellation_summary'] ?? '—'))) ?></p>
    <p><strong>Ödeme:</strong> <?= nl2br(e(($terms['payment_terms'] ?? '') ?: '—')) ?></p>
    <?php if (!empty($terms['important_info'])): ?><p><strong>Önemli:</strong> <?= nl2br(e($terms['important_info'])) ?></p><?php endif; ?>
</div>
<p class="small" style="margin-top:16px">Destek: <?= e(implode(' · ', array_filter([$supportPhone, $supportEmail]))) ?: '—' ?> · Oluşturulma: <?= e(date('d.m.Y H:i')) ?></p>
</body></html>
