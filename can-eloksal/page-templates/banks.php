<?php
/**
 * Template Name: Banka Hesapları
 *
 * Hesaplar "Banka Hesapları" menüsünden yönetilir. IBAN kopyalama butonu içerir.
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

get_header();
while ( have_posts() ) :
	the_post();
	$ce_id = get_the_ID();
	get_template_part(
		'template-parts/components/page-hero',
		null,
		array(
			'title'    => ce_page_title( $ce_id ),
			'src'      => ce_page_hero_src( $ce_id ),
			'subtitle' => ce_page_subtitle( $ce_id ),
			'eyebrow'  => ce_meta( $ce_id, 'hero_eyebrow' ),
			'image'    => ce_meta( $ce_id, 'hero_image' ),
		)
	);
	?>
	<section class="ce-section">
		<div class="ce-container">
			<?php get_template_part( 'template-parts/components/banks' ); ?>

			<?php if ( get_the_content() ) : ?>
				<div class="ce-note" data-reveal><?php echo ce_icon( 'info', 20 ); // phpcs:ignore ?><div class="ce-prose"><?php the_content(); ?></div></div>
			<?php endif; ?>
		</div>
	</section>
	<?php
endwhile;
get_footer();
