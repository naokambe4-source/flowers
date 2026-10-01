<div class="admin-title"><div><h1>Kurumlar</h1><p><?= (int) $page['total'] ?> kurum</p></div><a class="btn btn-secondary" href="<?= e(url('/yonetim/kurum-tipleri')) ?>">Kurum tipleri</a></div>
<form class="toolbar card card-body" method="get"><?= f_input('ara', 'Ara', $q, 'search') ?><div class="field" style="flex:0 0 auto"><span class="label">&nbsp;</span><button class="btn">Ara</button></div></form>
<div class="grid-2" style="grid-template-columns:minmax(0,1.5fr) minmax(0,1fr)">
<div><div class="table-wrap"><table class="table responsive"><thead><tr><th>Kurum</th><th>Tip</th><th>İndirim</th><th>Üye</th><th>Durum</th><th></th></tr></thead><tbody>
<?php foreach ($rows as $i): ?><tr><td data-label="Kurum"><strong><?= e($i['name']) ?></strong><div class="small muted"><?= e($i['contact_name']) ?></div></td><td data-label="Tip"><?= e($i['type_name'] ?: '—') ?></td>
<td data-label="İndirim"><?= $i['discount_bp'] !== null ? e(percent((int) $i['discount_bp'])) . ' (kurum)' : ($i['group_name'] ? e($i['group_name']) : 'Genel') ?></td><td data-label="Üye"><?= (int) $i['members'] ?></td>
<td data-label="Durum"><?= $i['is_active'] ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-danger">Pasif</span>' ?></td><td class="actions"><a class="btn btn-secondary btn-sm" href="<?= e(url('/yonetim/kurumlar/' . $i['id'])) ?>">Aç</a></td></tr><?php endforeach; ?>
<?php if (!$rows): ?><tr><td colspan="6" class="muted">Kurum yok.</td></tr><?php endif; ?></tbody></table></div><?= pagination_links($page, $query) ?></div>
<?php if (can('institutions.manage')): ?><div class="card"><div class="card-head"><h2>Yeni kurum</h2></div><div class="card-body"><?= App\Core\View::partial('admin/institution_form', get_defined_vars()) ?></div></div><?php endif; ?>
</div>
