<?php
/**
 * Template Name: İletişim
 *
 * Firma bilgileri, harita ve veritabanına kaydedilen iletişim formu.
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

get_header();
while ( have_posts() ) :
	the_post();
	$ce_id       = get_the_ID();
	$ce_subjects = ce_lines( ce_meta( $ce_id, 'subjects' ) );
	get_template_part(
		'template-parts/components/page-hero',
		null,
		array(
			'title'    => ce_page_title( $ce_id ),
			'subtitle' => ce_page_subtitle( $ce_id ),
			'eyebrow'  => ce_meta( $ce_id, 'hero_eyebrow' ),
			'image'    => ce_meta( $ce_id, 'hero_image' ),
		)
	);
	?>
	<section class="ce-section">
		<div class="ce-container ce-contact">
			<div class="ce-contact__info">
				<p class="ce-eyebrow"><?php echo esc_html( ce_opt( 'company_name' ) ); ?></p>
				<h2 class="ce-display ce-display--sm">Bize ulaşın</h2>
				<?php if ( get_the_content() ) : ?>
					<div class="ce-prose"><?php the_content(); ?></div>
				<?php endif; ?>
				<?php get_template_part( 'template-parts/components/contact-cards' ); ?>
			</div>

			<?php
			get_template_part(
				'template-parts/components/form-contact',
				null,
				array(
					'title'    => ce_meta( $ce_id, 'form_title', 'Bize yazın' ),
					'text'     => ce_meta( $ce_id, 'form_text' ),
					'subjects' => $ce_subjects,
				)
			);
			?>
		</div>
	</section>

	<?php $ce_map = ce_map_embed(); ?>
	<?php if ( $ce_map ) : ?>
		<section class="ce-map" aria-label="Konum haritası">
			<div class="ce-map__frame"><?php echo $ce_map; // phpcs:ignore -- ce_map_embed() yalnızca doğrulanmış Google Maps iframe döndürür. ?></div>
			<?php if ( ce_opt( 'maps_link' ) ) : ?>
				<a class="ce-btn ce-btn--dark ce-map__btn" href="<?php echo esc_url( ce_opt( 'maps_link' ) ); ?>" target="_blank" rel="noopener"><?php echo ce_icon( 'map-pin', 18, 'ce-btn__icon' ); // phpcs:ignore ?><span>Yol tarifi al</span></a>
			<?php endif; ?>
		</section>
	<?php endif; ?>
	<?php
endwhile;
get_footer();
