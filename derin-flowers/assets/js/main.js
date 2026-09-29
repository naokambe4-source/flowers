/* Derin Flowers — genel etkileşimler (bağımlılıksız; WooCommerce olayları için jQuery varsa kullanılır) */
( function () {
	'use strict';

	var D = window.DF || { ajax: '', nonce: '', i18n: {} };
	var $ = window.jQuery;
	var doc = document;
	var body = doc.body;
	var qs = function ( s, c ) { return ( c || doc ).querySelector( s ); };
	var qsa = function ( s, c ) { return Array.prototype.slice.call( ( c || doc ).querySelectorAll( s ) ); };

	doc.documentElement.classList.remove( 'no-js' );

	/* ---------- Toast ---------- */
	var toastEl = qs( '[data-df-toast]' );
	var toastTimer;
	function toast( msg ) {
		if ( ! toastEl || ! msg ) {
			return;
		}
		toastEl.textContent = msg;
		toastEl.classList.add( 'is-visible' );
		clearTimeout( toastTimer );
		toastTimer = setTimeout( function () {
			toastEl.classList.remove( 'is-visible' );
		}, 2600 );
	}

	/* ---------- AJAX yardımcı ---------- */
	function post( action, data ) {
		var fd = new FormData();
		fd.append( 'action', action );
		fd.append( 'nonce', D.nonce );
		Object.keys( data || {} ).forEach( function ( k ) {
			fd.append( k, data[ k ] );
		} );
		return fetch( D.ajax, { method: 'POST', body: fd, credentials: 'same-origin' } ).then( function ( r ) {
			return r.json();
		} );
	}

	/* ---------- Header: kaydırma ---------- */
	var header = qs( '#df-header' );
	if ( header ) {
		var ticking = false;
		window.addEventListener( 'scroll', function () {
			if ( ticking ) {
				return;
			}
			ticking = true;
			requestAnimationFrame( function () {
				header.classList.toggle( 'is-scrolled', window.scrollY > 60 );
				ticking = false;
			} );
		}, { passive: true } );
	}

	/* ---------- Çekmece / katman yönetimi ---------- */
	var overlay = qs( '[data-df-overlay]' );
	var openEl = null;
	var lastFocus = null;

	function openPanel( id ) {
		var el = doc.getElementById( id );
		if ( ! el ) {
			return false;
		}
		if ( openEl ) {
			closePanel();
		}
		lastFocus = doc.activeElement;
		openEl = el;
		el.classList.add( 'is-open' );
		el.setAttribute( 'aria-hidden', 'false' );
		qsa( '[data-df-open="' + id + '"]' ).forEach( function ( b ) {
			b.setAttribute( 'aria-expanded', 'true' );
		} );
		if ( overlay ) {
			overlay.hidden = false;
			requestAnimationFrame( function () {
				overlay.classList.add( 'is-visible' );
			} );
		}
		body.classList.add( 'df-lock' );
		setTimeout( function () {
			var f = el.querySelector( 'input[type="search"], .df-drawer__close, button, a' );
			if ( f ) {
				f.focus( { preventScroll: true } );
			}
		}, 80 );
		return true;
	}

	function closePanel() {
		if ( ! openEl ) {
			return;
		}
		var id = openEl.id;
		openEl.classList.remove( 'is-open' );
		openEl.setAttribute( 'aria-hidden', 'true' );
		qsa( '[data-df-open="' + id + '"]' ).forEach( function ( b ) {
			b.setAttribute( 'aria-expanded', 'false' );
		} );
		openEl = null;
		if ( overlay ) {
			overlay.classList.remove( 'is-visible' );
			setTimeout( function () {
				if ( ! openEl ) {
					overlay.hidden = true;
				}
			}, 350 );
		}
		body.classList.remove( 'df-lock' );
		if ( lastFocus && lastFocus.focus ) {
			lastFocus.focus( { preventScroll: true } );
		}
	}

	doc.addEventListener( 'click', function ( e ) {
		var opener = e.target.closest( '[data-df-open]' );
		if ( opener ) {
			if ( openPanel( opener.getAttribute( 'data-df-open' ) ) ) {
				e.preventDefault();
			}
			return;
		}
		if ( e.target.closest( '[data-df-close]' ) || e.target === overlay ) {
			e.preventDefault();
			closePanel();
			return;
		}
		// Arama katmanında boş alana tıklama.
		if ( openEl && openEl.id === 'df-search' && e.target === openEl ) {
			closePanel();
		}
	} );

	doc.addEventListener( 'keydown', function ( e ) {
		if ( 'Escape' === e.key && openEl ) {
			closePanel();
		}
		// Basit odak tuzağı.
		if ( 'Tab' === e.key && openEl ) {
			var f = qsa( 'a[href], button:not([disabled]), input:not([type="hidden"]), select, textarea', openEl ).filter( function ( n ) {
				return n.offsetParent !== null;
			} );
			if ( ! f.length ) {
				return;
			}
			if ( e.shiftKey && doc.activeElement === f[ 0 ] ) {
				e.preventDefault();
				f[ f.length - 1 ].focus();
			} else if ( ! e.shiftKey && doc.activeElement === f[ f.length - 1 ] ) {
				e.preventDefault();
				f[ 0 ].focus();
			}
		}
	} );

	/* ---------- Menü alt öğeleri (mobil + dokunmatik) ---------- */
	doc.addEventListener( 'click', function ( e ) {
		var caret = e.target.closest( '.df-nav__caret' );
		if ( caret ) {
			e.preventDefault();
			var li = caret.closest( 'li' );
			var open = li.classList.toggle( 'is-open' );
			caret.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
			return;
		}
		var t = e.target.closest( '.df-catlist__toggle' );
		if ( t ) {
			e.preventDefault();
			var item = t.closest( '.df-catlist__item' );
			t.setAttribute( 'aria-expanded', item.classList.toggle( 'is-open' ) ? 'true' : 'false' );
		}
	} );

	/* ---------- Canlı arama ---------- */
	var sInput = qs( '[data-df-live-search]' );
	var sResults = qs( '[data-df-search-results]' );
	if ( sInput && sResults && D.ajax ) {
		var sTimer;
		var sCtrl;
		sInput.addEventListener( 'input', function () {
			var q = sInput.value.trim();
			clearTimeout( sTimer );
			if ( q.length < 2 ) {
				sResults.innerHTML = '';
				return;
			}
			sTimer = setTimeout( function () {
				if ( sCtrl && sCtrl.abort ) {
					sCtrl.abort();
				}
				sCtrl = window.AbortController ? new AbortController() : null;
				var url = D.ajax + '?action=df_live_search&nonce=' + encodeURIComponent( D.nonce ) + '&q=' + encodeURIComponent( q );
				fetch( url, { credentials: 'same-origin', signal: sCtrl ? sCtrl.signal : undefined } )
					.then( function ( r ) {
						return r.json();
					} )
					.then( function ( res ) {
						if ( ! res || ! res.success ) {
							return;
						}
						var items = res.data.items || [];
						if ( ! items.length ) {
							sResults.innerHTML = '<p class="df-search__empty">' + esc( D.i18n.noResults ) + '</p>';
							return;
						}
						var html = '<div class="df-search__grid">';
						items.forEach( function ( it ) {
							html += '<a class="df-search__item" href="' + esc( it.url ) + '"><img src="' + esc( it.image ) + '" alt="" loading="lazy"><span><strong>' + esc( it.title ) + '</strong><span>' + esc( it.price ) + '</span></span></a>';
						} );
						html += '</div>';
						if ( res.data.total > items.length ) {
							html += '<a class="df-link-arrow df-search__all" href="' + esc( res.data.all ) + '">' + esc( D.i18n.allResults ) + ' (' + res.data.total + ')</a>';
						}
						sResults.innerHTML = html;
					} )
					.catch( function () {} );
			}, 220 );
		} );
	}

	function esc( s ) {
		return String( s == null ? '' : s ).replace( /[&<>"']/g, function ( c ) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ c ];
		} );
	}

	/* ---------- Favoriler ---------- */
	function setFavCount( n ) {
		qsa( '.df-fav-count' ).forEach( function ( el ) {
			el.textContent = n;
			el.classList.toggle( 'is-empty', ! n );
		} );
	}
	doc.addEventListener( 'click', function ( e ) {
		var btn = e.target.closest( '.df-fav' );
		if ( ! btn ) {
			return;
		}
		e.preventDefault();
		var id = btn.getAttribute( 'data-product' );
		var willAdd = ! btn.classList.contains( 'is-active' );
		// İyimser güncelleme.
		qsa( '.df-fav[data-product="' + id + '"]' ).forEach( function ( b ) {
			b.classList.toggle( 'is-active', willAdd );
			b.setAttribute( 'aria-pressed', willAdd ? 'true' : 'false' );
		} );
		btn.classList.remove( 'is-pop' );
		void btn.offsetWidth;
		btn.classList.add( 'is-pop' );
		post( 'df_wishlist_toggle', { product_id: id } ).then( function ( res ) {
			if ( res && res.success ) {
				setFavCount( res.data.count );
				toast( 'added' === res.data.status ? D.i18n.favAdded : D.i18n.favRemoved );
				var page = qs( '[data-df-wishlist-page]' );
				if ( page && 'removed' === res.data.status ) {
					var card = btn.closest( '.df-card' );
					if ( card ) {
						card.remove();
					}
				}
			}
		} ).catch( function () {
			toast( D.i18n.error );
		} );
	} );

	/* ---------- Sepet: eklenince çekmeceyi aç ---------- */
	if ( $ ) {
		$( body ).on( 'added_to_cart', function () {
			var count = qs( '.df-cart-count' );
			if ( count ) {
				count.classList.remove( 'is-bump' );
				void count.offsetWidth;
				count.classList.add( 'is-bump' );
			}
			if ( ! openPanel( 'df-cart-drawer' ) ) {
				toast( D.i18n.added );
			}
		} );
	}
	// Tekli ürün sayfasında normal (sayfa yenilemeli) ekleme sonrası çekmeceyi aç.
	if ( qs( '.woocommerce-message .df-added-msg' ) && doc.getElementById( 'df-cart-drawer' ) ) {
		setTimeout( function () {
			openPanel( 'df-cart-drawer' );
		}, 400 );
	}

	/* ---------- Adet butonları ---------- */
	function enhanceQty( ctx ) {
		qsa( '.quantity', ctx ).forEach( function ( q ) {
			var input = q.querySelector( 'input.qty' );
			if ( ! input || input.type === 'hidden' || q.querySelector( '.df-qty-btn' ) ) {
				return;
			}
			var minus = doc.createElement( 'button' );
			var plus = doc.createElement( 'button' );
			minus.type = plus.type = 'button';
			minus.className = plus.className = 'df-qty-btn';
			minus.setAttribute( 'aria-label', 'Azalt' );
			plus.setAttribute( 'aria-label', 'Arttır' );
			minus.textContent = '−';
			plus.textContent = '+';
			q.insertBefore( minus, input );
			q.appendChild( plus );
			function step( dir ) {
				var v = parseFloat( input.value ) || 0;
				var s = parseFloat( input.step ) || 1;
				var min = input.min !== '' ? parseFloat( input.min ) : 0;
				var max = input.max !== '' ? parseFloat( input.max ) : Infinity;
				v = Math.min( max, Math.max( min, v + dir * s ) );
				input.value = v;
				input.dispatchEvent( new Event( 'change', { bubbles: true } ) );
				if ( $ ) {
					$( input ).trigger( 'change' );
				}
			}
			minus.addEventListener( 'click', function () {
				step( -1 );
			} );
			plus.addEventListener( 'click', function () {
				step( 1 );
			} );
		} );
	}
	enhanceQty( doc );
	if ( $ ) {
		$( body ).on( 'updated_cart_totals', function () {
			enhanceQty( doc );
		} );
	}

	/* ---------- Geri sayım ---------- */
	qsa( '[data-df-countdown]' ).forEach( function ( el ) {
		var left = parseInt( el.getAttribute( 'data-df-countdown' ), 10 );
		var out = el.querySelector( '[data-df-countdown-text]' );
		if ( ! left || ! out ) {
			return;
		}
		var end = Date.now() + left * 1000;
		function tick() {
			var s = Math.max( 0, Math.round( ( end - Date.now() ) / 1000 ) );
			var h = Math.floor( s / 3600 );
			var m = Math.floor( ( s % 3600 ) / 60 );
			out.textContent = ( h ? h + ' sa ' : '' ) + m + ' dk';
			if ( s > 0 ) {
				setTimeout( tick, 30000 );
			}
		}
		tick();
	} );

	/* ---------- Bülten ---------- */
	qsa( '[data-df-newsletter]' ).forEach( function ( form ) {
		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			var input = form.querySelector( 'input[type="email"]' );
			var msg = form.querySelector( '[data-df-newsletter-msg]' );
			var btn = form.querySelector( 'button' );
			btn.disabled = true;
			post( 'df_subscribe', { email: input.value } ).then( function ( res ) {
				msg.classList.toggle( 'is-error', ! res.success );
				msg.textContent = ( res.data && res.data.message ) || ( res.success ? D.i18n.subscribed : D.i18n.error );
				if ( res.success ) {
					input.value = '';
				}
			} ).catch( function () {
				msg.classList.add( 'is-error' );
				msg.textContent = D.i18n.error;
			} ).then( function () {
				btn.disabled = false;
			} );
		} );
	} );

	/* ---------- Giriş / kayıt sekmeleri ---------- */
	qsa( '[data-df-auth]' ).forEach( function ( wrap ) {
		qsa( '[data-df-auth-tab]', wrap ).forEach( function ( tab ) {
			tab.addEventListener( 'click', function () {
				var t = tab.getAttribute( 'data-df-auth-tab' );
				qsa( '[data-df-auth-tab]', wrap ).forEach( function ( x ) {
					x.classList.toggle( 'is-active', x === tab );
					x.setAttribute( 'aria-selected', x === tab ? 'true' : 'false' );
				} );
				qsa( '[data-df-auth-pane]', wrap ).forEach( function ( p ) {
					p.classList.toggle( 'is-active', p.getAttribute( 'data-df-auth-pane' ) === t );
				} );
			} );
		} );
	} );

	/* ---------- Teslimat bölgeleri araması ---------- */
	var zq = qs( '[data-df-zone-search]' );
	if ( zq ) {
		zq.addEventListener( 'input', function () {
			var v = zq.value.trim().toLocaleLowerCase( 'tr' );
			qsa( '.df-zones__list li' ).forEach( function ( li ) {
				li.hidden = v && li.getAttribute( 'data-name' ).indexOf( v ) === -1;
			} );
		} );
	}

	/* ---------- Ürün galerisi ---------- */
	qsa( '[data-df-gallery]' ).forEach( function ( gal ) {
		var slides = qsa( '.df-gallery__slide', gal );
		var thumbs = qsa( '[data-df-gallery-go]', gal );
		var current = qs( '[data-df-gallery-current]', gal );
		var idx = 0;

		function stopMedia( slide ) {
			var v = slide.querySelector( 'video' );
			if ( v && ! v.hasAttribute( 'data-autoplay' ) ) {
				v.pause();
			}
			var f = slide.querySelector( 'iframe' );
			if ( f ) {
				var btn = doc.createElement( 'div' );
				btn.innerHTML = f.getAttribute( 'data-restore' ) || '';
				f.replaceWith( btn.firstChild || doc.createTextNode( '' ) );
			}
		}

		function go( n ) {
			if ( ! slides.length ) {
				return;
			}
			n = ( n + slides.length ) % slides.length;
			if ( n === idx && slides[ n ].classList.contains( 'is-active' ) ) {
				return;
			}
			stopMedia( slides[ idx ] );
			slides[ idx ].classList.remove( 'is-active' );
			idx = n;
			slides[ idx ].classList.add( 'is-active' );
			var auto = slides[ idx ].querySelector( 'video[data-autoplay]' );
			if ( auto ) {
				auto.play().catch( function () {} );
			}
			thumbs.forEach( function ( t, i ) {
				t.classList.toggle( 'is-active', i === idx );
			} );
			if ( thumbs[ idx ] && thumbs[ idx ].scrollIntoView && window.matchMedia( '(min-width: 901px)' ).matches === false ) {
				thumbs[ idx ].parentNode.scrollTo( { left: thumbs[ idx ].offsetLeft - 20, behavior: 'smooth' } );
			}
			if ( current ) {
				current.textContent = idx + 1;
			}
		}

		gal.addEventListener( 'click', function ( e ) {
			if ( e.target.closest( '[data-df-gallery-prev]' ) ) {
				go( idx - 1 );
			} else if ( e.target.closest( '[data-df-gallery-next]' ) ) {
				go( idx + 1 );
			} else if ( e.target.closest( '[data-df-gallery-go]' ) ) {
				go( parseInt( e.target.closest( '[data-df-gallery-go]' ).getAttribute( 'data-df-gallery-go' ), 10 ) );
			} else if ( e.target.closest( '[data-df-embed]' ) ) {
				var b = e.target.closest( '[data-df-embed]' );
				var src = b.getAttribute( 'data-df-embed' );
				var ifr = doc.createElement( 'iframe' );
				ifr.src = src + ( src.indexOf( '?' ) > -1 ? '&' : '?' ) + 'autoplay=1';
				ifr.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen';
				ifr.allowFullscreen = true;
				ifr.title = 'Ürün videosu';
				ifr.setAttribute( 'data-restore', b.outerHTML );
				b.replaceWith( ifr );
			} else if ( e.target.closest( '[data-df-zoom]' ) || e.target.closest( '[data-df-zoom-open]' ) ) {
				e.preventDefault();
				lightbox.open( gal, idx );
			}
		} );

		// Dokunmatik kaydırma.
		var main = qs( '.df-gallery__main', gal );
		var sx = 0;
		var sy = 0;
		main.addEventListener( 'touchstart', function ( e ) {
			sx = e.touches[ 0 ].clientX;
			sy = e.touches[ 0 ].clientY;
		}, { passive: true } );
		main.addEventListener( 'touchend', function ( e ) {
			var dx = e.changedTouches[ 0 ].clientX - sx;
			var dy = e.changedTouches[ 0 ].clientY - sy;
			if ( Math.abs( dx ) > 45 && Math.abs( dx ) > Math.abs( dy ) ) {
				go( dx < 0 ? idx + 1 : idx - 1 );
			}
		}, { passive: true } );
		gal.addEventListener( 'keydown', function ( e ) {
			if ( 'ArrowLeft' === e.key ) {
				go( idx - 1 );
			} else if ( 'ArrowRight' === e.key ) {
				go( idx + 1 );
			}
		} );

		// Varyasyon seçilince ilk görsele dön (WooCommerce ana görseli günceller).
		if ( $ ) {
			var firstImg = slides.findIndex ? slides.findIndex( function ( s ) {
				return s.classList.contains( 'woocommerce-product-gallery__image' );
			} ) : 0;
			$( gal ).closest( '.product' ).find( '.variations_form' ).on( 'found_variation reset_image', function () {
				go( Math.max( 0, firstImg ) );
				var img = slides[ Math.max( 0, firstImg ) ] && slides[ Math.max( 0, firstImg ) ].querySelector( 'img' );
				if ( img && thumbs[ Math.max( 0, firstImg ) ] ) {
					var ti = thumbs[ Math.max( 0, firstImg ) ].querySelector( 'img' );
					if ( ti ) {
						setTimeout( function () {
							ti.src = img.currentSrc || img.src;
							ti.removeAttribute( 'srcset' );
						}, 50 );
					}
				}
			} );
		}

		var auto = slides[ 0 ] && slides[ 0 ].querySelector( 'video[data-autoplay]' );
		if ( auto ) {
			auto.play().catch( function () {} );
		}
	} );

	/* ---------- Lightbox ---------- */
	var lightbox = ( function () {
		var box;
		var img;
		var list = [];
		var i = 0;
		function build() {
			box = doc.createElement( 'div' );
			box.className = 'df-lightbox';
			box.setAttribute( 'role', 'dialog' );
			box.setAttribute( 'aria-modal', 'true' );
			box.setAttribute( 'aria-label', 'Görsel' );
			box.innerHTML = '<img alt=""><button type="button" class="df-lightbox__close" aria-label="Kapat">✕</button><button type="button" class="df-lightbox__nav df-lightbox__nav--prev" aria-label="Önceki">‹</button><button type="button" class="df-lightbox__nav df-lightbox__nav--next" aria-label="Sonraki">›</button>';
			body.appendChild( box );
			img = box.querySelector( 'img' );
			box.addEventListener( 'click', function ( e ) {
				if ( e.target.closest( '.df-lightbox__nav--prev' ) ) {
					show( i - 1 );
				} else if ( e.target.closest( '.df-lightbox__nav--next' ) ) {
					show( i + 1 );
				} else {
					close();
				}
			} );
			doc.addEventListener( 'keydown', function ( e ) {
				if ( ! box.classList.contains( 'is-open' ) ) {
					return;
				}
				if ( 'Escape' === e.key ) {
					close();
				} else if ( 'ArrowLeft' === e.key ) {
					show( i - 1 );
				} else if ( 'ArrowRight' === e.key ) {
					show( i + 1 );
				}
			} );
		}
		function show( n ) {
			if ( ! list.length ) {
				return;
			}
			i = ( n + list.length ) % list.length;
			img.src = list[ i ];
			box.querySelectorAll( '.df-lightbox__nav' ).forEach( function ( b ) {
				b.hidden = list.length < 2;
			} );
		}
		function close() {
			box.classList.remove( 'is-open' );
			body.classList.remove( 'df-lock' );
		}
		return {
			open: function ( gal, slideIdx ) {
				if ( ! box ) {
					build();
				}
				var slides = qsa( '.df-gallery__slide', gal );
				list = [];
				var start = 0;
				slides.forEach( function ( s, k ) {
					var a = s.querySelector( '[data-df-zoom]' );
					if ( a && a.getAttribute( 'href' ) ) {
						if ( k === slideIdx ) {
							start = list.length;
						}
						list.push( a.getAttribute( 'href' ) );
					}
				} );
				if ( ! list.length ) {
					return;
				}
				box.classList.add( 'is-open' );
				body.classList.add( 'df-lock' );
				show( start );
				box.querySelector( '.df-lightbox__close' ).focus();
			}
		};
	}() );

	/* ---------- Mobil yapışkan sepet çubuğu ---------- */
	var bar = qs( '[data-df-sticky-bar]' );
	var cartForm = qs( '.df-single form.cart' );
	if ( bar && cartForm && 'IntersectionObserver' in window ) {
		var io = new IntersectionObserver( function ( entries ) {
			var visible = entries[ 0 ].isIntersecting || entries[ 0 ].boundingClientRect.top > 0;
			bar.classList.toggle( 'is-visible', ! visible );
			bar.setAttribute( 'aria-hidden', visible ? 'true' : 'false' );
		} );
		io.observe( cartForm );
		bar.querySelector( '[data-df-scroll-cart]' ).addEventListener( 'click', function () {
			cartForm.scrollIntoView( { behavior: 'smooth', block: 'center' } );
			var btn = cartForm.querySelector( '.single_add_to_cart_button' );
			if ( btn ) {
				setTimeout( function () {
					btn.focus( { preventScroll: true } );
				}, 500 );
			}
		} );
	}
}() );
