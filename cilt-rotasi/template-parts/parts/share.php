<?php
/**
 * Kaydet + paylaş düğmeleri.
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;
$vertical = ! empty( $args['vertical'] );
$url      = rawurlencode( get_permalink() );
$title    = rawurlencode( wp_strip_all_tags( get_the_title() ) );
$img      = rawurlencode( cr_post_image_url( get_the_ID(), 'large' ) );
?>
<div class="cr-share<?php echo $vertical ? ' cr-share--vertical' : ''; ?>">
	<?php echo cr_save_button( get_the_ID(), 'cr-share__btn', ! $vertical ); // phpcs:ignore ?>
	<button type="button" class="cr-share__btn" data-share data-title="<?php echo esc_attr( get_the_title() ); ?>" data-url="<?php echo esc_url( get_permalink() ); ?>" aria-label="Paylaş"><?php echo cr_icon( 'share', 20 ); // phpcs:ignore ?><?php echo $vertical ? '' : '<span>Paylaş</span>'; ?></button>
	<a class="cr-share__btn" href="https://wa.me/?text=<?php echo esc_attr( $title . '%20' . $url ); ?>" target="_blank" rel="noopener" aria-label="WhatsApp ile paylaş"><?php echo cr_icon( 'whatsapp', 20 ); // phpcs:ignore ?></a>
	<a class="cr-share__btn" href="https://pinterest.com/pin/create/button/?url=<?php echo esc_attr( $url ); ?>&amp;media=<?php echo esc_attr( $img ); ?>&amp;description=<?php echo esc_attr( $title ); ?>" target="_blank" rel="noopener" aria-label="Pinterest'e kaydet"><?php echo cr_icon( 'pinterest', 20 ); // phpcs:ignore ?></a>
	<a class="cr-share__btn" href="https://x.com/intent/post?url=<?php echo esc_attr( $url ); ?>&amp;text=<?php echo esc_attr( $title ); ?>" target="_blank" rel="noopener" aria-label="X'te paylaş"><?php echo cr_icon( 'x', 18 ); // phpcs:ignore ?></a>
	<button type="button" class="cr-share__btn" data-copy="<?php echo esc_url( get_permalink() ); ?>" aria-label="Bağlantıyı kopyala"><?php echo cr_icon( 'link', 20 ); // phpcs:ignore ?></button>
</div>
