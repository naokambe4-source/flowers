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
 * Tasarım Stüdyosu bağlantısı.
 *
 * @param string $url Açılacak sayfa.
 * @return string
 */
function df_studio_url( $url = '' ) {
	$link = admin_url( 'admin.php?page=df-studio' );
	return $url ? $link . '&url=' . rawurlencode( $url ) : $link;
}

/**
 * Şu anki ön yüz adresi.
 *
 * @return string
 */
function df_current_url() {
	return remove_query_arg( array( 'df_live', 'df_preview' ), df_request_url() );
}

/**
 * Admin çubuğu düğmesi.
 *
 * @param WP_Admin_Bar $bar Çubuk.
 */
function df_live_admin_bar( $bar ) {
	if ( is_admin() || ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	$bar->add_node(
		array(
			'id'    => 'df-live',
			'title' => '<span style="color:#f3c9b8">✎ Tasarım Stüdyosu</span>',
			'href'  => df_studio_url( df_current_url() ),
		)
	);
}
add_action( 'admin_bar_menu', 'df_live_admin_bar', 70 );

/**
 * Önizleme penceresinde admin çubuğu gösterilmez.
 *
 * @param bool $show Göster.
 * @return bool
 */
function df_live_hide_admin_bar( $show ) {
	return df_preview_requested() ? false : $show;
}
add_filter( 'show_admin_bar', 'df_live_hide_admin_bar', 99 );

/**
 * Admin çubuğu kapalı olsa da yöneticiye sitede küçük bir "Tasarım Stüdyosu" düğmesi.
 */
function df_live_fab() {
	if ( df_live() || ! df_opt( 'admin_fab', 1 ) || ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	printf(
		'<a class="df-studio-fab" href="%s" title="Bu sayfayı Tasarım Stüdyosu\'nda düzenle"><span aria-hidden="true">✎</span> Düzenle</a><style>.df-studio-fab{position:fixed;left:16px;bottom:16px;z-index:9990;display:inline-flex;align-items:center;gap:8px;height:44px;padding:0 18px;border-radius:999px;background:#2B2522;color:#fff!important;font:600 13px/1 system-ui,-apple-system,sans-serif;text-decoration:none;box-shadow:0 10px 30px rgba(0,0,0,.25);opacity:.92}.df-studio-fab:hover{background:#8E5E52;opacity:1}.df-studio-fab span{font-size:16px}@media print{.df-studio-fab{display:none}}</style>',
		esc_url( df_studio_url( df_current_url() ) )
	);
}
add_action( 'wp_footer', 'df_live_fab', 50 );

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
	if ( ! df_live() ) {
		return;
	}
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
			'is404'    => is_404(),
			'studio'   => df_studio_url( df_current_url() ),
			'context'  => df_live_context(),
			'origin'   => untrailingslashit( home_url() ),
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

/**
 * Önizlenen sayfanın türü ve hızlı düzenleme verileri (Tasarım Stüdyosu "Sayfa" sekmesi).
 *
 * @return array
 */
function df_live_context() {
	$ctx = array(
		'type'  => 'other',
		'title' => wp_get_document_title(),
	);
	if ( is_front_page() ) {
		$ctx['type'] = 'home';
		return $ctx;
	}
	if ( df_wc() ) {
		if ( is_cart() || is_checkout() ) {
			$ctx['type']     = 'wc';
			$ctx['settings'] = 'delivery';
			return $ctx;
		}
		if ( is_account_page() ) {
			$ctx['type']     = 'wc';
			$ctx['settings'] = 'account';
			return $ctx;
		}
		if ( is_shop() ) {
			$ctx['type']     = 'wc';
			$ctx['settings'] = 'product-0';
			return $ctx;
		}
	}
	$obj = get_queried_object();
	if ( $obj instanceof WP_Term ) {
		$thumb = 'product_cat' === $obj->taxonomy ? absint( get_term_meta( $obj->term_id, 'thumbnail_id', true ) ) : 0;
		return array(
			'type'     => 'term',
			'id'       => $obj->term_id,
			'taxonomy' => $obj->taxonomy,
			'title'    => $obj->name,
			'desc'     => $obj->description,
			'image'    => 'product_cat' === $obj->taxonomy ? $thumb : null,
			'imageUrl' => $thumb ? wp_get_attachment_image_url( $thumb, 'thumbnail' ) : '',
			'edit'     => get_edit_term_link( $obj->term_id, $obj->taxonomy ),
			'settings' => 'product_cat' === $obj->taxonomy ? 'product-0' : '',
		);
	}
	if ( $obj instanceof WP_Post ) {
		$ctx = array(
			'type'  => 'post',
			'id'    => $obj->ID,
			'title' => get_the_title( $obj ),
			'kind'  => get_post_type_object( $obj->post_type ) ? get_post_type_object( $obj->post_type )->labels->singular_name : '',
			'edit'  => get_edit_post_link( $obj->ID, 'raw' ),
		);
		$tpl = get_page_template_slug( $obj );
		if ( 'page' === $obj->post_type && ( in_array( $obj->post_name, array( 'hakkimizda', 'iletisim' ), true ) || in_array( $tpl, array( 'page-hakkimizda.php', 'page-iletisim.php' ), true ) ) ) {
			$ctx['settings'] = 'pages';
			$ctx['managed']  = 1;
		}
		if ( df_wc() && 'product' === $obj->post_type ) {
			$p = wc_get_product( $obj->ID );
			if ( $p ) {
				$img = $p->get_image_id();
				$ctx = array_merge(
					$ctx,
					array(
						'type'     => 'product',
						'variable' => $p->is_type( 'variable' ) ? 1 : 0,
						'regular'  => $p->get_regular_price( 'edit' ),
						'sale'     => $p->get_sale_price( 'edit' ),
						'short'    => $p->get_short_description( 'edit' ),
						'stock'    => $p->get_stock_status( 'edit' ),
						'image'    => $img,
						'imageUrl' => $img ? wp_get_attachment_image_url( $img, 'thumbnail' ) : '',
						'settings' => 'product',
					)
				);
			}
		}
		return $ctx;
	}
	return $ctx;
}
