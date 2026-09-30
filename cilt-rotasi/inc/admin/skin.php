<?php
/**
 * Yönetim paneli görünümü: marka teması, alt bilgi, sade menü.
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Tüm yönetime Cilt Rotası görünümü sınıfı.
 *
 * @param string $classes Sınıflar.
 * @return string
 */
function cr_admin_body_class( $classes ) {
	if ( cr_opt( 'adm_skin' ) ) {
		$classes .= ' cr-skin';
	}
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( $screen && false !== strpos( (string) $screen->id, 'cilt-rotasi' ) ) {
		$classes .= ' cr-page';
	}
	return $classes;
}
add_filter( 'admin_body_class', 'cr_admin_body_class' );

/**
 * Yönetim alt bilgisi.
 *
 * @return string
 */
function cr_admin_footer_text() {
	return '<span class="cr-foot-love">♥ ' . esc_html( cr_opt( 'adm_greeting' ) ) . ' — Cilt Rotası</span>';
}
add_filter( 'admin_footer_text', 'cr_admin_footer_text' );

/**
 * Sade menü: günlük kullanımda gerekmeyen öğeleri yönetici olmayanlardan gizler.
 */
function cr_clean_menu() {
	if ( ! cr_opt( 'adm_clean_menu' ) ) {
		return;
	}
	remove_menu_page( 'edit-comments.php' );
	if ( ! current_user_can( 'manage_options' ) ) {
		remove_menu_page( 'tools.php' );
		remove_menu_page( 'themes.php' );
		remove_menu_page( 'plugins.php' );
		remove_menu_page( 'options-general.php' );
	}
}
add_action( 'admin_menu', 'cr_clean_menu', 999 );

/**
 * Menü sırası: Cilt Rotası, Başlangıç, Yazılar, Sözlük, Ürün, Ortam, Sayfalar...
 *
 * @param array $order Sıra.
 * @return array
 */
function cr_menu_order( $order ) {
	$first = array( 'cilt-rotasi', 'index.php', 'separator1', 'edit.php', 'edit.php?post_type=icerik', 'edit.php?post_type=urun_rehberi', 'upload.php', 'edit.php?post_type=page' );
	$rest  = array_diff( $order, $first );
	return array_merge( array_values( array_intersect( $first, $order ) ), array_values( $rest ) );
}
add_filter( 'custom_menu_order', '__return_true' );
add_filter( 'menu_order', 'cr_menu_order' );

/**
 * Yazılar menüsünün adı: Rehberler.
 */
function cr_rename_posts() {
	global $menu, $submenu;
	foreach ( (array) $menu as $k => $m ) {
		if ( isset( $m[2] ) && 'edit.php' === $m[2] ) {
			$menu[ $k ][0] = 'Rehberler'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
		}
	}
	if ( isset( $submenu['edit.php'][5] ) ) {
		$submenu['edit.php'][5][0] = 'Tüm rehberler'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
	}
	if ( isset( $submenu['edit.php'][10] ) ) {
		$submenu['edit.php'][10][0] = 'Yeni rehber'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
	}
}
add_action( 'admin_menu', 'cr_rename_posts', 998 );

/**
 * İlk etkinleştirmede Kurulum sihirbazı bildirimi.
 */
function cr_setup_notice() {
	if ( ! current_user_can( 'edit_theme_options' ) || get_option( 'cr_setup_notice_dismissed' ) ) {
		return;
	}
	if ( get_category_by_slug( 'cilt-problemleri' ) ) {
		update_option( 'cr_setup_notice_dismissed', 1 );
		return;
	}
	echo '<div class="notice cr-setup-notice"><p><strong>♥ Cilt Rotası hazır.</strong> Kategorileri, sayfaları, menüleri ve örnek içerikleri tek tıkla kurmak için <a class="button button-primary" href="' . esc_url( admin_url( 'admin.php?page=cilt-rotasi-araclar' ) ) . '">Kurulum sihirbazını aç</a></p></div>';
}
add_action( 'admin_notices', 'cr_setup_notice' );
