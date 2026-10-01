<div class="container" style="max-width:920px">
    <div class="page-head"><h1>Teklif iste</h1><p>Anlık fiyatı olmayan oteller veya özel konaklama ihtiyaçlarınız için ekibimiz otelden fiyat alıp size teklif sunar.</p></div>
    <?php if ($target): ?>
        <div class="alert alert-info"><?= icon('tag') ?><div><strong>Onaya bağlı hedef teklif: <?= e(money($target->total, $target->currency)) ?></strong><br><span class="small"><?= e($target->message) ?></span></div></div>
    <?php endif; ?>
    <div class="card" style="margin-top:12px"><div class="card-body">
    <form method="post" action="<?= e(url('/teklif-iste')) ?>" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="idem" value="<?= e($idem) ?>">
        <?php if ($target): ?><input type="hidden" name="hedef" value="<?= (int) $target->referencePriceId ?>"><?php endif; ?>
        <div class="form-grid">
            <div class="field"><label for="otel">Otel</label>
                <select id="otel" name="otel"<?= aria_error('otel') ?>><option value="">Otel fark etmez (bölgeye göre teklif)</option><?php foreach ($hotels as $h): ?><option value="<?= (int) $h['id'] ?>"<?= ($hotel && (int) $hotel['id'] === (int) $h['id']) || (string) old('otel') === (string) $h['id'] ? ' selected' : '' ?>><?= e($h['name']) ?></option><?php endforeach; ?></select></div>
            <div class="field"><label for="bolge">Bölge</label>
                <select id="bolge" name="bolge"<?= aria_error('bolge') ?>><option value="">Seçiniz</option><?php foreach ($regions as $r): ?><option value="<?= (int) $r['id'] ?>"<?= (int) $regionId === (int) $r['id'] ? ' selected' : '' ?>><?= $r['parent_id'] ? '— ' : '' ?><?= e($r['name']) ?></option><?php endforeach; ?></select><?= field_error('bolge') ?></div>
            <?php if ($rooms): ?><div class="field"><label for="oda_tipi">Oda tipi</label><select id="oda_tipi" name="oda_tipi"><option value="">Fark etmez</option><?php foreach ($rooms as $r): ?><option value="<?= (int) $r['id'] ?>"<?= (int) $roomId === (int) $r['id'] ? ' selected' : '' ?>><?= e($r['name']) ?></option><?php endforeach; ?></select></div><?php endif; ?>
            <div class="field"><label for="konsept">Konsept tercihi</label><select id="konsept" name="konsept"><option value="">Fark etmez</option><?php foreach ($concepts as $c): ?><option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
        </div>
        <h2 style="font-size:1.1rem">Tarih ve konuklar</h2>
        <?php
        $today = date('Y-m-d');
        $state = $criteria ? array_map(static fn ($r) => ['y' => $r->adults, 'c' => $r->childAges], $criteria->rooms) : [['y' => 2, 'c' => []]];
        ?>
        <div class="form-grid" style="position:relative">
            <div class="field" data-daterange data-min="<?= e($today) ?>" data-max-nights="<?= (int) setting('booking.max_nights', '30') ?>">
                <span class="label" id="rq-dr">Giriş – Çıkış</span>
                <button type="button" class="trigger" data-dr-trigger aria-haspopup="dialog" aria-expanded="false" aria-labelledby="rq-dr" hidden><?= icon('calendar') ?><span class="trigger-text"></span></button>
                <div class="date-fallback">
                    <div><label for="rq-giris" class="small">Giriş</label><input type="date" id="rq-giris" name="giris" data-dr-in min="<?= e($today) ?>" value="<?= e($criteria?->checkIn ?? old('giris')) ?>"></div>
                    <div><label for="rq-cikis" class="small">Çıkış</label><input type="date" id="rq-cikis" name="cikis" data-dr-out value="<?= e($criteria?->checkOut ?? old('cikis')) ?>"></div>
                </div>
                <?= field_error('giris') ?><?= field_error('cikis') ?>
            </div>
            <div class="field" data-guests data-max-rooms="<?= (int) setting('booking.max_rooms', '5') ?>">
                <span class="label" id="rq-gp">Konuklar</span>
                <button type="button" class="trigger" data-gp-trigger aria-haspopup="dialog" aria-expanded="false" aria-labelledby="rq-gp" hidden><?= icon('users') ?><span class="trigger-text"></span></button>
                <div class="guest-fallback">
                    <div><label for="rq-y" class="small">Yetişkin</label><select id="rq-y" name="yetiskin"><?php for ($i = 1; $i <= 12; $i++): ?><option<?= ($criteria ? $criteria->adults() : 2) === $i ? ' selected' : '' ?>><?= $i ?></option><?php endfor; ?></select></div>
                    <div><label for="rq-c" class="small">Çocuk</label><select id="rq-c" name="cocuk"><?php for ($i = 0; $i <= 6; $i++): ?><option<?= ($criteria ? $criteria->children() : 0) === $i ? ' selected' : '' ?>><?= $i ?></option><?php endfor; ?></select></div>
                    <div><label for="rq-o" class="small">Oda</label><select id="rq-o" name="oda_sayisi"><?php for ($i = 1; $i <= 5; $i++): ?><option<?= ($criteria ? $criteria->roomCount() : 1) === $i ? ' selected' : '' ?>><?= $i ?></option><?php endfor; ?></select></div>
                    <div class="span-all"><label for="rq-a" class="small">Çocuk yaşları</label><input id="rq-a" name="yaslar" value="<?= e($criteria ? implode(', ', $criteria->allChildAges()) : '') ?>"></div>
                </div>
                <div data-gp-hidden></div>
                <script type="application/json" data-gp-state><?= json_encode($state) ?></script>
                <?= field_error('oda') ?>
            </div>
        </div>
        <div class="field"><label for="telefon">Size ulaşabileceğimiz telefon</label><input type="tel" id="telefon" name="telefon" value="<?= e(old('telefon', auth_user()['phone'] ?? '')) ?>" required<?= aria_error('telefon') ?>><?= field_error('telefon') ?></div>
        <div class="field"><label for="notlar">Notlarınız <span class="muted">(isteğe bağlı)</span></label><textarea id="notlar" name="notlar" rows="4" placeholder="Örn. bütçe aralığı, oda tercihi, ulaşım ihtiyacı"><?= e(old('notlar')) ?></textarea></div>
        <div class="row-between"><a class="btn btn-ghost" href="<?= e(url('/oteller')) ?>"><?= icon('chevron-left', 'icon-s') ?> GERİ DÖN</a><button class="btn btn-lg" type="submit">TALEBİ GÖNDER</button></div>
    </form>
    </div></div>
</div>
