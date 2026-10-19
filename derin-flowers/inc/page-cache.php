<?php
/**
 * Sayfa önbelleği (tema içi). Giriş yapmamış, sepeti boş ziyaretçilere sayfaların hazır HTML
 * kopyası verilir; WooCommerce, sorgular ve şablon hiç çalışmaz. Tema Ayarları → Görünüm → Hız.
 *
 * - Sepet, ödeme, hesap, sipariş takip, favoriler, arama ve 404 önbelleğe alınmaz.
 * - Sepette ürün olan, giriş yapmış veya favori ekleyen ziyaretçi her zaman canlı sayfayı görür.
 * - İçerik, ürün, stok, kategori, menü veya tema ayarı değişince önbellek kendiliğinden temizlenir.
 * - Gece yarısı ve aynı gün son sipariş saatinde eski kopyalar geçersiz olur ("Bugün / Yarın"
 *   teslimat seçenekleri hep doğru kalır).
 * - LiteSpeed Cache eklentisi kuruluysa tema önbelleği devre dışı kalır, aynı temizleme kuralları
 *   eklentiye uygulanır.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Önbellek klasörü.
 *
 * @return string
 */
function df_pc_dir() {
	return WP_CONTENT_DIR . '/cache/derin-flowers';
}

/**
 * Ayar (tema yüklenmeden önce de okunabilsin diye doğrudan seçenekten).
 *
 * @param string $key     Anahtar.
 * @param mixed  $default Varsayılan.
 * @return mixed
 */
function df_pc_opt( $key, $default ) {
	static $o = null;
	if ( null === $o ) {
		$o = get_option( 'derin_options', array() );
		$o = is_array( $o ) ? $o : array();
	}
	return array_key_exists( $key, $o ) ? $o[ $key ] : $default;
}

/**
 * Başka bir önbellek eklentisi var mı?
 *
 * @return bool
 */
function df_pc_plugin() {
	return defined( 'LSCWP_V' ) || defined( 'WP_ROCKET_VERSION' ) || defined( 'W3TC' ) || defined( 'WPCACHEHOME' ) || class_exists( 'WpFastestCache' ) || defined( 'BREEZE_VERSION' );
}

/**
 * Tema önbelleği açık mı?
 *
 * @return bool
 */
function df_pc_on() {
	return (bool) df_pc_opt( 'cache_on', 1 ) && ! df_pc_plugin() && ! ( defined( 'DONOTCACHEPAGE' ) && DONOTCACHEPAGE );
}

/**
 * İstek önbelleğe uygun mu (sayfa üretilmeden önce bilinenler)?
 *
 * @return bool
 */
function df_pc_request_ok() {
	// Karar sayfanın en başında (çerezler eklentilerce değiştirilmeden önce) bir kez verilir.
	static $ok = null;
	if ( null === $ok ) {
		$ok = df_pc_request_check();
	}
	return $ok;
}

/**
 * df_pc_request_ok() için asıl kontrol.
 *
 * @return bool
 */
function df_pc_request_check() {
	$m = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) ) : 'GET';
	if ( ! in_array( $m, array( 'GET', 'HEAD' ), true ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || ( defined( 'DOING_CRON' ) && DOING_CRON ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( defined( 'WP_CLI' ) && WP_CLI ) || is_admin() ) {
		return false;
	}
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '/'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	if ( preg_match( '#/(wp-admin|wp-login\.php|wp-json|xmlrpc\.php|wp-cron\.php|feed)(/|$|\?)|\.(xml|txt|php)(\?|$)#i', $uri ) ) {
		return false;
	}
	foreach ( array_keys( $_COOKIE ) as $c ) {
		if ( preg_match( '/^(wordpress_logged_in_|wp_woocommerce_session_|woocommerce_items_in_cart|woocommerce_cart_hash|comment_author_|wp-postpass_|df_wishlist|wordpress_no_cache)/', (string) $c ) ) {
			return false;
		}
	}
	// Yalnızca reklam / takip parametreleri olan adresler önbellekten verilir.
	$query = (string) wp_parse_url( $uri, PHP_URL_QUERY );
	if ( '' !== $query ) {
		parse_str( $query, $q );
		foreach ( array_keys( $q ) as $k ) {
			if ( ! preg_match( '/^(utm_[a-z_]+|gclid|gbraid|wbraid|fbclid|msclkid|srsltid|_ga|_gl|ref)$/', (string) $k ) ) {
				return false;
			}
		}
	}
	return true;
}

/**
 * İsteğin önbellek dosyası.
 *
 * @return string
 */
function df_pc_file() {
	$host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) ) : '';
	$path = strtolower( (string) wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '/', PHP_URL_PATH ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	return df_pc_dir() . '/' . md5( ( is_ssl() ? 'https' : 'http' ) . '://' . $host . $path ) . '.html';
}

