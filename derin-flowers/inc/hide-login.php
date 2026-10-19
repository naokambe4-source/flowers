<?php
/**
 * Gizli yönetim girişi: wp-login.php ve /wp-admin yerine yalnızca belirlenen adresten giriş.
 * Tema Ayarları → Yönetim & Giriş → "Gizli giriş adresi". Boş bırakılırsa kapalıdır.
 *
 * Kilitlenirseniz: wp-config.php dosyasına define( 'DF_DISABLE_HIDE_LOGIN', true ); ekleyin.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Gizli giriş adresi (boşsa özellik kapalı).
 *
 * @return string
 */
function df_hl_slug() {
	if ( defined( 'DF_DISABLE_HIDE_LOGIN' ) && DF_DISABLE_HIDE_LOGIN ) {
		return '';
	}
	$slug = sanitize_title( (string) df_opt( 'login_slug', '' ) );
	return in_array( $slug, array( '', 'wp-admin', 'wp-login', 'wp-login-php', 'admin', 'login', 'giris' ), true ) ? '' : $slug;
}

/**
 * Gizli giriş sayfasının tam adresi.
 *
 * @param string $query Sorgu dizesi.
 * @return string
 */
function df_hl_url( $query = '' ) {
	$url = get_option( 'permalink_structure' ) ? trailingslashit( home_url( '/' . df_hl_slug() ) ) : add_query_arg( df_hl_slug(), '', home_url( '/' ) );
	return $query ? $url . ( false === strpos( $url, '?' ) ? '?' : '&' ) . $query : $url;
}

/**
 * İstek gizli giriş adresine mi?
 *
 * @return bool
 */
function df_hl_is_slug_request() {
	$slug = df_hl_slug();
	if ( '' === $slug ) {
		return false;
	}
	$path = trim( (string) wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '', PHP_URL_PATH ), '/' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$home = trim( (string) wp_parse_url( home_url(), PHP_URL_PATH ), '/' );
	$want = trim( $home . '/' . $slug, '/' );
	return $path === $want || ( ! get_option( 'permalink_structure' ) && isset( $_GET[ $slug ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}

/**
 * Bulunamadı sayfasına gönder.
 */
function df_hl_404() {
	nocache_headers();
	wp_safe_redirect( home_url( '/404/' ), 302 );
	exit;
}

/**
 * İstekleri yönlendir: gizli adres → giriş ekranı; wp-login.php ve oturumsuz /wp-admin → 404.
 */
function df_hl_route() {
	if ( '' === df_hl_slug() || ( defined( 'WP_CLI' ) && WP_CLI ) || wp_doing_cron() ) {
		return;
	}
	global $pagenow;
	if ( df_hl_is_slug_request() ) {
		// wp-login.php kendi değişkenlerini global kabul eder.
		global $error, $interim_login, $action, $user_login, $user, $redirect_to;
		$pagenow = 'wp-login.php'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		require_once ABSPATH . 'wp-login.php';
		exit;
	}
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	if ( 'wp-login.php' === $pagenow || false !== stripos( $uri, 'wp-login.php' ) ) {
		// Şifre korumalı sayfa formu wp-login.php?action=postpass ile çalışır; ona dokunma.
		if ( isset( $_GET['action'] ) && 'postpass' === $_GET['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		df_hl_404();
	}
	if ( is_admin() && ! is_user_logged_in() && ! wp_doing_ajax() && 'admin-post.php' !== $pagenow ) {
		df_hl_404();
	}
}
add_action( 'init', 'df_hl_route', 1 );

/**
 * WordPress'in ürettiği giriş / çıkış / şifre sıfırlama bağlantılarını gizli adrese çevir.
 *
 * @param string $url Adres.
 * @return string
 */
function df_hl_filter_url( $url ) {
	if ( '' === df_hl_slug() || false === strpos( (string) $url, 'wp-login.php' ) || false !== strpos( (string) $url, 'action=postpass' ) ) {
		return $url;
	}
	$query = (string) wp_parse_url( $url, PHP_URL_QUERY );
	return df_hl_url( $query );
}
add_filter( 'site_url', 'df_hl_filter_url', 100 );
add_filter( 'network_site_url', 'df_hl_filter_url', 100 );
add_filter( 'wp_redirect', 'df_hl_filter_url', 100 );
add_filter( 'login_url', 'df_hl_filter_url', 100 );
add_filter( 'logout_url', 'df_hl_filter_url', 100 );
add_filter( 'lostpassword_url', 'df_hl_filter_url', 100 );
add_filter( 'register_url', 'df_hl_filter_url', 100 );

/**
 * Çıkış yapınca /wp-admin'e değil ana sayfaya dön (gizli adres açık ise).
 *
 * @param string $to Hedef.
 * @return string
 */
function df_hl_logout_redirect( $to ) {
	return ( '' !== df_hl_slug() && false !== strpos( (string) $to, 'wp-admin' ) ) ? home_url( '/' ) : $to;
}
add_filter( 'logout_redirect', 'df_hl_logout_redirect' );
