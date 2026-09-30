<?php
/**
 * Yazı kartı. Varyantlar: large, tall, wide, small, row, plain.
 *
 * Argümanlar: variant, heading (h2/h3), eager (bool).
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;

$variant = isset( $args['variant'] ) ? $args['variant'] : 'plain';
$heading = isset( $args['heading'] ) ? $args['heading'] : 'h3';
$eager   = ! empty( $args['eager'] );
$pid     = get_the_ID();
$sizes   = array(
	'large' => 'cr-wide',
	'tall'  => 'cr-card',
	'wide'  => 'cr-wide',
	'small' => 'cr-card',
	'row'   => 'cr-thumb',
	'plain' => 'cr-card',
);
$img_attr = array(
	'class'   => 'cr-card__img',
	'loading' => $eager ? 'eager' : 'lazy',
	'sizes'   => 'large' === $variant || 'wide' === $variant ? '(max-width: 768px) 100vw, 60vw' : '(max-width: 768px) 100vw, 30vw',
);
$terms = array();
foreach ( array( 'category', 'icerik_grubu', 'urun_turu' ) as $tx ) {
	$tt = get_the_terms( $pid, $tx );
	if ( $tt && ! is_wp_error( $tt ) ) {
		foreach ( $tt as $t ) {
			$terms[] = $t->slug;
		}
	}
}
?>
<article class="cr-card cr-card--<?php echo esc_attr( $variant ); ?>" data-cats="<?php echo esc_attr( implode( ' ', $terms ) ); ?>">
	<a class="cr-card__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
		<?php echo cr_post_image( $pid, $sizes[ $variant ], $img_attr ); // phpcs:ignore ?>
	</a>
	<div class="cr-card__body">
		<div class="cr-card__meta">
			<?php echo cr_term_badge( $pid ); // phpcs:ignore ?>
			<span class="cr-card__time"><?php echo cr_icon( 'clock', 14 ); // phpcs:ignore ?><?php echo esc_html( cr_reading_time( $pid ) ); ?> dk</span>
		</div>
		<<?php echo esc_attr( $heading ); ?> class="cr-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></<?php echo esc_attr( $heading ); ?>>
		<?php if ( in_array( $variant, array( 'large', 'wide' ), true ) ) : ?>
			<p class="cr-card__excerpt"><?php echo esc_html( cr_excerpt( $pid, 'large' === $variant ? 28 : 20 ) ); ?></p>
		<?php endif; ?>
	</div>
	<?php echo cr_save_button( $pid, 'cr-card__save' ); // phpcs:ignore ?>
</article>
