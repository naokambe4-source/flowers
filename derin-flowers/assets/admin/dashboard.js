/* Derin Flowers Yönetim Paneli */
( function ( $ ) {
	'use strict';
	var A = window.DFApp || {};

	// Kurye ataması.
	$( document ).on( 'change', '[data-df-courier]', function () {
		var $sel = $( this );
		var $row = $sel.closest( 'tr' );
		$sel.prop( 'disabled', true );
		$.post( A.ajax, { action: 'df_assign_courier', nonce: A.nonce, order: $row.data( 'order' ), courier: $sel.val() } )
			.done( function ( res ) {
				if ( res && res.success ) {
					var $wa = $row.find( '[data-df-wa]' );
					if ( res.data.wa ) {
						$wa.attr( 'href', res.data.wa ).prop( 'hidden', false );
					} else {
						$wa.prop( 'hidden', true );
					}
					if ( res.data.status ) {
						$row.find( '[data-status]' ).html( res.data.status );
					}
					$row.addClass( 'is-saved' );
					setTimeout( function () {
						$row.removeClass( 'is-saved' );
					}, 1200 );
				}
			} )
			.always( function () {
				$sel.prop( 'disabled', false );
			} );
	} );

	// Yazdırma: tümünü seç.
	$( document ).on( 'change', '[data-df-check-all]', function () {
		$( this ).closest( 'form' ).find( 'input[name="ids[]"]' ).prop( 'checked', this.checked );
	} );
	$( document ).on( 'submit', '[data-df-print-form]', function ( e ) {
		if ( ! $( this ).find( 'input[name="ids[]"]:checked' ).length ) {
			e.preventDefault();
			window.alert( 'Yazdırmak için en az bir sipariş seçin.' );
		}
	} );
}( jQuery ) );
