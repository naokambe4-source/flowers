<?php
/**
 * Yönetim menüsü ve Tema Ayarları ekranı.
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', 'ce_admin_menu', 9 );
/**
 * "Can Eloksal" menüsü.
 */
function ce_admin_menu() {
	$icon = 'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40"><path fill="none" stroke="#a7aaad" stroke-width="4" stroke-linecap="round" d="M28 12a10 10 0 1 0 0 16"/><circle cx="28.5" cy="20" r="3" fill="#a7aaad"/></svg>' ); // phpcs:ignore

	add_menu_page( 'Can Eloksal', 'Can Eloksal', 'edit_posts', 'ce-dashboard', 'ce_render_dashboard', $icon, 2 );
	add_submenu_page( 'ce-dashboard', 'Kontrol Paneli', 'Kontrol Paneli', 'edit_posts', 'ce-dashboard', 'ce_render_dashboard' );
	add_submenu_page( 'ce-dashboard', 'Tema Ayarları', 'Tema Ayarları', 'ce_manage_settings', 'ce-settings', 'ce_render_settings_page' );
	add_submenu_page( 'ce-dashboard', 'Menü Yönetimi', 'Menü Yönetimi', 'edit_theme_options', 'nav-menus.php' );
	add_submenu_page( 'ce-dashboard', 'Sayfalar', 'Sayfalar', 'edit_pages', 'edit.php?post_type=page' );
	add_submenu_page( 'ce-dashboard', 'Audit Log', 'Audit Log', 'ce_view_audit', 'ce-audit', 'ce_render_audit_page' );
	add_submenu_page( 'ce-dashboard', 'Kurulum & Araçlar', 'Kurulum & Araçlar', 'manage_options', 'ce-tools', 'ce_render_tools_page' );
}

/**
 * Yönetim sayfası üst başlığı.
 *
 * @param string $title    Başlık.
 * @param string $subtitle Alt başlık.
 * @param string $actions  Sağ taraf HTML.
 */
function ce_admin_header( $title, $subtitle = '', $actions = '' ) {
	echo '<header class="ce-admin-head">';
	echo '<div><p class="ce-admin-head__brand">Can Eloksal</p><h1>' . esc_html( $title ) . '</h1>';
	if ( $subtitle ) {
		echo '<p class="ce-admin-head__sub">' . esc_html( $subtitle ) . '</p>';
	}
	echo '</div>';
	if ( $actions ) {
		echo '<div class="ce-admin-head__actions">' . wp_kses_post( $actions ) . '</div>';
	}
	echo '</header>';
}

/**
 * Tema Ayarları ekranı.
 */
function ce_render_settings_page() {
	if ( ! current_user_can( 'ce_manage_settings' ) ) {
		wp_die( esc_html__( 'Bu sayfaya erişim yetkiniz yok.', 'can-eloksal' ) );
	}
	$schema = ce_options_schema();
	$tab    = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'brand'; // phpcs:ignore
	if ( ! isset( $schema[ $tab ] ) ) {
		$tab = 'brand';
	}
	$saved = get_option( CE_OPTION, array() );
	$saved = is_array( $saved ) ? $saved : array();
	$opts  = wp_parse_args( $saved, ce_option_defaults() );

	echo '<div class="wrap ce-admin">';
	ce_admin_header( 'Tema Ayarları', 'Marka, iletişim, ana sayfa, SEO, SMTP ve form ayarları.', '<a class="button" href="' . esc_url( home_url( '/' ) ) . '" target="_blank" rel="noopener">Siteyi görüntüle ↗</a>' );

	if ( isset( $_GET['updated'] ) ) { // phpcs:ignore
		echo '<div class="ce-toast ce-toast--success" role="status"><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span> Ayarlar kaydedildi.</div>';
	}

	echo '<div class="ce-settings">';
	echo '<nav class="ce-settings__tabs" aria-label="Ayar sekmeleri">';
	foreach ( $schema as $key => $t ) {
		printf(
			'<a href="%1$s" class="%2$s"%5$s><span class="dashicons %3$s" aria-hidden="true"></span>%4$s</a>',
			esc_url( admin_url( 'admin.php?page=ce-settings&tab=' . $key ) ),
			$key === $tab ? 'is-active' : '',
			esc_attr( $t['icon'] ),
			esc_html( $t['title'] ),
			$key === $tab ? ' aria-current="page"' : ''
		);
	}
	echo '</nav>';

	echo '<form class="ce-settings__form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	wp_nonce_field( 'ce_save_settings', 'ce_settings_nonce' );
	echo '<input type="hidden" name="action" value="ce_save_settings"><input type="hidden" name="tab" value="' . esc_attr( $tab ) . '">';
	foreach ( $schema[ $tab ]['sections'] as $si => $section ) {
		echo '<section class="ce-card ce-card--form">';
		echo '<header class="ce-card__head"><h2>' . esc_html( $section['title'] ) . '</h2>';
		if ( ! empty( $section['desc'] ) ) {
			echo '<p>' . esc_html( $section['desc'] ) . '</p>';
		}
		echo '</header><div class="ce-fields">';
		foreach ( $section['fields'] as $field ) {
			$value = 'heading' === $field['type'] ? '' : ( $opts[ $field['id'] ] ?? '' );
			ce_render_field( $field, $value, 'heading' === $field['type'] ? '' : CE_OPTION . '[' . $field['id'] . ']', 'ce-opt-' . ( $field['id'] ?? $si ) );
		}
		echo '</div></section>';
	}
	echo '<div class="ce-savebar"><button type="submit" class="button button-primary button-hero">Değişiklikleri kaydet</button></div>';
	echo '</form></div></div>';
}

add_action( 'admin_post_ce_save_settings', 'ce_save_settings' );
/**
 * Ayarları kaydeder (yalnızca gönderilen sekmenin alanları).
 */
function ce_save_settings() {
	if ( ! current_user_can( 'ce_manage_settings' ) ) {
		wp_die( esc_html__( 'Yetkiniz yok.', 'can-eloksal' ), 403 );
	}
	check_admin_referer( 'ce_save_settings', 'ce_settings_nonce' );

	$schema = ce_options_schema();
	$tab    = isset( $_POST['tab'] ) ? sanitize_key( wp_unslash( $_POST['tab'] ) ) : '';
	if ( ! isset( $schema[ $tab ] ) ) {
		wp_die( 'Geçersiz sekme.' );
	}
	$input = isset( $_POST[ CE_OPTION ] ) && is_array( $_POST[ CE_OPTION ] ) ? wp_unslash( $_POST[ CE_OPTION ] ) : array(); // phpcs:ignore -- aşağıda şemaya göre temizlenir.
	$saved = get_option( CE_OPTION, array() );
	$saved = is_array( $saved ) ? $saved : array();

	foreach ( $schema[ $tab ]['sections'] as $section ) {
		foreach ( $section['fields'] as $field ) {
			if ( 'heading' === $field['type'] ) {
				continue;
			}
			$raw                   = $input[ $field['id'] ] ?? ( 'toggle' === $field['type'] ? 0 : ( 'repeater' === $field['type'] ? array() : '' ) );
			$saved[ $field['id'] ] = ce_sanitize_field( $field, $raw, $saved[ $field['id'] ] ?? '' );
		}
	}
	update_option( CE_OPTION, $saved, true );
	$GLOBALS['ce_opt_reset'] = true;
	ce_audit( 'settings', 'settings', 0, $schema[ $tab ]['title'] );

	wp_safe_redirect( admin_url( 'admin.php?page=ce-settings&tab=' . $tab . '&updated=1' ) );
	exit;
}
