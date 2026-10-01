<div class="admin-title"><div><h1>Canlı otel verisi</h1><p>Gerçek Antalya otellerini ücretsiz kaynaklardan içe aktarın, canlı fiyat ve rezervasyonu açın.</p></div>
    <span class="badge badge-<?= $liveSearch ? 'success' : 'neutral' ?> badge-lg"><?= $liveSearch ? 'CANLI FİYAT AÇIK' : 'CANLI FİYAT KAPALI' ?></span>
</div>

<?php if ($demoActive): ?><div class="alert alert-warning"><?= icon('alert') ?><div>Demo oteller hâlâ yüklü. Gerçek verilerle çalışmadan önce <a href="<?= e(url('/yonetim/demo-mod')) ?>">Demo / Canlı Mod</a> sayfasından demo verileri kaldırın.</div></div><?php endif; ?>

<div class="grid-2">
<div class="card">
    <div class="card-head"><h2><?= icon('map') ?> OpenStreetMap — gerçek otel listesi</h2><span class="badge badge-success">Ücretsiz · anahtarsız</span></div>
    <div class="card-body">
        <p>Antalya bölgelerindeki <strong>gerçek</strong> otellerin adı, yıldızı, konumu, adresi, telefonu ve web sitesi içe aktarılır.</p>
        <ul class="small" style="padding-left:18px;margin:0 0 12px">
            <li><strong>Fiyat ve müsaitlik vermez.</strong> Bu oteller üyeler için “Teklif iste” akışıyla çalışır; fiyatı otelden alıp teklif olarak girersiniz.</li>
            <li>Fotoğraf vermez; otel kartında “Temsili bölge görseli” etiketiyle bölge illüstrasyonu gösterilir. Gerçek fotoğrafı yönetimden ekleyebilirsiniz.</li>
            <li>Veri lisansı ODbL: otel sayfalarında “© OpenStreetMap katkıcıları” kaynak gösterimi otomatik yer alır.</li>
        </ul>
        <p class="small muted">İçe aktarılan: <strong><?= (int) $totals['osm'] ?></strong> otel · Bağlantı: <?= e(status_label((string) ($osm['status'] ?? 'unknown'))) ?><?= !empty($osm['last_error']) ? ' — ' . e($osm['last_error']) : '' ?></p>
        <form method="post" action="<?= e(url('/yonetim/canli-veri/test/osm')) ?>"><?= csrf_field() ?><button class="btn btn-secondary btn-sm"><?= icon('check-circle') ?> Bağlantıyı test et</button></form>
    </div>
</div>

<div class="card">
    <div class="card-head"><h2><?= icon('globe') ?> LiteAPI — canlı fiyat ve rezervasyon</h2><span class="badge badge-teal">Ücretsiz hesap</span></div>
    <div class="card-body">
        <p>Gerçek otel içeriği ve <strong>fotoğrafları</strong>, arama anında <strong>canlı oda fiyatları</strong> ve sağlayıcı üzerinden <strong>anında rezervasyon</strong>.</p>
        <ol class="small" style="padding-left:18px;margin:0 0 12px">
            <li><a href="https://dashboard.liteapi.travel" target="_blank" rel="noopener">dashboard.liteapi.travel</a> adresinden ücretsiz hesap açın.</li>
            <li><em>API Keys</em> bölümündeki anahtarı aşağıya yapıştırın. <span class="code">sand_</span> ile başlayan <strong>sandbox</strong> anahtarı test fiyatları verir, rezervasyonları gerçek değildir. Canlı kullanım için LiteAPI hesabınızda canlı anahtarı ve ödeme yöntemini etkinleştirin.</li>
            <li>Kaydettiğinizde bağlantı test edilir ve canlı fiyat sorgusu açılır.</li>
        </ol>
        <?php if ($liteKey): ?><p class="small">Kayıtlı anahtar: <span class="code">••••<?= e($liteKey['last4']) ?></span> <?= $liteSandbox ? '<span class="badge badge-warning">SANDBOX (test)</span>' : '<span class="badge badge-success">CANLI</span>' ?> · Durum: <?= e(status_label((string) $lite['status'])) ?><?= !empty($lite['last_error']) ? ' — ' . e($lite['last_error']) : '' ?> · İçe aktarılan: <strong><?= (int) $totals['liteapi'] ?></strong> otel</p><?php endif; ?>
        <form method="post" action="<?= e(url('/yonetim/canli-veri/liteapi')) ?>" autocomplete="off"><?= csrf_field() ?>
            <div class="field"><label for="api_key">API anahtarı</label><input id="api_key" name="api_key" type="password" autocomplete="new-password" placeholder="<?= $liteKey ? 'Kayıtlı — değiştirmek için yeni anahtarı yazın' : 'sand_… veya canlı anahtar' ?>"<?= aria_error('api_key') ?>><?= field_error('api_key') ?><p class="hint">Anahtar şifreli saklanır, ekranda ve loglarda gösterilmez.</p></div>
            <label class="check"><input type="checkbox" name="booking_authorized" value="1"<?= !empty($lite['booking_authorized']) ? ' checked' : '' ?>><span>Üyeler LiteAPI fiyatlarıyla <strong>anında rezervasyon</strong> yapabilsin (işaretlenmezse fiyatlar “onaya bağlı hedef teklif” olarak gösterilir).</span></label>
            <div class="row" style="margin-top:10px"><button class="btn" type="submit"><?= icon('check') ?> KAYDET VE BAĞLAN</button></div>
        </form>
        <?php if ($liteKey): ?><form method="post" action="<?= e(url('/yonetim/canli-veri/test/liteapi')) ?>" style="margin-top:8px"><?= csrf_field() ?><button class="btn btn-ghost btn-sm"><?= icon('check-circle') ?> Bağlantıyı tekrar test et</button></form><?php endif; ?>
    </div>
