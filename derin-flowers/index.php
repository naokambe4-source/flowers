<?php
/**
 * Blog / arşiv listesi.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( is_home() && ! is_front_page() ) {
	$df_title = single_post_title( '', false );
	$df_sub   = '';
} elseif ( is_search() ) {
	/* translators: %s: arama */
	$df_title = sprintf( '"%s" için sonuçlar', get_search_query() );
	$df_sub   = '';
} elseif ( is_archive() ) {
	$df_title = wp_strip_all_tags( get_the_archive_title() );
	$df_sub   = get_the_archive_description();
} else {
	$df_title = 'Çiçek Rehberi';
	$df_sub   = '';
}

get_template_part(
	'template-parts/content/page-header',
	null,
	array(
		'title'   => $df_title,
		'sub'     => $df_sub,
		'eyebrow' => is_search() ? 'Arama' : 'Blog',
	)
);
?>
<div class="df-container df-archive">
	<?php if ( have_posts() ) : ?>
		<div class="df-blog__grid">
			<?php
			while ( have_posts() ) {
				the_post();
				get_template_part( 'template-parts/content/post-card' );
			}
			?>
		</div>
		<?php
		the_posts_pagination(
			array(
				'prev_text' => df_icon( 'arrow-left', array( 'size' => 18 ) ),
				'next_text' => df_icon( 'arrow-right', array( 'size' => 18 ) ),
				'class'     => 'df-pagination',
			)
		);
		?>
	<?php else : ?>
		<div class="df-empty">
			<?php df_the_icon( 'search', array( 'size' => 40 ) ); ?>
			<h2>Sonuç bulunamadı</h2>
			<p>Farklı bir kelimeyle tekrar aramayı deneyin.</p>
			<?php get_search_form(); ?>
		</div>
	<?php endif; ?>
</div>
<?php
get_footer();
