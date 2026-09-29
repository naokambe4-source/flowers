<?php
/**
 * Ana sayfa — Signature Collection (WooCommerce ürünleri, 4 büyük kart).
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

if ( ! df_wc() ) {
	return;
}

$products = df_query_products(
	df_opt( 'sig_source', 'featured' ),
	array(
		'ids'   => df_opt( 'sig_products', array() ),
		'cat'   => df_opt( 'sig_cat' ),
		'limit' => absint( df_opt( 'sig_count', 4 ) ),
	)
);
if ( ! $products ) {
	return;
}
// En Çok Sevilenler bölümünde tekrar etmemesi için.
$GLOBALS['df_signature_ids'] = array_map(
	function ( $p ) {
		return $p->get_id();
	},
	$products
);
?>
<section class="df-section df-signature" aria-labelledby="df-sig-title">
	<div class="df-container">
		<?php
		df_section_head(
			array(
				'eyebrow' => 'DERİN FLOWERS',
				'title'   => df_opt( 'sig_title' ),
				'sub'     => df_opt( 'sig_sub' ),
				'id'      => 'df-sig-title',
				'keys'    => array( 'title' => 'sig_title', 'sub' => 'sig_sub' ),
			)
		);
		?>
		<div class="df-grid df-grid--<?php echo in_array( (int) df_opt( 'sig_count', 4 ), array( 5, 10 ), true ) ? 5 : 4; ?> df-products-row">
			<?php foreach ( $products as $product ) : ?>
				<?php df_product_card( $product ); ?>
			<?php endforeach; ?>
		</div>
		<?php if ( df_opt( 'sig_btn' ) && df_opt( 'sig_url' ) ) : ?>
			<div class="df-section__more"><?php echo df_button( df_opt( 'sig_btn' ), df_opt( 'sig_url' ), 'outline' ); // phpcs:ignore ?></div>
		<?php endif; ?>
	</div>
</section>
