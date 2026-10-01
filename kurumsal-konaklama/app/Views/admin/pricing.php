<div class="admin-title"><div><h1>Fiyatlar</h1><p>Anlaşmalı / kampanya fiyatları ve doğrulanmış referans fiyatlar ayrı tutulur. Tutarlar kuruş hassasiyetinde saklanır.</p></div><a class="btn btn-secondary" href="<?= e(url('/yonetim/fiyatlar/hesapla', $hotel ? ['otel' => $hotel['id']] : [])) ?>"><?= icon('percent') ?> Fiyat hesaplama / simülasyon</a></div>
<form class="toolbar card card-body" method="get"><?= f_select('otel', 'Otel', $hotels, $hotel['id'] ?? '', 'Otel seçin') ?><div class="field" style="flex:0 0 auto"><span class="label">&nbsp;</span><button class="btn">Göster</button></div></form>
<?php if (!$hotel): ?><div class="empty"><?= icon('tag', 'icon-l') ?><h3>Fiyatlarını yönetmek için bir otel seçin</h3></div><?php else: ?>
<?php if (!$hotel['is_contracted']): ?><div class="alert alert-warning"><?= icon('alert') ?><div>Bu otel “anlaşmalı” işaretli değil. Girilen fiyatlar <strong>kesin fiyat olarak satılamaz</strong>; yalnız onaya bağlı hedef teklif olarak gösterilir.</div></div><?php endif; ?>
<?php if ($hotel['contract_valid_until'] && $hotel['contract_valid_until'] < date('Y-m-d')): ?><div class="alert alert-error"><?= icon('alert') ?><div>Otel anlaşmasının süresi <?= e(tr_date($hotel['contract_valid_until'])) ?> tarihinde doldu. Kesin fiyat gösterilmiyor.</div></div><?php endif; ?>
<div class="grid-2">
    <div class="card"><div class="card-head"><h2>Fiyat planları</h2></div><div class="card-body stack-s">
        <?php if (!$rooms): ?><p class="muted">Önce otele oda ekleyin.</p><?php endif; ?>
        <?php foreach ($plans as $p): ?>
            <div class="panel row-between"<?= (int) $p['id'] === $planId ? ' style="border-color:var(--c-teal)"' : '' ?>><div><strong><?= e($p['room_name']) ?> · <?= e($p['name']) ?></strong><div class="small muted"><?= e($p['concept_name'] ?: 'Konsept yok') ?> · <?= $p['source'] === 'campaign' ? 'Kampanya' : 'Anlaşmalı' ?> · <?= $p['is_bookable'] ? 'Satış yetkili' : 'Yalnız hedef teklif' ?> · <?= $p['member_discount_applies'] ? 'Üye indirimi uygulanır' : 'Net kurumsal fiyat' ?> · <?= $p['tax_included'] ? 'Vergi dahil' : 'Vergi hariç' ?><?= $p['is_active'] ? '' : ' · PASİF' ?></div></div>
            <div class="row"><a class="btn btn-secondary btn-sm" href="<?= e(url('/yonetim/fiyatlar', ['otel' => $hotel['id'], 'plan' => $p['id']])) ?>">Takvim</a><a class="btn btn-ghost btn-sm" href="<?= e(url('/yonetim/fiyatlar', ['otel' => $hotel['id'], 'plan' => $planId, 'plan_duzenle' => $p['id']])) ?>">Düzenle</a></div></div>
        <?php endforeach; ?>
    </div></div>
    <?php if ($rooms && can('pricing.manage')): $ep = $editPlan; ?>
    <div class="card"><div class="card-head"><h2><?= $ep ? 'Planı düzenle' : 'Yeni fiyat planı' ?></h2><?php if ($ep): ?><a href="<?= e(url('/yonetim/fiyatlar', ['otel' => $hotel['id'], 'plan' => $planId])) ?>">Yeni</a><?php endif; ?></div><div class="card-body">
        <form method="post" action="<?= e(url('/yonetim/fiyatlar/plan')) ?>"><?= csrf_field() ?><input type="hidden" name="hotel_id" value="<?= (int) $hotel['id'] ?>"><?php if ($ep): ?><input type="hidden" name="id" value="<?= (int) $ep['id'] ?>"><?php endif; ?>
            <div class="form-grid">
                <?= f_select('room_id', 'Oda', $rooms, $ep['room_id'] ?? '', 'Seçiniz', '', true) ?>
                <?= f_input('name', 'Plan adı', $ep['name'] ?? '', 'text', ['required' => true, 'placeholder' => 'Örn. 2026 Yaz Anlaşması HD']) ?>
                <?= f_select('concept_id', 'Konsept', $concepts, $ep['concept_id'] ?? '', 'Belirtilmemiş') ?>
                <?= f_select('source', 'Fiyat kaynağı', ['contract' => 'Anlaşmalı fiyat', 'campaign' => 'Kampanya fiyatı'], $ep['source'] ?? 'contract') ?>
                <?= f_select('currency', 'Para birimi', ['TRY' => 'TL', 'EUR' => 'EUR', 'USD' => 'USD'], $ep['currency'] ?? 'TRY') ?>
                <?= f_input('base_adults', 'Gecelik fiyata dahil yetişkin', $ep['base_adults'] ?? 2, 'number', ['min' => 1, 'max' => 6]) ?>
                <?= f_input('free_child_max_age', 'Bu yaş ve altı çocuk ücretsiz', $ep['free_child_max_age'] ?? 6, 'number', ['min' => 0, 'max' => 17]) ?>
                <?= f_input('child_max_age', 'Bu yaş ve üstü yetişkin fiyatı', $ep['child_max_age'] ?? 12, 'number', ['min' => 1, 'max' => 18]) ?>
                <?= f_input('free_cancel_days', 'Ücretsiz iptal (girişten kaç gün önce)', $ep['free_cancel_days'] ?? '', 'number', ['min' => 0]) ?>
                <?= f_input('contract_reference', 'Anlaşma / sözleşme no', $ep['contract_reference'] ?? '') ?>
                <?= f_input('valid_from', 'Geçerlilik başlangıcı', $ep['valid_from'] ?? '', 'date') ?>
                <?= f_input('valid_to', 'Geçerlilik bitişi', $ep['valid_to'] ?? '', 'date') ?>
                <?= f_textarea('cancellation_policy', 'İptal koşulu (plana özel)', $ep['cancellation_policy'] ?? '', 2) ?>
                <?= f_textarea('payment_terms', 'Ödeme koşulu (plana özel)', $ep['payment_terms'] ?? '', 2) ?>
            </div>
            <?= f_check('tax_included', 'Fiyatlar vergiler dahil', (bool) ($ep['tax_included'] ?? 1)) ?>
            <?= f_check('refundable', 'İade edilebilir fiyat', (bool) ($ep['refundable'] ?? 1)) ?>
            <?= f_check('member_discount_applies', 'Üye/kurum indirimi uygulanır', (bool) ($ep['member_discount_applies'] ?? 1), 'Kaldırırsanız girilen fiyat net kurumsal fiyat kabul edilir.') ?>
            <?= f_check('is_bookable', 'Geçerli otel anlaşmasıyla kesin satış yetkisi var', (bool) ($ep['is_bookable'] ?? 0), 'İşaretlenmezse bu fiyat yalnız “onaya bağlı hedef teklif” olarak gösterilir.') ?>
            <?= f_check('is_active', 'Aktif', (bool) ($ep['is_active'] ?? 1)) ?>
            <?= f_textarea('notes', 'Notlar', $ep['notes'] ?? '', 2) ?>
            <button class="btn" type="submit">PLANI KAYDET</button>
        </form>
    </div></div>
    <?php endif; ?>
