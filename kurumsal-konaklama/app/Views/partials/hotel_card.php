<?php
/** @var array $h @var array $query */
$q = $h['quote'] ?? null;
$link = url('/oteller/' . $h['slug'], $query ?? []);
$img = hotel_image_url($h['cover_image_id'] ? (int) $h['cover_image_id'] : null, 'medium');
?>
<article class="hotel-card<?= !empty($horizontal) ? ' horizontal' : '' ?>">
    <div class="hotel-media">
        <?php if ($img): ?><img src="<?= e($img) ?>" alt="<?= e($h['name']) ?> otel fotoğrafı" loading="lazy" decoding="async">
        <?php else: ?><div class="no-photo"><div><?= icon('image') ?>Bu otel için henüz fotoğraf eklenmedi</div></div><?php endif; ?>
        <form method="post" action="<?= e(url('/favoriler/' . (int) $h['id'])) ?>" data-fav data-no-lock>
            <?= csrf_field() ?>
            <button type="submit" class="fav-btn" aria-pressed="<?= !empty($h['is_favorite']) ? 'true' : 'false' ?>" aria-label="<?= !empty($h['is_favorite']) ? 'Favorilerden çıkar' : 'Favorilere ekle' ?>: <?= e($h['name']) ?>"><?= icon('heart') ?></button>
        </form>
    </div>
    <div class="hotel-body">
        <div class="meta-line"><?= App\Core\View::partial('partials/stars', ['stars' => $h['stars']]) ?><?php if (!empty($h['is_contracted'])): ?><span class="badge badge-gold">Anlaşmalı otel</span><?php endif; ?></div>
        <h3><a href="<?= e($link) ?>"><?= e($h['name']) ?></a></h3>
        <div class="meta-line">
            <span><?= icon('pin', 'icon-s') ?> <?= e(trim(($h['region_name'] ?? '') . ($h['district'] ? ', ' . $h['district'] : ''), ', ')) ?></span>
            <?php if (!empty($h['concept_name'])): ?><span><?= icon('restaurant', 'icon-s') ?> <?= e($h['concept_name']) ?></span><?php endif; ?>
            <?php if ($h['sea_distance_m'] !== null && $h['sea_distance_m'] !== ''): ?><span><?= icon('waves', 'icon-s') ?> Denize <?= (int) $h['sea_distance_m'] === 0 ? 'sıfır' : (int) $h['sea_distance_m'] . ' m' ?></span><?php endif; ?>
        </div>
        <?php if (!empty($h['short_description'])): ?><p class="muted small" style="margin:0"><?= e(mb_strimwidth($h['short_description'], 0, 170, '…')) ?></p><?php endif; ?>
        <?php if (!empty($h['amenities'])): ?>
            <ul class="feature-list"><?php foreach ($h['amenities'] as $a): ?><li><?= icon($a['icon'] ?: 'check') ?><?= e($a['name']) ?></li><?php endforeach; ?></ul>
        <?php endif; ?>
        <?php if ($q && $q->roomName && in_array($q->kind, ['firm', 'target'], true)): ?><p class="small" style="margin:0"><?= icon('bed', 'icon-s') ?> <?= e($q->roomName) ?><?= $q->conceptName ? ' · ' . e($q->conceptName) : '' ?></p><?php endif; ?>
    </div>
    <div class="hotel-price">
        <div class="stack-s" style="display:flex;flex-direction:column;gap:4px"><?= App\Core\View::partial('partials/price_block', ['q' => $q]) ?></div>
        <a class="btn" href="<?= e($link) ?>">DETAYLARI GÖR <?= icon('arrow-right', 'icon-s') ?></a>
    </div>
</article>
