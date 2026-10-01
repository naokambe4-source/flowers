<div class="card"><div class="card-body stack-s">
    <div class="row" style="gap:12px;flex-wrap:nowrap">
        <?php if ($hotel['cover_image_id']): ?><img src="<?= e(hotel_image_url((int) $hotel['cover_image_id'], 'thumb')) ?>" alt="" style="width:88px;height:66px;object-fit:cover;border-radius:10px"><?php endif; ?>
        <div><strong><?= e($hotel['name']) ?></strong><div class="muted small"><?= e($hotel['region_name']) ?></div></div>
    </div>
    <ul class="summary-list">
        <li><span>Giriş</span><strong><?= e(tr_date($booking['check_in'], true)) ?></strong></li>
        <li><span>Çıkış</span><strong><?= e(tr_date($booking['check_out'], true)) ?></strong></li>
        <li><span>Gece</span><strong><?= (int) $booking['nights'] ?></strong></li>
        <li><span>Konuklar</span><strong><?= e(guest_summary((int) $booking['adults'], (int) $booking['children'], (int) $booking['rooms_count'])) ?></strong></li>
        <li><span>Oda</span><strong><?= e($rooms[0]['room_name'] ?? '—') ?><?= !empty($rooms[0]['concept_name']) ? ' · ' . e($rooms[0]['concept_name']) : '' ?></strong></li>
    </ul>
    <div class="total-line"><span>Toplam <small class="muted">(vergiler dahil)</small></span><strong><?= e(money((int) $booking['total_minor'], $booking['currency'])) ?></strong></div>
    <?php if (!empty($terms['cancellation_summary'])): ?><span class="policy<?= !empty($terms['refundable']) ? '' : ' nonref' ?>"><?= icon('info', 'icon-s') ?> <?= e($terms['cancellation_summary']) ?></span><?php endif; ?>
</div></div>
