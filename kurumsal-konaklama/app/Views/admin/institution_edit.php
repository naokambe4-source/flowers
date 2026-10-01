<div class="admin-title"><div><p><a href="<?= e(url('/yonetim/kurumlar')) ?>">← Kurumlar</a></p><h1><?= e($edit['name']) ?></h1><p><?= (int) $stats['c'] ?> rezervasyon · onaylı toplam <?= e(money((int) $stats['t'])) ?></p></div>
<div class="row"><a class="btn btn-secondary" href="<?= e(url('/yonetim/rezervasyonlar', ['kurum' => $edit['id']])) ?>">Rezervasyonlar</a><a class="btn btn-secondary" href="<?= e(url('/yonetim/raporlar', ['kurum' => $edit['id']])) ?>">Rapor</a></div></div>
<div class="grid-2">
    <div class="card"><div class="card-head"><h2>Kurum bilgileri</h2></div><div class="card-body"><?php if ($edit['logo_path']): ?><img src="<?= e(url('/medya/kurum/' . $edit['id'])) ?>" alt="Logo" style="max-height:60px;margin-bottom:12px"><?php endif; ?>
        <?php if (can('institutions.manage')): ?><?= App\Core\View::partial('admin/institution_form', get_defined_vars()) ?><?php else: ?><dl class="kv"><dt>Yetkili</dt><dd><?= e($edit['contact_name']) ?></dd><dt>Telefon</dt><dd><?= e($edit['phone']) ?></dd><dt>E-posta</dt><dd><?= e($edit['email']) ?></dd></dl><?php endif; ?></div></div>
    <div class="card"><div class="card-head"><h2>Üyeler (<?= count($members) ?>)</h2><?php if (can('users.manage')): ?><a href="<?= e(url('/yonetim/uyeler/yeni', ['kurum' => $edit['id']])) ?>">+ Üye ekle</a><?php endif; ?></div><div class="card-body stack-s">
        <?php foreach ($members as $m): ?><a class="panel row-between" style="text-decoration:none;color:inherit" href="<?= e(url('/yonetim/uyeler/' . $m['id'])) ?>"><span><strong><?= e($m['first_name'] . ' ' . $m['last_name']) ?></strong><br><span class="small muted"><?= e($m['email']) ?> · <?= e($m['role']) ?></span></span><?= App\Core\View::partial('partials/status', ['status' => $m['status']]) ?></a><?php endforeach; ?>
        <?php if (!$members): ?><p class="muted">Bu kuruma bağlı üye yok.</p><?php endif; ?>
    </div></div>
</div>
