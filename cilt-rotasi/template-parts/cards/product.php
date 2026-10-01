<?php
/**
 * Ürün kartı (katalog). Argümanlar: post_id ya da title/url/image/tag/text/type; edit (canlı düzenleyici yolu).
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;

$pid = isset( $args['post_id'] ) ? (int) $args['post_id'] : 0;
if ( $pid ) {
	$tt     = get_the_terms( $pid, 'urun_turu' );
	$type   = ( $tt && ! is_wp_error( $tt ) ) ? $tt[0] : null;
	$card   = array(
		'title'  => get_the_title( $pid ),
		'url'    => get_permalink( $pid ),
		'tag'    => $type ? $type->name : 'Ürün rehberi',
		'type'   => $type ? $type->slug : '',
		'text'   => cr_excerpt( $pid, 14 ),
		'brand'  => get_post_meta( $pid, '_cr_brand', true ),
		'rating' => get_post_meta( $pid, '_cr_rating', true ),
		'price'  => get_post_meta( $pid, '_cr_price', true ),
	);
} else {
	$card = wp_parse_args(
		$args,
		array( 'title' => '', 'url' => '', 'tag' => '', 'type' => '', 'text' => '', 'image' => '', 'brand' => '', 'rating' => '', 'price' => '' )
	);
}
$edit = isset( $args['edit'] ) ? $args['edit'] : '';
?>
<article class="cr-product" data-type="<?php echo esc_attr( $card['type'] ); ?>">
	<a class="cr-product__media" href="<?php echo esc_url( cr_url( $card['url'] ) ); ?>" tabindex="-1" aria-hidden="true"<?php echo $edit ? cr_edit_img( $edit . '.image' ) : ''; // phpcs:ignore ?>>
		<?php
		if ( $pid ) {
			echo cr_post_image( $pid, 'cr-card', array( 'class' => 'cr-zoom', 'alt' => '', 'sizes' => '(max-width: 640px) 50vw, 25vw' ) ); // phpcs:ignore
		} else {
			echo cr_img( $card['image'], 'cr-card', array( 'class' => 'cr-zoom', 'alt' => '', 'sizes' => '(max-width: 640px) 50vw, 25vw' ) ); // phpcs:ignore
		}
		?>
		<?php if ( $card['rating'] ) : ?>
			<span class="cr-product__score"><?php echo cr_icon( 'star', 12 ); // phpcs:ignore ?><?php echo esc_html( str_replace( '.', ',', $card['rating'] ) ); ?></span>
		<?php endif; ?>
	</a>
	<div class="cr-product__body">
		<?php if ( $card['tag'] ) : ?>
			<span class="cr-product__tag"<?php echo $edit ? cr_edit( $edit . '.tag' ) : ''; // phpcs:ignore ?>><?php echo esc_html( $card['tag'] ); ?></span>
		<?php endif; ?>
		<h3 class="cr-product__title"><a href="<?php echo esc_url( cr_url( $card['url'] ) ); ?>"<?php echo $edit ? cr_edit( $edit . '.title' ) : ''; // phpcs:ignore ?>><?php echo esc_html( $card['title'] ); ?></a></h3>
		<?php if ( $card['brand'] ) : ?>
			<span class="cr-product__brand"><?php echo esc_html( $card['brand'] ); ?><?php echo $card['price'] ? ' · ' . esc_html( str_repeat( '₺', (int) $card['price'] ) ) : ''; ?></span>
		<?php endif; ?>
		<?php if ( $card['text'] ) : ?>
			<p class="cr-product__text"<?php echo $edit ? cr_edit( $edit . '.text' ) : ''; // phpcs:ignore ?>><?php echo esc_html( $card['text'] ); ?></p>
		<?php endif; ?>
	</div>
	<?php if ( $pid ) : ?>
		<?php echo cr_save_button( $pid, 'cr-card__save' ); // phpcs:ignore ?>
	<?php endif; ?>
</article>
