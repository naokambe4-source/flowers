<div class="admin-title"><div><p><a href="<?= e(url('/yonetim/uyeler')) ?>">← Üyeler</a></p><h1>Üye aktarımı (CSV / XLSX)</h1><p>1. Dosya yükle → 2. Sütun eşleştir → 3. Önizleme ve hata raporu → 4. Kaydet</p></div></div>
<?php if ($stage === 'upload'): ?>
<div class="card"><div class="card-body">
    <p>İlk satır başlık olmalıdır. Önerilen sütunlar: <span class="code">Ad</span>, <span class="code">Soyad</span>, <span class="code">E-posta</span>, <span class="code">Telefon</span>, <span class="code">Departman</span>, <span class="code">Kurum</span>. En fazla 2000 satır. Excel’de “CSV UTF-8” veya XLSX olarak kaydedin.</p>
    <form method="post" action="<?= e(url('/yonetim/uyeler/aktar')) ?>" enctype="multipart/form-data"><?= csrf_field() ?>
        <div class="form-grid"><div class="field"><label for="file">Dosya</label><input type="file" id="file" name="file" accept=".csv,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required></div>
        <?= f_select('institution_id', 'Kurum sütunu yoksa varsayılan kurum', $institutions, '', 'Seçiniz') ?></div>
        <button class="btn" type="submit"><?= icon('upload') ?> YÜKLE VE DEVAM ET</button>
    </form>
</div></div>
<?php elseif ($stage === 'map'): ?>
<form method="post" action="<?= e(url('/yonetim/uyeler/aktar/onizleme')) ?>" class="card"><div class="card-body"><?= csrf_field() ?><input type="hidden" name="token" value="<?= e($token) ?>">
    <p><strong><?= (int) $total ?></strong> veri satırı bulundu. Her sütunun hangi alana karşılık geldiğini seçin.</p>
    <div class="table-wrap"><table class="table"><thead><tr><th>Dosyadaki sütun</th><th>Örnek değerler</th><th>Alan</th></tr></thead><tbody>
    <?php foreach ($headers as $i => $h): ?><tr><td><strong><?= e($h ?: '(başlıksız)') ?></strong></td><td class="small muted"><?= e(implode(' · ', array_filter(array_map(static fn ($r) => $r[$i] ?? '', $sample)))) ?></td>
        <td><label class="sr-only" for="map<?= $i ?>">Alan</label><select id="map<?= $i ?>" name="map[<?= $i ?>]"><option value="">— Aktarma —</option><?php foreach (App\Services\ImportService::FIELDS as $k => $l): ?><option value="<?= e($k) ?>"<?= ($mapping[$i] ?? '') === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></td></tr><?php endforeach; ?>
    </tbody></table></div>
    <?= f_select('default_institution', 'Varsayılan kurum (kurum sütunu boşsa)', $institutions, $defaultInst ?? '', 'Yok') ?>
    <button class="btn" type="submit">ÖNİZLE VE DOĞRULA</button>
</div></form>
<?php elseif ($stage === 'preview'): ?>
<div class="tiles" style="margin-bottom:16px"><div class="tile"><span class="tile-label">Aktarılacak</span><span class="tile-value" style="color:var(--c-success)"><?= count($valid) ?></span></div><div class="tile"><span class="tile-label">Hatalı / atlanacak</span><span class="tile-value" style="color:var(--c-danger)"><?= count($errors) ?></span></div></div>
<?php if ($errors): ?><div class="card" style="margin-bottom:16px"><div class="card-head"><h2>Hata raporu</h2></div><div class="table-wrap" style="border:0"><table class="table responsive"><thead><tr><th>Satır</th><th>E-posta</th><th>Hatalar</th></tr></thead><tbody><?php foreach (array_slice($errors, 0, 300) as $er): ?><tr><td data-label="Satır"><?= (int) $er['line'] ?></td><td data-label="E-posta"><?= e($er['email'] ?: '—') ?></td><td data-label="Hata" style="color:var(--c-danger)"><?= e(implode('; ', $er['errors'])) ?></td></tr><?php endforeach; ?></tbody></table></div></div><?php endif; ?>
<?php if ($valid): ?><div class="card"><div class="card-head"><h2>Aktarılacak ilk 20 kayıt</h2></div><div class="table-wrap" style="border:0"><table class="table responsive"><thead><tr><th>Ad soyad</th><th>E-posta</th><th>Telefon</th><th>Kurum</th></tr></thead><tbody><?php foreach (array_slice($valid, 0, 20) as $v): ?><tr><td data-label="Ad"><?= e($v['first_name'] . ' ' . $v['last_name']) ?></td><td data-label="E-posta"><?= e($v['email']) ?></td><td data-label="Telefon"><?= e($v['phone']) ?></td><td data-label="Kurum"><?= e($instNames[$v['institution_id']] ?? '') ?></td></tr><?php endforeach; ?></tbody></table></div>
<div class="card-body"><form method="post" action="<?= e(url('/yonetim/uyeler/aktar/tamamla')) ?>"><?= csrf_field() ?><input type="hidden" name="token" value="<?= e($token) ?>"><input type="hidden" name="default_institution" value="<?= e($defaultInst) ?>"><?php foreach ($mapping as $c => $fld): ?><input type="hidden" name="map[<?= (int) $c ?>]" value="<?= e($fld) ?>"><?php endforeach; ?>
    <label class="check"><input type="checkbox" name="send_invites" value="1" checked> Aktarılan üyelere parola oluşturma (davet) bağlantısı gönder</label>
    <button class="btn" type="submit"><?= count($valid) ?> ÜYEYİ AKTAR</button></form></div></div><?php endif; ?>
<?php else: ?>
<div class="alert alert-success"><?= icon('check-circle') ?><div><strong><?= (int) $created ?></strong> üye oluşturuldu. <?= (int) $skipped ?> satır hatalı olduğu için atlandı.</div></div>
<?php if ($links): ?><div class="card"><div class="card-head"><h2>E-posta yapılandırılmadığı için davet bağlantıları</h2></div><div class="card-body"><p class="small muted">Bu bağlantılar 72 saat geçerlidir ve sayfa kapatıldığında tekrar gösterilmez. Yalnız ilgili kişiye güvenli kanaldan iletin.</p><div class="table-wrap"><table class="table"><tbody><?php foreach ($links as $l): ?><tr><td><?= e($l['email']) ?></td><td><input readonly value="<?= e($l['link']) ?>" aria-label="Bağlantı"></td></tr><?php endforeach; ?></tbody></table></div></div></div><?php endif; ?>
<a class="btn" href="<?= e(url('/yonetim/uyeler')) ?>">Üyelere dön</a>
<?php endif; ?>
