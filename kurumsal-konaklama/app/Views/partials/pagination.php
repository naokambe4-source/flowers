<?php
/** @var array $page @var array $query */
if (($page['pages'] ?? 1) <= 1) { return; }
$cur = (int) $page['current']; $pages = (int) $page['pages'];
$req = App\Core\App::request();
$base = $req ? $req->path : '/';
$link = static fn (int $p) => url($base, array_merge($query, ['sayfa' => $p]));
$window = array_unique(array_filter([1, $cur - 2, $cur - 1, $cur, $cur + 1, $cur + 2, $pages], static fn ($p) => $p >= 1 && $p <= $pages));
sort($window);
?>
<nav aria-label="Sayfalar"><ul class="pagination">
<?php if ($cur > 1): ?><li><a href="<?= e($link($cur - 1)) ?>" aria-label="Önceki sayfa">‹</a></li><?php endif; ?>
<?php $prev = 0; foreach ($window as $p): if ($p - $prev > 1): ?><li><span aria-hidden="true">…</span></li><?php endif; ?>
<li><?php if ($p === $cur): ?><span aria-current="page"><?= $p ?></span><?php else: ?><a href="<?= e($link($p)) ?>" aria-label="Sayfa <?= $p ?>"><?= $p ?></a><?php endif; ?></li>
<?php $prev = $p; endforeach; ?>
<?php if ($cur < $pages): ?><li><a href="<?= e($link($cur + 1)) ?>" aria-label="Sonraki sayfa">›</a></li><?php endif; ?>
</ul></nav>
