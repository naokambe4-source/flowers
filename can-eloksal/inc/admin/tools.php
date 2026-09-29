<?php
/**
 * Kurulum & Araçlar: başlangıç kurulumu, kalıcı bağlantı yenileme, özel hata sayfaları,
 * ayar dışa/içe aktarma ve sistem kontrolü.
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sunucu gereksinim kontrolleri.
 *
 * @return array<int,array{0:string,1:bool,2:string}>
 */
function ce_system_checks() {
	global $wpdb;
	$uploads = wp_upload_dir( null, false );
	return array(
		array( 'PHP 8.0+', version_compare( PHP_VERSION, '8.0', '>=' ), PHP_VERSION ),
		array( 'WordPress 6.2+', version_compare( get_bloginfo( 'version' ), '6.2', '>=' ), get_bloginfo( 'version' ) ),
		array( 'Veritabanı', true, $wpdb->db_server_info() ),
		array( 'fileinfo eklentisi (dosya MIME kontrolü)', function_exists( 'finfo_open' ), function_exists( 'finfo_open' ) ? 'var' : 'yok — teklif formunda dosya yükleme reddedilir' ),
		array( 'openssl eklentisi (SMTP şifre şifreleme)', function_exists( 'openssl_encrypt' ), function_exists( 'openssl_encrypt' ) ? 'var' : 'yok' ),
		array( 'GD / Imagick (görsel boyutlandırma)', wp_image_editor_supports(), wp_image_editor_supports() ? 'var' : 'yok' ),
		array( 'WebP üretimi', wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ), '' ),
		array( 'mbstring', function_exists( 'mb_strlen' ), '' ),
		array( 'Uploads yazılabilir', wp_is_writable( $uploads['basedir'] ), $uploads['basedir'] ),
		array( 'Kalıcı bağlantılar', (bool) get_option( 'permalink_structure' ), (string) get_option( 'permalink_structure' ) ),
		array( 'HTTPS', is_ssl(), is_ssl() ? '' : 'Canlı sitede SSL önerilir' ),
		array( 'Hata gösterimi kapalı (WP_DEBUG_DISPLAY)', ! ( defined( 'WP_DEBUG' ) && WP_DEBUG && ( ! defined( 'WP_DEBUG_DISPLAY' ) || WP_DEBUG_DISPLAY ) ), 'Canlıda WP_DEBUG_DISPLAY false olmalı' ),
	);
}

/**
 * Araçlar ekranı.
 */
function ce_render_tools_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Yetkiniz yok.' );
	}
	echo '<div class="wrap ce-admin">';
	ce_admin_header( 'Kurulum & Araçlar', 'Başlangıç içerikleri, bakım işlemleri ve sistem kontrolü.' );

	$log = get_transient( 'ce_tools_log_' . get_current_user_id() );
	if ( $log ) {
		delete_transient( 'ce_tools_log_' . get_current_user_id() );
		echo '<div class="ce-toast ce-toast--success" role="status"><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span><div><strong>İşlem tamamlandı.</strong><ul class="ce-log">';
		foreach ( (array) $log as $line ) {
			echo '<li>' . esc_html( $line ) . '</li>';
		}
		echo '</ul></div></div>';
	}

	$form = static function ( $action, $label, $class = 'button button-primary', $confirm = '' ) {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"' . ( $confirm ? ' data-confirm="' . esc_attr( $confirm ) . '"' : '' ) . '>';
		wp_nonce_field( 'ce_tool_' . $action );
		echo '<input type="hidden" name="action" value="ce_tool"><input type="hidden" name="tool" value="' . esc_attr( $action ) . '"><button class="' . esc_attr( $class ) . '">' . esc_html( $label ) . '</button></form>';
	};

	echo '<div class="ce-dash__grid">';

	echo '<section class="ce-card"><header class="ce-card__head"><h2>1. Başlangıç kurulumu</h2></header><div class="ce-card__body">';
	echo '<p>Tek tıkla oluşturulur: <strong>9 hizmet</strong> ve kategorileri, <strong>4 sektör</strong>, hero slaytı, <strong>2 banka hesabı</strong>, Hakkımızda / Kalite Politikamız / Galeri / Blog / Banka Hesapları / İletişim / Teklif Al / KVKK / Gizlilik / Çerez sayfaları, <strong>8 teknik blog yazısı</strong>, galeri kategorileri ve menüler. Statik ana sayfa ve <code>/blog/%postname%/</code> kalıcı bağlantısı ayarlanır.</p><p>Var olan kayıtlar değiştirilmez; tekrar çalıştırmak yalnızca eksikleri ekler. Sahte referans, sertifika veya rakam oluşturulmaz. Görseller Ortam Kütüphanesi\'nden sizin tarafınızdan eklenir.</p>';
	$form( 'setup', get_option( 'ce_setup_done' ) ? 'Eksikleri tamamla' : 'Kurulumu çalıştır' );
	echo '</div></section>';

	echo '<section class="ce-card"><header class="ce-card__head"><h2>Bakım</h2></header><div class="ce-card__body">';
	echo '<p><strong>Kalıcı bağlantıları yenile:</strong> 404 veren hizmet/blog adreslerinde kullanın.</p>';
	$form( 'flush', 'Kalıcı bağlantıları yenile', 'button' );
	echo '<hr><p><strong>Özel hata sayfaları:</strong> Veritabanı bağlantı hatası ve PHP ölümcül hata (500) durumunda markalı sayfa gösterilmesi için <code>wp-content/db-error.php</code> ve <code>wp-content/php-error.php</code> dosyalarını kurar.</p>';
	$form( 'error_pages', 'Hata sayfalarını kur', 'button' );
	echo '</div></section>';

	echo '<section class="ce-card"><header class="ce-card__head"><h2>Ayarları dışa / içe aktar</h2></header><div class="ce-card__body">';
	echo '<p>Tema Ayarları\'nı JSON olarak yedekleyin veya başka bir kurulumdan aktarın. (SMTP şifresi güvenlik nedeniyle dışa aktarılmaz.)</p>';
	echo '<p><a class="button" href="' . esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=ce_export_settings' ), 'ce_export_settings' ) ) . '">JSON indir</a></p>';
	echo '<form method="post" enctype="multipart/form-data" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" data-confirm="Mevcut tema ayarlarının üzerine yazılacak. Devam edilsin mi?">';
	wp_nonce_field( 'ce_import_settings' );
	echo '<input type="hidden" name="action" value="ce_import_settings"><label class="screen-reader-text" for="ce-import">JSON dosyası</label><input id="ce-import" type="file" name="settings" accept="application/json,.json" required> <button class="button">İçe aktar</button></form>';
	echo '</div></section>';

	echo '<section class="ce-card"><header class="ce-card__head"><h2>Sistem kontrolü</h2></header><ul class="ce-checks">';
	foreach ( ce_system_checks() as $c ) {
		printf( '<li class="%3$s"><span class="dashicons %4$s" aria-hidden="true"></span>%1$s <small>%2$s</small></li>', esc_html( $c[0] ), esc_html( $c[2] ), $c[1] ? 'is-ok' : 'is-warn', $c[1] ? 'dashicons-yes-alt' : 'dashicons-warning' );
	}
	echo '</ul></section>';

	echo '</div></div>';
}