/**
 * Bu andan önce üretilmiş kopyalar geçersiz (gece yarısı ve son sipariş saati).
 *
 * @return int Zaman damgası.
 */
function df_pc_boundary() {
	$tz    = wp_timezone();
	$now   = new DateTimeImmutable( 'now', $tz );
	$mid   = $now->setTime( 0, 0 )->getTimestamp();
	$parts = array_map( 'intval', explode( ':', (string) df_pc_opt( 'df_cutoff', '16:00' ) . ':0' ) );
	$cut   = $now->setTime( max( 0, min( 23, $parts[0] ) ), max( 0, min( 59, $parts[1] ) ) )->getTimestamp();
	$b     = $mid;
	if ( $now->getTimestamp() >= $cut ) {
		$b = max( $b, $cut );
	}
	return max( $b, (int) get_option( 'df_pc_flushed', 0 ) );
}

/**
 * Süre (saniye); güvenlik anahtarları geçerliyken yenilensin diye en fazla 10 saat.
 *
 * @return int
 */
function df_pc_ttl() {
	return HOUR_IN_SECONDS * max( 1, min( 10, (int) df_pc_opt( 'cache_ttl', 6 ) ) );
}

/**
 * Önbellekten ver (tema yüklenirken, WooCommerce ve şablon çalışmadan).
 */
function df_pc_serve() {
	if ( ! df_pc_on() || ! df_pc_request_ok() ) {
		return;
	}
	$file = df_pc_file();
	$time = @filemtime( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	if ( ! $time || $time < time() - df_pc_ttl() || $time < df_pc_boundary() ) {
		return;
	}
	header( 'Content-Type: text/html; charset=UTF-8' );
	header( 'Cache-Control: no-cache, must-revalidate, max-age=0' );
	header( 'X-DF-Cache: HIT' );
	header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', $time ) . ' GMT' );
	if ( 'HEAD' !== strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) ) ) {
		readfile( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}
	exit;
}

/**
 * Sayfa üretildikten sonra kaydet.
 */
function df_pc_start() {
	if ( ! df_pc_on() || ! df_pc_request_ok() || is_user_logged_in() ) {
		return;
	}
	if ( is_search() || is_404() || is_preview() || is_feed() || post_password_required() || ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() ) ) || is_page( array( 'siparis-takip', 'favorilerim' ) ) || df_opt( 'maint_on', 0 ) ) {
		return;
	}
	if ( function_exists( 'wc_notice_count' ) && wc_notice_count() > 0 ) {
		return;
	}
	header( 'X-DF-Cache: MISS' );
	ob_start( 'df_pc_store' );
}
add_action( 'template_redirect', 'df_pc_start', 9999 );

/**
 * Çıktıyı dosyaya yaz.
 *
 * @param string $html Sayfa.
 * @return string
 */
function df_pc_store( $html ) {
	if ( ( defined( 'DONOTCACHEPAGE' ) && DONOTCACHEPAGE ) || 200 !== http_response_code() || strlen( $html ) < 500 || false === stripos( $html, '</html>' ) ) {
		return $html;
	}
	foreach ( headers_list() as $h ) {
		if ( 0 === stripos( $h, 'Set-Cookie:' ) || ( 0 === stripos( $h, 'Content-Type:' ) && false === stripos( $h, 'text/html' ) ) ) {
			return $html;
		}
	}
	$dir = df_pc_dir();
	if ( ! is_dir( $dir ) && ! wp_mkdir_p( $dir ) ) {
		return $html;
	}
	if ( ! file_exists( $dir . '/index.html' ) ) {
		@file_put_contents( $dir . '/index.html', '' ); // phpcs:ignore
	}
	$tmp = df_pc_file() . '.' . wp_rand() . '.tmp';
	if ( false !== @file_put_contents( $tmp, $html . "\n<!-- Derin Flowers önbellek: " . wp_date( 'Y-m-d H:i:s' ) . ' -->' ) ) { // phpcs:ignore
		@rename( $tmp, df_pc_file() ); // phpcs:ignore
	}
	return $html;
}

/**
 * Tüm önbelleği temizle.
 */
