<?php
/** @var ?App\DTO\StayCriteria $criteria @var array $regions */
$criteria = $criteria ?? null;
$regionId = (int) ($regionId ?? 0);
$extra = $extra ?? [];
$state = $criteria ? array_map(static fn ($r) => ['y' => $r->adults, 'c' => $r->childAges], $criteria->rooms) : [['y' => 2, 'c' => []]];
$today = date('Y-m-d');
$maxNights = (int) setting('booking.max_nights', '30');
$uid = substr(md5((string) mt_rand()), 0, 6);
?>
<form class="search-box<?= !empty($compact) ? ' search-compact' : '' ?><?= !empty($hideRegion) ? ' no-region' : '' ?>" method="get" action="<?= e($action ?? url('/oteller')) ?>" role="search" aria-label="Otel arama">
    <?php foreach ($extra as $k => $v): if (is_scalar($v) && $v !== ''): ?><input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>"><?php endif; endforeach; ?>
    <?php if (empty($hideRegion)): ?>
    <div class="field">
        <label for="bolge-<?= $uid ?>">Antalya / bölge</label>
        <select id="bolge-<?= $uid ?>" name="bolge">
            <option value="">Tüm Antalya</option>
            <?php foreach ($regions as $r): ?>
                <option value="<?= (int) $r['id'] ?>"<?= $regionId === (int) $r['id'] ? ' selected' : '' ?>><?= $r['parent_id'] ? '— ' : '' ?><?= e($r['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>
    <div class="field" data-daterange data-min="<?= e($today) ?>" data-max="<?= e(date('Y-m-d', strtotime('+18 months'))) ?>" data-max-nights="<?= $maxNights ?>">
        <span class="label" id="dr-label-<?= $uid ?>">Giriş – Çıkış</span>
        <button type="button" class="trigger" data-dr-trigger aria-haspopup="dialog" aria-expanded="false" aria-labelledby="dr-label-<?= $uid ?>" hidden><?= icon('calendar') ?><span class="trigger-text"></span></button>
        <div class="date-fallback">
            <div><label for="giris-<?= $uid ?>" class="small">Giriş tarihi</label><input type="date" id="giris-<?= $uid ?>" name="giris" data-dr-in min="<?= e($today) ?>" value="<?= e($criteria?->checkIn ?? '') ?>"></div>
            <div><label for="cikis-<?= $uid ?>" class="small">Çıkış tarihi</label><input type="date" id="cikis-<?= $uid ?>" name="cikis" data-dr-out min="<?= e(date('Y-m-d', strtotime('+1 day'))) ?>" value="<?= e($criteria?->checkOut ?? '') ?>"></div>
        </div>
        <?= field_error('giris') ?><?= field_error('cikis') ?>
    </div>
    <div class="field" data-guests data-max-rooms="<?= (int) setting('booking.max_rooms', '5') ?>">
        <span class="label" id="gp-label-<?= $uid ?>">Konuklar</span>
        <button type="button" class="trigger" data-gp-trigger aria-haspopup="dialog" aria-expanded="false" aria-labelledby="gp-label-<?= $uid ?>" hidden><?= icon('users') ?><span class="trigger-text"></span></button>
        <div class="guest-fallback">
            <div><label for="y-<?= $uid ?>" class="small">Yetişkin</label><select id="y-<?= $uid ?>" name="yetiskin"><?php for ($i = 1; $i <= 12; $i++): ?><option<?= ($criteria ? $criteria->adults() : 2) === $i ? ' selected' : '' ?>><?= $i ?></option><?php endfor; ?></select></div>
            <div><label for="c-<?= $uid ?>" class="small">Çocuk</label><select id="c-<?= $uid ?>" name="cocuk"><?php for ($i = 0; $i <= 6; $i++): ?><option<?= ($criteria ? $criteria->children() : 0) === $i ? ' selected' : '' ?>><?= $i ?></option><?php endfor; ?></select></div>
            <div><label for="o-<?= $uid ?>" class="small">Oda</label><select id="o-<?= $uid ?>" name="oda_sayisi"><?php for ($i = 1; $i <= 5; $i++): ?><option<?= ($criteria ? $criteria->roomCount() : 1) === $i ? ' selected' : '' ?>><?= $i ?></option><?php endfor; ?></select></div>
            <div class="span-all"><label for="a-<?= $uid ?>" class="small">Çocuk yaşları (virgülle)</label><input id="a-<?= $uid ?>" name="yaslar" value="<?= e($criteria ? implode(', ', $criteria->allChildAges()) : '') ?>" placeholder="örn. 5, 9"></div>
        </div>
        <div data-gp-hidden></div>
        <script type="application/json" data-gp-state><?= json_encode($state) ?></script>
        <?= field_error('oda') ?><?= field_error('yaslar') ?>
    </div>
    <button type="submit" class="btn btn-lg"><?= icon('search') ?> <?= e($submitLabel ?? 'OTEL ARA') ?></button>
</form>
