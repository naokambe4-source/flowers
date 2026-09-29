/* Derin Flowers — yönetim paneli */
( function ( $ ) {
	'use strict';

	var $panel = $( '.df-panel' );
	if ( ! $panel.length ) {
		return;
	}

	/* Sekmeler ------------------------------------------------------------ */
	function openTab( id ) {
		var $tab = $( '.df-panel__tab[data-tab="' + id + '"]' );
		if ( ! $tab.length ) {
			$tab = $( '.df-panel__tab' ).first();
			id = $tab.data( 'tab' );
		}
		$( '.df-panel__tab' ).removeClass( 'is-active' );
		$tab.addClass( 'is-active' );
		$( '.df-panel__pane' ).removeClass( 'is-active' );
		$( '.df-panel__pane[data-pane="' + id + '"]' ).addClass( 'is-active' );
		$( '#df-active-tab' ).val( id );
	}
	$( '.df-panel__tab' ).on( 'click', function ( e ) {
		e.preventDefault();
		var id = $( this ).data( 'tab' );
		openTab( id );
		if ( history.replaceState ) {
			history.replaceState( null, '', '#' + id );
		}
	} );
	openTab( ( location.hash || '' ).replace( '#', '' ) );

	/* Katlanabilir gruplar -------------------------------------------------- */
	$panel.on( 'click', '.df-group--collapsible > .df-group__head', function () {
		$( this ).parent().toggleClass( 'is-collapsed' );
	} );

	/* Bölüm listesinden "Düzenle" → ilgili gruba git */
	$panel.on( 'click', '.df-sections__edit', function ( e ) {
		e.preventDefault();
		var target = $( this ).data( 'target' );
		if ( 'hero' === target ) {
			openTab( 'hero' );
			return;
		}
		var $g = $( '#df-group-' + target );
		if ( ! $g.length ) {
			return;
		}
		$g.removeClass( 'is-collapsed' ).addClass( 'is-highlight' );
		$( 'html, body' ).animate( { scrollTop: $g.offset().top - 60 }, 300 );
		setTimeout( function () {
			$g.removeClass( 'is-highlight' );
		}, 1600 );
	} );

	/* Bölüm aç/kapa + sıralama --------------------------------------------- */
	function reindexSections() {
		$( '.df-sections' ).each( function () {
			var name = $( this ).data( 'name' );
			$( this ).children( '.df-sections__item' ).each( function ( i ) {
				var $li = $( this );
				$li.find( '.df-sections__num' ).text( ( i + 1 < 10 ? '0' : '' ) + ( i + 1 ) );
				$li.find( '.df-sections__id' ).attr( 'name', name + '[' + i + '][id]' );
				$li.find( '.df-sections__off, .df-sections__on' ).attr( 'name', name + '[' + i + '][on]' );
			} );
		} );
	}
	$( '.df-sections' ).sortable( { handle: '.df-sections__handle', axis: 'y', update: reindexSections } );
	$panel.on( 'change', '.df-sections__on', function () {
		var on = this.checked;
		var $li = $( this ).closest( '.df-sections__item' );
		$li.toggleClass( 'is-off', ! on );
		$li.find( '.df-switch__label' ).text( on ? 'Açık' : 'Kapalı' );
		$( '#df-group-' + $li.data( 'id' ) + ' .df-badge' ).toggleClass( 'is-on', on ).toggleClass( 'is-off', ! on ).text( on ? 'Açık' : 'Kapalı' );
	} );

	/* Renk seçici ------------------------------------------------------------ */
	function initColors( $ctx ) {
		$ctx.find( '.df-color' ).each( function () {
			if ( ! $( this ).closest( '.wp-picker-container' ).length ) {
				$( this ).wpColorPicker();
			}
		} );
	}
	initColors( $panel );

	/* Medya seçici ----------------------------------------------------------- */
	$panel.on( 'click', '.df-media__select, .df-media__preview', function ( e ) {
		e.preventDefault();
		var $box = $( this ).closest( '.df-media' );
		var frame = wp.media( {
			title: 'Görsel seç',
			button: { text: 'Bu görseli kullan' },
			library: { type: 'image' },
			multiple: false
		} );
		frame.on( 'select', function () {
			var a = frame.state().get( 'selection' ).first().toJSON();
			var url = ( a.sizes && ( a.sizes.medium || a.sizes.thumbnail ) ) ? ( a.sizes.medium || a.sizes.thumbnail ).url : a.url;
			$box.find( '.df-media__input' ).val( a.id ).trigger( 'change' );
			$box.find( '.df-media__preview' ).html( '<img src="' + url + '" alt="">' );
			$box.addClass( 'has-image' );
			$box.find( '.df-media__remove' ).prop( 'hidden', false );
			$box.find( '.df-media__select' ).text( 'Değiştir' );
			var $item = $box.closest( '.df-repeater__item' );
			if ( $item.length && ! $item.find( '.df-repeater__thumb img' ).length ) {
				$item.find( '.df-repeater__thumb' ).html( '<img src="' + url + '" alt="">' );
			}
		} );
		frame.open();
	} );
	$panel.on( 'click', '.df-media__remove', function ( e ) {
		e.preventDefault();
		var $box = $( this ).closest( '.df-media' );
		$box.find( '.df-media__input' ).val( '' ).trigger( 'change' );
		$box.find( '.df-media__preview' ).html( '<span>Görsel seçilmedi</span>' );
		$box.removeClass( 'has-image' );
		$( this ).prop( 'hidden', true );
		$box.find( '.df-media__select' ).text( 'Görsel seç' );
	} );

	/* İkon seçici önizleme --------------------------------------------------- */
	$panel.on( 'change', '.df-iconpick__select', function () {
		var icons = ( window.DFAdmin && DFAdmin.icons ) || {};
		$( this ).siblings( '.df-iconpick__preview' ).html( icons[ this.value ] || '' );
	} );

	/* Tekrarlayıcı ----------------------------------------------------------- */
	var counter = Date.now();

	function reindexRepeater( $rep ) {
		var base = $rep.data( 'name' );
		$rep.children( '.df-repeater__items' ).children( '.df-repeater__item' ).each( function ( i ) {
			$( this ).find( '[name]' ).each( function () {
				var n = $( this ).attr( 'name' );
				if ( n.indexOf( base + '[' ) === 0 ) {
					var rest = n.substring( base.length );
					rest = rest.replace( /^\[[^\]]+\]/, '[' + i + ']' );
					$( this ).attr( 'name', base + rest );
				}
			} );
		} );
	}

	function initSortable( $ctx ) {
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
				}
			} );
		} );
	}
	initSortable( $panel );

	$panel.on( 'click', '.df-repeater__add', function ( e ) {
		e.preventDefault();
		var $rep = $( this ).closest( '.df-repeater' );
		var tpl = $rep.children( '.df-repeater__tpl' ).html().replace( /__i__/g, counter++ );
		var $item = $( tpl ).removeClass( 'is-collapsed' );
		$rep.children( '.df-repeater__items' ).append( $item );
		initColors( $item );
		reindexRepeater( $rep );
		$( document.body ).trigger( 'wc-enhanced-select-init' );
	} );

	$panel.on( 'click', '.df-repeater__remove', function ( e ) {
		e.preventDefault();
		e.stopPropagation();
		if ( ! window.confirm( 'Bu öğe silinsin mi?' ) ) {
			return;
		}
		var $rep = $( this ).closest( '.df-repeater' );
		$( this ).closest( '.df-repeater__item' ).remove();
		reindexRepeater( $rep );
	} );

	$panel.on( 'click', '.df-repeater__dup', function ( e ) {
		e.preventDefault();
		e.stopPropagation();
		var $item = $( this ).closest( '.df-repeater__item' );
		var $rep = $item.closest( '.df-repeater' );
		var $clone = $item.clone();
		$clone.find( '.wp-picker-container' ).each( function () {
			var $input = $( this ).find( '.df-color' ).clone().removeAttr( 'style' ).removeClass( 'wp-color-picker' );
			$( this ).replaceWith( $input );
		} );
		// Klonlanan select değerlerini koru.
		$item.find( 'select' ).each( function ( i ) {
			$clone.find( 'select' ).eq( i ).val( $( this ).val() );
		} );
		$item.after( $clone );
		initColors( $clone );
		reindexRepeater( $rep );
	} );

	$panel.on( 'click', '.df-repeater__bar', function ( e ) {
		if ( $( e.target ).closest( '.df-repeater__remove, .df-repeater__dup, .df-repeater__handle' ).length ) {
			return;
		}
		$( this ).closest( '.df-repeater__item' ).toggleClass( 'is-collapsed' );
	} );

	/* Başlık alanı değişince öğe başlığını güncelle */
	$panel.on( 'input', '.df-repeater__body input[type=text], .df-repeater__body textarea', function () {
		var $rep = $( this ).closest( '.df-repeater' );
		var tf = $rep.data( 'title-field' );
		if ( ! tf ) {
			return;
		}
		var name = $( this ).attr( 'name' ) || '';
		if ( name.slice( -( tf.length + 2 ) ) === '[' + tf + ']' ) {
			$( this ).closest( '.df-repeater__item' ).find( '> .df-repeater__bar .df-repeater__title' ).text( this.value.replace( /\s+/g, ' ' ).slice( 0, 70 ) || 'Öğe' );
		}
	} );

	/* Kaydedilmemiş değişiklik uyarısı */
	var dirty = false;
	$( '#df-panel-form' ).on( 'change input', ':input', function () {
		dirty = true;
	} ).on( 'submit', function () {
		dirty = false;
	} );
	$( window ).on( 'beforeunload', function () {
		return dirty ? 'Kaydedilmemiş değişiklikler var.' : undefined;
	} );
} )( jQuery );
