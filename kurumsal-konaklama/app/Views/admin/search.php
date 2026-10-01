<div class="admin-title"><div><h1>Arama sonuçları</h1><p>“<?= e($q) ?>”</p></div></div>
<?php if (mb_strlen($q) < 2): ?><div class="alert alert-info"><?= icon('info') ?><div>En az 2 karakter girin.</div></div><?php endif; ?>
<?php $groups = ['bookings' => 'Rezervasyonlar', 'requests' => 'Talepler', 'users' => 'Üyeler', 'institutions' => 'Kurumlar', 'hotels' => 'Oteller']; $any = false; ?>
<div class="grid-2">
<?php foreach ($groups as $k => $label): if (!$res[$k]) continue; $any = true; ?>
    <div class="card"><div class="card-head"><h2><?= e($label) ?> (<?= count($res[$k]) ?>)</h2></div><div class="card-body stack-s">
    <?php foreach ($res[$k] as $r): ?>
        <?php if ($k === 'bookings'): ?><a href="<?= e(url('/yonetim/rezervasyonlar/' . $r['id'])) ?>"><strong><?= e($r['code']) ?></strong> — <?= e($r['hotel_name']) ?> · <?= e($r['contact_name']) ?> · <?= e(status_label($r['status'])) ?></a>
        <?php elseif ($k === 'requests'): ?><a href="<?= e(url('/yonetim/talepler/' . $r['id'])) ?>"><strong><?= e($r['code']) ?></strong> · <?= e(status_label($r['status'])) ?></a>
        <?php elseif ($k === 'users'): ?><a href="<?= e(url('/yonetim/uyeler/' . $r['id'])) ?>"><?= e($r['first_name'] . ' ' . $r['last_name']) ?> — <?= e($r['email']) ?> <?= e($r['phone']) ?></a>
        <?php elseif ($k === 'institutions'): ?><a href="<?= e(url('/yonetim/kurumlar/' . $r['id'])) ?>"><?= e($r['name']) ?></a>
        <?php else: ?><a href="<?= e(url('/yonetim/oteller/' . $r['id'] . '/adim/1')) ?>"><?= e($r['name']) ?> · <?= e(status_label($r['status'])) ?></a><?php endif; ?>
    <?php endforeach; ?></div></div>
<?php endforeach; ?>
</div>
<?php if (!$any && mb_strlen($q) >= 2): ?><div class="empty"><?= icon('search', 'icon-l') ?><h3>Sonuç bulunamadı</h3></div><?php endif; ?>
