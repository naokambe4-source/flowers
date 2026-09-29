<?php
/**
 * Tema kurulumu: destekler, menüler, görsel boyutları, stil/script yükleme, performans.
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', 'ce_theme_setup' );
/**
 * Tema destekleri.
 */
function ce_theme_setup() {
	load_theme_textdomain( 'can-eloksal', CE_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
	add_theme_support( 'custom-logo', array( 'height' => 80, 'width' => 260, 'flex-width' => true, 'flex-height' => true ) );
	add_editor_style( array( 'assets/css/editor.css' ) );

	register_nav_menus(
		array(
			'primary'          => 'Ana menü (header)',
			'footer_corporate' => 'Footer — Kurumsal',
			'footer_services'  => 'Footer — Hizmetler',
			'footer_quick'     => 'Footer — Hızlı Linkler',
			'footer_legal'     => 'Footer — Yasal',
		)
	);

	// Görsel boyutları: kapak, kart, dikey kart, küçük, logo.
	add_image_size( 'ce-hero', 2400, 1400, false );
	add_image_size( 'ce-wide', 1600, 1000, false );
	add_image_size( 'ce-card', 960, 720, true );
	add_image_size( 'ce-tall', 800, 1040, true );
	add_image_size( 'ce-thumb', 520, 390, true );
	add_image_size( 'ce-logo', 360, 180, false );
}

add_filter( 'image_size_names_choose', 'ce_image_size_names' );
/**
 * Editörde seçilebilir boyutlar.
 *
 * @param array $sizes Boyutlar.
 * @return array
 */
function ce_image_size_names( $sizes ) {
	return array_merge( $sizes, array( 'ce-wide' => 'Geniş (1600)', 'ce-card' => 'Kart (960×720)' ) );
}

add_filter( 'image_editor_output_format', 'ce_webp_output_format' );
/**
 * Yüklenen JPG/PNG görsellerin alt boyutlarını WebP üretir (sunucu destekliyorsa).
 *
 * @param array $formats Biçimler.
 * @return array
 */
function ce_webp_output_format( $formats ) {
	if ( ! ce_opt( 'webp_enabled' ) || ! wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) ) {
		return $formats;
	}
	$formats['image/jpeg'] = 'image/webp';
	$formats['image/png']  = 'image/webp';
	return $formats;
}

add_filter( 'big_image_size_threshold', static fn() => 2560 );

add_action( 'wp_enqueue_scripts', 'ce_enqueue_assets' );
/**
 * Ön yüz dosyaları.
 */
function ce_enqueue_assets() {
	$css_ver = CE_VERSION . '.' . filemtime( CE_DIR . '/assets/css/main.css' );
	$js_ver  = CE_VERSION . '.' . filemtime( CE_DIR . '/assets/js/main.js' );

	wp_enqueue_style( 'ce-main', CE_URI . '/assets/css/main.css', array(), $css_ver );
	wp_enqueue_script( 'ce-main', CE_URI . '/assets/js/main.js', array(), $js_ver, array( 'strategy' => 'defer', 'in_footer' => true ) );

	$analytics = array();
	if ( ce_opt( 'ga4_id' ) && preg_match( '/^G-[A-Z0-9]+$/i', ce_opt( 'ga4_id' ) ) ) {
		$analytics['ga4'] = strtoupper( ce_opt( 'ga4_id' ) );
	}
	if ( ce_opt( 'gtm_id' ) && preg_match( '/^GTM-[A-Z0-9]+$/i', ce_opt( 'gtm_id' ) ) ) {
		$analytics['gtm'] = strtoupper( ce_opt( 'gtm_id' ) );
	}

	wp_localize_script(
		'ce-main',
		'CE',
		array(
			'ajax'      => admin_url( 'admin-ajax.php' ),
			'autoplay'  => absint( ce_opt( 'hero_autoplay', 7 ) ),
			'analytics' => $analytics,
			'maxMb'     => absint( ce_opt( 'quote_max_mb', 10 ) ),
			'maxFiles'  => absint( ce_opt( 'quote_max_files', 5 ) ),
			'i18n'      => array(
				'copied'     => 'IBAN panoya kopyalandı.',
				'copyFail'   => 'Kopyalanamadı, lütfen elle seçin.',
				'sending'    => 'Gönderiliyor…',
				'error'      => 'Bir hata oluştu. Lütfen tekrar deneyin.',
				'required'   => 'Bu alan zorunludur.',
				'email'      => 'Geçerli bir e-posta adresi girin.',
				'fileType'   => 'Yalnızca PDF, JPG, JPEG, PNG veya WEBP yükleyebilirsiniz.',
				'fileSize'   => 'Dosya boyutu sınırı aşıldı.',
				'fileCount'  => 'En fazla dosya sayısı aşıldı.',
				'close'      => 'Kapat',
				'prev'       => 'Önceki görsel',
				'next'       => 'Sonraki görsel',
				'noResults'  => 'Bu kategoride henüz içerik bulunmuyor.',
			),
		)
	);

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}

