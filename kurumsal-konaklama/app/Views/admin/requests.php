<div class="admin-title"><div><h1>Konaklama talepleri</h1><p>Manuel akış: Talep → Teklif → Üye kabulü → Otel teyidi → Onay</p></div></div>
<div class="seg" style="margin-bottom:16px"><?php foreach (['' => 'Tümü', 'new' => 'Yeni', 'offered' => 'Teklif gönderildi', 'converted' => 'Rezervasyona dönüştü', 'declined' => 'Reddedildi', 'expired' => 'Süresi doldu', 'cancelled' => 'İptal'] as $k => $l): ?><a href="<?= e(url('/yonetim/talepler', $k !== '' ? ['durum' => $k] : [])) ?>" aria-current="<?= $st === $k ? 'true' : 'false' ?>"><?= e($l) ?></a><?php endforeach; ?></div>
<?php if (!$rows): ?><div class="empty"><?= icon('document', 'icon-l') ?><h3>Talep yok</h3></div><?php else: ?>
<div class="table-wrap"><table class="table responsive"><thead><tr><th>Kod</th><th>Üye</th><th>Otel / bölge</th><th>Tarihler</th><th>Konuk</th><th>Hedef</th><th>Durum</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr>
    <td data-label="Kod"><strong><?= e($r['code']) ?></strong><div class="small muted"><?= e(tr_datetime($r['created_at'])) ?></div></td>
    <td data-label="Üye"><?= e($r['member']) ?><div class="small muted"><?= e($r['institution_name'] ?: '—') ?></div></td>
    <td data-label="Otel"><?= e($r['hotel_name'] ?: ($r['region_name'] ?: '—')) ?></td>
    <td data-label="Tarihler"><?= e(tr_date_short($r['check_in'])) ?> – <?= e(tr_date_short($r['check_out'])) ?></td>
    <td data-label="Konuk"><?= (int) $r['adults'] ?>+<?= (int) $r['children'] ?></td>
    <td data-label="Hedef" class="num"><?= $r['target_minor'] ? e(money((int) $r['target_minor'])) : '—' ?></td>
    <td data-label="Durum"><?= App\Core\View::partial('partials/status', ['status' => $r['status']]) ?></td>
    <td class="actions"><a class="btn btn-secondary btn-sm" href="<?= e(url('/yonetim/talepler/' . $r['id'])) ?>">Aç</a></td>
</tr><?php endforeach; ?></tbody></table></div>
<?= pagination_links($page, $query) ?>
<?php endif; ?>
