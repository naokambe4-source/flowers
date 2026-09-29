<?php
/**
 * Plugin Name:       Can Eloksal Builder
 * Plugin URI:        https://caneloksal.com
 * Description:       Canlı Editör (sayfada tıkla-düzenle: metin, görsel, renk, boşluk, yazı, cihaz bazlı stil, bölüm sıralama) ve sürükle-bırak bloklar. Can Eloksal temasının tüm bölümlerini (hero, hizmetler, sektörler, proses, CTA, galeri, blog, formlar, banka hesapları…) blok editöründe sürükle-bırak bloklar olarak kullanmanızı sağlar. Hazır sayfa düzenleri ve ana sayfayı düzenlenebilir bloklara aktarma aracı içerir.
 * Version:           2.0.0
 * Requires at least: 6.2
 * Requires PHP:      8.0
 * Author:            Can Eloksal
 * License:           GPL-2.0-or-later
 * Text Domain:       can-eloksal-builder
 *
 * @package CanEloksalBuilder
 */

defined( 'ABSPATH' ) || exit;

define( 'CEB_VERSION', '2.0.0' );
define( 'CEB_DIR', plugin_dir_path( __FILE__ ) );
define( 'CEB_URL', plugin_dir_url( __FILE__ ) );

/**
 * Can Eloksal teması etkin mi? (Bloklar temanın bileşenlerini ve stillerini kullanır.)
 *
 * @return bool
 */
function ceb_theme_ready() {
	return function_exists( 'ce_opt' ) && function_exists( 'ce_hopt' );
}

add_action(
	'after_setup_theme',
	static function () {
		if ( ! ceb_theme_ready() ) {
			add_action( 'admin_notices', 'ceb_theme_notice' );
			return;
		}
		require CEB_DIR . 'includes/blocks.php';
		require CEB_DIR . 'includes/patterns.php';
		require CEB_DIR . 'includes/converter.php';
		require CEB_DIR . 'includes/live/live.php';
		if ( is_admin() ) {
			require CEB_DIR . 'includes/admin.php';
		}
	},
	30
);

/**
 * Tema eksik uyarısı.
 */
function ceb_theme_notice() {
	if ( current_user_can( 'activate_plugins' ) ) {
		echo '<div class="notice notice-warning"><p><strong>Can Eloksal Builder</strong> yalnızca <strong>Can Eloksal</strong> teması etkinken çalışır. Görünüm → Temalar\'dan temayı etkinleştirin.</p></div>';
	}
}
