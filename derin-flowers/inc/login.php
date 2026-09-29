<?php
/**
 * Markalı yönetim girişi (wp-login.php). Derin Flowers → Yönetim & Giriş'ten kapatılabilir.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Giriş ekranı stilleri.
 */
function df_login_style() {
	if ( ! df_opt( 'admin_login_style', 1 ) ) {
		return;
	}
	$logo  = df_img_url( df_opt( 'admin_login_logo' ), 'medium' );
	$side  = df_img_url( df_opt( 'admin_login_image' ), 'df-portrait' );
	$fonts = df_fonts_url();
	if ( $fonts ) {
		wp_enqueue_style( 'df-fonts', $fonts, array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
	}
	$accent = df_opt( 'color_accent', '#8E5E52' );
	$css    = ':root{--df-a:' . $accent . ';--df-ad:' . df_opt( 'color_accent_dark', '#6F463C' ) . ';}' .
		'body.login{background:' . df_opt( 'color_ivory', '#FAF7F2' ) . ';font-family:"' . df_opt( 'font_body', 'Jost' ) . '",system-ui,sans-serif;display:flex;min-height:100vh}' .
		( $side ? 'body.login::before{content:"";flex:1 1 50%;background:url(' . esc_url_raw( $side ) . ') center/cover no-repeat}' : '' ) .
		'body.login #login{flex:0 0 auto;width:360px;margin:auto;padding:40px 40px 30px;background:#fff;border:1px solid #ece4dd;box-shadow:0 30px 60px -30px rgba(43,37,34,.25)}' .
		( $side ? 'body.login #login{margin:auto 6vw}' : '' ) .
		'.login h1 a{background:none!important;width:auto!important;height:auto!important;text-indent:0!important;font-family:"' . df_opt( 'font_heading', 'Cormorant Garamond' ) . '",serif;font-size:28px;letter-spacing:.18em;color:#2B2522;line-height:1.2;margin-bottom:6px;overflow:visible!important}' .
		( $logo ? '.login h1 a{background:url(' . esc_url_raw( $logo ) . ') center/contain no-repeat!important;height:80px!important;text-indent:-9999px!important}' : '' ) .
		'.login h1::after{content:"' . addcslashes( str_replace( array( "\r", "\n", '<', '>' ), ' ', wp_strip_all_tags( (string) df_opt( 'admin_login_text', '' ) ) ), '"\\' ) . '";display:block;margin:10px 0 18px;font:400 14px/1.4 system-ui,sans-serif;color:#7A6E68;text-align:center;letter-spacing:0}' .
		'.login form{border:0;box-shadow:none;padding:0;margin:0;background:transparent}' .
		'.login label{font-size:13px;color:#2B2522}' .
		'.login input[type=text],.login input[type=password],.login input[type=email]{border-radius:0;border-color:#e3dbd4;min-height:46px;box-shadow:none}' .
		'.login input:focus{border-color:#2B2522!important;box-shadow:0 0 0 2px rgba(43,37,34,.08)!important}' .
		'.wp-core-ui .button-primary{background:#2B2522;border-color:#2B2522;border-radius:0;min-height:44px;padding:0 22px;text-transform:uppercase;letter-spacing:.12em;font-size:12px}' .
		'.wp-core-ui .button-primary:hover,.wp-core-ui .button-primary:focus{background:var(--df-a);border-color:var(--df-a)}' .
		'.login #nav,.login #backtoblog{padding:0;text-align:center}.login #nav a,.login #backtoblog a{color:#7A6E68}' .
		'.login .message,.login .notice,.login #login_error{border-left-color:var(--df-a);box-shadow:none;background:#FAF7F2}' .
		'.login .language-switcher{display:none}' .
		'@media(max-width:800px){body.login::before{display:none}body.login #login{width:auto;margin:24px 16px;padding:28px 22px}}';
	wp_register_style( 'df-login', false, array(), DF_VERSION );
	wp_enqueue_style( 'df-login' );
	wp_add_inline_style( 'df-login', $css );
}
add_action( 'login_enqueue_scripts', 'df_login_style' );

/**
 * Logo bağlantısı → site.
 *
 * @return string
 */
function df_login_url() {
	return home_url( '/' );
}
add_filter( 'login_headerurl', 'df_login_url' );

/**
 * Logo metni.
 *
 * @return string
 */
function df_login_title() {
	return df_opt( 'logo_text', get_bloginfo( 'name' ) );
}
add_filter( 'login_headertext', 'df_login_title' );
