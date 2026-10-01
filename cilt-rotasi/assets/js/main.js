/**
 * Cilt Rotası — ön yüz betikleri (bağımlılıksız).
 */
(function () {
	'use strict';

	var C = window.CR || {};
	var I = C.i18n || {};
	var doc = document;
	var root = doc.documentElement;
	var body = doc.body;
	var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	root.classList.add('cr-js');

	var $ = function (s, c) { return (c || doc).querySelector(s); };
	var $$ = function (s, c) { return Array.prototype.slice.call((c || doc).querySelectorAll(s)); };
	var esc = function (s) {
		return String(s == null ? '' : s).replace(/[&<>"']/g, function (m) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[m];
		});
	};
	var trLower = function (s) {
		return String(s || '').replace(/I/g, 'ı').replace(/İ/g, 'i').toLocaleLowerCase('tr-TR');
	};
	var store = {
		get: function (k, d) {
			try { var v = window.localStorage.getItem(k); return v ? JSON.parse(v) : d; } catch (e) { return d; }
		},
		set: function (k, v) {
			try { window.localStorage.setItem(k, JSON.stringify(v)); } catch (e) { /* gizli pencere */ }
		}
	};

	/* ---------- Toast ---------- */
	var toastEl = $('.cr-toast');
	var toastT;
	function toast(msg) {
		if (!toastEl) { return; }
		toastEl.textContent = msg;
		toastEl.hidden = false;
		clearTimeout(toastT);
		toastT = setTimeout(function () { toastEl.hidden = true; }, 2400);
	}

	/* ---------- Kaydırma kilidi ---------- */
	var locks = 0;
	function lock(on) {
		locks = Math.max(0, locks + (on ? 1 : -1));
		body.classList.toggle('cr-lock', locks > 0);
	}

	/* ---------- Header ve alt menü ---------- */
	var header = $('#cr-header');
	var bottom = $('.cr-bottom-nav');
	var lastY = window.scrollY;
	var progress = $('[data-progress]');
	var article = $('.cr-article__main') || $('.cr-ing__body');
	var ticking = false;

	function onScroll() {
		var y = window.scrollY;
		if (header) { header.classList.toggle('is-scrolled', y > 40); }
		if (bottom) {
			if (y > lastY + 6 && y > 240) { bottom.classList.add('is-hidden'); }
			else if (y < lastY - 6 || y < 240) { bottom.classList.remove('is-hidden'); }
		}
		if (progress && article) {
			var r = article.getBoundingClientRect();
			var total = r.height - window.innerHeight * 0.6;
			var p = Math.min(1, Math.max(0, -r.top / (total > 0 ? total : 1)));
			progress.style.width = (p * 100).toFixed(2) + '%';
		}
		parallax();
		lastY = y;
		ticking = false;
	}
	window.addEventListener('scroll', function () {
		if (!ticking) { window.requestAnimationFrame(onScroll); ticking = true; }
	}, { passive: true });

	/* ---------- Parallax (hafif) ---------- */
	var pEls = (reduce || !body.classList.contains('cr-anim')) ? [] : $$('[data-parallax]');
	function parallax() {
		if (!pEls.length || window.innerWidth < 900) { return; }
		pEls.forEach(function (el) {
			var img = el.querySelector('img');
			if (!img) { return; }
			var r = el.getBoundingClientRect();
			if (r.bottom < 0 || r.top > window.innerHeight) { return; }
			var shift = (r.top + r.height / 2 - window.innerHeight / 2) * -0.06;
			img.style.transform = 'translate3d(0,' + shift.toFixed(1) + 'px,0)';
		});
	}
	onScroll();

	/* ---------- Menü alt panelleri ---------- */
	$$('.cr-nav__toggle').forEach(function (btn) {
		btn.addEventListener('click', function (e) {
			e.stopPropagation();
			var li = btn.closest('.cr-nav__item');
			var open = !li.classList.contains('is-open');
			$$('.cr-nav__item.is-open').forEach(function (o) {
				o.classList.remove('is-open');
				var b = o.querySelector('.cr-nav__toggle');
				if (b) { b.setAttribute('aria-expanded', 'false'); }
			});
			li.classList.toggle('is-open', open);
			btn.setAttribute('aria-expanded', open ? 'true' : 'false');
		});
	});
	doc.addEventListener('click', function (e) {
		if (!e.target.closest('.cr-nav__item')) {
			$$('.cr-nav__item.is-open').forEach(function (o) { o.classList.remove('is-open'); });
		}
	});

	/* ---------- Diyalog yardımcıları (çekmece, arama) ---------- */
	function makeDialog(el, openers, onOpen) {
		if (!el) { return null; }
		var lastFocus = null;
		var api = {
			open: function () {
				if (!el.hidden) { return; }
				lastFocus = doc.activeElement;
				el.hidden = false;
				lock(true);
				openers.forEach(function (b) { b.setAttribute('aria-expanded', 'true'); });
				if (onOpen) { onOpen(); }
			},
			close: function () {
				if (el.hidden) { return; }
				el.hidden = true;
				lock(false);
				openers.forEach(function (b) { b.setAttribute('aria-expanded', 'false'); });
				if (lastFocus && lastFocus.focus) { lastFocus.focus(); }
			},
			isOpen: function () { return !el.hidden; }
		};
		openers.forEach(function (b) {
			b.addEventListener('click', function (e) { e.preventDefault(); api.open(); });
		});
		el.addEventListener('keydown', function (e) {
			if (e.key === 'Escape') { api.close(); }
			if (e.key === 'Tab') {
				var f = $$('a[href],button:not([disabled]),input,select,textarea,[tabindex]:not([tabindex="-1"])', el).filter(function (x) { return x.offsetParent !== null; });
				if (!f.length) { return; }
				if (e.shiftKey && doc.activeElement === f[0]) { e.preventDefault(); f[f.length - 1].focus(); }
				else if (!e.shiftKey && doc.activeElement === f[f.length - 1]) { e.preventDefault(); f[0].focus(); }
			}
		});
		return api;
	}

	var drawerEl = $('#cr-drawer');
	var drawer = makeDialog(drawerEl, $$('[data-drawer-open]'), function () {
		var first = drawerEl.querySelector('.cr-drawer__nav a');
		if (first) { first.focus(); }
	});
	if (drawer) {
		$$('[data-drawer-close]', drawerEl).forEach(function (b) { b.addEventListener('click', drawer.close); });
		$$('a', drawerEl).forEach(function (a) { a.addEventListener('click', function () { drawer.close(); }); });
	}

	/* ---------- Canlı arama ---------- */
	var searchEl = $('#cr-search');
	var sInput = $('#cr-search-input');
	var sResults = $('[data-search-results]');
	var sEmpty = $('[data-search-empty]');
	var sType = '';
	var sTimer, sLogTimer, sCtrl;
	var sActive = -1;
	var cache = {};

	var search = makeDialog(searchEl, $$('[data-search-open]'), function () {
		if (drawer) { drawer.close(); }
		renderRecent();
		setTimeout(function () { sInput && sInput.focus(); }, 30);
	});

	function highlight(text, q) {
		var t = esc(text);
		if (!q) { return t; }
		var words = q.trim().split(/\s+/).filter(function (w) { return w.length > 1; }).map(function (w) { return w.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); });
		if (!words.length) { return t; }
		try { return t.replace(new RegExp('(' + words.join('|') + ')', 'gi'), '<mark>$1</mark>'); } catch (e) { return t; }
	}

	function renderRecent() {
		var wrap = $('.cr-search__recent-wrap');
		var list = $('[data-search-recent]');
		if (!wrap || !list) { return; }
		var rec = store.get('cr_recent', []);
		wrap.hidden = !rec.length;
		list.innerHTML = rec.map(function (r) { return '<button type="button" class="cr-chip" data-search-fill="' + esc(r) + '">' + esc(r) + '</button>'; }).join('');
	}
	function pushRecent(q) {
		q = q.trim();
		if (q.length < 2) { return; }
		var rec = store.get('cr_recent', []).filter(function (r) { return r !== q; });
		rec.unshift(q);
		store.set('cr_recent', rec.slice(0, 6));
	}

	function runSearch(q, log) {
		q = (q || '').trim();
		if (q.length < 2) {
			sResults.innerHTML = '';
			sEmpty.hidden = false;
			return;
		}
		sEmpty.hidden = true;
		var key = sType + '|' + q;
		if (cache[key] && !log) { paint(cache[key], q); return; }
		if (sCtrl && sCtrl.abort) { sCtrl.abort(); }
		sCtrl = window.AbortController ? new AbortController() : null;
		if (!cache[key]) { sResults.innerHTML = '<p class="cr-search__msg">' + esc(I.searching) + '</p>'; }
		var url = C.rest + 'search?q=' + encodeURIComponent(q) + (sType ? '&type=' + sType : '') + (log ? '&log=1' : '');
		fetch(url, sCtrl ? { signal: sCtrl.signal } : {}).then(function (r) { return r.json(); }).then(function (d) {
			cache[key] = d;
			paint(d, q);
		}).catch(function (e) {
			if (e && e.name === 'AbortError') { return; }
			sResults.innerHTML = '<p class="cr-search__msg">' + esc(I.error) + '</p>';
		});
	}

	function paint(d, q) {
		sActive = -1;
		if (!d.groups || !d.groups.length) {
			sResults.innerHTML = '<p class="cr-search__msg">' + esc(I.noResults) + '</p>';
			return;
		}
		var html = d.groups.map(function (g) {
			return '<div class="cr-search__group"><p class="cr-search__label">' + esc(g.label) + '</p>' + g.items.map(function (it) {
				return '<a class="cr-search__item" href="' + esc(it.url) + '" role="option"><span class="cr-search__thumb">' +
					(it.img ? '<img src="' + esc(it.img) + '" alt="" loading="lazy" width="52" height="52">' : '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M5 19c0-8 5-13 15-14-1 10-6 15-14 15"/></svg>') +
					'</span><span><strong>' + highlight(it.title, q) + '</strong>' + (it.meta ? '<small>' + esc(it.meta) + '</small>' : '') + '</span></a>';
			}).join('') + '</div>';
		}).join('');
		html += '<a class="cr-search__all" href="' + esc(d.all + (sType ? '&tur=' + sType : '')) + '">Tüm sonuçları gör <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a>';
		sResults.innerHTML = html;
	}

	if (search && sInput) {
		$$('[data-search-close]', searchEl).forEach(function (b) { b.addEventListener('click', search.close); });
		sInput.addEventListener('input', function () {
			clearTimeout(sTimer);
			clearTimeout(sLogTimer);
			var q = sInput.value;
			sTimer = setTimeout(function () { runSearch(q, false); }, 200);
			sLogTimer = setTimeout(function () { if (q.trim().length > 2) { runSearch(q, true); pushRecent(q); } }, 1600);
		});
		sInput.addEventListener('keydown', function (e) {
			var items = $$('.cr-search__item', sResults);
			if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
				if (!items.length) { return; }
				e.preventDefault();
				sActive = e.key === 'ArrowDown' ? (sActive + 1) % items.length : (sActive <= 0 ? items.length - 1 : sActive - 1);
				items.forEach(function (it, i) { it.classList.toggle('is-active', i === sActive); });
				items[sActive].scrollIntoView({ block: 'nearest' });
			} else if (e.key === 'Enter' && sActive > -1 && items[sActive]) {
				e.preventDefault();
				pushRecent(sInput.value);
				window.location.href = items[sActive].href;
			} else if (e.key === 'Enter') {
				pushRecent(sInput.value);
			}
		});
		searchEl.addEventListener('click', function (e) {
			var fill = e.target.closest('[data-search-fill]');
			if (fill) {
				sInput.value = fill.getAttribute('data-search-fill');
				sInput.focus();
				runSearch(sInput.value, true);
			}
			var tab = e.target.closest('[data-search-type]');
			if (tab) {
				sType = tab.getAttribute('data-search-type');
				$$('[data-search-type]', searchEl).forEach(function (t) {
					t.classList.toggle('is-active', t === tab);
					t.setAttribute('aria-selected', t === tab ? 'true' : 'false');
				});
				runSearch(sInput.value, false);
				sInput.focus();
			}
			if (e.target.closest('.cr-search__item')) { pushRecent(sInput.value); }
		});
		doc.addEventListener('keydown', function (e) {
			var tag = (e.target.tagName || '').toLowerCase();
			var typing = tag === 'input' || tag === 'textarea' || e.target.isContentEditable;
			if ((e.key === '/' && !typing) || ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k')) {
				e.preventDefault();
				search.open();
			}
		});
	}

	/* ---------- Kaydedilenler ---------- */
	var SKEY = 'cr_saved';
	function savedList() { return store.get(SKEY, []); }
	function isSaved(id) { return savedList().some(function (s) { return String(s.id) === String(id); }); }
	function syncSaved() {
		var list = savedList();
		$$('[data-saved-count]').forEach(function (c) {
			c.textContent = list.length;
			c.hidden = !list.length;
		});
		$$('[data-save]').forEach(function (b) {
			var d;
			try { d = JSON.parse(b.getAttribute('data-save')); } catch (e) { return; }
			var on = isSaved(d.id);
			b.setAttribute('aria-pressed', on ? 'true' : 'false');
			var lbl = b.querySelector('.cr-save__label');
			if (lbl) { lbl.textContent = on ? 'Kaydedildi' : 'Kaydet'; }
		});
	}
	doc.addEventListener('click', function (e) {
		var b = e.target.closest('[data-save]');
		if (!b) { return; }
		e.preventDefault();
		e.stopPropagation();
		var d;
		try { d = JSON.parse(b.getAttribute('data-save')); } catch (err) { return; }
		var list = savedList();
		if (isSaved(d.id)) {
			list = list.filter(function (s) { return String(s.id) !== String(d.id); });
			toast(I.removed);
		} else {
			d.t = Date.now();
			list.unshift(d);
			toast(I.saved);
		}
		store.set(SKEY, list.slice(0, 200));
		b.classList.remove('is-pop');
		void b.offsetWidth;
		b.classList.add('is-pop');
		syncSaved();
		renderSaved();
	});
	window.addEventListener('storage', function (e) { if (e.key === SKEY) { syncSaved(); renderSaved(); } });

	function renderSaved() {
		var grid = $('[data-saved-grid]');
		if (!grid) { return; }
		var list = savedList();
		var empty = $('[data-saved-empty]');
		var bar = $('[data-saved-toolbar]');
		var total = $('[data-saved-total]');
		empty.hidden = !!list.length;
		bar.hidden = !list.length;
		if (total) { total.textContent = list.length + ' içerik kaydedildi'; }
		grid.innerHTML = list.map(function (s) {
			var data = esc(JSON.stringify({ id: s.id, url: s.url, title: s.title, img: s.img, cat: s.cat }));
			return '<article class="cr-card cr-card--plain"><a class="cr-card__media" href="' + esc(s.url) + '" tabindex="-1" aria-hidden="true">' +
				(s.img ? '<img class="cr-card__img" src="' + esc(s.img) + '" alt="" loading="lazy">' : '<span class="cr-img-empty"></span>') +
				'</a><div class="cr-card__body"><div class="cr-card__meta">' + (s.cat ? '<span class="cr-badge">' + esc(s.cat) + '</span>' : '') + '</div>' +
				'<h2 class="cr-card__title"><a href="' + esc(s.url) + '">' + esc(s.title) + '</a></h2></div>' +
				'<button type="button" class="cr-save cr-card__save" data-save="' + data + '" aria-pressed="true" aria-label="Kaydedilenlerden çıkar">' +
				'<svg class="cr-icon cr-save__off" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M6 4h12v17l-6-4-6 4z"/></svg>' +
				'<svg class="cr-icon cr-save__on" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M6 4h12v17l-6-4-6 4z" fill="currentColor"/></svg></button></article>';
		}).join('');
	}
	var clearBtn = $('[data-saved-clear]');
	if (clearBtn) {
		clearBtn.addEventListener('click', function () {
			if (window.confirm('Kaydedilen tüm içerikler silinsin mi?')) {
				store.set(SKEY, []);
				syncSaved();
				renderSaved();
			}
		});
	}
	syncSaved();
	renderSaved();

	/* ---------- Paylaş / kopyala ---------- */
	function copy(text) {
		if (navigator.clipboard && window.isSecureContext) {
			return navigator.clipboard.writeText(text).then(function () { toast(I.copied); });
		}
		var ta = doc.createElement('textarea');
		ta.value = text;
		ta.style.position = 'fixed';
		ta.style.opacity = '0';
		body.appendChild(ta);
		ta.select();
		try { doc.execCommand('copy'); toast(I.copied); } catch (e) { /* yok */ }
		body.removeChild(ta);
	}
	doc.addEventListener('click', function (e) {
		var s = e.target.closest('[data-share]');
		if (s) {
			e.preventDefault();
			var url = s.getAttribute('data-url') || window.location.href;
			if (navigator.share) {
				navigator.share({ title: s.getAttribute('data-title') || doc.title, url: url }).catch(function () {});
			} else {
				copy(url);
			}
		}
		var c = e.target.closest('[data-copy]');
		if (c) { e.preventDefault(); copy(c.getAttribute('data-copy')); }
	});

	/* ---------- Görünür olunca belirme ---------- */
	if ('IntersectionObserver' in window && body.classList.contains('cr-anim') && !reduce) {
		var io = new IntersectionObserver(function (entries) {
			entries.forEach(function (en) {
				if (en.isIntersecting) {
					en.target.classList.add('is-in');
					io.unobserve(en.target);
				}
			});
		}, { rootMargin: '0px 0px -8% 0px', threshold: 0.06 });
		$$('.cr-reveal').forEach(function (el) {
			var sib = el.parentElement ? $$(':scope > .cr-reveal', el.parentElement) : [];
			var idx = sib.indexOf(el);
			if (idx > 0) { el.style.setProperty('--d', Math.min(idx, 6) * 0.07 + 's'); }
			io.observe(el);
		});
	} else {
		$$('.cr-reveal').forEach(function (el) { el.classList.add('is-in'); });
	}

	/* ---------- Yatay şeritler ---------- */
	$$('[data-rail]').forEach(function (rail) {
		var sec = rail.closest('section');
		var prev = sec && sec.querySelector('[data-rail-prev]');
		var next = sec && sec.querySelector('[data-rail-next]');
		function step() {
			var it = rail.querySelector('.cr-rail__item');
			return it ? it.getBoundingClientRect().width + 20 : 300;
		}
		function upd() {
			if (prev) { prev.disabled = rail.scrollLeft < 8; }
			if (next) { next.disabled = rail.scrollLeft + rail.clientWidth > rail.scrollWidth - 8; }
		}
		if (prev) { prev.addEventListener('click', function () { rail.scrollBy({ left: -step() * 2, behavior: reduce ? 'auto' : 'smooth' }); }); }
		if (next) { next.addEventListener('click', function () { rail.scrollBy({ left: step() * 2, behavior: reduce ? 'auto' : 'smooth' }); }); }
		rail.addEventListener('scroll', upd, { passive: true });
		upd();
	});

	/* ---------- Rehber filtreleri ---------- */
	var gWrap = $('[data-guides]');
	var gFilters = $('[data-guides-filters]');
	var gCache = {};
	if (gWrap && gFilters) {
		gCache[''] = gWrap.innerHTML;
		gFilters.addEventListener('click', function (e) {
			var b = e.target.closest('[data-cat]');
			if (!b) { return; }
			var cat = b.getAttribute('data-cat');
			$$('[data-cat]', gFilters).forEach(function (x) {
				x.classList.toggle('is-active', x === b);
				x.setAttribute('aria-selected', x === b ? 'true' : 'false');
			});
			if (gCache[cat] !== undefined) {
				gWrap.innerHTML = gCache[cat];
				syncSaved();
				return;
			}
			gWrap.classList.add('is-loading');
			fetch(C.rest + 'guides?cat=' + encodeURIComponent(cat)).then(function (r) { return r.json(); }).then(function (d) {
				gCache[cat] = d.html || '';
				gWrap.innerHTML = gCache[cat];
				syncSaved();
			}).catch(function () { toast(I.error); }).then(function () { gWrap.classList.remove('is-loading'); });
		});
	}

	/* ---------- Katalog filtresi ---------- */
	var cf = $('[data-catalog-filters]');
	var cg = $('[data-catalog]');
	if (cf && cg) {
		cf.addEventListener('click', function (e) {
			var b = e.target.closest('[data-type]');
			if (!b) { return; }
			var t = b.getAttribute('data-type');
			$$('[data-type]', cf).forEach(function (x) {
				x.classList.toggle('is-active', x === b);
				x.setAttribute('aria-selected', x === b ? 'true' : 'false');
			});
			var vis = 0;
			$$('.cr-product', cg).forEach(function (p) {
				var ok = !t || p.getAttribute('data-type') === t;
				p.hidden = !ok;
				if (ok) { vis++; }
			});
			var em = $('[data-catalog-empty]');
			if (em) { em.hidden = vis > 0; }
		});
	}

	/* ---------- Ana sayfa sözlük araması ---------- */
	$$('[data-ing-search]').forEach(function (form) {
		var input = form.querySelector('input[type="search"]');
		var box = form.querySelector('[data-ing-results]');
		var t;
		var act = -1;
		if (!input || !box) { return; }
		input.addEventListener('input', function () {
			clearTimeout(t);
			var q = input.value.trim();
			if (q.length < 2) { box.hidden = true; return; }
			t = setTimeout(function () {
				fetch(C.rest + 'search?type=icerik&q=' + encodeURIComponent(q)).then(function (r) { return r.json(); }).then(function (d) {
					var items = (d.groups && d.groups[0]) ? d.groups[0].items : [];
					act = -1;
					box.innerHTML = items.length ? items.map(function (it) {
						return '<a href="' + esc(it.url) + '"><strong>' + highlight(it.title, q) + '</strong>' + (it.meta ? '<small>' + esc(it.meta) + '</small>' : '') + '</a>';
					}).join('') : '<p class="cr-search__msg">' + esc(I.noResults) + '</p>';
					box.hidden = false;
				});
			}, 180);
		});
		input.addEventListener('keydown', function (e) {
			var links = $$('a', box);
			if ((e.key === 'ArrowDown' || e.key === 'ArrowUp') && links.length && !box.hidden) {
				e.preventDefault();
				act = e.key === 'ArrowDown' ? (act + 1) % links.length : (act <= 0 ? links.length - 1 : act - 1);
				links.forEach(function (l, i) { l.classList.toggle('is-active', i === act); });
			} else if (e.key === 'Enter' && act > -1 && links[act]) {
				e.preventDefault();
				window.location.href = links[act].href;
			} else if (e.key === 'Escape') {
				box.hidden = true;
			}
		});
		doc.addEventListener('click', function (e) { if (!form.contains(e.target)) { box.hidden = true; } });
	});

	/* ---------- Sözlük arşivi filtresi ---------- */
	var gl = $('[data-glossary-filter]');
	var glGroup = '';
	function glApply() {
		var q = gl ? trLower(gl.value.trim()) : '';
		var any = false;
		$$('[data-letter]').forEach(function (sec) {
			var vis = 0;
			$$('.cr-ing-card, .cr-entry', sec).forEach(function (c) {
				var ok = (!q || c.getAttribute('data-search').indexOf(q) > -1) && (!glGroup || (' ' + c.getAttribute('data-groups') + ' ').indexOf(' ' + glGroup + ' ') > -1);
				c.hidden = !ok;
				if (ok) { vis++; }
			});
			sec.hidden = !vis;
			if (vis) { any = true; }
		});
		var em = $('[data-glossary-empty]');
		if (em) { em.hidden = any; }
	}
	if (gl) { gl.addEventListener('input', glApply); }
	var glg = $('[data-glossary-groups]');
	if (glg) {
		glg.addEventListener('click', function (e) {
			var b = e.target.closest('[data-group]');
			if (!b) { return; }
			glGroup = b.getAttribute('data-group');
			$$('[data-group]', glg).forEach(function (x) { x.classList.toggle('is-active', x === b); });
			glApply();
		});
	}

	/* ---------- Arşiv anlık filtre ---------- */
	var lf = $('[data-live-filter]');
	var lg = $('[data-filter-grid]');
	if (lf && lg) {
		lf.addEventListener('input', function () {
			var q = trLower(lf.value.trim());
			var vis = 0;
			$$('.cr-card', lg).forEach(function (c) {
				var t = c.querySelector('.cr-card__title');
				var ok = !q || trLower(t ? t.textContent : '').indexOf(q) > -1;
				c.classList.toggle('is-filtered', !ok);
				if (ok) { vis++; }
			});
			var em = $('[data-filter-empty]');
			if (em) { em.hidden = vis > 0; }
		});
	}

	/* ---------- İçindekiler etkin başlık ---------- */
	var tocLinks = $$('[data-toc] a');
	if (tocLinks.length && 'IntersectionObserver' in window) {
		var map = {};
		tocLinks.forEach(function (a) {
			var id = decodeURIComponent(a.getAttribute('href').slice(1));
			(map[id] = map[id] || []).push(a);
		});
		var heads = Object.keys(map).map(function (id) { return doc.getElementById(id); }).filter(Boolean);
		var tio = new IntersectionObserver(function (entries) {
			entries.forEach(function (en) {
				if (en.isIntersecting) {
					tocLinks.forEach(function (a) { a.classList.remove('is-active'); });
					(map[en.target.id] || []).forEach(function (a) { a.classList.add('is-active'); });
				}
			});
		}, { rootMargin: '-20% 0px -70% 0px' });
		heads.forEach(function (h) { tio.observe(h); });
		$$('.cr-toc-mobile a').forEach(function (a) {
			a.addEventListener('click', function () { var d = a.closest('details'); if (d) { d.open = false; } });
		});
	}

	/* ---------- Bülten ---------- */
	$$('[data-news]').forEach(function (form) {
		form.addEventListener('submit', function (e) {
			e.preventDefault();
			var msg = form.querySelector('.cr-news-form__msg');
			var email = form.querySelector('input[type="email"]');
			var btn = form.querySelector('button[type="submit"]');
			msg.classList.remove('is-error');
			if (!email.value || !email.checkValidity()) {
				msg.textContent = 'Geçerli bir e-posta adresi yaz.';
				msg.classList.add('is-error');
				email.focus();
				return;
			}
			var fd = new FormData(form);
			fd.append('action', 'cr_subscribe');
			fd.append('nonce', C.newsNonce);
			btn.disabled = true;
			fetch(C.ajax, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (d) {
				msg.textContent = (d.data && d.data.message) || (d.success ? 'Teşekkürler!' : I.error);
				msg.classList.toggle('is-error', !d.success);
				if (d.success) { form.reset(); }
			}).catch(function () {
				msg.textContent = I.error;
				msg.classList.add('is-error');
			}).then(function () { btn.disabled = false; });
		});
	});

	/* ---------- Cilt testi ---------- */
	var quiz = $('[data-quiz]');
	if (quiz && C.quiz && C.quiz.questions && C.quiz.questions.length) {
		var Q = C.quiz;
		var step = 0;
		var picks = [];
		var labels = { kuru: 'Kuru', yagli: 'Yağlı', karma: 'Karma', normal: 'Normal', hassas: 'Hassas' };
		var el = function (k) { return quiz.querySelector('[data-quiz-' + k + ']'); };
		var show = function (name) {
			$$('[data-quiz-step]', quiz).forEach(function (s) { s.hidden = s.getAttribute('data-quiz-step') !== name; });
		};
		var renderQ = function () {
			var q = Q.questions[step];
			el('progress').style.width = (step / Q.questions.length * 100) + '%';
			el('count').textContent = 'Soru ' + (step + 1) + ' / ' + Q.questions.length;
			el('question').textContent = q.q;
			el('hint').textContent = q.hint || '';
			el('answers').innerHTML = q.answers.map(function (a, i) {
				return '<button type="button" class="cr-quiz__answer' + (picks[step] === i ? ' is-picked' : '') + '" data-i="' + i + '" style="animation-delay:' + (i * 0.05) + 's"><span>' + String.fromCharCode(65 + i) + '</span>' + esc(a.text) + '</button>';
			}).join('');
			el('back').style.visibility = step ? 'visible' : 'hidden';
			var first = el('answers').querySelector('button');
			if (first) { first.focus({ preventScroll: true }); }
		};
		var result = function () {
			var score = { kuru: 0, yagli: 0, karma: 0, normal: 0, hassas: 0 };
			picks.forEach(function (p, i) {
				var t = Q.questions[i].answers[p].type;
				if (score[t] !== undefined) { score[t]++; }
			});
			var top = Object.keys(score).sort(function (a, b) { return score[b] - score[a]; })[0];
			// Hassasiyet belirgin ise öncelik ver.
			if (score.hassas >= 2 && score.hassas >= score[top] - 1) { top = 'hassas'; }
			var r = Q.results[top] || {};
			el('rtitle').textContent = r.title || labels[top];
			el('rtext').textContent = r.text || '';
			el('rurl').href = r.url || C.home;
			el('disc').textContent = Q.disclaimer || '';
			var n = picks.length || 1;
			el('meter').innerHTML = Object.keys(score).sort(function (a, b) { return score[b] - score[a]; }).map(function (k) {
				var pct = Math.round(score[k] / n * 100);
				return '<div class="cr-quiz__row' + (k === top ? ' is-top' : '') + '"><span>' + labels[k] + '</span><i><b style="width:0" data-w="' + pct + '"></b></i><em>%' + pct + '</em></div>';
			}).join('');
			el('progress').style.width = '100%';
			show('result');
			store.set('cr_quiz_result', { type: top, t: Date.now() });
			setTimeout(function () {
				$$('[data-w]', quiz).forEach(function (b) { b.style.width = b.getAttribute('data-w') + '%'; });
			}, 60);
			var res = quiz.querySelector('[data-quiz-step="result"]');
			res.focus({ preventScroll: true });
			res.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'center' });
			if (window.gtag) { window.gtag('event', 'skin_quiz_complete', { skin_type: top }); }
		};
		el('start').addEventListener('click', function () { step = 0; picks = []; show('run'); renderQ(); });
		el('answers').addEventListener('click', function (e) {
			var b = e.target.closest('[data-i]');
			if (!b) { return; }
			picks[step] = parseInt(b.getAttribute('data-i'), 10);
			b.classList.add('is-picked');
			setTimeout(function () {
				if (step < Q.questions.length - 1) { step++; renderQ(); } else { result(); }
			}, 220);
		});
		el('back').addEventListener('click', function () { if (step > 0) { step--; renderQ(); } });
		el('restart').addEventListener('click', function () { step = 0; picks = []; show('run'); renderQ(); });
	}

	/* ---------- Okunma sayacı (etkileşimden sonra) ---------- */
	if (C.track && C.postId) {
		setTimeout(function () {
			if (doc.visibilityState === 'hidden') { return; }
			var k = 'cr_v_' + C.postId;
			try { if (window.sessionStorage.getItem(k)) { return; } window.sessionStorage.setItem(k, '1'); } catch (e) { /* yok */ }
			fetch(C.rest + 'view/' + C.postId, { method: 'POST', keepalive: true }).catch(function () {});
		}, 4000);
	}

	/* ---------- GA4 (gecikmeli) ---------- */
	if (C.ga) {
		var loaded = false;
		var loadGA = function () {
			if (loaded) { return; }
			loaded = true;
			var s = doc.createElement('script');
			s.async = true;
			s.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(C.ga);
			doc.head.appendChild(s);
			window.dataLayer = window.dataLayer || [];
			window.gtag = function () { window.dataLayer.push(arguments); };
			window.gtag('js', new Date());
			window.gtag('config', C.ga);
		};
		['scroll', 'pointerdown', 'keydown', 'touchstart'].forEach(function (ev) { window.addEventListener(ev, loadGA, { once: true, passive: true }); });
		setTimeout(loadGA, 4500);
	}
})();
