<div class="admin-title"><div><p><a href="<?= e(url('/yonetim/api')) ?>">← API yönetimi</a></p><h1><?= e($p['name']) ?></h1><p><?= e($p['notes']) ?></p></div><form method="post" action="<?= e(url('/yonetim/api/' . $p['id'] . '/test')) ?>"><?= csrf_field() ?><button class="btn">Bağlantı testi</button></form></div>
<div class="grid-2">
<div class="card"><div class="card-head"><h2>Ayarlar ve yetkiler</h2></div><div class="card-body">
<form method="post" action="<?= e(url('/yonetim/api/' . $p['id'])) ?>" autocomplete="off"><?= csrf_field() ?>
    <?php if ($p['code'] !== 'manual'): ?>
    <?= f_check('is_enabled', 'Sağlayıcı etkin', (bool) $p['is_enabled']) ?>
    <?= f_check('display_authorized', 'Fiyat verisinin üyelere gösterim (ticari kullanım) izni yazılı olarak doğrulandı', (bool) $p['display_authorized']) ?>
    <?= f_check('booking_authorized', 'Sağlayıcı üzerinden satış / rezervasyon yetkisi sözleşmeyle doğrulandı', (bool) $p['booking_authorized'], $adapter->supports(App\Providers\Contracts\Capability::BOOKING) ? '' : 'Bu adaptör rezervasyon oluşturmayı desteklemez.') ?>
    <input type="hidden" name="live_search" value="0"><label class="check"><input type="checkbox" name="live_search" value="1"<?= setting('providers.live_search') === '1' ? ' checked' : '' ?>><span>Aramalarda canlı sağlayıcı fiyatlarını kullan (genel ayar)</span></label>
    <?php endif; ?>
    <?= f_input('rate_limit_per_minute', 'Dakikalık istek sınırı', $p['rate_limit_per_minute'], 'number', ['min' => 1, 'max' => 600]) ?>
    <?php foreach ($settings as $k => $v): if (is_array($v)) continue; ?>
        <?php if ($k === 'apply_member_discount'): ?><input type="hidden" name="settings[apply_member_discount]" value="0"><label class="check"><input type="checkbox" name="settings[apply_member_discount]" value="1"<?= $v ? ' checked' : '' ?>> Yetkili sağlayıcı fiyatına üye indirimi uygula</label><?php continue; endif; ?>
        <div class="field"><label for="s_<?= e($k) ?>"><?= e(['base_url' => 'Servis adresi', 'timeout' => 'Zaman aşımı (sn)', 'customer_ip' => 'Customer-Ip başlığı', 'payment_type' => 'Ödeme tipi (sözleşmeye göre)'][$k] ?? $k) ?></label><input id="s_<?= e($k) ?>" name="settings[<?= e($k) ?>]" value="<?= e((string) $v) ?>"></div>
    <?php endforeach; ?>
    <?php if ($p['code'] === 'expedia_rapid' && !array_key_exists('payment_type', $settings)): ?><div class="field"><label for="s_pt">Ödeme tipi (boşsa rezervasyon kapalı)</label><input id="s_pt" name="settings[payment_type]" value=""></div><?php endif; ?>
    <?php if ($fields): ?><fieldset><legend>Kimlik bilgileri</legend>
        <p class="small muted">Kaydedilen anahtarlar tekrar gösterilmez. Değiştirmek için yeni değeri yazın; boş bırakılırsa mevcut değer korunur.</p>
        <?php foreach ($fields as $k => $label): ?><div class="field"><label for="c_<?= e($k) ?>"><?= e($label) ?></label><input type="password" id="c_<?= e($k) ?>" name="cred[<?= e($k) ?>]" value="" autocomplete="new-password" placeholder="<?= isset($stored[$k]) ? 'Kayıtlı (…' . e($stored[$k]['last4']) . ') · ' . e(tr_datetime($stored[$k]['updated_at'])) : 'Henüz girilmedi' ?>"></div><?php endforeach; ?>
    </fieldset><?php endif; ?>
    <?= f_textarea('notes', 'Notlar (sözleşme bilgisi, izin tarihi vb.)', $p['notes'], 3, '', false) ?>
    <button class="btn" type="submit">KAYDET</button>
