<?php
/**
 * Canlı arama (REST): Makaleler, İçerikler, Problemler, Ürün rehberleri. Arama kayıtları (içerik fırsatları).
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST rotası.
 */
function cr_search_routes() {
	register_rest_route(
		'cilt-rotasi/v1',
		'/search',
		array(
			'methods'             => 'GET',
			'permission_callback' => '__return_true',
			'callback'            => 'cr_search_rest',
			'args'                => array(
				'q'    => array( 'sanitize_callback' => 'sanitize_text_field' ),
				'type' => array( 'sanitize_callback' => 'sanitize_key' ),
				'log'  => array( 'sanitize_callback' => 'absint' ),
			),
		)
	);
}
add_action( 'rest_api_init', 'cr_search_routes' );

/**
 * Arama yanıtı.
 *
 * @param WP_REST_Request $req İstek.
 * @return WP_REST_Response
 */
function cr_search_rest( $req ) {
	$q = trim( (string) $req['q'] );
	if ( ( function_exists( 'mb_strlen' ) ? mb_strlen( $q ) : strlen( $q ) ) < 2 ) {
		return new WP_REST_Response( array( 'groups' => array() ), 200 );
	}
	$only   = (string) $req['type'];
	$groups = array();
	$types  = array(
		'post'         => 'Makaleler',
		'icerik'       => 'İçerikler',
		'urun_rehberi' => 'Ürün rehberleri',
	);
	$total = 0;
	foreach ( $types as $type => $label ) {
		if ( $only && $only !== $type ) {
			continue;
		}
		$query = new WP_Query(
			array(
				'post_type'      => $type,
				's'              => $q,
				'posts_per_page' => 'icerik' === $only ? 8 : 5,
				'no_found_rows'  => true,
				'post_status'    => 'publish',
			)
		);
		$items = array();
		foreach ( $query->posts as $p ) {
			$meta = '';
			if ( 'icerik' === $type ) {
				$meta = (string) get_post_meta( $p->ID, '_cr_function', true );
			} elseif ( 'post' === $type ) {
				$t    = cr_primary_term( $p->ID );
				$meta = ( $t ? $t->name . ' · ' : '' ) . cr_reading_time( $p->ID ) . ' dk';
			} else {
				$meta = (string) get_post_meta( $p->ID, '_cr_brand', true );
			}
			$items[] = array(
				'title' => html_entity_decode( wp_strip_all_tags( get_the_title( $p ) ), ENT_QUOTES, 'UTF-8' ),
				'url'   => get_permalink( $p ),
				'img'   => cr_post_image_url( $p->ID, 'cr-thumb' ),
				'meta'  => $meta,
			);
		}
		if ( $items ) {
			$groups[] = array( 'key' => $type, 'label' => $label, 'items' => $items );
			$total   += count( $items );
		}
	}
	if ( ! $only ) {
		$terms = get_terms(
			array(
				'taxonomy'   => array( 'cilt_sorunu', 'cilt_tipi' ),
				'name__like' => $q,
				'hide_empty' => false,
				'number'     => 5,
			)
		);
		if ( $terms && ! is_wp_error( $terms ) ) {
			$items = array();
			foreach ( $terms as $t ) {
				$items[] = array(
					'title' => $t->name,
					'url'   => get_term_link( $t ),
					'img'   => cr_img_url( cr_term_image( $t ), 'cr-thumb' ),
					'meta'  => $t->count . ' rehber',
				);
			}
			array_splice( $groups, 1, 0, array( array( 'key' => 'problem', 'label' => 'Problemler', 'items' => $items ) ) );
			$total += count( $items );
		}
	}
	if ( $req['log'] ) {
		cr_log_search( $q, $total );
	}
	return new WP_REST_Response(
		array(
			'groups' => $groups,
			'all'    => add_query_arg( 's', rawurlencode( $q ), home_url( '/' ) ),
		),
		200
	);
}

/**
 * Aramayı kaydeder.
 *
 * @param string $q     Sorgu.
 * @param int    $count Sonuç sayısı.
 */
function cr_log_search( $q, $count ) {
	if ( ! cr_opt( 'search_log' ) || cr_can_edit() ) {
		return;
	}
	$key = cr_lower( trim( $q ) );
	if ( '' === $key || strlen( $key ) > 80 ) {
		return;
	}
	$log = get_option( 'cr_search_log', array() );
	if ( ! is_array( $log ) ) {
		$log = array();
	}
	if ( ! isset( $log[ $key ] ) ) {
		$log[ $key ] = array( 'n' => 0, 'r' => 0, 't' => 0 );
	}
	$log[ $key ]['n']++;
	$log[ $key ]['r'] = (int) $count;
	$log[ $key ]['t'] = time();
	if ( count( $log ) > 600 ) {
		uasort(
			$log,
			function ( $a, $b ) {
				return $b['n'] <=> $a['n'];
			}
		);
		$log = array_slice( $log, 0, 500, true );
	}
	update_option( 'cr_search_log', $log, false );
}

/**
 * Klasik arama sayfası da kaydedilsin.
 */
function cr_log_classic_search() {
	if ( is_search() && ! is_paged() ) {
		global $wp_query;
		cr_log_search( get_search_query( false ), (int) $wp_query->found_posts );
	}
}
add_action( 'template_redirect', 'cr_log_classic_search' );