</div>
</div>

<div class="card" style="margin-top:16px">
    <div class="card-head"><h2><?= icon('download') ?> Otelleri içe aktar</h2></div>
    <div class="card-body">
        <form method="post" action="<?= e(url('/yonetim/canli-veri/ice-aktar')) ?>" data-confirm="Seçilen bölgeler için içe aktarma başlasın mı? Birkaç dakika sürebilir."><?= csrf_field() ?>
            <div class="form-grid">
                <?= f_select('kaynak', 'Kaynak', ['osm' => 'OpenStreetMap (anahtarsız, fiyat yok)', 'liteapi' => 'LiteAPI (fotoğraf + canlı fiyat)'], $liteKey ? 'liteapi' : 'osm') ?>
                <?= f_input('adet', 'Bölge başına en fazla yeni otel', '30', 'number', ['min' => 1, 'max' => 100]) ?>
            </div>
            <fieldset class="field"><legend>Bölgeler</legend><?= field_error('bolgeler') ?>
                <div class="table-wrap"><table class="table">
                    <thead><tr><th style="width:44px"><span class="sr-only">Seç</span></th><th>Bölge</th><th>OSM</th><th>LiteAPI</th><th>Elle girilen</th></tr></thead>
                    <tbody>
                    <?php foreach ($regions as $r): ?>
                        <tr><td><input type="checkbox" name="bolgeler[]" value="<?= (int) $r['id'] ?>" aria-label="<?= e($r['name']) ?>"<?= $r['latitude'] === null ? ' disabled' : '' ?>></td>
                            <td><?= $r['parent_id'] ? '<span class="muted">↳</span> ' : '' ?><?= e($r['name']) ?></td>
                            <td><?= (int) $r['osm_count'] ?></td><td><?= (int) $r['lite_count'] ?></td><td><?= (int) $r['manual_count'] ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table></div>
                <p class="hint">Üst bölgeler (ör. Belek, Kemer) 9 km, alt bölgeler 5 km yarıçapla taranır. Daha önce eklenen oteller tekrar eklenmez, bilgileri güncellenir. LiteAPI ile aktarılan otel, aynı otelin OpenStreetMap kaydıyla otomatik birleştirilir.</p>
            </fieldset>
            <button class="btn btn-lg" type="submit"><?= icon('download') ?> İÇE AKTAR</button>
        </form>
    </div>
</div>

<div class="grid-2" style="margin-top:16px">
<div class="card"><div class="card-head"><h2><?= icon('settings') ?> Canlı fiyat sorgusu</h2></div><div class="card-body">
    <p class="small">Açıkken otel sayfası ve arama sonuçları, eşlenmiş oteller için sağlayıcıdan güncel fiyat ister (önbellek süresi: Sistem Ayarları → Önbellek). Kapalıyken yalnız anlaşmalı fiyatlar ve teklif akışı kullanılır.</p>
    <form method="post" action="<?= e(url('/yonetim/canli-veri/canli-arama')) ?>"><?= csrf_field() ?><input type="hidden" name="acik" value="<?= $liveSearch ? '0' : '1' ?>"><button class="btn <?= $liveSearch ? 'btn-secondary' : '' ?>"><?= $liveSearch ? 'Canlı fiyatı kapat' : 'Canlı fiyatı aç' ?></button></form>
</div></div>
<div class="card"><div class="card-head"><h2><?= icon('trash') ?> İçe aktarılan otelleri kaldır</h2></div><div class="card-body">
    <form method="post" action="<?= e(url('/yonetim/canli-veri/kaldir')) ?>" data-confirm="Seçilen kaynaktan gelen oteller silinecek. Emin misiniz?"><?= csrf_field() ?>
        <?= f_select('kaynak', 'Kaynak', ['osm' => 'OpenStreetMap otelleri', 'liteapi' => 'LiteAPI otelleri'], 'osm') ?>
        <?= f_input('onay', 'Onay için SIL yazın', '', 'text', ['autocomplete' => 'off']) ?>
        <p class="hint">Rezervasyon veya teklif geçmişi olan oteller silinmez, yayından kaldırılır.</p>
        <button class="btn btn-danger" type="submit">KALDIR</button>
    </form>
</div></div>
</div>
