/**
 * Can Eloksal — yönetim paneli etkileşimleri.
 * Görsel/galeri seçici, tekrarlayıcı satırlar, sürükle-bırak sıralama, ikon önizleme,
 * SMTP testi ve silme onayları.
 */
( function ( $ ) {
	'use strict';

	var A = window.CEAdmin || {};

	/* Tekil görsel ------------------------------------------------------------- */
	$( document ).on( 'click', '.ce-media__select', function ( e ) {
		e.preventDefault();
		var $wrap = $( this ).closest( '.ce-media' );
		var frame = wp.media( { title: A.choose || 'Görsel seç', button: { text: A.use || 'Kullan' }, library: { type: 'image' }, multiple: false } );
		frame.on( 'select', function () {
			var att = frame.state().get( 'selection' ).first().toJSON();
			var url = att.sizes && att.sizes.medium ? att.sizes.medium.url : att.url;
			$wrap.find( '.ce-media__input' ).val( att.id ).trigger( 'change' );
			$wrap.find( '.ce-media__preview' ).first().html( $( '<img alt="">' ).attr( 'src', url ) );
			$wrap.addClass( 'has-image' );
		} );
		frame.open();
	} );
	$( document ).on( 'click', '.ce-media__remove', function ( e ) {
		e.preventDefault();
		var $wrap = $( this ).closest( '.ce-media' );
		$wrap.find( '.ce-media__input' ).val( '' ).trigger( 'change' );
		$wrap.find( '.ce-media__preview' ).first().html( '<span class="dashicons dashicons-format-image" aria-hidden="true"></span><span>Görsel seç</span>' );
		$wrap.removeClass( 'has-image' );
	} );

	/* Galeri --------------------------------------------------------------------- */
	function syncGallery( $wrap ) {
		var ids = $wrap.find( '.ce-gallery-field__list li' ).map( function () { return $( this ).data( 'id' ); } ).get();
		$wrap.find( '.ce-gallery-field__input' ).val( ids.join( ',' ) ).trigger( 'change' );
	}
	function initGallerySort( ctx ) {
		$( ctx ).find( '.ce-gallery-field__list' ).each( function () {
			if ( $( this ).data( 'ui-sortable' ) ) { return; }
			$( this ).sortable( { tolerance: 'pointer', update: function () { syncGallery( $( this ).closest( '.ce-gallery-field' ) ); } } );
		} );
	}
	$( document ).on( 'click', '.ce-gallery-field__add', function ( e ) {
		e.preventDefault();
		var $wrap = $( this ).closest( '.ce-gallery-field' );
		var frame = wp.media( { title: 'Görselleri seçin', button: { text: 'Ekle' }, library: { type: 'image' }, multiple: 'add' } );
		frame.on( 'select', function () {
			var existing = $wrap.find( '.ce-gallery-field__list li' ).map( function () { return String( $( this ).data( 'id' ) ); } ).get();
			frame.state().get( 'selection' ).each( function ( m ) {
				var att = m.toJSON();
				if ( existing.indexOf( String( att.id ) ) !== -1 ) { return; }
				var url = att.sizes && att.sizes.thumbnail ? att.sizes.thumbnail.url : att.url;
				$wrap.find( '.ce-gallery-field__list' ).append(
					$( '<li>' ).attr( 'data-id', att.id ).append( $( '<img alt="">' ).attr( 'src', url ) ).append( '<button type="button" class="ce-gallery-field__remove" aria-label="Kaldır">×</button>' )
				);
			} );
			syncGallery( $wrap );
		} );
		frame.open();
	} );
	$( document ).on( 'click', '.ce-gallery-field__remove', function ( e ) {
		e.preventDefault();
		var $wrap = $( this ).closest( '.ce-gallery-field' );
		$( this ).closest( 'li' ).remove();
		syncGallery( $wrap );
	} );

	/* Tekrarlayıcı --------------------------------------------------------------- */
	function initRepeaterSort( ctx ) {
		$( ctx ).find( '.ce-repeater__rows' ).each( function () {
			if ( $( this ).data( 'ui-sortable' ) ) { return; }
			$( this ).sortable( { handle: '.ce-repeater__handle', axis: 'y', tolerance: 'pointer' } );
		} );
	}
	$( document ).on( 'click', '.ce-repeater__add', function ( e ) {
		e.preventDefault();
		var $field = $( this ).closest( '.ce-field--repeater' );
		var tpl = $field.children( '.ce-repeater__template' ).html();
		var idx = 'n' + Date.now().toString( 36 ) + Math.floor( Math.random() * 1000 );
		var $row = $( tpl.split( '__INDEX__' ).join( idx ).replace( /<\\\/script/g, '</script' ) );
		$field.children( '.ce-repeater__rows' ).append( $row );
		initGallerySort( $row );
		$row.find( 'input, textarea' ).first().trigger( 'focus' );
	} );
	$( document ).on( 'click', '.ce-repeater__remove', function ( e ) {
		e.preventDefault();
		if ( window.confirm( 'Bu satır silinecek. Emin misiniz?' ) ) {
			$( this ).closest( '.ce-repeater__row' ).remove();
		}
	} );
	$( document ).on( 'input', '.ce-repeater__row .ce-field--text:first-of-type input, .ce-repeater__row input[name$="[title]"], .ce-repeater__row input[name$="[label]"]', function () {
		var v = $( this ).val();
		if ( v ) { $( this ).closest( '.ce-repeater__row' ).find( '.ce-repeater__title' ).first().text( v ); }
	} );
	$( document ).on( 'click', '.ce-repeater__bar', function ( e ) {
		if ( $( e.target ).is( 'button, .ce-repeater__handle' ) ) { return; }
		$( this ).closest( '.ce-repeater__row' ).toggleClass( 'is-collapsed' );
	} );

	/* İkon önizleme -------------------------------------------------------------- */
	$( document ).on( 'change', '.ce-icon-select select', function () {
		var name = $( this ).val();
		var paths = ( A.icons || {} )[ name ];
		if ( paths ) {
			$( this ).siblings( '.ce-icon-select__preview' ).html( '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">' + paths + '</svg>' );
		}
	} );

	/* SMTP testi ---------------------------------------------------------------- */
	$( document ).on( 'click', '.ce-smtp-test', function ( e ) {
		e.preventDefault();
		var $btn = $( this );
		var $out = $btn.siblings( '.ce-smtp-test__result' );
		$btn.prop( 'disabled', true );
		$out.removeClass( 'is-ok is-err' ).text( 'Gönderiliyor…' );
		$.post( A.ajax, { action: 'ce_smtp_test', nonce: A.nonce } )
			.done( function ( r ) { $out.addClass( r.success ? 'is-ok' : 'is-err' ).text( ( r.data && r.data.message ) || '' ); } )
			.fail( function ( x ) { $out.addClass( 'is-err' ).text( ( x.responseJSON && x.responseJSON.data && x.responseJSON.data.message ) || 'İstek başarısız.' ); } )
			.always( function () { $btn.prop( 'disabled', false ); } );
	} );

	/* Silme onayı ------------------------------------------------------------------ */
	$( document ).on( 'click', 'a.submitdelete', function ( e ) {
		var permanent = /action=delete(&|$)/.test( this.href || '' );
		if ( ! window.confirm( permanent ? A.confirm : A.trash ) ) { e.preventDefault(); }
	} );
	$( document ).on( 'submit', 'form[data-confirm]', function ( e ) {
		if ( ! window.confirm( $( this ).data( 'confirm' ) ) ) { e.preventDefault(); }
	} );
	$( document ).on( 'click', '#doaction, #doaction2', function ( e ) {
		var action = $( this ).prev( 'select' ).val();
		if ( 'delete' === action || 'delete_all' === action ) {
			if ( ! window.confirm( A.confirm ) ) { e.preventDefault(); }
		}
	} );
	$( document ).on( 'click', '#delete_all', function ( e ) {
		if ( ! window.confirm( 'Çöp kutusundaki tüm kayıtlar kalıcı olarak silinecek. Emin misiniz?' ) ) { e.preventDefault(); }
	} );

	/* Başlangıç ------------------------------------------------------------------ */
	$( function () {
		initGallerySort( document );
		initRepeaterSort( document );
		$( '.ce-sections' ).sortable( {
			handle: '.ce-sections__handle',
			axis: 'y',
			update: function () {
				$( this ).children( 'li' ).each( function ( i ) {
					$( this ).find( 'input' ).each( function () {
						this.name = this.name.replace( /\[\d+\]\[(key|on)\]$/, '[' + i + '][$1]' );
					} );
				} );
			}
		} );
		setTimeout( function () { $( '.ce-toast' ).addClass( 'is-leaving' ); }, 5000 );
	} );
}( jQuery ) );
