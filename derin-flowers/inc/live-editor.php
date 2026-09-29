<?php
/**
 * Canlı Düzenleyici: ön yüzde yazılara tıklayıp düzenleme, görsel değiştirme,
 * bölümleri sıralama / gizleme. Değişiklikler ham HTML olarak değil, tema ayarlarına
 * yazılır; böylece ürünler, fiyatlar ve header/footer hep canlı kalır.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Canlı düzenlenebilen sayfa mı?
 *
 * @return bool
 */
function df_live_supported_page() {
	return is_front_page() || is_page( array( 'hakkimizda', 'iletisim' ) ) || is_page_template( array( 'page-hakkimizda.php', 'page-iletisim.php' ) );
}

/**
 * Admin çubuğu düğmesi.
 *
 * @param WP_Admin_Bar $bar Çubuk.
 */
function df_live_admin_bar( $bar ) {
	if ( is_admin() || ! current_user_can( 'edit_theme_options' ) || ! df_live_supported_page() ) {
		return;
	}
	if ( df_live() ) {
		$bar->add_node(
			array(
				'id'    => 'df-live',
				'title' => '✕ Canlı düzenlemeden çık',
				'href'  => remove_query_arg( 'df_live' ),
			)
		);
		return;
	}
	$bar->add_node(
		array(
			'id'    => 'df-live',
			'title' => '<span style="color:#f3c9b8">✎ Canlı Düzenle</span>',
			'href'  => add_query_arg( 'df_live', '1' ),
		)
	);
}
add_action( 'admin_bar_menu', 'df_live_admin_bar', 70 );

/**
 * Canlı modda önbelleği kapat.
 */
function df_live_nocache() {
	if ( df_live() ) {
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		if ( ! defined( 'LSCACHE_NO_CACHE' ) ) {
			define( 'LSCACHE_NO_CACHE', true );
		}
		nocache_headers();
	}
}
add_action( 'template_redirect', 'df_live_nocache', 1 );

/**
 * Canlı mod varlıkları.
 */
function df_live_assets() {
	if ( ! df_live() || ! df_live_supported_page() ) {
		return;
	}
	wp_enqueue_media();
	wp_enqueue_style( 'df-live', DF_URI . '/assets/css/live-editor.css', array( 'df-main' ), DF_VERSION );
	wp_enqueue_script( 'df-live', DF_URI . '/assets/js/live-editor.js', array( 'jquery' ), DF_VERSION, true );
	$labels = df_home_section_labels();
	$panel  = array();
	foreach ( $labels as $id => $label ) {
		$panel[ $id ] = admin_url( 'admin.php?page=derin-flowers' . ( 'hero' === $id ? '#hero' : '#home' ) );
	}
	wp_localize_script(
		'df-live',
		'DFLive',
		array(
			'ajax'     => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'df_live_save' ),
			'labels'   => $labels,
			'panel'    => $panel,
			'panelUrl' => admin_url( 'admin.php?page=derin-flowers' ),
			'exitUrl'  => remove_query_arg( 'df_live' ),
			'isHome'   => is_front_page(),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'df_live_assets', 40 );

/**
 * Kaydet (AJAX).
 */
function df_live_save() {
	check_ajax_referer( 'df_live_save', 'nonce' );
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_send_json_error( array( 'message' => 'Yetkiniz yok.' ), 403 );
	}
	$fields   = isset( $_POST['fields'] ) ? json_decode( wp_unslash( $_POST['fields'] ), true ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- alan alan temizlenir.
	$sections = isset( $_POST['sections'] ) ? json_decode( wp_unslash( $_POST['sections'] ), true ) : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$saved    = get_option( DF_OPTION, array() );
	$opts     = array_merge( df_defaults(), is_array( $saved ) ? $saved : array() );
	$done     = 0;
	$skipped  = array();

	foreach ( (array) $fields as $path => $value ) {
		$def = df_field_def( $path );
		if ( ! $def || ! in_array( $def['type'], array( 'text', 'textarea', 'lines', 'image', 'url' ), true ) || is_array( $value ) ) {
			$skipped[] = $path;
			continue;
		}
		$clean = df_sanitize_field( $def, (string) $value );
		$parts = explode( '.', $path );
		if ( 1 === count( $parts ) ) {
			$opts[ $parts[0] ] = $clean;
		} else {
			$idx = (int) $parts[1];
			if ( ! isset( $opts[ $parts[0] ][ $idx ] ) || ! is_array( $opts[ $parts[0] ][ $idx ] ) ) {
				$skipped[] = $path;
				continue;
			}
			$opts[ $parts[0] ][ $idx ][ $parts[2] ] = $clean;
		}
		++$done;
	}

	if ( is_array( $sections ) ) {
		$labels = df_home_section_labels();
		$list   = array();
		foreach ( $sections as $row ) {
			if ( is_array( $row ) && ! empty( $row['id'] ) && isset( $labels[ $row['id'] ] ) ) {
				$list[] = array(
					'id' => sanitize_key( $row['id'] ),
					'on' => empty( $row['on'] ) ? 0 : 1,
				);
			}
		}
		if ( $list ) {
			$opts['home_sections'] = $list;
			++$done;
		}
	}

	$opts['__df_clean'] = 1;
	update_option( DF_OPTION, $opts );
	wp_send_json_success(
		array(
			'saved'   => $done,
			'skipped' => $skipped,
		)
	);
}
add_action( 'wp_ajax_df_live_save', 'df_live_save' );
