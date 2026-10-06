<?php
/**
 * Bakım modu: ziyaretçiler bakım sayfası görür (503), yöneticiler siteyi normal görür.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Bakım sayfası.
 */
function df_maintenance() {
	if ( ! df_opt( 'maint_on', 0 ) || is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || current_user_can( 'edit_theme_options' ) || current_user_can( 'edit_shop_orders' ) ) {
		return;
	}
	// Kurye ekranı ve robots/site haritası açık kalır.
	if ( isset( $_GET['df_kurye'] ) || is_robots() ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}
	status_header( 503 );
	header( 'Retry-After: 3600' );
	nocache_headers();
	$logo  = absint( df_opt( 'logo_image' ) );
	$phone = (string) df_opt( 'contact_phone1' );
	$wa    = df_whatsapp_url();
	?>
	<!doctype html>
	<html <?php language_attributes(); ?>><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex">
	<title><?php echo esc_html( df_opt( 'maint_title', 'Kısa bir aradayız' ) . ' — ' . get_bloginfo( 'name' ) ); ?></title>
	<style>
		body{margin:0;min-height:100vh;display:grid;place-items:center;background:<?php echo esc_attr( df_opt( 'color_ivory', '#FAF7F2' ) ); ?>;color:<?php echo esc_attr( df_opt( 'color_text', '#2B2522' ) ); ?>;font:16px/1.6 Georgia,serif;text-align:center;padding:24px;box-sizing:border-box}
		.box{max-width:520px}.logo img{max-width:220px;height:auto}.logo b{font-size:26px;letter-spacing:.2em}
		h1{font-weight:400;font-size:clamp(28px,6vw,42px);margin:28px 0 10px}p{color:#7a6e68;margin:0 0 26px}
		a.btn{display:inline-block;margin:4px;padding:12px 22px;border:1px solid currentColor;color:inherit;text-decoration:none;font:600 14px/1 system-ui,sans-serif;letter-spacing:.06em}
		a.btn--wa{background:#25d366;border-color:#25d366;color:#fff}
	</style></head>
	<body><div class="box">
		<div class="logo"><?php echo $logo ? wp_get_attachment_image( $logo, 'medium' ) : '<b>' . esc_html( df_opt( 'logo_text', get_bloginfo( 'name' ) ) ) . '</b>'; ?></div>
		<h1><?php echo esc_html( df_opt( 'maint_title', 'Kısa bir aradayız' ) ); ?></h1>
		<p><?php echo esc_html( df_opt( 'maint_text' ) ); ?></p>
		<?php if ( $phone ) : ?><a class="btn" href="<?php echo esc_url( df_tel( $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a><?php endif; ?>
		<?php if ( $wa ) : ?><a class="btn btn--wa" href="<?php echo esc_url( $wa ); ?>">WhatsApp</a><?php endif; ?>
	</div></body></html>
	<?php
	exit;
}
add_action( 'template_redirect', 'df_maintenance', 0 );

/**
 * Yönetici araç çubuğunda uyarı.
 *
 * @param WP_Admin_Bar $bar Çubuk.
 */
function df_maintenance_bar( $bar ) {
	if ( df_opt( 'maint_on', 0 ) && current_user_can( 'edit_theme_options' ) ) {
		$bar->add_node(
			array(
				'id'    => 'df-maint',
				'title' => '<span style="background:#a2574a;color:#fff;padding:2px 8px;border-radius:3px">BAKIM MODU AÇIK</span>',
				'href'  => admin_url( 'admin.php?page=derin-flowers#admin' ),
			)
		);
	}
}
add_action( 'admin_bar_menu', 'df_maintenance_bar', 5 );
