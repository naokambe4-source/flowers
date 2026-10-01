<?php
$hour = (int) date('G');
$greet = $hour < 12 ? 'Günaydın' : ($hour < 18 ? 'İyi günler' : 'İyi akşamlar');
$hero = site_image('home.hero_image', 'hero');
$support = site_image('support.image', 'support');
foreach ($sections as $s):
switch ($s['key']):
case 'hero': ?>
<section class="hero" aria-labelledby="hero-title">
    <div class="hero-media"><img src="<?= e($hero['url']) ?>" alt="" fetchpriority="high"></div>
    <?php if ($hero['representative']): ?><span class="media-note">Temsili illüstrasyon</span><?php endif; ?>
    <div class="container">
        <span class="greeting"><?= icon('sun', 'icon-s') ?> <?= e($greet) ?>, <?= e($user['first_name']) ?></span>
        <h1 id="hero-title"><?= e(setting('home.hero_title', 'Antalya’da size özel konaklama')) ?></h1>
        <p class="lead"><?= e(setting('home.hero_text')) ?><?php if ($user['institution_name']): ?> <strong style="color:#f3e2b8"><?= e($user['institution_name']) ?></strong> üyelerine özel.<?php endif; ?></p>
        <?php if (can('member.book')): ?><?= App\Core\View::partial('partials/search_form', ['regions' => $regions, 'criteria' => null]) ?><?php endif; ?>
    </div>
</section>
<?php break; case 'summary': ?>
<section class="section" aria-labelledby="sum-title" style="padding-bottom:0">
    <div class="container">
        <div class="section-head"><div><h2 id="sum-title">Bekleyen işlemleriniz</h2><p>Teklif ve rezervasyonlarınızın güncel durumu</p></div></div>
        <?php if (empty($pendingOffers) && empty($upcoming) && empty($openRequests)): ?>
            <div class="empty" style="padding:28px"><?= icon('calendar', 'icon-l') ?><h3>Bekleyen işleminiz yok</h3><p>Otel arayarak rezervasyon yapabilir veya teklif isteyebilirsiniz.</p><?php if (can('member.book')): ?><a class="btn" href="<?= e(url('/oteller')) ?>">OTEL ARA</a><?php endif; ?></div>
        <?php else: ?>
        <div class="grid-2">
            <div class="card"><div class="card-head"><h3><?= icon('document') ?> Tekliflerim</h3><a href="<?= e(url('/tekliflerim')) ?>">Tümü</a></div><div class="card-body stack-s">
                <?php if ($openRequests): ?><p class="badge badge-warning"><?= (int) $openRequests ?> talep teklif bekliyor</p><?php endif; ?>
                <?php foreach ($pendingOffers as $o): ?>
                    <a class="shortcut" href="<?= e(url('/tekliflerim/' . $o['code'])) ?>"><span class="tile-icon"><?= icon('tag') ?></span><span><?= e($o['hotel_name']) ?> — <?= e(money((int) $o['total_minor'])) ?><small>Son geçerlilik: <?= e(tr_datetime($o['valid_until'])) ?></small></span></a>
                <?php endforeach; ?>
                <?php if (!$pendingOffers && !$openRequests): ?><p class="muted">Yanıt bekleyen teklif bulunmuyor.</p><?php endif; ?>
            </div></div>
            <div class="card"><div class="card-head"><h3><?= icon('calendar') ?> Yaklaşan rezervasyonlar</h3><a href="<?= e(url('/rezervasyonlarim')) ?>">Tümü</a></div><div class="card-body stack-s">
                <?php foreach ($upcoming as $b): ?>
                    <a class="shortcut" href="<?= e(url('/rezervasyonlarim/' . $b['code'])) ?>"><span class="tile-icon"><?= icon('bed') ?></span><span><?= e($b['hotel_name']) ?><small><?= e(tr_date_short($b['check_in'])) ?> – <?= e(tr_date_short($b['check_out'])) ?> · <?= e(status_label($b['status'])) ?></small></span></a>
                <?php endforeach; ?>
                <?php if (!$upcoming): ?><p class="muted">Yaklaşan rezervasyonunuz yok.</p><?php endif; ?>
            </div></div>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php break; case 'featured': if (!can('member.book')) break; ?>
<section class="section" aria-labelledby="feat-title">
    <div class="container">
        <div class="section-head"><div><span class="eyebrow">Seçkin tesisler</span><h2 id="feat-title">Anlaşmalı oteller</h2><p>Yönetimimiz tarafından seçilen, kurumunuza özel koşullar sunan oteller</p></div><a class="btn btn-secondary" href="<?= e(url('/oteller')) ?>">Tüm oteller <?= icon('arrow-right', 'icon-s') ?></a></div>
        <?php if (empty($featured)): ?>
            <div class="empty"><?= icon('building', 'icon-l') ?><h3>Henüz öne çıkan otel yok</h3><p>Anlaşmalı oteller yayına alındıkça burada listelenecek. Bu süreçte bölgeye göre arama yapabilir veya teklif isteyebilirsiniz.</p><a class="btn" href="<?= e(url('/teklif-iste')) ?>">TEKLİF İSTE</a></div>
        <?php else: ?>
            <div class="grid-4" style="grid-template-columns:repeat(auto-fill,minmax(250px,1fr))">
                <?php foreach ($featured as $h): $h += ['quote' => null, 'amenities' => [], 'is_favorite' => false, 'is_contracted' => 1, 'sea_distance_m' => null]; ?>
                    <?= App\Core\View::partial('partials/hotel_card', ['h' => $h]) ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php break; case 'regions': ?>
