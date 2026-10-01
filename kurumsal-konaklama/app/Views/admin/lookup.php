<?php
$listCols = array_slice(array_keys($cfg['fields']), 0, 5);
?>
<div class="admin-title"><div><h1><?= e($cfg['title']) ?></h1><p><?= count($rows) ?> kayıt</p></div></div>
<div class="grid-2" style="grid-template-columns:minmax(0,1.4fr) minmax(0,1fr)">
    <div class="table-wrap"><table class="table responsive"><thead><tr><?php foreach ($listCols as $k): ?><th><?= e($cfg['fields'][$k][0]) ?></th><?php endforeach; ?><th></th></tr></thead><tbody>
        <?php foreach ($rows as $r): ?><tr>
            <?php foreach ($listCols as $k): $f = $cfg['fields'][$k]; ?><td data-label="<?= e($f[0]) ?>"><?= e(App\Controllers\Admin\LookupController::displayValue($f[1], $r[$k] ?? null, $f)) ?></td><?php endforeach; ?>
            <td class="actions"><div class="row" style="justify-content:flex-end;gap:6px"><a class="btn btn-secondary btn-sm" href="<?= e(url('/yonetim/' . $cfg['seg'], ['duzenle' => $r['id']])) ?>">Düzenle</a>
                <form method="post" action="<?= e(url('/yonetim/' . $cfg['seg'] . '/' . $r['id'] . '/sil')) ?>" data-confirm="Kayıt silinsin mi?"><?= csrf_field() ?><button class="btn btn-danger btn-sm" type="submit" aria-label="Sil: <?= e($r['name']) ?>"><?= icon('trash', 'icon-s') ?></button></form></div></td>
        </tr><?php endforeach; ?>
        <?php if (!$rows): ?><tr><td colspan="<?= count($listCols) + 1 ?>" class="muted">Kayıt yok.</td></tr><?php endif; ?>
    </tbody></table></div>
    <div class="card"><div class="card-head"><h2><?= $edit ? 'Düzenle: ' . e($edit['name']) : 'Yeni ' . e($cfg['singular']) ?></h2><?php if ($edit): ?><a href="<?= e(url('/yonetim/' . $cfg['seg'])) ?>">Yeni ekle</a><?php endif; ?></div><div class="card-body">
        <form method="post" action="<?= e(url('/yonetim/' . $cfg['seg'])) ?>" enctype="multipart/form-data"><?= csrf_field() ?>
            <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int) $edit['id'] ?>"><?php endif; ?>
            <?php foreach ($cfg['fields'] as $k => $f): $v = $edit[$k] ?? ($f[1] === 'check' ? 1 : null); ?>
                <?php if ($f[1] === 'select'): ?><?= f_select($k, $f[0], $f[3], $v, str_contains($f[2], 'nullable') ? '—' : '', '', str_contains($f[2], 'required')) ?>
                <?php elseif ($f[1] === 'check'): ?><?= f_check($k, $f[0], (bool) $v) ?>
                <?php elseif ($f[1] === 'percent'): ?><?= f_input($k, $f[0], bp_input($v !== null ? (int) $v : null), 'text', ['inputmode' => 'decimal']) ?>
                <?php else: ?><?= f_input($k, $f[0], $v, $f[1] === 'number' ? 'number' : ($f[1] === 'date' ? 'date' : 'text'), str_contains($f[2], 'required') ? ['required' => true] : []) ?><?php endif; ?>
            <?php endforeach; ?>
            <?php if (!empty($cfg['image'])): ?>
                <div class="field"><label for="image">Görsel (bölgeye ait gerçek fotoğraf)</label><input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp"><p class="hint">Yüklenmezse seçilen temsili illüstrasyon “Temsili” etiketiyle gösterilir.</p></div>
                <?php if ($edit && $edit['image_path']): ?><img src="<?= e(url('/medya/bolge/' . $edit['id'])) ?>" alt="" style="max-width:220px;border-radius:10px"><?= f_check('remove_image', 'Görseli kaldır', false) ?><?php endif; ?>
            <?php endif; ?>
            <button class="btn" type="submit">KAYDET</button>
        </form>
    </div></div>
</div>
