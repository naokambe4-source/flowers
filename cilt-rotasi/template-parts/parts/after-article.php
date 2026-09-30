<?php
/**
 * Makale sonrası: bir sonraki rehber + ilgili içerikler.
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;
$pid   = get_the_ID();
$type  = get_post_type();
$term  = cr_primary_term( $pid );
$count = (int) cr_opt( 'art_related', 3 );

$next = null;
if ( cr_opt( 'art_next' ) ) {
	$next = get_adjacent_post( (bool) $term && 'post' === $type, '', false, $term ? $term->taxonomy : 'category' );
	if ( ! $next ) {
		$next = get_adjacent_post( false, '', true );
	}
}

$related = array();
if ( $count ) {
	$args = array(
		'post_type'      => $type,
		'posts_per_page' => $count,
		'post__not_in'   => array_filter( array( $pid, $next ? $next->ID : 0 ) ),
		'no_found_rows'  => true,
		'fields'         => 'ids',
	);
	$tax_q = array( 'relation' => 'OR' );
	if ( $term ) {
		$tax_q[] = array( 'taxonomy' => $term->taxonomy, 'terms' => array( $term->term_id ) );
	}
	$probs = wp_get_post_terms( $pid, 'cilt_sorunu', array( 'fields' => 'ids' ) );
	if ( $probs && ! is_wp_error( $probs ) ) {
		$tax_q[] = array( 'taxonomy' => 'cilt_sorunu', 'terms' => $probs );
	}
	if ( count( $tax_q ) > 1 ) {
		$args['tax_query'] = $tax_q; // phpcs:ignore WordPress.DB.SlowDBQuery
	}
	$related = get_posts( $args );
	if ( count( $related ) < $count ) {
		$related = array_merge(
			$related,
			get_posts(
				array(
					'post_type'      => 'post',
					'posts_per_page' => $count - count( $related ),
					'post__not_in'   => array_merge( $related, array( $pid ) ),
					'no_found_rows'  => true,
					'fields'         => 'ids',
				)
			)
		);
	}
}
if ( ! $next && ! $related ) {
	return;
}
?>
<div class="cr-after">
	<div class="cr-container">
		<?php if ( $next ) : ?>
			<a class="cr-next" href="<?php echo esc_url( get_permalink( $next ) ); ?>">
				<span class="cr-next__media"><?php echo cr_post_image( $next->ID, 'cr-wide', array( 'alt' => '', 'class' => 'cr-zoom' ) ); // phpcs:ignore ?></span>
				<span class="cr-next__body">
					<span class="cr-eyebrow">Bir sonraki rehber <?php echo cr_icon( 'arrow-right', 14 ); // phpcs:ignore ?></span>
					<span class="cr-next__title"><?php echo esc_html( get_the_title( $next ) ); ?></span>
					<span class="cr-next__meta"><?php echo esc_html( cr_reading_time( $next->ID ) ); ?> dk okuma</span>
				</span>
			</a>
		<?php endif; ?>

		<?php if ( $related ) : ?>
			<div class="cr-related">
				<h2 class="cr-h3">İlgili içerikler</h2>
				<div class="cr-related__grid">
					<?php
					global $post;
					foreach ( $related as $rid ) {
						$post = get_post( $rid ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride
						setup_postdata( $post );
						get_template_part( 'template-parts/cards/card', null, array( 'variant' => 'plain' ) );
					}
					wp_reset_postdata();
					?>
				</div>
			</div>
		<?php endif; ?>
	</div>
</div>
