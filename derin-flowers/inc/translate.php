<?php
/**
 * Otomatik çeviri ve dil menüsü.
 *
 * - Üst bantta tek bir dil düğmesi (bayrak + kod); dokununca bayraklı dil listesi açılır (mobilde rahat).
 * - Sayfa siteden ayrılmadan Google Çeviri ile yerinde çevrilir; seçim çerezde tutulur.
 * - İlk girişte ziyaretçinin diline / ülkesine göre otomatik çeviri: telefon dili Türkçe olan
 *   (yurt dışındaki Türkler dahil) her zaman Türkçe görür; diğerleri için telefon dili, o da
 *   desteklenmiyorsa saat diliminden bulunan ülke kullanılır. Dışarıya istek atılmaz.
 * - Çeviri sonrası Google'ın üst çubuğu, sayfanın kayması ve yatay taşmalar engellenir.
 * Polylang / WPML kuruluysa yerinde çeviri devre dışıdır, menü onların dil bağlantılarını gösterir.
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
 * Dil adı ve bayrak (ülke) kodu.
 *
 * @param string $code Dil kodu.
 * @return array{name:string,flag:string}
 */
function df_lang_meta( $code ) {
	$code = strtolower( $code );
	$map  = array(
		'tr' => array( 'Türkçe', 'tr' ),
		'en' => array( 'English', 'gb' ),
		'ru' => array( 'Русский', 'ru' ),
		'ar' => array( 'العربية', 'sa' ),
		'de' => array( 'Deutsch', 'de' ),
		'fr' => array( 'Français', 'fr' ),
		'es' => array( 'Español', 'es' ),
		'it' => array( 'Italiano', 'it' ),
		'nl' => array( 'Nederlands', 'nl' ),
		'fa' => array( 'فارسی', 'ir' ),
		'uk' => array( 'Українська', 'ua' ),
		'zh' => array( '中文', 'cn' ),
		'zh-cn' => array( '中文', 'cn' ),
		'el' => array( 'Ελληνικά', 'gr' ),
		'az' => array( 'Azərbaycanca', 'az' ),
		'ja' => array( '日本語', 'jp' ),
		'ko' => array( '한국어', 'kr' ),
		'pt' => array( 'Português', 'pt' ),
		'pl' => array( 'Polski', 'pl' ),
		'bg' => array( 'Български', 'bg' ),
		'ro' => array( 'Română', 'ro' ),
		'sv' => array( 'Svenska', 'se' ),
		'he' => array( 'עברית', 'il' ),
		'iw' => array( 'עברית', 'il' ),
	);
	$m = isset( $map[ $code ] ) ? $map[ $code ] : array( strtoupper( $code ), '' );
	return array( 'name' => $m[0], 'flag' => $m[1] );
}

/**
 * Bayrak görseli (gerçek SVG bayrak; flag-icons, MIT lisansı).
 *
 * @param string $code Dil kodu.
 * @return string
 */
function df_lang_flag( $code ) {
	$m = df_lang_meta( $code );
	if ( ! $m['flag'] || ! file_exists( DF_DIR . '/assets/img/flags/' . $m['flag'] . '.svg' ) ) {
		return '<span class="df-lang__flag df-lang__flag--txt">' . esc_html( strtoupper( substr( $code, 0, 2 ) ) ) . '</span>';
	}
	return '<img class="df-lang__flag" src="' . esc_url( DF_URI . '/assets/img/flags/' . $m['flag'] . '.svg' ) . '" width="20" height="15" alt="" loading="lazy" decoding="async">';
}

/**
 * Üst bantta dil menüsü.
 *
 * @param array $langs df_lang_links() çıktısı.
 */
