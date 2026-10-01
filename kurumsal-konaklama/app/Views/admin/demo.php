<?php $active = $stats['hotels'] > 0; ?>
<div class="admin-title"><div><h1>Demo / Canlı mod</h1><p>Sistemi gerçek otelleri girmeden önce örnek verilerle deneyin; hazır olduğunuzda tek tıkla canlı moda geçin.</p></div>
    <span class="badge badge-<?= $active ? 'warning' : 'success' ?> badge-lg"><?= $active ? 'DEMO MODU' : 'CANLI MOD' ?></span>
</div>

<div class="grid-2">
<div class="card">
    <div class="card-head"><h2><?= icon('sparkle') ?> Demo verileri</h2></div>
    <div class="card-body">
        <?php if ($active): ?>
            <div class="stat-row">
                <div class="stat"><strong><?= (int) $stats['hotels'] ?></strong><span>demo otel</span></div>
                <div class="stat"><strong><?= (int) $stats['rooms'] ?></strong><span>oda tipi</span></div>
                <div class="stat"><strong><?= (int) $stats['images'] ?></strong><span>otel görseli</span></div>
                <div class="stat"><strong><?= (int) $stats['bookings'] ?></strong><span>deneme rezervasyonu</span></div>
            </div>
            <?php if ($loadedAt !== ''): ?><p class="muted small">Yüklenme: <?= e(tr_datetime($loadedAt)) ?></p><?php endif; ?>
            <p>Demo oteller üye ekranında aranabilir, karşılaştırılabilir ve rezerve edilebilir. Bu kayıtlar kurgusaldır; fiyatlar örnektir ve görseller <strong>temsili</strong> illüstrasyonlardır.</p>
            <div class="row"><a class="btn btn-secondary" href="<?= e(url('/oteller')) ?>"><?= icon('search') ?> Üye ekranında ara</a><a class="btn btn-ghost" href="<?= e(url('/yonetim/oteller')) ?>">Otelleri yönet</a></div>
        <?php else: ?>
            <p>Demo yükleme şunları ekler:</p>
            <ul class="check-list">
                <li>Antalya’nın 14 bölgesinde 14 kurgusal otel (Lara, Kundu, Belek, Kadriye, Side, Manavgat, Alanya, Kemer, Beldibi, Tekirova, Kaleiçi, şehir merkezi, Kaş, Kalkan)</li>
                <li>32 oda tipi, 12 aylık sezonluk fiyatlar ve kontenjan</li>
                <li>Anında onaylı ve otel onaylı (hedef teklif) örnekleri, örnek erken rezervasyon kuralı</li>
                <li>Otel, oda ve karşılama ekranları için temsili görseller</li>
            </ul>
            <?php if (!$available): ?><div class="alert alert-danger"><?= icon('alert') ?><div>Demo veri dosyaları sunucuda bulunamadı (<span class="code">database/demo</span>).</div></div>
            <?php else: ?>
            <form method="post" action="<?= e(url('/yonetim/demo-mod/yukle')) ?>" data-confirm="Demo oteller yüklensin mi? İşlem birkaç saniye sürebilir."><?= csrf_field() ?>
                <button class="btn btn-lg" type="submit"><?= icon('download') ?> DEMO VERİLERİ YÜKLE</button>
            </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-head"><h2><?= icon('shield') ?> Canlı moda geçiş</h2></div>
    <div class="card-body">
        <?php if ($active): ?>
            <p>Canlı moda geçtiğinizde <strong>yalnız demo kayıtları</strong> silinir: demo oteller, odaları, fiyatları, kontenjanları, görselleri, demo kuralı ve demo otellere yapılmış deneme rezervasyon/teklifleri. Sizin girdiğiniz <?= (int) $realHotels ?> gerçek otel, kurumlar, üyeler ve ayarlar korunur.</p>
            <form method="post" action="<?= e(url('/yonetim/demo-mod/kaldir')) ?>" data-confirm="Tüm demo veriler kalıcı olarak silinecek. Emin misiniz?"><?= csrf_field() ?>
                <?= f_input('onay', 'Onay için büyük harflerle CANLI yazın', '', 'text', ['autocomplete' => 'off', 'placeholder' => 'CANLI']) ?>
                <button class="btn btn-danger btn-lg" type="submit"><?= icon('check-circle') ?> CANLI MODA GEÇ</button>
            </form>
        <?php else: ?>
            <div class="alert alert-success"><?= icon('check-circle') ?><div>Sistem canlı modda. Sistemde demo kayıt yok; yalnız sizin girdiğiniz <?= (int) $realHotels ?> otel listelenir.</div></div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-head"><h2><?= icon('users') ?> Üyelik kaydı</h2></div>
    <div class="card-body">
        <p>Şu anki mod: <strong><?= e(['application' => 'Erişim talebi (yönetici onaylı)', 'open' => 'Açık kayıt (anında aktif)', 'closed' => 'Kapalı (yalnız yönetim)'][$mode] ?? $mode) ?></strong></p>
        <p class="muted small">Açık kayıt modunda giriş ekranında “Hesap oluştur” düğmesi görünür ve kullanıcılar onay beklemeden üye olur. İzinli e-posta alan adlarıyla sınırlandırabilirsiniz.</p>
        <a class="btn btn-secondary" href="<?= e(url('/yonetim/ayarlar')) ?>#uyelik"><?= icon('settings') ?> Üyelik ayarlarını değiştir</a>
    </div>
</div>
</div>
