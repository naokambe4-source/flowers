<?php
/**
 * Hız optimizasyonları (Görünüm & Marka → Hız). Her biri ayrı ayrı kapatılabilir.
 *
 * - Kullanılmayan WordPress blok / WooCommerce Blocks stilleri yüklenmez
 * - Emoji betikleri, oEmbed, jQuery Migrate kaldırılır
 * - Tema ve WooCommerce betikleri "defer" ile sayfayı bekletmeden yüklenir
 * - Google Fonts sayfayı bekletmeden (asenkron) yüklenir, gereksiz kalınlıklar alınmaz
 * - Sepet parçası (cart fragments) yalnızca sepette ürün varsa çalışır
 * - WooCommerce sipariş kaynak takibi betikleri kaldırılır (isteğe bağlı)
 * - Ürün sayfasında ağır select kütüphanesi yerine yerel liste
 * - Tema CSS'i küçültülmüş (.min.css) dosyalardan yüklenir
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Hız ayarı açık mı?
 *
 * @param string $key Anahtar (perf_*).
 * @return bool
 */
function df_perf( $key ) {
	return ! is_admin() && (bool) df_opt( $key, 1 );
}

/**
 * Bu sayfada blok içerik var mı? (blog yazısı / Gutenberg ile yazılmış sayfa)
 *
 * @return bool
 */
function df_perf_needs_blocks() {
	if ( ! is_singular() ) {
		return false;
	}
	$post = get_post();
	return $post && 'product' !== $post->post_type && has_blocks( $post );
}

/**
 * Widget alanlarında WooCommerce bloğu var mı?
 *
 * @return bool
 */
function df_perf_wc_blocks_used() {
	static $used = null;
	if ( null === $used ) {
		$used = false;
		foreach ( (array) get_option( 'widget_block', array() ) as $w ) {
			if ( is_array( $w ) && ! empty( $w['content'] ) && false !== strpos( $w['content'], 'wp:woocommerce/' ) ) {
				$used = true;
				break;
			}
		}
	}
	return $used;
}

/**
 * Yalnızca kullanılan blokların stillerini yükle (tüm blok kütüphanesi yerine).
 *
 * @param bool $load Ayrı yükleme.
 * @return bool
 */
function df_perf_block_assets( $load ) {
	return df_perf( 'perf_blocks' ) ? true : $load;
}
add_filter( 'should_load_separate_core_block_assets', 'df_perf_block_assets' );

/**
 * Gereksiz stilleri ve betikleri kaldır.
 */
