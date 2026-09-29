<?php
/**
 * Güvenlik sertleştirmeleri: giriş deneme sınırı, güvenlik başlıkları, XML-RPC kapatma,
 * kullanıcı adı taraması engeli, sürüm gizleme, tema/eklenti dosya düzenleyicisini kapatma.
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

// Yönetim panelinden PHP dosyası düzenlemeyi kapat (wp-config'te tanımlıysa ona uyulur).
if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
	define( 'DISALLOW_FILE_EDIT', true );
}

const CE_LOGIN_MAX_ATTEMPTS = 5;
const CE_LOGIN_LOCK_MINUTES = 15;

/**
 * IP'ye özgü transient anahtarı.
 *
 * @param string $prefix Önek.
 * @return string
 */
function ce_login_key( $prefix ) {
	return 'ce_' . $prefix . '_' . md5( ce_client_ip() . wp_salt( 'nonce' ) );
}

add_filter( 'authenticate', 'ce_login_check_lock', 30, 3 );
/**
 * Kilitli IP'lerin girişini engeller.
 *
 * @param WP_User|WP_Error|null $user     Kullanıcı.
 * @param string                $username Kullanıcı adı.
 * @param string                $password Şifre.
 * @return WP_User|WP_Error|null
 */
function ce_login_check_lock( $user, $username, $password ) {
	unset( $password );
	$until = (int) get_transient( ce_login_key( 'lock' ) );
	if ( $until > time() ) {
		$minutes = max( 1, (int) ceil( ( $until - time() ) / 60 ) );
		if ( function_exists( 'ce_audit' ) ) {
			ce_audit( 'login_blocked', 'auth', 0, $username );
		}
		return new WP_Error( 'ce_locked', sprintf( 'Çok fazla başarısız giriş denemesi. Lütfen %d dakika sonra tekrar deneyin.', $minutes ) );
	}
	return $user;
}

add_action( 'wp_login_failed', 'ce_login_failed' );
/**
 * Başarısız denemeleri sayar, sınır aşılınca kilitler.
 *
 * @param string $username Kullanıcı adı.
 */
function ce_login_failed( $username ) {
	$key   = ce_login_key( 'fail' );
	$count = (int) get_transient( $key ) + 1;
	set_transient( $key, $count, CE_LOGIN_LOCK_MINUTES * MINUTE_IN_SECONDS );
	if ( function_exists( 'ce_audit' ) ) {
		ce_audit( 'login_failed', 'auth', 0, $username );
	}
	if ( $count >= CE_LOGIN_MAX_ATTEMPTS ) {
		set_transient( ce_login_key( 'lock' ), time() + CE_LOGIN_LOCK_MINUTES * MINUTE_IN_SECONDS, CE_LOGIN_LOCK_MINUTES * MINUTE_IN_SECONDS );
		delete_transient( $key );
	}
}

add_action( 'wp_login', 'ce_login_success', 10, 2 );
/**
 * Başarılı girişte sayaçları temizler.
 */
function ce_login_success() {
	delete_transient( ce_login_key( 'fail' ) );
	delete_transient( ce_login_key( 'lock' ) );
}

add_filter( 'login_errors', 'ce_generic_login_error' );
/**
 * Kullanıcı adının var olup olmadığını ele vermeyen genel hata mesajı.
 *
 * @param string $error Hata.
 * @return string
 */
function ce_generic_login_error( $error ) {
	global $errors;
	if ( is_wp_error( $errors ) && in_array( 'ce_locked', $errors->get_error_codes(), true ) ) {
		return $error;
	}
	if ( is_wp_error( $errors ) && array_intersect( array( 'invalid_username', 'incorrect_password', 'invalid_email' ), $errors->get_error_codes() ) ) {
		return '<strong>Hata:</strong> Kullanıcı adı/e-posta veya şifre hatalı.';
	}
	return $error;
}

/**
 * Güvenlik başlıkları.
 */
function ce_security_headers() {
	if ( headers_sent() ) {
		return;
	}
	header( 'X-Content-Type-Options: nosniff' );
	header( 'X-Frame-Options: SAMEORIGIN' );
	header( 'Referrer-Policy: strict-origin-when-cross-origin' );
	header( 'Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()' );
	header_remove( 'X-Pingback' );
	header_remove( 'X-Powered-By' );
}
add_action( 'send_headers', 'ce_security_headers' );
add_action( 'admin_init', 'ce_security_headers' );
add_action( 'login_init', 'ce_security_headers' );

// XML-RPC ve pingback kapalı.
add_filter( 'xmlrpc_enabled', '__return_false' );
add_filter(
	'wp_headers',
	static function ( $headers ) {
		unset( $headers['X-Pingback'] );
		return $headers;
	}
);

// Sürüm bilgisini gizle.
remove_action( 'wp_head', 'wp_generator' );
add_filter( 'the_generator', '__return_empty_string' );

add_action( 'template_redirect', 'ce_block_author_scan', 1 );
/**
 * ?author=N ile kullanıcı adı taramasını engeller.
 */
function ce_block_author_scan() {
	if ( ! is_user_logged_in() && isset( $_GET['author'] ) ) { // phpcs:ignore
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}
	// Yazar arşivleri kurumsal sitede kullanılmaz.
	if ( is_author() ) {
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}
}

add_filter( 'rest_endpoints', 'ce_restrict_user_endpoints' );
/**
 * REST API kullanıcı listesini yalnızca giriş yapmış kullanıcılara açar.
 *
 * @param array $endpoints Uç noktalar.
 * @return array
 */
function ce_restrict_user_endpoints( $endpoints ) {
	if ( ! is_user_logged_in() ) {
		unset( $endpoints['/wp/v2/users'], $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
	}
	return $endpoints;
}

add_filter( 'wp_sitemaps_add_provider', 'ce_no_user_sitemap', 10, 2 );
/**
 * Çekirdek sitemap'te kullanıcı sağlayıcısını kapatır (tema kendi sitemap'ini üretir).
 *
 * @param WP_Sitemaps_Provider|false $provider Sağlayıcı.
 * @param string                     $name     Ad.
 * @return WP_Sitemaps_Provider|false
 */
function ce_no_user_sitemap( $provider, $name ) {
	return 'users' === $name ? false : $provider;
}
