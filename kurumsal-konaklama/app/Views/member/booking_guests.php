<div class="container" style="padding-top:16px">
    <?= App\Core\View::partial('partials/booking_steps', ['step' => 2]) ?>
    <div class="detail-layout" style="margin-top:0">
        <div class="card"><div class="card-body">
            <h1 style="font-size:1.5rem">Misafir bilgileri</h1>
            <p class="muted">Yalnızca rezervasyon için gerekli bilgileri isteriz. Her oda için sorumlu misafirin adı ve soyadı zorunludur.</p>
            <form method="post" action="<?= e(url('/rezervasyon/' . $booking['code'] . '/misafirler')) ?>" novalidate>
                <?= csrf_field() ?>
                <?php
                $leads = [];
                foreach ($guests as $g) { if ($g['is_lead']) { $leads[$g['booking_room_id']] = $g; } }
                foreach ($rooms as $i => $room):
                    $lead = $leads[$room['id']] ?? null;
                    $u = auth_user();
                    $ages = $room['children_ages'] === '' ? [] : explode(',', $room['children_ages']);
                ?>
                <fieldset>
                    <legend><?= $i + 1 ?>. oda — <?= e(guest_summary((int) $room['adults'], count($ages), 1)) ?></legend>
                    <?= field_error("misafir.$i") ?>
                    <div class="form-grid">
                        <div class="field"><label for="ad-<?= $i ?>">Sorumlu misafir adı</label><input id="ad-<?= $i ?>" name="misafir[<?= $i ?>][ad]" value="<?= e((old('misafir', [])[$i] ?? [])['ad'] ?? ($lead['first_name'] ?? ($i === 0 ? $u['first_name'] : ''))) ?>" autocomplete="<?= $i === 0 ? 'given-name' : 'off' ?>" required></div>
                        <div class="field"><label for="soyad-<?= $i ?>">Sorumlu misafir soyadı</label><input id="soyad-<?= $i ?>" name="misafir[<?= $i ?>][soyad]" value="<?= e((old('misafir', [])[$i] ?? [])['soyad'] ?? ($lead['last_name'] ?? ($i === 0 ? $u['last_name'] : ''))) ?>" autocomplete="<?= $i === 0 ? 'family-name' : 'off' ?>" required></div>
                    </div>
                    <?php for ($a = 1; $a < (int) $room['adults']; $a++): ?>
                        <div class="field"><label for="d-<?= $i ?>-<?= $a ?>"><?= $a + 1 ?>. yetişkin adı soyadı <span class="muted">(isteğe bağlı)</span></label><input id="d-<?= $i ?>-<?= $a ?>" name="misafir[<?= $i ?>][diger][]" value=""></div>
                    <?php endfor; ?>
                    <?php foreach ($ages as $j => $age): ?>
                        <div class="field"><label for="c-<?= $i ?>-<?= $j ?>"><?= $j + 1 ?>. çocuk (<?= (int) $age ?> yaş) adı soyadı <span class="muted">(isteğe bağlı)</span></label><input id="c-<?= $i ?>-<?= $j ?>" name="misafir[<?= $i ?>][cocuk][]" value=""></div>
                    <?php endforeach; ?>
                </fieldset>
                <?php endforeach; ?>
                <h2 style="font-size:1.1rem">İletişim</h2>
                <div class="form-grid">
                    <div class="field"><label for="tel">Cep telefonu</label><input type="tel" id="tel" name="iletisim_telefon" value="<?= e(old('iletisim_telefon', $booking['contact_phone'])) ?>" autocomplete="tel" required<?= aria_error('iletisim_telefon') ?>><?= field_error('iletisim_telefon') ?></div>
                    <div class="field"><label for="mail">E-posta</label><input type="email" id="mail" name="iletisim_eposta" value="<?= e(old('iletisim_eposta', $booking['contact_email'])) ?>" autocomplete="email" required<?= aria_error('iletisim_eposta') ?>><?= field_error('iletisim_eposta') ?></div>
                </div>
                <div class="field"><label for="not">Otele iletilecek not <span class="muted">(isteğe bağlı)</span></label><textarea id="not" name="not" rows="3" placeholder="Örn. geç giriş, yan yana oda isteği"><?= e(old('not', $booking['notes'])) ?></textarea><p class="hint">İstekler otel müsaitliğine bağlıdır.</p></div>
                <div class="field"><label for="promo">Promosyon kodu <span class="muted">(varsa)</span></label><input id="promo" name="promosyon" value="<?= e(old('promosyon')) ?>" autocomplete="off" style="max-width:260px;text-transform:uppercase"></div>
                <div class="row-between" style="margin-top:8px">
                    <a class="btn btn-ghost" href="<?= e(url('/oteller/' . $hotel['slug'])) ?>"><?= icon('chevron-left', 'icon-s') ?> GERİ DÖN</a>
                    <button type="submit" class="btn btn-lg">DEVAM ET <?= icon('arrow-right', 'icon-s') ?></button>
                </div>
            </form>
        </div></div>
        <aside><?= App\Core\View::partial('partials/booking_summary', get_defined_vars()) ?></aside>
    </div>
</div>
