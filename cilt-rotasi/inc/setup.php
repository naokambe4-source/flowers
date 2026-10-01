<?php
/**
 * Tema kurulumu, varlıklar, performans ve içerik filtreleri.
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Tema destekleri, menüler, görsel boyutları.
 */
function cr_setup() {
	load_theme_textdomain( 'cilt-rotasi', CR_DIR . '/languages' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'custom-logo' );
	add_editor_style( 'assets/css/editor.css' );

	add_theme_support(
		'editor-color-palette',
		array(
			array( 'name' => 'Koyu yeşil', 'slug' => 'green', 'color' => '#24483F' ),
			array( 'name' => 'İkinci yeşil', 'slug' => 'green-2', 'color' => '#3B5E53' ),
			array( 'name' => 'Sage', 'slug' => 'sage', 'color' => '#AAB7A2' ),
			array( 'name' => 'Açık sage', 'slug' => 'sage-light', 'color' => '#E6ECE1' ),
			array( 'name' => 'Krem', 'slug' => 'cream', 'color' => '#FBF8F2' ),
			array( 'name' => 'Bej', 'slug' => 'beige', 'color' => '#E9DDCE' ),
			array( 'name' => 'Şampanya', 'slug' => 'gold', 'color' => '#C9A06B' ),
			array( 'name' => 'Metin', 'slug' => 'text', 'color' => '#28322E' ),
		)
	);

	register_nav_menus(
		array(
			'primary'  => 'Ana menü (header)',
			'footer_1' => 'Footer — 1. kolon (Keşfet)',
			'footer_2' => 'Footer — 2. kolon (Kurumsal)',
			'footer_3' => 'Footer — 3. kolon (Yasal)',
		)
	);

	add_image_size( 'cr-card', 720, 900, true );
	add_image_size( 'cr-wide', 1200, 750, true );
	add_image_size( 'cr-hero', 1600, 1200, false );
	add_image_size( 'cr-thumb', 360, 360, true );
}
add_action( 'after_setup_theme', 'cr_setup' );

/**
 * İçerik genişliği.
 */
function cr_content_width() {
	$GLOBALS['content_width'] = 800;
}
add_action( 'after_setup_theme', 'cr_content_width', 0 );

/**
 * Google Fonts adresi.
 *
 * @return string
 */
function cr_fonts_url() {
	if ( ! cr_opt( 'google_fonts' ) ) {
		return '';
	}
	$map = array(
		'DM Serif Display'     => 'DM+Serif+Display:ital@0;1',
		'Instrument Serif'     => 'Instrument+Serif:ital@0;1',
		'Cormorant Garamond'   => 'Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,500',
		'Playfair Display'     => 'Playfair+Display:ital,wght@0,500;0,600;1,500',
		'Fraunces'             => 'Fraunces:ital,opsz,wght@0,9..144,500;0,9..144,600;1,9..144,500',
		'Libre Caslon Display' => 'Libre+Caslon+Display',
		'Manrope'              => 'Manrope:wght@400;500;600;700',
		'Inter'                => 'Inter:wght@400;500;600;700',
		'Plus Jakarta Sans'    => 'Plus+Jakarta+Sans:wght@400;500;600;700',
		'DM Sans'              => 'DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700',
		'Outfit'               => 'Outfit:wght@400;500;600;700',
	);
	$fam = array();
	foreach ( array( cr_opt( 'font_heading' ), cr_opt( 'font_body' ) ) as $f ) {
		if ( isset( $map[ $f ] ) ) {
			$fam[] = 'family=' . $map[ $f ];
		}
	}
	return $fam ? 'https://fonts.googleapis.com/css2?' . implode( '&', array_unique( $fam ) ) . '&display=swap&subset=latin-ext' : '';
}

/**
 * Ön yüz varlıkları.
 */