function df_pc_flush() {
	update_option( 'df_pc_flushed', time(), true );
	$dir = df_pc_dir();
	if ( is_dir( $dir ) ) {
		foreach ( (array) glob( $dir . '/*.html' ) as $f ) {
			if ( 'index.html' !== basename( $f ) ) {
				@unlink( $f ); // phpcs:ignore
			}
		}
	}
	if ( defined( 'LSCWP_V' ) ) {
		do_action( 'litespeed_purge_all' );
	}
	if ( function_exists( 'rocket_clean_domain' ) ) {
		rocket_clean_domain();
	}
}

/**
 * İçerik değişince temizle.
 *
 * @param int|null $post_id Yazı.
 */
function df_pc_flush_on_post( $post_id = null ) {
	if ( $post_id && ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) ) {
		return;
	}
	if ( $post_id && in_array( get_post_type( $post_id ), array( 'shop_order', 'shop_order_placehold', 'nav_menu_item', 'customize_changeset', 'oembed_cache' ), true ) ) {
		return;
	}
	df_pc_flush();
}
foreach ( array( 'save_post', 'deleted_post', 'trashed_post' ) as $df_pc_h ) {
	add_action( $df_pc_h, 'df_pc_flush_on_post' );
}
foreach ( array( 'edited_term', 'created_term', 'delete_term', 'wp_update_nav_menu', 'update_option_derin_options', 'switch_theme', 'customize_save_after', 'woocommerce_product_set_stock', 'woocommerce_variation_set_stock', 'woocommerce_product_set_stock_status', 'woocommerce_variation_set_stock_status', 'comment_post', 'wp_set_comment_status', 'update_option_woocommerce_bacs_accounts' ) as $df_pc_h ) {
	add_action( $df_pc_h, 'df_pc_flush', 99, 0 );
}

/**
 * LiteSpeed Cache eklentisi: dinamik sayfaları önbelleğe alma; sınır saatlerinde temizle.
 */
function df_pc_litespeed_rules() {
	if ( ! defined( 'LSCWP_V' ) ) {
		return;
	}
	if ( ( function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() ) ) || is_page( array( 'siparis-takip', 'favorilerim' ) ) ) {
		do_action( 'litespeed_control_set_nocache', 'derin-flowers: dinamik sayfa' );
	}
}
add_action( 'template_redirect', 'df_pc_litespeed_rules', 1 );

/**
 * Saatlik bakım: sınır saati geçtiyse (gece yarısı / son sipariş saati) eklenti önbelleğini de
 * temizle; süresi dolmuş dosyaları sil.
 */
function df_pc_cron() {
	$b = df_pc_boundary();
	if ( (int) get_option( 'df_pc_last_boundary', 0 ) < $b ) {
		update_option( 'df_pc_last_boundary', $b, false );
		if ( df_pc_plugin() ) {
			df_pc_flush();
		}
	}
	$dir = df_pc_dir();
	if ( is_dir( $dir ) ) {
		$limit = max( $b, time() - df_pc_ttl() );
		foreach ( (array) glob( $dir . '/*.html' ) as $f ) {
			if ( 'index.html' !== basename( $f ) && @filemtime( $f ) < $limit ) { // phpcs:ignore
				@unlink( $f ); // phpcs:ignore
			}
		}
	}
}
add_action( 'df_pc_hourly', 'df_pc_cron' );
if ( ! wp_next_scheduled( 'df_pc_hourly' ) ) {
	wp_schedule_event( time() + 300, 'hourly', 'df_pc_hourly' );
}

/**
 * Üst çubukta "Önbelleği temizle".
 *
 * @param WP_Admin_Bar $bar Çubuk.
 */
function df_pc_admin_bar( $bar ) {
	if ( ! current_user_can( 'manage_options' ) || ( ! df_pc_on() && ! defined( 'LSCWP_V' ) ) ) {
		return;
	}
	$bar->add_node(
		array(
			'id'    => 'df-cache-flush',
			'title' => 'Önbelleği temizle',
			'href'  => wp_nonce_url( admin_url( 'admin-post.php?action=df_cache_flush' ), 'df_cache_flush' ),
		)
	);
}
add_action( 'admin_bar_menu', 'df_pc_admin_bar', 90 );

/**
 * Temizle düğmesi.
 */
function df_pc_flush_action() {
	if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'df_cache_flush' ) ) {
		wp_die( 'Yetkiniz yok.' );
	}
	df_pc_flush();
	wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url() );
	exit;
}
add_action( 'admin_post_df_cache_flush', 'df_pc_flush_action' );

df_pc_serve();
