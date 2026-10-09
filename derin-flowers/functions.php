<?php
/**
 * Derin Flowers — tema başlangıç dosyası.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

define( 'DF_VERSION', '1.11.0' );
define( 'DF_DIR', get_template_directory() );
define( 'DF_URI', get_template_directory_uri() );
define( 'DF_OPTION', 'derin_options' );
define( 'DF_OPTION_DRAFT', 'derin_options_draft' );

require DF_DIR . '/inc/icons.php';
require DF_DIR . '/inc/options-schema.php';
require DF_DIR . '/inc/modules-schema.php';
require DF_DIR . '/inc/helpers.php';
require DF_DIR . '/inc/setup.php';
require DF_DIR . '/inc/nav.php';
require DF_DIR . '/inc/term-meta.php';
require DF_DIR . '/inc/newsletter.php';
require DF_DIR . '/inc/wishlist.php';
require DF_DIR . '/inc/ajax.php';
require DF_DIR . '/inc/shortcodes.php';
require DF_DIR . '/inc/live-editor.php';
require DF_DIR . '/inc/login.php';
require DF_DIR . '/inc/performance.php';
require DF_DIR . '/inc/maintenance.php';
require DF_DIR . '/inc/local-seo.php';

if ( is_admin() ) {
	require DF_DIR . '/inc/admin/fields.php';
	require DF_DIR . '/inc/admin/panel.php';
	require DF_DIR . '/inc/admin/tools.php';
	require DF_DIR . '/inc/admin/studio.php';
	require DF_DIR . '/inc/admin/skin.php';
}

if ( class_exists( 'WooCommerce' ) ) {
	require DF_DIR . '/inc/woocommerce/setup.php';
	require DF_DIR . '/inc/woocommerce/product-meta.php';
	require DF_DIR . '/inc/woocommerce/single.php';
	require DF_DIR . '/inc/woocommerce/delivery.php';
	require DF_DIR . '/inc/woocommerce/checkout.php';
	require DF_DIR . '/inc/woocommerce/quick-order.php';
	require DF_DIR . '/inc/woocommerce/gifts-ai.php';
	require DF_DIR . '/inc/woocommerce/order-status.php';
	require DF_DIR . '/inc/woocommerce/tracking.php';
	require DF_DIR . '/inc/woocommerce/account.php';
	require DF_DIR . '/inc/woocommerce/product-tools.php';
	require DF_DIR . '/inc/woocommerce/product-ai.php';
	require DF_DIR . '/inc/woocommerce/operations.php';
	require DF_DIR . '/inc/woocommerce/archive.php';
	require DF_DIR . '/inc/woocommerce/courier.php';
	require DF_DIR . '/inc/woocommerce/integrations.php';
	require DF_DIR . '/inc/woocommerce/print-slip.php';
	if ( is_admin() ) {
		require DF_DIR . '/inc/admin/dashboard.php';
		require DF_DIR . '/inc/admin/order-box.php';
	}
}
