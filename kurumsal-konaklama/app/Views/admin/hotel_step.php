<?php $h = $hotel; $hid = $h['id'] ?? null; $base = $hid ? '/yonetim/oteller/' . $hid : null; ?>
<div class="admin-title"><div><p><a href="<?= e(url('/yonetim/oteller')) ?>">← Oteller</a></p><h1><?= $h ? e($h['name']) : 'Yeni otel ekle' ?></h1><p>Adım <?= $step ?>/7 · <?= e($steps[$step]) ?></p></div>
<?php if ($h): ?><a class="btn btn-secondary" href="<?= e(url($base . '/onizleme')) ?>"><?= icon('eye') ?> Önizle</a><?php endif; ?></div>
<nav aria-label="Sihirbaz adımları"><ol class="wizard-steps">
<?php foreach ($steps as $n => $label): $reach = $h && ($n <= max((int) $h['wizard_step'], 1) || $h['status'] !== 'draft'); ?>
    <li><?php if ($reach): ?><a href="<?= e(url($base . '/adim/' . $n)) ?>"<?= $n === $step ? ' aria-current="step"' : '' ?> class="<?= $n < (int) ($h['wizard_step'] ?? 1) ? 'is-complete' : '' ?>"><span class="num"><?= $n ?></span><?= e($label) ?></a><?php else: ?><span<?= $n === $step ? ' aria-current="step" style="border-color:var(--c-teal);color:var(--c-ink)"' : '' ?>><span class="num"><?= $n ?></span><?= e($label) ?></span><?php endif; ?></li>
<?php endforeach; ?>
</ol></nav>
<?php if ($h): ?>
<div class="wizard-hotel"><?= icon('building') ?><div><strong><?= e($h['name']) ?></strong> düzenleniyor · <?= App\Core\View::partial('partials/status', ['status' => $h['status']]) ?></div>
<?php if ($missing): ?><span class="small" style="margin-left:auto"><?= icon('alert', 'icon-s') ?> Eksik: <?= e(implode(', ', $missing)) ?></span><?php endif; ?></div>
<?php endif; ?>

<?php if ($step === 1): ?>
<form method="post" action="<?= e(url($h ? $base . '/adim/1' : '/yonetim/oteller/yeni')) ?>" class="card"><div class="card-body"><?= csrf_field() ?>
    <div class="form-grid">
        <?= f_input('name', 'Otel adı', $h['name'] ?? '', 'text', ['required' => true]) ?>
        <div class="field"><label for="f_slug">Kısa adres (URL) <span aria-hidden="true" style="color:var(--c-danger)">*</span></label><input id="f_slug" name="slug" value="<?= e(old('slug', $h['slug'] ?? '')) ?>" data-slug-from="f_name" required<?= aria_error('slug') ?>><p class="hint">Adres: <?= e(url('/oteller/')) ?>…</p><?= field_error('slug') ?></div>
        <?= f_select('stars', 'Yıldız', ['1' => '1 yıldız', '2' => '2 yıldız', '3' => '3 yıldız', '4' => '4 yıldız', '5' => '5 yıldız'], $h['stars'] ?? '', 'Belirtilmemiş') ?>
        <?= f_select('booking_mode', 'Rezervasyon modu', ['instant' => 'Doğrulanmış stokla anında rezervasyon', 'request' => 'Otel teyidine bağlı rezervasyon talebi', 'offer' => 'Yalnız teklif usulü'], $h['booking_mode'] ?? 'request', '', 'Anında mod, geçerli anlaşma + satış yetkili fiyat planı + kontenjan gerektirir.', true) ?>
        <?= f_textarea('short_description', 'Kısa açıklama (kartlarda görünür)', $h['short_description'] ?? '', 2) ?>
        <?= f_textarea('description', 'Detaylı açıklama', $h['description'] ?? '', 7, 'Paragrafları boş satırla ayırın. Madde için satır başına “- ” yazın, alt başlık için “## ”.') ?>
        <div class="span-2"><?= f_check('is_contracted', 'Anlaşmalı otel', (bool) ($h['is_contracted'] ?? false), 'Otelle yazılı anlaşma varsa işaretleyin. Kesin fiyat yalnız anlaşmalı otellerde gösterilir.') ?></div>
        <?= f_input('contract_valid_until', 'Anlaşma bitiş tarihi', $h['contract_valid_until'] ?? '', 'date', [], 'Geçtiğinde fiyatlar otomatik “onaya bağlı” olur.') ?>
        <div class="field"><?= f_check('is_featured', 'Ana sayfada öne çıkar', (bool) ($h['is_featured'] ?? false)) ?></div>
        <?= f_input('featured_sort', 'Öne çıkarma sırası', $h['featured_sort'] ?? 0, 'number', ['min' => 0]) ?>
        <?= f_textarea('admin_notes', 'Yönetici notları (üyelere görünmez)', $h['admin_notes'] ?? '', 2) ?>
    </div>
