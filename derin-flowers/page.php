<?php
/**
 * Sayfa şablonu.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$df_is_wc = df_is_shop_context();
	get_template_part(
		'template-parts/content/page-header',
		null,
		array(
			'title'  => get_the_title(),
			'sub'    => has_excerpt() ? get_the_excerpt() : '',
			'crumbs' => ! $df_is_wc,
		)
	);
	?>
	<div class="df-container <?php echo $df_is_wc ? 'df-wc-page' : 'df-container--narrow df-page'; ?>">
		<div class="<?php echo $df_is_wc ? 'df-wc-content' : 'df-prose df-page__content'; ?>">
			<?php the_content(); ?>
			<?php wp_link_pages(); ?>
		</div>
	</div>
	<?php
endwhile;

get_footer();
