/* Derin Flowers — Tasarım Stüdyosu */
( function ( $ ) {
	'use strict';

	var S = window.DFStudio;
	var $side = $( '#dfs-side' );
	var $form = $( '#dfs-form' );
	var frame = document.getElementById( 'dfs-frame' );
	var pending = null;
	if ( ! S || ! $form.length || ! frame ) {
		return;
	}
	var origin = window.location.origin;
	var $status = $( '#dfs-status' );
	var $publish = $( '#dfs-publish' );
	var knownKeys = {};
	var scrollY = 0;
	var restoreScroll = false;
	var saving = false;
	var queued = null;
	var changed = !! S.changed;
	var history = [];
	var hIndex = -1;

	/* ================= Yardımcılar ================= */
	function nameOf( key ) {
		var p = String( key ).split( '.' );
		return S.option + '[' + p.join( '][' ) + ']';
	}
	function keyOf( name ) {
		var m = String( name || '' ).replace( S.option, '' ).match( /\[([^\]]*)\]/g );
		return m ? m.map( function ( x ) {
			return x.slice( 1, -1 );
		} ).join( '.' ) : '';
	}
	function withParams( url ) {
		var u = new URL( url, S.home );
		u.searchParams.set( 'df_live', '1' );
		u.searchParams.set( 'df_preview', '1' );
		return u.toString();
	}
	function post( msg ) {
		if ( frame.contentWindow ) {
			frame.contentWindow.postMessage( msg, origin );
		}
	}
	function status( text, cls ) {
		$status.text( text ).attr( 'class', 'dfs-status' + ( cls ? ' is-' + cls : '' ) );
	}
	function paintPublish() {
		$publish.prop( 'disabled', ! changed ).text( changed ? 'Yayınla' : 'Yayında ✓' );
	}

	/* ================= Önizleme ================= */
	// Yenileme arka planda ikinci bir çerçevede yapılır; hazır olunca ve kaydırma
	// konumu geri yüklenince yer değiştirilir. Böylece sayfa zıplamaz, beyaz yanıp sönmez.
	function loadFrame( url, keepScroll ) {
		restoreScroll = !! keepScroll;
		if ( ! frame.getAttribute( 'src' ) ) {
			$( '#dfs-loading' ).addClass( 'is-on' );
			frame.src = withParams( url );
			return;
		}
		if ( pending ) {
			pending.remove();
		}
		if ( ! keepScroll ) {
			$( '#dfs-loading' ).addClass( 'is-on' );
		} else {
			$( '#dfs-stage' ).addClass( 'is-refreshing' );
		}
		pending = document.createElement( 'iframe' );
		pending.className = 'dfs-frame is-next';
		pending.title = 'Site önizlemesi';
		pending.src = withParams( url );
		frame.parentNode.insertBefore( pending, frame.nextSibling );
	}
	function swapFrame() {
		var old = frame;
		frame = pending;
		pending = null;
		frame.classList.remove( 'is-next' );
		frame.id = 'dfs-frame';
		old.remove();
		$( '#dfs-stage' ).removeClass( 'is-refreshing' );
	}
	function reloadFrame() {
		try {
			loadFrame( frame.contentWindow.location.href, true );
		} catch ( e ) {
			loadFrame( $( '#dfs-page' ).val() || S.home, true );
		}
	}
	loadFrame( frame.getAttribute( 'data-src' ) || S.home, false );

	$( '#dfs-page' ).on( 'change', function () {
		if ( this.value ) {
			loadFrame( this.value, false );
		}
	} );
	$( '.dfs-devices' ).on( 'click', 'button', function () {
		$( '.dfs-devices button' ).removeClass( 'is-active' );
		$( this ).addClass( 'is-active' );
		$( '#dfs-frame-wrap' ).attr( 'data-device', $( this ).data( 'device' ) );
	} );

	/* ================= Sekmeler & gruplar ================= */
	function view( name ) {
		$( '.dfs-tabs button' ).removeClass( 'is-active' ).filter( '[data-view="' + name + '"]' ).addClass( 'is-active' );
		$( '.dfs-view' ).removeClass( 'is-active' ).filter( '[data-view="' + name + '"]' ).addClass( 'is-active' );
		$side.find( '.dfs-view.is-active' ).scrollTop( 0 );
	}
	$( '.dfs-tabs' ).on( 'click', 'button', function () {
		view( $( this ).data( 'view' ) );
	} );
	$( '#dfs-back' ).on( 'click', function () {
		view( $( '.dfs-groups' ).data( 'from' ) || 'sections' );
	} );

	function showGroups( $groups, title, from ) {
		$( '.dfs-group' ).prop( 'hidden', true );
		if ( ! $groups.length ) {
			return false;
		}
		$groups.prop( 'hidden', false );
		$( '#dfs-edit-title' ).text( title || $groups.first().data( 'title' ) );
		$( '#dfs-edit-empty' ).prop( 'hidden', true );
		$( '.dfs-groups' ).data( 'from', from || 'sections' );
		initWidgets( $groups );
		view( 'edit' );
		return true;
	}
	function openTarget( target, title, from ) {
		var $g = $( '.dfs-group[data-g="' + target + '"]' );
		if ( ! $g.length ) {
			$g = $( '.dfs-group[data-tab="' + target + '"]' );
		}
		if ( ! $g.length ) {
			$g = $( '.dfs-group[data-section="' + target + '"]' );
		}
		return showGroups( $g, title, from );
	}
	function openSection( id ) {
		var label = S.labels[ id ] || id;
		if ( openTarget( id, label, 'sections' ) ) {
			post( { df: 'show', id: id } );
		}
	}
	function openField( key, focusInput ) {
		var base = String( key ).split( '.' )[ 0 ];
		var $g = $( '.dfs-group' ).filter( function () {
			return ( ' ' + $( this ).data( 'fields' ) + ' ' ).indexOf( ' ' + base + ' ' ) > -1;
		} );
		if ( ! $g.length ) {
			return;
		}
		if ( $g.is( ':hidden' ) || ! $( '.dfs-view[data-view="edit"]' ).hasClass( 'is-active' ) ) {
			showGroups( $g.first(), null, 'sections' );
		}
		var $input = $form.find( '[name="' + nameOf( key ) + '"]' ).not( '[type=hidden]' ).first();
		if ( ! $input.length ) {
			$input = $form.find( '[name="' + nameOf( key ) + '"]' ).first();
		}
		$input.closest( '.df-repeater__item' ).removeClass( 'is-collapsed' );
		var $field = $input.closest( '.df-field' );
		if ( $field.length ) {
			$( '.df-field.is-target' ).removeClass( 'is-target' );
			$field.addClass( 'is-target' );
			var $v = $( '.dfs-view[data-view="edit"]' );
			$v.stop().animate( { scrollTop: $v.scrollTop() + $field.position().top - 90 }, 250 );
			if ( focusInput ) {
				$input.trigger( 'focus' );
			}
		}
	}
	$side.on( 'click', '[data-open]', function ( e ) {
		e.preventDefault();
		openTarget( String( $( this ).data( 'open' ) ), $( this ).clone().children().remove().end().text().trim(), $( this ).closest( '.dfs-view' ).data( 'view' ) );
	} );
	$side.on( 'click', '.df-sections__edit', function ( e ) {
		e.preventDefault();
		openSection( $( this ).data( 'target' ) );
	} );
	$side.on( 'click', '.df-sections__label', function () {
		post( { df: 'show', id: $( this ).closest( '.df-sections__item' ).data( 'id' ) } );
	} );

	/* ================= Alan bileşenleri ================= */
	function initColors( $ctx ) {
		$ctx.find( '.df-color' ).each( function () {
			var $i = $( this );
			if ( $i.closest( '.wp-picker-container' ).length ) {
				return;
			}
			$i.wpColorPicker( {
				change: function () {
					setTimeout( function () {
						$i.trigger( 'dfs:change' );
					}, 10 );
				},
				clear: function () {
					setTimeout( function () {
						$i.trigger( 'dfs:change' );
					}, 10 );
				}
			} );
		} );
	}
	function reindexRepeater( $rep ) {
		var base = $rep.data( 'name' );
		$rep.children( '.df-repeater__items' ).children( '.df-repeater__item' ).each( function ( i ) {
			$( this ).find( '[name]' ).each( function () {
				var n = $( this ).attr( 'name' );
				if ( n.indexOf( base + '[' ) === 0 ) {
					$( this ).attr( 'name', base + n.substring( base.length ).replace( /^\[[^\]]+\]/, '[' + i + ']' ) );
				}
			} );
		} );
	}
	function reindexSections() {
		$side.find( '.df-sections' ).each( function () {
			var name = $( this ).data( 'name' );
			$( this ).children( '.df-sections__item' ).each( function ( i ) {
				var $li = $( this );
				$li.find( '.df-sections__num' ).text( ( i < 9 ? '0' : '' ) + ( i + 1 ) );
				$li.find( '.df-sections__id' ).attr( 'name', name + '[' + i + '][id]' );
				$li.find( '.df-sections__off, .df-sections__on' ).attr( 'name', name + '[' + i + '][on]' );
			} );
		} );
	}
	function initWidgets( $ctx ) {
		initColors( $ctx );
		$ctx.find( '.df-repeater__items' ).each( function () {
			var $items = $( this );
			if ( $items.data( 'ui-sortable' ) ) {
				return;
			}
			$items.sortable( {
				handle: '.df-repeater__handle',
				axis: 'y',
				update: function () {
					reindexRepeater( $items.closest( '.df-repeater' ) );
					save( true );
				}
			} );
		} );
		if ( $ctx.find( '.wc-product-search' ).length ) {
			$( document.body ).trigger( 'wc-enhanced-select-init' );
		}
	}
	function initSections() {
		$side.find( '.df-sections' ).each( function () {
			if ( $( this ).data( 'ui-sortable' ) ) {
				return;
			}
			$( this ).sortable( {
				handle: '.df-sections__handle',
				axis: 'y',
				update: function () {
					reindexSections();
					save( true );
				}
			} );
		} );
	}
	initSections();

	// Medya
	function pickImage( $box, done ) {
		var fr = wp.media( { title: 'Görsel seç', button: { text: 'Bu görseli kullan' }, library: { type: 'image' }, multiple: false } );
		fr.on( 'select', function () {
			var a = fr.state().get( 'selection' ).first().toJSON();
			var url = ( a.sizes && ( a.sizes.medium || a.sizes.thumbnail ) ) ? ( a.sizes.medium || a.sizes.thumbnail ).url : a.url;
			$box.find( '.df-media__input' ).val( a.id );
			$box.find( '.df-media__preview' ).html( '<img src="' + url + '" alt="">' );
			$box.addClass( 'has-image' ).find( '.df-media__remove' ).prop( 'hidden', false );
			$box.find( '.df-media__select' ).text( 'Değiştir' );
			var $item = $box.closest( '.df-repeater__item' );
			if ( $item.length && ! $item.find( '.df-repeater__thumb img' ).length ) {
				$item.find( '.df-repeater__thumb' ).html( '<img src="' + url + '" alt="">' );
			}
			if ( done ) {
				done();
			}
			save( true );
		} );
		fr.open();
	}
	$side.on( 'click', '.df-media__select, .df-media__preview', function ( e ) {
		e.preventDefault();
		if ( $( this ).closest( '.dfs-context' ).length ) {
			return;
		}
		pickImage( $( this ).closest( '.df-media' ) );
	} );
	$side.on( 'click', '.df-media__remove', function ( e ) {
		e.preventDefault();
		if ( $( this ).closest( '.dfs-context' ).length ) {
			return;
		}
		var $box = $( this ).closest( '.df-media' );
		$box.find( '.df-media__input' ).val( '' );
		$box.find( '.df-media__preview' ).html( '<span>Görsel seçilmedi</span>' );
		$box.removeClass( 'has-image' );
		$( this ).prop( 'hidden', true );
		$box.find( '.df-media__select' ).text( 'Görsel seç' );
		save( true );
	} );
	$side.on( 'change', '.df-iconpick__select', function () {
		$( this ).siblings( '.df-iconpick__preview' ).html( S.icons[ this.value ] || '' );
	} );

	// Tekrarlayıcı
	var counter = Date.now();
	$side.on( 'click', '.df-repeater__add', function ( e ) {
		e.preventDefault();
		var $rep = $( this ).closest( '.df-repeater' );
		var $item = $( $rep.children( '.df-repeater__tpl' ).html().replace( /__i__/g, counter++ ) ).removeClass( 'is-collapsed' );
		$rep.children( '.df-repeater__items' ).append( $item );
		initWidgets( $item );
		reindexRepeater( $rep );
		save( true );
	} );
	$side.on( 'click', '.df-repeater__remove', function ( e ) {
		e.preventDefault();
		e.stopPropagation();
		if ( ! window.confirm( 'Bu öğe silinsin mi?' ) ) {
			return;
		}
		var $rep = $( this ).closest( '.df-repeater' );
		$( this ).closest( '.df-repeater__item' ).remove();
		reindexRepeater( $rep );
		save( true );
	} );
	$side.on( 'click', '.df-repeater__dup', function ( e ) {
		e.preventDefault();
		e.stopPropagation();
		var $item = $( this ).closest( '.df-repeater__item' );
		var $clone = $item.clone();
		$clone.find( '.wp-picker-container' ).each( function () {
			$( this ).replaceWith( $( this ).find( '.df-color' ).clone().removeAttr( 'style' ).removeClass( 'wp-color-picker' ) );
		} );
		$item.find( 'select' ).each( function ( i ) {
			$clone.find( 'select' ).eq( i ).val( $( this ).val() );
		} );
		$item.after( $clone );
		initWidgets( $clone );
		reindexRepeater( $item.closest( '.df-repeater' ) );
		save( true );
	} );
	$side.on( 'click', '.df-repeater__bar', function ( e ) {
		if ( ! $( e.target ).closest( '.df-repeater__remove, .df-repeater__dup, .df-repeater__handle' ).length ) {
			$( this ).closest( '.df-repeater__item' ).toggleClass( 'is-collapsed' );
		}
	} );
	$side.on( 'input', '.df-repeater__body input[type=text], .df-repeater__body textarea', function () {
		var tf = $( this ).closest( '.df-repeater' ).data( 'title-field' );
		var name = $( this ).attr( 'name' ) || '';
		if ( tf && name.slice( -( tf.length + 2 ) ) === '[' + tf + ']' ) {
			$( this ).closest( '.df-repeater__item' ).find( '> .df-repeater__bar .df-repeater__title' ).text( this.value.replace( /\s+/g, ' ' ).slice( 0, 70 ) || 'Öğe' );
		}
	} );

	// Bölüm aç/kapa
	$side.on( 'change', '.df-sections__on', function () {
		var $li = $( this ).closest( '.df-sections__item' );
		$li.toggleClass( 'is-off', ! this.checked ).find( '.df-switch__label' ).text( this.checked ? 'Açık' : 'Kapalı' );
	} );

	/* ================= Değişiklik → taslak ================= */
	var textTimer;
	$form.on( 'input', 'input[type=text]:not(.df-color):not(.df-media__input):not([data-q]), textarea:not([data-q])', function () {
		var key = keyOf( this.name );
		post( { df: 'setText', key: key, value: this.value } );
		clearTimeout( textTimer );
		textTimer = setTimeout( function () {
			save( ! knownKeys[ key ] );
		}, 900 );
	} );
	$form.on( 'change', 'input:not([data-q]), select:not([data-q]), textarea:not([data-q])', function ( e ) {
		if ( $( this ).is( 'input[type=text]:not(.df-color), textarea' ) ) {
			return; // Yazı alanları "input" olayıyla kaydedilir.
		}
		if ( e.isTrigger && $( this ).hasClass( 'df-media__input' ) ) {
			return;
		}
		save( true );
	} );
	$form.on( 'dfs:change', '.df-color', function () {
		save( true );
	} );

	function snapshot() {
		return $form.serialize();
	}
	function save( reload, fromHistory ) {
		if ( saving ) {
			queued = { reload: reload || ( queued && queued.reload ) };
			return;
		}
		var data = snapshot();
		if ( ! fromHistory ) {
			if ( history[ hIndex ] === data ) {
				if ( reload ) {
					reloadFrame();
				}
				return;
			}
			history = history.slice( 0, hIndex + 1 );
			history.push( data );
			hIndex = history.length - 1;
			paintHistory();
		}
		saving = true;
		status( 'Kaydediliyor…', 'busy' );
		$.post( S.ajax, { action: 'df_studio_draft', nonce: S.nonce, form: data } ).done( function ( res ) {
			if ( res && res.success ) {
				changed = res.data.changed;
				status( changed ? 'Taslak kaydedildi · yayınlanmadı' : 'Tüm değişiklikler yayında', changed ? 'draft' : 'ok' );
				paintPublish();
				if ( reload ) {
					reloadFrame();
				}
			} else {
				status( ( res && res.data && res.data.message ) || 'Kaydedilemedi', 'err' );
			}
		} ).fail( function () {
			status( 'Bağlantı hatası, tekrar deneyin', 'err' );
		} ).always( function () {
			saving = false;
			if ( queued ) {
				var q = queued;
				queued = null;
				save( q.reload );
			}
		} );
	}

	/* ================= Geri al / ileri al ================= */
	function paintHistory() {
		$( '#dfs-undo' ).prop( 'disabled', hIndex <= 0 );
		$( '#dfs-redo' ).prop( 'disabled', hIndex >= history.length - 1 );
	}
	function applyHistory( i ) {
		if ( i < 0 || i >= history.length || saving ) {
			return;
		}
		hIndex = i;
		paintHistory();
		saving = true;
		status( 'Geri yükleniyor…', 'busy' );
		$.post( S.ajax, { action: 'df_studio_draft', nonce: S.nonce, form: history[ i ] } ).then( function ( res ) {
			changed = res && res.success ? res.data.changed : changed;
			return $.post( S.ajax, { action: 'df_studio_form', nonce: S.nonce } );
		} ).done( function ( res ) {
			if ( res && res.success ) {
				var visible = $( '.dfs-group:not([hidden])' ).map( function () {
					return $( this ).data( 'g' );
				} ).get();
				$( '.dfs-groups' ).html( res.data.groups );
				$side.find( '.df-sections' ).replaceWith( res.data.sections );
				initSections();
				visible.forEach( function ( g ) {
					$( '.dfs-group[data-g="' + g + '"]' ).prop( 'hidden', false );
				} );
				initWidgets( $( '.dfs-group:not([hidden])' ) );
				status( changed ? 'Taslak kaydedildi · yayınlanmadı' : 'Tüm değişiklikler yayında', changed ? 'draft' : 'ok' );
				paintPublish();
				reloadFrame();
			}
		} ).always( function () {
			saving = false;
		} );
	}
	$( '#dfs-undo' ).on( 'click', function () {
		applyHistory( hIndex - 1 );
	} );
	$( '#dfs-redo' ).on( 'click', function () {
		applyHistory( hIndex + 1 );
	} );
	$( document ).on( 'keydown', function ( e ) {
		if ( ! ( e.ctrlKey || e.metaKey ) || $( e.target ).is( 'input, textarea, select, [contenteditable]' ) ) {
			return;
		}
		var k = e.key.toLowerCase();
		if ( 'z' === k && ! e.shiftKey ) {
			e.preventDefault();
			applyHistory( hIndex - 1 );
		} else if ( 'y' === k || ( 'z' === k && e.shiftKey ) ) {
			e.preventDefault();
			applyHistory( hIndex + 1 );
		}
	} );

	/* ================= Yayınla / vazgeç ================= */
	$publish.on( 'click', function () {
		if ( saving ) {
			return;
		}
		$publish.prop( 'disabled', true ).text( 'Yayınlanıyor…' );
		$.post( S.ajax, { action: 'df_studio_publish', nonce: S.nonce } ).done( function ( res ) {
			if ( res && res.success ) {
				changed = false;
				status( 'Yayınlandı ✓ Ziyaretçiler yeni hali görüyor', 'ok' );
			} else {
				status( ( res && res.data && res.data.message ) || 'Yayınlanamadı', 'err' );
			}
			paintPublish();
		} ).fail( function () {
			status( 'Yayınlanamadı, tekrar deneyin', 'err' );
			paintPublish();
		} );
	} );
	$( '#dfs-discard' ).on( 'click', function () {
		if ( ! changed ) {
			status( 'Vazgeçilecek değişiklik yok', 'ok' );
			return;
		}
		if ( ! window.confirm( 'Yayınlanmamış tüm değişiklikler silinsin mi?' ) ) {
			return;
		}
		$.post( S.ajax, { action: 'df_studio_discard', nonce: S.nonce } ).always( function () {
			changed = false;
			window.location.reload();
		} );
	} );
	window.addEventListener( 'beforeunload', function ( e ) {
		if ( saving || queued ) {
			e.preventDefault();
			e.returnValue = '';
		}
	} );

	function onReady( m, swapped ) {
		$( '#dfs-loading' ).removeClass( 'is-on' );
		knownKeys = {};
		try {
			Array.prototype.forEach.call( frame.contentDocument.querySelectorAll( '[data-df-edit]' ), function ( el ) {
				knownKeys[ el.getAttribute( 'data-df-edit' ) ] = true;
			} );
		} catch ( err ) {}
		if ( restoreScroll && ! swapped ) {
			post( { df: 'scrollTo', y: scrollY } );
		} else if ( ! restoreScroll ) {
			scrollY = 0;
		}
		restoreScroll = false;
		var $sel = $( '#dfs-page' );
		var match = $sel.find( 'option' ).filter( function () {
			return this.value && this.value.replace( /\/$/, '' ) === m.url.replace( /\/$/, '' );
		} );
		if ( match.length ) {
			$sel.val( match.val() );
		} else {
			$sel.find( '[data-custom]' ).text( 'Bu sayfa: ' + ( m.title || '' ).split( /[–|-]/ )[ 0 ].trim() ).val( m.url ).prop( 'selected', true );
		}
		$( '#dfs-open' ).attr( 'href', m.url );
		renderContext( m.context || {} );
	}

	/* ================= Önizlemeden gelen mesajlar ================= */
	window.addEventListener( 'message', function ( e ) {
		if ( e.origin !== origin || ! e.data || ! e.data.df ) {
			return;
		}
		var m = e.data;
		if ( pending && e.source === pending.contentWindow ) {
			if ( 'ready' !== m.df ) {
				return;
			}
			if ( restoreScroll ) {
				pending.contentWindow.postMessage( { df: 'scrollTo', y: scrollY }, origin );
			}
			setTimeout( function () {
				swapFrame();
				onReady( m, true );
			}, restoreScroll ? 120 : 0 );
			return;
		}
		if ( e.source !== frame.contentWindow ) {
			return;
		}
		switch ( m.df ) {
			case 'ready':
				onReady( m, false );
				break;
			case 'scroll':
				scrollY = m.y;
				break;
			case 'focus':
				openField( m.key, false );
				break;
			case 'edit':
				var $in = $form.find( '[name="' + nameOf( m.key ) + '"]' );
				if ( $in.length ) {
					$in.val( m.value );
					clearTimeout( textTimer );
					textTimer = setTimeout( function () {
						save( false );
					}, 900 );
				}
				break;
			case 'image':
				openField( m.key, false );
				var $box = $form.find( '[name="' + nameOf( m.key ) + '"]' ).closest( '.df-media' );
				if ( $box.length ) {
					pickImage( $box );
				}
				break;
			case 'section':
				var $li = $side.find( '.df-sections__item[data-id="' + m.id + '"]' );
				if ( 'edit' === m.act ) {
					openSection( m.id );
				} else if ( 'up' === m.act && $li.prev().length ) {
					$li.insertBefore( $li.prev() );
					reindexSections();
					save( true );
				} else if ( 'down' === m.act && $li.next().length ) {
					$li.insertAfter( $li.next() );
					reindexSections();
					save( true );
				} else if ( 'toggle' === m.act ) {
					$li.find( '.df-sections__on' ).prop( 'checked', ! $li.find( '.df-sections__on' ).prop( 'checked' ) ).trigger( 'change' );
				}
				break;
		}
	} );

	/* ================= Sayfa sekmesi: bu sayfa ================= */
	var ctx = {};
	function esc( v ) {
		return $( '<div>' ).text( null === v || undefined === v ? '' : String( v ) ).html();
	}
	var kinds = { product: 'Ürün', term: 'Kategori', post: 'Sayfa', wc: 'Mağaza sayfası', other: 'Sayfa' };
	function renderContext( c ) {
		ctx = c || {};
		var home = 'home' === ctx.type;
		$( '#dfs-home' ).prop( 'hidden', ! home );
		var $c = $( '#dfs-context' ).empty().prop( 'hidden', home );
		if ( home ) {
			return;
		}
		var html = '<div class="dfs-ctx-head"><span>' + esc( ctx.kind || kinds[ ctx.type ] || 'Sayfa' ) + '</span><h2>' + esc( ctx.title ) + '</h2></div>';
		html += '<p class="dfs-help">Önizlemede <strong>kesik çizgili</strong> yazılara tıklayıp doğrudan yazabilirsiniz (header, footer ve bu sayfanın metinleri).</p>';
		if ( 'product' === ctx.type || 'term' === ctx.type || ( 'post' === ctx.type && ! ctx.managed ) ) {
			html += '<div class="dfs-group dfs-quick"><div class="df-fields">';
			html += field( 'Başlık', '<input type="text" class="df-input" data-q="title" value="' + esc( ctx.title ) + '">' );
			if ( 'product' === ctx.type ) {
				if ( ctx.variable ) {
					html += '<p class="df-group__desc">Seçenekli (varyasyonlu) ürün: fiyatları ürün düzenleyicisinden değiştirin.</p>';
				} else {
					html += field( 'Normal fiyat (₺)', '<input type="text" inputmode="decimal" class="df-input" data-q="regular" value="' + esc( ctx.regular ) + '">', 'half' );
					html += field( 'İndirimli fiyat (₺)', '<input type="text" inputmode="decimal" class="df-input" data-q="sale" value="' + esc( ctx.sale ) + '" placeholder="Yok">', 'half' );
				}
				html += field( 'Stok durumu', '<select class="df-input" data-q="stock"><option value="instock"' + ( 'instock' === ctx.stock ? ' selected' : '' ) + '>Stokta</option><option value="outofstock"' + ( 'outofstock' === ctx.stock ? ' selected' : '' ) + '>Tükendi</option><option value="onbackorder"' + ( 'onbackorder' === ctx.stock ? ' selected' : '' ) + '>Siparişle</option></select>' );
				html += field( 'Kısa açıklama', '<textarea class="df-input" rows="4" data-q="short">' + esc( ctx.short ) + '</textarea>' );
			}
			if ( 'term' === ctx.type ) {
				html += field( 'Açıklama', '<textarea class="df-input" rows="4" data-q="desc">' + esc( ctx.desc ) + '</textarea>' );
			}
			if ( 'product' === ctx.type || ( 'term' === ctx.type && null !== ctx.image && undefined !== ctx.image ) ) {
				html += field( 'product' === ctx.type ? 'Ana görsel' : 'Kategori görseli', '<div class="df-media' + ( ctx.imageUrl ? ' has-image' : '' ) + '" data-qmedia><input type="hidden" data-q="image" value="' + esc( ctx.image || '' ) + '"><div class="df-media__preview">' + ( ctx.imageUrl ? '<img src="' + esc( ctx.imageUrl ) + '" alt="">' : '<span>Görsel seçilmedi</span>' ) + '</div><div class="df-media__actions"><button type="button" class="button" data-qpick>' + ( ctx.imageUrl ? 'Değiştir' : 'Görsel seç' ) + '</button></div></div>' );
			}
			html += '</div><button type="button" class="dfs-btn dfs-btn--block" id="dfs-quick-save">Kaydet</button><p class="dfs-note">Ürün ve kategori değişiklikleri <strong>hemen</strong> yayına girer.</p></div>';
		}
		html += '<div class="dfs-nav">';
		if ( ctx.edit ) {
			html += '<button type="button" data-editor="' + esc( ctx.edit ) + '">' + ( 'product' === ctx.type ? 'Ürünü tam düzenle (galeri, video, SSS…)' : ( 'term' === ctx.type ? 'Kategoriyi tam düzenle' : 'Sayfa içeriğini düzenle' ) ) + '<span class="dashicons dashicons-edit"></span></button>';
		}
		if ( ctx.settings ) {
			html += '<button type="button" data-open="' + esc( ctx.settings ) + '">' + ( ctx.managed ? 'Bu sayfanın yazıları ve görselleri' : 'Bu sayfa türünün tema ayarları' ) + '<span class="dashicons dashicons-arrow-right-alt2"></span></button>';
		}
		html += '<button type="button" data-open="header">Header & menü<span class="dashicons dashicons-arrow-right-alt2"></span></button>';
		html += '<button type="button" data-open="footer">Footer & iletişim<span class="dashicons dashicons-arrow-right-alt2"></span></button>';
		html += '</div>';
		$c.html( html );
	}
	function field( label, input, width ) {
		return '<div class="df-field' + ( width ? ' df-field--' + width : '' ) + '"><label class="df-field__label">' + label + '</label>' + input + '</div>';
	}
	$side.on( 'click', '[data-qpick]', function ( e ) {
		e.preventDefault();
		var $box = $( this ).closest( '[data-qmedia]' );
		var fr = wp.media( { title: 'Görsel seç', button: { text: 'Bu görseli kullan' }, library: { type: 'image' }, multiple: false } );
		fr.on( 'select', function () {
			var a = fr.state().get( 'selection' ).first().toJSON();
			var url = a.sizes && a.sizes.thumbnail ? a.sizes.thumbnail.url : a.url;
			$box.find( '[data-q="image"]' ).val( a.id ).attr( 'data-dirty', '1' );
			$box.addClass( 'has-image' ).find( '.df-media__preview' ).html( '<img src="' + url + '" alt="">' );
		} );
		fr.open();
	} );
	$side.on( 'click', '#dfs-quick-save', function () {
		var $b = $( this );
		var f = {};
		$( '#dfs-context [data-q]' ).each( function () {
			if ( 'image' !== $( this ).data( 'q' ) || $( this ).attr( 'data-dirty' ) ) {
				f[ $( this ).data( 'q' ) ] = $( this ).val();
			}
		} );
		$b.prop( 'disabled', true ).text( 'Kaydediliyor…' );
		$.post( S.ajax, { action: 'df_studio_quick', nonce: S.nonce, type: ctx.type, id: ctx.id, taxonomy: ctx.taxonomy || '', f: f } ).done( function ( res ) {
			if ( res && res.success ) {
				status( res.data.message + ' ✓', 'ok' );
				reloadFrame();
			} else {
				status( ( res && res.data && res.data.message ) || 'Kaydedilemedi', 'err' );
			}
		} ).fail( function () {
			status( 'Kaydedilemedi', 'err' );
		} ).always( function () {
			$b.prop( 'disabled', false ).text( 'Kaydet' );
		} );
	} );

	/* Tam editör: stüdyodan çıkmadan pencere içinde */
	$side.on( 'click', '[data-editor]', function () {
		$( '#dfs-modal-title' ).text( $( this ).clone().children().remove().end().text().trim() );
		$( '#dfs-modal-frame' ).attr( 'src', $( this ).data( 'editor' ) );
		$( '#dfs-modal' ).prop( 'hidden', false );
	} );
	$( '#dfs-modal-close' ).on( 'click', function () {
		$( '#dfs-modal' ).prop( 'hidden', true );
		$( '#dfs-modal-frame' ).attr( 'src', 'about:blank' );
		reloadFrame();
	} );

	/* ================= Başlangıç ================= */
	history.push( snapshot() );
	hIndex = 0;
	paintHistory();
	paintPublish();
	status( changed ? 'Önceki oturumdan yayınlanmamış değişiklikler var' : 'Değişiklikler önce taslak olarak kaydedilir', changed ? 'draft' : '' );
}( jQuery ) );