function df_perf_dequeue() {
	if ( is_admin() || df_live() ) {
		return;
	}
	// Blok stilleri: WordPress yalnızca sayfada (widget'lar dahil) kullanılan blokların stilini yükler
	// (df_perf_block_assets). Genel blok/tema stilleri kaldırılmaz; footer widget'ları bozulmaz.
	if ( df_perf( 'perf_blocks' ) && ! df_perf_needs_blocks() && ! df_perf_wc_blocks_used() ) {
		foreach ( array( 'wc-blocks-style', 'wc-blocks-vendors-style', 'wc-all-blocks-style' ) as $h ) {
			wp_dequeue_style( $h );
		}
	}
	if ( df_perf( 'perf_blocks' ) && df_wc() && ! is_tax( 'product_brand' ) ) {
		wp_dequeue_style( 'brands-styles' );
	}
	if ( df_perf( 'perf_attribution' ) ) {
		wp_dequeue_script( 'wc-order-attribution' );
		wp_dequeue_script( 'sourcebuster-js' );
	}
	if ( df_perf( 'perf_fragments' ) && df_wc() && ! is_cart() && ! is_checkout() ) {
		// Sepet boşsa sepet sayacını yenilemek için her sayfada istek atılmaz.
		if ( empty( $_COOKIE['woocommerce_items_in_cart'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			wp_dequeue_script( 'wc-cart-fragments' );
		}
	}
	if ( df_wc() && is_product() ) {
		wp_dequeue_script( 'comment-reply' );
		if ( df_perf( 'perf_select' ) ) {
			// İlçe listesi ürün sayfasında yerel <select> ile gösterilir (165 KB daha az).
			wp_dequeue_script( 'selectWoo' );
			wp_dequeue_style( 'select2' );
		}
	}
	if ( ! is_user_logged_in() ) {
		wp_dequeue_style( 'dashicons' );
	}
}
add_action( 'wp_enqueue_scripts', 'df_perf_dequeue', 100 );
add_action( 'wp_footer', 'df_perf_dequeue', 1 ); // Sonradan (sayfa sonunda) eklenen blok stilleri için.

/**
 * Betikleri "defer" ile yükle. WordPress, bağımlılığı ya da satır içi kodu nedeniyle
 * güvenli olmayan betiklerde defer'i kendisi uygulamaz.
 */
function df_perf_defer() {
	if ( is_admin() || ! df_perf( 'perf_defer' ) ) {
		return;
	}
	$skip = df_perf( 'perf_jquery_footer' ) ? array( 'jquery-migrate' ) : array( 'jquery', 'jquery-core', 'jquery-migrate' );
	// Ödeme ve sepet sayfalarında ödeme altyapılarının betiklerine dokunulmaz.
	if ( df_wc() && ( is_checkout() || is_cart() || is_account_page() ) ) {
		return;
	}
	$wp = wp_scripts();
	foreach ( $wp->queue as $handle ) {
		if ( in_array( $handle, $skip, true ) || empty( $wp->registered[ $handle ] ) ) {
			continue;
		}
		if ( ! $wp->get_data( $handle, 'strategy' ) ) {
			$wp->add_data( $handle, 'strategy', 'defer' );
		}
	}
	// jQuery de bekletmeden yüklenir (WordPress, satır içi kodu olan bağımlılıklarda bunu kendisi geri alır).
	if ( df_perf( 'perf_jquery_footer' ) ) {
		foreach ( array( 'jquery', 'jquery-core' ) as $h ) {
			if ( ! $wp->get_data( $h, 'strategy' ) ) {
				$wp->add_data( $h, 'strategy', 'defer' );
			}
		}
	}
}
add_action( 'wp_enqueue_scripts', 'df_perf_defer', 999 );

/**
 * jQuery Migrate, emoji, oEmbed.
 *
 * @param WP_Scripts $scripts Betikler.
 */
function df_perf_no_migrate( $scripts ) {
	if ( ! is_admin() && df_perf( 'perf_migrate' ) && isset( $scripts->registered['jquery'] ) ) {
		$scripts->registered['jquery']->deps = array_diff( $scripts->registered['jquery']->deps, array( 'jquery-migrate' ) );
	}
}
add_action( 'wp_default_scripts', 'df_perf_no_migrate' );

/**
 * Emoji ve oEmbed kaldır.
 */
function df_perf_cleanup() {
	if ( ! df_opt( 'perf_emoji', 1 ) ) {
		return;
	}
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
	remove_action( 'wp_head', 'wp_oembed_add_host_js' );
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );
	add_filter( 'emoji_svg_url', '__return_false' );
}
add_action( 'init', 'df_perf_cleanup' );

/**
 * Google Fonts: sayfayı bekletmeden yükle (preload + onload).
 *
 * @param string $html   Etiket.
 * @param string $handle Kimlik.
 * @param string $href   Adres.
 * @param string $media  Ortam.
 * @return string
 */
function df_perf_async_fonts( $html, $handle, $href, $media ) {
	if ( 'df-fonts' !== $handle || is_admin() || ! df_perf( 'perf_fonts' ) ) {
		return $html;
	}
	return '<link rel="preload" as="style" href="' . esc_url( $href ) . '">' . "\n"
		. '<link rel="stylesheet" id="df-fonts-css" href="' . esc_url( $href ) . '" media="print" onload="this.media=\'all\'">' . "\n"
		. '<noscript><link rel="stylesheet" href="' . esc_url( $href ) . '"></noscript>' . "\n";
}
add_filter( 'style_loader_tag', 'df_perf_async_fonts', 10, 4 );

/**
 * Tema CSS'i: varsa küçültülmüş sürüm (.min.css).
 *
 * @param string $src    Adres.
 * @param string $handle Kimlik.
 * @return string
 */
function df_perf_min_css( $src, $handle ) {
	if ( is_admin() || ! df_perf( 'perf_min' ) || ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG && ! df_opt( 'perf_min_force', 0 ) ) ) {
		return $src;
	}
	if ( 0 !== strpos( $handle, 'df-' ) || false === strpos( $src, DF_URI . '/assets/' ) ) {
		return $src;
	}
	$path = wp_parse_url( $src, PHP_URL_PATH );
	foreach ( array( '.css', '.js' ) as $ext ) {
		if ( substr( $path, -strlen( $ext ) ) === $ext && substr( $path, -strlen( '.min' . $ext ) ) !== '.min' . $ext ) {
			$rel = substr( $path, strpos( $path, '/assets/' ) );
			$min = substr( $rel, 0, -strlen( $ext ) ) . '.min' . $ext;
			// Küçültülmüş kopya kaynaktan eskiyse kaynağı kullan (unutulmuş derleme güvenliği).
			if ( file_exists( DF_DIR . $min ) && filemtime( DF_DIR . $min ) >= filemtime( DF_DIR . $rel ) ) {
				return str_replace( $rel, $min, $src );
			}
		}
	}
	return $src;
}
add_filter( 'style_loader_src', 'df_perf_min_css', 20, 2 );
add_filter( 'script_loader_src', 'df_perf_min_css', 20, 2 );

/**
 * Görseller: tarayıcıya "async" çözümleme.
 *
 * @param array $attr Öznitelikler.
 * @return array
 */
function df_perf_img_attr( $attr ) {
	if ( empty( $attr['decoding'] ) ) {
		$attr['decoding'] = 'async';
	}
	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'df_perf_img_attr' );

/**
 * Tema CSS'ini sayfaya göm (ayrı dosya isteği beklenmez → ilk görüntü hızlanır).
 *
 * @param string $html   Etiket.
 * @param string $handle Kimlik.
 * @param string $href   Adres.
 * @param string $media  Ortam.
 * @return string
 */
function df_perf_inline_css( $html, $handle, $href, $media ) {
	static $budget = null;
	if ( is_admin() || df_live() || ! df_perf( 'perf_inline' ) || ! in_array( $handle, array( 'df-main', 'df-home', 'df-shop', 'df-content', 'df-pages', 'df-loc' ), true ) ) {
		return $html;
	}
	$path = wp_parse_url( $href, PHP_URL_PATH );
	$rel  = substr( $path, strpos( $path, '/assets/' ) );
	$file = DF_DIR . $rel;
	if ( ! file_exists( $file ) ) {
		return $html;
	}
	if ( null === $budget ) {
		$budget = 140 * 1024;
	}
	$size = filesize( $file );
	if ( $size > $budget ) {
		return $html;
	}
	$budget -= $size;
	$css     = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	return '<style id="' . esc_attr( $handle ) . '-css">' . str_ireplace( '</style', '', $css ) . "</style>\n";
}
add_filter( 'style_loader_tag', 'df_perf_inline_css', 30, 4 );
