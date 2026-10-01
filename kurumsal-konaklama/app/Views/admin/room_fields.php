<?php $r = $r ?? null; $p = $r ? 'room_' . $r['id'] . '_' : 'new_'; ?>
<div class="form-grid form-grid-3">
    <div class="field"><label for="<?= $p ?>name">Oda adı *</label><input id="<?= $p ?>name" name="name" value="<?= e($r['name'] ?? '') ?>" required></div>
    <div class="field"><label for="<?= $p ?>bed">Yatak tipi</label><input id="<?= $p ?>bed" name="bed_type" value="<?= e($r['bed_type'] ?? '') ?>" placeholder="1 çift + 1 tek"></div>
    <div class="field"><label for="<?= $p ?>view">Manzara</label><input id="<?= $p ?>view" name="view_type" value="<?= e($r['view_type'] ?? '') ?>" placeholder="Deniz manzaralı"></div>
    <div class="field"><label for="<?= $p ?>m2">Büyüklük (m²)</label><input id="<?= $p ?>m2" type="number" min="0" name="size_m2" value="<?= e($r['size_m2'] ?? '') ?>"></div>
    <div class="field"><label for="<?= $p ?>ma">En fazla yetişkin *</label><input id="<?= $p ?>ma" type="number" min="1" max="10" name="max_adults" value="<?= e($r['max_adults'] ?? 2) ?>" required></div>
    <div class="field"><label for="<?= $p ?>mc">En fazla çocuk *</label><input id="<?= $p ?>mc" type="number" min="0" max="6" name="max_children" value="<?= e($r['max_children'] ?? 1) ?>" required></div>
    <div class="field"><label for="<?= $p ?>mo">En fazla toplam kişi *</label><input id="<?= $p ?>mo" type="number" min="1" max="12" name="max_occupancy" value="<?= e($r['max_occupancy'] ?? 3) ?>" required></div>
    <div class="field"><label for="<?= $p ?>sort">Sıra</label><input id="<?= $p ?>sort" type="number" name="sort" value="<?= e($r['sort'] ?? 0) ?>"></div>
    <div class="field"><input type="hidden" name="is_active" value="0"><label class="check"><input type="checkbox" name="is_active" value="1"<?= ($r['is_active'] ?? 1) ? ' checked' : '' ?>> Aktif</label></div>
    <div class="field span-3" style="grid-column:1/-1"><label for="<?= $p ?>desc">Açıklama</label><textarea id="<?= $p ?>desc" name="description" rows="2"><?= e($r['description'] ?? '') ?></textarea></div>
</div>
<fieldset><legend>Oda olanakları</legend><div class="grid-3" style="gap:0 16px"><?php foreach ($roomAmenities as $a): ?><label class="check" style="min-height:36px"><input type="checkbox" name="amenities[]" value="<?= (int) $a['id'] ?>"<?= in_array((int) $a['id'], $selected, true) ? ' checked' : '' ?>> <?= e($a['name']) ?></label><?php endforeach; ?></div></fieldset>
