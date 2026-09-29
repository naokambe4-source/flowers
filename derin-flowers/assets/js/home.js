/* Derin Flowers — ana sayfa hero slider */
( function () {
	'use strict';

	var hero = document.querySelector( '[data-df-hero]' );
	if ( ! hero ) {
		return;
	}
	var slides = Array.prototype.slice.call( hero.querySelectorAll( '.df-hero__slide' ) );
	var dots = Array.prototype.slice.call( hero.querySelectorAll( '[data-df-hero-dot]' ) );
	var reduce = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	var interval = parseInt( hero.getAttribute( 'data-interval' ), 10 ) || 7000;
	var autoplay = '1' === hero.getAttribute( 'data-autoplay' ) && ! reduce && slides.length > 1;
	var idx = 0;
	var timer = null;

	hero.style.setProperty( '--df-hero-interval', interval + 'ms' );

	// Arka plan görsellerini geciktirerek yükle (ilk slayt dışındakiler).
	function hydrate( slide ) {
		if ( slide && slide.hasAttribute( 'data-style' ) ) {
			slide.setAttribute( 'style', slide.getAttribute( 'data-style' ) );
			slide.removeAttribute( 'data-style' );
		}
	}

	function playVideo( slide ) {
		var v = slide && slide.querySelector( '.df-hero__video' );
		if ( ! v || reduce ) {
			return;
		}
		if ( v.getAttribute( 'data-src' ) && ! v.getAttribute( 'src' ) ) {
			v.src = v.getAttribute( 'data-src' );
		}
		var p = v.play();
		if ( p && p.catch ) {
			p.catch( function () {} );
		}
	}

	function go( n ) {
		if ( slides.length < 2 ) {
			return;
		}
		n = ( n + slides.length ) % slides.length;
		if ( n === idx ) {
			return;
		}
		hydrate( slides[ n ] );
		hydrate( slides[ ( n + 1 ) % slides.length ] );
		slides[ idx ].classList.remove( 'is-active' );
		slides[ idx ].setAttribute( 'aria-hidden', 'true' );
		slides[ idx ].querySelectorAll( 'a, button' ).forEach( function ( a ) {
			a.setAttribute( 'tabindex', '-1' );
		} );
		var oldVideo = slides[ idx ].querySelector( '.df-hero__video' );
		if ( oldVideo ) {
			oldVideo.pause();
		}
		idx = n;
		slides[ idx ].classList.add( 'is-active' );
		slides[ idx ].removeAttribute( 'aria-hidden' );
		slides[ idx ].querySelectorAll( 'a, button' ).forEach( function ( a ) {
			a.removeAttribute( 'tabindex' );
		} );
		playVideo( slides[ idx ] );
		dots.forEach( function ( d, i ) {
			d.classList.toggle( 'is-active', i === idx );
			if ( i === idx ) {
				d.setAttribute( 'aria-current', 'true' );
			} else {
				d.removeAttribute( 'aria-current' );
			}
		} );
		restart();
	}

	function restart() {
		if ( ! autoplay ) {
			return;
		}
		clearTimeout( timer );
		hero.classList.remove( 'is-playing' );
		void hero.offsetWidth; // İlerleme çubuğu animasyonunu sıfırla.
		hero.classList.add( 'is-playing' );
		timer = setTimeout( function () {
			go( idx + 1 );
		}, interval );
	}

	function pause() {
		clearTimeout( timer );
		hero.classList.remove( 'is-playing' );
	}

	hero.addEventListener( 'click', function ( e ) {
		if ( e.target.closest( '[data-df-hero-prev]' ) ) {
			go( idx - 1 );
		} else if ( e.target.closest( '[data-df-hero-next]' ) ) {
			go( idx + 1 );
		} else if ( e.target.closest( '[data-df-hero-dot]' ) ) {
			go( parseInt( e.target.closest( '[data-df-hero-dot]' ).getAttribute( 'data-df-hero-dot' ), 10 ) );
		}
	} );

	// Dokunmatik kaydırma.
	var sx = 0;
	var sy = 0;
	hero.addEventListener( 'touchstart', function ( e ) {
		sx = e.touches[ 0 ].clientX;
		sy = e.touches[ 0 ].clientY;
	}, { passive: true } );
	hero.addEventListener( 'touchend', function ( e ) {
		var dx = e.changedTouches[ 0 ].clientX - sx;
		var dy = e.changedTouches[ 0 ].clientY - sy;
		if ( Math.abs( dx ) > 50 && Math.abs( dx ) > Math.abs( dy ) ) {
			go( dx < 0 ? idx + 1 : idx - 1 );
		}
	}, { passive: true } );

	if ( autoplay ) {
		hero.addEventListener( 'mouseenter', pause );
		hero.addEventListener( 'mouseleave', restart );
		hero.addEventListener( 'focusin', pause );
		hero.addEventListener( 'focusout', restart );
		document.addEventListener( 'visibilitychange', function () {
			if ( document.hidden ) {
				pause();
			} else {
				restart();
			}
		} );
	}

	// İlk slayttaki videoyu ve ikinci slaytın görselini sayfa yüklendikten sonra hazırla.
	window.addEventListener( 'load', function () {
		playVideo( slides[ 0 ] );
		hydrate( slides[ 1 ] );
		restart();
	} );
}() );
