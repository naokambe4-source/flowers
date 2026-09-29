<?php
/**
 * Can Eloksal — tema başlangıç dosyası.
 *
 * Tüm modüller inc/ klasöründe ayrı dosyalardadır; bu dosya yalnızca yükleme sırasını belirler.
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

define( 'CE_VERSION', '1.0.0' );
define( 'CE_DIR', get_template_directory() );
define( 'CE_URI', get_template_directory_uri() );
define( 'CE_OPTION', 'ce_options' );
define( 'CE_DB_VERSION', '1.0.0' );

// Çekirdek.
require CE_DIR . '/inc/icons.php';
require CE_DIR . '/inc/options-schema.php';
require CE_DIR . '/inc/helpers.php';
require CE_DIR . '/inc/fields.php';
require CE_DIR . '/inc/setup.php';
require CE_DIR . '/inc/post-types.php';
require CE_DIR . '/inc/meta-boxes.php';
require CE_DIR . '/inc/nav.php';

// Güvenlik, yetki ve kayıt.
require CE_DIR . '/inc/security.php';
require CE_DIR . '/inc/roles.php';
require CE_DIR . '/inc/audit-log.php';

// Özellikler.
require CE_DIR . '/inc/forms.php';
require CE_DIR . '/inc/form-fields.php';
require CE_DIR . '/inc/smtp.php';
require CE_DIR . '/inc/seo.php';
require CE_DIR . '/inc/sitemap.php';
require CE_DIR . '/inc/breadcrumbs.php';
require CE_DIR . '/inc/builder-compat.php';
require CE_DIR . '/inc/customizer.php';

if ( is_admin() ) {
	require CE_DIR . '/inc/admin/panel.php';
	require CE_DIR . '/inc/admin/dashboard.php';
	require CE_DIR . '/inc/admin/inquiries.php';
	require CE_DIR . '/inc/admin/gallery-bulk.php';
	require CE_DIR . '/inc/admin/audit-page.php';
	require CE_DIR . '/inc/admin/seed-content.php';
	require CE_DIR . '/inc/admin/tools.php';
}
