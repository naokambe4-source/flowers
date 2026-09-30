/**
 * Cilt Rotası — Canlı düzenleyici.
 * Metne tıkla-yaz, görsel değiştir, bölüm sırala/gizle, renk & yazı tipi paneli.
 */
(function ($) {
	'use strict';

	var L = window.CRLive || {};
	var body = document.body;
	var active = false;
	var changes = { texts: {}, images: {}, design: {}, sections: null };
	var dirty = 0;
	var bar, drawer;

	function count() {
		dirty = Object.keys(changes.texts).length + Object.keys(changes.images).length + Object.keys(changes.design).length + (changes.sections ? 1 : 0);
		if (bar) {
			bar.find('[data-l-count]').text(dirty ? dirty + ' değişiklik' : 'Değişiklik yok');
			bar.find('[data-l-save]').prop('disabled', !dirty);
		}
	}

	function toast(msg) {
		var t = $('.cr-toast');
		if (!t.length) { return; }
		t.text(msg).prop('hidden', false);
		clearTimeout(t.data('t'));
		t.data('t', setTimeout(function () { t.prop('hidden', true); }, 2600));
	}

	/* ---------- Araç çubuğu ---------- */
	function buildBar() {
		bar = $(
			'<div class="crl-bar" role="toolbar" aria-label="Canlı düzenleyici">' +
				'<span class="crl-bar__brand"><span class="crl-heart">♥</span> Canlı düzenleyici</span>' +
				'<span class="crl-bar__count" data-l-count>Değişiklik yok</span>' +
				'<span class="crl-bar__sep"></span>' +
				'<button type="button" class="crl-btn" data-l-design>🎨 Tasarım</button>' +
				(L.isHome ? '<button type="button" class="crl-btn" data-l-sections>☰ Bölümler</button>' : '') +
				(L.editUrl ? '<a class="crl-btn" href="' + L.editUrl + '">✎ Bu sayfayı düzenle</a>' : '') +
				'<a class="crl-btn" href="' + L.panel + '">⚙ Panel</a>' +
				'<span class="crl-bar__sep"></span>' +
				'<button type="button" class="crl-btn" data-l-cancel>Vazgeç</button>' +
				'<button type="button" class="crl-btn crl-btn--primary" data-l-save disabled>Kaydet</button>' +
				'<button type="button" class="crl-btn crl-btn--icon" data-l-close aria-label="Kapat">✕</button>' +
			'</div>'
		);
		$('body').append(bar);
		bar.on('click', '[data-l-save]', save);
		bar.on('click', '[data-l-cancel]', function () {
			if (!dirty || window.confirm('Kaydedilmemiş değişiklikler silinsin mi?')) { dirty = 0; window.location.reload(); }
		});
		bar.on('click', '[data-l-close]', function () {
			if (dirty && !window.confirm('Kaydedilmemiş değişiklikler var. Yine de kapatılsın mı?')) { return; }
			if (dirty) { dirty = 0; window.location.reload(); return; }
			toggle(false);
		});
		bar.on('click', '[data-l-design]', function () { openDrawer('design'); });
		bar.on('click', '[data-l-sections]', function () { openDrawer('sections'); });
	}

	/* ---------- Metinler ---------- */
	function enableTexts() {
		$('[data-cr]').each(function () {
			var el = $(this);
			el.attr('contenteditable', 'true').attr('spellcheck', 'true').addClass('crl-editable');
			if (!el.data('orig')) { el.data('orig', el.text()); }
		});
	}
	function disableTexts() {
		$('[data-cr]').removeAttr('contenteditable').removeClass('crl-editable');
	}
	$(document).on('input', '[data-cr][contenteditable]', function () {
		var el = $(this);
		var key = el.attr('data-cr');
		var val = el.text();
		changes.texts[key] = val;
		$('[data-cr="' + key + '"]').not(el).text(val);
		count();
	});
	$(document).on('keydown', '[data-cr][contenteditable]', function (e) {
		if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); $(this).blur(); }
		if (e.key === 'Escape') { $(this).text($(this).data('orig')); $(this).trigger('input').blur(); }
	});
	$(document).on('paste', '[data-cr][contenteditable]', function (e) {
		e.preventDefault();
		var text = (e.originalEvent.clipboardData || window.clipboardData).getData('text');
		document.execCommand('insertText', false, text);
	});
	// Düzenleme modunda düzenlenebilir alanı içeren bağlantılar çalışmasın.
	document.addEventListener('click', function (e) {
		if (!active) { return; }
		var ed = e.target.closest('[data-cr], [data-cr-img], .crl-img-btn');
		if (ed && e.target.closest('a')) { e.preventDefault(); }
	}, true);

	/* ---------- Görseller ---------- */
	function enableImages() {
		$('[data-cr-img]').each(function () {
			var wrap = $(this);
			if (wrap.find('> .crl-img-btn').length) { return; }
			if (wrap.css('position') === 'static') { wrap.css('position', 'relative'); }
			wrap.addClass('crl-img').append('<button type="button" class="crl-img-btn">🖼 Görseli değiştir</button>');
		});
	}
	$(document).on('click', '.crl-img-btn', function (e) {
		e.preventDefault();
		e.stopPropagation();
		var wrap = $(this).closest('[data-cr-img]');
		var key = wrap.attr('data-cr-img');
		if (!window.wp || !wp.media) { return; }
		var frame = wp.media({ title: 'Görsel seç', button: { text: 'Bu görseli kullan' }, library: { type: 'image' }, multiple: false });
		frame.on('select', function () {
			var a = frame.state().get('selection').first().toJSON();
			var url = (a.sizes && (a.sizes.large || a.sizes.full)) ? (a.sizes.large || a.sizes.full).url : a.url;
			var img = wrap.find('img').first();
			if (img.length) {
				img.attr('src', url).removeAttr('srcset').removeAttr('sizes');
			} else {
				wrap.find('.cr-img-empty').replaceWith('<img src="' + url + '" alt="" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover">');
			}
			changes.images[key] = a.id;
			count();
		});
		frame.open();
	});

	/* ---------- Bölümler ---------- */
	function sectionState() {
		var list = [];
		$('[data-cr-section]').each(function () {
			var s = $(this);
			var hiddenWrap = s.closest('.cr-hidden-section');
			list.push({ id: s.attr('data-cr-section'), on: hiddenWrap.length ? (hiddenWrap.hasClass('crl-show') ? 1 : 0) : (s.hasClass('crl-off') ? 0 : 1) });
		});
		return list;
	}
	function enableSections() {
		$('.cr-hidden-section').prop('hidden', false).addClass('crl-hidden');
		$('[data-cr-section]').each(function () {
			var s = $(this);
			if (s.find('> .crl-sec').length) { return; }
			var off = s.closest('.cr-hidden-section').length > 0;
			if (off) { s.addClass('crl-off'); }
			s.addClass('crl-section').prepend(
				'<div class="crl-sec" contenteditable="false">' +
					'<span class="crl-sec__label">' + (s.attr('data-cr-label') || '') + '</span>' +
					'<button type="button" data-sec-up title="Yukarı taşı">▲</button>' +
					'<button type="button" data-sec-down title="Aşağı taşı">▼</button>' +
					'<button type="button" data-sec-toggle title="Göster / gizle">' + (off ? 'Göster' : 'Gizle') + '</button>' +
				'</div>'
			);
		});
	}
	function outer(s) {
		var w = s.closest('.cr-hidden-section');
		return w.length ? w : s;
	}
	$(document).on('click', '[data-sec-up],[data-sec-down]', function (e) {
		e.preventDefault();
		var s = $(this).closest('[data-cr-section]');
		var node = outer(s);
		var up = $(this).is('[data-sec-up]');
		var sib = up ? node.prevAll('section,.cr-hidden-section').first() : node.nextAll('section,.cr-hidden-section').first();
		if (!sib.length) { return; }
		if (up) { node.insertBefore(sib); } else { node.insertAfter(sib); }
		changes.sections = sectionState();
		count();
		$('html,body').animate({ scrollTop: node.offset().top - 120 }, 250);
		renderSectionList();
	});
	$(document).on('click', '[data-sec-toggle]', function (e) {
		e.preventDefault();
		var s = $(this).closest('[data-cr-section]');
		var w = s.closest('.cr-hidden-section');
		var off;
		if (w.length) {
			w.toggleClass('crl-show');
			off = !w.hasClass('crl-show');
		} else {
			s.toggleClass('crl-off');
			off = s.hasClass('crl-off');
		}
		s.toggleClass('crl-off', off);
		$(this).text(off ? 'Göster' : 'Gizle');
		changes.sections = sectionState();
		count();
		renderSectionList();
	});
	function disableSections() {
		$('.crl-sec').remove();
		$('.cr-hidden-section').prop('hidden', true).removeClass('crl-hidden crl-show');
		$('[data-cr-section]').removeClass('crl-section');
	}

	/* ---------- Çekmece: tasarım ve bölümler ---------- */
	function buildDrawer() {
		drawer = $('<aside class="crl-drawer" aria-label="Tasarım ayarları" hidden><div class="crl-drawer__head"><strong data-d-title>Tasarım</strong><button type="button" class="crl-btn crl-btn--icon" data-d-close aria-label="Kapat">✕</button></div><div class="crl-drawer__body"></div></aside>');
		$('body').append(drawer);
		drawer.on('click', '[data-d-close]', function () { drawer.prop('hidden', true); });
	}
	function renderDesign() {
		var b = drawer.find('.crl-drawer__body').empty();
		b.append('<p class="crl-help">Değişiklikler anında önizlenir. Kalıcı olması için <b>Kaydet</b>’e basın.</p>');
		var colors = $('<div class="crl-group"><p class="crl-group__title">Renkler</p></div>');
		(L.colors || []).forEach(function (c) {
			var val = changes.design[c.id] || c.value;
			colors.append('<label class="crl-color"><input type="color" value="' + val + '" data-color="' + c.id + '" data-var="' + c.var + '"><span>' + c.label + '</span><code>' + val + '</code></label>');
		});
		b.append(colors);
		var fonts = $('<div class="crl-group"><p class="crl-group__title">Yazı tipleri</p></div>');
		[['heading', 'Başlık', 'font_heading'], ['body', 'Gövde', 'font_body']].forEach(function (f) {
			var sel = $('<select data-font="' + f[0] + '" data-key="' + f[2] + '"></select>');
			$.each(L.fonts[f[0]], function (k, label) {
				sel.append($('<option>').val(k).text(label));
			});
			sel.val(changes.design[f[2]] || L.font[f[0]]);
			fonts.append($('<label class="crl-field"><span>' + f[1] + '</span></label>').append(sel));
		});
		b.append(fonts);
		var shape = $('<div class="crl-group"><p class="crl-group__title">Biçim</p></div>');
		shape.append('<label class="crl-field"><span>Köşe yuvarlaklığı <em data-rv>' + (changes.design.radius !== undefined ? changes.design.radius : L.radius) + 'px</em></span><input type="range" min="0" max="40" value="' + (changes.design.radius !== undefined ? changes.design.radius : L.radius) + '" data-range="radius"></label>');
		shape.append('<label class="crl-field"><span>Yazı ölçeği <em data-sv>%' + (changes.design.font_scale || L.scale) + '</em></span><input type="range" min="85" max="120" value="' + (changes.design.font_scale || L.scale) + '" data-range="font_scale"></label>');
		b.append(shape);
	}
	drawerDelegates();
	function drawerDelegates() {
		$(document).on('input', '.crl-drawer [data-color]', function () {
			var i = $(this);
			document.documentElement.style.setProperty(i.data('var'), i.val());
			i.siblings('code').text(i.val());
			changes.design[i.data('color')] = i.val();
			count();
		});
		$(document).on('change', '.crl-drawer [data-font]', function () {
			var s = $(this);
			var fam = s.val();
			var id = 'crl-font-' + fam.replace(/\s+/g, '-');
			if (!document.getElementById(id)) {
				$('head').append('<link id="' + id + '" rel="stylesheet" href="https://fonts.googleapis.com/css2?family=' + encodeURIComponent(fam).replace(/%20/g, '+') + ':wght@400;500;600;700&display=swap&subset=latin-ext">');
			}
			var stack = s.data('font') === 'heading' ? "'" + fam + "',Georgia,serif" : "'" + fam + "',system-ui,sans-serif";
			document.documentElement.style.setProperty(s.data('font') === 'heading' ? '--cr-font-head' : '--cr-font-body', stack);
			changes.design[s.data('key')] = fam;
			count();
		});
		$(document).on('input', '.crl-drawer [data-range]', function () {
			var r = $(this);
			var v = parseInt(r.val(), 10);
			if (r.data('range') === 'radius') {
				document.documentElement.style.setProperty('--cr-radius', v + 'px');
				r.closest('label').find('[data-rv]').text(v + 'px');
				changes.design.radius = v;
			} else {
				document.documentElement.style.setProperty('--cr-scale', v / 100);
				r.closest('label').find('[data-sv]').text('%' + v);
				changes.design.font_scale = v;
			}
			count();
		});
	}
	function renderSectionList() {
		if (!drawer || drawer.prop('hidden') || drawer.data('mode') !== 'sections') { return; }
		var b = drawer.find('.crl-drawer__body').empty();
		b.append('<p class="crl-help">Sıralamak için okları kullanın; gizli bölümler ziyaretçilere görünmez.</p>');
		var ul = $('<ul class="crl-seclist"></ul>');
		sectionState().forEach(function (s) {
			var el = $('[data-cr-section="' + s.id + '"]');
			ul.append('<li class="' + (s.on ? '' : 'is-off') + '"><button type="button" class="crl-seclist__go" data-go="' + s.id + '">' + (el.attr('data-cr-label') || s.id) + '</button><span>' + (s.on ? 'Açık' : 'Gizli') + '</span></li>');
		});
		b.append(ul);
	}
	$(document).on('click', '[data-go]', function () {
		var el = $('[data-cr-section="' + $(this).data('go') + '"]');
		if (el.length) { $('html,body').animate({ scrollTop: el.offset().top - 100 }, 300); }
	});
	function openDrawer(mode) {
		drawer.data('mode', mode).prop('hidden', false);
		drawer.find('[data-d-title]').text(mode === 'design' ? 'Tasarım' : 'Bölümler');
		if (mode === 'design') { renderDesign(); } else { renderSectionList(); }
	}

	/* ---------- Kaydet ---------- */
	function save() {
		var btn = bar.find('[data-l-save]').prop('disabled', true).text('Kaydediliyor…');
		$.post(L.ajax, { action: 'cr_live_save', nonce: L.nonce, changes: JSON.stringify(changes) }).done(function (r) {
			if (r && r.success) {
				toast('✓ ' + r.data.message);
				changes = { texts: {}, images: {}, design: {}, sections: null };
				$('[data-cr]').each(function () { $(this).data('orig', $(this).text()); });
				count();
			} else {
				toast((r && r.data && r.data.message) || 'Kaydedilemedi.');
				btn.prop('disabled', false);
			}
		}).fail(function () {
			toast('Bağlantı hatası, tekrar deneyin.');
			btn.prop('disabled', false);
		}).always(function () { btn.text('Kaydet'); });
	}

	/* ---------- Aç / kapat ---------- */
	function toggle(on) {
		active = typeof on === 'boolean' ? on : !active;
		body.classList.toggle('cr-editing', active);
		if (active) {
			if (!bar) { buildBar(); buildDrawer(); }
			bar.prop('hidden', false);
			enableTexts();
			enableImages();
			enableSections();
			count();
			toast('Düzenlemek için yazılara tıklayın.');
		} else {
			if (bar) { bar.prop('hidden', true); }
			if (drawer) { drawer.prop('hidden', true); }
			disableTexts();
			disableSections();
			$('.crl-img-btn').remove();
		}
	}
	$(document).on('click', '.cr-live-toggle, #wp-admin-bar-cr-live a', function (e) {
		e.preventDefault();
		toggle();
	});
	window.addEventListener('beforeunload', function (e) {
		if (dirty) { e.preventDefault(); e.returnValue = ''; }
	});
	if (L.autoOpen) { $(function () { toggle(true); }); }
})(jQuery);
