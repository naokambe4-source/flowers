/* Derin Flowers — Canlı Düzenleyici */
( function ( $ ) {
	'use strict';

	var L = window.DFLive;
	if ( ! L ) {
		return;
	}
	var changes = {};
	var sectionsDirty = false;
	var body = document.body;
	body.classList.add( 'df-live-on' );

	/* ---------- Alt araç çubuğu ---------- */
	var bar = document.createElement( 'div' );
	bar.className = 'df-live-bar';
	bar.innerHTML =
		'<div class="df-live-bar__info"><strong>Canlı Düzenleme</strong><span data-count>Yazılara tıklayıp düzenleyin, görsellerin üzerindeki düğmeyle değiştirin.</span></div>' +
		'<div class="df-live-bar__actions">' +
		'<a class="df-live-btn df-live-btn--ghost" href="' + L.panelUrl + '" target="_blank" rel="noopener">Tüm ayarlar</a>' +
		'<button type="button" class="df-live-btn df-live-btn--ghost" data-live-cancel>Vazgeç</button>' +
		'<button type="button" class="df-live-btn" data-live-save disabled>Kaydet</button>' +
		'</div>';
	body.appendChild( bar );
	var saveBtn = bar.querySelector( '[data-live-save]' );
	var countEl = bar.querySelector( '[data-count]' );

	function dirtyCount() {
		return Object.keys( changes ).length + ( sectionsDirty ? 1 : 0 );
	}
	function refresh() {
		var n = dirtyCount();
		saveBtn.disabled = ! n;
		countEl.textContent = n ? n + ' değişiklik kaydedilmeyi bekliyor.' : 'Yazılara tıklayıp düzenleyin, görsellerin üzerindeki düğmeyle değiştirin.';
	}

	/* ---------- Yazı alanları ---------- */
	var editables = document.querySelectorAll( '[data-df-edit]' );
	Array.prototype.forEach.call( editables, function ( el ) {
		var multi = /^(textarea|lines)$/.test( el.getAttribute( 'data-df-type' ) );
		try {
			el.setAttribute( 'contenteditable', 'plaintext-only' );
			if ( 'plaintext-only' !== el.contentEditable ) {
				el.setAttribute( 'contenteditable', 'true' );
			}
		} catch ( e ) {
			el.setAttribute( 'contenteditable', 'true' );
		}
		el.setAttribute( 'spellcheck', 'true' );
		el.setAttribute( 'title', multi ? 'Düzenlemek için tıklayın (Enter: yeni satır)' : 'Düzenlemek için tıklayın' );
		el.addEventListener( 'keydown', function ( e ) {
			if ( 'Enter' === e.key && ! multi ) {
				e.preventDefault();
				el.blur();
			}
			if ( 'Escape' === e.key ) {
				el.blur();
			}
		} );
		el.addEventListener( 'paste', function ( e ) {
			e.preventDefault();
			var text = ( e.clipboardData || window.clipboardData ).getData( 'text/plain' );
			if ( ! multi ) {
				text = text.replace( /\s*\n\s*/g, ' ' );
			}
			document.execCommand( 'insertText', false, text );
		} );
		el.addEventListener( 'input', function () {
			var val = el.innerText.replace( / /g, ' ' );
			if ( ! multi ) {
				val = val.replace( /\s*\n\s*/g, ' ' );
			}
			changes[ el.getAttribute( 'data-df-edit' ) ] = val.replace( /\n$/, '' );
			// Aynı alanı gösteren diğer öğeleri eşitle.
			Array.prototype.forEach.call( document.querySelectorAll( '[data-df-edit="' + el.getAttribute( 'data-df-edit' ) + '"]' ), function ( o ) {
				if ( o !== el ) {
					o.innerText = el.innerText;
				}
			} );
			refresh();
		} );
	} );

	/* ---------- Bağlantılar düzenleme sırasında çalışmasın ---------- */
	document.addEventListener( 'click', function ( e ) {
		var a = e.target.closest( 'a' );
		if ( ! a || e.target.closest( '.df-live-bar, .df-live-tools, #wpadminbar, .df-drawer, .df-search' ) ) {
			return;
		}
		if ( a.closest( 'main' ) ) {
			e.preventDefault();
		}
	}, true );

	/* ---------- Görseller ---------- */
	Array.prototype.forEach.call( document.querySelectorAll( '[data-df-img]' ), function ( el ) {
		var btn = document.createElement( 'button' );
		btn.type = 'button';
		btn.className = 'df-live-imgbtn';
		btn.textContent = 'Görseli değiştir';
		// Arka plan katmanları metnin altında kalır; düğme üst kapsayıcıya eklenir.
		var host = el.closest( '.df-hero__slide' ) || ( el.classList.contains( 'df-editorial__bg' ) ? el.closest( '.df-editorial' ) : null ) || el;
		if ( host !== el ) {
			btn.classList.add( 'df-live-imgbtn--bg' );
		}
		if ( 'static' === getComputedStyle( host ).position ) {
			host.style.position = 'relative';
		}
		host.appendChild( btn );
		btn.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			e.stopPropagation();
			var frame = wp.media( { title: 'Görsel seç', library: { type: 'image' }, multiple: false, button: { text: 'Bu görseli kullan' } } );
			frame.on( 'select', function () {
				var at = frame.state().get( 'selection' ).first().toJSON();
				var url = at.sizes && at.sizes.large ? at.sizes.large.url : at.url;
				changes[ el.getAttribute( 'data-df-img' ) ] = String( at.id );
				var img = el.querySelector( 'img' );
				var ph = el.querySelector( '.df-ph' );
				if ( img ) {
					img.removeAttribute( 'srcset' );
					img.removeAttribute( 'sizes' );
					img.src = url;
				} else if ( ph ) {
					var ni = document.createElement( 'img' );
					ni.src = url;
					ni.alt = '';
					ph.replaceWith( ni );
				} else {
					el.style.backgroundImage = 'url("' + url + '")';
				}
				// Hero / banner arka planları CSS değişkeniyle çizilir.
				var slide = el.closest( '.df-hero__slide' );
				if ( slide ) {
					slide.style.setProperty( '--df-hero-d', 'url("' + url + '")' );
					slide.style.setProperty( '--df-hero-m', 'url("' + url + '")' );
					var p = slide.querySelector( '.df-ph' );
					if ( p ) {
						p.remove();
					}
				}
				var ed = el.closest( '.df-editorial' );
				if ( ed ) {
					ed.style.setProperty( '--df-ed-d', 'url("' + url + '")' );
					ed.style.setProperty( '--df-ed-m', 'url("' + url + '")' );
					var q = ed.querySelector( '.df-ph' );
					if ( q ) {
						q.remove();
					}
				}
				refresh();
			} );
			frame.open();
		} );
	} );

	/* ---------- Bölümler: sırala / gizle ---------- */
	var sections = Array.prototype.slice.call( document.querySelectorAll( '[data-df-section]' ) );
	sections.forEach( function ( sec ) {
		var id = sec.getAttribute( 'data-df-section' );
		var tools = document.createElement( 'div' );
		tools.className = 'df-live-tools';
		tools.innerHTML =
			'<span class="df-live-tools__name">' + ( L.labels[ id ] || id ) + '</span>' +
			'<button type="button" data-act="up" title="Yukarı taşı">▲</button>' +
			'<button type="button" data-act="down" title="Aşağı taşı">▼</button>' +
			'<button type="button" data-act="toggle" title="Göster / gizle"></button>' +
			'<a href="' + ( L.panel[ id ] || L.panelUrl ) + '" target="_blank" rel="noopener" title="Bu bölümün tüm ayarları">Ayarlar</a>';
		sec.insertBefore( tools, sec.firstChild );
		paintToggle( sec );
		tools.addEventListener( 'click', function ( e ) {
			var b = e.target.closest( 'button' );
			if ( ! b ) {
				return;
			}
			var act = b.getAttribute( 'data-act' );
			if ( 'up' === act && sec.previousElementSibling && sec.previousElementSibling.hasAttribute( 'data-df-section' ) ) {
				sec.parentNode.insertBefore( sec, sec.previousElementSibling );
			} else if ( 'down' === act && sec.nextElementSibling && sec.nextElementSibling.hasAttribute( 'data-df-section' ) ) {
				sec.parentNode.insertBefore( sec.nextElementSibling, sec );
			} else if ( 'toggle' === act ) {
				sec.setAttribute( 'data-df-on', '1' === sec.getAttribute( 'data-df-on' ) ? '0' : '1' );
				paintToggle( sec );
			} else {
				return;
			}
			sectionsDirty = true;
			refresh();
			sec.scrollIntoView( { block: 'nearest', behavior: 'smooth' } );
		} );
	} );
	function paintToggle( sec ) {
		var on = '1' === sec.getAttribute( 'data-df-on' );
		sec.classList.toggle( 'is-off', ! on );
		var b = sec.querySelector( '.df-live-tools [data-act="toggle"]' );
		if ( b ) {
			b.textContent = on ? 'Gizle' : 'Göster';
		}
	}

	/* ---------- Kaydet / vazgeç ---------- */
	saveBtn.addEventListener( 'click', function () {
		saveBtn.disabled = true;
		saveBtn.textContent = 'Kaydediliyor…';
		var data = { action: 'df_live_save', nonce: L.nonce, fields: JSON.stringify( changes ) };
		if ( sectionsDirty ) {
			data.sections = JSON.stringify( Array.prototype.map.call( document.querySelectorAll( '[data-df-section]' ), function ( s ) {
				return { id: s.getAttribute( 'data-df-section' ), on: '1' === s.getAttribute( 'data-df-on' ) ? 1 : 0 };
			} ) );
		}
		$.post( L.ajax, data ).done( function ( res ) {
			if ( res && res.success ) {
				changes = {};
				sectionsDirty = false;
				countEl.textContent = 'Kaydedildi ✓ Sitede yayında.';
				saveBtn.textContent = 'Kaydet';
				saveBtn.disabled = true;
			} else {
				fail( res && res.data && res.data.message );
			}
		} ).fail( function () {
			fail();
		} );
	} );
	function fail( msg ) {
		saveBtn.disabled = false;
		saveBtn.textContent = 'Kaydet';
		countEl.textContent = msg || 'Kaydedilemedi, lütfen tekrar deneyin.';
	}
	bar.querySelector( '[data-live-cancel]' ).addEventListener( 'click', function () {
		if ( ! dirtyCount() || window.confirm( 'Kaydedilmemiş değişiklikler silinsin mi?' ) ) {
			changes = {};
			sectionsDirty = false;
			window.location.reload();
		}
	} );
	window.addEventListener( 'beforeunload', function ( e ) {
		if ( dirtyCount() ) {
			e.preventDefault();
			e.returnValue = '';
		}
	} );
}( jQuery ) );
