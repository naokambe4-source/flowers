<?php
/**
 * Yazı listelerine SEO skoru ve okunma sütunları; sayfalar listesinde "Canlı düzenle" bağlantısı.
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sütunları ekle.
 *
 * @param array $cols Sütunlar.
 * @return array
 */
function cr_list_columns( $cols ) {
	$new = array();
	foreach ( $cols as $k => $v ) {
		$new[ $k ] = $v;
		if ( 'title' === $k ) {
			$new['cr_seo']   = 'SEO';
			$new['cr_views'] = 'Okunma';
		}
	}
	return $new;
}

/**
 * Sütun içeriği.
 *
 * @param string $col Sütun.
 * @param int    $id  Yazı.
 */
function cr_list_column_value( $col, $id ) {
	if ( 'cr_seo' === $col ) {
		if ( 'publish' !== get_post_status( $id ) || cr_audit_skip( $id ) ) {
			echo '<span class="cr-score is-none">—</span>';
			return;
		}
		$a    = cr_seo_audit( $id );
		$miss = array();
		foreach ( $a['checks'] as $c ) {
			if ( ! $c['ok'] ) {
				$miss[] = $c['group'] . ': ' . $c['label'];
			}
		}
		echo '<span class="cr-score ' . esc_attr( cr_score_class( $a['score'] ) ) . '" title="' . esc_attr( $miss ? implode( "\n", $miss ) : 'Kusursuz' ) . '">' . (int) $a['score'] . '</span>';
	}
	if ( 'cr_views' === $col ) {
		echo esc_html( number_format_i18n( cr_views( $id ) ) );
	}
}

foreach ( array( 'post', 'page', 'icerik', 'urun_rehberi' ) as $cr_pt ) {
	add_filter( 'manage_' . $cr_pt . '_posts_columns', 'cr_list_columns' );
	add_action( 'manage_' . $cr_pt . '_posts_custom_column', 'cr_list_column_value', 10, 2 );
	add_filter(
		'manage_edit-' . $cr_pt . '_sortable_columns',
		function ( $cols ) {
			$cols['cr_views'] = 'cr_views';
			return $cols;
		}
	);
}

/**
 * Okunmaya göre sıralama.
 *
 * @param WP_Query $q Sorgu.
 */
function cr_admin_sort_views( $q ) {
	if ( is_admin() && $q->is_main_query() && 'cr_views' === $q->get( 'orderby' ) ) {
		$q->set( 'meta_key', '_cr_views' );
		$q->set( 'orderby', 'meta_value_num' );
	}
}
add_action( 'pre_get_posts', 'cr_admin_sort_views' );

/**
 * Satır eylemleri: ön yüzde canlı düzenle.
 *
 * @param array   $actions Eylemler.
 * @param WP_Post $post    Yazı.
 * @return array
 */
function cr_row_actions( $actions, $post ) {
	if ( 'publish' === $post->post_status && current_user_can( 'edit_theme_options' ) ) {
		$actions['cr_live'] = '<a href="' . esc_url( add_query_arg( 'cr_edit', '1', get_permalink( $post ) ) ) . '">✎ Canlı düzenle</a>';
	}
	if ( 'publish' === $post->post_status && cr_opt( 'geo_markdown' ) && 'page' !== $post->post_type ) {
		$actions['cr_md'] = '<a href="' . esc_url( add_query_arg( 'format', 'md', get_permalink( $post ) ) ) . '" target="_blank">Markdown</a>';
	}
	return $actions;
}
add_filter( 'page_row_actions', 'cr_row_actions', 10, 2 );
add_filter( 'post_row_actions', 'cr_row_actions', 10, 2 );
