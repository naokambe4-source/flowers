/* Derin Flowers — ürün düzenleme: video, kapak ve SSS alanları */
( function ( $ ) {
	'use strict';

	function pick( type, cb ) {
		var frame = wp.media( {
			title: 'video' === type ? 'Ürün videosu seç' : 'Kapak görseli seç',
			library: { type: type },
			multiple: false
		} );
		frame.on( 'select', function () {
			cb( frame.state().get( 'selection' ).first().toJSON() );
		} );
		frame.open();
	}

	$( '#df_video_select' ).on( 'click', function ( e ) {
		e.preventDefault();
		pick( 'video', function ( a ) {
			$( '#df_video_id' ).val( a.id );
			$( '#df_video_preview' ).html( '<video src="' + a.url + '" muted controls preload="metadata"></video>' );
			$( '#df_video_remove' ).prop( 'hidden', false );
		} );
	} );
	$( '#df_video_remove' ).on( 'click', function ( e ) {
		e.preventDefault();
		$( '#df_video_id' ).val( '' );
		$( '#df_video_preview' ).empty();
		$( this ).prop( 'hidden', true );
	} );

	$( '#df_poster_select' ).on( 'click', function ( e ) {
		e.preventDefault();
		pick( 'image', function ( a ) {
			var url = a.sizes && a.sizes.thumbnail ? a.sizes.thumbnail.url : a.url;
			$( '#df_video_poster' ).val( a.id );
			$( '#df_poster_preview' ).html( '<img src="' + url + '" width="80" alt="">' );
			$( '#df_poster_remove' ).prop( 'hidden', false );
		} );
	} );
	$( '#df_poster_remove' ).on( 'click', function ( e ) {
		e.preventDefault();
		$( '#df_video_poster' ).val( '' );
		$( '#df_poster_preview' ).empty();
		$( this ).prop( 'hidden', true );
	} );

	var $rows = $( '#df_faq_rows' );
	function reindex() {
		$rows.children( '.df-faq-row' ).each( function ( i ) {
			$( this ).find( '[name]' ).each( function () {
				this.name = this.name.replace( /df_faq\[[^\]]+\]/, 'df_faq[' + i + ']' );
			} );
		} );
	}
	$( '#df_faq_add' ).on( 'click', function ( e ) {
		e.preventDefault();
		$rows.append( $( '#df_faq_tpl' ).html().replace( /__i__/g, $rows.children().length ) );
		reindex();
	} );
	$rows.on( 'click', '.df-faq-remove', function ( e ) {
		e.preventDefault();
		$( this ).closest( '.df-faq-row' ).remove();
		reindex();
	} );
	if ( $.fn.sortable ) {
		$rows.sortable( { handle: '.df-faq-handle', update: reindex } );
	}
} )( jQuery );
