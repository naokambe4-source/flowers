<?php
/**
 * Canlı düzenleyici: ön yüzde metne tıklayıp yazma, görsel değiştirme, bölüm sıralama/gizleme,
 * renk ve yazı tipi paneli. Değişiklikler tema seçeneklerine yazılır (ham HTML değil).
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Canlı düzenleyici varlıkları.
 */
function cr_live_assets() {
	if ( ! cr_can_edit() || is_customize_preview() ) {
		return;
	}
	wp_enqueue_media();
	wp_enqueue_style( 'cr-live', CR_URI . '/assets/css/live-editor.css', array(), CR_VERSION );
	wp_enqueue_script( 'cr-live', CR_URI . '/assets/js/live-editor.js', array( 'jquery' ), CR_VERSION, true );
	$colors = array();
	foreach ( cr_schema_fields() as $id => $f ) {
		if ( 'color' === $f['type'] ) {
			$colors[] = array(
				'id'    => $id,
				'label' => $f['label'],
				'var'   => $f['var'],
				'value' => cr_opt( $id ),
			);
		}
	}
	$fonts = cr_font_choices();
	wp_localize_script(
		'cr-live',
		'CRLive',
		array(
			'ajax'     => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'cr_live' ),
			'panel'    => admin_url( 'admin.php?page=cilt-rotasi-ayarlar' ),
			'isHome'   => is_front_page() ? 1 : 0,
			'autoOpen' => isset( $_GET['cr_edit'] ) ? 1 : 0, // phpcs:ignore WordPress.Security.NonceVerification
			'colors'   => $colors,
			'fonts'    => $fonts,
			'font'     => array(
				'heading' => cr_opt( 'font_heading' ),
				'body'    => cr_opt( 'font_body' ),
			),
			'radius'   => (int) cr_opt( 'radius' ),
			'scale'    => (int) cr_opt( 'font_scale' ),
			'sections' => cr_sections(),
			'labels'   => cr_home_section_labels(),
			'editUrl'  => is_singular() ? get_edit_post_link( get_the_ID(), 'raw' ) : '',
		)
	);
}
add_action( 'wp_enqueue_scripts', 'cr_live_assets', 20 );

/**
 * Admin çubuğuna düğme.
 *
 * @param WP_Admin_Bar $bar Çubuk.
 */
function cr_live_adminbar( $bar ) {
	if ( ! cr_can_edit() ) {
		return;
	}
	if ( is_admin() ) {
		$bar->add_node(
			array(
				'id'    => 'cr-live',
				'title' => '✎ Canlı Düzenle',
				'href'  => add_query_arg( 'cr_edit', '1', home_url( '/' ) ),
			)
		);
		return;
	}
	$bar->add_node(
		array(
			'id'    => 'cr-live',
			'title' => '✎ Canlı Düzenle',
			'href'  => '#cr-live',
			'meta'  => array( 'class' => 'cr-live-toggle' ),
		)
	);
	$bar->add_node(
		array(
			'id'    => 'cr-panel',
			'title' => '♥ Cilt Rotası Paneli',
			'href'  => admin_url( 'admin.php?page=cilt-rotasi' ),
		)
	);
}
add_action( 'admin_bar_menu', 'cr_live_adminbar', 80 );

/**
 * Yüzen düğme.
 */
function cr_live_fab() {
	if ( ! cr_can_edit() || ! cr_opt( 'live_edit_button' ) ) {
		return;
	}
	echo '<button type="button" class="cr-live-fab cr-live-toggle" aria-label="Canlı düzenleyiciyi aç">' . cr_icon( 'edit', 18 ) . '<span>Düzenle</span></button>'; // phpcs:ignore
}
add_action( 'wp_footer', 'cr_live_fab', 5 );

/**
 * Metin değerini şemaya göre temizler.
 *
 * @param string $type  Tip.
 * @param mixed  $value Değer.
 * @return mixed
 */
function cr_live_clean( $type, $value ) {
	switch ( $type ) {
		case 'url':
			return esc_url_raw( $value );
		case 'image':
			return is_numeric( $value ) ? absint( $value ) : esc_url_raw( $value );
		case 'color':
			$c = sanitize_hex_color( $value );
			return $c ? $c : '';
		case 'number':
			return (int) $value;
		case 'textarea':
		case 'lines':
			return sanitize_textarea_field( $value );
		default:
			return sanitize_text_field( $value );
	}
}

/**
 * Kaydet (AJAX).
 */
