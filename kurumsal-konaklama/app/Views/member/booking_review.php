<div class="container" style="padding-top:16px">
    <?= App\Core\View::partial('partials/booking_steps', ['step' => 3]) ?>
    <div class="detail-layout" style="margin-top:0">
        <div class="stack">
            <?php if ($oldTotal !== null && $oldTotal !== (int) $booking['total_minor']): ?>
                <div class="alert alert-warning" role="alert"><?= icon('alert') ?><div><strong>Fiyat güncellendi.</strong> Önceki toplam <?= e(money($oldTotal, $booking['currency'])) ?> idi; güncel toplam <strong><?= e(money((int) $booking['total_minor'], $booking['currency'])) ?></strong>. Devam etmek için yeni toplamı onaylamanız gerekir.</div></div>
            <?php endif; ?>
            <?php if ($unavailable): ?>
                <div class="alert alert-error" role="alert"><?= icon('alert') ?><div><strong>Bu oda artık rezerve edilemiyor.</strong> <?= e($unavailable) ?></div></div>
                <div class="row"><a class="btn" href="<?= e(url('/oteller/' . $hotel['slug'])) ?>">BAŞKA ODA SEÇ</a><a class="btn btn-secondary" href="<?= e(url('/teklif-iste', ['otel' => $hotel['id'], 'giris' => $booking['check_in'], 'cikis' => $booking['check_out']])) ?>">TEKLİF İSTE</a></div>
            <?php else: ?>
            <div class="card"><div class="card-body">
                <h1 style="font-size:1.5rem">Fiyat ve koşul kontrolü</h1>
                <p class="muted">Fiyat ve müsaitlik az önce yeniden doğrulandı. Lütfen bilgileri kontrol edin.</p>
                <h2 style="font-size:1.1rem">Fiyat dökümü</h2>
                <?= App\Core\View::partial('partials/breakdown', ['breakdown' => $breakdown, 'b' => $booking]) ?>
                <h2 style="font-size:1.1rem;margin-top:20px">Misafirler</h2>
                <ul><?php foreach ($guests as $g): ?><li><?= e($g['first_name'] . ' ' . $g['last_name']) ?><?= $g['is_child'] ? ' (' . (int) $g['age'] . ' yaş)' : '' ?><?= $g['is_lead'] ? ' — sorumlu misafir' : '' ?></li><?php endforeach; ?></ul>
                <p class="small">İletişim: <?= e($booking['contact_phone']) ?> · <?= e($booking['contact_email']) ?> <a href="<?= e(url('/rezervasyon/' . $booking['code'] . '/misafirler')) ?>">Düzenle</a></p>
                <h2 style="font-size:1.1rem;margin-top:20px">İptal koşulları</h2>
                <div class="prose"><?= nl2p($terms['cancellation_policy'] ?? '') ?: '<p>' . e($terms['cancellation_summary'] ?? 'Otel politikası geçerlidir.') . '</p>' ?></div>
                <h2 style="font-size:1.1rem">Ödeme koşulları</h2>
                <div class="prose"><?= nl2p($terms['payment_terms'] ?? '') ?: '<p class="muted">Ödeme koşulu otel teyidinde bildirilecektir.</p>' ?></div>
                <?php if (!empty($terms['platform_terms'])): ?><h2 style="font-size:1.1rem">Rezervasyon koşulları</h2><div class="prose"><?= nl2p($terms['platform_terms']) ?></div><?php endif; ?>
                <div class="alert alert-info" style="margin-top:12px"><?= icon('info') ?><div><?= $booking['mode'] === 'instant' ? 'Bu oda doğrulanmış kontenjandan anında onaylanır.' : 'Bu rezervasyon otel teyidine bağlıdır. Talebiniz otele iletilir; teyit sonrası onay ve voucher gönderilir.' ?></div></div>
                <form method="post" action="<?= e(url('/rezervasyon/' . $booking['code'] . '/onayla')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="quote_hash" value="<?= e($booking['quote_hash']) ?>">
                    <label class="check"><input type="checkbox" name="kosullar" value="1" required<?= aria_error('kosullar') ?>><span>Toplam <strong><?= e(money((int) $booking['total_minor'], $booking['currency'])) ?></strong> tutarı, iptal ve ödeme koşullarını okudum ve kabul ediyorum.</span></label>
                    <?= field_error('kosullar') ?>
                    <div class="row-between" style="margin-top:12px">
                        <a class="btn btn-ghost" href="<?= e(url('/rezervasyon/' . $booking['code'] . '/misafirler')) ?>"><?= icon('chevron-left', 'icon-s') ?> GERİ DÖN</a>
                        <button type="submit" class="btn btn-lg"><?= $booking['mode'] === 'instant' ? 'REZERVASYONU ONAYLA' : 'TALEBİ GÖNDER' ?></button>
                    </div>
                </form>
            </div></div>
            <?php endif; ?>
        </div>
        <aside><?= App\Core\View::partial('partials/booking_summary', get_defined_vars()) ?></aside>
    </div>
</div>
