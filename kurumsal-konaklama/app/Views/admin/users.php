<div class="admin-title"><div><h1>Üyeler</h1><p><?= (int) $page['total'] ?> kullanıcı</p></div><div class="row"><?php if (can('users.import')): ?><a class="btn btn-secondary" href="<?= e(url('/yonetim/uyeler/aktar')) ?>"><?= icon('upload') ?> CSV / XLSX aktar</a><?php endif; ?><?php if (can('users.manage')): ?><a class="btn" href="<?= e(url('/yonetim/uyeler/yeni')) ?>"><?= icon('plus') ?> Yeni üye</a><?php endif; ?></div></div>
<form class="toolbar card card-body" method="get">
    <?= f_input('ara', 'Ara', $f['ara'] ?? '', 'search', ['placeholder' => 'Ad, e-posta, telefon']) ?>
    <?= f_select('durum', 'Durum', ['active' => 'Aktif', 'pending' => 'Onay bekliyor', 'passive' => 'Pasif', 'suspended' => 'Askıda', 'rejected' => 'Reddedildi'], $f['durum'] ?? '', 'Tümü') ?>
    <?= f_select('rol', 'Rol', $roles, $f['rol'] ?? '', 'Tümü') ?>
    <?= f_select('kurum', 'Kurum', $institutions, $f['kurum'] ?? '', 'Tümü') ?>
    <div class="field" style="flex:0 0 auto"><span class="label">&nbsp;</span><button class="btn">Filtrele</button></div>
</form>
<div class="table-wrap"><table class="table responsive"><thead><tr><th>Ad soyad</th><th>E-posta / telefon</th><th>Kurum</th><th>Rol</th><th>Durum</th><th>Son giriş</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $u): ?><tr><td data-label="Ad"><strong><?= e($u['first_name'] . ' ' . $u['last_name']) ?></strong><div class="small muted"><?= e($u['department'] ?: '') ?></div></td><td data-label="İletişim"><?= e($u['email']) ?><div class="small muted"><?= e($u['phone']) ?></div></td><td data-label="Kurum"><?= e($u['institution_name'] ?: '—') ?></td><td data-label="Rol"><?= e($u['role_name']) ?></td><td data-label="Durum"><?= App\Core\View::partial('partials/status', ['status' => $u['status'] === 'pending' ? 'pending_user' : $u['status']]) ?></td><td data-label="Son giriş"><?= e(tr_datetime($u['last_login_at'])) ?></td><td class="actions"><a class="btn btn-secondary btn-sm" href="<?= e(url('/yonetim/uyeler/' . $u['id'])) ?>">Aç</a></td></tr><?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="7" class="muted">Kayıt bulunamadı.</td></tr><?php endif; ?></tbody></table></div>
<?= pagination_links($page, $query) ?>
