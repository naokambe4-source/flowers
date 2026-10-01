<?php
/** @var array $hotel */
$h = $hotel;
$cq = $criteria ? $criteria->toQuery() : [];
$photos = $images;
$allQuotes = [];
foreach ($quotesByRoom as $list) { foreach ($list as $q) { $allQuotes[] = $q; } }
$providerOffers = $providerOffers ?? [];
foreach ($providerOffers as $q) { $allQuotes[] = $q; }
$selectable = array_values(array_filter($allQuotes, static fn ($q) => in_array($q->kind, ['firm', 'target'], true)));
usort($selectable, static fn ($a, $b) => [$a->kind === 'firm' ? 0 : 1, $a->total] <=> [$b->kind === 'firm' ? 0 : 1, $b->total]);
$defaultSel = $selectable[0] ?? null;
$offerQuery = static fn (?int $roomId, ?App\DTO\PriceQuote $q) => array_merge($cq, ['otel' => $h['id'], 'oda_tipi' => $roomId, 'hedef' => $q && $q->kind === 'target' ? $q->referencePriceId : null]);
$formIdFor = static fn (App\DTO\PriceQuote $q) => 'rate-' . (int) $q->roomId . '-' . (int) $q->ratePlanId . '-' . (int) $q->referencePriceId;
$beachLabels = ['kum' => 'Kum plaj', 'cakil' => 'Çakıl plaj', 'kum_cakil' => 'Kum-çakıl plaj', 'iskele' => 'İskele', 'platform' => 'Platform', 'yok' => 'Plaj yok'];
?>
<div class="container">
    <?php if ($preview): ?><div class="alert alert-warning" style="margin-top:16px"><?= icon('eye') ?><div><strong>Önizleme:</strong> Bu sayfa yalnız yöneticilere görünür. Durum: <?= e(status_label($h['status'])) ?>. <a href="<?= e(url('/yonetim/oteller/' . $h['id'] . '/adim/7')) ?>">Yayın adımına dön</a></div></div><?php endif; ?>
    <nav aria-label="Konum"><ol class="breadcrumb" style="margin-top:16px"><li><a href="<?= e(url('/panel')) ?>">Ana Sayfa</a></li><li><a href="<?= e(url('/oteller', $cq)) ?>">Oteller</a></li><?php if ($h['region_name']): ?><li><a href="<?= e(url('/oteller', $cq + ['bolge' => $h['region_id']])) ?>"><?= e($h['region_name']) ?></a></li><?php endif; ?><li aria-current="page"><?= e($h['name']) ?></li></ol></nav>

    <?php if ($photos): ?>
    <div class="gallery" data-gallery>
        <?php foreach (array_slice($photos, 0, 5) as $i => $img): ?>
            <button type="button" class="<?= $i === 0 ? 'g-main' : 'g-thumb' ?>" data-full="<?= e(url('/medya/otel/' . $img['id'] . '/large')) ?>" data-caption="<?= e($img['caption'] ?: $h['name']) ?>" aria-label="Fotoğrafı büyüt: <?= e($img['caption'] ?: $h['name']) ?>">
                <img src="<?= e(url('/medya/otel/' . $img['id'] . '/' . ($i === 0 ? 'large' : 'medium'))) ?>" alt="<?= e($img['caption'] ?: $h['name'] . ' fotoğrafı') ?>" <?= $i === 0 ? 'fetchpriority="high"' : 'loading="lazy"' ?>>
                <?php if ($i === 4 && count($photos) > 5): ?><span class="g-more">+<?= count($photos) - 5 ?> fotoğraf</span><?php endif; ?>
            </button>
        <?php endforeach; ?>
        <?php foreach (array_slice($photos, 5) as $img): ?><button type="button" hidden data-full="<?= e(url('/medya/otel/' . $img['id'] . '/large')) ?>" data-caption="<?= e($img['caption'] ?: $h['name']) ?>"></button><?php endforeach; ?>
    </div>
    <?php elseif (($h['data_source'] ?? 'manual') !== 'manual' && !empty($h['region_illustration'])): ?>
        <div class="hotel-illustration"><img src="<?= e(asset_url('img/illustrations/' . $h['region_illustration'] . '.svg')) ?>" alt=""><span class="img-note">Temsili bölge görseli — bu otelin fotoğrafı henüz eklenmedi</span></div>
    <?php else: ?>
        <div class="empty" style="padding:28px"><?= icon('image', 'icon-l') ?><h3>Bu otel için henüz fotoğraf eklenmedi</h3><p>Yanıltıcı olmaması için başka tesislere ait görseller kullanılmaz.</p></div>
    <?php endif; ?>

    <div class="detail-title">
        <div>
            <div class="meta-line"><?= App\Core\View::partial('partials/stars', ['stars' => $h['stars']]) ?><?php if ($h['is_contracted']): ?><span class="badge badge-gold">Anlaşmalı otel</span><?php endif; ?><?php if ($h['concept_name']): ?><span class="badge badge-teal"><?= e($h['concept_name']) ?></span><?php endif; ?></div>
            <h1><?= e($h['name']) ?></h1>
            <div class="meta-line"><span><?= icon('pin', 'icon-s') ?> <?= e(implode(', ', array_filter([$h['neighborhood'], $h['district'], $h['region_name']]))) ?></span><?php if ($h['latitude'] !== null): ?><a href="#konum">Haritada göster</a><?php endif; ?></div>
            <?php if ($h['address']): ?><p class="muted small" style="margin:4px 0 0"><?= e($h['address']) ?></p><?php endif; ?>
        </div>
        <form method="post" action="<?= e(url('/favoriler/' . $h['id'])) ?>" data-fav data-no-lock>
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-secondary" aria-pressed="<?= $isFav ? 'true' : 'false' ?>"><?= icon('heart') ?> <span class="fav-text"><?= $isFav ? 'Favorilerde' : 'Favoriye ekle' ?></span></button>
        </form>
    </div>
