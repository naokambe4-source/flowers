<?php
/**
 * Varsayılan sayfa şablonu (KVKK, gizlilik, özel sayfalar). İçerik blok editöründen yönetilir.
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( ce_is_builder_content( get_queried_object_id() ) ) {
	ce_render_canvas();
	get_footer();
	return;
}

while ( have_posts() ) :
	the_post();
	get_template_part(
		'template-parts/components/page-hero',
		null,
		array(
			'title'    => ce_page_title( get_the_ID() ),
			'src'      => ce_page_hero_src( get_the_ID() ),
			'subtitle' => ce_page_subtitle( get_the_ID() ),
			'eyebrow'  => ce_meta( get_the_ID(), 'hero_eyebrow' ),
			'image'    => ce_meta( get_the_ID(), 'hero_image' ) ? ce_meta( get_the_ID(), 'hero_image' ) : get_post_thumbnail_id(),
		)
	);
	?>
	<section class="ce-section">
		<div class="ce-container ce-container--narrow">
			<article <?php post_class( 'ce-prose ce-entry' ); ?>>
				<?php
				the_content();
				wp_link_pages( array( 'before' => '<nav class="ce-pages">', 'after' => '</nav>' ) );
				?>
				<?php if ( get_the_modified_date() ) : ?>
					<p class="ce-entry__updated">Son güncelleme: <time datetime="<?php echo esc_attr( get_the_modified_date( 'c' ) ); ?>"><?php echo esc_html( get_the_modified_date( 'j F Y' ) ); ?></time></p>
				<?php endif; ?>
			</article>
		</div>
	</section>
	<?php
endwhile;
get_footer();