function df_lang_menu( $langs, $float = false ) {
	if ( ! $langs ) {
		return;
	}
	$gt  = df_gt_on();
	$cur = $langs[0];
	foreach ( $langs as $l ) {
		if ( ! empty( $l['current'] ) ) {
			$cur = $l;
		}
	}
	$code = strtolower( $cur['code'] );
	?>
	<div class="df-lang notranslate<?php echo $float ? ' df-lang--float' . ( df_lang_wa_shown() ? '' : ' df-lang--solo' ) : ''; ?>" translate="no" data-df-langmenu>
		<button type="button" class="df-lang__btn" aria-haspopup="true" aria-expanded="false" aria-label="Dil seçimi / Language">
			<span class="df-lang__cur" data-df-lang-cur><?php echo df_lang_flag( $code ); // phpcs:ignore ?><span class="df-lang__code"><?php echo esc_html( strtoupper( $code ) ); ?></span></span>
			<?php df_the_icon( 'chevron-down', array( 'size' => 14 ) ); ?>
		</button>
		<ul class="df-lang__list" hidden>
			<?php foreach ( $langs as $l ) : ?>
				<?php $c = strtolower( $l['code'] ); ?>
				<li><a href="<?php echo esc_url( $l['url'] ); ?>" lang="<?php echo esc_attr( $c ); ?>" hreflang="<?php echo esc_attr( $c ); ?>"<?php echo $gt ? ' data-df-lang="' . esc_attr( $c ) . '" rel="nofollow"' : ''; ?><?php echo ! empty( $l['current'] ) ? ' class="is-current" aria-current="true"' : ''; ?>>
					<?php echo df_lang_flag( $c ); // phpcs:ignore ?><span class="df-lang__name"><?php echo esc_html( df_lang_meta( $c )['name'] ); ?></span><span class="df-lang__ck"><?php df_the_icon( 'check', array( 'size' => 14 ) ); ?></span>
				</a></li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php
}

/**
 * Sağ altta WhatsApp düğmesinin üstünde sabit dil düğmesi (mobil ve masaüstü).
 */
function df_lang_float() {
	if ( is_admin() || ! function_exists( 'df_lang_links' ) ) {
		return;
	}
	df_lang_menu( df_lang_links(), true );
}
add_action( 'wp_footer', 'df_lang_float', 6 );

/**
 * WhatsApp düğmesi bu sayfada görünüyor mu? (Dil düğmesi onun üstüne yerleşir.)
 *
 * @return bool
 */
function df_lang_wa_shown() {
	return df_opt( 'whatsapp_float' ) && function_exists( 'df_whatsapp_url' ) && df_whatsapp_url() && ! ( df_wc() && ( is_checkout() || is_cart() ) );
}

/**
 * Menü aç / kapa (çeviri kapalıyken de çalışır).
 */
function df_lang_menu_js() {
	if ( ! df_opt( 'lang_on' ) ) {
		return;
	}
	?>
	<script>
	( function () {
		document.querySelectorAll( '[data-df-langmenu]' ).forEach( function ( box ) {
			var btn = box.querySelector( '.df-lang__btn' ), list = box.querySelector( '.df-lang__list' );
			function set( open ) { list.hidden = ! open; btn.setAttribute( 'aria-expanded', open ? 'true' : 'false' ); box.classList.toggle( 'is-open', open ); }
			btn.addEventListener( 'click', function ( e ) { e.stopPropagation(); set( list.hidden ); } );
			document.addEventListener( 'click', function ( e ) { if ( ! box.contains( e.target ) ) { set( false ); } } );
			document.addEventListener( 'keydown', function ( e ) { if ( 'Escape' === e.key ) { set( false ); } } );
		} );
	}() );
	</script>
	<?php
}
add_action( 'wp_footer', 'df_lang_menu_js', 40 );

/**
 * Çeviri betiği, gizli Google alanı ve otomatik dil.
 */
