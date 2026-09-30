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

/* -------------------------------------------------------------------------
 * Sabit, düzenli sol menü: her ekranda aynı sıra ve adlar. "Sade menü" müşteriye
 * gerekmeyen WordPress öğelerini gizler; alttaki bağlantıyla tüm menü açılır.
 * ---------------------------------------------------------------------- */

/**
 * Bu kullanıcı için sade menü mü?
 *
 * @return bool
 */
function df_menu_simple() {
	return df_skin_on() && df_opt( 'admin_simple_menu', 1 ) && ! get_user_meta( get_current_user_id(), 'df_full_menu', true );
}

/**
 * Siparişler menü bağlantısı (HPOS / klasik).
 *
 * @return string
 */
function df_menu_orders_slug() {
	$hpos = class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
	return $hpos ? 'admin.php?page=wc-orders' : 'edit.php?post_type=shop_order';
}

/**
 * Menü düzeni.
 */
function df_menu_build() {
	global $menu;
	if ( ! df_skin_on() ) {
		return;
	}
	if ( df_wc() ) {
		$count = (int) wc_orders_count( 'processing' );
		add_menu_page( 'Siparişler', 'Siparişler' . ( $count ? ' <span class="awaiting-mod">' . $count . '</span>' : '' ), 'edit_shop_orders', df_menu_orders_slug(), '', 'dashicons-clipboard', 2.1 );
	}
	if ( current_user_can( 'edit_theme_options' ) ) {
		add_submenu_page( 'derin-flowers', 'Menüler', 'Menüler', 'edit_theme_options', 'nav-menus.php' );
	}
	$rename = array(
		'derin-flowers' => 'Site Ayarları',
		'edit.php'      => 'Blog',
		'upload.php'    => 'Görseller',
		'users.php'     => 'Kullanıcılar',
	);
	foreach ( (array) $menu as $i => $item ) {
		if ( isset( $item[2], $rename[ $item[2] ] ) ) {
			$menu[ $i ][0] = $rename[ $item[2] ]; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}
	}
	if ( df_menu_simple() ) {
		$hide = array( 'edit-comments.php', 'tools.php', 'plugins.php', 'options-general.php', 'themes.php', 'woocommerce-marketing' );
		if ( df_wc() && df_opt( 'admin_replace_dashboard', 1 ) ) {
			$hide[] = 'index.php';
		}
		foreach ( (array) $menu as $item ) {
			if ( empty( $item[2] ) ) {
				continue;
			}
			if ( in_array( $item[2], $hide, true ) || false !== strpos( $item[2], 'path=/analytics' ) || false !== strpos( $item[2], 'wc-settings&tab=checkout' ) ) {
				remove_menu_page( $item[2] );
			}
		}
	}
	$label = df_menu_simple() ? 'Tüm menüyü göster' : 'Sade menüye dön';
	add_menu_page( $label, $label, 'read', 'admin-post.php?action=df_toggle_menu&_wpnonce=' . wp_create_nonce( 'df_toggle_menu' ), '', df_menu_simple() ? 'dashicons-visibility' : 'dashicons-hidden', 9999 );
}
add_action( 'admin_menu', 'df_menu_build', 9999 );

/**
 * Menü sırası.
 *
 * @param array $order Sıra.
 * @return array
 */
function df_menu_order( $order ) {
	if ( ! df_skin_on() || ! is_array( $order ) ) {
		return $order;
	}
	$first = array( 'df-dashboard', df_menu_orders_slug(), 'edit.php?post_type=product', 'users.php', 'df-studio', 'derin-flowers', 'edit.php?post_type=page', 'edit.php', 'upload.php', 'separator1', 'woocommerce' );
	$top   = array();
	foreach ( $first as $slug ) {
		if ( in_array( $slug, $order, true ) ) {
			$top[] = $slug;
		}
	}
	return array_merge( $top, array_values( array_diff( $order, $top ) ) );
}
add_filter( 'custom_menu_order', '__return_true' );
add_filter( 'menu_order', 'df_menu_order', 99 );

/**
 * Sipariş ekranlarında "Siparişler" menüsü seçili görünsün.
 *
 * @param string $parent Üst menü.
 * @return string
 */
function df_menu_parent_file( $parent ) {
	global $submenu_file;
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( df_skin_on() && df_wc() && $screen && ( 'edit-shop_order' === $screen->id || 'shop_order' === $screen->id || 'woocommerce_page_wc-orders' === $screen->id ) ) {
		$submenu_file = null; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		return df_menu_orders_slug();
	}
	return $parent;
}
add_filter( 'parent_file', 'df_menu_parent_file', 99 );

/**
 * Sade / tüm menü değiştir.
 */
function df_menu_toggle() {
	check_admin_referer( 'df_toggle_menu' );
	$uid = get_current_user_id();
	if ( get_user_meta( $uid, 'df_full_menu', true ) ) {
		delete_user_meta( $uid, 'df_full_menu' );
	} else {
		update_user_meta( $uid, 'df_full_menu', 1 );
	}
	wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url() );
	exit;
}
add_action( 'admin_post_df_toggle_menu', 'df_menu_toggle' );

/**
 * Tasarım Stüdyosu içindeki editör penceresinde yönetim menüsü gizlenir.
 */
function df_skin_in_modal() {
	?>
	<script>try { if ( window.self !== window.top && window.top.document.getElementById( 'dfs' ) ) { document.documentElement.className += ' df-in-modal'; } } catch ( e ) {}</script>
	<style>.df-in-modal #adminmenumain, .df-in-modal #wpadminbar, .df-in-modal #wpfooter { display: none !important; } .df-in-modal #wpcontent, .df-in-modal #wpfooter { margin-left: 0 !important; } html.df-in-modal.wp-toolbar { padding-top: 0 !important; } .df-in-modal .interface-interface-skeleton { top: 0 !important; left: 0 !important; }</style>
	<?php
}
add_action( 'admin_head', 'df_skin_in_modal', 1 );
