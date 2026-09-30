<?php
/**
 * En çok okunanlar: 1 büyük öne çıkan + numaralı liste.
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;

$src  = cr_opt( 'featured_source', 'popular' );
$args = array(
	'post_type'           => 'post',
	'posts_per_page'      => 4,
	'ignore_sticky_posts' => true,
	'no_found_rows'       => true,
);
if ( 'popular' === $src ) {
	$args['meta_key'] = '_cr_views'; // phpcs:ignore WordPress.DB.SlowDBQuery
	$args['orderby']  = array( 'meta_value_num' => 'DESC', 'date' => 'DESC' );
} elseif ( 'sticky' === $src ) {
	$sticky           = get_option( 'sticky_posts' );
	$args['post__in'] = $sticky ? $sticky : array( 0 );
}
$ids = get_posts( array_merge( $args, array( 'fields' => 'ids' ) ) );
if ( count( $ids ) < 4 ) {
	$more = get_posts(
		array(
			'post_type'      => 'post',
			'posts_per_page' => 4 - count( $ids ),
			'post__not_in'   => $ids ? $ids : array( 0 ),
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);
	$ids  = array_merge( $ids, $more );
}
if ( ! $ids ) {
	return;
}
$main = array_shift( $ids );
?>
<section class="cr-section cr-featured"<?php echo cr_section_attr( 'featured' ); // phpcs:ignore ?>>
	<div class="cr-container">
		<div class="cr-head cr-head--split cr-reveal">
			<div>
				<p class="cr-eyebrow"><?php echo cr_icon( 'star', 14 ); // phpcs:ignore ?> Popüler</p>
				<h2 class="cr-h2"<?php echo cr_edit( 'featured_title' ); // phpcs:ignore ?>><?php cr_t( 'featured_title' ); ?></h2>
			</div>
			<a class="cr-link" href="<?php echo esc_url( cr_url( cr_opt( 'bn_explore_url' ) ) ); ?>">Tüm rehberler <?php echo cr_icon( 'arrow-right', 16 ); // phpcs:ignore ?></a>
		</div>
		<div class="cr-featured__grid">
			<article class="cr-featured__main cr-reveal">
				<a class="cr-featured__media" href="<?php echo esc_url( get_permalink( $main ) ); ?>" tabindex="-1" aria-hidden="true">
					<?php echo cr_post_image( $main, 'cr-wide', array( 'class' => 'cr-zoom', 'sizes' => '(max-width: 900px) 100vw, 58vw' ) ); // phpcs:ignore ?>
				</a>
				<div class="cr-featured__body">
					<div class="cr-card__meta">
						<?php echo cr_term_badge( $main ); // phpcs:ignore ?>
						<span class="cr-card__time"><?php echo cr_icon( 'clock', 14 ); // phpcs:ignore ?><?php echo esc_html( cr_reading_time( $main ) ); ?> dk okuma</span>
					</div>
					<h3 class="cr-featured__title"><a href="<?php echo esc_url( get_permalink( $main ) ); ?>"><?php echo esc_html( get_the_title( $main ) ); ?></a></h3>
					<p class="cr-featured__excerpt"><?php echo esc_html( cr_excerpt( $main, 30 ) ); ?></p>
				</div>
				<?php echo cr_save_button( $main, 'cr-card__save' ); // phpcs:ignore ?>
			</article>
			<ol class="cr-featured__list">
				<?php foreach ( $ids as $n => $pid ) : ?>
					<li class="cr-reveal">
						<a class="cr-rank" href="<?php echo esc_url( get_permalink( $pid ) ); ?>">
							<span class="cr-rank__num" aria-hidden="true"><?php echo esc_html( str_pad( (string) ( $n + 2 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
							<span class="cr-rank__body">
								<span class="cr-rank__cat"><?php echo wp_kses_post( wp_strip_all_tags( cr_term_badge( $pid ) ) ); ?></span>
								<span class="cr-rank__title"><?php echo esc_html( get_the_title( $pid ) ); ?></span>
								<span class="cr-rank__time"><?php echo esc_html( cr_reading_time( $pid ) ); ?> dk okuma</span>
							</span>
							<span class="cr-rank__img"><?php echo cr_post_image( $pid, 'cr-thumb', array( 'alt' => '' ) ); // phpcs:ignore ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ol>
		</div>
	</div>
</section>
