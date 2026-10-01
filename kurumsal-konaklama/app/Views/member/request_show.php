<div class="container">
    <div class="page-head"><nav aria-label="Konum"><ol class="breadcrumb"><li><a href="<?= e(url('/tekliflerim')) ?>">Tekliflerim</a></li><li aria-current="page"><?= e($req['code']) ?></li></ol></nav>
        <h1>Talep <?= e($req['code']) ?></h1><p><?= App\Core\View::partial('partials/status', ['status' => $req['status']]) ?> · <?= e(tr_datetime($req['created_at'])) ?></p></div>
    <div class="detail-layout" style="margin-top:8px">
        <div class="stack">
            <?php if (!$offers): ?>
                <div class="empty"><?= icon('clock', 'icon-l') ?><h3>Teklif hazırlanıyor</h3><p>Ekibimiz otelle iletişime geçerek size teklif sunacak. Teklif geldiğinde bildirim alacaksınız.</p></div>
            <?php endif; ?>
            <?php foreach ($offers as $o): $live = $o['status'] === 'sent' && strtotime($o['valid_until']) > time(); ?>
                <article class="card"<?= $live ? ' style="border-color:var(--c-teal);box-shadow:var(--shadow)"' : '' ?>><div class="card-body stack-s">
                    <div class="row-between"><h2 style="font-size:1.15rem;margin:0">Teklif — sürüm <?= (int) $o['version'] ?></h2><?= App\Core\View::partial('partials/status', ['status' => $o['status'] === 'sent' && !$live ? 'expired' : $o['status']]) ?></div>
                    <dl class="kv">
                        <dt>Otel</dt><dd><?= e($o['hotel_name']) ?></dd>
                        <dt>Oda</dt><dd><?= e($o['room_name']) ?></dd>
                        <dt>Konsept</dt><dd><?= e($o['concept_name'] ?: '—') ?></dd>
                        <dt>Tarihler</dt><dd><?= e(tr_date($o['check_in'])) ?> – <?= e(tr_date($o['check_out'])) ?></dd>
                        <dt>Toplam</dt><dd style="font-size:1.25rem;font-weight:800"><?= e(money((int) $o['total_minor'], $o['currency'])) ?> <small class="muted">(vergiler dahil)</small></dd>
                        <dt>Geçerlilik</dt><dd><?= e(tr_datetime($o['valid_until'])) ?></dd>
                    </dl>
                    <h3 style="font-size:1rem;margin-top:8px">Ödeme koşulları</h3><div class="prose"><?= nl2p($o['payment_terms']) ?></div>
                    <h3 style="font-size:1rem">İptal koşulları</h3><div class="prose"><?= nl2p($o['cancellation_terms']) ?></div>
                    <?php if ($o['note']): ?><p class="small">Not: <?= e($o['note']) ?></p><?php endif; ?>
                    <?php if ($o['booking_code']): ?><a class="btn btn-secondary" href="<?= e(url('/rezervasyonlarim/' . $o['booking_code'])) ?>">Rezervasyonu görüntüle</a><?php endif; ?>
                    <?php if ($live && $canModify): ?>
                        <div class="alert alert-info"><?= icon('info') ?><div>Teklif kabulü otel teyidi anlamına gelmez. Kabulünüzden sonra ekibimiz otelden teyit numarası alarak rezervasyonu onaylar.</div></div>
                        <form method="post" action="<?= e(url('/tekliflerim/' . $req['code'] . '/kabul')) ?>">
                            <?= csrf_field() ?><input type="hidden" name="teklif" value="<?= (int) $o['id'] ?>"><input type="hidden" name="surum" value="<?= (int) $o['version'] ?>">
                            <label class="check"><input type="checkbox" name="kosullar" value="1" required><span>Toplam tutarı, ödeme ve iptal koşullarını kabul ediyorum.</span></label><?= field_error('kosullar') ?>
                            <button class="btn btn-lg btn-block" type="submit">TEKLİFİ KABUL ET</button>
                        </form>
                        <form method="post" action="<?= e(url('/tekliflerim/' . $req['code'] . '/reddet')) ?>" data-confirm="Teklifi reddetmek istediğinize emin misiniz?"><?= csrf_field() ?><input type="hidden" name="teklif" value="<?= (int) $o['id'] ?>"><button class="btn btn-danger btn-block" type="submit">TEKLİFİ REDDET</button></form>
                    <?php endif; ?>
                </div></article>
            <?php endforeach; ?>
        </div>
        <aside class="stack">
            <div class="card"><div class="card-body">
                <h2 style="font-size:1.1rem">Talep bilgileri</h2>
                <dl class="kv">
                    <dt>Otel</dt><dd><?= e($req['hotel_name'] ?: 'Fark etmez') ?></dd>
                    <dt>Bölge</dt><dd><?= e($req['region_name'] ?: '—') ?></dd>
                    <dt>Tarihler</dt><dd><?= e(tr_date_short($req['check_in'])) ?> – <?= e(tr_date_short($req['check_out'])) ?></dd>
                    <dt>Konuklar</dt><dd><?= (int) $req['adults'] ?> yetişkin<?= (int) $req['children'] ? ', ' . (int) $req['children'] . ' çocuk' : '' ?></dd>
                    <?php if ($req['target_minor']): ?><dt>Hedef teklif</dt><dd><?= e(money((int) $req['target_minor'])) ?></dd><?php endif; ?>
                </dl>
                <?php if ($req['notes']): ?><p class="small" style="margin-top:8px"><?= e($req['notes']) ?></p><?php endif; ?>
                <?php if ($canModify && in_array($req['status'], ['new', 'offered'], true)): ?>
                    <form method="post" action="<?= e(url('/tekliflerim/' . $req['code'] . '/iptal')) ?>" data-confirm="Talebi iptal etmek istediğinize emin misiniz?" style="margin-top:12px"><?= csrf_field() ?><button class="btn btn-danger btn-block" type="submit">TALEBİ İPTAL ET</button></form>
                <?php endif; ?>
            </div></div>
        </aside>
    </div>
</div>