</div>

<nav class="subnav" aria-label="Otel bölümleri"><div class="container"><ul>
    <li><a href="#genel">Genel Bakış</a></li><li><a href="#odalar">Odalar ve Fiyatlar</a></li><li><a href="#ozellikler">Özellikler</a></li><li><a href="#konsept">Konsept</a></li>
    <li><a href="#konum">Konum</a></li><li><a href="#cocuk">Çocuk Politikası</a></li><li><a href="#iptal">İptal ve Ödeme</a></li><li><a href="#onemli">Önemli Bilgiler</a></li>
</ul></div></nav>

<div class="container">
    <?php foreach ($notices as $n): ?><div class="alert alert-info" style="margin-top:16px"><?= icon('info') ?><div><?= e($n) ?></div></div><?php endforeach; ?>
    <div class="detail-layout">
        <div>
            <section id="genel" class="detail-section">
                <h2>Genel bakış</h2>
                <?php if ($h['short_description']): ?><p class="lead" style="font-size:1.08rem"><?= e($h['short_description']) ?></p><?php endif; ?>
                <div class="prose"><?= nl2p($h['description']) ?></div>
                <div class="facts" style="margin-top:16px">
                    <div class="fact"><span>Giriş saati</span><strong><?= e($h['check_in_time'] ?: '—') ?></strong></div>
                    <div class="fact"><span>Çıkış saati</span><strong><?= e($h['check_out_time'] ?: '—') ?></strong></div>
                    <div class="fact"><span>Denize uzaklık</span><strong><?= $h['sea_distance_m'] === null ? '—' : ((int) $h['sea_distance_m'] === 0 ? 'Denize sıfır' : (int) $h['sea_distance_m'] . ' m') ?></strong></div>
                    <div class="fact"><span>Havalimanı</span><strong><?= $h['airport_distance_km'] !== null ? e(str_replace('.', ',', (string) (float) $h['airport_distance_km'])) . ' km' : '—' ?></strong></div>
                </div>
                <?php if ($h['video_url']): ?><p style="margin-top:12px"><a href="<?= e($h['video_url']) ?>" target="_blank" rel="noopener"><?= icon('external', 'icon-s') ?> Tanıtım videosunu izle</a></p><?php endif; ?>
            </section>

            <section id="odalar" class="detail-section">
                <div class="row-between"><h2 style="margin:0">Odalar ve fiyatlar</h2><?php if ($criteria): ?><span class="badge badge-teal" style="white-space:normal"><?= e(tr_date_short($criteria->checkIn)) ?> – <?= e(tr_date_short($criteria->checkOut)) ?> · <?= $criteria->nights() ?> gece · <?= e($criteria->summary()) ?></span><?php endif; ?></div>
                <?php if (!$criteria): ?>
                    <div class="alert alert-info" style="margin-top:12px"><?= icon('calendar') ?><div>Oda fiyatlarını ve müsaitliği görmek için tarih ve konuk seçin.</div></div>
                <?php endif; ?>
                <div style="margin:14px 0"><?= App\Core\View::partial('partials/search_form', ['regions' => $regions, 'criteria' => $criteria, 'regionId' => 0, 'compact' => true, 'hideRegion' => true, 'action' => url('/oteller/' . $h['slug']), 'submitLabel' => 'FİYATLARI GÖSTER']) ?></div>
                <?php if ($providerOffers): ?>
                    <article class="room-card live-offers">
                        <div class="room-info">
                            <h3><?= icon('globe') ?> Canlı oda fiyatları</h3>
                            <p class="muted small" style="margin:4px 0 0"><?= e($providerInfo['name'] ?? '') ?> üzerinden<?= !empty($providerInfo['captured_at']) ? ' ' . e(tr_datetime($providerInfo['captured_at'])) . ' itibarıyla' : '' ?> alındı<?= !empty($providerInfo['valid_until']) ? '; ' . e(substr((string) $providerInfo['valid_until'], 11, 5)) . '’e kadar geçerli' : '' ?>. Rezervasyon öncesi fiyat sağlayıcıda yeniden doğrulanır.</p>
                            <?php if (!empty($providerInfo['sandbox'])): ?><div class="alert alert-warning" style="margin:10px 0 0"><?= icon('alert') ?><div><strong>TEST (sandbox) bağlantısı:</strong> Fiyatlar test amaçlıdır ve yapılan rezervasyonlar gerçek değildir. Canlı kullanım için yönetimden canlı API anahtarı girilmelidir.</div></div><?php endif; ?>
                        </div>
                        <?php foreach ($providerOffers as $q): $fid = $formIdFor($q); $ref = (string) ($q->breakdown['external_rate_id'] ?? ''); ?>
                            <div class="rate-row">
                                <div>
                                    <strong><?= e($q->roomName ?: 'Oda') ?></strong>
                                    <?php if ($q->conceptName): ?><div class="muted small"><?= e($q->conceptName) ?></div><?php endif; ?>
                                    <?php if ($q->kind === 'firm'): ?><span class="badge badge-success" style="margin-top:6px"><?= icon('check', 'icon-s') ?> Anında onay</span><?php else: ?><span class="badge badge-teal" style="margin-top:6px">Onaya bağlı hedef teklif</span><?php endif; ?>
                                    <?php if (!$q->taxIncluded): ?><p class="muted small" style="margin:6px 0 0">Bazı vergi/ücretler otelde ödenir.</p><?php endif; ?>
                                </div>
                                <div style="display:flex;flex-direction:column;gap:4px"><?= App\Core\View::partial('partials/price_block', ['q' => $q]) ?></div>
                                <div class="stack-s">
                                    <?php if ($q->kind === 'firm'): ?>
                                        <label class="check" style="min-height:auto;padding:0"><input type="radio" name="secili_fiyat" value="<?= e($fid) ?>" data-form="<?= e($fid) ?>" data-room="<?= e($q->roomName ?: 'Oda') ?>" data-total="<?= e(money($q->total, $q->currency)) ?>" data-note="<?= e(($q->conceptName ?: '') . ' · ' . $q->cancellationSummary) ?>" data-action-label="ODAYI SEÇ"<?= $defaultSel === $q ? ' checked' : '' ?>> Bu seçeneği işaretle</label>
                                        <form id="<?= e($fid) ?>" method="post" action="<?= e(url('/rezervasyon/baslat')) ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="otel" value="<?= (int) $h['id'] ?>"><input type="hidden" name="oda_tipi" value="0"><input type="hidden" name="plan" value="0"><input type="hidden" name="teklif_ref" value="<?= e($ref) ?>">
                                            <input type="hidden" name="giris" value="<?= e($criteria->checkIn) ?>"><input type="hidden" name="cikis" value="<?= e($criteria->checkOut) ?>">
                                            <?php foreach ($criteria->rooms as $i => $r): ?><input type="hidden" name="oda[<?= $i ?>][y]" value="<?= $r->adults ?>"><input type="hidden" name="oda[<?= $i ?>][c]" value="<?= e(implode('-', $r->childAges)) ?>"><?php endforeach; ?>
                                            <input type="hidden" name="idem" value="<?= e($idemKey . '-p' . (int) $q->referencePriceId) ?>">
                                            <button type="submit" class="btn btn-block"<?= $preview ? ' disabled' : '' ?>>ODAYI SEÇ</button>
                                        </form>
                                    <?php else: ?>
                                        <label class="check" style="min-height:auto;padding:0"><input type="radio" name="secili_fiyat" value="<?= e($fid) ?>" data-form="<?= e($fid) ?>" data-room="<?= e($q->roomName ?: 'Oda') ?>" data-total="<?= e(money($q->total, $q->currency)) ?>" data-note="Onaya bağlı hedef teklif" data-action-label="TEKLİF İSTE"<?= $defaultSel === $q ? ' checked' : '' ?>> Bu seçeneği işaretle</label>
                                        <form id="<?= e($fid) ?>" method="get" action="<?= e(url('/teklif-iste')) ?>">
                                            <?php foreach ($offerQuery(null, $q) as $k => $v): if ($v === null) continue; if (is_array($v)) { foreach ($v as $i => $r) { echo '<input type="hidden" name="oda[' . (int) $i . '][y]" value="' . (int) $r['y'] . '"><input type="hidden" name="oda[' . (int) $i . '][c]" value="' . e($r['c']) . '">'; } continue; } ?><input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>"><?php endforeach; ?>
                                            <button type="submit" class="btn btn-secondary btn-block">TEKLİF İSTE</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </article>
                <?php endif; ?>
                <?php if (!$rooms && !$providerOffers): ?><div class="empty"><?= icon('bed', 'icon-l') ?><h3>Oda bilgisi henüz eklenmedi</h3><p>Bu otel için teklif isteyebilirsiniz.</p><a class="btn" href="<?= e(url('/teklif-iste', $offerQuery(null, null))) ?>">TEKLİF İSTE</a></div><?php endif; ?>
                <?php if (!empty($quotesByRoom[0])): $q = $quotesByRoom[0][0]; ?>
                    <div class="room-card"><div class="room-info"><h3>Otel geneli doğrulanmış fiyat</h3><p class="muted small"><?= e($q->roomName ?: 'Oda tipi kaynakta belirtilmemiş') ?> · <?= e($q->message) ?></p></div>
                        <div class="rate-row"><div><?= App\Core\View::partial('partials/price_block', ['q' => $q]) ?></div><div></div>
                            <div><label class="check" style="min-height:auto"><input type="radio" name="secili_fiyat" value="1" data-form="<?= e($formIdFor($q)) ?>" data-room="Otel geneli" data-total="<?= e(money($q->total)) ?>" data-note="Onaya bağlı hedef teklif" data-action-label="TEKLİF İSTE"<?= $defaultSel === $q ? ' checked' : '' ?>> Seç</label>
                                <form id="<?= e($formIdFor($q)) ?>" method="get" action="<?= e(url('/teklif-iste')) ?>"><?php foreach ($offerQuery(null, $q) as $k => $v): if (is_array($v)) { foreach ($v as $i => $r) { echo '<input type="hidden" name="oda[' . (int) $i . '][y]" value="' . (int) $r['y'] . '"><input type="hidden" name="oda[' . (int) $i . '][c]" value="' . e($r['c']) . '">'; } continue; } if ($v === null) continue; ?><input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>"><?php endforeach; ?><button class="btn btn-secondary" type="submit">TEKLİF İSTE</button></form>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                <?php foreach ($rooms as $room): $rq = $quotesByRoom[(int) $room['id']] ?? []; $rimg = $roomImages[(int) $room['id']] ?? []; ?>
                    <article class="room-card" aria-labelledby="room-<?= (int) $room['id'] ?>">
                        <div class="room-top">
                            <div class="room-photo"<?= $rimg ? ' data-gallery' : '' ?>>
                                <?php if ($rimg): ?><button type="button" data-full="<?= e(url('/medya/oda/' . $rimg[0]['id'] . '/large')) ?>" data-caption="<?= e($room['name']) ?>" style="position:absolute;inset:0;border:0;padding:0;cursor:zoom-in;background:none" aria-label="<?= e($room['name']) ?> fotoğraflarını aç"><img src="<?= e(url('/medya/oda/' . $rimg[0]['id'] . '/medium')) ?>" alt="<?= e($room['name']) ?>" loading="lazy"></button><?php foreach (array_slice($rimg, 1) as $ri): ?><button type="button" hidden data-full="<?= e(url('/medya/oda/' . $ri['id'] . '/large')) ?>" data-caption="<?= e($room['name']) ?>"></button><?php endforeach; ?>
                                <?php else: ?><div class="no-photo"><div><?= icon('bed') ?>Oda fotoğrafı eklenmedi</div></div><?php endif; ?>
                            </div>
                            <div class="room-info">
                                <h3 id="room-<?= (int) $room['id'] ?>"><?= e($room['name']) ?></h3>
                                <div class="meta-line">
                                    <?php if ($room['size_m2']): ?><span><?= (int) $room['size_m2'] ?> m²</span><?php endif; ?>
                                    <?php if ($room['bed_type']): ?><span><?= icon('bed', 'icon-s') ?> <?= e($room['bed_type']) ?></span><?php endif; ?>
                                    <?php if ($room['view_type']): ?><span><?= icon('eye', 'icon-s') ?> <?= e($room['view_type']) ?></span><?php endif; ?>
                                    <span><?= icon('users', 'icon-s') ?> En fazla <?= (int) $room['max_adults'] ?> yetişkin<?= (int) $room['max_children'] ? ', ' . (int) $room['max_children'] . ' çocuk' : '' ?> (toplam <?= (int) $room['max_occupancy'] ?>)</span>
                                </div>
                                <?php if ($room['description']): ?><p class="small" style="margin:8px 0 0"><?= e(mb_strimwidth($room['description'], 0, 300, '…')) ?></p><?php endif; ?>
                                <?php if (!empty($roomAmenities[(int) $room['id']])): ?><ul class="feature-list" style="margin-top:8px"><?php foreach ($roomAmenities[(int) $room['id']] as $an): ?><li><?= e($an) ?></li><?php endforeach; ?></ul><?php endif; ?>
                            </div>
                        </div>
                        <?php if (!$criteria): ?>
                            <div class="rate-row"><span class="muted">Fiyat ve müsaitlik için yukarıdan tarih seçin.</span></div>
                        <?php endif; ?>
                        <?php foreach ($rq as $q): $fid = $formIdFor($q); ?>
                            <div class="rate-row">
                                <div>
                                    <strong><?= e($q->conceptName ?: 'Konsept belirtilmemiş') ?></strong>
                                    <?php if (!empty($q->breakdown['rate_plan'])): ?><div class="muted small"><?= e($q->breakdown['rate_plan']) ?></div><?php endif; ?>
                                    <?php if ($q->kind === 'firm'): ?><span class="badge badge-success" style="margin-top:6px"><?= icon('check', 'icon-s') ?> Kesin fiyat<?= $q->source === 'contract' ? ' · anlaşmalı' : '' ?></span><?php elseif ($q->kind === 'target'): ?><span class="badge badge-teal" style="margin-top:6px">Onaya bağlı hedef teklif</span><?php endif; ?>
                                    <?php if ($q->message && $q->kind === 'target'): ?><p class="muted small" style="margin:6px 0 0"><?= e($q->message) ?></p><?php endif; ?>
                                </div>
                                <div style="display:flex;flex-direction:column;gap:4px"><?= App\Core\View::partial('partials/price_block', ['q' => $q]) ?></div>
                                <div class="stack-s">
                                    <?php if ($q->kind === 'firm'): ?>
                                        <label class="check" style="min-height:auto;padding:0"><input type="radio" name="secili_fiyat" value="<?= e($fid) ?>" data-form="<?= e($fid) ?>" data-room="<?= e($room['name']) ?>" data-total="<?= e(money($q->total, $q->currency)) ?>" data-note="<?= e(($q->conceptName ?: '') . ' · ' . $q->cancellationSummary) ?>" data-action-label="ODAYI SEÇ"<?= $defaultSel === $q ? ' checked' : '' ?>> Bu seçeneği işaretle</label>
                                        <form id="<?= e($fid) ?>" method="post" action="<?= e(url('/rezervasyon/baslat')) ?>">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="otel" value="<?= (int) $h['id'] ?>"><input type="hidden" name="oda_tipi" value="<?= (int) $q->roomId ?>"><input type="hidden" name="plan" value="<?= (int) $q->ratePlanId ?>">
                                            <input type="hidden" name="giris" value="<?= e($criteria->checkIn) ?>"><input type="hidden" name="cikis" value="<?= e($criteria->checkOut) ?>">
                                            <?php foreach ($criteria->rooms as $i => $r): ?><input type="hidden" name="oda[<?= $i ?>][y]" value="<?= $r->adults ?>"><input type="hidden" name="oda[<?= $i ?>][c]" value="<?= e(implode('-', $r->childAges)) ?>"><?php endforeach; ?>
                                            <input type="hidden" name="idem" value="<?= e($idemKey . '-' . $q->roomId . '-' . $q->ratePlanId) ?>">
                                            <button type="submit" class="btn btn-block"<?= $preview ? ' disabled' : '' ?>>ODAYI SEÇ</button>
                                        </form>
                                    <?php elseif ($q->kind === 'target' || $q->kind === 'request'): ?>
                                        <?php if ($q->kind === 'target'): ?><label class="check" style="min-height:auto;padding:0"><input type="radio" name="secili_fiyat" value="<?= e($fid) ?>" data-form="<?= e($fid) ?>" data-room="<?= e($room['name']) ?>" data-total="<?= e(money($q->total, $q->currency)) ?>" data-note="Onaya bağlı hedef teklif" data-action-label="TEKLİF İSTE"<?= $defaultSel === $q ? ' checked' : '' ?>> Bu seçeneği işaretle</label><?php endif; ?>
                                        <form id="<?= e($fid) ?>" method="get" action="<?= e(url('/teklif-iste')) ?>">
                                            <?php foreach ($offerQuery((int) $room['id'], $q) as $k => $v): if ($v === null) continue; if (is_array($v)) { foreach ($v as $i => $r) { echo '<input type="hidden" name="oda[' . (int) $i . '][y]" value="' . (int) $r['y'] . '"><input type="hidden" name="oda[' . (int) $i . '][c]" value="' . e($r['c']) . '">'; } continue; } ?><input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>"><?php endforeach; ?>
                                            <button type="submit" class="btn btn-secondary btn-block">TEKLİF İSTE</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Müsait değil</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </article>
                <?php endforeach; ?>
            </section>

            <section id="ozellikler" class="detail-section">
                <h2>Özellikler</h2>
                <?php if ($amenities): ?><ul class="amenity-grid"><?php foreach ($amenities as $a): ?><li><?= icon($a['icon'] ?: 'check') ?> <?= e($a['name']) ?></li><?php endforeach; ?></ul><?php else: ?><p class="muted">Özellik bilgisi henüz girilmedi.</p><?php endif; ?>
                <?php if ($h['beach_type'] || $h['beach_info']): ?><h3 style="margin-top:18px">Plaj</h3><p><?= e($beachLabels[$h['beach_type']] ?? '') ?><?= $h['beach_info'] ? ' — ' . e($h['beach_info']) : '' ?></p><?php endif; ?>
            </section>
            <section id="konsept" class="detail-section">
                <h2>Konsept</h2>
                <p><?= $h['concept_name'] ? e($h['concept_name']) : 'Konsept bilgisi oda fiyat seçeneklerinde belirtilir.' ?></p>
            </section>
            <section id="konum" class="detail-section">
                <h2>Konum</h2>
                <?php if ($h['address']): ?><p><?= icon('pin', 'icon-s') ?> <?= e($h['address']) ?></p><?php endif; ?>
                <?php if (!empty($h['phone']) || !empty($h['website'])): ?><p class="small"><?php if (!empty($h['phone'])): ?><?= icon('phone', 'icon-s') ?> <a href="<?= e(tel_link($h['phone'])) ?>"><?= e($h['phone']) ?></a>&nbsp;&nbsp;<?php endif; ?><?php if (!empty($h['website'])): ?><?= icon('external', 'icon-s') ?> <a href="<?= e($h['website']) ?>" target="_blank" rel="noopener nofollow">Otelin web sitesi</a><?php endif; ?></p><?php endif; ?>
                <?php if ($h['latitude'] !== null): ?>
                    <div class="map-box" style="height:340px" data-map data-tiles="<?= e(setting('map.tile_url', 'https://tile.openstreetmap.org/{z}/{x}/{y}.png')) ?>" data-attribution="<?= e(setting('map.attribution')) ?>" data-items="<?= e(json_encode([['lat' => (float) $h['latitude'], 'lng' => (float) $h['longitude'], 'name' => $h['name'], 'region' => $h['region_name'], 'price' => null, 'url' => null]], JSON_UNESCAPED_UNICODE)) ?>" role="region" aria-label="Otel konumu haritası"></div>
                    <p style="margin-top:8px"><a href="https://www.openstreetmap.org/?mlat=<?= e($h['latitude']) ?>&amp;mlon=<?= e($h['longitude']) ?>#map=16/<?= e($h['latitude']) ?>/<?= e($h['longitude']) ?>" target="_blank" rel="noopener"><?= icon('external', 'icon-s') ?> Büyük haritada aç</a></p>
                <?php else: ?><p class="muted">Harita konumu henüz girilmedi.</p><?php endif; ?>
                <?php if (!empty($h['source_attribution'])): ?><p class="source-note"><?= icon('info', 'icon-s') ?> <?= e($h['source_attribution']) ?><?= !empty($h['last_synced_at']) ? ' · son güncelleme ' . e(tr_datetime($h['last_synced_at'])) : '' ?></p><?php endif; ?>
                <?php if ($h['center_distance_km'] !== null): ?><p class="muted small">Merkeze uzaklık: <?= e(str_replace('.', ',', (string) (float) $h['center_distance_km'])) ?> km</p><?php endif; ?>
            </section>
            <section id="cocuk" class="detail-section"><h2>Çocuk politikası</h2><?= $h['child_policy'] ? '<div class="prose">' . nl2p($h['child_policy']) . '</div>' : '<p class="muted">Bilgi girilmedi; rezervasyon öncesi destek ekibine danışabilirsiniz.</p>' ?><?php if ($h['pet_policy']): ?><h3>Evcil hayvan</h3><p><?= e($h['pet_policy']) ?></p><?php endif; ?></section>
            <section id="iptal" class="detail-section"><h2>İptal ve ödeme koşulları</h2>
                <h3>İptal</h3><?= $h['cancellation_policy'] ? '<div class="prose">' . nl2p($h['cancellation_policy']) . '</div>' : '<p class="muted">Seçilen fiyat seçeneğinin iptal koşulu rezervasyon adımında gösterilir.</p>' ?>
                <h3>Ödeme</h3><?= $h['payment_policy'] ? '<div class="prose">' . nl2p($h['payment_policy']) . '</div>' : '<p class="muted">Ödeme koşulu rezervasyon adımında gösterilir.</p>' ?>
            </section>
            <section id="onemli" class="detail-section"><h2>Önemli bilgiler</h2><?= $h['important_info'] ? '<div class="prose">' . nl2p($h['important_info']) . '</div>' : '<p class="muted">Ek bilgi bulunmuyor.</p>' ?></section>
        </div>

        <aside aria-label="Rezervasyon özeti">
            <div class="card booking-card"><div class="card-body">
                <h2 style="font-size:1.15rem;margin:0">Rezervasyon özeti</h2>
                <ul class="summary-list">
                    <li><span>Tarihler</span><strong><?= $criteria ? e(tr_date_short($criteria->checkIn)) . ' – ' . e(tr_date_short($criteria->checkOut)) : '—' ?></strong></li>
                    <li><span>Gece</span><strong><?= $criteria ? $criteria->nights() : '—' ?></strong></li>
                    <li><span>Konuklar</span><strong><?= $criteria ? e($criteria->summary()) : '—' ?></strong></li>
                    <li><span>Seçilen oda</span><strong data-sum-room><?= $defaultSel ? e($defaultSel->roomName ?: 'Otel geneli') : '—' ?></strong></li>
                    <li><span>Koşul</span><strong data-sum-note class="small"><?= $defaultSel ? e($defaultSel->kind === 'target' ? 'Onaya bağlı hedef teklif' : trim(($defaultSel->conceptName ?: '') . ' · ' . $defaultSel->cancellationSummary, ' ·')) : '—' ?></strong></li>
                </ul>
                <div class="total-line"><span>Toplam <small class="muted">(vergiler dahil)</small></span><strong data-sum-total><?= $defaultSel ? e(money($defaultSel->total, $defaultSel->currency)) : '—' ?></strong></div>
                <?php if ($defaultSel): ?>
                    <button type="button" class="btn btn-lg btn-block" data-sum-submit data-target-form="<?= e($formIdFor($defaultSel)) ?>"><?= $defaultSel->kind === 'firm' ? 'ODAYI SEÇ' : 'TEKLİF İSTE' ?></button>
                <?php elseif ($criteria): ?>
                    <a class="btn btn-lg btn-block" href="<?= e(url('/teklif-iste', $offerQuery(null, null))) ?>">TEKLİF İSTE</a>
                    <p class="muted small" style="margin:0">Bu tarihler için anlık fiyat yok. Ekibimiz sizin için otelden teklif alabilir.</p>
                <?php else: ?>
                    <a class="btn btn-lg btn-block" href="#odalar">TARİH SEÇ</a>
                <?php endif; ?>
                <p class="muted small" style="margin:0"><?= icon('shield', 'icon-s') ?> Kesin fiyat yalnız geçerli otel anlaşmasıyla sunulur. Fiyatlar onay öncesi yeniden doğrulanır.</p>
            </div></div>
        </aside>
    </div>
</div>
<div class="mobile-bar" role="region" aria-label="Seçim özeti">
    <div class="mb-price"><small data-sum-room><?= $defaultSel ? e($defaultSel->roomName ?: 'Otel geneli') : ($criteria ? 'Fiyat bulunamadı' : 'Tarih seçin') ?></small><strong data-sum-total><?= $defaultSel ? e(money($defaultSel->total, $defaultSel->currency)) : '—' ?></strong></div>
    <?php if ($defaultSel): ?><button type="button" class="btn" data-sum-submit data-target-form="<?= e($formIdFor($defaultSel)) ?>"><?= $defaultSel->kind === 'firm' ? 'ODAYI SEÇ' : 'TEKLİF İSTE' ?></button>
    <?php else: ?><a class="btn" href="<?= $criteria ? e(url('/teklif-iste', $offerQuery(null, null))) : '#odalar' ?>"><?= $criteria ? 'TEKLİF İSTE' : 'TARİH SEÇ' ?></a><?php endif; ?>
</div>
