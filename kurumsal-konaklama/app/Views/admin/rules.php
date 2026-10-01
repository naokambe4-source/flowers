<?php use App\Controllers\Admin\RuleController as RC; $e = $edit; ?>
<div class="admin-title"><div><h1>Fiyat kuralları</h1><p>Kurallar gece bazında uygulanır.</p></div></div>
<div class="alert alert-info"><?= icon('info') ?><div><strong>Uygulama sırası:</strong> 1) Kaynak gecelik fiyat → 2) Uygun kurallar yüksek öncelikten düşüğe uygulanır. “Birlikte uygulanamaz” kurallardan yalnız <em>en yüksek öncelikli</em> olanı (eşitlikte en avantajlısı) seçilir; “birlikte uygulanabilir” kuralların tamamı sırayla ve bir öncekinin sonucuna uygulanır → 3) Üye indirimi (kurum → fiyat grubu → genel, yönetim ayarı) → 4) Toplam indirim “azami indirim” ayarını aşamaz → 5) Promosyon kodu → 6) Vergiler. Hesap dökümü her rezervasyonda saklanır.</div></div>
<div class="table-wrap" style="margin-bottom:20px"><table class="table responsive"><thead><tr><th>Kural</th><th>Kapsam</th><th>Uygulama</th><th>Koşullar</th><th>Öncelik</th><th>Durum</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $r): ?><tr>
    <td data-label="Kural"><strong><?= e($r['name']) ?></strong><div class="small muted"><?= e(RC::KINDS[$r['kind']]) ?></div></td>
    <td data-label="Kapsam"><?= e(implode(' · ', array_filter([$r['inst'], $r['pg'], $r['hotel'], $r['room']])) ?: 'Tüm üyeler / oteller') ?></td>
    <td data-label="Uygulama"><?= str_ends_with($r['adjustment'], 'percent') ? e(percent((int) $r['value'])) : e(money((int) $r['value'])) ?> <?= e(str_starts_with($r['adjustment'], 'discount') ? 'indirim' : 'ek ücret') ?></td>
    <td data-label="Koşullar" class="small"><?= e(implode(' · ', array_filter([
        $r['stay_from'] ? 'Konaklama ' . tr_date_short($r['stay_from']) . '–' . tr_date_short($r['stay_to']) : null,
        $r['weekdays'] ? 'Günler: ' . implode(',', array_map(static fn ($d) => TR_DAYS_SHORT[(int) $d], explode(',', $r['weekdays']))) : null,
        $r['season'] ? 'Sezon: ' . $r['season'] : null,
        $r['min_nights'] ? 'En az ' . $r['min_nights'] . ' gece' : null,
        $r['min_lead_days'] !== null ? 'Girişe ≥' . $r['min_lead_days'] . ' gün' : null,
        $r['max_lead_days'] !== null ? 'Girişe ≤' . $r['max_lead_days'] . ' gün' : null,
    ])) ?: '—') ?></td>
    <td data-label="Öncelik"><?= (int) $r['priority'] ?><div class="small muted"><?= $r['stackable'] ? 'Birlikte uygulanır' : 'Tekil' ?></div></td>
    <td data-label="Durum"><?= $r['is_active'] ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge">Pasif</span>' ?></td>
    <td class="actions"><?php if (can('pricing.manage')): ?><div class="row" style="justify-content:flex-end;gap:6px"><a class="btn btn-secondary btn-sm" href="<?= e(url('/yonetim/fiyat-kurallari', ['duzenle' => $r['id']])) ?>">Düzenle</a><form method="post" action="<?= e(url('/yonetim/fiyat-kurallari/' . $r['id'] . '/sil')) ?>" data-confirm="Kural silinsin mi?"><?= csrf_field() ?><button class="btn btn-danger btn-sm"><?= icon('trash', 'icon-s') ?></button></form></div><?php endif; ?></td>