</div>
<?php if ($planId): ?>
<div class="grid-2" style="margin-top:20px">
    <?php if (can('pricing.manage')): ?>
    <div class="card"><div class="card-head"><h2>Toplu gecelik fiyat girişi</h2></div><div class="card-body">
        <form method="post" action="<?= e(url('/yonetim/fiyatlar/takvim')) ?>"><?= csrf_field() ?><input type="hidden" name="plan_id" value="<?= (int) $planId ?>">
            <div class="form-grid">
                <?= f_input('from', 'Başlangıç', date('Y-m-d'), 'date', ['required' => true]) ?>
                <?= f_input('to', 'Bitiş', date('Y-m-d', strtotime('+30 days')), 'date', ['required' => true]) ?>
                <?= f_input('price', 'Oda başı gecelik fiyat (TL)', '', 'text', ['required' => true, 'inputmode' => 'decimal', 'placeholder' => '4.500,00']) ?>
                <?= f_input('single', 'Tek kişi kullanımı (boş: aynı)', '', 'text', ['inputmode' => 'decimal']) ?>
                <?= f_input('extra_adult', 'İlave yetişkin / gece', '', 'text', ['inputmode' => 'decimal']) ?>
                <?= f_input('child', 'Ücretli çocuk / gece', '', 'text', ['inputmode' => 'decimal']) ?>
            </div>
            <?= App\Core\View::partial('admin/partials_weekdays') ?>
            <button class="btn" type="submit">FİYATLARI KAYDET</button>
        </form>
    </div></div>
    <?php endif; ?>
    <div class="card"><div class="card-head"><h2>Fiyat takvimi (42 gün)</h2><form method="get" class="row"><input type="hidden" name="otel" value="<?= (int) $hotel['id'] ?>"><input type="hidden" name="plan" value="<?= (int) $planId ?>"><label class="sr-only" for="bas">Başlangıç</label><input type="date" id="bas" name="bas" value="<?= e($from) ?>" style="width:auto"><button class="btn btn-secondary btn-sm">Git</button></form></div><div class="card-body">
        <?php $byDate = []; foreach ($calendar as $c) { $byDate[$c['stay_date']] = $c; } $start = new DateTimeImmutable($from); $offset = (int) $start->format('N') - 1; ?>
        <div class="cal-grid" role="grid"><?php foreach (['Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz'] as $dn): ?><div class="small muted text-center" role="columnheader"><?= $dn ?></div><?php endforeach; ?>
        <?php for ($i = 0; $i < $offset; $i++): ?><div></div><?php endfor; ?>
        <?php for ($i = 0; $i < 42; $i++): $d = $start->modify("+$i day"); $k = $d->format('Y-m-d'); $c = $byDate[$k] ?? null; ?>
            <div class="cal-cell<?= $c ? '' : ' none' ?>" role="gridcell"><div class="d"><?= $d->format('j') ?> <?= TR_MONTHS_SHORT[(int) $d->format('n')] ?></div><?= $c ? e(money((int) $c['price_minor'])) : 'fiyat yok' ?></div>
        <?php endfor; ?></div>
    </div></div>