</div><div class="wizard-actions"><span></span><div class="row"><?php if ($h): ?><button class="btn btn-secondary" type="submit" name="next" value="0">Taslak kaydet</button><?php endif; ?><button class="btn" type="submit" name="next" value="1"><?= $h ? 'Kaydet ve devam et' : 'Oluştur ve devam et' ?> <?= icon('arrow-right', 'icon-s') ?></button></div></div></form>

<?php elseif ($step === 2): ?>
<form method="post" action="<?= e(url($base . '/adim/2')) ?>" class="card"><div class="card-body"><?= csrf_field() ?>
    <div class="form-grid">
        <?= f_select('region_id', 'Bölge', $regions, $h['region_id'], 'Seçiniz', '', true) ?>
        <?= f_input('district', 'İlçe', $h['district']) ?>
        <?= f_input('neighborhood', 'Mahalle', $h['neighborhood']) ?>
        <?= f_input('address', 'Açık adres', $h['address']) ?>
        <?= f_input('latitude', 'Enlem (latitude)', $h['latitude'], 'text', ['inputmode' => 'decimal', 'placeholder' => '36.8573'], 'OpenStreetMap’te otele sağ tıklayıp “Burada ne var?” ile koordinatı alabilirsiniz.') ?>
        <?= f_input('longitude', 'Boylam (longitude)', $h['longitude'], 'text', ['inputmode' => 'decimal', 'placeholder' => '30.8155']) ?>
        <?= f_select('concept_id', 'Ana konsept', $concepts, $h['concept_id'], 'Belirtilmemiş') ?>
        <?= f_select('beach_type', 'Plaj', ['kum' => 'Kum plaj', 'cakil' => 'Çakıl plaj', 'kum_cakil' => 'Kum-çakıl', 'iskele' => 'İskele', 'platform' => 'Platform', 'yok' => 'Plaj yok'], $h['beach_type'], 'Belirtilmemiş') ?>
        <?= f_input('beach_info', 'Plaj bilgisi', $h['beach_info']) ?>
        <?= f_input('sea_distance_m', 'Denize uzaklık (m)', $h['sea_distance_m'], 'number', ['min' => 0], 'Denize sıfır için 0 girin.') ?>
        <?= f_input('airport_distance_km', 'Havalimanı uzaklığı (km)', $h['airport_distance_km'], 'text', ['inputmode' => 'decimal']) ?>
        <?= f_input('center_distance_km', 'Merkeze uzaklık (km)', $h['center_distance_km'], 'text', ['inputmode' => 'decimal']) ?>
        <?= f_input('video_url', 'Tanıtım videosu bağlantısı', $h['video_url'], 'url', ['placeholder' => 'https://']) ?>
    </div>
    <fieldset><legend>Tesis özellikleri</legend><div class="grid-3" style="gap:0 16px">
        <?php foreach ($amenities as $a): ?><label class="check"><input type="checkbox" name="amenities[]" value="<?= (int) $a['id'] ?>"<?= in_array((int) $a['id'], $selected, true) ? ' checked' : '' ?>><span><?= e($a['name']) ?><?= $a['filter_key'] ? ' <small class="muted">(filtre)</small>' : '' ?></span></label><?php endforeach; ?>
    </div></fieldset>
    <?php if ($h['latitude'] !== null): ?><div class="map-box" style="height:280px" data-map data-tiles="<?= e(setting('map.tile_url')) ?>" data-attribution="<?= e(setting('map.attribution')) ?>" data-items="<?= e(json_encode([['lat' => (float) $h['latitude'], 'lng' => (float) $h['longitude'], 'name' => $h['name'], 'region' => '', 'price' => null, 'url' => null]])) ?>"></div><?php endif; ?>