</tr><?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="7" class="muted">Henüz kural yok. Yalnız üye indirimi uygulanır.</td></tr><?php endif; ?>
</tbody></table></div>
<?php if (can('pricing.manage')): ?>
<div class="card"><div class="card-head"><h2><?= $e ? 'Kuralı düzenle' : 'Yeni kural' ?></h2></div><div class="card-body">
<form method="post" action="<?= e(url('/yonetim/fiyat-kurallari')) ?>"><?= csrf_field() ?><?php if ($e): ?><input type="hidden" name="id" value="<?= (int) $e['id'] ?>"><?php endif; ?>
    <div class="form-grid form-grid-3">
        <?= f_input('name', 'Kural adı', $e['name'] ?? '', 'text', ['required' => true]) ?>
        <?= f_select('kind', 'Kural türü', RC::KINDS, $e['kind'] ?? 'global', '', '', true) ?>
        <?= f_select('adjustment', 'Uygulama', RC::ADJ, $e['adjustment'] ?? 'discount_percent', '', '', true) ?>
        <?= f_input('value', 'Değer (yüzde veya TL)', $e ? (str_ends_with($e['adjustment'], 'percent') ? bp_input((int) $e['value']) : money_input((int) $e['value'])) : '', 'text', ['required' => true, 'placeholder' => '5 veya 250,00']) ?>
        <?= f_input('priority', 'Öncelik (yüksek önce)', $e['priority'] ?? 100, 'number', ['min' => 0]) ?>
        <div class="field"><?= f_check('stackable', 'Diğer kurallarla birlikte uygulanabilir', (bool) ($e['stackable'] ?? 1)) ?><?= f_check('is_active', 'Aktif', (bool) ($e['is_active'] ?? 1)) ?></div>
        <?= f_select('institution_id', 'Kurum', $institutions, $e['institution_id'] ?? '', 'Tüm kurumlar') ?>
        <?= f_select('price_group_id', 'Fiyat grubu', $groups, $e['price_group_id'] ?? '', 'Tümü') ?>
        <?= f_select('hotel_id', 'Otel', $hotels, $e['hotel_id'] ?? '', 'Tüm oteller') ?>
        <?= f_select('room_id', 'Oda', $rooms, $e['room_id'] ?? '', 'Tüm odalar') ?>
        <?= f_select('season_id', 'Sezon / bayram', $seasons, $e['season_id'] ?? '', 'Yok') ?>
        <?= f_input('min_nights', 'En az gece (uzun konaklama)', $e['min_nights'] ?? '', 'number', ['min' => 1]) ?>
        <?= f_input('stay_from', 'Konaklama başlangıcı', $e['stay_from'] ?? '', 'date') ?>
        <?= f_input('stay_to', 'Konaklama bitişi', $e['stay_to'] ?? '', 'date') ?>
        <div></div>
        <?= f_input('min_lead_days', 'Erken rezervasyon: girişe en az (gün)', $e['min_lead_days'] ?? '', 'number', ['min' => 0]) ?>
        <?= f_input('max_lead_days', 'Son dakika: girişe en fazla (gün)', $e['max_lead_days'] ?? '', 'number', ['min' => 0]) ?>
        <div></div>
        <?= f_input('valid_from', 'Satış geçerlilik başlangıcı', $e && $e['valid_from'] ? str_replace(' ', 'T', substr($e['valid_from'], 0, 16)) : '', 'datetime-local') ?>
        <?= f_input('valid_to', 'Satış geçerlilik bitişi', $e && $e['valid_to'] ? str_replace(' ', 'T', substr($e['valid_to'], 0, 16)) : '', 'datetime-local') ?>
    </div>
    <?php $wd = $e && $e['weekdays'] ? array_map('intval', explode(',', $e['weekdays'])) : range(1, 7); ?>
    <fieldset><legend>Geçerli günler (gece bazında)</legend><div class="row"><?php foreach ([1 => 'Pzt', 2 => 'Sal', 3 => 'Çar', 4 => 'Per', 5 => 'Cum', 6 => 'Cmt', 7 => 'Paz'] as $n => $l): ?><label class="check" style="min-height:36px"><input type="checkbox" name="weekdays[]" value="<?= $n ?>"<?= in_array($n, $wd, true) ? ' checked' : '' ?>> <?= $l ?></label><?php endforeach; ?></div><p class="hint">Hafta sonu türünde gün seçmezseniz Cuma ve Cumartesi geceleri kullanılır.</p></fieldset>
    <button class="btn" type="submit">KURALI KAYDET</button>
</form></div></div>
<?php endif; ?>
