<?php
/**
 * Yazı kartı (editoryal). Varyantlar: journal (4:5), wide (16:10), lead (yarı yarıya büyük), row (yatay küçük).
 *
 * Argümanlar: variant, heading (h2/h3), eager (bool).
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;

$variant = isset( $args['variant'] ) ? $args['variant'] : 'journal';
$legacy  = array( 'large' => 'lead', 'tall' => 'journal', 'small' => 'journal', 'plain' => 'journal' );
$variant = isset( $legacy[ $variant ] ) ? $legacy[ $variant ] : $variant;
$heading = isset( $args['heading'] ) ? $args['heading'] : 'h3';
$eager   = ! empty( $args['eager'] );
$pid     = get_the_ID();
$sizes   = array(
	'journal' => 'cr-card',
	'wide'    => 'cr-wide',
	'lead'    => 'cr-hero',
	'row'     => 'cr-thumb',
);
$img_attr = array(
	'class'   => 'cr-card__img cr-zoom',
	'loading' => $eager ? 'eager' : 'lazy',
	'sizes'   => 'lead' === $variant ? '(max-width: 900px) 100vw, 50vw' : '(max-width: 768px) 100vw, 33vw',
);
if ( $eager ) {
	$img_attr['fetchpriority'] = 'high';
}
$terms = array();
foreach ( array( 'category', 'icerik_grubu', 'urun_turu' ) as $tx ) {
	$tt = get_the_terms( $pid, $tx );
	if ( $tt && ! is_wp_error( $tt ) ) {
		foreach ( $tt as $t ) {
			$terms[] = $t->slug;
		}
	}
}
$badge = wp_strip_all_tags( cr_term_badge( $pid ) );
?>
<article class="cr-card cr-card--<?php echo esc_attr( $variant ); ?>" data-cats="<?php echo esc_attr( implode( ' ', $terms ) ); ?>">
	<a class="cr-card__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
		<?php echo cr_post_image( $pid, isset( $sizes[ $variant ] ) ? $sizes[ $variant ] : 'cr-card', $img_attr ); // phpcs:ignore ?>
	</a>
	<div class="cr-card__body">
		<p class="cr-card__meta">
			<?php if ( $badge ) : ?>
				<span class="cr-card__cat"><?php echo esc_html( $badge ); ?></span>
				<span class="cr-card__dot" aria-hidden="true">•</span>
			<?php endif; ?>
			<span class="cr-card__time"><?php echo esc_html( cr_reading_time( $pid ) ); ?> dk okuma</span>
		</p>
		<<?php echo esc_attr( $heading ); ?> class="cr-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></<?php echo esc_attr( $heading ); ?>>
		<?php if ( 'row' !== $variant ) : ?>
			<p class="cr-card__excerpt"><?php echo esc_html( cr_excerpt( $pid, 'lead' === $variant ? 34 : 18 ) ); ?></p>
		<?php endif; ?>
		<?php if ( 'lead' === $variant ) : ?>
			<a class="cr-btn-line" href="<?php the_permalink(); ?>" tabindex="-1">Rehberi Oku</a>
		<?php endif; ?>
	</div>
	<?php echo cr_save_button( $pid, 'cr-card__save' ); // phpcs:ignore ?>
</article>