</div><div class="wizard-actions"><a class="btn btn-ghost" href="<?= e(url($base . '/adim/1')) ?>">← Geri</a><div class="row"><button class="btn btn-secondary" name="next" value="0">Taslak kaydet</button><button class="btn" name="next" value="1">Kaydet ve devam et →</button></div></div></form>

<?php elseif ($step === 3): ?>
<div class="card"><div class="card-body">
    <div class="alert alert-info"><?= icon('info') ?><div>Yalnız <strong>bu tesise ait</strong> ve kullanım hakkınız olan fotoğrafları yükleyin. Başka tesislerin veya yapay zekâ ile üretilmiş görseller gerçek tesis fotoğrafı gibi kullanılamaz. Fotoğraflar herkese açık klasörde tutulmaz; yalnız giriş yapmış üyelere sunulur.</div></div>
    <form method="post" action="<?= e(url($base . '/gorseller')) ?>" enctype="multipart/form-data" class="dropzone"><?= csrf_field() ?>
        <label for="photos" class="label">Fotoğraf seçin (JPG, PNG, WebP · en fazla 8 MB · birden fazla seçilebilir)</label>
        <input type="file" id="photos" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple required style="margin:10px auto;max-width:420px">
        <label class="check" style="justify-content:center"><input type="checkbox" name="owner_confirm" value="1" required><span>Fotoğraflar bu tesise aittir ve kullanım hakkına sahibim.</span></label>
        <button class="btn" type="submit"><?= icon('upload') ?> YÜKLE</button>
    </form>
</div></div>
<?php if ($images): ?>
<form method="post" action="<?= e(url($base . '/adim/3')) ?>" style="margin-top:16px"><?= csrf_field() ?>
    <div class="img-grid">
    <?php foreach ($images as $img): ?>
        <div class="img-item"><div class="ii-media"><img src="<?= e(url('/medya/otel/' . $img['id'] . '/thumb')) ?>" alt="<?= e($img['caption'] ?: 'Otel fotoğrafı') ?>" loading="lazy"><?php if ((int) $h['cover_image_id'] === (int) $img['id']): ?><span class="badge badge-gold ii-cover">Kapak</span><?php endif; ?></div>
        <div class="ii-body">
            <label class="check" style="min-height:auto;padding:0"><input type="radio" name="cover" value="<?= (int) $img['id'] ?>"<?= (int) $h['cover_image_id'] === (int) $img['id'] ? ' checked' : '' ?>> Kapak görseli</label>
            <label class="sr-only" for="cap<?= (int) $img['id'] ?>">Açıklama</label><input id="cap<?= (int) $img['id'] ?>" name="images[<?= (int) $img['id'] ?>][caption]" value="<?= e($img['caption']) ?>" placeholder="Açıklama (örn. Ana havuz)">
            <label class="sr-only" for="sort<?= (int) $img['id'] ?>">Sıra</label><input id="sort<?= (int) $img['id'] ?>" type="number" name="images[<?= (int) $img['id'] ?>][sort]" value="<?= (int) $img['sort'] ?>" aria-label="Sıra">
            <button class="btn btn-danger btn-sm" type="submit" form="del-<?= (int) $img['id'] ?>">Sil</button>
        </div></div>
    <?php endforeach; ?>
    </div>
    <div class="wizard-actions"><a class="btn btn-ghost" href="<?= e(url($base . '/adim/2')) ?>">← Geri</a><div class="row"><button class="btn btn-secondary" name="next" value="0">Kaydet</button><button class="btn" name="next" value="1">Kaydet ve devam et →</button></div></div>
</form>
<?php foreach ($images as $img): ?><form id="del-<?= (int) $img['id'] ?>" method="post" action="<?= e(url($base . '/gorseller/' . $img['id'])) ?>" data-confirm="Fotoğraf silinsin mi?"><?= csrf_field() ?><input type="hidden" name="islem" value="sil"></form><?php endforeach; ?>
<?php else: ?>
<div class="wizard-actions"><a class="btn btn-ghost" href="<?= e(url($base . '/adim/2')) ?>">← Geri</a><a class="btn btn-secondary" href="<?= e(url($base . '/adim/4')) ?>">Fotoğrafları sonra ekle →</a></div>
<?php endif; ?>

