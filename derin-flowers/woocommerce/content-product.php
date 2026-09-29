<?php
/**
 * Liste ürün kartı — tema kartını kullanır (template-parts/product/card.php).
 *
 * @package DerinFlowers
 * @version 3.6.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( empty( $product ) || ! $product->is_visible() ) {
	return;
}
df_product_card(
	$product,
	array(
		'tag'     => 'li',
		'loading' => wc_get_loop_prop( 'loop' ) < 4 ? 'eager' : 'lazy',
	)
);
