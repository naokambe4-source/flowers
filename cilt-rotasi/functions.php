<?php
/**
 * Cilt Rotası — tema başlangıç dosyası.
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;

define( 'CR_VERSION', '1.1.0' );
define( 'CR_DIR', get_template_directory() );
define( 'CR_URI', get_template_directory_uri() );
define( 'CR_OPTION', 'cilt_rotasi_options' );

require CR_DIR . '/inc/icons.php';
require CR_DIR . '/inc/options-schema.php';
require CR_DIR . '/inc/helpers.php';
require CR_DIR . '/inc/setup.php';
require CR_DIR . '/inc/post-types.php';
require CR_DIR . '/inc/nav.php';
require CR_DIR . '/inc/meta.php';
require CR_DIR . '/inc/seo.php';
require CR_DIR . '/inc/schema.php';
require CR_DIR . '/inc/geo.php';
require CR_DIR . '/inc/search.php';
require CR_DIR . '/inc/newsletter.php';
require CR_DIR . '/inc/shortcodes.php';
require CR_DIR . '/inc/live-editor.php';
require CR_DIR . '/inc/login.php';

if ( is_admin() ) {
	require CR_DIR . '/inc/admin/fields.php';
	require CR_DIR . '/inc/admin/panel.php';
	require CR_DIR . '/inc/admin/dashboard.php';
	require CR_DIR . '/inc/admin/meta-boxes.php';
	require CR_DIR . '/inc/admin/seo-columns.php';
	require CR_DIR . '/inc/admin/tools.php';
	require CR_DIR . '/inc/admin/demo.php';
	require CR_DIR . '/inc/admin/skin.php';
}
