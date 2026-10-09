/* Derin Flowers — çiçekçi ödeme akışı: takvim, saat aralığı, teslimat türü, bölge, not */
( function ( $ ) {
	'use strict';

	var C = window.DFCheckout;
	var $form = $( 'form.checkout' );
	var isProduct = false;
	if ( ! $form.length && $( '[data-df-quick]' ).length ) {
		// Ürün sayfasındaki hızlı sipariş: alanlar sepete ekle formunun içinde.
		$form = $( '[data-df-quick]' ).closest( 'form' );
		isProduct = true;
	}
	if ( ! C || ! $form.length ) {
		return;
	}

	var $body = $( document.body );
	var update = function () {
		if ( ! isProduct ) {
			$body.trigger( 'update_checkout' );
		}
		$form.trigger( 'df:changed' );
	};

	/* ---------------------------------------------------------------------
	 * Takvim
	 * ------------------------------------------------------------------ */
	var $cal = $( '[data-df-calendar]' );
	var $date = $( '#df_date' );
	var $slots = $( '[data-df-slots]' );
	var $dateText = $( '[data-df-date-text]' );
	var savedSlot = $( '#df_slot_saved' ).val() || '';
	var available = C.days || {};
	var todayStr = C.today;
	var maxStr = C.max;

	function parse( s ) {
		var p = s.split( '-' );
		return new Date( +p[ 0 ], +p[ 1 ] - 1, +p[ 2 ] );
	}
	function fmt( d ) {
		var m = d.getMonth() + 1;
		var day = d.getDate();
		return d.getFullYear() + '-' + ( m < 10 ? '0' : '' ) + m + '-' + ( day < 10 ? '0' : '' ) + day;
	}
	function human( s, withWeekday ) {
		var d = parse( s );
		var t = d.getDate() + ' ' + C.months[ d.getMonth() ];
		return withWeekday ? t + ' ' + C.wlong[ d.getDay() ] : t;
	}
	function relLabel( s ) {
		var t = parse( todayStr );
		var d = parse( s );
		var diff = Math.round( ( d - t ) / 86400000 );
		if ( 0 === diff ) {
			return C.i18n.today;
		}
		if ( 1 === diff ) {
			return C.i18n.tomorrow;
		}
		return C.wlong[ d.getDay() ];
	}

	var today = parse( todayStr );
	var view = new Date( today.getFullYear(), today.getMonth(), 1 );
	var sel = $date.val() && available[ $date.val() ] ? $date.val() : '';
	if ( sel ) {
		var sd = parse( sel );
		view = new Date( sd.getFullYear(), sd.getMonth(), 1 );
	}

	// Hafta başlıkları.
	$cal.find( '.df-cal__week' ).html( C.wdays.map( function ( w ) {
		return '<span>' + w + '</span>';
	} ).join( '' ) );

	var ICON_CAL = '<svg class="df-cal__ico" viewBox="0 0 24 24" width="26" height="26" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="3.5" y="5" width="17" height="15.5" rx="1.5"/><path d="M3.5 9.5h17M8 3v4M16 3v4"/><circle cx="12" cy="15" r="1.1" fill="currentColor" stroke="none"/></svg>';
	var ICON_CLOCK = '<svg class="df-slot__ico" viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/></svg>';
	var tomorrowStr = fmt( new Date( today.getFullYear(), today.getMonth(), today.getDate() + 1 ) );
	function chip( k, title ) {
		var ok = !! available[ k ];
		return '<button type="button" class="df-cal__chip' + ( k === sel ? ' is-selected' : '' ) + '" data-date="' + k + '"' + ( ok ? '' : ' disabled' ) + ' aria-pressed="' + ( k === sel ? 'true' : 'false' ) + '">' + ICON_CAL +
			'<span class="df-cal__chip-t"><strong>' + title + '</strong><span>' + ( ok ? human( k ) : ( C.i18n.closed || 'Teslimat yok' ) ) + '</span></span><i class="df-radio" aria-hidden="true"></i></button>';
	}
	function renderQuick() {
		var later = sel && sel !== todayStr && sel !== tomorrowStr;
		var open = $cal.hasClass( 'is-open' );
		var html = chip( todayStr, C.i18n.today ) + chip( tomorrowStr, C.i18n.tomorrow ) +
			'<button type="button" class="df-cal__chip df-cal__more' + ( later ? ' is-selected' : '' ) + ( open ? ' is-open' : '' ) + '" data-df-cal-more aria-expanded="' + ( open ? 'true' : 'false' ) + '">' + ICON_CAL +
			'<span class="df-cal__chip-t"><strong>' + ( C.i18n.later || 'İleri tarih seç' ) + '</strong><span>' + ( later ? human( sel, true ) : ( C.i18n.fromCal || 'Takvimden seçin' ) ) + '</span></span><i class="df-radio" aria-hidden="true"></i></button>';
		$cal.find( '[data-df-cal-quick]' ).html( html );
	}
	// Bugün ve yarın teslimat yoksa ya da seçili gün ileri bir tarihse takvim açık başlar.
	if ( ( ! available[ todayStr ] && ! available[ tomorrowStr ] && ! sel ) ) {
		$cal.addClass( 'is-open' );
	}
	$cal.on( 'click', '[data-df-cal-more]', function () {
		$cal.toggleClass( 'is-open' );
		renderQuick();
		renderMonth();
	} );

	function renderMonth() {
		var y = view.getFullYear();
		var m = view.getMonth();
		var first = new Date( y, m, 1 );
		var lead = ( first.getDay() + 6 ) % 7; // Pazartesi başlangıç.
		var days = new Date( y, m + 1, 0 ).getDate();
		var html = '';
		var i;
		for ( i = 0; i < lead; i++ ) {
			html += '<span class="df-cal__day is-blank" aria-hidden="true"></span>';
		}
		for ( i = 1; i <= days; i++ ) {
			var d = new Date( y, m, i );
			var k = fmt( d );
			var ok = !! available[ k ];
			var past = k < todayStr || k > maxStr;
			var cls = 'df-cal__day' + ( ok ? ' is-available' : '' ) + ( ! ok && ! past ? ' is-closed' : '' ) + ( k === todayStr ? ' is-today' : '' ) + ( k === sel ? ' is-selected' : '' );
			html += '<button type="button" role="gridcell" class="' + cls + '" data-date="' + k + '"' + ( ok ? '' : ' disabled' ) + ' aria-label="' + human( k, true ) + '"' + ( k === sel ? ' aria-pressed="true"' : '' ) + '>' + i + '</button>';
		}
		$cal.find( '[data-df-cal-grid]' ).html( html );
		$cal.find( '[data-df-cal-month]' ).text( C.months[ m ] + ' ' + y );
		var minView = new Date( today.getFullYear(), today.getMonth(), 1 );
		var maxD = parse( maxStr );
		$cal.find( '[data-df-cal-prev]' ).prop( 'disabled', view <= minView );
		$cal.find( '[data-df-cal-next]' ).prop( 'disabled', new Date( y, m + 1, 1 ) > maxD );
	}

	function renderSlots() {
		if ( ! sel ) {
			$dateText.text( C.i18n.pickDate );
			$slots.html( '<p class="df-slots__empty">' + C.i18n.pickDate + '</p>' );
			return;
		}
		$dateText.text( relLabel( sel ) === C.wlong[ parse( sel ).getDay() ] ? human( sel, true ) : relLabel( sel ) + ', ' + human( sel ) );
		var keys = available[ sel ] || [];
		if ( ! keys.length ) {
			$slots.html( '<p class="df-slots__empty">' + C.i18n.noSlots + '</p>' );
			return;
		}
		if ( keys.indexOf( savedSlot ) === -1 ) {
			savedSlot = keys[ 0 ];
		}
		var html = keys.map( function ( k ) {
			var s = C.slots[ k ] || { label: k, fee: 0 };
			var fee = s.fee > 0 ? '<small>+' + s.fee.toLocaleString( 'tr-TR' ) + ' ' + C.currency + '</small>' : '';
			return '<label class="df-slot"><input type="radio" name="df_slot" value="' + k + '"' + ( k === savedSlot ? ' checked' : '' ) + '><span>' + ICON_CLOCK + '<b>' + s.label.replace( ' - ', ' – ' ) + '</b>' + fee + '<i class="df-radio" aria-hidden="true"></i></span></label>';
		} ).join( '' );
		$slots.html( html );
	}

	function select( k ) {
		if ( ! available[ k ] ) {
			return;
		}
		sel = k;
		$date.val( k ).trigger( 'change' );
		var d = parse( k );
		view = new Date( d.getFullYear(), d.getMonth(), 1 );
		$cal.removeClass( 'is-open' );
		renderQuick();
		renderMonth();
		renderSlots();
		$( '#df_date_field' ).removeClass( 'is-invalid' );
		update();
	}

	$cal.on( 'click', '[data-date]', function () {
		select( $( this ).data( 'date' ) );
	} );
	$cal.on( 'click', '[data-df-cal-prev]', function () {
		view = new Date( view.getFullYear(), view.getMonth() - 1, 1 );
		renderMonth();
	} );
	$cal.on( 'click', '[data-df-cal-next]', function () {
		view = new Date( view.getFullYear(), view.getMonth() + 1, 1 );
		renderMonth();
	} );
	// Klavye ile gün gezinme.
	$cal.on( 'keydown', '.df-cal__day', function ( e ) {
		var map = { ArrowLeft: -1, ArrowRight: 1, ArrowUp: -7, ArrowDown: 7 };
		if ( ! map[ e.key ] ) {
			return;
		}
		e.preventDefault();
		var d = parse( $( this ).data( 'date' ) );
		d.setDate( d.getDate() + map[ e.key ] );
		var k = fmt( d );
		if ( d.getMonth() !== view.getMonth() ) {
			view = new Date( d.getFullYear(), d.getMonth(), 1 );
			renderMonth();
		}
		$cal.find( '[data-date="' + k + '"]' ).trigger( 'focus' );
	} );
	$slots.on( 'change', 'input', function () {
		savedSlot = this.value;
		$( '#df_slot_field' ).removeClass( 'is-invalid' );
		update();
	} );

	// İlk tarih seçili değilse en erken günü öner (seçmeden).
	renderQuick();
	renderMonth();
	renderSlots();

	/* ---------------------------------------------------------------------
	 * Teslimat türü
	 * ------------------------------------------------------------------ */
	function applyType() {
		var type = $form.find( 'input[name="df_type"]:checked' ).val() || $form.find( 'input[name="df_type"]' ).val() || 'address';
		$form.find( '[data-df-for]' ).each( function () {
			var on = $( this ).data( 'df-for' ) === type;
			this.hidden = ! on;
			$( this ).find( 'input, select, textarea' ).prop( 'disabled', ! on );
		} );
	}
	$form.on( 'change', 'input[name="df_type"]', function () {
		applyType();
		update();
	} );
	applyType();

	/* ---------------------------------------------------------------------
	 * Bölge (aranabilir liste + ücret)
	 * ------------------------------------------------------------------ */
	var $district = $( '#df_district' );
	var $feeHint = $( '[data-df-fee-hint]' );

	function feeText( v ) {
		var f = C.districts[ v ];
		if ( undefined === f ) {
			return '';
		}
		return f > 0 ? f.toLocaleString( 'tr-TR', { minimumFractionDigits: 2 } ) + ' ' + C.currency : C.i18n.free;
	}
	function districtHint() {
		var v = $district.val();
		$feeHint.text( v ? 'Teslimat ücreti: ' + feeText( v ) : '' );
	}
	if ( $.fn.selectWoo ) {
		var tpl = function ( o ) {
			if ( ! o.id ) {
				return o.text;
			}
			var name = String( o.text ).split( ' — ' )[ 0 ];
			var f = C.districts[ o.id ];
			var $o = $( '<span></span>' ).text( name );
			$o.append( $( '<span class="df-fee"></span>' ).toggleClass( 'is-free', ! f ).text( feeText( o.id ) ) );
			return $o;
		};
		$district.selectWoo( {
			width: '100%',
			placeholder: $district.data( 'placeholder' ),
			templateResult: tpl,
			language: {
				noResults: function () {
					return C.i18n.noResults;
				},
				searching: function () {
					return C.i18n.search;
				}
			}
		} );
		// Arama kutusu otomatik odaklansın.
		$district.on( 'select2:open', function () {
			var el = document.querySelector( '.select2-container--open .select2-search__field' );
			if ( el ) {
				el.setAttribute( 'placeholder', C.i18n.search );
				el.focus();
			}
		} );
	}
	$district.on( 'change', function () {
		districtHint();
		$( '#df_district_field' ).removeClass( 'is-invalid' );
		update();
	} );
	districtHint();

	/* ---------------------------------------------------------------------
	 * Telefon biçimi (5XX XXX XX XX)
	 * ------------------------------------------------------------------ */
	$form.on( 'input', 'input[type="tel"]', function () {
		var cc = $( this ).siblings( '.df-phone__cc' ).val() || '+90';
		if ( '+90' !== cc ) {
			return;
		}
		var d = this.value.replace( /\D/g, '' ).replace( /^90/, '' ).replace( /^0/, '' ).slice( 0, 10 );
		var out = d.slice( 0, 3 );
		if ( d.length > 3 ) {
			out += ' ' + d.slice( 3, 6 );
		}
		if ( d.length > 6 ) {
			out += ' ' + d.slice( 6, 8 );
		}
		if ( d.length > 8 ) {
			out += ' ' + d.slice( 8, 10 );
		}
		this.value = out;
	} );

	/* ---------------------------------------------------------------------
	 * Çiçek notu
	 * ------------------------------------------------------------------ */
	var $note = $( '#df_note' );
	var $count = $( '[data-df-note-count]' );
	var $tpls = $( '[data-df-note-templates]' );
	var $prev = $( '[data-df-note-preview]' );
	var $fromPrev = $( '[data-df-note-from-preview]' );
	var placeholder = $prev.text();

	function renderTemplates() {
		var cat = $( '#df_note_cat' ).val();
		var list = ( C.templates && C.templates[ cat ] ) || [];
		$tpls.html( list.map( function ( t ) {
			return $( '<button type="button" class="df-note__tpl"></button>' ).text( t ).prop( 'outerHTML' );
		} ).join( '' ) );
	}
	function refreshNote() {
		var v = $note.val() || '';
		$count.text( v.length );
		$prev.text( v || placeholder );
		var anon = $form.find( 'input[name="df_anon"]' ).is( ':checked' );
		var from = $.trim( $( '#df_note_from' ).val() || '' );
		$fromPrev.text( anon ? '' : ( from ? '— ' + from : '' ) );
		$( '#df_note_from' ).prop( 'disabled', anon );
		$( '.df-note' ).toggleClass( 'is-disabled', $form.find( 'input[name="df_no_note"]' ).is( ':checked' ) );
	}
	$( '#df_note_cat' ).on( 'change', renderTemplates );
	$tpls.on( 'click', '.df-note__tpl', function () {
		$note.val( $( this ).text().slice( 0, C.noteMax ) );
		$tpls.find( '.df-note__tpl' ).removeClass( 'is-used' );
		$( this ).addClass( 'is-used' );
		refreshNote();
	} );
	$form.on( 'input change', '#df_note, #df_note_from, input[name="df_anon"], input[name="df_no_note"]', refreshNote );
	renderTemplates();
	refreshNote();

	// Gönderen adı boşsa gönderici adından öner.
	$( '#df_sender_name' ).on( 'blur', function () {
		var $from = $( '#df_note_from' );
		if ( ! $.trim( $from.val() ) && this.value ) {
			$from.val( $.trim( this.value ).split( /\s+/ )[ 0 ] );
			refreshNote();
		}
	} );

	/* ---------------------------------------------------------------------
	 * Mobil sipariş özeti
	 * ------------------------------------------------------------------ */
	$( '[data-df-summary-toggle]' ).on( 'click', function () {
		var $s = $( this ).closest( '.df-summary' ).toggleClass( 'is-open' );
		$( this ).attr( 'aria-expanded', $s.hasClass( 'is-open' ) ? 'true' : 'false' );
	} );
	$body.on( 'updated_checkout', function () {
		var total = $( '#order_review .order-total td' ).first().html();
		if ( total ) {
			$( '[data-df-summary-total]' ).html( total );
		}
	} );

	/* ---------------------------------------------------------------------
	 * Hata alanlarını işaretle
	 * ------------------------------------------------------------------ */
	$body.on( 'checkout_error', function () {
		$form.find( '.is-invalid' ).removeClass( 'is-invalid' );
		$( '.woocommerce-error li[data-id]' ).each( function () {
			var id = $( this ).data( 'id' );
			var $f = $( '#' + id + '_field' );
			if ( ! $f.length ) {
				$f = $( '#' + id ).closest( '.df-field, .form-row' );
			}
			$f.addClass( 'is-invalid' );
		} );
	} );
	$form.on( 'input change', '.is-invalid input, .is-invalid textarea, .is-invalid select', function () {
		$( this ).closest( '.is-invalid' ).removeClass( 'is-invalid' );
	} );

	/* ---------------------------------------------------------------------
	 * Yapay zekâ ile not önerisi
	 * ------------------------------------------------------------------ */
	var $ai = $( '[data-df-ai]' );
	$ai.on( 'click', '[data-df-ai-go]', function () {
		var $b = $( this );
		var $out = $ai.find( '[data-df-ai-out]' );
		if ( ! C.ai ) {
			return;
		}
		$b.prop( 'disabled', true ).addClass( 'is-busy' );
		$out.html( '<span class="df-ai__wait df-ai__wait--busy">Size özel notlar yazılıyor…</span>' );
		$.post( C.ai.ajax, {
			action: 'df_ai_note',
			nonce: C.ai.nonce,
			tone: $ai.find( '.df-ai__tone' ).val(),
			hint: $ai.find( '.df-ai__hint' ).val(),
			cat: $( '#df_note_cat' ).val() || '',
			to: $( '#df_recipient_name' ).val() || '',
			from: $( '#df_note_from' ).val() || $( '#df_sender_name' ).val() || '',
			product: C.ai.product || ''
		} ).done( function ( res ) {
			if ( res && res.success && res.data.notes.length ) {
				$out.html( res.data.notes.map( function ( t ) {
					return $( '<button type="button" class="df-ai__pick"></button>' ).text( t ).prop( 'outerHTML' );
				} ).join( '' ) + '<small class="df-ai__tip">Beğendiğiniz nota dokunun; sonra dilediğiniz gibi düzenleyebilirsiniz.</small>' +
					( res.data.admin ? '<small class="df-ai__admin"></small>' : '' ) );
				if ( res.data.admin ) {
					$out.find( '.df-ai__admin' ).text( res.data.admin );
				}
				$b.find( 'span' ).text( 'Yeniden öner' );
			} else {
				$out.html( '<span class="df-ai__wait">' + ( ( res && res.data && res.data.message ) || 'Şu an öneri alınamadı, hazır mesajlardan seçebilirsiniz.' ) + '</span>' );
			}
		} ).fail( function () {
			$out.html( '<span class="df-ai__wait">Şu an öneri alınamadı, hazır mesajlardan seçebilirsiniz.</span>' );
		} ).always( function () {
			$b.prop( 'disabled', false ).removeClass( 'is-busy' );
		} );
	} );
	$ai.on( 'click', '.df-ai__pick', function () {
		$note.val( $( this ).text().slice( 0, C.noteMax ) ).trigger( 'input' );
		$ai.find( '.df-ai__pick' ).removeClass( 'is-used' );
		$( this ).addClass( 'is-used' );
		refreshNote();
	} );
	$ai.on( 'keydown', '.df-ai__hint', function ( e ) {
		if ( 'Enter' === e.key ) {
			e.preventDefault();
			$ai.find( '[data-df-ai-go]' ).trigger( 'click' );
		}
	} );

	/* ---------------------------------------------------------------------
	 * Ödeme sayfası: bilgiler ürün sayfasında alındıysa sadece ödeme
	 * ------------------------------------------------------------------ */
	$( '[data-df-quick-edit]' ).on( 'click', function () {
		$body.removeClass( 'df-checkout-quick' );
		$( '#df-quick-sum' ).remove();
		dfScroll( $( '#customer_details' ).offset().top - dfHeadOffset() );
	} );
	$body.on( 'checkout_error', function () {
		if ( $body.hasClass( 'df-checkout-quick' ) && $( '.woocommerce-error li[data-id]' ).length ) {
			$body.removeClass( 'df-checkout-quick' );
			$( '#df-quick-sum' ).remove();
		}
	} );

	/* ---------------------------------------------------------------------
	 * Ürün sayfası: adımlar açılır/kapanır, seçimler başlıkta özetlenir
	 * ------------------------------------------------------------------ */
	if ( ! isProduct ) {
		return;
	}
	$form.attr( 'novalidate', 'novalidate' );
	var $steps = $form.find( '.df-step' );
	$steps.each( function ( i ) {
		var $st = $( this );
		var $t = $st.children( '.df-step__title' );
		$t.find( '.df-step__num' ).text( i + 1 );
		$t.attr( { role: 'button', tabindex: 0, 'aria-expanded': i === 0 ? 'true' : 'false' } ).append( '<span class="df-step__sum" data-df-sum></span>' );
		$st.toggleClass( 'is-open', i === 0 );
	} );
	function dfHeadOffset() {
		var h = document.querySelector( '.df-header' );
		var off = 16;
		if ( h && /sticky|fixed/.test( getComputedStyle( h ).position ) ) {
			off += h.getBoundingClientRect().height;
		}
		return off;
	}
	function dfScroll( top, instant ) {
		var root = document.documentElement;
		if ( instant ) {
			root.style.scrollBehavior = 'auto';
			window.scrollTo( 0, Math.max( 0, top ) );
			root.style.scrollBehavior = '';
			return;
		}
		window.scrollTo( { top: Math.max( 0, top ), behavior: 'smooth' } );
	}
	function openStep( $st ) {
		$steps.not( $st ).removeClass( 'is-open' ).children( '.df-step__title' ).attr( 'aria-expanded', 'false' );
		$st.addClass( 'is-open' ).children( '.df-step__title' ).attr( 'aria-expanded', 'true' );
	}
	$form.on( 'click keydown', '.df-step__title', function ( e ) {
		if ( 'keydown' === e.type && 'Enter' !== e.key && ' ' !== e.key ) {
			return;
		}
		e.preventDefault();
		var $st = $( this ).closest( '.df-step' );
		if ( $st.hasClass( 'is-open' ) ) {
			$st.removeClass( 'is-open' );
			$( this ).attr( 'aria-expanded', 'false' );
		} else {
			openStep( $st );
		}
	} );
	function val( n ) {
		return $.trim( $form.find( '[name="' + n + '"]' ).filter( function () {
			return ! this.disabled && ( ! /radio|checkbox/.test( this.type ) || this.checked );
		} ).first().val() || '' );
	}
	function summarize() {
		var date = $( '#df_date' ).val();
		var slot = $slots.find( 'input:checked' ).closest( 'label' ).text();
		if ( date ) {
			$( '#df_date_field' ).removeClass( 'is-invalid' );
		}
		if ( slot ) {
			$( '#df_slot_field' ).removeClass( 'is-invalid' );
		}
		var pickup = 'pickup' === val( 'df_type' );
		var dsel = $( '#df_district option:selected' );
		var gifts = $form.find( 'input[name="df_gifts[]"]:checked' ).map( function () {
			return $( this ).data( 'name' );
		} ).get();
		var sums = {
			'df-step-gifts': gifts.join( ', ' ),
			'df-step-delivery': date ? ( /^(Bugün|Yarın)$/.test( relLabel( date ) ) ? relLabel( date ) : human( date, true ) ) + ( slot ? ' · ' + $.trim( slot ) : '' ) : '',
			'df-step-sender': val( 'df_sender_name' ),
			'df-step-recipient': val( 'df_recipient_name' ) ? val( 'df_recipient_name' ) + ( pickup ? '' : ( dsel.val() ? ' · ' + $.trim( dsel.text().split( '—' )[ 0 ] ) : '' ) ) : '',
			'df-step-note': $form.find( 'input[name="df_no_note"]' ).is( ':checked' ) ? 'Not yok' : ( val( 'df_note' ) ? val( 'df_note' ).slice( 0, 34 ) + ( val( 'df_note' ).length > 34 ? '…' : '' ) : '' )
		};
		$.each( sums, function ( id, t ) {
			var $st = $( '#' + id );
			$st.find( '[data-df-sum]' ).text( t );
			$st.toggleClass( 'is-done', !! t );
		} );
	}
	$form.on( 'input change df:changed', summarize );
	$form.on( 'click', '.df-note__tpl', function () {
		setTimeout( summarize, 0 );
	} );
	summarize();
	// "Devam" düğmesi: bir sonraki adımı aç.
	$steps.each( function ( i ) {
		if ( i < $steps.length - 1 ) {
			$( '<button type="button" class="df-step__next">Devam et <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M4 12h15M13 6l6 6-6 6"/></svg></button>' ).appendTo( this ).on( 'click', function () {
				var $next = $steps.eq( i + 1 );
				// Adım kapanınca sayfa kısalır; sonraki adımın başlığı düğmenin olduğu yerde kalsın,
				// sonra yumuşakça başlığın altına kaysın (sıçrama olmasın).
				var y = this.getBoundingClientRect().top;
				openStep( $next );
				dfScroll( $next.offset().top - y, true );
				dfScroll( $next.offset().top - dfHeadOffset() );
			} );
		}
	} );
	// Ürün sayfasında sadece "Hemen Al": sepete ekle düğmesi kaldırılır.
	$form.find( '.single_add_to_cart_button' ).remove();
	var $buy = $form.find( '.df-buy-now--main' );

	/* ---------- Eksik bilgi uyarısı ---------- */
	var $alert = $( '<div class="df-qalert" role="alert" tabindex="-1" hidden></div>' ).insertBefore( $buy );
	var $toast = $( '<div class="df-qtoast" role="status" aria-live="assertive"></div>' ).appendTo( document.body );
	var toastTimer;
	function toast( msg ) {
		$toast.text( msg ).addClass( 'is-on' );
		clearTimeout( toastTimer );
		toastTimer = setTimeout( function () {
			$toast.removeClass( 'is-on' );
		}, 4200 );
	}
	function markField( $f, msg ) {
		$f.addClass( 'is-invalid' );
		if ( ! $f.find( '.df-err' ).length ) {
			$f.append( $( '<span class="df-err"></span>' ).text( msg ) );
		}
	}
	$form.on( 'input change', '.is-invalid input, .is-invalid textarea, .is-invalid select', function () {
		$( this ).closest( '.is-invalid' ).removeClass( 'is-invalid' ).find( '.df-err' ).remove();
	} );
	function showAlert( items ) {
		if ( ! items.length ) {
			$alert.prop( 'hidden', true ).empty();
			return;
		}
		$alert.html( '<strong class="df-qalert__title">Siparişi tamamlamak için ' + items.length + ' bilgi eksik</strong><ul></ul>' );
		items.forEach( function ( it ) {
			$( '<li><button type="button"></button></li>' ).find( 'button' ).text( it.label ).on( 'click', function () {
				goTo( it.$f );
			} ).end().appendTo( $alert.find( 'ul' ) );
		} );
		$alert.prop( 'hidden', false );
		$buy.removeClass( 'is-shake' );
		void $buy[ 0 ].offsetWidth;
		$buy.addClass( 'is-shake' );
	}
	function goTo( $f ) {
		var $st = $f.closest( '.df-step' );
		if ( $st.length ) {
			openStep( $st );
		}
		dfScroll( $f.offset().top - dfHeadOffset() - 20 );
		var $el = $f.find( 'input:visible:not([type=hidden]), select:visible, textarea:visible' ).first();
		if ( $el.length ) {
			setTimeout( function () {
				$el.trigger( 'focus' );
			}, 260 );
		}
	}
	function check() {
		$form.find( '.df-err' ).remove();
		$form.find( '.is-invalid' ).removeClass( 'is-invalid' );
		var pickup = 'pickup' === val( 'df_type' );
		var phoneOk = function ( n ) {
			var d = val( n ).replace( /\D/g, '' );
			return d.length >= 10;
		};
		var rules = [
			[ '#df_date_field', 'Teslimat tarihi', function () { return !! $( '#df_date' ).val(); }, 'Takvimden bir gün seçin' ],
			[ '#df_slot_field', 'Teslimat saati', function () { return !! $slots.find( 'input:checked' ).length; }, 'Bir saat aralığı seçin' ],
			[ '#df_sender_name_field', 'Gönderici adı soyadı', function () { return val( 'df_sender_name' ).length >= 3; }, 'Adınızı ve soyadınızı yazın' ],
			[ '#df_sender_phone_field', 'Gönderici telefonu', function () { return phoneOk( 'df_sender_phone' ); }, 'Geçerli bir telefon yazın' ],
			[ '#billing_email_field', 'E-posta adresi', function () { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( val( 'billing_email' ) ); }, 'Geçerli bir e-posta yazın' ],
			[ '#df_recipient_name_field', 'Alıcı adı soyadı', function () { return val( 'df_recipient_name' ).length >= 3; }, 'Alıcının adını ve soyadını yazın' ],
			[ '#df_recipient_phone_field', 'Alıcı telefonu', function () { return phoneOk( 'df_recipient_phone' ); }, 'Geçerli bir telefon yazın' ]
		];
		if ( ! pickup ) {
			rules.push( [ '#df_district_field', 'Teslimat bölgesi', function () { return !! val( 'df_district' ); }, 'Teslimat bölgesini seçin' ] );
			rules.push( [ '#df_address_field', 'Alıcı adresi', function () { return val( 'df_address' ).length >= 10; }, 'Adresi eksiksiz yazın (mahalle, sokak, no)' ] );
		}
		var $var = $form.find( 'input[name="variation_id"]' );
		var items = [];
		if ( $var.length && ! parseInt( $var.val(), 10 ) ) {
			var $vt = $form.find( '.variations' );
			markField( $vt, 'Lütfen bir seçenek seçin' );
			items.push( { label: 'Ürün seçeneği', $f: $vt } );
		}
		rules.forEach( function ( r ) {
			var $f = $( r[ 0 ] );
			if ( $f.length && ! r[ 2 ]() ) {
				markField( $f, r[ 3 ] );
				items.push( { label: r[ 1 ], $f: $f } );
			}
		} );
		$steps.each( function () {
			$( this ).toggleClass( 'has-error', !! $( this ).find( '.is-invalid' ).length );
		} );
		return items;
	}

	$form.on( 'submit', function ( e ) {
		var sub = e.originalEvent && e.originalEvent.submitter;
		if ( sub && 'df_buy_now' !== sub.name ) {
			return;
		}
		var items = check();
		showAlert( items );
		if ( items.length ) {
			e.preventDefault();
			toast( items.length + ' eksik bilgi var: ' + items[ 0 ].label + ( items.length > 1 ? ' ve diğerleri' : '' ) );
			goTo( items[ 0 ].$f );
			return;
		}
		$buy.addClass( 'is-loading' ).find( 'span' ).first();
	} );
	$form.on( 'input change', function () {
		if ( ! $alert.prop( 'hidden' ) ) {
			var left = $form.find( '.is-invalid' ).length;
			if ( ! left ) {
				showAlert( [] );
			}
		}
	} );

	// Sunucudan dönen hatalar: aynı uyarı kutusunda göster.
	var srv = $( '.woocommerce-error li' ).map( function () {
		var id = $( this ).data( 'id' );
		var $f = id ? $( '#' + id + '_field' ) : $();
		if ( ! $f.length && id ) {
			$f = $( '#' + id ).closest( '.df-field, .form-row' );
		}
		return { label: $( this ).text().trim(), $f: $f.length ? $f : $buy };
	} ).get();
	if ( srv.length ) {
		showAlert( srv );
		srv.forEach( function ( it ) {
			if ( it.$f !== $buy ) {
				markField( it.$f, 'Lütfen kontrol edin' );
			}
		} );
		$( '.woocommerce-notices-wrapper .woocommerce-error' ).remove();
		goTo( srv[ 0 ].$f );
	}
}( jQuery ) );
