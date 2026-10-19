<?php
/**
 * Otomatik çeviri: üst banttaki TR · EN · RU … düğmeleri sayfayı siteden ayrılmadan Google Çeviri
 * ile yerinde çevirir; seçim çerezde tutulur, diğer sayfalarda da çeviri sürer. İsteğe bağlı olarak
 * tarayıcı dili Türkçe olmayan ziyaretçiye ilk girişte kendi dilinde açılır.
 * Polylang / WPML kuruluysa bu modül devre dışıdır (onların gerçek çevirileri kullanılır).
 * Çeviri tarayıcıda yapılır; sayfa önbelleği ve SEO etkilenmez (Google Türkçe sayfayı görür).
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Yerinde çeviri kullanılacak mı?
 *
 * @return bool
 */
function df_gt_on() {
	return df_opt( 'lang_on' ) && 'link' !== df_opt( 'lang_mode', 'inpage' ) && ! function_exists( 'pll_the_languages' ) && ! defined( 'ICL_SITEPRESS_VERSION' );
}

/**
 * Diller (küçük harf), ilki sitenin dili.
 *
 * @return string[]
 */
function df_gt_langs() {
	$out = array();
	foreach ( explode( ',', (string) df_opt( 'lang_codes', 'TR, EN, RU, AR, DE, FR' ) ) as $c ) {
		$c = strtolower( preg_replace( '/[^A-Za-z-]/', '', $c ) );
		if ( '' !== $c ) {
			$out[] = $c;
		}
	}
	return array_values( array_unique( $out ) );
}

/**
 * Çeviri betiği ve gizli Google alanı.
 */
function df_gt_footer() {
	if ( ! df_gt_on() || is_admin() ) {
		return;
	}
	$langs = df_gt_langs();
	$base  = strtolower( (string) df_opt( 'lang_base', $langs ? $langs[0] : 'tr' ) );
	?>
	<div id="df-gt" class="df-gt" aria-hidden="true"></div>
	<script>
	( function () {
		var base = <?php echo wp_json_encode( $base ); ?>, langs = <?php echo wp_json_encode( $langs ); ?>, auto = <?php echo df_opt( 'lang_auto', 1 ) ? 'true' : 'false'; ?>;
		function get() { var m = document.cookie.match( /(?:^|; )googtrans=([^;]*)/ ); return m ? decodeURIComponent( m[1] ) : ''; }
		function set( v ) {
			var host = location.hostname.replace( /^www\./, '' ), tail = v ? '; path=/; max-age=31536000' : '; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT';
			document.cookie = 'googtrans=' + v + tail;
			if ( host.indexOf( '.' ) > 0 ) { document.cookie = 'googtrans=' + v + tail + '; domain=.' + host; }
		}
		function chosen() { try { return localStorage.getItem( 'df_lang' ); } catch ( e ) { return null; } }
		function remember( l ) { try { localStorage.setItem( 'df_lang', l ); } catch ( e ) {} }
		function load() {
			if ( window.dfGtLoaded ) { return; }
			window.dfGtLoaded = true;
			window.dfGtInit = function () {
				new google.translate.TranslateElement( { pageLanguage: base, includedLanguages: langs.join( ',' ), autoDisplay: false }, 'df-gt' );
			};
			var s = document.createElement( 'script' );
			s.src = 'https://translate.google.com/translate_a/element.js?cb=dfGtInit';
			s.async = true;
			document.body.appendChild( s );
		}
		var cur = ( get().split( '/' )[2] || base ).toLowerCase();
		if ( ! get() && auto && ! chosen() ) {
			var nl = ( navigator.language || '' ).slice( 0, 2 ).toLowerCase();
			if ( nl && nl !== base && langs.indexOf( nl ) > -1 ) { set( '/' + base + '/' + nl ); cur = nl; }
		}
		if ( cur !== base ) { load(); }
		document.documentElement.setAttribute( 'data-df-lang-cur', cur );
		document.querySelectorAll( 'a[data-df-lang]' ).forEach( function ( a ) {
			var l = a.getAttribute( 'data-df-lang' );
			a.classList.toggle( 'is-current', l === cur );
			if ( l === cur ) { a.setAttribute( 'aria-current', 'true' ); } else { a.removeAttribute( 'aria-current' ); }
			a.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				remember( l );
				if ( l === base ) { set( '' ); location.reload(); return; }
				set( '/' + base + '/' + l );
				var sel = document.querySelector( '.goog-te-combo' );
				if ( sel ) {
					sel.value = l; sel.dispatchEvent( new Event( 'change' ) );
					document.querySelectorAll( 'a[data-df-lang]' ).forEach( function ( b ) { b.classList.toggle( 'is-current', b === a ); } );
					return;
				}
				location.reload();
			} );
		} );
	}() );
	</script>
	<?php
}
add_action( 'wp_footer', 'df_gt_footer', 50 );

/**
 * Google'ın üst çubuğunu ve vurgularını gizle.
 */
function df_gt_css() {
	if ( ! df_gt_on() ) {
		return;
	}
	echo '<style id="df-gt-css">.df-gt,.skiptranslate>iframe,.goog-te-banner-frame,#goog-gt-tt,.goog-te-balloon-frame,.VIpgJd-ZVi9od-ORHb-OEVmcd,.VIpgJd-ZVi9od-aZ2wEe-wOHMyf{display:none!important}body{top:0!important;position:static!important}.goog-text-highlight,font[style]{background:none!important;box-shadow:none!important}</style>' . "\n";
}
add_action( 'wp_head', 'df_gt_css', 99 );
