<?php
/**
 * Hero: tam genişlik arka plan görseli + soldan gradyan + editoryal başlık.
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;
$focus   = cr_opt( 'hero_focus', 'center' );
$overlay = cr_opt( 'hero_overlay', 'soft' );
$desk    = cr_opt( 'hero_image' );
$mob     = cr_opt( 'hero_image_mobile' );
?>
<section class="cr-hero cr-hero--<?php echo esc_attr( $overlay ); ?><?php echo cr_opt( 'hero_motion' ) ? ' has-motion' : ''; ?>"<?php echo cr_section_attr( 'hero' ); // phpcs:ignore ?>>
	<div class="cr-hero__bg"<?php echo cr_edit_img( 'hero_image' ); // phpcs:ignore ?>>
		<?php if ( $mob ) : ?>
			<picture>
				<source media="(max-width: 767px)" srcset="<?php echo esc_url( cr_img_url( $mob, 'cr-hero' ) ); ?>">
				<?php echo cr_img( $desk, 'full', array( 'alt' => cr_opt( 'hero_image_alt' ), 'class' => 'cr-hero__img', 'loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '100vw', 'style' => 'object-position:' . esc_attr( $focus ) ) ); // phpcs:ignore ?>
			</picture>
		<?php else : ?>
			<?php echo cr_img( $desk, 'full', array( 'alt' => cr_opt( 'hero_image_alt' ), 'class' => 'cr-hero__img', 'loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '100vw', 'style' => 'object-position:' . esc_attr( $focus ) ) ); // phpcs:ignore ?>
		<?php endif; ?>
		<span class="cr-hero__shade" aria-hidden="true"></span>
	</div>

	<div class="cr-container cr-hero__inner">
		<div class="cr-hero__copy">
			<?php if ( cr_opt( 'hero_eyebrow' ) ) : ?>
				<p class="cr-eyebrow cr-eyebrow--line cr-hero__eyebrow"><span<?php echo cr_edit( 'hero_eyebrow' ); // phpcs:ignore ?>><?php cr_t( 'hero_eyebrow' ); ?></span></p>
			<?php endif; ?>
			<h1 class="cr-hero__title">
				<span<?php echo cr_edit( 'hero_title' ); // phpcs:ignore ?>><?php cr_t( 'hero_title' ); ?></span><br>
				<em class="cr-hero__accent"<?php echo cr_edit( 'hero_title_accent' ); // phpcs:ignore ?>><?php cr_t( 'hero_title_accent' ); ?></em>
				<span<?php echo cr_edit( 'hero_title_after' ); // phpcs:ignore ?>><?php cr_t( 'hero_title_after' ); ?></span>
			</h1>
			<p class="cr-hero__text"<?php echo cr_edit( 'hero_text' ); // phpcs:ignore ?>><?php cr_t( 'hero_text' ); ?></p>
			<?php
			// Boş butonlar ziyaretçiye gösterilmez; düzenleyicide yazı girilebilsin diye yalnızca düzenleme modunda görünür.
			$cta1 = '' !== trim( (string) cr_opt( 'hero_cta1_text' ) );
			$cta2 = '' !== trim( (string) cr_opt( 'hero_cta2_text' ) );
			$edit = cr_can_edit();
			?>
			<?php if ( $cta1 || $cta2 || $edit ) : ?>
				<div class="cr-hero__ctas<?php echo ( $cta1 || $cta2 ) ? '' : ' cr-edit-only'; ?>">
					<?php if ( $cta1 || $edit ) : ?>
						<a class="cr-btn cr-btn--solid<?php echo $cta1 ? '' : ' cr-edit-only'; ?>" href="<?php echo esc_url( cr_url( cr_opt( 'hero_cta1_url' ) ) ); ?>"><span<?php echo cr_edit( 'hero_cta1_text' ); // phpcs:ignore ?>><?php cr_t( 'hero_cta1_text' ); ?></span></a>
					<?php endif; ?>
					<?php if ( $cta2 || $edit ) : ?>
						<a class="cr-btn-line<?php echo $cta2 ? '' : ' cr-edit-only'; ?>" href="<?php echo esc_url( cr_url( cr_opt( 'hero_cta2_url' ) ) ); ?>"><span<?php echo cr_edit( 'hero_cta2_text' ); // phpcs:ignore ?>><?php cr_t( 'hero_cta2_text' ); ?></span></a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>

	<?php if ( cr_opt( 'hero_chip_on' ) ) : ?>
		<a class="cr-hero__chip" href="<?php echo esc_url( cr_url( cr_opt( 'hero_chip_url' ) ) ); ?>">
			<small<?php echo cr_edit( 'hero_chip_label' ); // phpcs:ignore ?>><?php cr_t( 'hero_chip_label' ); ?></small>
			<strong<?php echo cr_edit( 'hero_chip_title' ); // phpcs:ignore ?>><?php cr_t( 'hero_chip_title' ); ?></strong>
			<span<?php echo cr_edit( 'hero_chip_text' ); // phpcs:ignore ?>><?php cr_t( 'hero_chip_text' ); ?></span>
		</a>
	<?php endif; ?>

	<a class="cr-hero__scroll" href="#cr-categories" aria-label="Aşağı kaydır"><span></span></a>
</section>
