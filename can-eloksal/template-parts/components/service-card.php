<?php
/**
 * Hizmet kartı (fotoğraf odaklı).
 *
 * @package CanEloksal
 *
 * @var array $args post (WP_Post), size (lg|md|sm), heading (h2|h3), index.
 */

defined( 'ABSPATH' ) || exit;

$ce_post    = $args['post'];
$ce_size    = $args['size'] ?? 'md';
$ce_heading = $args['heading'] ?? 'h3';
$ce_terms   = get_the_terms( $ce_post, 'ce_service_cat' );
$ce_cat     = ( $ce_terms && ! is_wp_error( $ce_terms ) ) ? $ce_terms[0] : null;
$ce_summary = ce_meta( $ce_post->ID, 'summary' );
$ce_img_sz  = 'lg' === $ce_size ? 'ce-wide' : 'ce-card';
?>
<article class="ce-scard ce-scard--<?php echo esc_attr( $ce_size ); ?>" data-reveal data-cats="<?php echo esc_attr( $ce_cat ? $ce_cat->slug : '' ); ?>" style="--i:<?php echo (int) ( $args['index'] ?? 0 ); ?>">
	<a class="ce-scard__link" href="<?php echo esc_url( get_permalink( $ce_post ) ); ?>">
		<span class="ce-scard__media"<?php echo ce_edimg( 'thumb:' . $ce_post->ID ); // phpcs:ignore ?>>
			<?php
			echo ce_post_visual( // phpcs:ignore
				$ce_post->ID,
				$ce_img_sz,
				ce_service_tone( $ce_post->ID ),
				array( 'sizes' => 'lg' === $ce_size ? '(min-width: 1024px) 50vw, 100vw' : '(min-width: 1024px) 33vw, (min-width: 640px) 50vw, 100vw' )
			);
			?>
		</span>
		<span class="ce-scard__body">
			<?php if ( $ce_cat ) : ?>
				<span class="ce-scard__cat"><?php echo esc_html( $ce_cat->name ); ?></span>
			<?php endif; ?>
			<<?php echo esc_html( $ce_heading ); ?> class="ce-scard__title"<?php echo ce_ed( 'post:' . $ce_post->ID . ':title' ); // phpcs:ignore ?>><?php echo esc_html( get_the_title( $ce_post ) ); ?></<?php echo esc_html( $ce_heading ); ?>>
			<?php if ( $ce_summary ) : ?>
				<span class="ce-scard__text"<?php echo ce_ed( 'meta:' . $ce_post->ID . ':summary' ); // phpcs:ignore ?>><?php echo esc_html( $ce_summary ); ?></span>
			<?php endif; ?>
			<span class="ce-scard__arrow" aria-hidden="true"><?php echo ce_icon( 'arrow-up-right', 20 ); // phpcs:ignore ?></span>
		</span>
	</a>
</article>
