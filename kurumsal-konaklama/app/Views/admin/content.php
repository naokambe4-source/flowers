<?php use App\Controllers\Admin\ContentController as CC; ?>
<div class="admin-title"><div><h1>İçerik yönetimi</h1><p>Marka, giriş sayfası, ana sayfa, iletişim ve yasal metinler. Boş bırakılan iletişim bilgileri sitede gösterilmez.</p></div></div>
<div class="grid-2">
<form method="post" action="<?= e(url('/yonetim/icerik/ayarlar')) ?>" enctype="multipart/form-data" class="card"><div class="card-head"><h2>Site içerikleri</h2></div><div class="card-body"><?= csrf_field() ?>
    <?php foreach (CC::TEXT_KEYS as $k => [$label, $type]): $f = str_replace('.', '__', $k); ?>
        <?php if ($type === 'textarea'): ?><?= f_textarea($f, $label, $values[$k], 3, '', false) ?><?php else: ?><?= f_input($f, $label, $values[$k], $type === 'tel' ? 'tel' : ($type === 'email' ? 'email' : ($type === 'url' ? 'url' : 'text'))) ?><?php endif; ?>
    <?php endforeach; ?>
    <h3>Görseller</h3>
    <p class="small muted">Otel fotoğrafları burada değil, otel sihirbazında yüklenir. Görsel yüklenmezse temsili illüstrasyon “Temsili” etiketiyle gösterilir.</p>
    <?php foreach (CC::IMAGE_KEYS as $k => $label): $f = str_replace('.', '__', $k); ?>
        <div class="field"><label for="<?= $f ?>"><?= e($label) ?></label>
            <?php if ($values[$k] !== ''): ?><img src="<?= e(url('/medya/site/' . str_replace('.', '_', $k))) ?>" alt="" style="max-height:80px;max-width:220px;border-radius:8px;object-fit:cover"><label class="check" style="min-height:auto"><input type="checkbox" name="remove_<?= $f ?>" value="1"> Kaldır</label><?php endif; ?>
            <input type="file" id="<?= $f ?>" name="<?= $f ?>" accept="image/jpeg,image/png,image/webp"><?= field_error($f) ?></div>
    <?php endforeach; ?>
    <button class="btn" type="submit">KAYDET</button>
</div></form>
<div class="stack">
    <form method="post" action="<?= e(url('/yonetim/icerik/bolumler')) ?>" class="card" id="bolumler"><div class="card-head"><h2>Üye ana sayfa bölümleri</h2></div><div class="card-body"><?= csrf_field() ?>
        <p class="small muted">Bölümleri açıp kapatın, ok düğmeleriyle sıralayın.</p>
        <ul class="sortable-list" data-sortable>
        <?php foreach ($sections as $s): ?><li><input type="hidden" name="order[]" value="<?= e($s['key']) ?>"><label class="check" style="min-height:auto;padding:0"><input type="checkbox" name="enabled[]" value="<?= e($s['key']) ?>"<?= $s['is_enabled'] ? ' checked' : '' ?>><span class="sr-only">Göster: </span></label><span class="grow"><?= e($s['title']) ?></span><button type="button" class="btn btn-secondary btn-sm" data-move="up" aria-label="Yukarı taşı: <?= e($s['title']) ?>">↑</button><button type="button" class="btn btn-secondary btn-sm" data-move="down" aria-label="Aşağı taşı: <?= e($s['title']) ?>">↓</button></li><?php endforeach; ?>
        </ul>
        <button class="btn" type="submit">SIRALAMAYI KAYDET</button>
    </div></form>
    <div class="card" id="sayfalar"><div class="card-head"><h2>Yasal sayfalar</h2></div><div class="card-body stack">
        <p class="small muted">Metinler düz yazı olarak girilir (HTML kabul edilmez). Paragrafları boş satırla, maddeleri “- ” ile, alt başlıkları “## ” ile başlatın. Veri sorumlusu bilgilerini kurumunuzun hukuk birimiyle doğrulayın.</p>
        <?php foreach ($pages as $p): ?>
            <details><summary><strong><?= e($p['title']) ?></strong> <?= trim((string) $p['body']) === '' ? '<span class="badge badge-warning">Boş</span>' : '<span class="small muted">· ' . e(tr_datetime($p['updated_at'])) . '</span>' ?></summary>
                <form method="post" action="<?= e(url('/yonetim/icerik/sayfa/' . $p['id'])) ?>" style="margin-top:10px"><?= csrf_field() ?>
                    <div class="field"><label for="pt<?= (int) $p['id'] ?>">Başlık</label><input id="pt<?= (int) $p['id'] ?>" name="title" value="<?= e($p['title']) ?>"></div>
                    <div class="field"><label for="pb<?= (int) $p['id'] ?>">Metin</label><textarea id="pb<?= (int) $p['id'] ?>" name="body" rows="10"><?= e($p['body']) ?></textarea></div>
                    <div class="row"><button class="btn btn-secondary btn-sm">Kaydet</button><a class="btn btn-ghost btn-sm" href="<?= e(url('/' . $p['slug'])) ?>" target="_blank">Görüntüle</a></div>
                </form></details>
        <?php endforeach; ?>
    </div></div>
</div>
</div>
