/* Kurumsal Konaklama — arayüz bileşenleri (bağımlılıksız, aşamalı geliştirme).
   JavaScript kapalıyken tüm formlar yerel alanlarla çalışır. */
(function () {
  'use strict';
  document.documentElement.classList.add('js');

  var MONTHS = ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];
  var MONTHS_S = ['Oca', 'Şub', 'Mar', 'Nis', 'May', 'Haz', 'Tem', 'Ağu', 'Eyl', 'Eki', 'Kas', 'Ara'];
  var DOW = ['Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz'];
  var DOW_LONG = ['Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi'];
  var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
  var mqMobile = window.matchMedia('(max-width: 719px)');

  function $(sel, root) { return (root || document).querySelector(sel); }
  function $$(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }
  function pad(n) { return (n < 10 ? '0' : '') + n; }
  function iso(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()); }
  function parseIso(s) { if (!s || !/^\d{4}-\d{2}-\d{2}$/.test(s)) return null; var p = s.split('-'); return new Date(+p[0], +p[1] - 1, +p[2]); }
  function addDays(d, n) { var x = new Date(d.getTime()); x.setDate(x.getDate() + n); return x; }
  function diffDays(a, b) { return Math.round((Date.UTC(b.getFullYear(), b.getMonth(), b.getDate()) - Date.UTC(a.getFullYear(), a.getMonth(), a.getDate())) / 86400000); }
  function fmt(d) { return d.getDate() + ' ' + MONTHS_S[d.getMonth()]; }
  function el(tag, attrs, html) {
    var e = document.createElement(tag);
    if (attrs) Object.keys(attrs).forEach(function (k) { if (attrs[k] !== null && attrs[k] !== undefined) e.setAttribute(k, attrs[k]); });
    if (html !== undefined) e.innerHTML = html;
    return e;
  }
  var openPopover = null;
  function closePopover() {
    if (!openPopover) return;
    openPopover.panel.classList.remove('is-open');
    openPopover.trigger.setAttribute('aria-expanded', 'false');
    if (openPopover.backdrop) openPopover.backdrop.remove();
    var t = openPopover.trigger;
    openPopover = null;
    t.focus();
  }
  function showPopover(trigger, panel, onClose) {
    if (openPopover) closePopover();
    panel.classList.add('is-open');
    trigger.setAttribute('aria-expanded', 'true');
    var backdrop = null;
    if (mqMobile.matches) {
      backdrop = el('div', { 'class': 'popover-backdrop' });
      backdrop.addEventListener('click', function () { if (onClose) onClose(); closePopover(); });
      // Panel ile aynı yığın bağlamında olmalı (aksi halde arka plan paneli örter)
      panel.parentNode.insertBefore(backdrop, panel);
    }
    panel.style.left = '';
    if (!mqMobile.matches) {
      var r = panel.getBoundingClientRect(), vw = document.documentElement.clientWidth;
      if (r.right > vw - 8) panel.style.left = (panel.offsetLeft - (r.right - vw + 8)) + 'px';
      r = panel.getBoundingClientRect();
      if (r.left < 8) panel.style.left = (panel.offsetLeft + (8 - r.left)) + 'px';
    }
    openPopover = { trigger: trigger, panel: panel, backdrop: backdrop, onClose: onClose };
    var f = panel.querySelector('button, [tabindex="0"]');
    if (f) f.focus();
  }
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      if (openPopover) { if (openPopover.onClose) openPopover.onClose(); closePopover(); }
      $$('.dropdown.is-open').forEach(function (d) { d.classList.remove('is-open'); });
    }
  });
  document.addEventListener('click', function (e) {
    if (openPopover && !openPopover.panel.contains(e.target) && !openPopover.trigger.contains(e.target) && !mqMobile.matches) {
      if (openPopover.onClose) openPopover.onClose();
      closePopover();
    }
  });

  /* ---------- Mobil menü, açılır menüler, yönetim kenar çubuğu ---------- */
  $$('[data-menu-toggle]').forEach(function (btn) {
    var panel = document.getElementById(btn.getAttribute('aria-controls'));
    if (!panel) return;
    btn.addEventListener('click', function () {
      var open = btn.getAttribute('aria-expanded') === 'true';
      btn.setAttribute('aria-expanded', open ? 'false' : 'true');
      btn.setAttribute('aria-label', open ? 'Menüyü aç' : 'Menüyü kapat');
      panel.classList.toggle('is-open', !open);
      btn.innerHTML = open ? btn.getAttribute('data-icon-open') : btn.getAttribute('data-icon-close');
      document.body.style.overflow = open ? '' : 'hidden';
    });
    panel.addEventListener('click', function (e) { if (e.target === panel) btn.click(); });
  });
  $$('[data-dropdown]').forEach(function (btn) {
    var menu = document.getElementById(btn.getAttribute('aria-controls'));
    if (!menu) return;
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      var open = menu.classList.toggle('is-open');
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    document.addEventListener('click', function (e) {
      if (!menu.contains(e.target) && e.target !== btn) { menu.classList.remove('is-open'); btn.setAttribute('aria-expanded', 'false'); }
    });
  });
  $$('.submenu-toggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var sub = document.getElementById(btn.getAttribute('aria-controls'));
      var open = btn.getAttribute('aria-expanded') === 'true';
      btn.setAttribute('aria-expanded', open ? 'false' : 'true');
      if (sub) sub.classList.toggle('is-open', !open);
    });
  });
  $$('[data-sidebar-toggle]').forEach(function (btn) {
    var sb = $('.admin-sidebar'), bd = $('.admin-backdrop');
    function set(open) { sb.classList.toggle('is-open', open); bd.classList.toggle('is-open', open); btn.setAttribute('aria-expanded', open ? 'true' : 'false'); }
    btn.addEventListener('click', function () { set(!sb.classList.contains('is-open')); });
    if (bd) bd.addEventListener('click', function () { set(false); });
  });

  /* ---------- Onay soran formlar ---------- */
  $$('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) { if (!window.confirm(f.getAttribute('data-confirm'))) e.preventDefault(); });
  });
  /* Tekrarlanan gönderim koruması */
  $$('form[method="post"], form[method="POST"]').forEach(function (f) {
    f.addEventListener('submit', function () {
      if (f.hasAttribute('data-no-lock')) return;
      setTimeout(function () {
        $$('button[type="submit"], button:not([type])', f).forEach(function (b) { b.disabled = true; b.setAttribute('aria-busy', 'true'); });
      }, 0);
    });
  });

  /* ---------- Tarih aralığı seçici ---------- */
  $$('[data-daterange]').forEach(function (root) {
    var inIn = $('input[data-dr-in]', root), inOut = $('input[data-dr-out]', root);
    var trigger = $('[data-dr-trigger]', root), fallback = $('.date-fallback', root);
    if (!inIn || !inOut || !trigger) return;
    var minDate = parseIso(root.getAttribute('data-min')) || new Date();
    minDate.setHours(0, 0, 0, 0);
    var maxDate = parseIso(root.getAttribute('data-max')) || addDays(minDate, 540);
    var maxNights = parseInt(root.getAttribute('data-max-nights') || '30', 10);
    var start = parseIso(inIn.value), end = parseIso(inOut.value);
    var view = start ? new Date(start.getFullYear(), start.getMonth(), 1) : new Date(minDate.getFullYear(), minDate.getMonth(), 1);
    var saved = null;

    fallback.classList.add('is-enhanced');
    trigger.hidden = false;
    var panel = el('div', { 'class': 'popover popover-dates', role: 'dialog', 'aria-modal': 'false', 'aria-label': 'Tarih seçimi' });
    root.style.position = 'relative';
    root.appendChild(panel);

    function label() {
      var t = $('.trigger-text', trigger);
      if (start && end) {
        var n = diffDays(start, end);
        t.innerHTML = '<strong>' + fmt(start) + ' – ' + fmt(end) + '</strong><small>' + n + ' gece</small>';
      } else {
        t.innerHTML = '<strong>Tarih seçin</strong><small>Giriş – Çıkış</small>';
      }
    }
    function commit() {
      inIn.value = start ? iso(start) : '';
      inOut.value = end ? iso(end) : '';
      label();
      root.dispatchEvent(new CustomEvent('daterange:change', { bubbles: true }));
    }
    function monthHtml(y, m) {
      var first = new Date(y, m, 1), days = new Date(y, m + 1, 0).getDate();
      var offset = (first.getDay() + 6) % 7;
      var h = '<div class="dr-month"><h4>' + MONTHS[m] + ' ' + y + '</h4><div class="dr-grid" role="grid">';
      DOW.forEach(function (d) { h += '<div class="dow" aria-hidden="true">' + d + '</div>'; });
      for (var i = 0; i < offset; i++) h += '<div></div>';
      var today = iso(new Date());
      for (var d = 1; d <= days; d++) {
        var date = new Date(y, m, d), s = iso(date), cls = 'dr-day';
        var disabled = date < minDate || date > maxDate;
        if (start && !end && date > start && diffDays(start, date) > maxNights) disabled = true;
        if (start && iso(start) === s) cls += ' is-start';
        if (end && iso(end) === s) cls += ' is-end';
        if (start && end && date > start && date < end) cls += ' in-range';
        if (s === today) cls += ' is-today';
        var lbl = d + ' ' + MONTHS[m] + ' ' + y + ', ' + DOW_LONG[date.getDay()];
        h += '<button type="button" class="' + cls + '" data-date="' + s + '"' + (disabled ? ' disabled' : '') + ' aria-label="' + lbl + '"' + ((start && iso(start) === s) || (end && iso(end) === s) ? ' aria-pressed="true"' : '') + '>' + d + '</button>';
      }
      return h + '</div></div>';
    }
    function render() {
      var two = window.matchMedia('(min-width: 900px)').matches;
      var next = new Date(view.getFullYear(), view.getMonth() + 1, 1);
      var canPrev = new Date(view.getFullYear(), view.getMonth(), 1) > new Date(minDate.getFullYear(), minDate.getMonth(), 1);
      var summary = start && end ? fmt(start) + ' – ' + fmt(end) + '<span class="nights">' + diffDays(start, end) + ' gece</span>'
        : (start ? fmt(start) + ' – <span class="muted">çıkış tarihini seçin</span>' : '<span class="muted">Giriş tarihini seçin</span>');
      panel.innerHTML =
        '<div class="popover-head"><strong>Giriş ve çıkış tarihi</strong><div class="dr-nav">' +
        '<button type="button" class="btn btn-secondary btn-icon" data-nav="-1" aria-label="Önceki ay"' + (canPrev ? '' : ' disabled') + '>‹</button>' +
        '<button type="button" class="btn btn-secondary btn-icon" data-nav="1" aria-label="Sonraki ay">›</button></div></div>' +
        '<div class="dr-months' + (two ? ' two' : '') + '">' + monthHtml(view.getFullYear(), view.getMonth()) + (two ? monthHtml(next.getFullYear(), next.getMonth()) : '') + '</div>' +
        '<div class="popover-foot"><div class="dr-summary" aria-live="polite">' + summary + '</div><div class="row">' +
        '<button type="button" class="btn btn-ghost" data-act="clear">Temizle</button>' +
        '<button type="button" class="btn btn-secondary" data-act="close">Kapat</button>' +
        '<button type="button" class="btn" data-act="apply"' + (start && end ? '' : ' disabled') + '>Uygula</button></div></div>';
    }
    panel.addEventListener('click', function (e) {
      e.stopPropagation();
      var b = e.target.closest('button');
      if (!b) return;
      if (b.hasAttribute('data-nav')) { view = new Date(view.getFullYear(), view.getMonth() + parseInt(b.getAttribute('data-nav'), 10), 1); render(); return; }
      if (b.hasAttribute('data-date')) {
        var d = parseIso(b.getAttribute('data-date'));
        if (!start || (start && end) || d <= start) { start = d; end = null; }
        else { end = d; }
        render();
        var again = panel.querySelector('[data-date="' + b.getAttribute('data-date') + '"]');
        if (again) again.focus();
        return;
      }
      var act = b.getAttribute('data-act');
      if (act === 'clear') { start = null; end = null; render(); }
      if (act === 'close') { restore(); closePopover(); }
      if (act === 'apply' && start && end) { commit(); saved = null; closePopover(); }
    });
    function restore() { if (saved) { start = saved[0]; end = saved[1]; saved = null; } }
    trigger.addEventListener('click', function (e) {
      e.stopPropagation();
      if (panel.classList.contains('is-open')) { restore(); closePopover(); return; }
      saved = [start, end];
      render();
      showPopover(trigger, panel, restore);
    });
    label();
  });

  /* ---------- Konuk ve oda seçici ---------- */
  $$('[data-guests]').forEach(function (root) {
    var trigger = $('[data-gp-trigger]', root), fallback = $('.guest-fallback', root), hidden = $('[data-gp-hidden]', root);
    var stateEl = $('script[data-gp-state]', root);
    if (!trigger || !fallback || !hidden) return;
    var maxRooms = parseInt(root.getAttribute('data-max-rooms') || '5', 10);
    var rooms;
    try { rooms = JSON.parse(stateEl ? stateEl.textContent : '[]'); } catch (e) { rooms = []; }
    if (!rooms.length) rooms = [{ y: 2, c: [] }];
    var saved = null;
    fallback.classList.add('is-enhanced');
    $$('input, select', fallback).forEach(function (i) { i.disabled = true; });
    trigger.hidden = false;
    var panel = el('div', { 'class': 'popover popover-guests', role: 'dialog', 'aria-label': 'Konuk ve oda seçimi' });
    root.style.position = 'relative';
    root.appendChild(panel);

    function totals() {
      var a = 0, c = 0;
      rooms.forEach(function (r) { a += r.y; c += r.c.length; });
      return { a: a, c: c };
    }
    function summary() {
      var t = totals();
      return t.a + ' yetişkin' + (t.c ? ' · ' + t.c + ' çocuk' : '') + ' · ' + rooms.length + ' oda';
    }
    function writeHidden() {
      hidden.innerHTML = '';
      rooms.forEach(function (r, i) {
        hidden.appendChild(el('input', { type: 'hidden', name: 'oda[' + i + '][y]', value: r.y }));
        hidden.appendChild(el('input', { type: 'hidden', name: 'oda[' + i + '][c]', value: r.c.join('-') }));
      });
      $('.trigger-text', trigger).innerHTML = '<strong>' + summary() + '</strong><small>Konuk ve oda</small>';
    }
    function stepper(label, sub, val, min, max, key, i) {
      return '<div class="stepper-row"><div class="label">' + label + (sub ? '<small>' + sub + '</small>' : '') + '</div><div class="stepper">' +
        '<button type="button" data-step="-1" data-key="' + key + '" data-room="' + i + '" aria-label="' + label + ' azalt"' + (val <= min ? ' disabled' : '') + '>−</button>' +
        '<output aria-live="polite">' + val + '</output>' +
        '<button type="button" data-step="1" data-key="' + key + '" data-room="' + i + '" aria-label="' + label + ' artır"' + (val >= max ? ' disabled' : '') + '>+</button></div></div>';
    }
    function render() {
      var h = '<div class="popover-head"><strong>Konuklar ve odalar</strong></div>';
      rooms.forEach(function (r, i) {
        h += '<div class="gp-room"><div class="gp-room-head"><strong>' + (i + 1) + '. Oda</strong>' +
          (rooms.length > 1 ? '<button type="button" class="btn btn-ghost btn-sm" data-remove="' + i + '">Odayı kaldır</button>' : '') + '</div>';
        h += stepper('Yetişkin', '18 yaş ve üzeri', r.y, 1, 6, 'y', i);
        h += stepper('Çocuk', '0–17 yaş', r.c.length, 0, 4, 'c', i);
        if (r.c.length) {
          h += '<div class="ages-grid">';
          r.c.forEach(function (age, j) {
            var id = 'gp-age-' + i + '-' + j + '-' + Math.random().toString(36).slice(2, 6);
            h += '<div class="field" style="margin:0"><label for="' + id + '">' + (j + 1) + '. çocuk yaşı</label><select id="' + id + '" data-age="' + j + '" data-room="' + i + '">';
            for (var a = 0; a <= 17; a++) h += '<option value="' + a + '"' + (a === age ? ' selected' : '') + '>' + a + ' yaş</option>';
            h += '</select></div>';
          });
          h += '</div>';
        }
        h += '</div>';
      });
      h += '<div class="popover-foot"><button type="button" class="btn btn-secondary" data-add' + (rooms.length >= maxRooms ? ' disabled' : '') + '>+ Oda ekle</button>' +
        '<div class="row"><span class="muted small" aria-live="polite">' + summary() + '</span><button type="button" class="btn" data-apply>Uygula</button></div></div>';
      panel.innerHTML = h;
    }
    panel.addEventListener('click', function (e) {
      e.stopPropagation();
      var b = e.target.closest('button');
      if (!b) return;
      if (b.hasAttribute('data-step')) {
        var i = +b.getAttribute('data-room'), k = b.getAttribute('data-key'), s = +b.getAttribute('data-step');
        if (k === 'y') rooms[i].y = Math.max(1, Math.min(6, rooms[i].y + s));
        else if (s > 0 && rooms[i].c.length < 4) rooms[i].c.push(7);
        else if (s < 0) rooms[i].c.pop();
        render();
        var again = panel.querySelector('[data-step="' + s + '"][data-key="' + k + '"][data-room="' + i + '"]');
        if (again && !again.disabled) again.focus();
      } else if (b.hasAttribute('data-remove')) { rooms.splice(+b.getAttribute('data-remove'), 1); render(); }
      else if (b.hasAttribute('data-add')) { if (rooms.length < maxRooms) { rooms.push({ y: 1, c: [] }); render(); } }
      else if (b.hasAttribute('data-apply')) { writeHidden(); saved = null; closePopover(); }
    });
    panel.addEventListener('change', function (e) {
      var s = e.target;
      if (s.hasAttribute('data-age')) rooms[+s.getAttribute('data-room')].c[+s.getAttribute('data-age')] = +s.value;
    });
    function restore() { if (saved) { rooms = JSON.parse(saved); saved = null; } }
    trigger.addEventListener('click', function (e) {
      e.stopPropagation();
      if (panel.classList.contains('is-open')) { restore(); closePopover(); return; }
      saved = JSON.stringify(rooms);
      render();
      showPopover(trigger, panel, restore);
    });
    writeHidden();
  });

  /* ---------- Filtre paneli (mobil) ---------- */
  $$('[data-open-filters]').forEach(function (btn) {
    var panel = document.getElementById(btn.getAttribute('aria-controls'));
    if (!panel) return;
    btn.addEventListener('click', function () { panel.classList.add('is-open'); btn.setAttribute('aria-expanded', 'true'); document.body.style.overflow = 'hidden'; var c = $('.close-filters', panel); if (c) c.focus(); });
    $$('.close-filters', panel).forEach(function (c) {
      c.addEventListener('click', function () { panel.classList.remove('is-open'); btn.setAttribute('aria-expanded', 'false'); document.body.style.overflow = ''; btn.focus(); });
    });
  });

  /* ---------- Favoriler ---------- */
  $$('form[data-fav]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var btn = $('button', form);
      fetch(form.action, { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-Token': csrf, 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin', body: new FormData(form) })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          if (!d || !d.ok) return;
          btn.setAttribute('aria-pressed', d.favorite ? 'true' : 'false');
          btn.setAttribute('aria-label', d.favorite ? 'Favorilerden çıkar' : 'Favorilere ekle');
          var live = $('#live-region');
          if (live) live.textContent = d.favorite ? 'Favorilere eklendi' : 'Favorilerden çıkarıldı';
          var t = $('.fav-text', btn); if (t) t.textContent = d.favorite ? 'Favorilerde' : 'Favoriye ekle';
        })
        .catch(function () { form.submit(); });
    });
  });

  /* ---------- Galeri / lightbox ---------- */
  $$('[data-gallery]').forEach(function (g) {
    var items = $$('[data-full]', g);
    if (!items.length) return;
    var lb = el('div', { 'class': 'lightbox', role: 'dialog', 'aria-modal': 'true', 'aria-label': 'Fotoğraf galerisi' });
    lb.innerHTML = '<button type="button" class="icon-btn lb-close" aria-label="Galeriyi kapat">✕</button><button type="button" class="icon-btn lb-prev" aria-label="Önceki fotoğraf">‹</button><img alt=""><button type="button" class="icon-btn lb-next" aria-label="Sonraki fotoğraf">›</button><div class="lb-caption" aria-live="polite"></div>';
    document.body.appendChild(lb);
    var idx = 0, img = $('img', lb), cap = $('.lb-caption', lb), opener = null;
    function show(i) {
      idx = (i + items.length) % items.length;
      img.src = items[idx].getAttribute('data-full');
      img.alt = items[idx].getAttribute('data-caption') || '';
      cap.textContent = (idx + 1) + ' / ' + items.length + (items[idx].getAttribute('data-caption') ? ' · ' + items[idx].getAttribute('data-caption') : '');
    }
    function close() { lb.classList.remove('is-open'); document.body.style.overflow = ''; if (opener) opener.focus(); }
    items.forEach(function (it, i) { it.addEventListener('click', function () { opener = it; show(i); lb.classList.add('is-open'); document.body.style.overflow = 'hidden'; $('.lb-close', lb).focus(); }); });
    $('.lb-close', lb).addEventListener('click', close);
    $('.lb-prev', lb).addEventListener('click', function () { show(idx - 1); });
    $('.lb-next', lb).addEventListener('click', function () { show(idx + 1); });
    lb.addEventListener('click', function (e) { if (e.target === lb) close(); });
    document.addEventListener('keydown', function (e) {
      if (!lb.classList.contains('is-open')) return;
      if (e.key === 'Escape') close();
      if (e.key === 'ArrowLeft') show(idx - 1);
      if (e.key === 'ArrowRight') show(idx + 1);
    });
  });

  /* ---------- Harita (OpenStreetMap + Leaflet) ---------- */
  $$('[data-map]').forEach(function (box) {
    if (typeof L === 'undefined') { box.innerHTML = '<p class="muted" style="padding:16px">Harita yüklenemedi.</p>'; return; }
    var map = L.map(box, { scrollWheelZoom: false });
    L.tileLayer(box.getAttribute('data-tiles'), { maxZoom: 18, attribution: box.getAttribute('data-attribution') }).addTo(map);
    function draw(items) {
      var bounds = [];
      items.forEach(function (it) {
        var m = L.marker([it.lat, it.lng]).addTo(map);
        var div = document.createElement('div');
        div.className = 'map-popup';
        var s = document.createElement('strong'); s.textContent = it.name; div.appendChild(s);
        var p = document.createElement('div'); p.textContent = (it.region || '') + (it.price ? ' · ' + it.price : ''); div.appendChild(p);
        if (it.url) { var a = document.createElement('a'); a.href = it.url; a.textContent = 'Detayları Gör'; div.appendChild(a); }
        m.bindPopup(div);
        bounds.push([it.lat, it.lng]);
      });
      if (bounds.length > 1) map.fitBounds(bounds, { padding: [30, 30] });
      else if (bounds.length === 1) map.setView(bounds[0], 14);
      else map.setView([36.85, 30.85], 9);
    }
    var inline = box.getAttribute('data-items');
    if (inline) { try { draw(JSON.parse(inline)); } catch (e) { draw([]); } }
    else if (box.getAttribute('data-src')) {
      fetch(box.getAttribute('data-src'), { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
        .then(function (r) { return r.json(); }).then(function (d) { draw(d.items || []); }).catch(function () { draw([]); });
    }
  });

  /* ---------- Oda / fiyat seçimi (otel detayı) ---------- */
  var rateRadios = $$('input[name="secili_fiyat"]');
  if (rateRadios.length) {
    var sumRoom = $$('[data-sum-room]'), sumTotal = $$('[data-sum-total]'), sumNote = $$('[data-sum-note]'), sumBtn = $$('[data-sum-submit]');
    function update(r) {
      $$('.rate-row').forEach(function (row) { row.classList.remove('is-selected'); });
      var row = r.closest('.rate-row'); if (row) row.classList.add('is-selected');
      sumRoom.forEach(function (x) { x.textContent = r.getAttribute('data-room'); });
      sumTotal.forEach(function (x) { x.textContent = r.getAttribute('data-total'); });
      sumNote.forEach(function (x) { x.textContent = r.getAttribute('data-note'); });
      sumBtn.forEach(function (b) { b.textContent = r.getAttribute('data-action-label'); b.setAttribute('data-target-form', r.getAttribute('data-form')); });
    }
    rateRadios.forEach(function (r) { r.addEventListener('change', function () { update(r); }); if (r.checked) update(r); });
    sumBtn.forEach(function (b) {
      b.addEventListener('click', function () {
        var f = document.getElementById(b.getAttribute('data-target-form'));
        if (f) { if (f.requestSubmit) f.requestSubmit(); else f.submit(); }
        else { var rooms = document.getElementById('odalar'); if (rooms) rooms.scrollIntoView(); }
      });
    });
  }

  /* ---------- Sıralanabilir liste (yönetim: ana sayfa bölümleri) ---------- */
  $$('[data-sortable]').forEach(function (list) {
    list.addEventListener('click', function (e) {
      var b = e.target.closest('[data-move]');
      if (!b) return;
      var li = b.closest('li');
      if (b.getAttribute('data-move') === 'up' && li.previousElementSibling) list.insertBefore(li, li.previousElementSibling);
      if (b.getAttribute('data-move') === 'down' && li.nextElementSibling) list.insertBefore(li.nextElementSibling, li);
      b.focus();
    });
  });

  /* ---------- Otomatik kısa adres ---------- */
  $$('[data-slug-from]').forEach(function (slug) {
    var src = document.getElementById(slug.getAttribute('data-slug-from'));
    if (!src) return;
    var touched = slug.value !== '';
    slug.addEventListener('input', function () { touched = true; });
    src.addEventListener('input', function () {
      if (touched) return;
      var map = { 'ç': 'c', 'ğ': 'g', 'ı': 'i', 'İ': 'i', 'ö': 'o', 'ş': 's', 'ü': 'u', 'Ç': 'c', 'Ğ': 'g', 'Ö': 'o', 'Ş': 's', 'Ü': 'u' };
      slug.value = src.value.replace(/[çğıİöşüÇĞÖŞÜ]/g, function (c) { return map[c]; }).toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
    });
  });

  /* ---------- Kopyala ---------- */
  $$('[data-copy]').forEach(function (b) {
    b.addEventListener('click', function () {
      var t = document.getElementById(b.getAttribute('data-copy'));
      if (!t) return;
      t.select();
      try { navigator.clipboard.writeText(t.value); } catch (e) { document.execCommand('copy'); }
      b.textContent = 'Kopyalandı';
    });
  });

  /* ---------- Kurulum: rewrite testi ---------- */
  $$('[data-rewrite-test]').forEach(function (box) {
    var out = $('[data-rewrite-status]', box), input = $('input[name="rewrite_nonce"]', box.closest('form') || document);
    fetch(box.getAttribute('data-rewrite-test'), { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
      .then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
      .then(function (d) {
        if (d && d.ok) { if (input) input.value = d.nonce; out.className = 'ok'; out.textContent = 'Temiz adresler (mod_rewrite) çalışıyor.'; }
        else throw new Error('yanıt');
      })
      .catch(function (e) { out.className = 'fail'; out.textContent = 'Temiz adresler çalışmıyor (' + e.message + '). .htaccess dosyasının yüklendiğini ve AllowOverride ayarını kontrol edin.'; });
  });

  /* ---------- Hafta günü onay kutuları: tümünü seç ---------- */
  $$('[data-check-all]').forEach(function (b) {
    b.addEventListener('click', function () {
      var boxes = $$('input[type=checkbox][name="' + b.getAttribute('data-check-all') + '"]');
      var all = boxes.every(function (x) { return x.checked; });
      boxes.forEach(function (x) { x.checked = !all; });
    });
  });
})();
