<?php
/**
 * Ürün galerisi: görseller + ürün videosu (dosya / YouTube / Vimeo), küçük resimler, büyütme.
 *
 * @package DerinFlowers
 * @version 9.7.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

$df_main    = $product->get_image_id();
$df_images  = array_values( array_filter( array_merge( array( $df_main ), $product->get_gallery_image_ids() ) ) );
$df_video   = function_exists( 'df_product_video' ) ? df_product_video( $product ) : null;
$df_media   = array();
foreach ( $df_images as $df_id ) {
	$df_media[] = array(
		'type' => 'image',
		'id'   => (int) $df_id,
	);
}
if ( $df_video ) {
	$df_item = array_merge( array( 'type' => 'video' ), $df_video );
	if ( 'first' === $df_video['pos'] ) {
		array_unshift( $df_media, $df_item );
	} elseif ( 'last' === $df_video['pos'] || count( $df_media ) < 2 ) {
		$df_media[] = $df_item;
	} else {
		array_splice( $df_media, 1, 0, array( $df_item ) );
	}
}
$df_count = count( $df_media );
$df_first = true;
?>
<div class="images woocommerce-product-gallery df-gallery<?php echo $df_count > 1 ? ' has-thumbs' : ''; ?>" data-df-gallery data-columns="4">
	<div class="df-gallery__main">
		<div class="df-gallery__badges">
			<?php if ( $product->is_on_sale() ) : ?>
				<?php echo apply_filters( 'woocommerce_sale_flash', '', get_post( $product->get_id() ), $product ); // phpcs:ignore ?>
			<?php endif; ?>
		</div>
		<?php echo df_wishlist_button( $product->get_id(), 'df-gallery__fav' ); // phpcs:ignore ?>

		<div class="df-gallery__track" data-df-gallery-track>
			<?php if ( ! $df_media ) : ?>
				<figure class="df-gallery__slide woocommerce-product-gallery__image--placeholder is-active">
					<?php echo wc_placeholder_img( 'woocommerce_single', array( 'class' => 'wp-post-image' ) ); // phpcs:ignore ?>
				</figure>
			<?php endif; ?>

			<?php foreach ( $df_media as $df_i => $df_item ) : ?>
				<?php if ( 'image' === $df_item['type'] ) : ?>
					<?php
					$df_full = wp_get_attachment_image_src( $df_item['id'], 'full' );
					$df_attr = array(
						'loading'  => 0 === $df_i ? 'eager' : 'lazy',
						'decoding' => 'async',
						'sizes'    => '(max-width: 900px) 100vw, 55vw',
						'alt'      => trim( wp_strip_all_tags( get_post_meta( $df_item['id'], '_wp_attachment_image_alt', true ) ) ) ? trim( wp_strip_all_tags( get_post_meta( $df_item['id'], '_wp_attachment_image_alt', true ) ) ) : $product->get_name(),
					);
					if ( $df_first ) {
						$df_attr['class']         = 'wp-post-image';
						$df_attr['fetchpriority'] = 'high';
					}
					?>
					<figure class="df-gallery__slide woocommerce-product-gallery__image<?php echo 0 === $df_i ? ' is-active' : ''; ?>" data-index="<?php echo (int) $df_i; ?>" data-thumb="<?php echo esc_url( wp_get_attachment_image_url( $df_item['id'], 'thumbnail' ) ); ?>">
						<a href="<?php echo esc_url( $df_full ? $df_full[0] : '' ); ?>" data-df-zoom data-width="<?php echo esc_attr( $df_full ? $df_full[1] : '' ); ?>" data-height="<?php echo esc_attr( $df_full ? $df_full[2] : '' ); ?>">
							<?php echo wp_get_attachment_image( $df_item['id'], 'woocommerce_single', false, $df_attr ); ?>
						</a>
					</figure>
					<?php $df_first = false; ?>
				<?php else : ?>
					<figure class="df-gallery__slide df-gallery__slide--video<?php echo 0 === $df_i ? ' is-active' : ''; ?>" data-index="<?php echo (int) $df_i; ?>">
						<?php if ( 'file' === $df_item['type'] ) : ?>
							<video class="df-gallery__video" src="<?php echo esc_url( $df_item['src'] ); ?>" playsinline preload="metadata" <?php echo $df_item['poster'] ? 'poster="' . esc_url( $df_item['poster'] ) . '"' : ''; ?> <?php echo $df_item['autoplay'] ? 'muted loop autoplay data-autoplay' : 'controls'; ?>></video>
						<?php else : ?>
							<button type="button" class="df-gallery__embed" data-df-embed="<?php echo esc_url( $df_item['embed'] ); ?>" aria-label="Ürün videosunu oynat">
								<?php if ( $df_item['poster'] ) : ?>
									<img src="<?php echo esc_url( $df_item['poster'] ); ?>" alt="" loading="lazy">
								<?php endif; ?>
								<span class="df-play"><?php df_the_icon( 'play', array( 'size' => 28 ) ); ?></span>
							</button>
						<?php endif; ?>
					</figure>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>

		<?php if ( $df_count > 1 ) : ?>
			<button type="button" class="df-gallery__arrow df-gallery__arrow--prev" data-df-gallery-prev aria-label="Önceki görsel"><?php df_the_icon( 'chevron-left', array( 'size' => 22 ) ); ?></button>
			<button type="button" class="df-gallery__arrow df-gallery__arrow--next" data-df-gallery-next aria-label="Sonraki görsel"><?php df_the_icon( 'chevron-right', array( 'size' => 22 ) ); ?></button>
			<span class="df-gallery__counter" aria-hidden="true"><span data-df-gallery-current>1</span> / <?php echo (int) $df_count; ?></span>
		<?php endif; ?>
		<?php if ( $df_media ) : ?>
			<button type="button" class="df-gallery__zoom" data-df-zoom-open aria-label="Görseli büyüt"><?php df_the_icon( 'zoom', array( 'size' => 20 ) ); ?></button>
		<?php endif; ?>
	</div>

	<?php if ( $df_count > 1 ) : ?>
		<div class="df-gallery__thumbs" role="tablist" aria-label="Ürün görselleri">
			<?php foreach ( $df_media as $df_i => $df_item ) : ?>
				<button type="button" class="df-gallery__thumb<?php echo 0 === $df_i ? ' is-active' : ''; ?><?php echo 'video' === $df_item['type'] ? ' is-video' : ''; ?>" data-df-gallery-go="<?php echo (int) $df_i; ?>" aria-label="<?php echo esc_attr( 'video' === $df_item['type'] ? 'Ürün videosu' : ( $df_i + 1 ) . '. görsel' ); ?>">
					<?php if ( 'image' === $df_item['type'] ) : ?>
						<?php echo wp_get_attachment_image( $df_item['id'], 'df-card', false, array( 'loading' => 'lazy', 'alt' => '', 'sizes' => '(max-width: 900px) 64px, 92px' ) ); ?>
					<?php else : ?>
						<?php if ( $df_item['poster'] ) : ?>
							<img src="<?php echo esc_url( $df_item['poster'] ); ?>" alt="" loading="lazy">
						<?php endif; ?>
						<span class="df-play df-play--sm"><?php df_the_icon( 'play', array( 'size' => 16 ) ); ?></span>
					<?php endif; ?>
				</button>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