add_action( 'admin_post_ce_tool', 'ce_handle_tool' );
/**
 * Araç işlemleri.
 */
function ce_handle_tool() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Yetkiniz yok.', 403 );
	}
	$tool = isset( $_POST['tool'] ) ? sanitize_key( wp_unslash( $_POST['tool'] ) ) : '';
	check_admin_referer( 'ce_tool_' . $tool );
	$log = array();
	switch ( $tool ) {
		case 'setup':
			ce_install_roles();
			ce_audit_install();
			$log = ce_seed_all();
			break;
		case 'flush':
			ce_register_post_types();
			ce_sitemap_rewrite();
			flush_rewrite_rules();
			$log[] = 'Kalıcı bağlantılar yenilendi.';
			break;
		case 'error_pages':
			foreach ( array( 'db-error.php', 'php-error.php' ) as $file ) {
				$ok    = copy( CE_DIR . '/extras/' . $file, WP_CONTENT_DIR . '/' . $file );
				$log[] = ( $ok ? '✓ ' : '✗ ' ) . 'wp-content/' . $file . ( $ok ? ' kuruldu.' : ' kopyalanamadı (klasör izinlerini kontrol edin).' );
			}
			break;
	}
	set_transient( 'ce_tools_log_' . get_current_user_id(), $log, MINUTE_IN_SECONDS * 5 );
	wp_safe_redirect( admin_url( 'admin.php?page=ce-tools' ) );
	exit;
}

add_action( 'admin_post_ce_export_settings', 'ce_export_settings' );
/**
 * Ayarları JSON olarak indirir.
 */
function ce_export_settings() {
	check_admin_referer( 'ce_export_settings' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Yetkiniz yok.', 403 );
	}
	$options = get_option( CE_OPTION, array() );
	unset( $options['smtp_password'] );
	ce_audit( 'export', 'settings', 0, 'Tema ayarları JSON' );
	nocache_headers();
	header( 'Content-Type: application/json; charset=UTF-8' );
	header( 'Content-Disposition: attachment; filename="can-eloksal-ayarlar-' . gmdate( 'Y-m-d' ) . '.json"' );
	echo wp_json_encode( array( 'theme' => 'can-eloksal', 'version' => CE_VERSION, 'options' => $options ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ); // phpcs:ignore
	exit;
}

add_action( 'admin_post_ce_import_settings', 'ce_import_settings' );
/**
 * JSON ayarlarını şemaya göre temizleyerek içe aktarır.
 */
function ce_import_settings() {
	check_admin_referer( 'ce_import_settings' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Yetkiniz yok.', 403 );
	}
	$log = array();
	if ( empty( $_FILES['settings']['tmp_name'] ) || ! is_uploaded_file( $_FILES['settings']['tmp_name'] ) ) { // phpcs:ignore
		$log[] = '✗ Dosya yüklenemedi.';
	} else {
		$data = json_decode( (string) file_get_contents( $_FILES['settings']['tmp_name'] ), true ); // phpcs:ignore
		if ( ! is_array( $data ) || ( $data['theme'] ?? '' ) !== 'can-eloksal' || ! is_array( $data['options'] ?? null ) ) {
			$log[] = '✗ Geçersiz ayar dosyası.';
		} else {
			$current = get_option( CE_OPTION, array() );
			$current = is_array( $current ) ? $current : array();
			foreach ( ce_options_schema() as $tab ) {
				foreach ( $tab['sections'] as $section ) {
					foreach ( $section['fields'] as $field ) {
						if ( in_array( $field['type'], array( 'heading', 'password' ), true ) || ! array_key_exists( $field['id'], $data['options'] ) ) {
							continue;
						}
						$current[ $field['id'] ] = ce_sanitize_field( $field, $data['options'][ $field['id'] ] );
					}
				}
			}
			update_option( CE_OPTION, $current );
			ce_audit( 'import', 'settings', 0, 'Tema ayarları JSON' );
			$log[] = '✓ Ayarlar içe aktarıldı.';
		}
	}
	set_transient( 'ce_tools_log_' . get_current_user_id(), $log, MINUTE_IN_SECONDS * 5 );
	wp_safe_redirect( admin_url( 'admin.php?page=ce-tools' ) );
	exit;
}
