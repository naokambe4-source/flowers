<?php
/** @var ?App\DTO\PriceQuote $q */
$q = $q ?? null;
if ($q === null): ?>
    <span class="price-label">Fiyat</span>
    <span class="price-sub">Fiyatları görmek için tarih ve konuk seçin.</span>
<?php elseif ($q->kind === 'firm'): ?>
    <span class="price-label">Üyeye özel toplam fiyat</span>
    <?php if ($q->referenceTotal && $q->verifiedSavings): ?><span class="price-old" title="Aynı koşullarda doğrulanmış karşılaştırma fiyatı"><?= e(money($q->referenceTotal, $q->currency)) ?></span><?php endif; ?>
    <span class="price-main"><?= e(money($q->total, $q->currency)) ?></span>
    <span class="price-sub"><?= (int) $q->nights ?> gece toplamı · vergiler dahil<?= $q->nights > 1 ? ' · gecelik ort. ' . e(money($q->perNight(), $q->currency)) : '' ?></span>
    <?php if ($q->verifiedSavings): ?><span class="saving"><?= icon('check-circle', 'icon-s') ?> <?= e(money($q->verifiedSavings, $q->currency)) ?> doğrulanmış avantaj</span>
    <?php elseif ($q->discountTotal > 0): ?><span class="member-discount" title="Anlaşmalı fiyat üzerinden uygulanan kurum/üye indirimi (piyasa karşılaştırması değildir)"><?= icon('tag', 'icon-s') ?> Kurum indirimi dahil · <?= e(money($q->discountTotal, $q->currency)) ?></span><?php endif; ?>
<?php elseif ($q->kind === 'target'): ?>
    <span class="price-label">Onaya bağlı hedef teklif</span>
    <?php if ($q->referenceTotal && $q->discountTotal > 0): ?><span class="price-old" title="Doğrulanmış referans fiyat"><?= e(money($q->referenceTotal, $q->currency)) ?></span><?php endif; ?>
    <span class="price-main target"><?= e(money($q->total, $q->currency)) ?></span>
    <span class="price-sub"><?= (int) $q->nights ?> gece toplamı · <?= $q->taxIncluded ? 'vergiler dahil' : 'vergiler hariç' ?> · kesin fiyat otel onayıyla belirlenir</span>
    <?php if ($q->discountTotal > 0): ?><span class="saving"><?= icon('tag', 'icon-s') ?> <?= e(money($q->discountTotal, $q->currency)) ?> hedef avantaj</span><?php endif; ?>
<?php elseif ($q->kind === 'request'): ?>
    <span class="price-label">Fiyat</span>
    <span class="price-sub"><?= e($q->message ?? 'Bu tarihler için fiyat tanımlı değil.') ?></span>
<?php else: ?>
    <span class="price-label">Müsaitlik</span>
    <span class="price-sub" style="color:var(--c-warning);font-weight:600"><?= e($q->message ?? 'Seçilen tarihlerde müsait değil.') ?></span>
<?php endif; ?>
<?php if ($q && in_array($q->kind, ['firm', 'target'], true) && $q->cancellationSummary): ?>
    <span class="policy<?= $q->refundable ? '' : ' nonref' ?>"><?= icon($q->refundable ? 'check' : 'info', 'icon-s') ?> <?= e($q->cancellationSummary) ?></span>
<?php endif; ?>