<section class="section" aria-labelledby="reg-title" style="background:#fff;border-top:1px solid var(--c-line);border-bottom:1px solid var(--c-line)">
    <div class="container">
        <div class="section-head"><div><span class="eyebrow">Antalya</span><h2 id="reg-title">Bölgelere göre keşfedin</h2><p>Lara’dan Kaş’a, sahil ilçelerindeki anlaşmalı oteller</p></div></div>
        <div class="region-grid">
            <?php foreach (array_filter($regions, static fn ($r) => (int) $r['show_on_home'] === 1) as $r): $img = region_image($r); ?>
                <a class="region-card" href="<?= e(url('/oteller', ['bolge' => $r['id']])) ?>">
                    <img src="<?= e($img['url']) ?>" alt="" loading="lazy">
                    <?php if ($img['representative']): ?><span class="tag-repr">Temsili</span><?php endif; ?>
                    <span class="region-name"><?= e($r['name']) ?> <?= icon('arrow-right', 'icon-s') ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php break; case 'campaigns': if (empty($campaigns)) break; ?>
<section class="section" aria-labelledby="camp-title">
    <div class="container">
        <div class="section-head"><div><span class="eyebrow">Geçerli kampanyalar</span><h2 id="camp-title">Kampanyalar</h2></div></div>
        <div class="grid-3">
            <?php foreach ($campaigns as $c): ?>
                <article class="campaign-card">
                    <div class="cc-media"><?php if ($c['image_path']): ?><img src="<?= e(url('/medya/kampanya/' . $c['id'])) ?>" alt="" loading="lazy"><?php endif; ?></div>
                    <div class="cc-body">
                        <h3 style="margin:0"><?= e($c['title']) ?></h3>
                        <?php if ($c['summary']): ?><p class="muted" style="margin:0"><?= e($c['summary']) ?></p><?php endif; ?>
                        <p class="small" style="margin:0"><?= icon('clock', 'icon-s') ?> Son gün: <?= e(tr_date($c['valid_to'])) ?><?= $c['stay_from'] ? ' · Konaklama: ' . e(tr_date_short($c['stay_from'])) . ' – ' . e(tr_date_short($c['stay_to'])) : '' ?></p>
                        <a class="btn btn-secondary" style="margin-top:auto" href="<?= e($c['hotel_slug'] ? url('/oteller/' . $c['hotel_slug']) : url('/oteller', ['bolge' => $c['region_id']])) ?>">İNCELE</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php break; case 'shortcuts': ?>
<section class="section" aria-labelledby="sc-title" style="padding-bottom:0">
    <div class="container">
        <h2 id="sc-title" class="sr-only">Kısa yollar</h2>
        <div class="grid-4">
            <a class="shortcut" href="<?= e(url('/rezervasyonlarim')) ?>"><span class="tile-icon"><?= icon('calendar') ?></span><span>Rezervasyonlarım<small>Durum ve voucher</small></span></a>
            <a class="shortcut" href="<?= e(url('/tekliflerim')) ?>"><span class="tile-icon"><?= icon('document') ?></span><span>Tekliflerim<small>Talepler ve teklifler</small></span></a>
            <a class="shortcut" href="<?= e(url('/favorilerim')) ?>"><span class="tile-icon"><?= icon('heart') ?></span><span>Favorilerim<small><?= (int) $favCount ?> otel</small></span></a>
            <a class="shortcut" href="<?= e(url('/teklif-iste')) ?>"><span class="tile-icon"><?= icon('tag') ?></span><span>Teklif İste<small>Özel konaklama talebi</small></span></a>
        </div>
    </div>
</section>
<?php break; case 'support': ?>
<section class="section" aria-labelledby="sup-title">
    <div class="container">
        <div class="support-band">
            <div class="sb-media"><img src="<?= e($support['url']) ?>" alt="" loading="lazy"></div>
            <div class="sb-body stack">
                <span class="eyebrow">Yardım</span>
                <h2 id="sup-title">Rezervasyon desteği</h2>
                <p class="muted">Otel seçimi, teklif veya rezervasyonunuzla ilgili ekibimize ulaşabilirsiniz.</p>
                <?= App\Core\View::partial('partials/support_contacts') ?>
            </div>
        </div>
    </div>
</section>
<?php break; endswitch; endforeach; ?>
