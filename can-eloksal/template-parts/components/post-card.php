<?php
/**
 * Blog kartı.
 *
 * @package CanEloksal
 *
 * @var array $args post (WP_Post), heading, featured (bool), index.
 */

defined( 'ABSPATH' ) || exit;

$ce_post = $args['post'];
$ce_h    = $args['heading'] ?? 'h3';
$ce_cats = get_the_category( $ce_post->ID );
?>
<article class="ce-pcard<?php echo ! empty( $args['featured'] ) ? ' ce-pcard--featured' : ''; ?>" data-reveal style="--i:<?php echo (int) ( $args['index'] ?? 0 ); ?>">
	<a class="ce-pcard__media"<?php echo ce_edimg( 'thumb:' . $ce_post->ID ); // phpcs:ignore ?> href="<?php echo esc_url( get_permalink( $ce_post ) ); ?>" tabindex="-1" aria-hidden="true">
		<?php echo ce_post_visual( $ce_post->ID, 'ce-card', ce_post_tone( $ce_post->ID ), array( 'sizes' => '(min-width: 1024px) 33vw, 100vw' ) ); // phpcs:ignore ?>
	</a>
	<div class="ce-pcard__body">
		<p class="ce-pcard__meta">
			<?php if ( $ce_cats ) : ?>
				<span class="ce-pcard__cat"><?php echo esc_html( $ce_cats[0]->name ); ?></span>
			<?php endif; ?>
			<time datetime="<?php echo esc_attr( get_the_date( 'c', $ce_post ) ); ?>"><?php echo esc_html( ce_date( $ce_post ) ); ?></time>
		</p>
		<<?php echo esc_html( $ce_h ); ?> class="ce-pcard__title"><a<?php echo ce_ed( 'post:' . $ce_post->ID . ':title' ); // phpcs:ignore ?> href="<?php echo esc_url( get_permalink( $ce_post ) ); ?>"><?php echo esc_html( get_the_title( $ce_post ) ); ?></a></<?php echo esc_html( $ce_h ); ?>>
		<p class="ce-pcard__excerpt"<?php echo ce_ed( 'post:' . $ce_post->ID . ':excerpt' ); // phpcs:ignore ?>><?php echo esc_html( wp_trim_words( get_the_excerpt( $ce_post ), 22, '…' ) ); ?></p>
		<span class="ce-pcard__more" aria-hidden="true">Devamını oku <?php echo ce_icon( 'arrow-right', 16 ); // phpcs:ignore ?></span>
	</div>
</article>
