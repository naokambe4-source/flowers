<?php /** @var array $breakdown @var array $b */ ?>
<table class="price-breakdown">
    <caption class="sr-only">Fiyat dökümü</caption>
    <tbody>
    <?php foreach (($breakdown['lines'] ?? []) as $line): ?>
        <tr><td><?= e($line['label']) ?></td><td class="<?= (int) $line['amount'] < 0 ? 'neg' : '' ?>"><?= e(money((int) $line['amount'], $b['currency'])) ?></td></tr>
    <?php endforeach; ?>
    <tr class="total"><td>Toplam (vergiler dahil)</td><td><?= e(money((int) $b['total_minor'], $b['currency'])) ?></td></tr>
    </tbody>
</table>
<p class="muted small" style="margin-top:8px">
    Fiyat kaynağı: <?= e($breakdown['price_source'] ?? '—') ?>.
    <?php if (!empty($breakdown['tax_included'])): ?>Vergiler fiyata dahildir<?= (int) $b['tax_minor'] > 0 ? ' (içerdiği vergi: ' . e(money((int) $b['tax_minor'], $b['currency'])) . ')' : '' ?>.<?php else: ?>Vergiler toplama eklenmiştir.<?php endif; ?>
    <?php if (!empty($b['verified_savings_minor'])): ?> Aynı tarih, oda, konuk, konsept, vergi ve iptal koşullarında doğrulanmış karşılaştırma ile avantaj: <strong><?= e(money((int) $b['verified_savings_minor'], $b['currency'])) ?></strong>.<?php endif; ?>
</p>