<?php elseif ($step === 4): ?>
<?php foreach ($rooms as $r): ?>
<details class="card" style="margin-bottom:12px"<?= count($rooms) === 1 ? ' open' : '' ?>><summary class="card-head" style="cursor:pointer"><h2><?= e($r['name']) ?></h2><span class="small muted"><?= (int) $r['max_adults'] ?> yet. · <?= (int) $r['max_children'] ?> çoc. · toplam <?= (int) $r['max_occupancy'] ?> · <?= $r['is_active'] ? 'Aktif' : 'Pasif' ?></span></summary><div class="card-body">
    <form method="post" action="<?= e(url('/yonetim/odalar/' . $r['id'])) ?>"><?= csrf_field() ?>
        <?= App\Core\View::partial('admin/room_fields', ['r' => $r, 'roomAmenities' => $roomAmenities, 'selected' => $roomAmenityIds[(int) $r['id']] ?? []]) ?>
        <button class="btn btn-secondary" type="submit">Odayı kaydet</button>
    </form>
    <h3 style="margin-top:16px;font-size:1rem">Oda fotoğrafları</h3>
    <div class="img-grid"><?php foreach ($roomImages[(int) $r['id']] ?? [] as $img): ?><div class="img-item"><div class="ii-media"><img src="<?= e(url('/medya/oda/' . $img['id'] . '/thumb')) ?>" alt="" loading="lazy"></div><div class="ii-body"><form method="post" action="<?= e(url('/yonetim/odalar/' . $r['id'] . '/gorseller/' . $img['id'] . '/sil')) ?>" data-confirm="Fotoğraf silinsin mi?"><?= csrf_field() ?><button class="btn btn-danger btn-sm">Sil</button></form></div></div><?php endforeach; ?></div>
    <form method="post" action="<?= e(url('/yonetim/odalar/' . $r['id'] . '/gorseller')) ?>" enctype="multipart/form-data" class="row" style="margin-top:10px"><?= csrf_field() ?><label class="sr-only" for="rp<?= (int) $r['id'] ?>">Oda fotoğrafı</label><input id="rp<?= (int) $r['id'] ?>" type="file" name="photos[]" multiple accept="image/jpeg,image/png,image/webp" required><label class="check" style="min-height:auto"><input type="checkbox" name="owner_confirm" value="1" required> Bu tesise aittir</label><button class="btn btn-secondary btn-sm" type="submit"><?= icon('upload', 'icon-s') ?> Yükle</button></form>
</div></details>
<?php endforeach; ?>
<div class="card"><div class="card-head"><h2><?= icon('plus') ?> Yeni oda ekle</h2></div><div class="card-body">
    <form method="post" action="<?= e(url($base . '/odalar')) ?>"><?= csrf_field() ?>
        <?= App\Core\View::partial('admin/room_fields', ['r' => null, 'roomAmenities' => $roomAmenities, 'selected' => []]) ?>
        <button class="btn" type="submit">ODAYI EKLE</button>
    </form>
</div></div>
<div class="wizard-actions"><a class="btn btn-ghost" href="<?= e(url($base . '/adim/3')) ?>">← Geri</a><a class="btn" href="<?= e(url($base . '/adim/5')) ?>">Devam et →</a></div>

<?php elseif ($step === 5): ?>
<form method="post" action="<?= e(url($base . '/adim/5')) ?>" class="card"><div class="card-body"><?= csrf_field() ?>
    <div class="form-grid">
        <?= f_input('check_in_time', 'Giriş saati', $h['check_in_time'] ?? '14:00', 'time') ?>
        <?= f_input('check_out_time', 'Çıkış saati', $h['check_out_time'] ?? '12:00', 'time') ?>
        <?= f_textarea('child_policy', 'Çocuk politikası', $h['child_policy'], 3) ?>
        <?= f_input('pet_policy', 'Evcil hayvan politikası', $h['pet_policy']) ?>
        <div></div>
        <?= f_textarea('cancellation_policy', 'İptal politikası', $h['cancellation_policy'], 4, 'Fiyat planında ayrı iptal koşulu yoksa bu metin kullanılır.') ?>
        <?= f_textarea('payment_policy', 'Ödeme politikası', $h['payment_policy'], 3) ?>
        <?= f_textarea('important_info', 'Önemli bilgiler', $h['important_info'], 3) ?>
        <?= f_input('vat_bp', 'KDV oranı (%)', bp_input($h['vat_bp'] !== null ? (int) $h['vat_bp'] : null), 'text', ['placeholder' => 'Genel ayar: %' . bp_input((int) setting('pricing.vat_bp', '1000'))], 'Boş bırakılırsa sistem ayarı kullanılır.') ?>
        <?= f_input('accommodation_tax_bp', 'Konaklama vergisi (%)', bp_input($h['accommodation_tax_bp'] !== null ? (int) $h['accommodation_tax_bp'] : null), 'text', ['placeholder' => 'Genel ayar: %' . bp_input((int) setting('pricing.accommodation_tax_bp', '200'))]) ?>
    </div>
