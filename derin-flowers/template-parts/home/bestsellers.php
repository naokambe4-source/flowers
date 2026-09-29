<?php
/**
 * Ana sayfa — En Çok Sevilenler (WooCommerce ürünleri).
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

if ( ! df_wc() ) {
	return;
}

$exclude  = df_opt( 'best_exclude_sig' ) && ! empty( $GLOBALS['df_signature_ids'] ) ? $GLOBALS['df_signature_ids'] : array();
$products = df_query_products(
	df_opt( 'best_source', 'bestsellers' ),
	array(
		'ids'     => df_opt( 'best_products', array() ),
		'cat'     => df_opt( 'best_cat' ),
		'limit'   => absint( df_opt( 'best_count', 4 ) ),
		'exclude' => $exclude,
	)
);
if ( ! $products ) {
	return;
}
?>
<section class="df-section df-bestsellers" aria-labelledby="df-best-title">
	<div class="df-container">
		<?php
		df_section_head(
			array(
				'title'     => df_opt( 'best_title' ),
				'sub'       => df_opt( 'best_sub' ),
				'align'     => 'split',
				'link'      => df_opt( 'best_url' ),
				'link_text' => df_opt( 'best_btn' ),
				'id'        => 'df-best-title',
				'keys'      => array( 'title' => 'best_title', 'sub' => 'best_sub' ),
			)
		);
		?>
		<div class="df-grid df-grid--4 df-products-row">
			<?php foreach ( $products as $product ) : ?>
				<?php df_product_card( $product ); ?>
			<?php endforeach; ?>
		</div>
	</div>
</section>
