<ol class="steps" aria-label="Rezervasyon adımları">
    <?php foreach (['Otel ve oda seçimi', 'Misafir bilgileri', 'Fiyat ve koşul kontrolü', 'Onay'] as $i => $label): $n = $i + 1; ?>
        <li class="<?= $n < $step ? 'is-done' : ($n === $step ? 'is-current' : '') ?>"<?= $n === $step ? ' aria-current="step"' : '' ?>><?= e($label) ?></li>
    <?php endforeach; ?>
</ol>
