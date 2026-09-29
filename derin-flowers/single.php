<?php
/**
 * Blog yazısı.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$df_cats = get_the_category();
	?>
	<article <?php post_class( 'df-article' ); ?>>
		<header class="df-article__head df-container df-container--narrow">
			<p class="df-eyebrow">
				<?php if ( $df_cats ) : ?>
					<a href="<?php echo esc_url( get_category_link( $df_cats[0] ) ); ?>"><?php echo esc_html( $df_cats[0]->name ); ?></a> ·
				<?php endif; ?>
				<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date( 'j F Y' ) ); ?></time>
			</p>
			<h1 class="df-article__title"><?php the_title(); ?></h1>
			<?php if ( has_excerpt() ) : ?>
				<p class="df-article__lead"><?php echo esc_html( get_the_excerpt() ); ?></p>
			<?php endif; ?>
		</header>
		<?php if ( has_post_thumbnail() ) : ?>
			<figure class="df-article__cover df-container">
				<?php the_post_thumbnail( 'df-banner', array( 'loading' => 'eager', 'sizes' => '(max-width: 1440px) 100vw, 1440px' ) ); ?>
			</figure>
		<?php endif; ?>
		<div class="df-container df-container--narrow">
			<div class="df-prose df-article__content">
				<?php the_content(); ?>
				<?php wp_link_pages(); ?>
			</div>
			<footer class="df-article__foot">
				<?php the_tags( '<div class="df-tags">', '', '</div>' ); ?>
				<nav class="df-article__nav">
					<?php previous_post_link( '<div class="df-article__prev">%link</div>', '&larr; %title' ); ?>
					<?php next_post_link( '<div class="df-article__next">%link</div>', '%title &rarr;' ); ?>
				</nav>
			</footer>
			<?php
			if ( comments_open() || get_comments_number() ) {
				comments_template();
			}
			?>
		</div>
	</article>
	<?php
	$df_related = new WP_Query(
		array(
			'post_type'           => 'post',
			'posts_per_page'      => 3,
			'post__not_in'        => array( get_the_ID() ),
			'category__in'        => wp_list_pluck( $df_cats, 'term_id' ),
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		)
	);
	if ( $df_related->have_posts() ) :
		?>
		<section class="df-section df-blog">
			<div class="df-container">
				<?php df_section_head( array( 'title' => 'Diğer Yazılar', 'align' => 'split' ) ); ?>
				<div class="df-blog__grid">
					<?php
					while ( $df_related->have_posts() ) {
						$df_related->the_post();
						get_template_part( 'template-parts/content/post-card' );
					}
					wp_reset_postdata();
					?>
				</div>
			</div>
		</section>
		<?php
	endif;
endwhile;

get_footer();