function cr_live_save() {
	check_ajax_referer( 'cr_live', 'nonce' );
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_send_json_error( array( 'message' => 'Yetkiniz yok.' ), 403 );
	}
	$raw  = isset( $_POST['changes'] ) ? json_decode( wp_unslash( $_POST['changes'] ), true ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$raw  = is_array( $raw ) ? $raw : array();
	$opts = get_option( CR_OPTION, array() );
	$opts = is_array( $opts ) ? $opts : array();
	cr_snapshot( 'Canlı düzenleyici' );
	$opts   = array_merge( cr_defaults(), $opts );
	$fields = cr_schema_fields();
	$count  = 0;

	$values = array();
	if ( ! empty( $raw['texts'] ) && is_array( $raw['texts'] ) ) {
		$values = $raw['texts'];
	}
	if ( ! empty( $raw['images'] ) && is_array( $raw['images'] ) ) {
		$values = array_merge( $values, $raw['images'] );
	}
	if ( ! empty( $raw['design'] ) && is_array( $raw['design'] ) ) {
		$values = array_merge( $values, $raw['design'] );
	}

	foreach ( $values as $path => $value ) {
		$parts = explode( '.', (string) $path );
		$key   = $parts[0];
		if ( ! isset( $fields[ $key ] ) ) {
			continue;
		}
		$field = $fields[ $key ];
		if ( 1 === count( $parts ) ) {
			if ( 'select' === $field['type'] ) {
				if ( isset( $field['choices'] ) && is_array( $field['choices'] ) && array_key_exists( $value, $field['choices'] ) ) {
					$opts[ $key ] = $value;
					$count++;
				}
				continue;
			}
			if ( in_array( $field['type'], array( 'repeater', 'sections', 'code' ), true ) ) {
				continue;
			}
			$opts[ $key ] = cr_live_clean( $field['type'], $value );
			if ( 'number' === $field['type'] ) {
				$min          = isset( $field['min'] ) ? $field['min'] : PHP_INT_MIN;
				$max          = isset( $field['max'] ) ? $field['max'] : PHP_INT_MAX;
				$opts[ $key ] = max( $min, min( $max, $opts[ $key ] ) );
			}
			$count++;
			continue;
		}
		if ( 'repeater' === $field['type'] && 3 === count( $parts ) ) {
			$idx = (int) $parts[1];
			$sub = $parts[2];
			if ( ! isset( $field['fields'][ $sub ] ) || ! isset( $opts[ $key ][ $idx ] ) || ! is_array( $opts[ $key ][ $idx ] ) ) {
				continue;
			}
			$opts[ $key ][ $idx ][ $sub ] = cr_live_clean( $field['fields'][ $sub ]['type'], $value );
			$count++;
		}
	}

	if ( isset( $raw['sections'] ) && is_array( $raw['sections'] ) ) {
		$labels = cr_home_section_labels();
		$secs   = array();
		foreach ( $raw['sections'] as $s ) {
			if ( isset( $s['id'], $labels[ $s['id'] ] ) ) {
				$secs[] = array( 'id' => $s['id'], 'on' => empty( $s['on'] ) ? 0 : 1 );
			}
		}
		if ( $secs ) {
			$opts['sections'] = $secs;
			$count++;
		}
	}

	update_option( CR_OPTION, $opts );
	cr_flush_options_cache();
	cr_purge_caches();
	wp_send_json_success( array( 'message' => $count . ' değişiklik kaydedildi.' ) );
}
add_action( 'wp_ajax_cr_live_save', 'cr_live_save' );

/**
 * Ayarların anlık görüntüsünü alır (geri yükleme için, son 15).
 *
 * @param string $label Açıklama.
 */
function cr_snapshot( $label ) {
	$current = get_option( CR_OPTION, array() );
	if ( ! $current ) {
		return;
	}
	$snaps = get_option( 'cr_snapshots', array() );
	$snaps = is_array( $snaps ) ? $snaps : array();
	array_unshift(
		$snaps,
		array(
			't'    => time(),
			'l'    => $label,
			'u'    => get_current_user_id(),
			'data' => $current,
		)
	);
	update_option( 'cr_snapshots', array_slice( $snaps, 0, 15 ), false );
}

/**
 * Yaygın önbellek eklentilerini temizler.
 */
function cr_purge_caches() {
	do_action( 'litespeed_purge_all' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
	if ( function_exists( 'rocket_clean_domain' ) ) {
		rocket_clean_domain();
	}
	if ( function_exists( 'w3tc_flush_all' ) ) {
		w3tc_flush_all();
	}
	if ( function_exists( 'wp_cache_clear_cache' ) ) {
		wp_cache_clear_cache();
	}
	delete_transient( 'cr_llms_txt' );
}
