<?php
/** @var array $result */
$items = $result['items'];
$page = $result['page'];
$regionId = (int) ($filters['region_id'] ?? 0);
$baseQuery = $query;
$listQ = $baseQuery; unset($listQ['gorunum']);
$mapQ = $baseQuery; $mapQ['gorunum'] = 'harita';
?>
<div class="container">
    <div class="page-head">
        <h1>Otel ara</h1>
        <p><?= $criteria ? e(tr_date($criteria->checkIn)) . ' – ' . e(tr_date($criteria->checkOut)) . ' · ' . $criteria->nights() . ' gece · ' . e($criteria->summary()) : 'Fiyatları görmek için tarih ve konuk seçin.' ?></p>
    </div>
    <?= App\Core\View::partial('partials/search_form', ['regions' => $regions, 'criteria' => $criteria, 'regionId' => $regionId, 'compact' => true]) ?>
    <?php foreach ($notices as $n): ?><div class="alert alert-info" style="margin-top:14px"><?= icon('info') ?><div><?= e($n) ?></div></div><?php endforeach; ?>

    <div class="results-layout">
        <aside class="filters card" id="filtreler" aria-label="Filtreler">
            <form method="get" action="<?= e(url('/oteller')) ?>">
                <?php if ($criteria): foreach (['giris' => $criteria->checkIn, 'cikis' => $criteria->checkOut] as $k => $v): ?><input type="hidden" name="<?= $k ?>" value="<?= e($v) ?>"><?php endforeach; foreach ($criteria->rooms as $i => $r): ?><input type="hidden" name="oda[<?= $i ?>][y]" value="<?= $r->adults ?>"><input type="hidden" name="oda[<?= $i ?>][c]" value="<?= e(implode('-', $r->childAges)) ?>"><?php endforeach; endif; ?>
                <?php if ($viewMode === 'harita'): ?><input type="hidden" name="gorunum" value="harita"><?php endif; ?>
                <input type="hidden" name="sirala" value="<?= e($sort) ?>">
                <div class="filter-head"><h2 style="font-size:1.05rem;margin:0">Filtreler <?php if ($activeCount): ?><span class="active-count"><?= $activeCount ?></span><?php endif; ?></h2><button type="button" class="icon-btn close-filters" aria-label="Filtreleri kapat"><?= icon('close') ?></button></div>
                <div class="filter-group">
                    <label for="f-bolge"><h3>Bölge</h3></label>
                    <select id="f-bolge" name="bolge"><option value="">Tüm Antalya</option><?php foreach ($regions as $r): ?><option value="<?= (int) $r['id'] ?>"<?= $regionId === (int) $r['id'] ? ' selected' : '' ?>><?= $r['parent_id'] ? '— ' : '' ?><?= e($r['name']) ?></option><?php endforeach; ?></select>
                </div>
                <fieldset class="filter-group" style="border:0;border-bottom:1px solid var(--c-line);border-radius:0;margin:0">
                    <legend style="padding:0"><h3 style="margin:0">Toplam fiyat (TL)</h3></legend>
                    <?php if (!$criteria): ?><p class="muted small">Fiyat filtresi için tarih seçin.</p><?php endif; ?>
                    <div class="row" style="flex-wrap:nowrap">
                        <label class="sr-only" for="f-min">En az</label><input id="f-min" name="fiyat_min" inputmode="numeric" placeholder="En az" value="<?= e($filters['price_min'] !== null ? (string) intdiv($filters['price_min'], 100) : '') ?>"<?= $criteria ? '' : ' disabled' ?>>
                        <label class="sr-only" for="f-max">En çok</label><input id="f-max" name="fiyat_max" inputmode="numeric" placeholder="En çok" value="<?= e($filters['price_max'] !== null ? (string) intdiv($filters['price_max'], 100) : '') ?>"<?= $criteria ? '' : ' disabled' ?>>
                    </div>
                </fieldset>
                <fieldset class="filter-group" style="border:0;border-bottom:1px solid var(--c-line);border-radius:0;margin:0">
                    <legend style="padding:0"><h3 style="margin:0">Yıldız</h3></legend>
                    <?php for ($s = 5; $s >= 3; $s--): ?><label class="check"><input type="checkbox" name="yildiz[]" value="<?= $s ?>"<?= in_array($s, $filters['stars'], true) ? ' checked' : '' ?>><span><?= $s ?> yıldız</span></label><?php endfor; ?>
                </fieldset>
                <div class="filter-group">
                    <label for="f-konsept"><h3>Konsept</h3></label>
                    <select id="f-konsept" name="konsept"><option value="">Tümü</option><?php foreach ($concepts as $c): ?><option value="<?= (int) $c['id'] ?>"<?= (int) $filters['concept_id'] === (int) $c['id'] ? ' selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select>
                    <label class="check"><input type="checkbox" name="kahvalti" value="1"<?= $filters['breakfast'] ? ' checked' : '' ?>><span>Kahvaltı dahil</span></label>
                    <label class="check"><input type="checkbox" name="her_sey_dahil" value="1"<?= $filters['all_inclusive'] ? ' checked' : '' ?>><span>Her şey dahil</span></label>
                    <label class="check"><input type="checkbox" name="ucretsiz_iptal" value="1"<?= $filters['free_cancel'] ? ' checked' : '' ?><?= $criteria ? '' : ' disabled' ?>><span>Ücretsiz iptal<?= $criteria ? '' : ' <small class="muted">(tarih seçin)</small>' ?></span></label>
                </div>
                <fieldset class="filter-group" style="border:0;border-radius:0;margin:0">
                    <legend style="padding:0"><h3 style="margin:0">Tesis özellikleri</h3></legend>
                    <?php foreach (App\Repositories\HotelRepository::FILTER_KEYS as $k => $label): ?><label class="check"><input type="checkbox" name="ozellik[]" value="<?= e($k) ?>"<?= in_array($k, $filters['features'], true) ? ' checked' : '' ?>><span><?= e($label) ?></span></label><?php endforeach; ?>
                </fieldset>
                <div class="filter-actions">
                    <a class="btn btn-secondary" href="<?= e(url('/oteller', $criteria ? $criteria->toQuery() + ['bolge' => $regionId ?: null] : ['bolge' => $regionId ?: null])) ?>">Tümünü temizle</a>
                    <button type="submit" class="btn" style="flex:1">FİLTRELE</button>
                </div>
            </form>
        </aside>

        <section aria-labelledby="sonuc-baslik">
            <div class="results-toolbar">
                <h2 id="sonuc-baslik" class="count" style="font-size:1.05rem;margin:0" aria-live="polite"><?= (int) $page['total'] ?> otel bulundu</h2>
                <div class="row">
                    <button type="button" class="btn btn-secondary filter-chip-btn" data-open-filters aria-controls="filtreler" aria-expanded="false"><?= icon('filter') ?> Filtreler <?php if ($activeCount): ?><span class="active-count"><?= $activeCount ?></span><?php endif; ?></button>
                    <form method="get" action="<?= e(url('/oteller')) ?>" class="row" style="gap:6px">
                        <?php foreach ($baseQuery as $k => $v): if ($k === 'sirala') continue; if (is_array($v)): foreach ($v as $kk => $vv): if (is_array($vv)): foreach ($vv as $k3 => $v3): ?><input type="hidden" name="<?= e($k) ?>[<?= e($kk) ?>][<?= e($k3) ?>]" value="<?= e($v3) ?>"><?php endforeach; else: ?><input type="hidden" name="<?= e($k) ?>[]" value="<?= e($vv) ?>"><?php endif; endforeach; else: ?><input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>"><?php endif; endforeach; ?>
                        <label for="sirala" class="sr-only">Sırala</label>
                        <select id="sirala" name="sirala"><?php foreach (App\Services\SearchService::SORTS as $k => $l): ?><option value="<?= e($k) ?>"<?= $sort === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select>
                        <button class="btn btn-secondary" type="submit">Sırala</button>
                    </form>
                    <div class="seg" role="group" aria-label="Görünüm">
                        <a href="<?= e(url('/oteller', $listQ)) ?>" aria-current="<?= $viewMode === 'liste' ? 'true' : 'false' ?>"><?= icon('list', 'icon-s') ?> Liste</a>
                        <a href="<?= e(url('/oteller', $mapQ)) ?>" aria-current="<?= $viewMode === 'harita' ? 'true' : 'false' ?>"><?= icon('map', 'icon-s') ?> Harita</a>
                    </div>
                </div>
            </div>

            <?php if ($viewMode === 'harita'): ?>
                <div class="map-box" data-map data-tiles="<?= e(setting('map.tile_url', 'https://tile.openstreetmap.org/{z}/{x}/{y}.png')) ?>" data-attribution="<?= e(setting('map.attribution')) ?>" data-items="<?= e(json_encode($mapItems, JSON_UNESCAPED_UNICODE)) ?>" role="region" aria-label="Otellerin haritası"></div>
                <p class="muted small" style="margin-top:8px"><?= count($mapItems) ?> otel haritada gösteriliyor. Konumu girilmemiş oteller listede yer alır.</p>
            <?php elseif (!$items): ?>
                <div class="empty">
                    <?= icon('search', 'icon-l') ?>
                    <h3>Kriterlerinize uygun otel bulunamadı</h3>
                    <p>Filtreleri azaltmayı veya farklı bir bölge seçmeyi deneyin. Aradığınız konaklama için ekibimizden özel teklif de isteyebilirsiniz.</p>
                    <div class="row" style="justify-content:center"><a class="btn btn-secondary" href="<?= e(url('/oteller')) ?>">Filtreleri temizle</a><a class="btn" href="<?= e(url('/teklif-iste', $criteria ? $criteria->toQuery() + ['bolge' => $regionId ?: null] : [])) ?>">TEKLİF İSTE</a></div>
                </div>
            <?php else: ?>
                <div class="stack">
                    <?php foreach ($items as $h): ?><?= App\Core\View::partial('partials/hotel_card', ['h' => $h, 'horizontal' => true, 'query' => $detailQuery]) ?><?php endforeach; ?>
                </div>
                <?= pagination_links($page, $query) ?>
            <?php endif; ?>
        </section>
    </div>
</div>