</div><div class="wizard-actions"><a class="btn btn-ghost" href="<?= e(url($base . '/adim/4')) ?>">← Geri</a><div class="row"><button class="btn btn-secondary" name="next" value="0">Taslak kaydet</button><button class="btn" name="next" value="1">Kaydet ve devam et →</button></div></div></form>

<?php elseif ($step === 6): ?>
<div class="grid-2">
    <div class="card"><div class="card-head"><h2>Fiyat planları</h2><a class="btn btn-secondary btn-sm" href="<?= e(url('/yonetim/fiyatlar', ['otel' => $hid])) ?>">Fiyatları yönet</a></div><div class="card-body">
        <?php if (!$plans): ?><p class="muted">Henüz fiyat planı yok. Anlaşmalı fiyat yoksa otel “teklif iste” akışıyla çalışır.</p><?php endif; ?>
        <?php foreach ($plans as $p): ?><div class="panel" style="margin-bottom:8px"><strong><?= e($p['room_name']) ?> · <?= e($p['name']) ?></strong><div class="small muted"><?= e($p['concept_name'] ?: '—') ?> · <?= $p['is_bookable'] ? 'Satış yetkili (kesin fiyat)' : 'Satış yetkisi yok' ?> · Önümüzdeki 90 günde <?= (int) $p['rate_days'] ?> gün fiyatlı</div></div><?php endforeach; ?>
    </div></div>
    <div class="card"><div class="card-head"><h2>Kontenjan</h2><a class="btn btn-secondary btn-sm" href="<?= e(url('/yonetim/kontenjan', ['otel' => $hid])) ?>">Kontenjanı yönet</a></div><div class="card-body">
        <?php foreach ($inv as $i): ?><div class="panel" style="margin-bottom:8px"><strong><?= e($i['name']) ?></strong><div class="small muted">90 günde <?= (int) $i['days'] ?> gün tanımlı · önümüzdeki 30 günde toplam boş oda-gece: <?= (int) $i['free30'] ?></div></div><?php endforeach; ?>
        <?php if (!$inv): ?><p class="muted">Önce oda ekleyin.</p><?php endif; ?>
    </div></div>
</div>
<div class="wizard-actions"><a class="btn btn-ghost" href="<?= e(url($base . '/adim/5')) ?>">← Geri</a><a class="btn" href="<?= e(url($base . '/adim/7')) ?>">Devam et →</a></div>

<?php else: ?>
<div class="card"><div class="card-body stack">
    <?php if ($missing): ?><div class="alert alert-warning"><?= icon('alert') ?><div><strong>Yayın için eksik bilgiler:</strong><ul style="margin:6px 0 0;padding-left:18px"><?php foreach ($missing as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul></div></div>
    <?php else: ?><div class="alert alert-success"><?= icon('check-circle') ?><div>Zorunlu bilgiler tamam. Önizlemeyi kontrol edip yayına alabilirsiniz.</div></div><?php endif; ?>
    <div class="row">
        <a class="btn btn-secondary" href="<?= e(url($base . '/onizleme')) ?>" target="_blank"><?= icon('eye') ?> ÖNİZLE</a>
        <?php if (can('hotels.publish')): ?>
            <?php if ($h['status'] !== 'published'): ?><form method="post" action="<?= e(url($base . '/yayin')) ?>"><?= csrf_field() ?><input type="hidden" name="islem" value="yayinla"><button class="btn btn-lg" type="submit"<?= $missing ? ' disabled' : '' ?>>YAYINA AL</button></form>
            <?php else: ?><form method="post" action="<?= e(url($base . '/yayin')) ?>" data-confirm="Otel yayından kaldırılsın mı?"><?= csrf_field() ?><input type="hidden" name="islem" value="kaldir"><button class="btn btn-danger" type="submit">YAYINDAN KALDIR</button></form><?php endif; ?>
        <?php else: ?><p class="muted">Yayına alma yetkiniz yok; yetkili yöneticiye iletin.</p><?php endif; ?>
    </div>
</div></div>
<?php endif; ?>
