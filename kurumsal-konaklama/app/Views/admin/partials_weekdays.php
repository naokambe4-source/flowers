<fieldset><legend>Haftanın günleri <button type="button" class="link-btn small" data-check-all="weekdays[]">Tümünü seç / kaldır</button></legend>
<div class="row"><?php foreach ([1 => 'Pzt', 2 => 'Sal', 3 => 'Çar', 4 => 'Per', 5 => 'Cum', 6 => 'Cmt', 7 => 'Paz'] as $n => $l): ?><label class="check" style="min-height:36px"><input type="checkbox" name="weekdays[]" value="<?= $n ?>" checked> <?= $l ?></label><?php endforeach; ?></div>
<p class="hint">Hiçbiri seçilmezse tüm günlere uygulanır.</p></fieldset>
