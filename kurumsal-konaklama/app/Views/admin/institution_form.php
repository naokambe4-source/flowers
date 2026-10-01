<?php $e = $edit ?? null; ?>
<form method="post" action="<?= e(url('/yonetim/kurumlar')) ?>" enctype="multipart/form-data"><?= csrf_field() ?><?php if ($e): ?><input type="hidden" name="id" value="<?= (int) $e['id'] ?>"><?php endif; ?>
    <div class="form-grid">
        <?= f_input('name', 'Kurum adı', $e['name'] ?? '', 'text', ['required' => true]) ?>
        <?= f_select('institution_type_id', 'Kurum tipi', $types, $e['institution_type_id'] ?? '', 'Seçiniz') ?>
        <?= f_input('contact_name', 'Yetkili kişi', $e['contact_name'] ?? '') ?>
        <?= f_input('phone', 'Telefon', $e['phone'] ?? '', 'tel') ?>
        <?= f_input('email', 'E-posta', $e['email'] ?? '', 'email') ?>
        <?= f_select('price_group_id', 'Fiyat grubu', $groups, $e['price_group_id'] ?? '', 'Yok') ?>
        <?= f_input('discount_bp', 'Kuruma özel indirim (%)', bp_input(isset($e['discount_bp']) ? (int) $e['discount_bp'] : null), 'text', ['inputmode' => 'decimal'], 'Boşsa fiyat grubu veya genel indirim (%' . bp_input((int) setting('pricing.default_discount_bp', '1000')) . ') uygulanır.') ?>
        <div class="field"><label for="logo">Logo</label><input type="file" id="logo" name="logo" accept="image/png,image/jpeg,image/webp"></div>
        <?= f_textarea('admin_notes', 'Yönetici notları', $e['admin_notes'] ?? '', 2) ?>
    </div>
    <?= f_check('is_active', 'Aktif (pasif kurumun üyeleri giriş yapamaz)', (bool) ($e['is_active'] ?? 1)) ?>
    <button class="btn" type="submit">KAYDET</button>
</form>
