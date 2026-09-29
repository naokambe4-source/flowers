<?php
/**
 * Ürün detay düzeni: galeri (görsel + video) | özet, ardından tam genişlik açıklama, SSS, benzer ürünler.
 *
 * @package DerinFlowers
 * @version 3.6.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

do_action( 'woocommerce_before_single_product' );

if ( post_password_required() ) {
	echo get_the_password_form(); // phpcs:ignore
	return;
}
?>
<div id="product-<?php the_ID(); ?>" <?php wc_product_class( 'df-single', $product ); ?>>
	<div class="df-container df-single__top">
		<?php woocommerce_breadcrumb(); ?>
		<div class="df-single__grid">
			<div class="df-single__gallery">
				<?php do_action( 'woocommerce_before_single_product_summary' ); ?>
			</div>
			<div class="df-single__summary summary entry-summary">
				<div class="df-single__sticky">
					<?php do_action( 'woocommerce_single_product_summary' ); ?>
				</div>
			</div>
		</div>
	</div>

	<?php do_action( 'woocommerce_after_single_product_summary' ); ?>

	<?php if ( $product->is_purchasable() && $product->is_in_stock() ) : ?>
		<div class="df-single__bar" data-df-sticky-bar aria-hidden="true">
			<div class="df-single__bar-info">
				<span class="df-single__bar-name"><?php echo esc_html( $product->get_name() ); ?></span>
				<span class="df-single__bar-price"><?php echo wp_kses_post( $product->get_price_html() ); ?></span>
			</div>
			<button type="button" class="df-btn df-btn--solid" data-df-scroll-cart tabindex="-1"><span>Sepete Ekle</span></button>
		</div>
	<?php endif; ?>
</div>

<?php do_action( 'woocommerce_after_single_product' ); ?>
