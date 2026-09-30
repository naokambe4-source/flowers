/**
 * Cilt Rotası — yönetim etkileşimleri.
 */
(function ($) {
	'use strict';

	var A = window.CRAdmin || {};

	/* ---------- Renk seçiciler ---------- */
	function initColors(ctx) {
		if ($.fn.wpColorPicker) {
			$('.cr-color', ctx || document).not('.wp-color-picker').wpColorPicker({ change: dirty });
		}
	}

	/* ---------- Kaydedilmemiş değişiklik uyarısı ---------- */
	var isDirty = false;
	function dirty() {
		isDirty = true;
		$('.cr-savebar').addClass('is-dirty');
	}
	$(document).on('input change', '.cr-settings__form :input', dirty);
	$(document).on('submit', '.cr-settings__form', function () { isDirty = false; });
	window.addEventListener('beforeunload', function (e) {
		if (isDirty) { e.preventDefault(); e.returnValue = ''; }
	});

	/* ---------- Medya ---------- */
	$(document).on('click', '[data-media-pick]', function (e) {
		e.preventDefault();
		var box = $(this).closest('[data-media]');
		var frame = wp.media({ title: 'Görsel seç', button: { text: 'Kullan' }, library: { type: 'image' }, multiple: false });
		frame.on('select', function () {
			var a = frame.state().get('selection').first().toJSON();
			var url = a.sizes && a.sizes.medium ? a.sizes.medium.url : a.url;
			box.find('[data-media-input]').val(a.id).trigger('change');
			box.find('[data-media-url]').val('');
			box.find('[data-media-preview]').html('<img src="' + url + '" alt="">');
		});
		frame.open();
	});
	$(document).on('click', '[data-media-clear]', function (e) {
		e.preventDefault();
		var box = $(this).closest('[data-media]');
		box.find('[data-media-input]').val('').trigger('change');
		box.find('[data-media-url]').val('');
		box.find('[data-media-preview]').html('<span>Görsel yok</span>');
	});
	$(document).on('change input', '[data-media-url]', function () {
		var box = $(this).closest('[data-media]');
		var v = $(this).val().trim();
		if (!v) { return; }
		box.find('[data-media-input]').val(v).trigger('change');
		box.find('[data-media-preview]').html('<img src="' + v.replace(/"/g, '') + '" alt="">');
	});

	/* ---------- Tekrarlayıcı ---------- */
	function reindex(rep) {
		var base = rep.data('name');
		rep.find('> [data-rows] > [data-row]').each(function (i) {
			$(this).find(':input[name]').each(function () {
				var n = $(this).attr('name');
				if (n.indexOf(base + '[') === 0) {
					$(this).attr('name', n.replace(/^([^\[]+\[[^\]]+\])\[[^\]]+\]/, '$1[' + i + ']'));
				}
			});
		});
		var max = parseInt(rep.data('max'), 10) || 0;
		rep.find('> [data-add]').prop('disabled', max && rep.find('> [data-rows] > [data-row]').length >= max);
	}
	function initSortables(ctx) {
		if (!$.fn.sortable) { return; }
		$('[data-repeater] > [data-rows]', ctx || document).sortable({
			handle: '.cr-row__drag',
			placeholder: 'cr-row-placeholder',
			update: function () { reindex($(this).closest('[data-repeater]')); dirty(); }
		});
		$('[data-sortable]', ctx || document).sortable({
			handle: '.cr-row__drag',
			placeholder: 'cr-row-placeholder',
			update: function () {
				$(this).children('li').each(function (i) {
					$(this).find(':input[name]').each(function () {
						$(this).attr('name', $(this).attr('name').replace(/\[sections\]\[\d+\]/, '[sections][' + i + ']'));
					});
				});
				dirty();
			}
		});
		$('[data-pairs] > [data-rows]', ctx || document).sortable({
			handle: '.cr-row__drag',
			placeholder: 'cr-row-placeholder',
			update: function () { reindexPairs($(this).closest('[data-pairs]')); }
		});
	}
	$(document).on('click', '[data-repeater] > [data-add]', function (e) {
		e.preventDefault();
		var rep = $(this).closest('[data-repeater]');
		var tpl = rep.find('> [data-row-template]').html();
		var row = $(tpl.replace(/__i__/g, Date.now()));
		row.addClass('is-open');
		rep.find('> [data-rows]').append(row);
		initColors(row);
		reindex(rep);
		dirty();
		row.find(':input:visible').first().trigger('focus');
	});
	$(document).on('click', '[data-repeater] [data-remove]', function (e) {
		e.preventDefault();
		if (!window.confirm('Bu öğe silinsin mi?')) { return; }
		var rep = $(this).closest('[data-repeater]');
		$(this).closest('[data-row]').remove();
		reindex(rep);
		dirty();
	});
	$(document).on('click', '[data-repeater] [data-dup]', function (e) {
		e.preventDefault();
		var rep = $(this).closest('[data-repeater]');
		var row = $(this).closest('[data-row]');
		var copy = row.clone();
		copy.find('textarea').each(function (i) { $(this).val(row.find('textarea').eq(i).val()); });
		copy.find('select').each(function (i) { $(this).val(row.find('select').eq(i).val()); });
		row.after(copy);
		reindex(rep);
		dirty();
	});
	$(document).on('click', '[data-repeater] [data-toggle]', function (e) {
		e.preventDefault();
		$(this).closest('[data-row]').toggleClass('is-open');
	});
	$(document).on('input', '[data-repeater] [data-row] .cr-row__field:first-child :input', function () {
		var v = $(this).val();
		$(this).closest('[data-row]').find('> .cr-row__head [data-toggle]').text(v ? v.substring(0, 60) : 'Yeni öğe');
	});
	$(document).on('change', '[data-section-toggle]', function () {
		$(this).closest('li').toggleClass('is-off', !this.checked);
	});

	/* ---------- Ayar arama ---------- */
	$(document).on('input', '[data-settings-search]', function () {
		var q = $(this).val().toLocaleLowerCase('tr-TR').trim();
		$('.cr-settings__form .cr-field').each(function () {
			var s = $(this).data('search') || '';
			$(this).toggleClass('is-hidden', !!q && s.indexOf(q) === -1);
		});
		$('.cr-settings__form .cr-card-a').each(function () {
			$(this).toggle(!q || $(this).find('.cr-field:not(.is-hidden)').length > 0);
		});
	});

	/* ---------- SSS / adım çiftleri ---------- */
	function reindexPairs(box) {
		var name = box.data('name');
		box.find('[data-row]').each(function (i) {
			$(this).find(':input[name]').each(function () {
				$(this).attr('name', $(this).attr('name').replace(/\]\[\d+\]\[/, '][' + i + ']['));
			});
		});
		return name;
	}
	$(document).on('click', '[data-add-pair]', function (e) {
		e.preventDefault();
		var box = $(this).closest('[data-pairs]');
		var i = box.find('[data-row]').length;
		var n = box.data('name');
		var row = $('<div class="cr-pair" data-row><span class="cr-row__drag">⋮⋮</span><div class="cr-pair__fields">' +
			'<input type="text" class="cr-input" name="' + n + '[' + i + '][' + box.data('a') + ']" placeholder="' + box.data('la') + '">' +
			'<textarea class="cr-input" rows="2" name="' + n + '[' + i + '][' + box.data('b') + ']" placeholder="' + box.data('lb') + '"></textarea>' +
			'</div><button type="button" class="cr-row__btn cr-danger" data-remove title="Sil">✕</button></div>');
		box.find('[data-rows]').append(row);
		row.find('input').trigger('focus');
	});
	$(document).on('click', '[data-pairs] [data-remove]', function (e) {
		e.preventDefault();
		var box = $(this).closest('[data-pairs]');
		$(this).closest('[data-row]').remove();
		reindexPairs(box);
	});

	/* ---------- Meta kutusu sekmeleri ---------- */
	$(document).on('click', '[data-mtab]', function () {
		var box = $(this).closest('.cr-metabox');
		var t = $(this).data('mtab');
		box.find('[data-mtab]').removeClass('is-active');
		$(this).addClass('is-active');
		box.find('[data-mpanel]').removeClass('is-active').filter('[data-mpanel="' + t + '"]').addClass('is-active');
	});

	/* ---------- SERP önizleme + canlı kontrol ---------- */
	var serp = $('[data-serp]');
	function editorData() {
		var d = { title: '', content: '', excerpt: '', slug: '' };
		try {
			if (window.wp && wp.data && wp.data.select('core/editor')) {
				var s = wp.data.select('core/editor');
				d.title = s.getEditedPostAttribute('title') || '';
				d.content = s.getEditedPostContent() || '';
				d.excerpt = s.getEditedPostAttribute('excerpt') || '';
				d.slug = s.getEditedPostAttribute('slug') || '';
				return d;
			}
		} catch (e) { /* klasik düzenleyici */ }
		d.title = $('#title').val() || '';
		d.content = $('#content').val() || '';
		d.excerpt = $('#excerpt').val() || '';
		d.slug = $('#post_name').val() || '';
		return d;
	}
	function lower(s) { return String(s || '').replace(/I/g, 'ı').replace(/İ/g, 'i').toLocaleLowerCase('tr-TR'); }
	function strip(h) { var t = document.createElement('div'); t.innerHTML = h; return (t.textContent || '').replace(/\s+/g, ' ').trim(); }
	function slugify(s) {
		var map = { 'ç': 'c', 'ğ': 'g', 'ı': 'i', 'ö': 'o', 'ş': 's', 'ü': 'u' };
		return lower(s).replace(/[çğıöşü]/g, function (c) { return map[c]; }).replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
	}
	function counter(key, len, min, max) {
		var el = $('[data-counter-for="' + key + '"]');
		el.text(len + ' karakter').removeClass('is-good is-bad').addClass(len >= min && len <= max ? 'is-good' : (len ? 'is-bad' : ''));
	}
	function updateSerp() {
		if (!serp.length) { return; }
		var d = editorData();
		var tIn = $('[name="cr_meta[_cr_seo_title]"]').val();
		var dIn = $('[name="cr_meta[_cr_seo_desc]"]').val();
		var short = $('[name="cr_meta[_cr_short_answer]"]').val();
		var title = tIn || (d.title + ' ' + (A.sep || '—') + ' ' + (A.site || ''));
		var desc = dIn || strip(d.excerpt) || short || strip(d.content).substring(0, 158);
		serp.find('[data-serp-title]').text(title);
		serp.find('[data-serp-desc]').text(desc.length > 160 ? desc.substring(0, 157) + '…' : desc);
		counter('_cr_seo_title', (tIn || title).length, 30, 65);
		counter('_cr_seo_desc', (dIn || desc).length, 110, 165);
		var words = short ? short.trim().split(/\s+/).length : 0;
		$('[data-counter-for="_cr_short_answer"]').text(words + ' kelime').removeClass('is-good is-bad').addClass(words >= 30 && words <= 70 ? 'is-good' : (words ? 'is-bad' : ''));

		var kw = lower($('[name="cr_meta[_cr_focus_kw]"]').val() || '').trim();
		var text = lower(strip(d.content));
		var wc = text ? text.split(/\s+/).length : 0;
		var checks = [];
		if (kw) {
			checks.push([lower(title).indexOf(kw) > -1, 'Anahtar kelime başlıkta']);
			checks.push([lower(desc).indexOf(kw) > -1, 'Anahtar kelime açıklamada']);
			checks.push([text.split(/\s+/).slice(0, 110).join(' ').indexOf(kw) > -1, 'Anahtar kelime ilk paragrafta']);
			checks.push([(d.slug || slugify(d.title)).indexOf(slugify(kw)) > -1, 'Anahtar kelime adreste']);
			var occ = text.split(kw).length - 1;
			var dens = wc ? (occ * kw.split(/\s+/).length / wc * 100) : 0;
			checks.push([dens >= 0.4 && dens <= 2.5, 'Anahtar kelime yoğunluğu %' + dens.toFixed(1) + ' (ideal %0,5–2,5)']);
		} else {
			checks.push([false, 'Odak anahtar kelime gir']);
		}
		checks.push([wc >= 600, 'İçerik uzunluğu: ' + wc + ' kelime']);
		checks.push([(d.content.match(/<h2/gi) || []).length >= 2, 'En az 2 ara başlık (H2)']);
		checks.push([!!short, 'Kısa cevap (AEO)']);
		checks.push([$('[name^="cr_meta[_cr_faq]"]').filter('input').filter(function () { return this.value; }).length >= 2, 'En az 2 SSS (AEO)']);
		checks.push([!!($('[name="cr_meta[_cr_sources]"]').val() || '').trim(), 'Kaynaklar (GEO)']);
		$('[data-live-checks]').html('<ul>' + checks.map(function (c) { return '<li class="' + (c[0] ? 'ok' : 'no') + '">' + c[1] + '</li>'; }).join('') + '</ul>');
	}
	if (serp.length) {
		$(document).on('input change', '.cr-metabox :input', updateSerp);
		$(document).on('input', '#title, #content, #excerpt', updateSerp);
		updateSerp();
		if (window.wp && wp.data && wp.data.subscribe) {
			var t;
			wp.data.subscribe(function () { clearTimeout(t); t = setTimeout(updateSerp, 800); });
		}
	}

	/* ---------- Kontrol paneli kalpleri ---------- */
	var hearts = document.querySelector('[data-hearts]');
	if (hearts && A.hearts && !(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches)) {
		var spawn = function () {
			var h = document.createElement('span');
			h.textContent = '♥';
			h.style.left = Math.random() * 100 + '%';
			h.style.fontSize = (12 + Math.random() * 22) + 'px';
			h.style.animationDuration = (7 + Math.random() * 7) + 's';
			hearts.appendChild(h);
			setTimeout(function () { h.remove(); }, 15000);
		};
		for (var i = 0; i < 8; i++) { setTimeout(spawn, i * 350); }
		setInterval(spawn, 900);
	}

	$(function () {
		initColors();
		initSortables();
		$('[data-repeater]').each(function () { reindex($(this)); });
	});
})(jQuery);
