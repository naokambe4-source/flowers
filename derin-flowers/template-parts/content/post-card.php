<?php
/**
 * Blog kartı (editorial).
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

$cats = get_the_category();
?>
<article <?php post_class( 'df-post-card' ); ?>>
	<a class="df-post-card__media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
		<?php
		if ( has_post_thumbnail() ) {
			the_post_thumbnail(
				'df-wide',
				array(
					'loading' => 'lazy',
					'sizes'   => '(max-width: 700px) 100vw, 33vw',
				)
			);
		} else {
			echo df_placeholder( 'Öne çıkan görsel' ); // phpcs:ignore
		}
		?>
	</a>
	<div class="df-post-card__body">
		<p class="df-post-card__meta">
			<?php if ( $cats ) : ?>
				<a href="<?php echo esc_url( get_category_link( $cats[0] ) ); ?>"><?php echo esc_html( $cats[0]->name ); ?></a>
				<span aria-hidden="true">·</span>
			<?php endif; ?>
			<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date( 'j F Y' ) ); ?></time>
		</p>
		<h3 class="df-post-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
		<a class="df-link-arrow" href="<?php the_permalink(); ?>">Yazıyı Oku<?php df_the_icon( 'arrow-right', array( 'size' => 16 ) ); ?></a>
	</div>
</article>
