/* Derin Flowers — Tasarım Stüdyosu önizleme penceresi (iframe içinde çalışır) */
( function () {
	'use strict';

	var L = window.DFLive;
	if ( ! L ) {
		return;
	}
	// Stüdyo dışında açıldıysa (eski ?df_live=1 bağlantısı) stüdyoya yönlendir.
	if ( window.parent === window ) {
		window.location.replace( L.studio );
		return;
	}

	var body = document.body;
	body.classList.add( 'df-live-on' );

	function send( msg ) {
		msg.df = msg.df || 'x';
		window.parent.postMessage( msg, window.location.origin );
	}

	function withParams( url ) {
		try {
			var u = new URL( url, window.location.href );
			u.searchParams.set( 'df_live', '1' );
			u.searchParams.set( 'df_preview', '1' );
			return u.toString();
		} catch ( e ) {
			return url;
		}
	}

	/* ---------- Yazı alanları ---------- */
	function textOf( el ) {
		var multi = /^(textarea|lines)$/.test( el.getAttribute( 'data-df-type' ) );
		var val = el.innerText.replace( / /g, ' ' );
		if ( ! multi ) {
			val = val.replace( /\s*\n\s*/g, ' ' );
		}
		return val.replace( /\n$/, '' );
	}

	Array.prototype.forEach.call( document.querySelectorAll( '[data-df-edit]' ), function ( el ) {
		var key = el.getAttribute( 'data-df-edit' );
		var multi = /^(textarea|lines)$/.test( el.getAttribute( 'data-df-type' ) );
		try {
			el.setAttribute( 'contenteditable', 'plaintext-only' );
			if ( 'plaintext-only' !== el.contentEditable ) {
				el.setAttribute( 'contenteditable', 'true' );
			}
		} catch ( e ) {
			el.setAttribute( 'contenteditable', 'true' );
		}
		el.setAttribute( 'spellcheck', 'true' );
		el.setAttribute( 'title', multi ? 'Yazmak için tıklayın (Enter: yeni satır)' : 'Yazmak için tıklayın' );
		el.addEventListener( 'keydown', function ( e ) {
			if ( ( 'Enter' === e.key && ! multi ) || 'Escape' === e.key ) {
				e.preventDefault();
				el.blur();
			}
		} );
		el.addEventListener( 'paste', function ( e ) {
			e.preventDefault();
			var text = ( e.clipboardData || window.clipboardData ).getData( 'text/plain' );
			if ( ! multi ) {
				text = text.replace( /\s*\n\s*/g, ' ' );
			}
			document.execCommand( 'insertText', false, text );
		} );
		el.addEventListener( 'focus', function () {
			send( { df: 'focus', key: key } );
		} );
		el.addEventListener( 'input', function () {
			var val = textOf( el );
			Array.prototype.forEach.call( document.querySelectorAll( '[data-df-edit="' + key + '"]' ), function ( o ) {
				if ( o !== el ) {
					o.innerText = el.innerText;
				}
			} );
			send( { df: 'edit', key: key, value: val } );
		} );
	} );

	/* ---------- Görseller ---------- */
	Array.prototype.forEach.call( document.querySelectorAll( '[data-df-img]' ), function ( el ) {
		var btn = document.createElement( 'button' );
		btn.type = 'button';
		btn.className = 'df-live-imgbtn';
		btn.innerHTML = '<span aria-hidden="true">🖼</span> Görseli değiştir';
		var host = el.closest( '.df-hero__slide' ) || ( el.classList.contains( 'df-editorial__bg' ) ? el.closest( '.df-editorial' ) : null ) || el;
		if ( host !== el ) {
			btn.classList.add( 'df-live-imgbtn--bg' );
		}
		if ( 'static' === getComputedStyle( host ).position ) {
			host.style.position = 'relative';
		}
		host.appendChild( btn );
		btn.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			e.stopPropagation();
			send( { df: 'image', key: el.getAttribute( 'data-df-img' ) } );
		} );
	} );

	/* ---------- Bölüm araçları ---------- */
	Array.prototype.forEach.call( document.querySelectorAll( '[data-df-section]' ), function ( sec ) {
		var id = sec.getAttribute( 'data-df-section' );
		var tools = document.createElement( 'div' );
		tools.className = 'df-live-tools';
		tools.innerHTML =
			'<span class="df-live-tools__name">' + ( L.labels[ id ] || id ) + '</span>' +
			'<button type="button" data-act="edit" title="İçerik ve tasarım">✎ Düzenle</button>' +
			'<button type="button" data-act="up" title="Yukarı taşı">▲</button>' +
			'<button type="button" data-act="down" title="Aşağı taşı">▼</button>' +
			'<button type="button" data-act="toggle">' + ( '1' === sec.getAttribute( 'data-df-on' ) ? 'Gizle' : 'Göster' ) + '</button>';
		sec.insertBefore( tools, sec.firstChild );
		tools.addEventListener( 'click', function ( e ) {
			var b = e.target.closest( 'button' );
			if ( b ) {
				e.preventDefault();
				send( { df: 'section', id: id, act: b.getAttribute( 'data-act' ) } );
			}
		} );
		sec.classList.toggle( 'is-off', '1' !== sec.getAttribute( 'data-df-on' ) );
	} );

	/* ---------- Bağlantılar: önizleme içinde gezin ---------- */
	document.addEventListener( 'click', function ( e ) {
		if ( e.target.closest( '[data-df-edit], .df-live-tools, .df-live-imgbtn' ) ) {
			var inLink = e.target.closest( 'a' );
			if ( inLink ) {
				e.preventDefault();
			}
			return;
		}
		var a = e.target.closest( 'a[href]' );
		if ( ! a || a.target === '_blank' || e.defaultPrevented ) {
			return;
		}
		var href = a.getAttribute( 'href' );
		if ( ! href || '#' === href.charAt( 0 ) || /^(mailto|tel|javascript|whatsapp):/i.test( href ) ) {
			return;
		}
		var u;
		try {
			u = new URL( a.href );
		} catch ( err ) {
			return;
		}
		if ( u.origin !== window.location.origin || /\/wp-(admin|login)/.test( u.pathname ) ) {
			e.preventDefault();
			return;
		}
		e.preventDefault();
		window.location.href = withParams( a.href );
	}, true );
	// Arama gibi GET formları önizlemede kalsın.
	Array.prototype.forEach.call( document.querySelectorAll( 'form' ), function ( f ) {
		if ( ( f.getAttribute( 'method' ) || 'get' ).toLowerCase() === 'get' ) {
			[ 'df_live', 'df_preview' ].forEach( function ( n ) {
				var i = document.createElement( 'input' );
				i.type = 'hidden';
				i.name = n;
				i.value = '1';
				f.appendChild( i );
			} );
		}
	} );

	/* ---------- Stüdyodan gelen mesajlar ---------- */
	window.addEventListener( 'message', function ( e ) {
		if ( e.origin !== window.location.origin || ! e.data || ! e.data.df ) {
			return;
		}
		var m = e.data;
		if ( 'scrollTo' === m.df ) {
			window.scrollTo( 0, m.y || 0 );
		} else if ( 'setText' === m.df ) {
			Array.prototype.forEach.call( document.querySelectorAll( '[data-df-edit="' + m.key + '"]' ), function ( o ) {
				if ( document.activeElement !== o ) {
					o.innerText = m.value;
				}
			} );
		} else if ( 'show' === m.df ) {
			var t = document.querySelector( '[data-df-section="' + m.id + '"]' ) || document.querySelector( '[data-df-sec="' + m.id + '"]' );
			if ( t ) {
				// scrollIntoView üst pencereyi de kaydırır; yalnızca önizlemeyi kaydır.
				var head = document.querySelector( '.df-header' );
				var off = head && 'sticky' === getComputedStyle( head ).position ? head.offsetHeight : 0;
				window.scrollTo( { top: Math.max( 0, t.getBoundingClientRect().top + window.scrollY - off ), behavior: 'smooth' } );
				t.classList.add( 'df-live-flash' );
				setTimeout( function () {
					t.classList.remove( 'df-live-flash' );
				}, 1400 );
			}
		}
	} );

	var st;
	window.addEventListener( 'scroll', function () {
		clearTimeout( st );
		st = setTimeout( function () {
			send( { df: 'scroll', y: window.scrollY } );
		}, 120 );
	}, { passive: true } );

	send( {
		df: 'ready',
		url: window.location.href.replace( /([?&])df_(live|preview)=1&?/g, '$1' ).replace( /[?&]$/, '' ),
		title: document.title,
		home: !! L.isHome,
		is404: !! L.is404,
		context: L.context || {}
	} );
}() );
