/**
 * Can Eloksal Canlı Editör — çerçeve (düzenlenen sayfanın içinde çalışır).
 * Kabuk (window.parent.CEBShell) ile doğrudan iletişim kurar.
 */
( function () {
	'use strict';

	var D = window.CEBFrameData || {};
	var shell = null;
	try { shell = window.parent && window.parent !== window ? window.parent.CEBShell : null; } catch ( e ) { shell = null; }
	if ( ! shell ) { return; }

	// Düzenleme sırasında otomatik slayt ve kaydırmada gizlenen header kapalı.
	if ( window.CE ) { window.CE.autoplay = 0; }
	document.documentElement.classList.add( 'ceb-editing' );

	var selected = null;
	var editing = null;
	var ui, hoverBox, selectBox, hoverLabel, selectLabel, toolbar;
	var TEXT_TAGS = /^(H[1-6]|P|SPAN|A|LI|STRONG|B|EM|I|SMALL|LABEL|BUTTON|DT|DD|FIGCAPTION|BLOCKQUOTE|CODE|ADDRESS|TIME|LEGEND)$/;
	var NAMES = {
		H1: 'Ana başlık', H2: 'Başlık', H3: 'Alt başlık', H4: 'Başlık', P: 'Paragraf', A: 'Bağlantı', IMG: 'Görsel', SECTION: 'Bölüm',
		BUTTON: 'Buton', LI: 'Liste öğesi', UL: 'Liste', OL: 'Liste', SPAN: 'Metin', DIV: 'Kutu', ARTICLE: 'Kart', HEADER: 'Üst alan',
		FOOTER: 'Alt alan', NAV: 'Menü', FORM: 'Form', INPUT: 'Form alanı', STRONG: 'Kalın metin', FIGURE: 'Görsel alanı', ADDRESS: 'Adres',
		DT: 'Etiket', DD: 'Değer', SMALL: 'Küçük metin', MAIN: 'İçerik', ASIDE: 'Yan panel', SVG: 'İkon', PICTURE: 'Görsel', VIDEO: 'Video'
	};

	/* Yardımcılar ------------------------------------------------------------ */
	function isUI( el ) { return el && el.closest && el.closest( '#ceb-ui' ); }
	function esc( s ) { return String( s ).replace( /[&<>"]/g, function ( c ) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[ c ]; } ); }
	function okClass( c ) { return c && ! /^(is-|has-|ceb-|ce-js$|wp-image-|ce-hide-sm$|ce-editable$|screen-reader)/.test( c ) && c.length < 60; }

	function part( node ) {
		var tag = node.tagName.toLowerCase();
		var cls = Array.prototype.filter.call( node.classList, okClass ).slice( 0, 2 );
		return tag + ( cls.length ? '.' + cls.map( function ( c ) { return CSS.escape( c ); } ).join( '.' ) : '' );
	}

	/** Canlı sitede de geçerli, benzersiz CSS seçici. */
	function uniqueSelector( el ) {
		var path = [];
		var node = el;
		while ( node && node.nodeType === 1 && node !== document.body && node !== document.documentElement ) {
			var p;
			if ( node.id && ! /^ceb-/.test( node.id ) ) {
				p = '#' + CSS.escape( node.id );
				path.unshift( p );
				break;
			}
			p = part( node );
			var parent = node.parentElement;
			if ( parent ) {
				var same = Array.prototype.filter.call( parent.children, function ( c ) { return c !== node && c.matches( p ); } );
				if ( same.length ) {
					p += ':nth-child(' + ( Array.prototype.indexOf.call( parent.children, node ) + 1 ) + ')';
				}
			}
			path.unshift( p );
			var sel = path.join( ' > ' );
			try { if ( path.length > 1 && document.querySelectorAll( sel ).length === 1 ) { break; } } catch ( e ) { /* devam */ }
			node = parent;
		}
		return path.join( ' > ' );
	}

	/** Sayfadaki benzer tüm öğeler (bölüm + öğe sınıfı). */
	function similarSelector( el ) {
		var own = part( el );
		var sec = el.parentElement ? el.parentElement.closest( 'section[class], footer[class], header[class]' ) : null;
		if ( sec && sec !== el ) {
			var cls = Array.prototype.filter.call( sec.classList, okClass ).filter( function ( c ) { return ! /^ce-section(--|$)/.test( c ); } )[ 0 ];
			if ( cls ) { return '.' + CSS.escape( cls ) + ' ' + own; }
		}
		return own;
	}

	function extract( el, format ) {
		if ( 'html' === format ) {
			var clone = el.cloneNode( true );
			clone.removeAttribute( 'contenteditable' );
			clone.classList.remove( 'ceb-selected', 'ceb-is-editing' );
			[ 'data-ce-edit', 'data-ce-format', 'data-ce-block', 'data-ce-bname', 'data-ce-img', 'spellcheck' ].forEach( function ( a ) { clone.removeAttribute( a ); } );
			if ( ! clone.getAttribute( 'class' ) ) { clone.removeAttribute( 'class' ); }
			return clone.outerHTML;
		}
		if ( 'lines' === format ) {
			var lis = el.querySelectorAll( 'li' );
			if ( lis.length ) { return Array.prototype.map.call( lis, function ( li ) { return li.innerText.trim(); } ).filter( Boolean ).join( '\n' ); }
		}
		var text = el.innerText.replace( / /g, ' ' );
		if ( 'pipe' === format ) { return text.split( /\n+/ ).map( function ( l ) { return l.trim(); } ).filter( Boolean ).join( ' | ' ); }
		if ( 'nl' === format || 'lines' === format ) { return text.split( /\n+/ ).map( function ( l ) { return l.trim(); } ).filter( Boolean ).join( '\n' ); }
		if ( 'paras' === format ) { return text.split( /\n\s*\n/ ).map( function ( l ) { return l.replace( /\n/g, ' ' ).trim(); } ).filter( Boolean ).join( '\n\n' ); }
		return text.replace( /\s*\n\s*/g, ' ' ).trim();
	}

	function render( el, format, value ) {
		value = String( value == null ? '' : value );
		if ( 'html' === format ) { return; }
		if ( 'pipe' === format ) {
			var lines = value.split( '|' ).map( function ( l ) { return l.trim(); } ).filter( Boolean );
			el.innerHTML = lines.map( function ( l, i ) { return '<span class="ce-hero__line" style="--d:' + i + '">' + esc( l ) + '</span>'; } ).join( '' );
		} else if ( 'lines' === format && 'UL' === el.tagName ) {
			el.innerHTML = value.split( '\n' ).filter( function ( l ) { return l.trim(); } ).map( function ( l ) { return '<li>' + esc( l.trim() ) + '</li>'; } ).join( '' );
		} else if ( 'nl' === format || 'lines' === format ) {
			el.innerHTML = value.split( '\n' ).map( esc ).join( '<br>' );
		} else if ( 'paras' === format ) {
			el.innerHTML = value.split( /\n\s*\n/ ).filter( function ( p ) { return p.trim(); } ).map( function ( p ) { return '<p>' + esc( p.trim() ).replace( /\n/g, '<br>' ) + '</p>'; } ).join( '' );
		} else {
			el.textContent = value;
		}
	}

	/* Arayüz katmanı ------------------------------------------------------------ */
	function buildUI() {
		ui = document.createElement( 'div' );
		ui.id = 'ceb-ui';
		ui.innerHTML =
			'<div class="ceb-box ceb-box--hover"><span class="ceb-box__label"></span></div>' +
			'<div class="ceb-box ceb-box--select"><span class="ceb-box__label"></span></div>' +
			'<div class="ceb-toolbar" role="toolbar" aria-label="Öğe araçları">' +
				'<button type="button" data-act="parent" title="Üst öğeyi seç">⇡</button>' +
				'<button type="button" data-act="up" title="Bölümü yukarı taşı">▲</button>' +
				'<button type="button" data-act="down" title="Bölümü aşağı taşı">▼</button>' +
				'<button type="button" data-act="duplicate" title="Bölümü çoğalt">⧉</button>' +
				'<button type="button" data-act="remove" class="is-danger" title="Bölümü kaldır">✕</button>' +
			'</div>';
		document.body.appendChild( ui );
		hoverBox = ui.querySelector( '.ceb-box--hover' );
		selectBox = ui.querySelector( '.ceb-box--select' );
		hoverLabel = hoverBox.querySelector( '.ceb-box__label' );
		selectLabel = selectBox.querySelector( '.ceb-box__label' );
		toolbar = ui.querySelector( '.ceb-toolbar' );
		toolbar.addEventListener( 'click', function ( e ) {
			var b = e.target.closest( 'button' );
			if ( ! b ) { return; }
			e.preventDefault();
			e.stopPropagation();
			shell.action( b.dataset.act );
		} );
	}

	function place( box, el ) {
		if ( ! el || ! document.contains( el ) ) { box.style.display = 'none'; return; }
		var r = el.getBoundingClientRect();
		box.style.display = 'block';
		box.style.top = ( r.top + window.scrollY ) + 'px';
		box.style.left = ( r.left + window.scrollX ) + 'px';
		box.style.width = r.width + 'px';
		box.style.height = r.height + 'px';
		box.classList.toggle( 'is-top', r.top < 28 );
	}

	function unitOf( el ) { return el ? el.closest( '[data-ce-block], [data-ce-section]' ) : null; }

	function nameOf( el ) {
		var n = NAMES[ el.tagName ] || el.tagName.toLowerCase();
		if ( el.hasAttribute( 'data-ce-section' ) ) { n = 'Bölüm: ' + el.getAttribute( 'data-ce-section' ); }
		return n;
	}

	function refresh() {
		if ( selected ) {
			place( selectBox, selected );
			var u = unitOf( selected );
			toolbar.style.display = 'flex';
			var r = selected.getBoundingClientRect();
			toolbar.style.top = Math.max( 0, r.top + window.scrollY - 38 ) + 'px';
			toolbar.style.left = ( r.left + window.scrollX ) + 'px';
			toolbar.querySelectorAll( '[data-act="up"],[data-act="down"],[data-act="remove"]' ).forEach( function ( b ) { b.hidden = ! u; } );
			toolbar.querySelector( '[data-act="duplicate"]' ).hidden = ! u || ! u.hasAttribute( 'data-ce-block' );
		} else {
			selectBox.style.display = 'none';
			toolbar.style.display = 'none';
		}
	}

	/* Seçim ----------------------------------------------------------------------- */
	function computed( el ) {
		var cs = window.getComputedStyle( el );
		var keys = [ 'color', 'backgroundColor', 'backgroundImage', 'backgroundSize', 'fontFamily', 'fontSize', 'fontWeight', 'fontStyle', 'lineHeight', 'letterSpacing', 'textAlign', 'textTransform', 'textDecorationLine',
			'paddingTop', 'paddingRight', 'paddingBottom', 'paddingLeft', 'marginTop', 'marginRight', 'marginBottom', 'marginLeft', 'width', 'maxWidth', 'minHeight', 'height',
			'display', 'flexDirection', 'alignItems', 'justifyContent', 'gap', 'borderTopWidth', 'borderTopStyle', 'borderTopColor', 'borderTopLeftRadius', 'boxShadow', 'opacity', 'zIndex' ];
		var out = {};
		keys.forEach( function ( k ) { out[ k ] = cs[ k ]; } );
		return out;
	}

	function describe( el ) {
		var textEl = el.closest( '[data-ce-edit]' );
		var imgEl = el.closest( '[data-ce-img]' );
		var linkEl = el.closest( 'a' );
		var unit = unitOf( el );
		var info = {
			tag: el.tagName,
			name: nameOf( el ),
			selector: uniqueSelector( el ),
			similar: similarSelector( el ),
			computed: computed( el ),
			text: textEl ? { source: textEl.getAttribute( 'data-ce-edit' ), format: textEl.getAttribute( 'data-ce-format' ) || 'text', value: extract( textEl, textEl.getAttribute( 'data-ce-format' ) || 'text' ), self: textEl === el } : null,
			image: imgEl ? { source: imgEl.getAttribute( 'data-ce-img' ), src: ( imgEl.querySelector( 'img' ) || {} ).currentSrc || '' } : null,
			link: linkEl ? { source: linkEl.getAttribute( 'data-ce-link' ) || '', href: linkEl.getAttribute( 'href' ) || '' } : null,
			unit: unit ? { id: unit.hasAttribute( 'data-ce-block' ) ? 'block:' + unit.getAttribute( 'data-ce-block' ) : 'section:' + unit.getAttribute( 'data-ce-section' ), type: unit.hasAttribute( 'data-ce-block' ) ? 'block' : 'section', name: unit.getAttribute( 'data-ce-bname' ) || unit.getAttribute( 'data-ce-section' ) } : null,
			ancestors: []
		};
		var p = el.parentElement;
		while ( p && p !== document.body && info.ancestors.length < 4 ) { info.ancestors.push( nameOf( p ) ); p = p.parentElement; }
		return info;
	}

	function stopEditing() {
		if ( editing ) {
			editing.removeAttribute( 'contenteditable' );
			editing.classList.remove( 'ceb-is-editing' );
			editing = null;
		}
	}

	function startEditing( textEl, x, y ) {
		stopEditing();
		editing = textEl;
		var format = textEl.getAttribute( 'data-ce-format' ) || 'text';
		var mode = 'html' === format ? 'true' : 'plaintext-only';
		textEl.setAttribute( 'contenteditable', mode );
		if ( textEl.contentEditable !== mode ) { textEl.setAttribute( 'contenteditable', 'true' ); }
		textEl.setAttribute( 'spellcheck', 'true' );
		textEl.classList.add( 'ceb-is-editing' );
		textEl.focus( { preventScroll: true } );
		if ( document.caretRangeFromPoint && null != x ) {
			var range = document.caretRangeFromPoint( x, y );
			if ( range ) { var s = window.getSelection(); s.removeAllRanges(); s.addRange( range ); }
		}
	}

	function select( el, x, y ) {
		if ( ! el || isUI( el ) ) { return; }
		if ( selected ) { selected.classList.remove( 'ceb-selected' ); }
		selected = el;
		selected.classList.add( 'ceb-selected' );
		selectLabel.textContent = nameOf( el );
		var textEl = el.closest( '[data-ce-edit]' );
		if ( textEl && ( textEl === el || textEl.contains( el ) ) && ( TEXT_TAGS.test( el.tagName ) || textEl === el ) ) {
			if ( editing !== textEl ) { startEditing( textEl, x, y ); }
		} else {
			stopEditing();
		}
		refresh();
		shell.onSelect( describe( el ) );
	}

	function deselect() {
		stopEditing();
		if ( selected ) { selected.classList.remove( 'ceb-selected' ); }
		selected = null;
		refresh();
		shell.onSelect( null );
	}

	/* Olaylar -------------------------------------------------------------------- */
	document.addEventListener( 'mouseover', function ( e ) {
		if ( isUI( e.target ) ) { return; }
		place( hoverBox, e.target );
		hoverLabel.textContent = nameOf( e.target ) + ( e.target.closest( '[data-ce-edit]' ) ? ' · düzenlenebilir' : '' );
	}, true );
	document.addEventListener( 'mouseleave', function () { hoverBox.style.display = 'none'; } );

	document.addEventListener( 'click', function ( e ) {
		if ( isUI( e.target ) ) { return; }
		if ( editing && editing.contains( e.target ) ) {
			// Düzenlenen metnin içinde imleç konumlandırmaya izin ver, bağlantıyı açma.
			if ( e.target.closest( 'a' ) ) { e.preventDefault(); }
			return;
		}
		e.preventDefault();
		e.stopPropagation();
		select( e.target, e.clientX, e.clientY );
	}, true );

	[ 'submit', 'dblclick' ].forEach( function ( ev ) {
		document.addEventListener( ev, function ( e ) { if ( ! isUI( e.target ) ) { e.preventDefault(); } }, true );
	} );

	document.addEventListener( 'input', function ( e ) {
		if ( ! editing || ! editing.contains( e.target ) ) { return; }
		var format = editing.getAttribute( 'data-ce-format' ) || 'text';
		var source = editing.getAttribute( 'data-ce-edit' );
		var value = extract( editing, format );
		// Aynı kaynağı gösteren diğer öğeleri de güncelle (ör. telefon hem footer'da hem CTA'da).
		document.querySelectorAll( '[data-ce-edit="' + CSS.escape( source ) + '"]' ).forEach( function ( other ) {
			if ( other !== editing ) { render( other, format, value ); }
		} );
		shell.onValue( source, value );
		refresh();
	}, true );

	document.addEventListener( 'keydown', function ( e ) {
		if ( editing && 'Enter' === e.key && 'text' === ( editing.getAttribute( 'data-ce-format' ) || 'text' ) ) {
			e.preventDefault();
			return;
		}
		if ( 'Escape' === e.key ) { deselect(); return; }
		var mod = e.ctrlKey || e.metaKey;
		if ( mod && ! editing && ( 'z' === e.key.toLowerCase() || 'y' === e.key.toLowerCase() ) ) {
			e.preventDefault();
			shell.action( ( 'y' === e.key.toLowerCase() || e.shiftKey ) ? 'redo' : 'undo' );
		}
		if ( mod && 's' === e.key.toLowerCase() ) { e.preventDefault(); shell.action( 'save' ); }
	}, true );

	document.addEventListener( 'focusout', function ( e ) {
		if ( editing && e.target === editing ) { shell.commit(); }
	}, true );

	document.addEventListener( 'paste', function ( e ) {
		if ( ! editing ) { return; }
		e.preventDefault();
		var text = ( e.clipboardData || window.clipboardData ).getData( 'text/plain' );
		document.execCommand( 'insertText', false, text );
	}, true );

	var raf = null;
	var onMove = function () { if ( ! raf ) { raf = requestAnimationFrame( function () { raf = null; refresh(); } ); } };
	window.addEventListener( 'scroll', onMove, true );
	window.addEventListener( 'resize', onMove );

	/* Kabuk API'si ---------------------------------------------------------------------- */
	var REGIONS = [ 'header.ce-header', 'main', '.ce-prefooter', 'footer.ce-footer' ];
	function clean( root ) {
		root.querySelectorAll( '.ceb-selected' ).forEach( function ( el ) { el.classList.remove( 'ceb-selected' ); } );
		root.querySelectorAll( '[contenteditable]' ).forEach( function ( el ) { el.removeAttribute( 'contenteditable' ); el.classList.remove( 'ceb-is-editing' ); } );
	}

	window.CEBFrame = {
		data: D,
		setCSS: function ( css ) {
			var tag = document.getElementById( 'ceb-live-preview' );
			if ( ! tag ) { tag = document.createElement( 'style' ); tag.id = 'ceb-live-preview'; document.head.appendChild( tag ); }
			tag.textContent = css;
			refresh();
		},
		setValue: function ( source, value ) {
			document.querySelectorAll( '[data-ce-edit="' + CSS.escape( source ) + '"]' ).forEach( function ( el ) {
				if ( el !== editing ) { render( el, el.getAttribute( 'data-ce-format' ) || 'text', value ); }
			} );
			refresh();
		},
		setImage: function ( source, url ) {
			document.querySelectorAll( '[data-ce-img="' + CSS.escape( source ) + '"]' ).forEach( function ( box ) {
				var img = box.querySelector( 'img' );
				if ( img ) {
					img.removeAttribute( 'srcset' );
					img.removeAttribute( 'sizes' );
					img.src = url;
				} else {
					var mat = box.querySelector( '.ce-material, .ce-hero__art, .ce-phero__art, .ce-cta__art' );
					var n = document.createElement( 'img' );
					n.src = url;
					n.alt = '';
					n.style.cssText = 'position:absolute;inset:0;width:100%;height:100%;object-fit:cover';
					if ( mat ) { mat.replaceWith( n ); } else { box.appendChild( n ); }
				}
			} );
			refresh();
		},
		setLink: function ( source, url ) {
			document.querySelectorAll( '[data-ce-link="' + CSS.escape( source ) + '"]' ).forEach( function ( a ) { a.setAttribute( 'href', url ); } );
		},
		selectParent: function () {
			if ( selected && selected.parentElement && selected.parentElement !== document.body ) { select( selected.parentElement ); }
		},
		reselect: function () { if ( selected && document.contains( selected ) ) { shell.onSelect( describe( selected ) ); refresh(); } },
		deselect: deselect,
		snapshot: function () {
			return REGIONS.map( function ( sel ) {
				var el = document.querySelector( sel );
				if ( ! el ) { return null; }
				var c = el.cloneNode( true );
				clean( c );
				return c.innerHTML;
			} );
		},
		restore: function ( snap ) {
			deselect();
			REGIONS.forEach( function ( sel, i ) {
				var el = document.querySelector( sel );
				if ( el && null != snap[ i ] ) { el.innerHTML = snap[ i ]; }
			} );
			document.querySelectorAll( '[data-reveal]' ).forEach( function ( el ) { el.classList.add( 'is-visible' ); } );
		},
		/** Yapı işlemi; kabuğa gönderilecek işlem nesnesini döndürür. */
		structure: function ( act ) {
			var unit = unitOf( selected );
			if ( ! unit ) { return null; }
			var id = unit.hasAttribute( 'data-ce-block' ) ? 'block:' + unit.getAttribute( 'data-ce-block' ) : 'section:' + unit.getAttribute( 'data-ce-section' );
			var isBlock = unit.hasAttribute( 'data-ce-block' );
			var peers = function ( el ) { return el && ( isBlock ? el.hasAttribute( 'data-ce-block' ) : el.hasAttribute( 'data-ce-section' ) ); };
			var op = null;
			if ( 'up' === act || 'down' === act ) {
				var sib = 'up' === act ? unit.previousElementSibling : unit.nextElementSibling;
				while ( sib && ! peers( sib ) ) { sib = 'up' === act ? sib.previousElementSibling : sib.nextElementSibling; }
				if ( ! sib ) { return null; }
				if ( 'up' === act ) { unit.parentNode.insertBefore( unit, sib ); } else { unit.parentNode.insertBefore( sib, unit ); }
				op = { type: 'move', unit: id, dir: act };
			} else if ( 'duplicate' === act && isBlock ) {
				var parts = unit.getAttribute( 'data-ce-block' ).split( ':' );
				var newKey = parts[ 1 ] + 'd' + Date.now().toString( 36 );
				var clone = unit.cloneNode( true );
				clean( clone );
				clone.classList.remove( 'ceb-selected' );
				var re = new RegExp( ':' + parts[ 1 ] + '(?=[:]|$)', 'g' );
				[ clone ].concat( Array.prototype.slice.call( clone.querySelectorAll( '[data-ce-edit],[data-ce-img],[data-ce-link],[data-ce-block]' ) ) ).forEach( function ( n ) {
					[ 'data-ce-edit', 'data-ce-img', 'data-ce-link', 'data-ce-block' ].forEach( function ( a ) {
						if ( n.hasAttribute( a ) ) { n.setAttribute( a, n.getAttribute( a ).replace( re, ':' + newKey ) ); }
					} );
				} );
				unit.parentNode.insertBefore( clone, unit.nextSibling );
				op = { type: 'duplicate', unit: id, newKey: newKey };
			} else if ( 'remove' === act ) {
				unit.remove();
				op = { type: isBlock ? 'delete' : 'hide', unit: id };
				deselect();
			}
			refresh();
			return op;
		}
	};

	/* Başlangıç ------------------------------------------------------------------------- */
	buildUI();
	document.querySelectorAll( '[data-reveal]' ).forEach( function ( el ) { el.classList.add( 'is-visible' ); } );
	document.body.classList.remove( 'ce-header-autohide' );
	shell.onReady( window.CEBFrame );
}() );
