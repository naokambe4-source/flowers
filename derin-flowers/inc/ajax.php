<?php
/**
 * AJAX uç noktaları: canlı ürün araması ve sepet parçaları.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Canlı arama.
 */
function df_ajax_live_search() {
	check_ajax_referer( 'df_nonce', 'nonce' );
	$term = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
	if ( mb_strlen( $term ) < 2 || ! df_wc() ) {
		wp_send_json_success( array( 'items' => array() ) );
	}
	$query = new WP_Query(
		array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			's'              => $term,
			'posts_per_page' => 6,
			'no_found_rows'  => false,
			'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				array(
					'taxonomy' => 'product_visibility',
					'field'    => 'name',
					'terms'    => array( 'exclude-from-search' ),
					'operator' => 'NOT IN',
				),
			),
		)
	);
	$items = array();
	foreach ( $query->posts as $post ) {
		$product = wc_get_product( $post );
		if ( ! $product ) {
			continue;
		}
		$items[] = array(
			'title' => $product->get_name(),
			'url'   => $product->get_permalink(),
			'price' => wp_strip_all_tags( wc_price( wc_get_price_to_display( $product ) ) ),
			'image' => $product->get_image_id() ? wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' ) : wc_placeholder_img_src( 'thumbnail' ),
		);
	}
	wp_send_json_success(
		array(
			'items' => $items,
			'total' => (int) $query->found_posts,
			'all'   => add_query_arg(
				array(
					's'         => rawurlencode( $term ),
					'post_type' => 'product',
				),
				home_url( '/' )
			),
		)
	);
}
add_action( 'wp_ajax_df_live_search', 'df_ajax_live_search' );
add_action( 'wp_ajax_nopriv_df_live_search', 'df_ajax_live_search' );

/**
 * Sepet sayacı parçası.
 *
 * @param array $fragments Parçalar.
 * @return array
 */
function df_cart_fragments( $fragments ) {
	$count                              = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
	$fragments['span.df-cart-count']    = '<span class="df-cart-count' . ( $count ? '' : ' is-empty' ) . '">' . (int) $count . '</span>';
	$fragments['span.df-drawer-count']  = '<span class="df-drawer-count">(' . (int) $count . ')</span>';
	return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'df_cart_fragments' );
