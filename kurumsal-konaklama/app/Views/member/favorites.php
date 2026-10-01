<div class="container">
    <div class="page-head"><h1>Favorilerim</h1><p>Beğendiğiniz otelleri kaydedip daha sonra karşılaştırabilirsiniz.</p></div>
    <?php if (!$hotels): ?>
        <div class="empty" style="margin-top:16px"><?= icon('heart', 'icon-l') ?><h3>Henüz favori oteliniz yok</h3><p>Otel kartlarındaki kalp simgesine dokunarak favorilerinize ekleyebilirsiniz.</p><a class="btn" href="<?= e(url('/oteller')) ?>">OTEL ARA</a></div>
    <?php else: ?>
        <div class="stack" style="margin-top:16px"><?php foreach ($hotels as $h): ?><?= App\Core\View::partial('partials/hotel_card', ['h' => $h, 'horizontal' => true]) ?><?php endforeach; ?></div>
    <?php endif; ?>
</div>