add_action( 'wp_head', 'ce_preload_fonts', 1 );
/**
 * Yalnızca ilk ekranda kullanılan Latin başlık fontunu önceden yükler.
 */
function ce_preload_fonts() {
	printf( '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url( CE_URI . '/assets/fonts/manrope-latin-wght-normal.woff2' ) );
	printf( '<style id="ce-fonts">%s</style>' . "\n", ce_font_face_css() ); // phpcs:ignore
}

/**
 * Yerel font tanımları (fontlar temayla birlikte gelir, dış istek yok).
 *
 * @return string
 */
function ce_font_face_css() {
	$base  = esc_url( CE_URI . '/assets/fonts/' );
	$ext   = 'U+0100-02BA,U+02BD-02C5,U+02C7-02CC,U+02CE-02D7,U+02DD-02FF,U+0304,U+0308,U+0329,U+1D00-1DBF,U+1E00-1E9F,U+1EF2-1EFF,U+2020,U+20A0-20AB,U+20AD-20C0,U+2113,U+2C60-2C7F,U+A720-A7FF';
	$latin = 'U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD';
	$css   = '';
	foreach ( array( 'Manrope' => 'manrope', 'Inter' => 'inter' ) as $family => $file ) {
		$weight = 'Manrope' === $family ? '200 800' : '100 900';
		$css   .= "@font-face{font-family:'{$family}';font-style:normal;font-display:swap;font-weight:{$weight};src:url({$base}{$file}-latin-ext-wght-normal.woff2) format('woff2');unicode-range:{$ext}}";
		$css   .= "@font-face{font-family:'{$family}';font-style:normal;font-display:swap;font-weight:{$weight};src:url({$base}{$file}-latin-wght-normal.woff2) format('woff2');unicode-range:{$latin}}";
	}
	return $css;
}

add_action( 'enqueue_block_editor_assets', 'ce_editor_fonts' );
/**
 * Blok editöründe tema fontları.
 */
function ce_editor_fonts() {
	wp_register_style( 'ce-editor-fonts', false, array(), CE_VERSION );
	wp_enqueue_style( 'ce-editor-fonts' );
	wp_add_inline_style( 'ce-editor-fonts', ce_font_face_css() );
}

add_action( 'wp_head', 'ce_head_meta', 2 );
/**
 * Tema rengi, favicon ve doğrulama etiketleri.
 */
function ce_head_meta() {
	// Scroll reveal için erken sınıf; ana betik 3 sn içinde yüklenmezse içerik görünür kalır.
	echo "<script>document.documentElement.classList.add('ce-js');setTimeout(function(){if(!window.ceReady){document.documentElement.classList.remove('ce-js');}},3000);</script>\n";
	echo '<meta name="theme-color" content="#0A1118">' . "\n";
	if ( ! has_site_icon() && ce_opt( 'favicon' ) ) {
		$icon = wp_get_attachment_image_url( absint( ce_opt( 'favicon' ) ), 'thumbnail' );
		if ( $icon ) {
			echo '<link rel="icon" href="' . esc_url( $icon ) . '">' . "\n";
			echo '<link rel="apple-touch-icon" href="' . esc_url( $icon ) . '">' . "\n";
		}
	} elseif ( ! has_site_icon() ) {
		echo '<link rel="icon" href="' . esc_url( CE_URI . '/assets/images/favicon.svg' ) . '" type="image/svg+xml">' . "\n";
	}
	$gsc = trim( (string) ce_opt( 'gsc_verification' ) );
	if ( $gsc ) {
		echo '<meta name="google-site-verification" content="' . esc_attr( $gsc ) . '">' . "\n";
	}
}

add_filter( 'body_class', 'ce_body_classes' );
/**
 * Gövde sınıfları.
 *
 * @param array $classes Sınıflar.
 * @return array
 */
function ce_body_classes( $classes ) {
	$classes[] = 'ce-site';
	if ( is_front_page() ) {
		$classes[] = 'ce-home';
	}
	return $classes;
}

add_filter( 'excerpt_length', static fn() => 24 );
add_filter( 'excerpt_more', static fn() => '…' );

// Performans: emoji betikleri ve gereksiz head etiketleri.
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
remove_action( 'admin_print_styles', 'print_emoji_styles' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wp_shortlink_wp_head' );

add_action( 'wp_enqueue_scripts', 'ce_dequeue_unused', 100 );
/**
 * Klasik temada kullanılmayan blok stillerini hafifletir.
 */
function ce_dequeue_unused() {
	if ( ! is_singular() ) {
		wp_dequeue_style( 'wp-block-library-theme' );
		wp_dequeue_style( 'classic-theme-styles' );
	}
}

add_action( 'after_switch_theme', 'ce_activate' );
/**
 * Tema etkinleştirildiğinde: roller, audit tablosu, gizli yükleme klasörü, rewrite.
 */
function ce_activate() {
	ce_install_roles();
	ce_audit_install();
	ce_private_upload_dir();
	ce_register_post_types();
	flush_rewrite_rules();
	update_option( 'ce_db_version', CE_DB_VERSION );
	if ( ! get_option( 'ce_setup_done' ) ) {
		set_transient( 'ce_setup_notice', 1, WEEK_IN_SECONDS );
	}
}

add_action( 'admin_init', 'ce_maybe_upgrade' );
/**
 * Dosyalar FTP ile güncellendiğinde gerekli yapıların kurulu olduğunu garanti eder.
 */
function ce_maybe_upgrade() {
	if ( get_option( 'ce_db_version' ) !== CE_DB_VERSION ) {
		ce_activate();
	}
}

add_action( 'admin_notices', 'ce_setup_notice' );
/**
 * Etkinleştirme sonrası kurulum hatırlatması.
 */
function ce_setup_notice() {
	if ( get_option( 'ce_setup_done' ) || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$screen = get_current_screen();
	if ( $screen && 'can-eloksal_page_ce-tools' === $screen->id ) {
		return;
	}
	echo '<div class="notice notice-info"><p><strong>Can Eloksal teması etkin.</strong> Sayfaları, hizmetleri, menüleri ve başlangıç içeriklerini tek tıkla oluşturmak için <a href="' . esc_url( admin_url( 'admin.php?page=ce-tools' ) ) . '">Kurulum &amp; Araçlar</a> sayfasını açın.</p></div>';
}

add_filter( 'upload_mimes', 'ce_upload_mimes' );
/**
 * SVG yüklemesini engeller, WebP'ye izin verir.
 *
 * @param array $mimes MIME listesi.
 * @return array
 */
function ce_upload_mimes( $mimes ) {
	unset( $mimes['svg'], $mimes['svgz'] );
	$mimes['webp'] = 'image/webp';
	return $mimes;
}
