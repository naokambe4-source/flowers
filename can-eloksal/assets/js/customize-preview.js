/**
 * Özelleştirici canlı önizleme: CSS değişkenlerini anında günceller.
 */
( function ( api ) {
	'use strict';
	var root = document.documentElement;
	var vars = {
		ce_color_accent: '--c-accent',
		ce_color_accent2: '--c-accent-2',
		ce_color_primary: '--c-primary',
		ce_color_dark: '--c-dark',
		ce_color_bg_alt: '--c-bg-alt',
		ce_color_text: '--c-text',
		ce_color_metal: '--c-metal'
	};
	function shade( hex, amt ) {
		hex = String( hex || '' ).replace( '#', '' );
		if ( 3 === hex.length ) { hex = hex.replace( /(.)/g, '$1$1' ); }
		if ( 6 !== hex.length ) { return hex; }
		return '#' + hex.match( /../g ).map( function ( c ) {
			return ( '0' + Math.round( parseInt( c, 16 ) * ( 1 - amt ) ).toString( 16 ) ).slice( -2 );
		} ).join( '' );
	}
	Object.keys( vars ).forEach( function ( key ) {
		api( key, function ( value ) {
			value.bind( function ( v ) {
				root.style.setProperty( vars[ key ], v );
				if ( 'ce_color_accent' === key ) { root.style.setProperty( '--c-accent-ink', shade( v, 0.3 ) ); }
			} );
		} );
	} );
	api( 'ce_font_head', function ( value ) { value.bind( function ( v ) { root.style.setProperty( '--f-head', ( window.CEFonts || {} )[ v ] ); } ); } );
	api( 'ce_font_body', function ( value ) { value.bind( function ( v ) { root.style.setProperty( '--f-body', ( window.CEFonts || {} )[ v ] ); } ); } );
	api( 'ce_font_scale', function ( value ) { value.bind( function ( v ) { root.style.fontSize = v + '%'; } ); } );
	api( 'ce_container', function ( value ) { value.bind( function ( v ) { root.style.setProperty( '--container', v + 'px' ); } ); } );
	api( 'ce_radius', function ( value ) {
		value.bind( function ( v ) {
			root.style.setProperty( '--radius', v + 'px' );
			root.style.setProperty( '--radius-lg', Math.round( v * 1.55 ) + 'px' );
			root.style.setProperty( '--radius-sm', Math.round( v * 0.55 ) + 'px' );
		} );
	} );
	api( 'ce_section_space', function ( value ) {
		value.bind( function ( v ) {
			var f = v / 100;
			root.style.setProperty( '--section', 'clamp(' + Math.round( 72 * f ) + 'px,' + ( 9 * f ).toFixed( 2 ) + 'vw,' + Math.round( 136 * f ) + 'px)' );
		} );
	} );
	var shapeStyle = document.createElement( 'style' );
	document.head.appendChild( shapeStyle );
	api( 'ce_btn_shape', function ( value ) {
		value.bind( function ( v ) {
			var r = 'rounded' === v ? '12px' : ( 'square' === v ? '2px' : '999px' );
			shapeStyle.textContent = '.ce-btn,.ce-filter__btn,.ce-searchform__input,.ce-searchform__btn{border-radius:' + r + '}';
		} );
	} );
}( wp.customize ) );
