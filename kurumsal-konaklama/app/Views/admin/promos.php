<?php $e = $edit; ?>
<div class="admin-title"><div><h1>Promosyon kodları</h1><p>Promosyon, kurallar ve üye indiriminden sonra toplam tutara uygulanır; azami indirim sınırını aşamaz.</p></div></div>
<div class="table-wrap" style="margin-bottom:20px"><table class="table responsive"><thead><tr><th>Kod</th><th>İndirim</th><th>Kapsam</th><th>Kullanım</th><th>Geçerlilik</th><th>Durum</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr><td data-label="Kod"><span class="code"><?= e($r['code']) ?></span><div class="small muted"><?= e($r['description']) ?></div></td>
<td data-label="İndirim"><?= $r['adjustment'] === 'discount_percent' ? e(percent((int) $r['value'])) : e(money((int) $r['value'])) ?></td>
<td data-label="Kapsam"><?= e(implode(' · ', array_filter([$r['inst'], $r['hotel'], $r['min_nights'] ? 'en az ' . $r['min_nights'] . ' gece' : null])) ?: 'Genel') ?></td>
<td data-label="Kullanım"><?= (int) $r['used_count'] ?><?= $r['max_uses'] ? ' / ' . (int) $r['max_uses'] : '' ?></td>
<td data-label="Geçerlilik" class="small"><?= e($r['valid_from'] ? tr_datetime($r['valid_from']) : '—') ?> – <?= e($r['valid_to'] ? tr_datetime($r['valid_to']) : '—') ?></td>
<td data-label="Durum"><?= $r['is_active'] ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge">Pasif</span>' ?></td>
<td class="actions"><?php if (can('pricing.manage')): ?><div class="row" style="justify-content:flex-end;gap:6px"><a class="btn btn-secondary btn-sm" href="<?= e(url('/yonetim/promosyonlar', ['duzenle' => $r['id']])) ?>">Düzenle</a><form method="post" action="<?= e(url('/yonetim/promosyonlar/' . $r['id'] . '/sil')) ?>" data-confirm="Kod silinsin mi?"><?= csrf_field() ?><button class="btn btn-danger btn-sm"><?= icon('trash', 'icon-s') ?></button></form></div><?php endif; ?></td></tr><?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="7" class="muted">Promosyon kodu yok.</td></tr><?php endif; ?></tbody></table></div>
<?php if (can('pricing.manage')): ?>
<div class="card"><div class="card-head"><h2><?= $e ? 'Kodu düzenle' : 'Yeni promosyon kodu' ?></h2></div><div class="card-body">
<form method="post" action="<?= e(url('/yonetim/promosyonlar')) ?>"><?= csrf_field() ?><?php if ($e): ?><input type="hidden" name="id" value="<?= (int) $e['id'] ?>"><?php endif; ?>
<div class="form-grid form-grid-3">
    <?= f_input('code', 'Kod', $e['code'] ?? '', 'text', ['required' => true, 'style' => 'text-transform:uppercase']) ?>
    <?= f_select('adjustment', 'Tür', ['discount_percent' => '% indirim', 'discount_amount' => 'Sabit TL indirim'], $e['adjustment'] ?? 'discount_percent') ?>
    <?= f_input('value', 'Değer', $e ? ($e['adjustment'] === 'discount_percent' ? bp_input((int) $e['value']) : money_input((int) $e['value'])) : '', 'text', ['required' => true]) ?>
    <?= f_input('description', 'Açıklama', $e['description'] ?? '') ?>
    <?= f_select('institution_id', 'Kurum', $institutions, $e['institution_id'] ?? '', 'Tüm kurumlar') ?>
    <?= f_select('hotel_id', 'Otel', $hotels, $e['hotel_id'] ?? '', 'Tüm oteller') ?>
    <?= f_input('max_uses', 'Toplam kullanım limiti', $e['max_uses'] ?? '', 'number', ['min' => 1]) ?>
    <?= f_input('per_user_limit', 'Kişi başı limit', $e['per_user_limit'] ?? '', 'number', ['min' => 1]) ?>
    <?= f_input('min_nights', 'En az gece', $e['min_nights'] ?? '', 'number', ['min' => 1]) ?>
    <?= f_input('valid_from', 'Başlangıç', $e && $e['valid_from'] ? str_replace(' ', 'T', substr($e['valid_from'], 0, 16)) : '', 'datetime-local') ?>
    <?= f_input('valid_to', 'Bitiş', $e && $e['valid_to'] ? str_replace(' ', 'T', substr($e['valid_to'], 0, 16)) : '', 'datetime-local') ?>
</div>
<?= f_check('is_active', 'Aktif', (bool) ($e['is_active'] ?? 1)) ?>
<button class="btn" type="submit">KAYDET</button>
</form></div></div>
<?php endif; ?>
