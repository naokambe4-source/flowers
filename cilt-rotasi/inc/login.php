<?php
/**
 * Markalı giriş ekranı, girişten sonra panel yönlendirmesi, üst çubuk karşılaması.
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Giriş ekranı stilleri.
 */
function cr_login_styles() {
	if ( ! cr_opt( 'adm_login' ) ) {
		return;
	}
	wp_enqueue_style( 'cr-login-fonts', 'https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Manrope:wght@400;500;600;700&display=swap&subset=latin-ext', array(), null ); // phpcs:ignore
	wp_enqueue_style( 'cr-login', CR_URI . '/assets/admin/login.css', array(), CR_VERSION );
	$greet = str_replace( array( '\\', '"', "\n" ), array( '', '\\"', ' ' ), wp_strip_all_tags( cr_opt( 'adm_greeting' ) ) );
	wp_add_inline_style( 'cr-login', '#login h1 a::after{content:"' . $greet . ' \\2665"}' );
	$img = cr_img_url( cr_opt( 'adm_login_image' ), 'large' );
	if ( $img ) {
		wp_add_inline_style( 'cr-login', '.cr-login-art{background-image:url(' . esc_url( $img ) . ')}' );
	}
}
add_action( 'login_enqueue_scripts', 'cr_login_styles' );

/**
 * Giriş ekranı görsel paneli ve karşılama sözü.
 */
function cr_login_art() {
	if ( ! cr_opt( 'adm_login' ) ) {
		return;
	}
	echo '<div class="cr-login-art" aria-hidden="true"><div class="cr-login-art__inner"><span class="cr-login-art__heart">♥</span><p class="cr-login-art__quote">' . esc_html( cr_opt( 'adm_login_text' ) ) . '</p><span class="cr-login-art__brand">' . esc_html( cr_opt( 'logo_text' ) ) . '</span></div></div>';
}
add_action( 'login_header', 'cr_login_art' );

/**
 * Logo bağlantısı ve başlığı.
 *
 * @return string
 */
function cr_login_url() {
	return home_url( '/' );
}
add_filter( 'login_headerurl', 'cr_login_url' );
add_filter(
	'login_headertext',
	function () {
		return cr_opt( 'logo_text', get_bloginfo( 'name' ) );
	}
);

/**
 * Girişten sonra Cilt Rotası paneline yönlendir.
 *
 * @param string  $redirect_to Hedef.
 * @param string  $requested   İstenen.
 * @param WP_User $user        Kullanıcı.
 * @return string
 */
function cr_login_redirect( $redirect_to, $requested, $user ) {
	if ( ! cr_opt( 'adm_redirect' ) || is_wp_error( $user ) || ! $user instanceof WP_User ) {
		return $redirect_to;
	}
	if ( user_can( $user, 'edit_posts' ) && ( ! $requested || false !== strpos( $requested, 'wp-admin/' ) && ( admin_url() === $requested || admin_url( 'index.php' ) === $requested ) ) ) {
		return admin_url( 'admin.php?page=cilt-rotasi' );
	}
	return $redirect_to;
}
add_filter( 'login_redirect', 'cr_login_redirect', 10, 3 );

/**
 * Üst çubukta "Merhaba" yerine karşılama.
 *
 * @param WP_Admin_Bar $bar Çubuk.
 */
function cr_howdy( $bar ) {
	if ( ! cr_opt( 'adm_howdy' ) ) {
		return;
	}
	$node = $bar->get_node( 'my-account' );
	if ( ! $node ) {
		return;
	}
	$node->title = preg_replace( '/^[^<]*/', '♥ ' . esc_html( cr_opt( 'adm_greeting' ) ) . ' · ', $node->title );
	$bar->add_node( (array) $node );
}
add_action( 'admin_bar_menu', 'cr_howdy', 10000 );
