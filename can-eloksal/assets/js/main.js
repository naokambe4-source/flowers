/**
 * Can Eloksal — ön yüz etkileşimleri (bağımlılıksız, defer ile yüklenir).
 *
 * Header · Açılır menü · Mobil çekmece · Hero slider · Scroll reveal · Sektör panelleri ·
 * Filtreler · Lightbox · Kopyalama · Toast · AJAX formlar · Dosya seçimi · Çerez onayı.
 */
( function () {
	'use strict';

	var CE = window.CE || { i18n: {} };
	var t = function ( key, fallback ) { return ( CE.i18n && CE.i18n[ key ] ) || fallback || key; };
	var $ = function ( sel, ctx ) { return ( ctx || document ).querySelector( sel ); };
	var $$ = function ( sel, ctx ) { return Array.prototype.slice.call( ( ctx || document ).querySelectorAll( sel ) ); };
	var reduceMotion = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	window.ceReady = true;
	document.documentElement.classList.add( 'ce-js' );

	/* Toast ---------------------------------------------------------------- */
	var ICONS = {
		check: '<svg class="ce-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 12 5 5L20 7"/></svg>',
		alert: '<svg class="ce-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9.5"/><path d="M12 8v5M12 16h.01"/></svg>',
		close: '<svg class="ce-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>',
		prev: '<svg class="ce-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>',
		next: '<svg class="ce-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>',
		play: '<svg class="ce-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m7 4 13 8-13 8z"/></svg>',
		pause: '<svg class="ce-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 5v14M16 5v14"/></svg>'
	};

	function toast( message, type ) {
		var box = $( '[data-toasts]' );
		if ( ! box ) { return; }
		var el = document.createElement( 'div' );
		el.className = 'ce-toast' + ( 'error' === type ? ' ce-toast--error' : '' );
		el.innerHTML = ( 'error' === type ? ICONS.alert : ICONS.check );
		var span = document.createElement( 'span' );
		span.textContent = message;
		el.appendChild( span );
		box.appendChild( el );
		setTimeout( function () { el.remove(); }, 3200 );
	}

	/* Header --------------------------------------------------------------- */
	var header = $( '[data-header]' );
	if ( header ) {
		var lastY = window.scrollY;
		var ticking = false;
		var onScroll = function () {
			var y = window.scrollY;
			header.classList.toggle( 'is-scrolled', y > 24 );
			var drawerOpen = document.body.classList.contains( 'ce-lock' );
			var menuOpen = !! $( '.ce-nav__item.is-open', header ) || header.contains( document.activeElement );
			var autohide = document.body.classList.contains( 'ce-header-autohide' );
			header.classList.toggle( 'is-hidden', autohide && ! drawerOpen && ! menuOpen && y > 480 && y > lastY + 4 );
			if ( y < lastY - 4 ) { header.classList.remove( 'is-hidden' ); }
			lastY = y;
			ticking = false;
		};
		window.addEventListener( 'scroll', function () {
			if ( ! ticking ) { window.requestAnimationFrame( onScroll ); ticking = true; }
		}, { passive: true } );
		onScroll();
	}

	/* Masaüstü açılır menü --------------------------------------------------- */
	$$( '.ce-nav__toggle' ).forEach( function ( btn ) {
		var item = btn.closest( '.ce-nav__item' );
		btn.addEventListener( 'click', function ( e ) {
			e.stopPropagation();
			var open = ! item.classList.contains( 'is-open' );
			$$( '.ce-nav__item.is-open' ).forEach( function ( i ) {
				i.classList.remove( 'is-open' );
				$( '.ce-nav__toggle', i ).setAttribute( 'aria-expanded', 'false' );
			} );
			item.classList.toggle( 'is-open', open );
			btn.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		} );
	} );
	document.addEventListener( 'click', function ( e ) {
		if ( ! e.target.closest( '.ce-nav__item' ) ) {
			$$( '.ce-nav__item.is-open' ).forEach( function ( i ) {
				i.classList.remove( 'is-open' );
				$( '.ce-nav__toggle', i ).setAttribute( 'aria-expanded', 'false' );
			} );
		}
	} );
	document.addEventListener( 'keydown', function ( e ) {
		if ( 'Escape' !== e.key ) { return; }
		var open = $( '.ce-nav__item.is-open' );
		if ( open ) {
			open.classList.remove( 'is-open' );
			var tg = $( '.ce-nav__toggle', open );
			tg.setAttribute( 'aria-expanded', 'false' );
			tg.focus();
		}
	} );

	/* Mobil çekmece ------------------------------------------------------------ */
	var drawer = $( '[data-drawer]' );
	var opener = $( '[data-drawer-open]' );
	var lastFocus = null;
	function focusables( ctx ) {
		return $$( 'a[href], button:not([disabled]), input, select, textarea, [tabindex]:not([tabindex="-1"])', ctx ).filter( function ( el ) {
			return el.offsetParent !== null;
		} );
	}
	function openDrawer() {
		if ( ! drawer ) { return; }
		lastFocus = document.activeElement;
		drawer.hidden = false;
		document.body.classList.add( 'ce-lock' );
		opener.setAttribute( 'aria-expanded', 'true' );
		requestAnimationFrame( function () {
			drawer.classList.add( 'is-open' );
			var close = $( '.ce-drawer__close', drawer );
			if ( close ) { close.focus(); }
		} );
	}
	function closeDrawer() {
		if ( ! drawer || drawer.hidden ) { return; }
		drawer.classList.remove( 'is-open' );
		document.body.classList.remove( 'ce-lock' );
		opener.setAttribute( 'aria-expanded', 'false' );
		setTimeout( function () { drawer.hidden = true; }, reduceMotion ? 0 : 450 );
		if ( lastFocus ) { lastFocus.focus(); }
	}
	if ( drawer && opener ) {
		opener.addEventListener( 'click', openDrawer );
		$$( '[data-drawer-close]', drawer ).forEach( function ( el ) { el.addEventListener( 'click', closeDrawer ); } );
		drawer.addEventListener( 'keydown', function ( e ) {
			if ( 'Escape' === e.key ) { closeDrawer(); return; }
			if ( 'Tab' !== e.key ) { return; }
			var f = focusables( $( '.ce-drawer__panel', drawer ) );
			if ( ! f.length ) { return; }
			if ( e.shiftKey && document.activeElement === f[ 0 ] ) { e.preventDefault(); f[ f.length - 1 ].focus(); }
			else if ( ! e.shiftKey && document.activeElement === f[ f.length - 1 ] ) { e.preventDefault(); f[ 0 ].focus(); }
		} );
		$$( '.ce-drawer__toggle', drawer ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				var sub = document.getElementById( btn.getAttribute( 'aria-controls' ) );
				var open = 'true' !== btn.getAttribute( 'aria-expanded' );
				btn.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
				if ( sub ) { sub.hidden = ! open; }
			} );
		} );
		window.addEventListener( 'resize', function () { if ( window.innerWidth >= 1200 ) { closeDrawer(); } } );
	}

	/* Hero slider --------------------------------------------------------------- */
	$$( '[data-hero]' ).forEach( function ( hero ) {
		var slides = $$( '.ce-hero__slide', hero );
		var dots = $$( '[data-hero-dot]', hero );
		var pauseBtn = $( '[data-hero-pause]', hero );
		var delay = Math.max( 0, parseInt( CE.autoplay, 10 ) || 0 ) * 1000;
		var index = 0;
		var timer = null;
		var paused = reduceMotion || ! delay;
		var userPaused = false;

		hero.style.setProperty( '--ce-autoplay', ( delay / 1000 ) + 's' );

		function playVideo( slide ) {
			var v = $( '.ce-hero__video', slide );
			if ( ! v || reduceMotion ) { return; }
			if ( ! v.src && v.dataset.src ) {
				v.src = v.dataset.src;
				v.addEventListener( 'canplay', function () { v.classList.add( 'is-ready' ); }, { once: true } );
			}
			var p = v.play();
			if ( p && p.catch ) { p.catch( function () {} ); }
		}
		function go( i ) {
			if ( slides.length < 2 ) { return; }
			var prev = slides[ index ];
			index = ( i + slides.length ) % slides.length;
			var cur = slides[ index ];
			prev.classList.remove( 'is-active' );
			prev.setAttribute( 'aria-hidden', 'true' );
			var pv = $( '.ce-hero__video', prev );
			if ( pv ) { pv.pause(); }
			cur.classList.add( 'is-active' );
			cur.removeAttribute( 'aria-hidden' );
			dots.forEach( function ( d, n ) {
				d.classList.toggle( 'is-active', n === index );
				d.setAttribute( 'aria-selected', n === index ? 'true' : 'false' );
			} );
			playVideo( cur );
			schedule();
		}
		function schedule() {
			clearTimeout( timer );
			if ( ! paused && slides.length > 1 ) {
				timer = setTimeout( function () { go( index + 1 ); }, delay );
			}
		}
		function setPaused( state ) {
			paused = state;
			hero.classList.toggle( 'is-paused', state );
			schedule();
		}

		playVideo( slides[ 0 ] );
		if ( slides.length < 2 ) { return; }
		hero.classList.toggle( 'is-paused', paused );
		schedule();

		var prevBtn = $( '[data-hero-prev]', hero );
		var nextBtn = $( '[data-hero-next]', hero );
		if ( prevBtn ) { prevBtn.addEventListener( 'click', function () { go( index - 1 ); } ); }
		if ( nextBtn ) { nextBtn.addEventListener( 'click', function () { go( index + 1 ); } ); }
		dots.forEach( function ( d ) { d.addEventListener( 'click', function () { go( parseInt( d.dataset.heroDot, 10 ) ); } ); } );
		if ( pauseBtn ) {
			if ( ! delay ) { pauseBtn.hidden = true; }
			pauseBtn.addEventListener( 'click', function () {
				userPaused = ! userPaused;
				pauseBtn.setAttribute( 'aria-pressed', userPaused ? 'true' : 'false' );
				pauseBtn.innerHTML = ( userPaused ? ICONS.play : ICONS.pause ) + '<span class="screen-reader-text">' + ( userPaused ? 'Otomatik geçişi başlat' : 'Otomatik geçişi durdur' ) + '</span>';
				setPaused( userPaused || reduceMotion || ! delay );
			} );
		}
		hero.addEventListener( 'mouseenter', function () { if ( delay ) { setPaused( true ); } } );
		hero.addEventListener( 'mouseleave', function () { if ( delay ) { setPaused( userPaused || reduceMotion ); } } );
		hero.addEventListener( 'focusin', function () { if ( delay ) { setPaused( true ); } } );
		hero.addEventListener( 'focusout', function () { if ( delay ) { setPaused( userPaused || reduceMotion ); } } );
		document.addEventListener( 'visibilitychange', function () { if ( delay ) { setPaused( document.hidden || userPaused || reduceMotion ); } } );

		var sx = null;
		hero.addEventListener( 'touchstart', function ( e ) { sx = e.touches[ 0 ].clientX; }, { passive: true } );
		hero.addEventListener( 'touchend', function ( e ) {
			if ( null === sx ) { return; }
			var dx = e.changedTouches[ 0 ].clientX - sx;
			if ( Math.abs( dx ) > 50 ) { go( index + ( dx < 0 ? 1 : -1 ) ); }
			sx = null;
		} );
	} );

	/* Scroll reveal ---------------------------------------------------------------- */
	var revealEls = $$( '[data-reveal]' );
	if ( 'IntersectionObserver' in window && ! reduceMotion ) {
		var io = new IntersectionObserver( function ( entries ) {
			entries.forEach( function ( entry ) {
				if ( entry.isIntersecting ) {
					entry.target.classList.add( 'is-visible' );
					io.unobserve( entry.target );
				}
			} );
		}, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 } );
		revealEls.forEach( function ( el ) { io.observe( el ); } );
	} else {
		revealEls.forEach( function ( el ) { el.classList.add( 'is-visible' ); } );
	}

	/* Sektör panelleri ------------------------------------------------------------ */
	$$( '[data-sectors]' ).forEach( function ( wrap ) {
		var items = $$( '.ce-sector', wrap );
		var activate = function ( el ) {
			items.forEach( function ( i ) { i.classList.toggle( 'is-open', i === el ); } );
		};
		items.forEach( function ( el ) {
			el.addEventListener( 'mouseenter', function () { activate( el ); } );
			el.addEventListener( 'focus', function () { activate( el ); } );
		} );
	} );

	/* Filtreler (galeri + hizmetler) ------------------------------------------------ */
	$$( '[data-filter]' ).forEach( function ( bar ) {
		var scope = bar.parentElement;
		var target = $( '[data-filter-target]', scope );
		var empty = $( '[data-filter-empty]', scope );
		if ( ! target ) { return; }
		var buttons = $$( '[data-filter-value]', bar );
		buttons.forEach( function ( btn ) {
			btn.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				var value = btn.dataset.filterValue;
				buttons.forEach( function ( b ) {
					var on = b === btn;
					b.classList.toggle( 'is-active', on );
					if ( 'BUTTON' === b.tagName ) { b.setAttribute( 'aria-pressed', on ? 'true' : 'false' ); }
					else if ( on ) { b.setAttribute( 'aria-current', 'page' ); } else { b.removeAttribute( 'aria-current' ); }
				} );
				var shown = 0;
				Array.prototype.forEach.call( target.children, function ( item ) {
					var cats = ( item.dataset.cats || '' ).split( ' ' );
					var match = '*' === value || cats.indexOf( value ) !== -1;
					item.classList.toggle( 'is-filtered', ! match );
					if ( match ) { shown++; item.classList.add( 'is-visible' ); }
				} );
				if ( empty ) { empty.hidden = shown > 0; }
			} );
		} );
	} );

	/* Lightbox ---------------------------------------------------------------------- */
	var lb = null;
	var lbItems = [];
	var lbIndex = 0;
	var lbLast = null;
	function buildLightbox() {
		lb = document.createElement( 'div' );
		lb.className = 'ce-lightbox';
		lb.setAttribute( 'role', 'dialog' );
		lb.setAttribute( 'aria-modal', 'true' );
		lb.setAttribute( 'aria-label', 'Görsel görüntüleyici' );
		lb.hidden = true;
		lb.innerHTML =
			'<div class="ce-lightbox__bar"><span class="ce-lightbox__count" aria-live="polite"></span><button type="button" class="ce-lightbox__close">' + ICONS.close + '<span class="screen-reader-text">' + t( 'close', 'Kapat' ) + '</span></button></div>' +
			'<div class="ce-lightbox__stage"><img class="ce-lightbox__img" alt=""><button type="button" class="ce-lightbox__prev">' + ICONS.prev + '<span class="screen-reader-text">' + t( 'prev', 'Önceki' ) + '</span></button><button type="button" class="ce-lightbox__next">' + ICONS.next + '<span class="screen-reader-text">' + t( 'next', 'Sonraki' ) + '</span></button></div>' +
			'<p class="ce-lightbox__caption"></p>';
		document.body.appendChild( lb );
		$( '.ce-lightbox__close', lb ).addEventListener( 'click', closeLightbox );
		$( '.ce-lightbox__prev', lb ).addEventListener( 'click', function () { showLightbox( lbIndex - 1 ); } );
		$( '.ce-lightbox__next', lb ).addEventListener( 'click', function () { showLightbox( lbIndex + 1 ); } );
		$( '.ce-lightbox__stage', lb ).addEventListener( 'click', function ( e ) { if ( e.target === e.currentTarget ) { closeLightbox(); } } );
		lb.addEventListener( 'keydown', function ( e ) {
			if ( 'Escape' === e.key ) { closeLightbox(); }
			else if ( 'ArrowLeft' === e.key ) { showLightbox( lbIndex - 1 ); }
			else if ( 'ArrowRight' === e.key ) { showLightbox( lbIndex + 1 ); }
			else if ( 'Tab' === e.key ) {
				var f = focusables( lb );
				if ( e.shiftKey && document.activeElement === f[ 0 ] ) { e.preventDefault(); f[ f.length - 1 ].focus(); }
				else if ( ! e.shiftKey && document.activeElement === f[ f.length - 1 ] ) { e.preventDefault(); f[ 0 ].focus(); }
			}
		} );
		var sx = null;
		lb.addEventListener( 'touchstart', function ( e ) { sx = e.touches[ 0 ].clientX; }, { passive: true } );
		lb.addEventListener( 'touchend', function ( e ) {
			if ( null === sx ) { return; }
			var dx = e.changedTouches[ 0 ].clientX - sx;
			if ( Math.abs( dx ) > 50 ) { showLightbox( lbIndex + ( dx < 0 ? 1 : -1 ) ); }
			sx = null;
		} );
	}
	function showLightbox( i ) {
		if ( ! lbItems.length ) { return; }
		lbIndex = ( i + lbItems.length ) % lbItems.length;
		var link = lbItems[ lbIndex ];
		var img = $( '.ce-lightbox__img', lb );
		var thumb = $( 'img', link );
		img.classList.add( 'is-loading' );
		img.onload = function () { img.classList.remove( 'is-loading' ); };
		img.onerror = function () { img.classList.remove( 'is-loading' ); };
		img.src = link.getAttribute( 'href' );
		img.alt = thumb ? thumb.alt : '';
		$( '.ce-lightbox__caption', lb ).textContent = link.dataset.caption || '';
		$( '.ce-lightbox__count', lb ).textContent = ( lbIndex + 1 ) + ' / ' + lbItems.length;
		var multi = lbItems.length > 1;
		$( '.ce-lightbox__prev', lb ).hidden = ! multi;
		$( '.ce-lightbox__next', lb ).hidden = ! multi;
	}
	function openLightbox( link ) {
		if ( ! lb ) { buildLightbox(); }
		var group = link.dataset.lightbox;
		lbItems = $$( '[data-lightbox="' + group + '"]' ).filter( function ( a ) { return ! a.closest( '.is-filtered' ); } );
		lbLast = link;
		lb.hidden = false;
		document.body.classList.add( 'ce-lock' );
		showLightbox( lbItems.indexOf( link ) );
		requestAnimationFrame( function () { lb.classList.add( 'is-open' ); $( '.ce-lightbox__close', lb ).focus(); } );
	}
	function closeLightbox() {
		if ( ! lb ) { return; }
		lb.classList.remove( 'is-open' );
		document.body.classList.remove( 'ce-lock' );
		setTimeout( function () { lb.hidden = true; $( '.ce-lightbox__img', lb ).removeAttribute( 'src' ); }, reduceMotion ? 0 : 300 );
		if ( lbLast ) { lbLast.focus(); }
	}
	document.addEventListener( 'click', function ( e ) {
		var link = e.target.closest( '[data-lightbox]' );
		if ( link ) { e.preventDefault(); openLightbox( link ); }
	} );

	/* Kopyalama (IBAN, bağlantı) ------------------------------------------------------ */
	function copyText( text ) {
		if ( navigator.clipboard && window.isSecureContext ) {
			return navigator.clipboard.writeText( text );
		}
		return new Promise( function ( resolve, reject ) {
			var ta = document.createElement( 'textarea' );
			ta.value = text;
			ta.setAttribute( 'readonly', '' );
			ta.style.position = 'fixed';
			ta.style.opacity = '0';
			document.body.appendChild( ta );
			ta.select();
			try { document.execCommand( 'copy' ) ? resolve() : reject(); } catch ( err ) { reject( err ); }
			ta.remove();
		} );
	}
	document.addEventListener( 'click', function ( e ) {
		var btn = e.target.closest( '[data-copy]' );
		if ( ! btn ) { return; }
		copyText( btn.dataset.copy ).then( function () {
			toast( btn.dataset.copyMsg || t( 'copied', 'IBAN panoya kopyalandı.' ) );
			btn.classList.add( 'is-done' );
			setTimeout( function () { btn.classList.remove( 'is-done' ); }, 1800 );
		} ).catch( function () {
			toast( t( 'copyFail', 'Kopyalanamadı.' ), 'error' );
		} );
	} );

	/* Dosya seçimi -------------------------------------------------------------------- */
	var ALLOWED = [ 'pdf', 'jpg', 'jpeg', 'png', 'webp' ];
	function fileError( input ) {
		var maxMb = parseInt( input.dataset.maxMb, 10 ) || CE.maxMb || 10;
		var maxFiles = parseInt( input.dataset.maxFiles, 10 ) || CE.maxFiles || 5;
		var files = Array.prototype.slice.call( input.files || [] );
		if ( files.length > maxFiles ) { return t( 'fileCount', 'En fazla dosya sayısı aşıldı.' ) + ' (' + maxFiles + ')'; }
		for ( var i = 0; i < files.length; i++ ) {
			var ext = files[ i ].name.split( '.' ).pop().toLowerCase();
			if ( ALLOWED.indexOf( ext ) === -1 ) { return t( 'fileType' ); }
			if ( files[ i ].size > maxMb * 1048576 ) { return t( 'fileSize' ) + ' (' + maxMb + ' MB)'; }
		}
		return '';
	}
	function formatSize( b ) { return b > 1048576 ? ( b / 1048576 ).toFixed( 1 ) + ' MB' : Math.max( 1, Math.round( b / 1024 ) ) + ' KB'; }
	$$( '[data-drop]' ).forEach( function ( drop ) {
		var input = $( 'input[type="file"]', drop );
		var list = drop.parentElement.querySelector( '[data-file-list]' );
		var render = function () {
			if ( ! list ) { return; }
			list.innerHTML = '';
			Array.prototype.forEach.call( input.files || [], function ( f ) {
				var li = document.createElement( 'li' );
				var ext = f.name.split( '.' ).pop().toLowerCase();
				if ( ALLOWED.indexOf( ext ) === -1 ) { li.className = 'is-bad'; }
				var name = document.createElement( 'span' );
				name.textContent = f.name;
				var size = document.createElement( 'small' );
				size.textContent = formatSize( f.size );
				li.appendChild( name );
				li.appendChild( size );
				list.appendChild( li );
			} );
			setFieldError( input.form, 'files', fileError( input ) );
		};
		input.addEventListener( 'change', render );
		[ 'dragenter', 'dragover' ].forEach( function ( ev ) { drop.addEventListener( ev, function () { drop.classList.add( 'is-drag' ); } ); } );
		[ 'dragleave', 'drop' ].forEach( function ( ev ) { drop.addEventListener( ev, function () { drop.classList.remove( 'is-drag' ); } ); } );
	} );

	/* Formlar ------------------------------------------------------------------------- */
	function setFieldError( form, name, message ) {
		var err = form.querySelector( '[data-error-for="' + name + '"]' );
		var field = form.querySelector( '[name="' + name + '"], [name="' + name + '[]"]' );
		if ( err ) { err.textContent = message || ''; }
		if ( field ) {
			var group = field.closest( '.ce-fg' );
			if ( group ) { group.classList.toggle( 'has-error', !! message ); }
			if ( message ) { field.setAttribute( 'aria-invalid', 'true' ); } else { field.removeAttribute( 'aria-invalid' ); }
		}
	}
	function validate( form ) {
		var errors = {};
		$$( '[required]', form ).forEach( function ( field ) {
			var value = 'checkbox' === field.type ? field.checked : field.value.trim();
			if ( ! value ) { errors[ field.name ] = t( 'required', 'Bu alan zorunludur.' ); }
		} );
		$$( 'input[type="email"]', form ).forEach( function ( field ) {
			if ( field.value && ! /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test( field.value.trim() ) ) { errors[ field.name ] = t( 'email' ); }
		} );
		$$( 'input[type="tel"]', form ).forEach( function ( field ) {
			var digits = field.value.replace( /\D/g, '' );
			if ( field.value && ( digits.length < 10 || digits.length > 15 ) ) { errors[ field.name ] = 'Geçerli bir telefon numarası girin.'; }
		} );
		var file = $( 'input[type="file"]', form );
		if ( file ) {
			var fe = fileError( file );
			if ( fe ) { errors.files = fe; }
		}
		return errors;
	}
	function setStatus( form, ok, message ) {
		var box = $( '[data-form-status]', form );
		if ( ! box ) { return; }
		box.hidden = false;
		box.className = 'ce-form__status ' + ( ok ? 'is-success' : 'is-error' );
		box.innerHTML = ok ? ICONS.check : ICONS.alert;
		var span = document.createElement( 'span' );
		span.textContent = message;
		box.appendChild( span );
	}
	$$( '[data-ajax-form]' ).forEach( function ( form ) {
		var busy = false;
		$$( 'input, select, textarea', form ).forEach( function ( field ) {
			var evt = 'checkbox' === field.type || 'SELECT' === field.tagName ? 'change' : 'input';
			field.addEventListener( evt, function () {
				if ( field.closest( '.has-error' ) && 'file' !== field.type ) { setFieldError( form, field.name, '' ); }
			} );
		} );
		form.addEventListener( 'submit', function ( e ) {
			if ( ! window.fetch || ! window.FormData ) { return; }
			e.preventDefault();
			if ( busy ) { return; }
			$$( '[data-error-for]', form ).forEach( function ( el ) { setFieldError( form, el.dataset.errorFor, '' ); } );
			var errors = validate( form );
			var keys = Object.keys( errors );
			if ( keys.length ) {
				keys.forEach( function ( k ) { setFieldError( form, k, errors[ k ] ); } );
				setStatus( form, false, 'Lütfen işaretli alanları kontrol edin.' );
				var first = form.querySelector( '[aria-invalid="true"]' );
				if ( first ) { first.focus(); }
				return;
			}
			busy = true;
			var btn = $( '[data-submit]', form );
			var label = btn ? $( '.ce-btn__label', btn ) : null;
			var original = label ? label.textContent : '';
			if ( btn ) { btn.classList.add( 'is-loading' ); btn.disabled = true; btn.setAttribute( 'aria-busy', 'true' ); }
			if ( label ) { label.textContent = t( 'sending', 'Gönderiliyor…' ); }

			fetch( CE.ajax, { method: 'POST', body: new FormData( form ), credentials: 'same-origin' } )
				.then( function ( res ) {
					return res.json().catch( function () { throw new Error( 'json' ); } );
				} )
				.then( function ( json ) {
					var data = json && json.data ? json.data : {};
					if ( json && json.success ) {
						setStatus( form, true, data.message || 'Gönderildi.' );
						toast( data.message || 'Gönderildi.' );
						form.reset();
						var list = $( '[data-file-list]', form );
						if ( list ) { list.innerHTML = ''; }
					} else {
						var errs = data.errors || {};
						Object.keys( errs ).forEach( function ( k ) { setFieldError( form, k, errs[ k ] ); } );
						setStatus( form, false, data.message || t( 'error' ) );
					}
				} )
				.catch( function () { setStatus( form, false, t( 'error', 'Bir hata oluştu.' ) ); } )
				.then( function () {
					busy = false;
					if ( btn ) { btn.classList.remove( 'is-loading' ); btn.disabled = false; btn.removeAttribute( 'aria-busy' ); }
					if ( label ) { label.textContent = original; }
					var status = $( '[data-form-status]', form );
					if ( status ) { status.scrollIntoView( { behavior: reduceMotion ? 'auto' : 'smooth', block: 'center' } ); }
				} );
		} );
	} );

	/* Kırık görseller ------------------------------------------------------------------ */
	document.addEventListener( 'error', function ( e ) {
		var img = e.target;
		if ( img && 'IMG' === img.tagName && ! img.closest( '.ce-lightbox' ) ) {
			img.classList.add( 'is-broken' );
			if ( img.parentElement ) { img.parentElement.classList.add( 'ce-media-fallback' ); }
		}
	}, true );

	/* Çerez onayı ve analitik ------------------------------------------------------------ */
	var cookieBox = $( '[data-cookie]' );
	function getConsent() {
		try { return window.localStorage.getItem( 'ce_consent' ); } catch ( err ) {
			var m = document.cookie.match( /(?:^|; )ce_consent=([^;]+)/ );
			return m ? m[ 1 ] : null;
		}
	}
	function setConsent( value ) {
		try { window.localStorage.setItem( 'ce_consent', value ); } catch ( err ) { /* yoksay */ }
		document.cookie = 'ce_consent=' + value + ';path=/;max-age=' + ( 180 * 86400 ) + ';SameSite=Lax' + ( 'https:' === location.protocol ? ';Secure' : '' );
	}
	function loadAnalytics() {
		var a = CE.analytics || {};
		if ( window.ceAnalyticsLoaded ) { return; }
		window.ceAnalyticsLoaded = true;
		if ( a.gtm ) {
			window.dataLayer = window.dataLayer || [];
			window.dataLayer.push( { 'gtm.start': Date.now(), event: 'gtm.js' } );
			var g = document.createElement( 'script' );
			g.async = true;
			g.src = 'https://www.googletagmanager.com/gtm.js?id=' + encodeURIComponent( a.gtm );
			document.head.appendChild( g );
		}
		if ( a.ga4 ) {
			var s = document.createElement( 'script' );
			s.async = true;
			s.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent( a.ga4 );
			document.head.appendChild( s );
			window.dataLayer = window.dataLayer || [];
			window.gtag = function () { window.dataLayer.push( arguments ); };
			window.gtag( 'js', new Date() );
			window.gtag( 'config', a.ga4, { anonymize_ip: true } );
		}
	}
	var consent = getConsent();
	if ( 'all' === consent ) { loadAnalytics(); }
	if ( cookieBox ) {
		if ( ! consent ) { cookieBox.hidden = false; }
		$$( '[data-cookie-choice]', cookieBox ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				setConsent( btn.dataset.cookieChoice );
				cookieBox.hidden = true;
				if ( 'all' === btn.dataset.cookieChoice ) { loadAnalytics(); }
			} );
		} );
		$$( '[data-cookie-open]' ).forEach( function ( btn ) {
			btn.addEventListener( 'click', function () {
				cookieBox.hidden = false;
				var first = $( 'button', cookieBox );
				if ( first ) { first.focus(); }
			} );
		} );
	}
}() );
