<?php
/**
 * Tema kurulumu, görsel boyutları, stil ve script yüklemeleri.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Tema desteği.
 */
function df_setup() {
	load_theme_textdomain( 'derin-flowers', DF_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );
	add_theme_support( 'editor-styles' );

	// WooCommerce: tema kendi galerisini kullanır (video desteği için).
	add_theme_support(
		'woocommerce',
		array(
			'thumbnail_image_width' => 720,
			'single_image_width'    => 1200,
			'product_grid'          => array(
				'default_columns' => 4,
				'min_columns'     => 2,
				'max_columns'     => 4,
			),
		)
	);

	register_nav_menus(
		array(
			'primary'  => 'Ana Menü (header altı)',
			'footer_1' => 'Footer: Hakkımızda',
			'footer_2' => 'Footer: Müşteri Hizmetleri',
			'legal'    => 'Footer: Yasal bağlantılar (alt satır)',
		)
	);

	// Görsel boyutları — her kullanım için doğru oran, bulanık/küçük görsel yok.
	add_image_size( 'df-hero', 2400, 1400, false );
	add_image_size( 'df-hero-mobile', 1080, 1600, false );
	add_image_size( 'df-card', 720, 900, true );        // 4:5 ürün kartı.
	add_image_size( 'df-portrait', 900, 1200, true );   // 3:4 kategori.
	add_image_size( 'df-wide', 1600, 1000, true );      // 16:10 editorial.
	add_image_size( 'df-banner', 2400, 1100, false );   // Tam genişlik banner.
	add_image_size( 'df-square', 640, 640, true );      // Instagram.
}
add_action( 'after_setup_theme', 'df_setup' );

/**
 * İçerik genişliği.
 */
function df_content_width() {
	$GLOBALS['content_width'] = 1200;
}
add_action( 'after_setup_theme', 'df_content_width', 0 );

/**
 * Mağaza sayfaları mı? (shop.css yüklemek için)
 *
 * @return bool
 */
function df_is_shop_context() {
	if ( ! df_wc() ) {
		return false;
	}
	if ( is_woocommerce() || is_cart() || is_checkout() || is_account_page() ) {
		return true;
	}
	$post = get_post();
	if ( $post && is_singular() ) {
		foreach ( array( 'derin_siparis_takip', 'derin_favoriler', 'derin_teslimat_bolgeleri', 'products', 'product_category', 'woocommerce_order_tracking' ) as $sc ) {
			if ( has_shortcode( $post->post_content, $sc ) ) {
				return true;
			}
		}
	}
	return false;
}

/**
 * Stil ve scriptler.
 */
