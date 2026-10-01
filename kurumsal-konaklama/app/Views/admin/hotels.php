<div class="admin-title"><div><h1>Oteller</h1><p><?= (int) $page['total'] ?> otel</p></div><?php if (can('hotels.manage')): ?><a class="btn" href="<?= e(url('/yonetim/oteller/yeni')) ?>"><?= icon('plus') ?> YENİ OTEL EKLE</a><?php endif; ?></div>
<form class="toolbar card card-body" method="get">
    <?= f_input('ara', 'Otel adı', $f['ara'] ?? '', 'search') ?>
    <?= f_select('bolge', 'Bölge', $regions, $f['bolge'] ?? '', 'Tümü') ?>
    <?= f_select('durum', 'Durum', ['draft' => 'Taslak', 'published' => 'Yayında', 'unpublished' => 'Yayından kaldırıldı'], $f['durum'] ?? '', 'Tümü') ?>
    <div class="field" style="flex:0 0 auto"><span class="label">&nbsp;</span><button class="btn" type="submit"><?= icon('filter') ?> Filtrele</button></div>
</form>
<?php if (!$rows): ?><div class="empty"><?= icon('building', 'icon-l') ?><h3>Henüz otel yok</h3><p>“Yeni otel ekle” ile adım adım otel tanımlayabilirsiniz.</p></div><?php else: ?>
<div class="table-wrap"><table class="table responsive"><thead><tr><th>Otel</th><th>Bölge</th><th>Mod</th><th>Oda / foto</th><th>Durum</th><th class="actions">İşlemler</th></tr></thead><tbody>
<?php foreach ($rows as $h): ?><tr>
    <td data-label="Otel"><div class="row" style="gap:10px;flex-wrap:nowrap"><?php if ($h['cover_image_id']): ?><img src="<?= e(url('/medya/otel/' . $h['cover_image_id'] . '/thumb')) ?>" alt="" style="width:64px;height:48px;object-fit:cover;border-radius:8px" loading="lazy"><?php endif; ?><div><strong><?= e($h['name']) ?></strong><div class="small muted"><?= str_repeat('★', (int) $h['stars']) ?> <?= $h['is_contracted'] ? '· Anlaşmalı' : '' ?><?= $h['is_featured'] ? ' · Öne çıkan' : '' ?></div></div></div></td>
    <td data-label="Bölge"><?= e($h['region_name'] ?: '—') ?></td>
    <td data-label="Mod"><?= e(['instant' => 'Anında', 'request' => 'Otel teyitli', 'offer' => 'Teklif'][$h['booking_mode']]) ?></td>
    <td data-label="Oda / foto"><?= (int) $h['room_count'] ?> / <?= (int) $h['image_count'] ?></td>
    <td data-label="Durum"><?= App\Core\View::partial('partials/status', ['status' => $h['status']]) ?><?php if ($h['status'] === 'draft'): ?><div class="small muted">Adım <?= (int) $h['wizard_step'] ?>/7</div><?php endif; ?></td>
    <td class="actions"><div class="row" style="justify-content:flex-end;gap:6px">
        <?php if (can('hotels.manage')): ?><a class="btn btn-secondary btn-sm" href="<?= e(url('/yonetim/oteller/' . $h['id'] . '/adim/' . ($h['status'] === 'draft' ? min(7, (int) $h['wizard_step']) : 1))) ?>"><?= icon('edit', 'icon-s') ?> Düzenle</a><?php endif; ?>
        <a class="btn btn-ghost btn-sm" href="<?= e(url('/yonetim/oteller/' . $h['id'] . '/onizleme')) ?>"><?= icon('eye', 'icon-s') ?> Önizle</a>
        <?php if ($h['status'] === 'published' && can('hotels.publish')): ?><form method="post" action="<?= e(url('/yonetim/oteller/' . $h['id'] . '/yayin')) ?>" data-confirm="Otel yayından kaldırılsın mı? Üyeler artık göremeyecek."><?= csrf_field() ?><input type="hidden" name="islem" value="kaldir"><button class="btn btn-danger btn-sm" type="submit">Yayından kaldır</button></form><?php endif; ?>
    </div></td>
</tr><?php endforeach; ?></tbody></table></div>
<?= pagination_links($page, $query) ?>
<?php endif; ?>
