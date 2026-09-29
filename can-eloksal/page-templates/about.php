<?php
/**
 * Template Name: Hakkımızda
 *
 * Bölümler: hero, firma (editör içeriği + görsel), içerik blokları (üretim yaklaşımı, teknoloji,
 * kalite, tesis), sektörler, tesis galerisi, CTA. Tümü sayfa düzenleme ekranından yönetilir.
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

get_header();
while ( have_posts() ) :
	the_post();
	$ce_id     = get_the_ID();
	$ce_blocks = (array) ce_meta( $ce_id, 'blocks', array() );
	$ce_intro  = absint( ce_meta( $ce_id, 'intro_image' ) );

	get_template_part(
		'template-parts/components/page-hero',
		null,
		array(
			'title'    => ce_page_title( $ce_id ),
			'subtitle' => ce_page_subtitle( $ce_id ),
			'eyebrow'  => ce_meta( $ce_id, 'hero_eyebrow' ),
			'image'    => ce_meta( $ce_id, 'hero_image' ) ? ce_meta( $ce_id, 'hero_image' ) : get_post_thumbnail_id(),
			'size'     => 'lg',
		)
	);
	?>
	<section class="ce-section">
		<div class="ce-container ce-split ce-split--reverse">
			<div class="ce-split__content" data-reveal>
				<p class="ce-eyebrow"><?php echo esc_html( get_the_title() ); ?></p>
				<h2 class="ce-display ce-display--md"><?php echo ce_nl2br( ce_meta( $ce_id, 'intro_title', "HER YÜZEYDE\nÖZENLİ İŞÇİLİK." ) ); // phpcs:ignore ?></h2>
				<div class="ce-prose ce-entry"><?php the_content(); ?></div>
			</div>
			<div class="ce-split__media ce-about__media" data-reveal>
				<div class="ce-about__frame">
					<?php echo $ce_intro ? ce_img( $ce_intro, 'ce-tall', array( 'sizes' => '(min-width: 1024px) 45vw, 100vw', 'fallback_alt' => 'Can Eloksal tesisi' ) ) : ce_material( 'natural', 'Can Eloksal' ); // phpcs:ignore ?>
				</div>
				<?php if ( ce_meta( $ce_id, 'intro_badge' ) ) : ?>
					<p class="ce-about__badge"><?php echo ce_icon( 'shield', 18 ); // phpcs:ignore ?><span><?php echo esc_html( ce_meta( $ce_id, 'intro_badge' ) ); ?></span></p>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<?php if ( $ce_blocks ) : ?>
		<section class="ce-section ce-section--tint">
			<div class="ce-container">
				<div class="ce-feature-list">
					<?php foreach ( $ce_blocks as $ce_i => $ce_b ) : ?>
						<?php $ce_bimg = absint( $ce_b['image'] ?? 0 ); ?>
						<article class="ce-feature<?php echo $ce_bimg ? ' has-image' : ''; ?><?php echo $ce_i % 2 ? ' is-alt' : ''; ?>" data-reveal>
							<div class="ce-feature__head">
								<span class="ce-feature__num"><?php echo esc_html( sprintf( '%02d', $ce_i + 1 ) ); ?></span>
								<span class="ce-feature__icon"><?php echo ce_icon( $ce_b['icon'] ?? 'check', 24 ); // phpcs:ignore ?></span>
							</div>
							<div class="ce-feature__body">
								<?php if ( ! empty( $ce_b['eyebrow'] ) ) : ?>
									<p class="ce-eyebrow"><?php echo esc_html( $ce_b['eyebrow'] ); ?></p>
								<?php endif; ?>
								<h2 class="ce-feature__title"><?php echo esc_html( $ce_b['title'] ?? '' ); ?></h2>
								<?php if ( ! empty( $ce_b['text'] ) ) : ?>
									<div class="ce-feature__text"><?php echo ce_paragraphs( $ce_b['text'] ); // phpcs:ignore ?></div>
								<?php endif; ?>
							</div>
							<?php if ( $ce_bimg ) : ?>
								<div class="ce-feature__media"><?php echo ce_img( $ce_bimg, 'ce-card', array( 'sizes' => '(min-width: 1024px) 40vw, 100vw', 'fallback_alt' => $ce_b['title'] ?? '' ) ); // phpcs:ignore ?></div>
							<?php endif; ?>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( ce_meta( $ce_id, 'show_sectors', 1 ) && ce_get_items( 'ce_sector', 1 ) ) : ?>
		<section class="ce-section ce-section--dark">
			<div class="ce-container">
				<?php ce_section_head( array( 'eyebrow' => 'Sektörler', 'title' => ce_opt( 'sectors_title' ), 'align' => 'left' ) ); ?>
				<?php get_template_part( 'template-parts/components/sectors' ); ?>
			</div>
		</section>
	<?php endif; ?>

	<?php $ce_facility = (array) ce_meta( $ce_id, 'facility', array() ); ?>
	<?php if ( $ce_facility ) : ?>
		<section class="ce-section">
			<div class="ce-container">
				<?php ce_section_head( array( 'eyebrow' => 'Tesis', 'title' => ce_meta( $ce_id, 'facility_title', 'Tesisimizden' ), 'align' => 'left' ) ); ?>
				<?php get_template_part( 'template-parts/components/gallery-grid', null, array( 'items' => $ce_facility, 'group' => 'facility' ) ); ?>
			</div>
		</section>
	<?php endif; ?>

	<?php
	get_template_part(
		'template-parts/components/cta-band',
		null,
		array(
			'title' => ce_meta( $ce_id, 'cta_title' ),
			'text'  => ce_meta( $ce_id, 'cta_text' ),
		)
	);
endwhile;
get_footer();