</form></div></div>
<div class="stack">
<?php if ($adapter->supports(App\Providers\Contracts\Capability::DESTINATIONS) && $p['code'] !== 'manual'): ?>
<div class="card" id="destinasyon"><div class="card-head"><h2>Antalya bölge eşleştirme</h2></div><div class="card-body">
    <p class="small muted">Her bölgeyi sağlayıcının gerçek destinasyon ID’si ile eşleyin. Aynı isimli başka ülke/şehir sonuçları uyarı ile gösterilir ve ek onay olmadan kabul edilmez.</p>
    <form method="post" action="<?= e(url('/yonetim/api/' . $p['id'] . '/destinasyon-ara')) ?>" class="row" style="align-items:flex-end"><?= csrf_field() ?>
        <div class="field" style="flex:1"><label for="dq">Arama</label><input id="dq" name="q" value="<?= e($destResults['q'] ?? 'Antalya') ?>"></div>
        <div class="field"><label for="dr">Bölge</label><select id="dr" name="region_id"><?php foreach ($regions as $r): ?><option value="<?= (int) $r['id'] ?>"<?= (int) ($destResults['region_id'] ?? 0) === (int) $r['id'] ? ' selected' : '' ?>><?= e($r['name']) ?></option><?php endforeach; ?></select></div>
        <button class="btn btn-secondary" style="margin-bottom:16px">Ara</button>
    </form>
    <?php if ($destResults): $reg = null; foreach ($regions as $r) { if ((int) $r['id'] === (int) $destResults['region_id']) { $reg = $r; } } ?>
        <?php if (!$destResults['items']): ?><p class="muted">Sonuç yok.</p><?php endif; ?>
        <?php foreach ($destResults['items'] as $it):
            $foreign = strtoupper((string) $it['country_code']) !== 'TR';
            $far = null;
            if ($reg && $reg['latitude'] !== null && $it['latitude'] !== null) { $far = round(6371 * 2 * asin(sqrt(sin(deg2rad(($it['latitude'] - $reg['latitude']) / 2)) ** 2 + cos(deg2rad((float) $reg['latitude'])) * cos(deg2rad($it['latitude'])) * sin(deg2rad(($it['longitude'] - $reg['longitude']) / 2)) ** 2))); }
        ?>
            <form method="post" action="<?= e(url('/yonetim/api/' . $p['id'] . '/destinasyon')) ?>" class="panel" style="margin-bottom:8px"><?= csrf_field() ?>
                <input type="hidden" name="region_id" value="<?= (int) $destResults['region_id'] ?>"><input type="hidden" name="external_id" value="<?= e($it['external_id']) ?>"><input type="hidden" name="external_name" value="<?= e($it['label']) ?>"><input type="hidden" name="country_code" value="<?= e($it['country_code']) ?>"><input type="hidden" name="external_type" value="<?= e($it['type']) ?>">
                <div class="row-between"><div><strong><?= e($it['label']) ?></strong><div class="small muted">ID: <?= e($it['external_id']) ?> · tür: <?= e($it['type'] ?: '—') ?> · ülke: <?= e($it['country_code'] ?: '?') ?><?= $far !== null ? ' · bölge merkezine ~' . (int) $far . ' km' : '' ?></div>
                <?php if ($foreign): ?><p class="field-error"><?= icon('alert', 'icon-s') ?> Türkiye dışı veya ülkesi belirsiz sonuç!</p><label class="check" style="min-height:auto"><input type="checkbox" name="confirm_foreign" value="1"> Yine de eşle (doğruladım)</label><?php elseif ($far !== null && $far > 60): ?><p class="field-error"><?= icon('alert', 'icon-s') ?> Bölge merkezinden uzak — doğru yer olduğundan emin olun.</p><?php endif; ?></div>
                <button class="btn btn-sm" type="submit">Bu sonucu eşle</button></div>
            </form>
        <?php endforeach; ?>
    <?php endif; ?>
    <div class="table-wrap" style="margin-top:12px"><table class="table"><thead><tr><th>Bölge</th><th>Eşlenen destinasyon</th></tr></thead><tbody><?php foreach ($regions as $r): ?><tr><td><?= $r['parent_id'] ? '— ' : '' ?><?= e($r['name']) ?></td><td><?= $r['external_id'] ? e($r['external_name']) . ' <span class="small muted">(' . e($r['external_id']) . ', ' . e($r['country_code'] ?: '?') . ')</span>' : '<span class="muted">Eşlenmedi</span>' ?></td></tr><?php endforeach; ?></tbody></table></div>
</div></div>
<?php endif; ?>
<?php if ($p['code'] !== 'manual'): ?>
<div class="card" id="oteller"><div class="card-head"><h2>Otel eşleştirme</h2></div><div class="card-body">
    <p class="small muted">Yerel oteli sağlayıcıdaki kimliğiyle eşleyin. Fiyatlar yalnız eşlenmiş oteller için alınır. Eşlemeyi kaldırmak için kimliği boş bırakıp kaydedin.</p>
    <?php foreach ($hotelMaps as $hm): ?><form method="post" action="<?= e(url('/yonetim/api/' . $p['id'] . '/otel-esle')) ?>" class="row" style="margin-bottom:8px"><?= csrf_field() ?><input type="hidden" name="hotel_id" value="<?= (int) $hm['id'] ?>"><span style="flex:1;min-width:160px"><strong><?= e($hm['name']) ?></strong></span><label class="sr-only" for="ext<?= (int) $hm['id'] ?>">Dış kimlik</label><input id="ext<?= (int) $hm['id'] ?>" name="external_hotel_id" value="<?= e($hm['external_hotel_id']) ?>" placeholder="Dış otel ID" style="width:160px"><input name="external_name" value="<?= e($hm['external_name']) ?>" placeholder="Sağlayıcıdaki ad" aria-label="Sağlayıcıdaki ad" style="width:180px"><button class="btn btn-secondary btn-sm">Kaydet</button></form><?php endforeach; ?>
</div></div>
<?php endif; ?>
<div class="card"><div class="card-head"><h2>Son istekler</h2></div><div class="table-wrap" style="border:0"><table class="table"><tbody><?php foreach ($logs as $l): ?><tr><td class="small"><?= e(tr_datetime($l['created_at'])) ?> · <?= e($l['operation']) ?><br><span class="muted"><?= e($l['endpoint']) ?></span><?php if ($l['error']): ?><br><span style="color:var(--c-danger)"><?= e($l['error']) ?></span><?php endif; ?></td><td><span class="badge badge-<?= $l['success'] ? 'success' : 'danger' ?>"><?= e($l['status_code'] ?? 'ağ') ?></span></td></tr><?php endforeach; ?><?php if (!$logs): ?><tr><td class="muted">Henüz istek yapılmadı.</td></tr><?php endif; ?></tbody></table></div></div>
</div>
</div>