function df_gt_footer() {
	if ( ! df_gt_on() || is_admin() ) {
		return;
	}
	$langs = df_gt_langs();
	$base  = strtolower( (string) df_opt( 'lang_base', $langs ? $langs[0] : 'tr' ) );
	$names = array();
	foreach ( $langs as $l ) {
		$names[ $l ] = df_lang_meta( $l )['name'];
	}
	?>
	<div id="df-gt" class="df-gt" aria-hidden="true"></div>
	<script>
	( function () {
		var base = <?php echo wp_json_encode( $base ); ?>, langs = <?php echo wp_json_encode( $langs ); ?>, names = <?php echo wp_json_encode( $names ); ?>, auto = <?php echo df_opt( 'lang_auto', 1 ) ? 'true' : 'false'; ?>;
		// Saat dilimi → ülkenin dili (yaklaşık ülke tespiti, dışarıya istek atmadan).
		var tz = { 'Europe/Moscow': 'ru', 'Europe/Kaliningrad': 'ru', 'Europe/Samara': 'ru', 'Europe/Volgograd': 'ru', 'Asia/Yekaterinburg': 'ru', 'Asia/Novosibirsk': 'ru', 'Europe/Minsk': 'ru', 'Asia/Almaty': 'ru', 'Asia/Tashkent': 'ru', 'Europe/Kiev': 'uk', 'Europe/Kyiv': 'uk',
			'Europe/Berlin': 'de', 'Europe/Vienna': 'de', 'Europe/Zurich': 'de', 'Europe/Paris': 'fr', 'Europe/Brussels': 'fr', 'Europe/Luxembourg': 'fr', 'Europe/Monaco': 'fr',
			'Europe/London': 'en', 'Europe/Dublin': 'en', 'Europe/Amsterdam': 'nl', 'Europe/Madrid': 'es', 'Europe/Rome': 'it', 'Europe/Athens': 'el', 'Europe/Warsaw': 'pl', 'Europe/Sofia': 'bg', 'Europe/Bucharest': 'ro', 'Europe/Stockholm': 'sv', 'Europe/Lisbon': 'pt',
			'Asia/Riyadh': 'ar', 'Asia/Dubai': 'ar', 'Asia/Qatar': 'ar', 'Asia/Kuwait': 'ar', 'Asia/Bahrain': 'ar', 'Asia/Muscat': 'ar', 'Asia/Baghdad': 'ar', 'Asia/Amman': 'ar', 'Asia/Beirut': 'ar', 'Asia/Damascus': 'ar', 'Africa/Cairo': 'ar', 'Africa/Tripoli': 'ar', 'Africa/Tunis': 'ar', 'Africa/Algiers': 'ar', 'Africa/Casablanca': 'ar',
			'Asia/Tehran': 'fa', 'Asia/Baku': 'az', 'Asia/Jerusalem': 'he', 'Asia/Tokyo': 'ja', 'Asia/Seoul': 'ko', 'Asia/Shanghai': 'zh', 'Asia/Hong_Kong': 'zh' };
		function get() { var m = document.cookie.match( /(?:^|; )googtrans=([^;]*)/ ); return m ? decodeURIComponent( m[1] ) : ''; }
		function set( v ) {
			var host = location.hostname.replace( /^www\./, '' ), tail = v ? '; path=/; max-age=31536000; SameSite=Lax' : '; path=/; expires=Thu, 01 Jan 1970 00:00:00 GMT';
			document.cookie = 'googtrans=' + v + tail;
			if ( host.indexOf( '.' ) > 0 ) { document.cookie = 'googtrans=' + v + tail + '; domain=.' + host; }
		}
		function chosen() { try { return localStorage.getItem( 'df_lang' ); } catch ( e ) { return null; } }
		function remember( l ) { try { localStorage.setItem( 'df_lang', l ); } catch ( e ) {} }
		function supported( l ) { l = ( l || '' ).toLowerCase(); if ( langs.indexOf( l ) > -1 ) { return l; } l = l.split( '-' )[0]; return langs.indexOf( l ) > -1 ? l : ( 'zh' === l && langs.indexOf( 'zh-cn' ) > -1 ? 'zh-cn' : '' ); }
		function detect() {
			var nav = ( navigator.languages && navigator.languages.length ? navigator.languages : [ navigator.language || '' ] );
			var first = ( nav[0] || '' ).toLowerCase().split( '-' )[0];
			if ( first === base ) { return base; } // Telefonu Türkçe olan her zaman Türkçe.
			for ( var i = 0; i < nav.length; i++ ) { var s = supported( nav[i] ); if ( s ) { return s; } }
			var zone = ''; try { zone = Intl.DateTimeFormat().resolvedOptions().timeZone || ''; } catch ( e ) {}
			if ( 'Europe/Istanbul' === zone ) { return base; }
			var byTz = supported( tz[ zone ] || ( /^(America|Australia|Pacific\/Auckland)/.test( zone ) ? 'en' : '' ) );
			if ( byTz ) { return byTz; }
			return first && first !== base ? supported( 'en' ) || base : base;
		}
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
		function mark( l ) {
			document.documentElement.setAttribute( 'data-df-lang-cur', l );
			document.querySelectorAll( 'a[data-df-lang]' ).forEach( function ( a ) {
				var on = a.getAttribute( 'data-df-lang' ) === l;
				a.classList.toggle( 'is-current', on );
				if ( on ) { a.setAttribute( 'aria-current', 'true' ); } else { a.removeAttribute( 'aria-current' ); }
			} );
			document.querySelectorAll( '[data-df-lang-cur]' ).forEach( function ( c ) {
				var src = document.querySelector( 'a[data-df-lang="' + l + '"]' );
				if ( c !== document.documentElement && src ) {
					var flag = src.querySelector( '.df-lang__flag' );
					c.innerHTML = ( flag ? flag.outerHTML : '' ) + '<span class="df-lang__code">' + l.toUpperCase() + '</span>';
				}
			} );
		}
		function toast( l ) {
			var t = document.createElement( 'div' );
			t.className = 'df-langtoast notranslate'; t.setAttribute( 'translate', 'no' );
			t.innerHTML = '<span>🌐 ' + ( names[ l ] || l.toUpperCase() ) + '</span><button type="button" data-back>' + ( names[ base ] || base.toUpperCase() ) + '</button><button type="button" data-x aria-label="Kapat">×</button>';
			document.body.appendChild( t );
			t.querySelector( '[data-back]' ).addEventListener( 'click', function () { remember( base ); set( '' ); location.reload(); } );
			t.querySelector( '[data-x]' ).addEventListener( 'click', function () { t.remove(); } );
			setTimeout( function () { t.classList.add( 'is-out' ); setTimeout( function () { t.remove(); }, 400 ); }, 9000 );
		}
		var cur = ( get().split( '/' )[2] || base ).toLowerCase(), autoNow = false;
		if ( ! get() && auto && ! chosen() ) {
			var d = detect();
			if ( d && d !== base ) { set( '/' + base + '/' + d ); cur = d; autoNow = true; }
			remember( d || base );
		}
		if ( cur !== base ) { load(); }
		mark( cur );
		if ( autoNow ) { toast( cur ); }
		document.querySelectorAll( 'a[data-df-lang]' ).forEach( function ( a ) {
			a.addEventListener( 'click', function ( e ) {
				var l = a.getAttribute( 'data-df-lang' );
				e.preventDefault();
				remember( l );
				if ( l === base ) { set( '' ); location.reload(); return; }
				set( '/' + base + '/' + l );
				var sel = document.querySelector( '.goog-te-combo' );
				if ( sel ) { sel.value = l; sel.dispatchEvent( new Event( 'change' ) ); mark( l ); var box = a.closest( '[data-df-langmenu]' ); if ( box ) { box.querySelector( '.df-lang__list' ).hidden = true; box.classList.remove( 'is-open' ); } return; }
				location.reload();
			} );
		} );
	}() );
	</script>
	<?php
}
add_action( 'wp_footer', 'df_gt_footer', 50 );

