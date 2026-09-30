<?php
/**
 * Hero.
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;
$focus = cr_opt( 'hero_focus', 'center' );
?>
<section class="cr-hero<?php echo cr_opt( 'hero_motion' ) ? ' has-motion' : ''; ?>"<?php echo cr_section_attr( 'hero' ); // phpcs:ignore ?>>
	<div class="cr-container cr-hero__grid">
		<div class="cr-hero__copy">
			<?php if ( cr_opt( 'hero_eyebrow' ) ) : ?>
				<p class="cr-eyebrow cr-hero__eyebrow"><span class="cr-dot" aria-hidden="true"></span><span<?php echo cr_edit( 'hero_eyebrow' ); // phpcs:ignore ?>><?php cr_t( 'hero_eyebrow' ); ?></span></p>
			<?php endif; ?>
			<h1 class="cr-hero__title">
				<span class="cr-hero__line"<?php echo cr_edit( 'hero_title' ); // phpcs:ignore ?>><?php cr_t( 'hero_title' ); ?></span>
				<em class="cr-hero__accent"<?php echo cr_edit( 'hero_title_accent' ); // phpcs:ignore ?>><?php cr_t( 'hero_title_accent' ); ?></em>
			</h1>
			<p class="cr-hero__text"<?php echo cr_edit( 'hero_text' ); // phpcs:ignore ?>><?php cr_t( 'hero_text' ); ?></p>
			<div class="cr-hero__ctas">
				<a class="cr-btn cr-btn--primary cr-btn--lg" href="<?php echo esc_url( cr_url( cr_opt( 'hero_cta1_url' ) ) ); ?>"><span<?php echo cr_edit( 'hero_cta1_text' ); // phpcs:ignore ?>><?php cr_t( 'hero_cta1_text' ); ?></span><?php echo cr_icon( 'arrow-right', 18 ); // phpcs:ignore ?></a>
				<a class="cr-btn cr-btn--ghost cr-btn--lg" href="<?php echo esc_url( cr_url( cr_opt( 'hero_cta2_url' ) ) ); ?>"><span<?php echo cr_edit( 'hero_cta2_text' ); // phpcs:ignore ?>><?php cr_t( 'hero_cta2_text' ); ?></span></a>
			</div>
		</div>
		<div class="cr-hero__visual">
			<span class="cr-hero__orb cr-hero__orb--1" aria-hidden="true"></span>
			<span class="cr-hero__orb cr-hero__orb--2" aria-hidden="true"></span>
			<div class="cr-hero__frame" data-parallax<?php echo cr_edit_img( 'hero_image' ); // phpcs:ignore ?>>
				<?php
				echo cr_img( // phpcs:ignore
					cr_opt( 'hero_image' ),
					'cr-hero',
					array(
						'alt'           => cr_opt( 'hero_image_alt' ),
						'class'         => 'cr-hero__img',
						'loading'       => 'eager',
						'fetchpriority' => 'high',
						'sizes'         => '(max-width: 900px) 92vw, 46vw',
						'style'         => 'object-position:' . esc_attr( $focus ),
						'width'         => 1200,
						'height'        => 1400,
					)
				);
				?>
			</div>
			<?php if ( cr_opt( 'hero_chip_on' ) ) : ?>
				<a class="cr-hero__chip" href="<?php echo esc_url( cr_url( cr_opt( 'hero_chip_url' ) ) ); ?>">
					<span class="cr-hero__chip-icon" aria-hidden="true"><?php echo cr_icon( 'flask', 20 ); // phpcs:ignore ?></span>
					<span class="cr-hero__chip-text">
						<small<?php echo cr_edit( 'hero_chip_label' ); // phpcs:ignore ?>><?php cr_t( 'hero_chip_label' ); ?></small>
						<strong<?php echo cr_edit( 'hero_chip_title' ); // phpcs:ignore ?>><?php cr_t( 'hero_chip_title' ); ?></strong>
						<span<?php echo cr_edit( 'hero_chip_text' ); // phpcs:ignore ?>><?php cr_t( 'hero_chip_text' ); ?></span>
					</span>
					<?php echo cr_icon( 'arrow-up-right', 18, 'cr-hero__chip-arrow' ); // phpcs:ignore ?>
				</a>
			<?php endif; ?>
			<svg class="cr-hero__leaf" viewBox="0 0 120 120" aria-hidden="true"><path d="M20 100C20 50 50 20 104 16c-4 54-34 84-84 84zM20 100 70 50" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
		</div>
	</div>
</section>
