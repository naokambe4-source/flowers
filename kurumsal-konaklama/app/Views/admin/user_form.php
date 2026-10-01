<?php $types = App\Controllers\Admin\UserController::TYPES; ?>
<div class="admin-title"><div><p><a href="<?= e(url('/yonetim/uyeler')) ?>">← Üyeler</a></p><h1><?= $u ? e($u['first_name'] . ' ' . $u['last_name']) : 'Yeni üye' ?></h1><?php if ($u): ?><p><?= App\Core\View::partial('partials/status', ['status' => $u['status'] === 'pending' ? 'pending_user' : $u['status']]) ?> · Kayıt: <?= e(tr_datetime($u['created_at'])) ?> · Son giriş: <?= e(tr_datetime($u['last_login_at'])) ?></p><?php endif; ?></div></div>
<?php if (!empty($resetLink)): ?><div class="alert alert-warning"><?= icon('key') ?><div style="flex:1"><strong>Parola oluşturma bağlantısı</strong> (tek kullanımlık, süreli). Bu sayfadan ayrıldığınızda tekrar gösterilmez.<div class="copy-field" style="margin-top:8px"><input id="reset-link" readonly value="<?= e($resetLink) ?>"><button type="button" class="btn btn-secondary" data-copy="reset-link">Kopyala</button></div></div></div><?php endif; ?>
<div class="grid-2">
<div class="card"><div class="card-head"><h2>Bilgiler</h2></div><div class="card-body">
<?php if (can('users.manage')): ?>
<form method="post" action="<?= e(url('/yonetim/uyeler')) ?>"><?= csrf_field() ?><?php if ($u): ?><input type="hidden" name="id" value="<?= (int) $u['id'] ?>"><?php endif; ?>
    <div class="form-grid">
        <?= f_input('first_name', 'Ad', $u['first_name'] ?? '', 'text', ['required' => true]) ?>
        <?= f_input('last_name', 'Soyad', $u['last_name'] ?? '', 'text', ['required' => true]) ?>
        <?= f_input('email', 'E-posta', $u['email'] ?? '', 'email', ['required' => true]) ?>
        <?= f_input('phone', 'Telefon', $u['phone'] ?? '', 'tel') ?>
        <?= f_select('institution_id', 'Kurum', $institutions, $u['institution_id'] ?? ($defaultInst ?? ''), 'Kurumsuz (personel)') ?>
        <?= f_input('department', 'Departman', $u['department'] ?? '') ?>
        <?= f_select('membership_type', 'Üyelik tipi', $types, $u['membership_type'] ?? 'personel') ?>
        <?= f_select('role_id', 'Rol', $roles, $u['role_id'] ?? array_search('Standart Üye', $roles, true), '', '', true) ?>
        <?= f_select('status', 'Durum', ['active' => 'Aktif', 'pending' => 'Onay bekliyor', 'passive' => 'Pasif', 'suspended' => 'Askıda', 'rejected' => 'Reddedildi'], $u['status'] ?? 'active') ?>
    </div>
    <?php if (!$u): ?><label class="check"><input type="checkbox" name="send_invite" value="1" checked> Parola oluşturma (davet) bağlantısı gönder</label><?php endif; ?>
    <button class="btn" type="submit">KAYDET</button>
</form>
<?php else: ?><dl class="kv"><dt>E-posta</dt><dd><?= e($u['email']) ?></dd><dt>Telefon</dt><dd><?= e($u['phone']) ?></dd></dl><?php endif; ?>
</div></div>
<?php if ($u && can('users.manage')): ?>
<div class="stack">
    <div class="card"><div class="card-head"><h2>Hesap işlemleri</h2></div><div class="card-body stack-s">
        <div class="row">
            <?php foreach (['active' => ['Aktifleştir', 'btn-secondary'], 'passive' => ['Pasifleştir', 'btn-secondary'], 'suspended' => ['Askıya al', 'btn-danger']] as $st => [$lbl, $cls]): if ($u['status'] === $st) continue; ?>
                <form method="post" action="<?= e(url('/yonetim/uyeler/' . $u['id'] . '/durum')) ?>" data-confirm="Hesap durumu değiştirilsin mi?"><?= csrf_field() ?><input type="hidden" name="durum" value="<?= $st ?>"><button class="btn <?= $cls ?> btn-sm" type="submit"><?= $lbl ?></button></form>
            <?php endforeach; ?>
        </div>
        <form method="post" action="<?= e(url('/yonetim/uyeler/' . $u['id'] . '/parola')) ?>"><?= csrf_field() ?><button class="btn btn-secondary btn-sm" type="submit"><?= icon('key', 'icon-s') ?> Parola sıfırlama bağlantısı oluştur</button></form>
        <p class="small muted">Parolalar yöneticiler tarafından görülemez; yalnız kullanıcıya tek kullanımlık bağlantı iletilir.</p>
    </div></div>
    <?php if (!empty($bookings)): ?><div class="card"><div class="card-head"><h2>Rezervasyonları</h2></div><div class="card-body stack-s"><?php foreach ($bookings as $b): ?><a href="<?= e(url('/yonetim/rezervasyonlar/' . $b['id'])) ?>"><?= e($b['code']) ?> · <?= e($b['hotel_name']) ?> · <?= e(tr_date_short($b['check_in'])) ?> · <?= e(status_label($b['status'])) ?></a><?php endforeach; ?></div></div><?php endif; ?>
</div>
<?php endif; ?>
</div>