function cr_enqueue() {
	$fonts = cr_fonts_url();
	if ( $fonts ) {
		wp_enqueue_style( 'cr-fonts', $fonts, array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
	}
	wp_enqueue_style( 'cr-main', CR_URI . '/assets/css/main.css', array(), CR_VERSION );
	wp_add_inline_style( 'cr-main', cr_inline_css() );

	wp_enqueue_script(
		'cr-main',
		CR_URI . '/assets/js/main.js',
		array(),
		CR_VERSION,
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
	wp_localize_script(
		'cr-main',
		'CR',
		array(
			'rest'      => esc_url_raw( rest_url( 'cilt-rotasi/v1/' ) ),
			'home'      => esc_url_raw( home_url( '/' ) ),
			'savedUrl'  => esc_url_raw( cr_saved_url() ),
			'postId'    => is_singular() ? get_the_ID() : 0,
			'track'     => ( is_singular( array( 'post', 'icerik', 'urun_rehberi' ) ) && cr_opt( 'track_views' ) && ! cr_can_edit() ) ? 1 : 0,
			'anim'      => cr_opt( 'animations' ) ? 1 : 0,
			'ga'        => cr_opt( 'ga4_id' ),
			'quiz'      => cr_quiz_data(),
			'i18n'      => array(
				'saved'     => 'Kaydedildi',
				'removed'   => 'Kaydedilenlerden çıkarıldı',
				'copied'    => 'Bağlantı kopyalandı',
				'noResults' => 'Sonuç bulunamadı. Başka bir kelime dene.',
				'searching' => 'Aranıyor…',
				'error'     => 'Bir sorun oluştu, tekrar dene.',
			),
			'newsNonce' => wp_create_nonce( 'cr_news' ),
			'ajax'      => admin_url( 'admin-ajax.php' ),
		)
	);

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) && cr_opt( 'art_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'cr_enqueue' );

/**
 * Renk ve ölçü değişkenleri.
 *
 * @return string
 */
function cr_inline_css() {
	$vars = array();
	foreach ( cr_schema_fields() as $id => $f ) {
		if ( 'color' === $f['type'] && ! empty( $f['var'] ) ) {
			$c = sanitize_hex_color( cr_opt( $id ) );
			if ( $c ) {
				$vars[] = $f['var'] . ':' . $c;
			}
		}
	}
	$head   = cr_opt( 'font_heading' );
	$body   = cr_opt( 'font_body' );
	$vars[] = "--cr-font-head:'" . esc_attr( $head ) . "',Georgia,'Times New Roman',serif";
	$vars[] = "--cr-font-body:'" . esc_attr( $body ) . "',system-ui,-apple-system,'Segoe UI',Roboto,sans-serif";
	$vars[] = '--cr-scale:' . ( max( 85, min( 120, (int) cr_opt( 'font_scale', 100 ) ) ) / 100 );
	$vars[] = '--cr-container:' . max( 1100, min( 1440, (int) cr_opt( 'container', 1360 ) ) ) . 'px';
	$vars[] = '--cr-radius:' . max( 0, min( 40, (int) cr_opt( 'radius', 22 ) ) ) . 'px';
	$vars[] = '--cr-section:' . max( 48, min( 180, (int) cr_opt( 'section_space', 112 ) ) ) . 'px';
	$css    = ':root{' . implode( ';', $vars ) . '}';
	$custom = cr_opt( 'custom_css' );
	if ( $custom ) {
		$css .= "\n" . wp_strip_all_tags( $custom );
	}
	return $css;
}

/**
 * Head: kaynak ön bağlantıları ve hero görseli ön yüklemesi (LCP).
 */
function cr_head_perf() {
	if ( cr_opt( 'google_fonts' ) ) {
		echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
		echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
	}
	$lcp = '';
	if ( is_front_page() ) {
		$lcp = cr_img_url( cr_opt( 'hero_image' ), 'full' );
	} elseif ( is_singular( array( 'post', 'urun_rehberi' ) ) ) {
		$lcp = cr_post_image_url( get_the_ID(), 'cr-hero' );
	}
	if ( $lcp ) {
		echo '<link rel="preload" as="image" href="' . esc_url( $lcp ) . '" fetchpriority="high">' . "\n";
	}
	echo '<meta name="theme-color" content="' . esc_attr( cr_opt( 'c_cream', '#FBF8F2' ) ) . '">' . "\n";
}
add_action( 'wp_head', 'cr_head_perf', 1 );

/**
 * Özel head / footer kodu ve GA4.
 */
function cr_head_code() {
	$code = cr_opt( 'code_head' );
	if ( $code ) {
		echo "\n" . $code . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- Yönetici tarafından eklenen kod.
	}
}
add_action( 'wp_head', 'cr_head_code', 99 );

/**
 * Footer kodu.
 */
function cr_footer_code() {
	$code = cr_opt( 'code_footer' );
	if ( $code ) {
		echo "\n" . $code . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput
	}
}
add_action( 'wp_footer', 'cr_footer_code', 99 );

/**
 * Gereksiz yükleri kaldır (emoji, oEmbed keşif, RSD, wlw, jenerator).
 */
function cr_cleanup() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );
	add_filter( 'emoji_svg_url', '__return_false' );
}
add_action( 'init', 'cr_cleanup' );

/**
 * Gövde sınıfları.
 *
 * @param array $classes Sınıflar.
 * @return array
 */
function cr_body_class( $classes ) {
	if ( cr_opt( 'bottom_nav' ) ) {
		$classes[] = 'has-bottom-nav';
	}
	if ( cr_opt( 'animations' ) ) {
		$classes[] = 'cr-anim';
	}
	if ( cr_opt( 'announce_on' ) ) {
		$classes[] = 'has-announce';
	}
	if ( is_front_page() && ! cr_opt( 'announce_on' ) ) {
		$first = null;
		foreach ( cr_sections() as $s ) {
			if ( $s['on'] ) {
				$first = $s['id'];
				break;
			}
		}
		if ( 'hero' === $first ) {
			$classes[] = 'cr-over-hero';
		}
	}
	return $classes;
}
add_filter( 'body_class', 'cr_body_class' );

/**
 * Özet uzunluğu.
 *
 * @return int
 */
function cr_excerpt_length() {
	return 26;
}
add_filter( 'excerpt_length', 'cr_excerpt_length' );
add_filter(
	'excerpt_more',
	function () {
		return '…';
	}
);

/**
 * Makale başlıklarına kimlik ekler (içindekiler ve derin bağlantı için).
 *
 * @param string $content İçerik.
 * @return string
 */
function cr_heading_ids( $content ) {
	if ( ! is_singular() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	$used = array();
	return preg_replace_callback(
		'/<h([23])([^>]*)>(.*?)<\/h\1>/is',
		function ( $m ) use ( &$used ) {
			if ( false !== stripos( $m[2], 'id=' ) ) {
				return $m[0];
			}
			$base = sanitize_title( remove_accents( wp_strip_all_tags( $m[3] ) ) );
			$base = $base ? $base : 'bolum';
			$id   = $base;
			$i    = 2;
			while ( isset( $used[ $id ] ) ) {
				$id = $base . '-' . $i++;
			}
			$used[ $id ] = true;
			return '<h' . $m[1] . $m[2] . ' id="' . esc_attr( $id ) . '">' . $m[3] . '</h' . $m[1] . '>';
		},
		$content
	);
}
add_filter( 'the_content', 'cr_heading_ids', 12 );

/**
 * İçerikten içindekiler listesi çıkarır.
 *
 * @param string $html İşlenmiş içerik.
 * @return array
 */
function cr_toc_from_html( $html ) {
	$out = array();
	if ( preg_match_all( '/<h([23])[^>]*id="([^"]+)"[^>]*>(.*?)<\/h\1>/is', $html, $m, PREG_SET_ORDER ) ) {
		foreach ( $m as $h ) {
			$out[] = array(
				'level' => (int) $h[1],
				'id'    => $h[2],
				'text'  => wp_strip_all_tags( $h[3] ),
			);
		}
	}
	return $out;
}

/**
 * İçerik görsellerine tembel yükleme + alt metin yedeği.
 *
 * @param array   $attr       Nitelikler.
 * @param WP_Post $attachment Ek.
 * @return array
 */
function cr_image_attr( $attr, $attachment ) {
	if ( empty( $attr['alt'] ) && cr_opt( 'seo_auto_alt' ) ) {
		$parent      = $attachment->post_parent ? get_the_title( $attachment->post_parent ) : '';
		$attr['alt'] = $parent ? $parent : get_the_title( $attachment );
	}
	if ( empty( $attr['decoding'] ) ) {
		$attr['decoding'] = 'async';
	}
	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'cr_image_attr', 10, 2 );

/**
 * Okunma sayacı (önbellek dostu: REST ile, JS tarafından).
 */
function cr_register_view_route() {
	register_rest_route(
		'cilt-rotasi/v1',
		'/view/(?P<id>\d+)',
		array(
			'methods'             => 'POST',
			'permission_callback' => '__return_true',
			'callback'            => function ( $req ) {
				$id = (int) $req['id'];
				if ( ! cr_opt( 'track_views' ) || 'publish' !== get_post_status( $id ) ) {
					return new WP_REST_Response( array( 'ok' => false ), 200 );
				}
				update_post_meta( $id, '_cr_views', cr_views( $id ) + 1 );
				return new WP_REST_Response( array( 'ok' => true ), 200 );
			},
		)
	);
}
add_action( 'rest_api_init', 'cr_register_view_route' );

/**
 * Ek (attachment) sayfalarını ana içeriğe yönlendirir (ince içerik önleme).
 */
function cr_attachment_redirect() {
	if ( is_attachment() && cr_opt( 'seo_attachment_redirect' ) ) {
		$parent = wp_get_post_parent_id( get_the_ID() );
		wp_safe_redirect( $parent ? get_permalink( $parent ) : home_url( '/' ), 301 );
		exit;
	}
}
add_action( 'template_redirect', 'cr_attachment_redirect' );

/**
 * RSS akışına görsel ekler.
 *
 * @param string $content İçerik.
 * @return string
 */
function cr_feed_image( $content ) {
	$img = cr_post_image_url( get_the_ID(), 'cr-wide' );
	if ( $img ) {
		$content = '<p><img src="' . esc_url( $img ) . '" alt="' . esc_attr( get_the_title() ) . '"></p>' . $content;
	}
	return $content;
}
add_filter( 'the_excerpt_rss', 'cr_feed_image' );
add_filter( 'the_content_feed', 'cr_feed_image' );

/**
 * Arama sonuçlarında yalnızca içerik türleri (sayfalar hariç değil ama ek dosyalar hariç).
 *
 * @param WP_Query $q Sorgu.
 */
function cr_search_query( $q ) {
	if ( ! is_admin() && $q->is_main_query() && $q->is_search() ) {
		$type = isset( $_GET['tur'] ) ? sanitize_key( wp_unslash( $_GET['tur'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$q->set( 'post_type', in_array( $type, array( 'post', 'icerik', 'urun_rehberi' ), true ) ? $type : array( 'post', 'icerik', 'urun_rehberi', 'page' ) );
	}
	if ( ! is_admin() && $q->is_main_query() && ( $q->is_archive() || $q->is_home() ) && isset( $_GET['siralama'] ) && 'populer' === $_GET['siralama'] ) { // phpcs:ignore WordPress.Security.NonceVerification
		$q->set(
			'meta_query',
			array(
				'relation' => 'OR',
				'views'    => array( 'key' => '_cr_views', 'type' => 'NUMERIC' ),
				array( 'key' => '_cr_views', 'compare' => 'NOT EXISTS' ),
			)
		);
		$q->set( 'orderby', array( 'views' => 'DESC', 'date' => 'DESC' ) );
	}
	if ( ! is_admin() && $q->is_main_query() && ( $q->is_post_type_archive( 'urun_rehberi' ) || $q->is_tax( 'urun_turu' ) ) ) {
		$q->set( 'posts_per_page', 16 );
		$sort = isset( $_GET['siralama'] ) ? sanitize_key( wp_unslash( $_GET['siralama'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		if ( 'puan' === $sort ) {
			$q->set(
				'meta_query',
				array(
					'relation' => 'OR',
					'rating'   => array( 'key' => '_cr_rating', 'type' => 'DECIMAL(3,1)' ),
					array( 'key' => '_cr_rating', 'compare' => 'NOT EXISTS' ),
				)
			);
			$q->set( 'orderby', array( 'rating' => 'DESC', 'date' => 'DESC' ) );
		} elseif ( 'ad' === $sort ) {
			$q->set( 'orderby', 'title' );
			$q->set( 'order', 'ASC' );
		}
	}
	if ( ! is_admin() && $q->is_main_query() && $q->is_tax( 'icerik_grubu' ) ) {
		$q->set( 'posts_per_page', 300 );
		$q->set( 'orderby', 'title' );
		$q->set( 'order', 'ASC' );
	}
	if ( ! is_admin() && $q->is_main_query() && $q->is_post_type_archive( 'icerik' ) ) {
		$q->set( 'posts_per_page', 300 );
		$q->set( 'orderby', 'title' );
		$q->set( 'order', 'ASC' );
	}
}
add_action( 'pre_get_posts', 'cr_search_query' );

/**
 * İçerik Türkçe olduğundan html lang her zaman tr (Türkçe büyük harf İ/ı dönüşümleri için).
 *
 * @param string $out Nitelikler.
 * @return string
 */
function cr_lang_attr( $out ) {
	if ( 0 !== strpos( get_locale(), 'tr' ) ) {
		$out = preg_replace( '/lang="[^"]*"/', 'lang="tr-TR"', $out );
	}
	return $out;
}
add_filter( 'language_attributes', 'cr_lang_attr' );
