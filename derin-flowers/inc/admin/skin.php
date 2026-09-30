<?php
/**
 * Yönetim ekranı görünümü: koyu menü, marka başlığı, sade üst çubuk.
 * Derin Flowers → Yönetim & Giriş → "Yönetim ekranında Derin Flowers görünümü" ile kapatılabilir.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Görünüm açık mı?
 *
 * @return bool
 */
function df_skin_on() {
	return (bool) df_opt( 'admin_skin', 1 );
}

/**
 * Stil dosyası.
 */
function df_skin_assets() {
	if ( df_skin_on() ) {
		wp_enqueue_style( 'df-skin', DF_URI . '/assets/admin/skin.css', array(), DF_VERSION );
	}
}
add_action( 'admin_enqueue_scripts', 'df_skin_assets', 5 );

/**
 * Menünün üstüne marka başlığı.
 */
function df_skin_brand() {
	if ( ! df_skin_on() ) {
		return;
	}
	$home = df_wc() && current_user_can( 'edit_shop_orders' ) ? admin_url( 'admin.php?page=df-dashboard' ) : admin_url();
	?>
	<script>
	( function () {
		var menu = document.getElementById( 'adminmenuwrap' );
		if ( ! menu || document.querySelector( '.df-skin-brand' ) ) {
			return;
		}
		var a = document.createElement( 'a' );
		a.className = 'df-skin-brand';
		a.href = <?php echo wp_json_encode( $home ); ?>;
		a.innerHTML = <?php echo wp_json_encode( '<span class="df-skin-brand__logo">' . df_icon( 'bouquet', array( 'size' => 22 ) ) . '</span><span class="df-skin-brand__text"><strong>' . esc_html( df_opt( 'logo_text', 'DERİN FLOWERS' ) ) . '</strong><small>Yönetim</small></span>' ); ?>;
		menu.insertBefore( a, menu.firstChild );
	}() );
	</script>
	<?php
}
add_action( 'adminmenu', 'df_skin_brand' );

/**
 * Üst çubuk: WordPress logosu yerine sade görünüm; stüdyo kısayolu.
 *
 * @param WP_Admin_Bar $bar Çubuk.
 */
function df_skin_admin_bar( $bar ) {
	if ( ! df_skin_on() ) {
		return;
	}
	$bar->remove_node( 'wp-logo' );
	if ( is_admin() && current_user_can( 'edit_theme_options' ) ) {
		$bar->add_node(
			array(
				'id'    => 'df-studio',
				'title' => '<span class="ab-icon dashicons dashicons-art" style="top:2px"></span>Tasarım Stüdyosu',
				'href'  => admin_url( 'admin.php?page=df-studio' ),
			)
		);
	}
}
add_action( 'admin_bar_menu', 'df_skin_admin_bar', 99 );

/**
 * Alt bilgi metni.
 *
 * @return string
 */
function df_skin_footer_text() {
	return esc_html( df_opt( 'logo_text', 'Derin Flowers' ) ) . ' Yönetim · Tema v' . esc_html( DF_VERSION );
}
add_filter( 'admin_footer_text', 'df_skin_footer_text' );
