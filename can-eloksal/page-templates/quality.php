<?php
/**
 * Template Name: Kalite Politikası
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

get_header();
while ( have_posts() ) :
	the_post();
	$ce_id         = get_the_ID();
	$ce_principles = (array) ce_meta( $ce_id, 'principles', array() );
	$ce_steps      = (array) ce_meta( $ce_id, 'steps', array() );
	$ce_img        = absint( ce_meta( $ce_id, 'quality_image' ) );

	get_template_part(
		'template-parts/components/page-hero',
		null,
		array(
			'title'    => ce_page_title( $ce_id ),
			'src'      => ce_page_hero_src( $ce_id ),
			'subtitle' => ce_page_subtitle( $ce_id ),
			'eyebrow'  => ce_meta( $ce_id, 'hero_eyebrow' ),
			'image'    => ce_meta( $ce_id, 'hero_image' ) ? ce_meta( $ce_id, 'hero_image' ) : get_post_thumbnail_id(),
			'size'     => 'lg',
			'tone'     => 'natural',
		)
	);
	?>
	<section class="ce-section">
		<div class="ce-container ce-quality-intro">
			<div class="ce-quality-intro__lead" data-reveal>
				<p class="ce-eyebrow">Yaklaşımımız</p>
				<p class="ce-quote-mark"<?php echo ce_ed( 'meta:' . $ce_id . ':hero_subtitle' ); // phpcs:ignore ?>><?php echo esc_html( ce_page_subtitle( $ce_id ) ); ?></p>
				<?php if ( $ce_img ) : ?>
					<div class="ce-quality-intro__media"><?php echo ce_img( $ce_img, 'ce-card', array( 'sizes' => '(min-width: 1024px) 40vw, 100vw' ) ); // phpcs:ignore ?></div>
				<?php endif; ?>
			</div>
			<div class="ce-prose ce-entry ce-quality-intro__text" data-reveal><?php the_content(); ?></div>
		</div>
	</section>

	<?php if ( $ce_principles ) : ?>
		<section class="ce-section ce-section--tint">
			<div class="ce-container">
				<?php ce_section_head( array( 'eyebrow' => 'İlkeler', 'title' => ce_meta( $ce_id, 'principles_title', 'Kalite ilkelerimiz' ), 'align' => 'left' ) ); ?>
				<ul class="ce-bento ce-bento--even">
					<?php foreach ( $ce_principles as $ce_i => $ce_p ) : ?>
						<li class="ce-bento__item" data-reveal style="--i:<?php echo (int) $ce_i; ?>">
							<span class="ce-bento__num"><?php echo esc_html( sprintf( '%02d', $ce_i + 1 ) ); ?></span>
							<span class="ce-bento__icon"><?php echo ce_icon( $ce_p['icon'] ?? 'check', 26 ); // phpcs:ignore ?></span>
							<h3 class="ce-bento__title"<?php echo ce_ed( 'meta:' . $ce_id . ':principles@' . $ce_i . '.title' ); // phpcs:ignore ?>><?php echo esc_html( $ce_p['title'] ?? '' ); ?></h3>
							<?php if ( ! empty( $ce_p['text'] ) ) : ?>
								<p class="ce-bento__text"<?php echo ce_ed( 'meta:' . $ce_id . ':principles@' . $ce_i . '.text' ); // phpcs:ignore ?>><?php echo esc_html( $ce_p['text'] ); ?></p>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $ce_steps ) : ?>
		<section class="ce-section ce-section--dark">
			<div class="ce-container">
				<?php ce_section_head( array( 'eyebrow' => 'Kontrol', 'title' => ce_meta( $ce_id, 'steps_title', 'Kalite kontrol adımlarımız' ), 'align' => 'left' ) ); ?>
				<ol class="ce-steps">
					<?php foreach ( $ce_steps as $ce_i => $ce_s ) : ?>
						<li class="ce-steps__item" data-reveal style="--i:<?php echo (int) $ce_i; ?>">
							<span class="ce-steps__num"><?php echo esc_html( sprintf( '%02d', $ce_i + 1 ) ); ?></span>
							<h3 class="ce-steps__title"<?php echo ce_ed( 'meta:' . $ce_id . ':steps@' . $ce_i . '.title' ); // phpcs:ignore ?>><?php echo esc_html( $ce_s['title'] ?? '' ); ?></h3>
							<?php if ( ! empty( $ce_s['text'] ) ) : ?>
								<p class="ce-steps__text"<?php echo ce_ed( 'meta:' . $ce_id . ':steps@' . $ce_i . '.text' ); // phpcs:ignore ?>><?php echo esc_html( $ce_s['text'] ); ?></p>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ol>
			</div>
		</section>
	<?php endif; ?>
	<?php
endwhile;
get_footer();