function df_enqueue() {
	$fonts = df_fonts_url();
	if ( $fonts ) {
		wp_enqueue_style( 'df-fonts', $fonts, array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
	}

	wp_enqueue_style( 'df-main', DF_URI . '/assets/css/main.css', array(), DF_VERSION );
	wp_add_inline_style( 'df-main', df_css_variables() );

	if ( is_front_page() ) {
		wp_enqueue_style( 'df-home', DF_URI . '/assets/css/home.css', array( 'df-main' ), DF_VERSION );
		wp_enqueue_script( 'df-home', DF_URI . '/assets/js/home.js', array(), DF_VERSION, true );
	} elseif ( function_exists( 'is_product' ) && is_product() ) {
		// Benzer ürünler vitrin kartlarını kullanır.
		wp_enqueue_style( 'df-home', DF_URI . '/assets/css/home.css', array( 'df-main' ), DF_VERSION );
	}

	if ( df_is_shop_context() ) {
		wp_enqueue_style( 'df-shop', DF_URI . '/assets/css/shop.css', array( 'df-main' ), DF_VERSION );
	}

	if ( is_singular() && ! df_wc_is_product() && ! is_front_page() ) {
		wp_enqueue_style( 'df-content', DF_URI . '/assets/css/content.css', array( 'df-main' ), DF_VERSION );
	} elseif ( is_home() || is_archive() || is_search() || is_404() ) {
		if ( ! df_wc() || ! is_woocommerce() ) {
			wp_enqueue_style( 'df-content', DF_URI . '/assets/css/content.css', array( 'df-main' ), DF_VERSION );
		}
	}

	$deps = array();
	if ( df_wc() ) {
		$deps[] = 'jquery';
		wp_enqueue_script( 'wc-cart-fragments' );
	}
	wp_enqueue_script( 'df-main', DF_URI . '/assets/js/main.js', $deps, DF_VERSION, true );
	wp_localize_script(
		'df-main',
		'DF',
		array(
			'ajax'      => admin_url( 'admin-ajax.php' ),
			'nonce'     => wp_create_nonce( 'df_nonce' ),
			'loggedIn'  => is_user_logged_in(),
			'wishlist'  => function_exists( 'df_wishlist_ids' ) ? df_wishlist_ids() : array(),
			'shopUrl'   => df_wc() ? wc_get_page_permalink( 'shop' ) : home_url( '/' ),
			'i18n'      => array(
				'added'       => 'Sepete eklendi',
				'favAdded'    => 'Favorilere eklendi',
				'favRemoved'  => 'Favorilerden çıkarıldı',
				'noResults'   => 'Sonuç bulunamadı.',
				'allResults'  => 'Tüm sonuçları gör',
				'subscribed'  => 'Teşekkürler! Bültenimize kaydoldunuz.',
				'error'       => 'Bir hata oluştu, lütfen tekrar deneyin.',
			),
		)
	);

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'df_enqueue', 20 );

/**
 * Ürün sayfası mı?
 *
 * @return bool
 */
function df_wc_is_product() {
	return df_wc() && is_product();
}

/**
 * Font bağlantıları için preconnect ve hero görseli için preload.
 */
function df_head_hints() {
	if ( df_fonts_url() ) {
		echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
		echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
	}
	if ( is_front_page() && in_array( 'hero', df_active_sections(), true ) ) {
		$slides = df_opt( 'hero_slides', array() );
		if ( ! empty( $slides[0]['image'] ) ) {
			$desk = df_img_url( $slides[0]['image'], 'df-hero' );
			$mob  = ! empty( $slides[0]['image_mobile'] ) ? df_img_url( $slides[0]['image_mobile'], 'df-hero-mobile' ) : df_img_url( $slides[0]['image'], 'large' );
			if ( $mob ) {
				printf( '<link rel="preload" as="image" href="%s" media="(max-width: 767px)" fetchpriority="high">' . "\n", esc_url( $mob ) );
				printf( '<link rel="preload" as="image" href="%s" media="(min-width: 768px)" fetchpriority="high">' . "\n", esc_url( $desk ) );
			} elseif ( $desk ) {
				printf( '<link rel="preload" as="image" href="%s" fetchpriority="high">' . "\n", esc_url( $desk ) );
			}
		}
	}
}
add_action( 'wp_head', 'df_head_hints', 2 );

/**
 * Body sınıfları.
 *
 * @param array $classes Sınıflar.
 * @return array
 */
function df_body_classes( $classes ) {
	$classes[] = 'df';
	if ( df_opt( 'header_sticky' ) ) {
		$classes[] = 'df-sticky-header';
	}
	$classes[] = 'df-header--' . sanitize_html_class( df_opt( 'header_layout', 'center' ) );
	return $classes;
}
add_filter( 'body_class', 'df_body_classes' );

/**
 * Özet uzunluğu.
 *
 * @return int
 */
function df_excerpt_length() {
	return 22;
}
add_filter( 'excerpt_length', 'df_excerpt_length' );

/**
 * Özet sonu.
 *
 * @return string
 */
function df_excerpt_more() {
	return '…';
}
add_filter( 'excerpt_more', 'df_excerpt_more' );

/**
 * Widget alanı (blog kenar çubuğu opsiyonel).
 */
function df_widgets() {
	register_sidebar(
		array(
			'name'          => 'Blog kenar çubuğu',
			'id'            => 'blog',
			'before_widget' => '<section id="%1$s" class="df-widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h3 class="df-widget__title">',
			'after_title'   => '</h3>',
		)
	);
}
add_action( 'widgets_init', 'df_widgets' );

/**
 * Sabit WhatsApp butonu.
 */
function df_whatsapp_float() {
	if ( ! df_opt( 'whatsapp_float' ) || ! df_whatsapp_url() || ( df_wc() && ( is_checkout() || is_cart() ) ) ) {
		return;
	}
	printf(
		'<a class="df-wa-float" href="%s" target="_blank" rel="noopener" aria-label="WhatsApp ile yazın">%s</a>',
		esc_url( df_whatsapp_url( 'Merhaba, sipariş hakkında bilgi almak istiyorum.' ) ),
		df_icon( 'whatsapp', array( 'size' => 26 ) ) // phpcs:ignore
	);
}
add_action( 'wp_footer', 'df_whatsapp_float', 5 );

/**
 * Gutenberg editöründe tema fontları.
 */
function df_editor_assets() {
	$fonts = df_fonts_url();
	if ( $fonts ) {
		add_editor_style( $fonts );
	}
}
add_action( 'admin_init', 'df_editor_assets' );

/**
 * Admin çubuğuna kısayol.
 *
 * @param WP_Admin_Bar $bar Çubuk.
 */
function df_admin_bar( $bar ) {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	$bar->add_node(
		array(
			'id'    => 'df-panel',
			'title' => 'Derin Flowers Paneli',
			'href'  => admin_url( 'admin.php?page=derin-flowers' . ( is_front_page() ? '#home' : '' ) ),
		)
	);
}
add_action( 'admin_bar_menu', 'df_admin_bar', 80 );
