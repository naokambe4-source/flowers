<?php
/**
 * Ürün kartı.
 *
 * @package DerinFlowers
 *
 * @var array $args product, tag, loading.
 */

defined( 'ABSPATH' ) || exit;

/** @var WC_Product $product */
$product = $args['product'];
$tag     = in_array( $args['tag'], array( 'li', 'div', 'article' ), true ) ? $args['tag'] : 'div';
$link    = $product->get_permalink();
$name    = $product->get_name();
$cat     = df_opt( 'card_show_cat' ) ? df_product_primary_cat( $product ) : null;
$gallery = $product->get_gallery_image_ids();
$hover   = ( df_opt( 'card_hover_image' ) && $gallery ) ? (int) $gallery[0] : 0;
$img_id  = $product->get_image_id();
$classes = array( 'df-card' );
if ( $hover ) {
	$classes[] = 'has-hover';
}
if ( ! $product->is_in_stock() ) {
	$classes[] = 'is-out';
}
$subtitle = get_post_meta( $product->get_id(), '_df_subtitle', true );
// Vitrinde kırpılmamış görsel: kare/dikey fotoğraflar kesilmeden sığar.
$img_size = ( ! empty( $args['variant'] ) && 'vitrin' === $args['variant'] && '4-5' !== df_opt( 'vit_ratio', '1-1' ) ) ? 'medium_large' : 'df-card';
?>
<<?php echo esc_attr( $tag ); ?> <?php wc_product_class( $classes, $product ); ?>>
	<div class="df-card__media">
		<a class="df-card__img" href="<?php echo esc_url( $link ); ?>" tabindex="-1" aria-hidden="true">
			<?php
			if ( $img_id ) {
				echo wp_get_attachment_image(
					$img_id,
					$img_size,
					false,
					array(
						'class'    => 'df-card__primary',
						'loading'  => $args['loading'],
						'decoding' => 'async',
						'sizes'    => '(max-width: 600px) 50vw, (max-width: 1100px) 33vw, 25vw',
						'alt'      => $name,
					)
				);
			} else {
				echo df_placeholder(); // phpcs:ignore
			}
			if ( $hover ) {
				echo wp_get_attachment_image(
					$hover,
					$img_size,
					false,
					array(
						'class'    => 'df-card__secondary',
						'loading'  => 'lazy',
						'decoding' => 'async',
						'sizes'    => '(max-width: 600px) 50vw, (max-width: 1100px) 33vw, 25vw',
						'alt'      => '',
					)
				);
			}
			?>
		</a>

		<div class="df-card__badges">
			<?php if ( ! $product->is_in_stock() ) : ?>
				<span class="df-badge df-badge--muted">Tükendi</span>
			<?php elseif ( $product->is_on_sale() ) : ?>
				<?php echo apply_filters( 'woocommerce_sale_flash', '', get_post( $product->get_id() ), $product ); // phpcs:ignore ?>
			<?php elseif ( df_product_is_new( $product ) ) : ?>
				<span class="df-badge">Yeni</span>
			<?php elseif ( $product->is_featured() ) : ?>
				<span class="df-badge df-badge--light">Signature</span>
			<?php endif; ?>
			<?php if ( get_post_meta( $product->get_id(), '_df_video_id', true ) || get_post_meta( $product->get_id(), '_df_video_url', true ) ) : ?>
				<span class="df-badge df-badge--icon" title="Videolu ürün"><?php df_the_icon( 'play', array( 'size' => 12 ) ); ?></span>
			<?php endif; ?>
		</div>

		<?php echo df_wishlist_button( $product->get_id(), 'df-card__fav' ); // phpcs:ignore ?>

		<div class="df-card__action">
			<?php if ( df_quick_add_allowed( $product ) ) : ?>
				<a href="<?php echo esc_url( $product->add_to_cart_url() ); ?>"
					data-quantity="1"
					data-product_id="<?php echo esc_attr( $product->get_id() ); ?>"
					data-product_sku="<?php echo esc_attr( $product->get_sku() ); ?>"
					class="df-card__btn add_to_cart_button ajax_add_to_cart product_type_simple"
					aria-label="<?php echo esc_attr( sprintf( '%s sepete ekle', $name ) ); ?>"
					rel="nofollow"><?php df_the_icon( 'bag', array( 'size' => 18 ) ); ?><span>Sepete Ekle</span></a>
			<?php else : ?>
				<a href="<?php echo esc_url( $link ); ?>" class="df-card__btn" aria-label="<?php echo esc_attr( sprintf( '%s ürününü incele', $name ) ); ?>"><span><?php echo esc_html( df_opt( 'card_btn_text', 'İncele' ) ); ?></span><?php df_the_icon( 'arrow-right', array( 'size' => 16 ) ); ?></a>
			<?php endif; ?>
		</div>
	</div>

	<div class="df-card__body">
		<?php if ( $cat ) : ?>
			<a class="df-card__cat" href="<?php echo esc_url( get_term_link( $cat ) ); ?>"><?php echo esc_html( $cat->name ); ?></a>
		<?php endif; ?>
		<h3 class="df-card__title"><a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $name ); ?></a></h3>
		<?php if ( $subtitle ) : ?>
			<p class="df-card__sub"><?php echo esc_html( $subtitle ); ?></p>
		<?php endif; ?>
		<?php if ( df_opt( 'card_show_sku' ) && $product->get_sku() ) : ?>
			<span class="df-card__sku"><?php echo esc_html( $product->get_sku() ); ?></span>
		<?php endif; ?>
		<div class="df-card__foot">
			<div class="df-card__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></div>
			<?php if ( ! empty( $args['variant'] ) && 'vitrin' === $args['variant'] ) : ?>
				<a href="<?php echo esc_url( $link ); ?>" class="df-card__view<?php echo 'outline' === df_opt( 'card_view_style' ) ? ' df-card__view--outline' : ''; ?>" aria-label="<?php echo esc_attr( sprintf( '%s ürününü incele', $name ) ); ?>"><?php echo esc_html( df_opt( 'card_btn_text', 'İncele' ) ); ?><?php df_the_icon( 'arrow-right', array( 'size' => 14 ) ); ?></a>
			<?php elseif ( df_opt( 'card_cart_icon' ) ) : ?>
				<?php if ( df_quick_add_allowed( $product ) ) : ?>
					<a href="<?php echo esc_url( $product->add_to_cart_url() ); ?>" data-quantity="1" data-product_id="<?php echo esc_attr( $product->get_id() ); ?>" class="df-card__cart add_to_cart_button ajax_add_to_cart" aria-label="<?php echo esc_attr( sprintf( '%s sepete ekle', $name ) ); ?>" rel="nofollow"><?php df_the_icon( 'bag', array( 'size' => 18 ) ); ?></a>
				<?php else : ?>
					<a href="<?php echo esc_url( $link ); ?>" class="df-card__cart" aria-label="<?php echo esc_attr( sprintf( '%s ürününü incele', $name ) ); ?>"><?php df_the_icon( 'bag', array( 'size' => 18 ) ); ?></a>
				<?php endif; ?>
			<?php endif; ?>
		</div>
	</div>
</<?php echo esc_attr( $tag ); ?>>
