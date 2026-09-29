/**
 * Can Eloksal Canlı Editör — kabuk (sol panel + üst çubuk + sayfa önizlemesi).
 */
( function () {
	'use strict';

	var S = window.CEBShellData || {};
	var app = document.getElementById( 'ceb-live-app' );
	if ( ! app ) { return; }

	var DEVICES = { desktop: '100%', tablet: '820px', mobile: '390px' };
	var MEDIA = { desktop: '', tablet: '@media (max-width: 1023px)', mobile: '@media (max-width: 639px)' };
	var SHADOWS = {
		'': 'Varsayılan', none: 'Yok',
		'0 2px 8px rgba(10,17,24,.08)': 'Hafif',
		'0 12px 30px -12px rgba(10,17,24,.25)': 'Orta',
		'0 30px 60px -24px rgba(10,17,24,.4)': 'Güçlü',
		'0 0 0 6px rgba(0,175,193,.18)': 'Turkuaz hale'
	};

	var frame = null;
	var info = null;
	var device = 'desktop';
	var scope = 'page';
	var target = 'element';
	var tab = 'content';
	var pending = emptyState();
	var saved = { global: {}, page: {} };
	var attachments = {};
	var history = [];
	var hIndex = -1;
	var dirtyText = false;
	var currentUrl = S.url;

	function emptyState() {
		var d = function () { return { desktop: {}, tablet: {}, mobile: {} }; };
		return { values: {}, ops: [], styles: { global: d(), page: d() }, resets: [] };
	}
	function esc( s ) { return String( s == null ? '' : s ).replace( /[&<>"']/g, function ( c ) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ c ]; } ); }
	function $( sel, ctx ) { return ( ctx || app ).querySelector( sel ); }
	function $$( sel, ctx ) { return Array.prototype.slice.call( ( ctx || app ).querySelectorAll( sel ) ); }
	function frameUrl( url ) { return url + ( url.indexOf( '?' ) === -1 ? '?' : '&' ) + 'ceb_frame=1'; }
	function toHex( rgb ) {
		if ( ! rgb ) { return ''; }
		if ( rgb.charAt( 0 ) === '#' ) { return rgb; }
		var m = rgb.match( /rgba?\(([^)]+)\)/ );
		if ( ! m ) { return ''; }
		var p = m[ 1 ].split( ',' ).map( function ( x ) { return parseFloat( x ); } );
		if ( p.length > 3 && p[ 3 ] === 0 ) { return ''; }
		return '#' + [ 0, 1, 2 ].map( function ( i ) { return ( '0' + Math.round( p[ i ] ).toString( 16 ) ).slice( -2 ); } ).join( '' );
	}
	function num( v ) { var n = parseFloat( v ); return isNaN( n ) ? '' : Math.round( n * 100 ) / 100; }

	/* Arayüz ------------------------------------------------------------------------ */
	var groups = {};
	( S.pages || [] ).forEach( function ( p ) { ( groups[ p.group ] = groups[ p.group ] || [] ).push( p ); } );
	var pageOptions = Object.keys( groups ).map( function ( g ) {
		return '<optgroup label="' + esc( g ) + '">' + groups[ g ].map( function ( p ) {
			return '<option value="' + esc( p.url ) + '">' + esc( p.title || '(başlıksız)' ) + '</option>';
		} ).join( '' ) + '</optgroup>';
	} ).join( '' );

	app.innerHTML =
		'<div class="cebs">' +
		'<header class="cebs-top">' +
			'<div class="cebs-brand"><span class="dashicons dashicons-edit-page" aria-hidden="true"></span><span>Canlı Editör</span></div>' +
			'<label class="screen-reader-text" for="cebs-page">Düzenlenecek sayfa</label>' +
			'<select id="cebs-page" class="cebs-page">' + pageOptions + '</select>' +
			'<span class="cebs-ctx" title="Stil kapsamı: bu sayfa"></span>' +
			'<div class="cebs-devices" role="group" aria-label="Cihaz">' +
				'<button type="button" data-device="desktop" class="is-active" title="Masaüstü"><span class="dashicons dashicons-desktop"></span></button>' +
				'<button type="button" data-device="tablet" title="Tablet (≤1023px)"><span class="dashicons dashicons-tablet"></span></button>' +
				'<button type="button" data-device="mobile" title="Mobil (≤639px)"><span class="dashicons dashicons-smartphone"></span></button>' +
			'</div>' +
			'<div class="cebs-hist"><button type="button" data-act="undo" title="Geri al (Ctrl+Z)" disabled><span class="dashicons dashicons-undo"></span></button><button type="button" data-act="redo" title="İleri al (Ctrl+Y)" disabled><span class="dashicons dashicons-redo"></span></button></div>' +
			'<span class="cebs-count" aria-live="polite">Değişiklik yok</span>' +
			'<a class="cebs-btn cebs-btn--ghost" data-exit href="#">Çık</a>' +
			'<button type="button" class="cebs-btn cebs-btn--primary" data-act="save" disabled>Kaydet</button>' +
		'</header>' +
		'<aside class="cebs-side">' +
			'<div class="cebs-sel"></div>' +
			'<nav class="cebs-tabs" role="tablist">' +
				'<button type="button" data-tab="content" class="is-active">İçerik</button>' +
				'<button type="button" data-tab="style">Stil</button>' +
				'<button type="button" data-tab="typo">Yazı</button>' +
				'<button type="button" data-tab="layout">Düzen</button>' +
				'<button type="button" data-tab="adv">Gelişmiş</button>' +
			'</nav>' +
			'<div class="cebs-panel"></div>' +
		'</aside>' +
		'<main class="cebs-canvas"><div class="cebs-frame-wrap"><iframe class="cebs-frame" title="Sayfa önizlemesi"></iframe><div class="cebs-loading"><span class="cebs-spin"></span>Sayfa yükleniyor…</div></div></main>' +
		'<div class="cebs-toasts" aria-live="polite"></div>' +
		'</div>';

	var iframe = $( '.cebs-frame' );
	var panel = $( '.cebs-panel' );
	var wrap = $( '.cebs-frame-wrap' );

	function toast( msg, type, ms ) {
		var t = document.createElement( 'div' );
		t.className = 'cebs-toast' + ( type ? ' is-' + type : '' );
		t.innerHTML = msg;
		$( '.cebs-toasts' ).appendChild( t );
		setTimeout( function () { t.classList.add( 'is-out' ); }, ms || 3500 );
		setTimeout( function () { t.remove(); }, ( ms || 3500 ) + 400 );
	}

	/* Durum ve geçmiş ------------------------------------------------------------ */
	function countChanges() {
		var n = Object.keys( pending.values ).length + pending.ops.length + pending.resets.length;
		[ 'global', 'page' ].forEach( function ( sc ) {
			Object.keys( pending.styles[ sc ] ).forEach( function ( d ) {
				Object.keys( pending.styles[ sc ][ d ] ).forEach( function ( s ) { n += Object.keys( pending.styles[ sc ][ d ][ s ] ).length; } );
			} );
		} );
		return n;
	}
	function updateBar() {
		var n = countChanges();
		$( '.cebs-count' ).textContent = n ? n + ' kaydedilmemiş değişiklik' : 'Değişiklik yok';
		$( '.cebs-count' ).classList.toggle( 'is-dirty', n > 0 );
		$( '[data-act="save"]' ).disabled = ! n;
		$( '[data-act="undo"]' ).disabled = hIndex <= 0;
		$( '[data-act="redo"]' ).disabled = hIndex >= history.length - 1;
	}
	function pushHistory() {
		if ( ! frame ) { return; }
		var entry = { snap: frame.snapshot(), pending: JSON.stringify( pending ) };
		if ( hIndex >= 0 && history[ hIndex ].pending === entry.pending ) { updateBar(); return; }
		history = history.slice( 0, hIndex + 1 );
		history.push( entry );
		if ( history.length > 80 ) { history.shift(); }
		hIndex = history.length - 1;
		dirtyText = false;
		updateBar();
	}
	function restoreEntry( entry ) {
		frame.restore( entry.snap );
		pending = JSON.parse( entry.pending );
		applyCSS();
		info = null;
		renderPanel();
		updateBar();
	}
	function undo() { if ( hIndex > 0 ) { hIndex--; restoreEntry( history[ hIndex ] ); } }
	function redo() { if ( hIndex < history.length - 1 ) { hIndex++; restoreEntry( history[ hIndex ] ); } }

	/* Stiller ---------------------------------------------------------------------- */
	function merged( sc ) {
		var out = {};
		var src = [ saved[ sc ] || {}, pending.styles[ sc ] ];
		var resets = pending.resets.filter( function ( r ) { return r.scope === sc; } ).map( function ( r ) { return r.selector; } );
		src.forEach( function ( tree, i ) {
			Object.keys( tree ).forEach( function ( d ) {
				Object.keys( tree[ d ] || {} ).forEach( function ( sel ) {
					if ( i === 0 && resets.indexOf( sel ) !== -1 ) { return; }
					out[ d ] = out[ d ] || {};
					out[ d ][ sel ] = Object.assign( {}, out[ d ][ sel ] || {}, tree[ d ][ sel ] );
				} );
			} );
		} );
		return out;
	}
	function compile( tree ) {
		var css = '';
		[ 'desktop', 'tablet', 'mobile' ].forEach( function ( d ) {
			var block = '';
			Object.keys( tree[ d ] || {} ).forEach( function ( sel ) {
				var decl = [];
				Object.keys( tree[ d ][ sel ] ).forEach( function ( p ) {
					var v = tree[ d ][ sel ][ p ];
					if ( v === '' || v == null ) { return; }
					var m = /^att:(\d+)$/.exec( v );
					if ( m ) { v = attachments[ m[ 1 ] ] ? 'url("' + attachments[ m[ 1 ] ] + '")' : 'none'; }
					decl.push( p + ':' + v + ' !important' );
				} );
				if ( decl.length ) { block += sel + '{' + decl.join( ';' ) + '}'; }
			} );
			if ( block ) { css += MEDIA[ d ] ? MEDIA[ d ] + '{' + block + '}' : block; }
		} );
		return css;
	}
	function applyCSS() { if ( frame ) { frame.setCSS( compile( merged( 'global' ) ) + compile( merged( 'page' ) ) ); } }
	function selector() { return info ? ( 'similar' === target ? info.similar : info.selector ) : ''; }
	function styleValue( prop ) {
		var sel = selector();
		var p = pending.styles[ scope ][ device ][ sel ];
		if ( p && prop in p ) { return p[ prop ]; }
		var s = ( saved[ scope ] || {} )[ device ];
		if ( s && s[ sel ] && prop in s[ sel ] ) { return s[ sel ][ prop ]; }
		return null;
	}
	function setStyle( prop, value, commit ) {
		if ( ! info || ! S.canStyle ) { return; }
		var sel = selector();
		var bucket = pending.styles[ scope ][ device ];
		bucket[ sel ] = bucket[ sel ] || {};
		bucket[ sel ][ prop ] = value;
		applyCSS();
		if ( commit ) { pushHistory(); setTimeout( function () { if ( frame ) { frame.reselect(); } }, 30 ); } else { updateBar(); }
	}

	/* Panel ------------------------------------------------------------------------- */
	function sourceLabel( src ) {
		if ( ! src ) { return ''; }
		var p = src.split( ':' );
		switch ( p[ 0 ] ) {
			case 'opt': return 'Tema Ayarları · tüm sitede kullanılır';
			case 'meta': return 'İçerik alanı (#' + p[ 1 ] + ')';
			case 'post': return 'excerpt' === p[ 2 ] ? 'İçerik özeti (#' + p[ 1 ] + ')' : 'İçerik başlığı (#' + p[ 1 ] + ')';
			case 'thumb': return 'Öne çıkan görsel (#' + p[ 1 ] + ')';
			case 'battr': return 'Bu sayfadaki blok ayarı';
			case 'cblock': return 'Sayfa içeriği (blok)';
			case 'cimg': return 'Sayfa içeriği (görsel bloğu)';
		}
		return src;
	}
	function canEditSource( src ) { return src.indexOf( 'opt:' ) !== 0 || S.canSettings; }

	function field( label, html, cls ) { return '<div class="cebs-f' + ( cls ? ' ' + cls : '' ) + '"><label>' + label + '</label>' + html + '</div>'; }
	function colorField( label, prop, current ) {
		var hex = toHex( current );
		return field( label, '<div class="cebs-color"><input type="color" data-prop="' + prop + '" value="' + ( hex || '#ffffff' ) + '"><input type="text" data-prop="' + prop + '" value="' + esc( hex ) + '" placeholder="#hex"><button type="button" class="cebs-x" data-clear="' + prop + '" title="Varsayılana döndür">↺</button></div>' +
			'<div class="cebs-swatches">' + ( S.palette || [] ).map( function ( c ) { return '<button type="button" data-swatch="' + prop + '" data-val="' + c + '" style="background:' + c + '" title="' + c + '"></button>'; } ).join( '' ) + '</div>' );
	}
	function numField( label, prop, current, unit, attrs ) {
		return field( label, '<div class="cebs-num"><input type="number" data-prop="' + prop + '" data-unit="' + ( unit || '' ) + '" value="' + esc( current ) + '" ' + ( attrs || '' ) + '><span>' + ( unit || '' ) + '</span></div>', 'is-half' );
	}
	function rangeField( label, prop, current, min, max, unit, step ) {
		return field( label + ' <output>' + esc( current ) + ( unit || '' ) + '</output>', '<input type="range" data-prop="' + prop + '" data-unit="' + ( unit || '' ) + '" min="' + min + '" max="' + max + '" step="' + ( step || 1 ) + '" value="' + esc( current ) + '">' );
	}
	function selectField( label, prop, current, options, half ) {
		return field( label, '<select data-prop="' + prop + '">' + Object.keys( options ).map( function ( k ) {
			return '<option value="' + esc( k ) + '"' + ( String( current ) === k ? ' selected' : '' ) + '>' + esc( options[ k ] ) + '</option>';
		} ).join( '' ) + '</select>', half ? 'is-half' : '' );
	}
	function segField( label, prop, current, options ) {
		return field( label, '<div class="cebs-seg">' + Object.keys( options ).map( function ( k ) {
			return '<button type="button" data-seg="' + prop + '" data-val="' + esc( k ) + '" class="' + ( current === k ? 'is-active' : '' ) + '">' + options[ k ] + '</button>';
		} ).join( '' ) + '</div>' );
	}
	function cur( prop, computedKey, fmt ) {
		var v = styleValue( prop );
		if ( v != null && v !== '' ) { return fmt ? fmt( v ) : v; }
		var c = info.computed[ computedKey ];
		return fmt ? fmt( c ) : c;
	}

	function scopeBar() {
		if ( ! S.canStyle ) {
			return '<div class="cebs-note is-warn">Stil değiştirme yetkiniz yok. Metinleri "İçerik" sekmesinden düzenleyebilirsiniz.</div>';
		}
		return '<div class="cebs-scope">' +
			segField( 'Nerede geçerli?', '__scope', scope, { page: 'Bu sayfa', global: 'Tüm site' } ) +
			segField( 'Hangi öğeler?', '__target', target, { element: 'Yalnızca bu', similar: 'Benzerleri (' + ( info.similarCount || 1 ) + ')' } ) +
			( 'desktop' !== device ? '<div class="cebs-note">' + ( 'tablet' === device ? 'Tablet' : 'Mobil' ) + ' görünümdesiniz: değişiklik yalnızca bu ekran boyutunda geçerli olur.</div>' : '' ) +
			'</div>';
	}

	function renderSelHeader() {
		var box = $( '.cebs-sel' );
		if ( ! info ) {
			box.innerHTML = '<div class="cebs-empty"><span class="dashicons dashicons-admin-customizer"></span><p><strong>Düzenlemek için sayfada bir öğeye tıklayın.</strong></p><p>Metinlere tıklayıp doğrudan yazabilir; renk, yazı ve boşlukları soldaki sekmelerden değiştirebilirsiniz. Bölümleri araç çubuğuyla taşıyabilir, çoğaltabilir veya kaldırabilirsiniz.</p><ul><li><kbd>Ctrl</kbd>+<kbd>Z</kbd> geri al · <kbd>Ctrl</kbd>+<kbd>Y</kbd> ileri al</li><li><kbd>Ctrl</kbd>+<kbd>S</kbd> kaydet · <kbd>Esc</kbd> seçimi kaldır</li></ul></div>';
			return;
		}
		box.innerHTML = '<div class="cebs-sel__row"><div><strong>' + esc( info.name ) + '</strong><small>' + esc( info.ancestors.slice().reverse().join( ' › ' ) ) + '</small></div>' +
			'<button type="button" class="cebs-icon" data-act="parent" title="Üst öğeyi seç"><span class="dashicons dashicons-arrow-up-alt"></span></button>' +
			'<button type="button" class="cebs-icon" data-act="deselect" title="Seçimi kaldır"><span class="dashicons dashicons-no-alt"></span></button></div>';
	}

	function renderPanel() {
		renderSelHeader();
		$$( '.cebs-tabs button' ).forEach( function ( b ) { b.classList.toggle( 'is-active', b.dataset.tab === tab ); b.disabled = ! info; } );
		$( '.cebs-tabs' ).classList.toggle( 'is-hidden', ! info );
		if ( ! info ) { panel.innerHTML = ''; return; }
		var c = info.computed;
		var html = '';

		if ( 'content' === tab ) {
			if ( info.text ) {
				var t = info.text;
				var val = t.source in pending.values ? pending.values[ t.source ] : t.value;
				var editable = canEditSource( t.source );
				var multi = [ 'nl', 'paras', 'pipe', 'lines' ].indexOf( t.format ) !== -1;
				var hint = { pipe: 'Her satır başlığın bir satırı olur.', lines: 'Her satır bir öğe olur.', paras: 'Boş satır yeni paragraf başlatır.', nl: 'Satır başı yeni satır olur.' }[ t.format ] || '';
				html += '<section class="cebs-card"><h3>Metin</h3><p class="cebs-src"><span class="dashicons dashicons-database"></span>' + esc( sourceLabel( t.source ) ) + '</p>';
				if ( 'html' === t.format ) {
					html += '<div class="cebs-note">Bu metni doğrudan sayfada yazarak düzenleyin. Kalın/italik için <kbd>Ctrl</kbd>+<kbd>B</kbd> / <kbd>Ctrl</kbd>+<kbd>I</kbd>.</div>';
				} else if ( ! editable ) {
					html += '<div class="cebs-note is-warn">Bu metin Tema Ayarları\'ndan gelir; düzenleme yetkiniz yok.</div><p class="cebs-ro">' + esc( val ) + '</p>';
				} else {
					html += multi ? '<textarea data-text="' + esc( t.source ) + '" rows="' + ( 'paras' === t.format ? 8 : 4 ) + '">' + esc( 'pipe' === t.format ? String( val ).split( '|' ).map( function ( s ) { return s.trim(); } ).join( '\n' ) : val ) + '</textarea>' : '<input type="text" data-text="' + esc( t.source ) + '" value="' + esc( val ) + '">';
					if ( hint ) { html += '<p class="cebs-hint">' + hint + '</p>'; }
					if ( t.source.indexOf( 'opt:' ) === 0 ) { html += '<p class="cebs-hint">Bu metin Tema Ayarları\'nda saklanır; kullanıldığı her yerde değişir.</p>'; }
				}
				html += '</section>';
			}
			if ( info.link ) {
				html += '<section class="cebs-card"><h3>Bağlantı</h3>';
				if ( info.link.source && canEditSource( info.link.source ) ) {
					var lv = info.link.source in pending.values ? pending.values[ info.link.source ] : info.link.href;
					html += '<input type="text" data-link="' + esc( info.link.source ) + '" value="' + esc( lv ) + '" placeholder="https://… veya /sayfa/">';
				} else {
					html += '<p class="cebs-ro">' + esc( info.link.href ) + '</p><p class="cebs-hint">Bu bağlantı menüden veya otomatik oluşturulur.</p>';
				}
				html += '</section>';
			}
			if ( info.image ) {
				html += '<section class="cebs-card"><h3>Görsel</h3><p class="cebs-src"><span class="dashicons dashicons-format-image"></span>' + esc( sourceLabel( info.image.source ) ) + '</p>' +
					( info.image.src ? '<img class="cebs-thumb" src="' + esc( info.image.src ) + '" alt="">' : '<p class="cebs-hint">Henüz görsel yok (metal doku gösteriliyor).</p>' ) +
					( canEditSource( info.image.source ) ? '<button type="button" class="cebs-btn cebs-btn--block" data-act="image">Görseli değiştir</button>' : '' ) + '</section>';
			}
			if ( info.unit ) {
				html += '<section class="cebs-card"><h3>Bölüm</h3><p class="cebs-hint">' + ( 'block' === info.unit.type ? 'Sayfa bloğu: ' + esc( info.unit.name ) : 'Ana sayfa bölümü: ' + esc( info.unit.name ) ) + '</p>' +
					'<div class="cebs-actions"><button type="button" class="cebs-btn" data-act="up">▲ Yukarı</button><button type="button" class="cebs-btn" data-act="down">▼ Aşağı</button>' +
					( 'block' === info.unit.type ? '<button type="button" class="cebs-btn" data-act="duplicate">⧉ Çoğalt</button>' : '' ) +
					'<button type="button" class="cebs-btn is-danger" data-act="remove">✕ ' + ( 'block' === info.unit.type ? 'Sil' : 'Gizle' ) + '</button></div></section>';
			}
			if ( ! info.text && ! info.image && ! info.unit && ! ( info.link && info.link.source ) ) {
				html += '<div class="cebs-note">Bu öğenin içeriği otomatik oluşturuluyor (menü, liste, form vb.). Görünümünü <strong>Stil</strong>, <strong>Yazı</strong> ve <strong>Düzen</strong> sekmelerinden değiştirebilirsiniz.</div>';
			}
			if ( frame && frame.data.editLink ) {
				html += '<p class="cebs-links"><a href="' + esc( frame.data.editLink ) + '" target="_blank" rel="noopener">Sayfayı blok editöründe aç ↗</a></p>';
			}
		}

		if ( 'style' === tab ) {
			html += scopeBar();
			if ( S.canStyle ) {
				html += '<section class="cebs-card"><h3>Arka plan</h3>' + colorField( 'Renk', 'background-color', cur( 'background-color', 'backgroundColor' ) ) +
					'<div class="cebs-actions"><button type="button" class="cebs-btn" data-act="bgimage">Görsel seç</button><button type="button" class="cebs-btn" data-act="bgnone">Görseli kaldır</button></div>' +
					selectField( 'Görsel boyutu', 'background-size', styleValue( 'background-size' ) || '', { '': 'Varsayılan', cover: 'Kapla', contain: 'Sığdır', auto: 'Orijinal' }, true ) +
					selectField( 'Görsel konumu', 'background-position', styleValue( 'background-position' ) || '', { '': 'Varsayılan', center: 'Orta', top: 'Üst', bottom: 'Alt', left: 'Sol', right: 'Sağ' }, true ) + '</section>';
				html += '<section class="cebs-card"><h3>Kenarlık & köşe</h3>' +
					rangeField( 'Kalınlık', 'border-width', num( cur( 'border-width', 'borderTopWidth' ) ) || 0, 0, 20, 'px' ) +
					selectField( 'Çizgi', 'border-style', styleValue( 'border-style' ) || '', { '': 'Varsayılan', solid: 'Düz', dashed: 'Kesik', dotted: 'Noktalı', none: 'Yok' }, true ) +
					colorField( 'Kenarlık rengi', 'border-color', cur( 'border-color', 'borderTopColor' ) ) +
					rangeField( 'Köşe yuvarlaklığı', 'border-radius', num( cur( 'border-radius', 'borderTopLeftRadius' ) ) || 0, 0, 80, 'px' ) + '</section>';
				html += '<section class="cebs-card"><h3>Efekt</h3>' + selectField( 'Gölge', 'box-shadow', styleValue( 'box-shadow' ) || '', SHADOWS ) +
					rangeField( 'Opaklık', 'opacity', Math.round( ( parseFloat( cur( 'opacity', 'opacity' ) ) || 1 ) * 100 ), 0, 100, '%' ) + '</section>';
			}
		}

		if ( 'typo' === tab ) {
			html += scopeBar();
			if ( S.canStyle ) {
				var fonts = { '': 'Varsayılan' };
				Object.keys( S.fonts || {} ).forEach( function ( k ) { fonts[ S.fonts[ k ] ] = S.fontLabels[ k ]; } );
				html += '<section class="cebs-card"><h3>Yazı</h3>' +
					selectField( 'Font', 'font-family', styleValue( 'font-family' ) || '', fonts ) +
					numField( 'Boyut', 'font-size', num( cur( 'font-size', 'fontSize' ) ), 'px', 'min="8" max="200"' ) +
					selectField( 'Kalınlık', 'font-weight', String( cur( 'font-weight', 'fontWeight' ) ), { 300: 'İnce', 400: 'Normal', 500: 'Orta', 600: 'Yarı kalın', 700: 'Kalın', 800: 'Çok kalın' }, true ) +
					numField( 'Satır yüksekliği', 'line-height', num( styleValue( 'line-height' ) || ( parseFloat( c.lineHeight ) / parseFloat( c.fontSize ) ) ), '', 'step="0.05" min="0.8" max="3"' ) +
					numField( 'Harf aralığı', 'letter-spacing', num( cur( 'letter-spacing', 'letterSpacing' ) ) || 0, 'px', 'step="0.1"' ) +
					colorField( 'Renk', 'color', cur( 'color', 'color' ) ) +
					segField( 'Hizalama', 'text-align', cur( 'text-align', 'textAlign' ), { left: '<span class="dashicons dashicons-editor-alignleft"></span>', center: '<span class="dashicons dashicons-editor-aligncenter"></span>', right: '<span class="dashicons dashicons-editor-alignright"></span>', justify: '<span class="dashicons dashicons-editor-justify"></span>' } ) +
					selectField( 'Harf dönüşümü', 'text-transform', styleValue( 'text-transform' ) || '', { '': 'Varsayılan', none: 'Yok', uppercase: 'BÜYÜK HARF', lowercase: 'küçük harf', capitalize: 'Baş Harfler' }, true ) +
					selectField( 'Stil', 'font-style', styleValue( 'font-style' ) || '', { '': 'Varsayılan', normal: 'Normal', italic: 'İtalik' }, true ) +
					selectField( 'Çizgi', 'text-decoration', styleValue( 'text-decoration' ) || '', { '': 'Varsayılan', none: 'Yok', underline: 'Altı çizili', 'line-through': 'Üstü çizili' } ) +
					'</section>';
			}
		}

		if ( 'layout' === tab ) {
			html += scopeBar();
			if ( S.canStyle ) {
				var box = function ( label, p, ck ) {
					return '<section class="cebs-card"><h3>' + label + '</h3><div class="cebs-box4">' +
						[ [ 'top', 'Üst' ], [ 'right', 'Sağ' ], [ 'bottom', 'Alt' ], [ 'left', 'Sol' ] ].map( function ( s ) {
							var k = ck + s[ 0 ].charAt( 0 ).toUpperCase() + s[ 0 ].slice( 1 );
							return '<label><span>' + s[ 1 ] + '</span><input type="number" data-prop="' + p + '-' + s[ 0 ] + '" data-unit="px" value="' + esc( num( cur( p + '-' + s[ 0 ], k ) ) ) + '"></label>';
						} ).join( '' ) + '<label class="is-all"><span>Tümü</span><input type="number" data-all="' + p + '" placeholder="—"></label></div></section>';
				};
				html += box( 'İç boşluk (padding)', 'padding', 'padding' ) + box( 'Dış boşluk (margin)', 'margin', 'margin' );
				html += '<section class="cebs-card"><h3>Boyut</h3>' +
					field( 'Genişlik', '<input type="text" data-prop="width" value="' + esc( styleValue( 'width' ) || '' ) + '" placeholder="auto, 100%, 480px">', 'is-half' ) +
					field( 'En fazla genişlik', '<input type="text" data-prop="max-width" value="' + esc( styleValue( 'max-width' ) || '' ) + '" placeholder="none, 720px">', 'is-half' ) +
					field( 'En az yükseklik', '<input type="text" data-prop="min-height" value="' + esc( styleValue( 'min-height' ) || '' ) + '" placeholder="0, 400px, 60vh">', 'is-half' ) +
					field( 'Yükseklik', '<input type="text" data-prop="height" value="' + esc( styleValue( 'height' ) || '' ) + '" placeholder="auto">', 'is-half' ) + '</section>';
				html += '<section class="cebs-card"><h3>Yerleşim</h3>' +
					selectField( 'Görünüm', 'display', styleValue( 'display' ) || '', { '': 'Varsayılan', block: 'Blok', 'inline-block': 'Satır içi blok', flex: 'Esnek (flex)', grid: 'Izgara (grid)', none: 'Gizli' } ) +
					selectField( 'Yön', 'flex-direction', styleValue( 'flex-direction' ) || '', { '': 'Varsayılan', row: 'Yatay', column: 'Dikey', 'row-reverse': 'Yatay (ters)', 'column-reverse': 'Dikey (ters)' }, true ) +
					selectField( 'Sarma', 'flex-wrap', styleValue( 'flex-wrap' ) || '', { '': 'Varsayılan', wrap: 'Sar', nowrap: 'Sarma' }, true ) +
					selectField( 'Dikey hiza', 'align-items', styleValue( 'align-items' ) || '', { '': 'Varsayılan', 'flex-start': 'Başa', center: 'Ortaya', 'flex-end': 'Sona', stretch: 'Uzat' }, true ) +
					selectField( 'Yatay dağılım', 'justify-content', styleValue( 'justify-content' ) || '', { '': 'Varsayılan', 'flex-start': 'Başa', center: 'Ortaya', 'flex-end': 'Sona', 'space-between': 'Aralıklı' }, true ) +
					numField( 'Boşluk (gap)', 'gap', num( styleValue( 'gap' ) || c.gap ), 'px' ) + '</section>';
				html += '<div class="cebs-actions"><button type="button" class="cebs-btn" data-act="hide">' + ( 'desktop' === device ? 'Tüm cihazlarda gizle' : 'Bu cihazda gizle' ) + '</button><button type="button" class="cebs-btn" data-act="show">Göster</button></div>';
			}
		}

		if ( 'adv' === tab ) {
			html += scopeBar();
			if ( S.canStyle ) {
				html += '<section class="cebs-card"><h3>Seçici</h3><p class="cebs-hint">Bu öğe:</p><code class="cebs-code">' + esc( info.selector ) + '</code><p class="cebs-hint">Benzerleri:</p><code class="cebs-code">' + esc( info.similar ) + '</code></section>' +
					'<section class="cebs-card"><h3>Diğer</h3>' +
					numField( 'Katman (z-index)', 'z-index', styleValue( 'z-index' ) || '', '' ) +
					selectField( 'Görsel sığdırma', 'object-fit', styleValue( 'object-fit' ) || '', { '': 'Varsayılan', cover: 'Kapla', contain: 'Sığdır', fill: 'Uzat' }, true ) +
					field( 'En-boy oranı', '<input type="text" data-prop="aspect-ratio" value="' + esc( styleValue( 'aspect-ratio' ) || '' ) + '" placeholder="16 / 9">' ) + '</section>' +
					'<section class="cebs-card"><h3>Ek CSS</h3><textarea class="cebs-css" rows="5" placeholder="örnek:\ncolor: #00AFC1;\npadding-top: 40px;"></textarea><p class="cebs-hint">"özellik: değer;" biçiminde yazın. Güvenlik nedeniyle yalnızca desteklenen özellikler kaydedilir.</p><button type="button" class="cebs-btn cebs-btn--block" data-act="applycss">Uygula</button></section>' +
					'<button type="button" class="cebs-btn cebs-btn--block is-danger" data-act="reset">Bu seçicinin stillerini sıfırla</button>';
			}
		}
		panel.innerHTML = html;
	}

	/* Panel olayları ------------------------------------------------------------ */
	function inputValue( el ) {
		var v = el.value;
		if ( '' === v ) { return ''; }
		if ( 'opacity' === el.dataset.prop ) { return String( Math.max( 0, Math.min( 100, parseFloat( v ) ) ) / 100 ); }
		return v + ( el.dataset.unit && /^-?[\d.]+$/.test( v ) ? el.dataset.unit : '' );
	}
	function syncSiblings( el ) {
		$$( '[data-prop="' + el.dataset.prop + '"]', panel ).forEach( function ( o ) {
			if ( o !== el ) { o.value = 'color' === o.type ? ( /^#[0-9a-f]{6}$/i.test( el.value ) ? el.value : o.value ) : el.value; }
		} );
		var out = el.parentElement && el.parentElement.querySelector( 'output' );
		var lbl = el.closest( '.cebs-f' );
		out = lbl ? lbl.querySelector( 'output' ) : out;
		if ( out ) { out.textContent = el.value + ( el.dataset.unit || '' ); }
	}
	panel.addEventListener( 'input', function ( e ) {
		var el = e.target;
		if ( el.dataset.prop ) {
			syncSiblings( el );
			if ( 'text' === el.type && 'color' !== el.type && /color/.test( el.dataset.prop ) && el.value && ! /^#[0-9a-f]{3,8}$/i.test( el.value ) ) { return; }
			setStyle( el.dataset.prop, inputValue( el ), false );
		} else if ( el.dataset.text ) {
			var src = el.dataset.text;
			var val = el.value;
			var fmt = info && info.text ? info.text.format : 'text';
			if ( 'pipe' === fmt ) { val = val.split( '\n' ).map( function ( s ) { return s.trim(); } ).filter( Boolean ).join( ' | ' ); }
			pending.values[ src ] = val;
			frame.setValue( src, val );
			dirtyText = true;
			updateBar();
		} else if ( el.dataset.link ) {
			pending.values[ el.dataset.link ] = el.value;
			frame.setLink( el.dataset.link, el.value );
			dirtyText = true;
			updateBar();
		} else if ( el.dataset.all ) {
			[ 'top', 'right', 'bottom', 'left' ].forEach( function ( s ) {
				var i = $( '[data-prop="' + el.dataset.all + '-' + s + '"]', panel );
				if ( i ) { i.value = el.value; }
				setStyle( el.dataset.all + '-' + s, '' === el.value ? '' : el.value + 'px', false );
			} );
		}
	} );
	panel.addEventListener( 'change', function ( e ) {
		var el = e.target;
		if ( el.dataset.prop || el.dataset.all ) {
			if ( el.dataset.prop ) { setStyle( el.dataset.prop, inputValue( el ), false ); }
			pushHistory();
			if ( frame ) { frame.reselect(); }
		} else if ( el.dataset.text || el.dataset.link ) {
			pushHistory();
		}
	} );
	panel.addEventListener( 'click', function ( e ) {
		var b = e.target.closest( 'button' );
		if ( ! b ) { return; }
		if ( b.dataset.seg ) {
			if ( '__scope' === b.dataset.seg ) { scope = b.dataset.val; renderPanel(); return; }
			if ( '__target' === b.dataset.seg ) { target = b.dataset.val; renderPanel(); return; }
			setStyle( b.dataset.seg, b.dataset.val, true );
			renderPanel();
			return;
		}
		if ( b.dataset.swatch ) { setStyle( b.dataset.swatch, b.dataset.val, true ); renderPanel(); return; }
		if ( b.dataset.clear ) { setStyle( b.dataset.clear, '', true ); renderPanel(); return; }
		if ( b.dataset.act ) { action( b.dataset.act ); }
	} );

	$$( '.cebs-tabs button' ).forEach( function ( b ) {
		b.addEventListener( 'click', function () { tab = b.dataset.tab; renderPanel(); } );
	} );
	app.addEventListener( 'click', function ( e ) {
		var b = e.target.closest( '.cebs-top [data-act], .cebs-sel [data-act]' );
		if ( b ) { action( b.dataset.act ); }
	} );

	/* İşlemler ---------------------------------------------------------------------- */
	function openMedia( cb ) {
		if ( ! window.wp || ! wp.media ) { toast( 'Ortam kütüphanesi yüklenemedi.', 'error' ); return; }
		var m = wp.media( { title: 'Görsel seç', button: { text: 'Kullan' }, library: { type: 'image' }, multiple: false } );
		m.on( 'select', function () {
			var a = m.state().get( 'selection' ).first().toJSON();
			var url = a.sizes && a.sizes.large ? a.sizes.large.url : a.url;
			attachments[ a.id ] = a.sizes && a.sizes.full ? a.sizes.full.url : a.url;
			cb( a.id, url );
		} );
		m.open();
	}

	function action( act ) {
		switch ( act ) {
			case 'undo': undo(); break;
			case 'redo': redo(); break;
			case 'save': save(); break;
			case 'parent': if ( frame ) { frame.selectParent(); } break;
			case 'deselect': if ( frame ) { frame.deselect(); } break;
			case 'up':
			case 'down':
			case 'duplicate':
			case 'remove':
				if ( ! frame ) { return; }
				if ( 'remove' === act && ! window.confirm( 'Bu bölüm kaldırılacak (kaydedene kadar geri alabilirsiniz). Devam edilsin mi?' ) ) { return; }
				var op = frame.structure( act );
				if ( op ) { pending.ops.push( op ); pushHistory(); frame.reselect(); } else if ( 'remove' !== act ) { toast( 'Bu yönde taşınamaz.', 'warn' ); }
				break;
			case 'image':
				if ( ! info || ! info.image ) { return; }
				var src = info.image.source;
				openMedia( function ( id, url ) { pending.values[ src ] = id; frame.setImage( src, url ); pushHistory(); frame.reselect(); } );
				break;
			case 'bgimage':
				openMedia( function ( id ) {
					setStyle( 'background-image', 'att:' + id, false );
					if ( ! styleValue( 'background-size' ) ) { setStyle( 'background-size', 'cover', false ); }
					if ( ! styleValue( 'background-position' ) ) { setStyle( 'background-position', 'center', false ); }
					pushHistory();
				} );
				break;
			case 'bgnone': setStyle( 'background-image', 'none', true ); break;
			case 'hide': setStyle( 'display', 'none', true ); toast( 'Öğe gizlendi. "Göster" ile geri alabilirsiniz.' ); break;
			case 'show': setStyle( 'display', '', true ); break;
			case 'applycss':
				var txt = $( '.cebs-css', panel ).value;
				var n = 0;
				txt.split( ';' ).forEach( function ( d ) {
					var i = d.indexOf( ':' );
					if ( i > 0 ) { var p = d.slice( 0, i ).trim().toLowerCase(); var v = d.slice( i + 1 ).trim(); if ( /^[a-z-]+$/.test( p ) && v ) { setStyle( p, v, false ); n++; } }
				} );
				pushHistory();
				toast( n + ' özellik uygulandı.' );
				break;
			case 'reset':
				var sel = selector();
				[ 'desktop', 'tablet', 'mobile' ].forEach( function ( d ) { delete pending.styles[ scope ][ d ][ sel ]; } );
				pending.resets.push( { scope: scope, selector: sel } );
				applyCSS();
				pushHistory();
				renderPanel();
				toast( 'Stiller sıfırlandı (kaydedince kalıcı olur).' );
				break;
		}
	}

	function save() {
		var n = countChanges();
		if ( ! n || ! frame ) { return; }
		var btn = $( '[data-act="save"]' );
		btn.disabled = true;
		btn.textContent = 'Kaydediliyor…';
		var fd = new FormData();
		fd.append( 'action', 'ceb_live_save' );
		fd.append( 'nonce', S.nonce );
		fd.append( 'payload', JSON.stringify( { context: frame.data.context, values: pending.values, ops: pending.ops, styles: pending.styles, resets: pending.resets } ) );
		fetch( S.ajax, { method: 'POST', body: fd, credentials: 'same-origin' } )
			.then( function ( r ) { return r.json(); } )
			.then( function ( res ) {
				if ( ! res || ! res.success ) { throw new Error( res && res.data && res.data.message ? res.data.message : 'Kaydedilemedi' ); }
				var d = res.data;
				if ( d.errors && d.errors.length ) {
					toast( '<strong>' + d.applied + ' değişiklik kaydedildi, ' + d.errors.length + ' tanesi kaydedilemedi:</strong><ul>' + d.errors.map( function ( e ) { return '<li>' + esc( e.message ) + '</li>'; } ).join( '' ) + '</ul>', 'warn', 9000 );
				} else {
					toast( '<span class="dashicons dashicons-yes-alt"></span> ' + d.applied + ' değişiklik kaydedildi.', 'success' );
				}
				pending = emptyState();
				history = [];
				hIndex = -1;
				load( currentUrl );
			} )
			.catch( function ( err ) { toast( 'Hata: ' + esc( err.message ), 'error', 6000 ); } )
			.then( function () { btn.textContent = 'Kaydet'; updateBar(); } );
	}

	/* Çerçeve ------------------------------------------------------------------------- */
	function load( url ) {
		currentUrl = url;
		frame = null;
		info = null;
		renderPanel();
		wrap.classList.add( 'is-loading' );
		iframe.src = frameUrl( url );
		var sel = $( '#cebs-page' );
		if ( ! Array.prototype.some.call( sel.options, function ( o ) { return o.value === url; } ) ) {
			var o = document.createElement( 'option' );
			o.value = url;
			o.textContent = url.replace( S.home, '/' );
			sel.insertBefore( o, sel.firstChild );
		}
		sel.value = url;
		$( '[data-exit]' ).href = url;
	}

	window.CEBShell = {
		onReady: function ( api ) {
			frame = api;
			var d = api.data || {};
			saved = { global: d.saved && d.saved.global ? d.saved.global : {}, page: d.saved && d.saved.page ? d.saved.page : {} };
			attachments = Object.assign( {}, d.attachments || {} );
			$( '.cebs-ctx' ).textContent = d.contextLabel || '';
			wrap.classList.remove( 'is-loading' );
			applyCSS();
			history = [];
			hIndex = -1;
			pushHistory();
		},
		onSelect: function ( i ) {
			if ( i && frame ) {
				try { i.similarCount = iframe.contentDocument.querySelectorAll( i.similar ).length; } catch ( e ) { i.similarCount = 1; }
			}
			info = i;
			renderPanel();
		},
		onValue: function ( source, value ) {
			pending.values[ source ] = value;
			dirtyText = true;
			var ta = panel.querySelector( '[data-text="' + CSS.escape( source ) + '"]' );
			if ( ta && document.activeElement !== ta ) {
				var fmt = info && info.text ? info.text.format : 'text';
				ta.value = 'pipe' === fmt ? String( value ).split( '|' ).map( function ( s ) { return s.trim(); } ).join( '\n' ) : value;
			}
			updateBar();
		},
		commit: function () { if ( dirtyText ) { pushHistory(); } },
		action: action
	};

	/* Üst çubuk ----------------------------------------------------------------------- */
	$$( '[data-device]' ).forEach( function ( b ) {
		b.addEventListener( 'click', function () {
			device = b.dataset.device;
			$$( '[data-device]' ).forEach( function ( x ) { x.classList.toggle( 'is-active', x === b ); } );
			wrap.style.width = DEVICES[ device ];
			wrap.classList.toggle( 'is-device', 'desktop' !== device );
			setTimeout( function () { if ( frame ) { frame.reselect(); } renderPanel(); }, 320 );
		} );
	} );
	$( '#cebs-page' ).addEventListener( 'change', function ( e ) {
		if ( countChanges() && ! window.confirm( 'Kaydedilmemiş değişiklikler kaybolacak. Başka sayfaya geçilsin mi?' ) ) { e.target.value = currentUrl; return; }
		pending = emptyState();
		load( e.target.value );
	} );
	$( '[data-exit]' ).addEventListener( 'click', function ( e ) {
		if ( countChanges() && ! window.confirm( 'Kaydedilmemiş değişiklikler kaybolacak. Çıkılsın mı?' ) ) { e.preventDefault(); }
	} );
	document.addEventListener( 'keydown', function ( e ) {
		var mod = e.ctrlKey || e.metaKey;
		if ( ! mod ) { return; }
		var inField = /INPUT|TEXTAREA|SELECT/.test( ( document.activeElement || {} ).tagName || '' );
		var k = e.key.toLowerCase();
		if ( 's' === k ) { e.preventDefault(); save(); }
		if ( ! inField && ( 'z' === k || 'y' === k ) ) { e.preventDefault(); if ( 'y' === k || e.shiftKey ) { redo(); } else { undo(); } }
	} );
	window.addEventListener( 'beforeunload', function ( e ) {
		if ( countChanges() ) { e.preventDefault(); e.returnValue = ''; }
	} );
	iframe.addEventListener( 'load', function () {
		// Çerçeve betiği çalışmadıysa (ör. oturum düşmüş) bilgilendir.
		setTimeout( function () {
			if ( ! frame ) {
				wrap.classList.remove( 'is-loading' );
				toast( 'Bu sayfa canlı editörde açılamadı. Oturumunuzun açık olduğundan ve Can Eloksal temasının etkin olduğundan emin olun.', 'error', 8000 );
			}
		}, 1500 );
	} );

	load( S.url );
	renderPanel();
	updateBar();
}() );