/**
 * Google'ın üst çubuğunu gizle; çeviri sonrası sayfanın aşağı / sağa kaymasını ve taşmaları engelle.
 */
function df_gt_css() {
	if ( ! df_gt_on() ) {
		return;
	}
	echo '<style id="df-gt-css">'
		. '.df-gt,body>.skiptranslate,.skiptranslate>iframe,iframe.goog-te-banner-frame,.goog-te-banner-frame,#goog-gt-tt,.goog-te-balloon-frame,.goog-tooltip,.VIpgJd-ZVi9od-ORHb-OEVmcd,.VIpgJd-ZVi9od-aZ2wEe-wOHMyf,.VIpgJd-ZVi9od-l4eHX-hSRGPd,.VIpgJd-yAWNEb-L7lbkb{display:none!important;visibility:hidden!important}'
		. 'html,body{top:0!important;margin-top:0!important}body{position:static!important;min-height:0!important}'
		. 'html.translated-ltr,html.translated-rtl,html.translated-ltr body,html.translated-rtl body{overflow-x:hidden;overflow-x:clip;max-width:100%}'
		. 'html.translated-ltr :is(h1,h2,h3,.df-btn,.df-chip,.df-fchip,.df-card__title,.df-nav a,.df-cathero__title),html.translated-rtl :is(h1,h2,h3,.df-btn,.df-chip,.df-fchip,.df-card__title,.df-nav a,.df-cathero__title){overflow-wrap:anywhere;hyphens:auto}'
		. '.goog-text-highlight,font[style]{background:none!important;box-shadow:none!important}'
		. '</style>' . "\n";
}
add_action( 'wp_head', 'df_gt_css', 99 );