</div>
<?php endif; ?>
<div class="card" style="margin-top:20px"><div class="card-head"><h2>Doğrulanmış referans fiyatlar</h2></div><div class="card-body">
    <div class="alert alert-info"><?= icon('info') ?><div>Dış sitelerde görülen fiyatlar <strong>satış veya rezervasyon yetkisi vermez</strong>. Bu kayıtlar yalnız “onaya bağlı hedef teklif” üretmek ve aynı koşullardaki (tarih, oda, konuk, konsept, vergi, iptal) karşılaştırmalı tasarrufu göstermek için kullanılır. Süresi geçen kayıt kullanılmaz.</div></div>
    <?php if ($references): ?><div class="table-wrap" style="margin-bottom:16px"><table class="table responsive"><thead><tr><th>Kaynak</th><th>Oda / konsept</th><th>Tarih / konuk</th><th class="num">Toplam</th><th>Geçerlilik</th><th></th></tr></thead><tbody>
    <?php foreach ($references as $r): $valid = strtotime($r['valid_until']) > time(); ?><tr>
        <td data-label="Kaynak"><?= e($r['source'] === 'provider' ? 'Sağlayıcı: ' . $r['provider_name'] : ($r['source_label'] ?: 'Manuel')) ?><div class="small muted">Sorgu: <?= e(tr_datetime($r['captured_at'])) ?></div></td>
        <td data-label="Oda"><?= e($r['room_label'] ?: ($r['room_name'] ?: '—')) ?> · <?= e($r['concept_name'] ?: '—') ?></td>
        <td data-label="Tarih"><?= e(tr_date_short($r['check_in'])) ?> – <?= e(tr_date_short($r['check_out'])) ?> · <?= (int) $r['adults'] ?> yet.<?= $r['children_ages'] !== '' ? ' + ' . e($r['children_ages']) : '' ?> · <?= (int) $r['rooms_count'] ?> oda</td>
        <td data-label="Toplam" class="num"><?= e(money((int) $r['total_minor'])) ?> <small class="muted"><?= $r['tax_included'] ? 'vergi dahil' : 'vergi hariç' ?></small></td>
        <td data-label="Geçerlilik"><span class="badge badge-<?= $valid ? 'success' : 'neutral' ?>"><?= $valid ? 'Geçerli' : 'Süresi doldu' ?></span><div class="small muted"><?= e(tr_datetime($r['valid_until'])) ?></div></td>
        <td class="actions"><?php if (can('pricing.manage')): ?><form method="post" action="<?= e(url('/yonetim/fiyatlar/referans/' . $r['id'] . '/sil')) ?>" data-confirm="Referans fiyat silinsin mi?"><?= csrf_field() ?><button class="btn btn-danger btn-sm"><?= icon('trash', 'icon-s') ?></button></form><?php endif; ?></td>
    </tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
    <?php if (can('pricing.manage')): ?>
    <details><summary class="btn btn-secondary">+ Referans fiyat ekle</summary>
    <form method="post" action="<?= e(url('/yonetim/fiyatlar/referans')) ?>" style="margin-top:12px"><?= csrf_field() ?><input type="hidden" name="hotel_id" value="<?= (int) $hotel['id'] ?>"><input type="hidden" name="currency" value="TRY">
        <div class="form-grid form-grid-3">
            <?= f_input('source_label', 'Kaynak (site / kanal adı)', '', 'text', ['required' => true]) ?>
            <?= f_select('room_id', 'Eşleşen oda', $rooms, '', 'Belirtilmemiş') ?>
            <?= f_input('room_name', 'Kaynaktaki oda adı', '') ?>
            <?= f_input('check_in', 'Giriş', '', 'date', ['required' => true]) ?>
            <?= f_input('check_out', 'Çıkış', '', 'date', ['required' => true]) ?>
            <?= f_input('total', 'Toplam fiyat (TL)', '', 'text', ['required' => true, 'inputmode' => 'decimal']) ?>
            <?= f_input('adults', 'Yetişkin', 2, 'number', ['min' => 1, 'required' => true]) ?>
            <?= f_input('children_ages', 'Çocuk yaşları (virgülle)', '') ?>
            <?= f_input('rooms_count', 'Oda sayısı', 1, 'number', ['min' => 1]) ?>
            <?= f_select('concept_id', 'Konsept', $concepts, '', 'Belirtilmemiş') ?>
            <?= f_select('refundable', 'İptal koşulu', ['1' => 'İade edilebilir', '0' => 'İade edilemez'], '', 'Bilinmiyor') ?>
            <?= f_input('cancellation_summary', 'İptal koşulu özeti', '') ?>
            <?= f_input('captured_at', 'Sorgu zamanı', date('Y-m-d\TH:i'), 'datetime-local', ['required' => true]) ?>
            <?= f_input('valid_until', 'Geçerlilik sonu', date('Y-m-d\TH:i', strtotime('+1 day')), 'datetime-local', ['required' => true]) ?>
            <?= f_input('notes', 'Not', '') ?>
        </div>
        <?= f_check('tax_included', 'Fiyat vergiler dahil', true) ?>
        <label class="check"><input type="checkbox" name="verified" value="1" required><span>Bu fiyatı belirtilen tarih, oda, konuk ve koşullar için bizzat doğruladım.</span></label><?= field_error('verified') ?>
        <button class="btn" type="submit">REFERANSI KAYDET</button>
    </form></details>
    <?php endif; ?>
</div></div>
<?php endif; ?>
