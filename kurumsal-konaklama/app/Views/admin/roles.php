<div class="admin-title"><div><h1>Yetki matrisi</h1><p>İzinler sunucu tarafında her istekte kontrol edilir. Super Admin tüm yetkilere sahiptir.</p></div></div>
<form method="post" action="<?= e(url('/yonetim/yetkiler')) ?>"><?= csrf_field() ?>
<div class="table-wrap"><table class="table matrix"><thead><tr><th>İzin</th><?php foreach ($roles as $r): ?><th><?= e($r['name']) ?></th><?php endforeach; ?></tr></thead><tbody>
<?php $group = null; foreach ($permissions as $p): if ($group !== $p['group_name']): $group = $p['group_name']; ?><tr><th colspan="<?= count($roles) + 1 ?>" style="text-align:left"><?= e($group) ?></th></tr><?php endif; ?>
<tr><td><?= e($p['name']) ?><div class="small muted code" style="display:inline-block;margin-top:2px"><?= e($p['slug']) ?></div></td>
<?php foreach ($roles as $r): $isSuper = $r['slug'] === 'super_admin'; ?><td><label class="sr-only" for="p<?= (int) $r['id'] ?>_<?= (int) $p['id'] ?>"><?= e($r['name'] . ': ' . $p['name']) ?></label><input type="checkbox" id="p<?= (int) $r['id'] ?>_<?= (int) $p['id'] ?>" name="perm[<?= (int) $r['id'] ?>][<?= (int) $p['id'] ?>]" value="1" style="width:20px;height:20px"<?= $isSuper || isset($map[(int) $r['id']][(int) $p['id']]) ? ' checked' : '' ?><?= $isSuper || !can('roles.manage') ? ' disabled' : '' ?>></td><?php endforeach; ?></tr>
<?php endforeach; ?></tbody></table></div>
<?php if (can('roles.manage')): ?><div class="wizard-actions"><span></span><button class="btn" type="submit">YETKİLERİ KAYDET</button></div><?php endif; ?>
</form>
