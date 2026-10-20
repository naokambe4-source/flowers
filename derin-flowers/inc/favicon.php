<?php
/**
 * Site simgesi (favicon). WordPress'te "Site Simgesi" yüklenmemişse temanın Derin Flowers
 * monogramı kullanılır; /favicon.ico isteği WordPress logosu yerine bu simgeyi verir.
 * Kendi simgenizi Görünüm → Özelleştir → Site Kimliği → Site Simgesi'nden yükleyebilirsiniz.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Simge etiketleri.
 */
function df_favicon_tags() {
	if ( has_site_icon() ) {
		return;
	}
	$u = DF_URI . '/assets/img/favicon/';
	echo '<link rel="icon" href="' . esc_url( $u . 'favicon-32.png' ) . '" sizes="32x32" type="image/png">' . "\n";
	echo '<link rel="icon" href="' . esc_url( $u . 'favicon-192.png' ) . '" sizes="192x192" type="image/png">' . "\n";
	echo '<link rel="apple-touch-icon" href="' . esc_url( $u . 'apple-touch-icon.png' ) . '">' . "\n";
	echo '<meta name="msapplication-TileImage" content="' . esc_url( $u . 'favicon-512.png' ) . '">' . "\n";
}
add_action( 'wp_head', 'df_favicon_tags', 2 );
add_action( 'admin_head', 'df_favicon_tags' );
add_action( 'login_head', 'df_favicon_tags' );

/**
 * /favicon.ico isteği.
 */
function df_favicon_ico() {
	if ( has_site_icon() ) {
		return;
	}
	$file = DF_DIR . '/assets/img/favicon/favicon.ico';
	if ( file_exists( $file ) ) {
		header( 'Content-Type: image/x-icon' );
		header( 'Cache-Control: public, max-age=604800' );
		readfile( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		exit;
	}
}
add_action( 'do_faviconico', 'df_favicon_ico', 1 );
