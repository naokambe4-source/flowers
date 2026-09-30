<?php
/**
 * Sayfa.
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<article <?php post_class( 'cr-page' ); ?>>
		<header class="cr-page__head">
			<div class="cr-container cr-container--narrow">
				<?php cr_breadcrumb_html(); ?>
				<h1 class="cr-article__title"><?php the_title(); ?></h1>
				<?php if ( has_excerpt() ) : ?>
					<p class="cr-article__intro"><?php echo esc_html( get_the_excerpt() ); ?></p>
				<?php endif; ?>
			</div>
		</header>
		<?php if ( has_post_thumbnail() ) : ?>
			<figure class="cr-article__hero cr-container"><?php the_post_thumbnail( 'cr-hero', array( 'loading' => 'eager', 'fetchpriority' => 'high' ) ); ?></figure>
		<?php endif; ?>
		<div class="cr-container cr-container--narrow">
			<?php if ( cr_meta( '_cr_short_answer' ) ) : ?>
				<div class="cr-short-answer">
					<span class="cr-short-answer__label"><?php echo cr_icon( 'sparkle', 16 ); // phpcs:ignore ?><?php cr_t( 'art_short_label' ); ?></span>
					<p class="cr-short-answer__text"><?php echo esc_html( cr_meta( '_cr_short_answer' ) ); ?></p>
				</div>
			<?php endif; ?>
			<div class="cr-prose entry-content">
				<?php the_content(); ?>
			</div>
			<?php get_template_part( 'template-parts/parts/article-extras' ); ?>
		</div>
	</article>
	<?php
endwhile;

get_footer();
