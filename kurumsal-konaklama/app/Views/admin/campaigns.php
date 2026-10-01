<?php $e = $edit; ?>
<div class="admin-title"><div><h1>Kampanyalar</h1><p>Kampanya fiyata yansıyorsa bir fiyat kuralına bağlayın. Süresi dolan veya kuralı pasif olan kampanya üyelere gösterilmez.</p></div></div>
<div class="table-wrap" style="margin-bottom:20px"><table class="table responsive"><thead><tr><th>Kampanya</th><th>Kapsam</th><th>Fiyat kuralı</th><th>Geçerlilik</th><th>Durum</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): $live = $r['is_active'] && strtotime($r['valid_from']) <= time() && strtotime($r['valid_to']) >= time() && ($r['rate_rule_id'] === null || $r['rule_active']); ?><tr>
    <td data-label="Kampanya"><strong><?= e($r['title']) ?></strong><div class="small muted"><?= e($r['summary']) ?></div></td>
    <td data-label="Kapsam"><?= e($r['hotel'] ?: ($r['region'] ?: 'Genel')) ?></td>
    <td data-label="Kural"><?= e($r['rule_name'] ?: '—') ?></td>
    <td data-label="Geçerlilik"><?= e(tr_datetime($r['valid_from'])) ?> – <?= e(tr_datetime($r['valid_to'])) ?></td>
    <td data-label="Durum"><span class="badge badge-<?= $live ? 'success' : 'neutral' ?>"><?= $live ? 'Yayında' : 'Yayında değil' ?></span></td>
    <td class="actions"><?php if (can('pricing.manage')): ?><div class="row" style="justify-content:flex-end;gap:6px"><a class="btn btn-secondary btn-sm" href="<?= e(url('/yonetim/kampanyalar', ['duzenle' => $r['id']])) ?>">Düzenle</a><form method="post" action="<?= e(url('/yonetim/kampanyalar/' . $r['id'] . '/sil')) ?>" data-confirm="Kampanya silinsin mi?"><?= csrf_field() ?><button class="btn btn-danger btn-sm"><?= icon('trash', 'icon-s') ?></button></form></div><?php endif; ?></td>
</tr><?php endforeach; ?><?php if (!$rows): ?><tr><td colspan="6" class="muted">Kampanya yok.</td></tr><?php endif; ?></tbody></table></div>
<?php if (can('pricing.manage')): ?>
<div class="card"><div class="card-head"><h2><?= $e ? 'Kampanyayı düzenle' : 'Yeni kampanya' ?></h2></div><div class="card-body">
<form method="post" action="<?= e(url('/yonetim/kampanyalar')) ?>" enctype="multipart/form-data"><?= csrf_field() ?><?php if ($e): ?><input type="hidden" name="id" value="<?= (int) $e['id'] ?>"><?php endif; ?>
<div class="form-grid form-grid-3">
    <?= f_input('title', 'Başlık', $e['title'] ?? '', 'text', ['required' => true]) ?>
    <?= f_select('hotel_id', 'Otel', $hotels, $e['hotel_id'] ?? '', 'Genel') ?>
    <?= f_select('region_id', 'Bölge', $regions, $e['region_id'] ?? '', 'Tümü') ?>
    <?= f_select('rate_rule_id', 'Bağlı fiyat kuralı', $rules, $e['rate_rule_id'] ?? '', 'Yok (bilgilendirme)') ?>
    <?= f_input('valid_from', 'Yayın başlangıcı', $e ? str_replace(' ', 'T', substr($e['valid_from'], 0, 16)) : date('Y-m-d\TH:i'), 'datetime-local', ['required' => true]) ?>
    <?= f_input('valid_to', 'Yayın bitişi', $e ? str_replace(' ', 'T', substr($e['valid_to'], 0, 16)) : '', 'datetime-local', ['required' => true]) ?>
    <?= f_input('stay_from', 'Konaklama başlangıcı', $e['stay_from'] ?? '', 'date') ?>
    <?= f_input('stay_to', 'Konaklama bitişi', $e['stay_to'] ?? '', 'date') ?>
    <?= f_input('sort', 'Sıra', $e['sort'] ?? 0, 'number') ?>
    <?= f_textarea('summary', 'Kısa özet', $e['summary'] ?? '', 2) ?>
    <?= f_textarea('description', 'Açıklama / koşullar', $e['description'] ?? '', 3) ?>
    <div class="field"><label for="image">Görsel</label><input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp"></div>
</div>
<?= f_check('is_active', 'Aktif', (bool) ($e['is_active'] ?? 1)) ?><?= f_check('show_on_home', 'Ana sayfada göster', (bool) ($e['show_on_home'] ?? 1)) ?>
<button class="btn" type="submit">KAYDET</button>
</form></div></div>
<?php endif; ?>
