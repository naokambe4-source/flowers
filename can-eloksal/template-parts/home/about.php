<?php
/**
 * Hakkımızda bölümü (split editoryal düzen).
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

$ce_img   = absint( ce_opt( 'about_image' ) );
$ce_img2  = absint( ce_opt( 'about_image_2' ) );
$ce_badge = ce_opt( 'about_badge' );
?>
<section class="ce-section ce-about" aria-labelledby="ce-about-title">
	<div class="ce-container ce-split">
		<div class="ce-split__media ce-about__media" data-reveal>
			<div class="ce-about__frame">
				<?php echo $ce_img ? ce_img( $ce_img, 'ce-tall', array( 'sizes' => '(min-width: 1024px) 45vw, 100vw', 'fallback_alt' => 'Can Eloksal üretim hattı' ) ) : ce_material( 'natural', 'Can Eloksal' ); // phpcs:ignore ?>
			</div>
			<?php if ( $ce_img2 ) : ?>
				<div class="ce-about__inset"><?php echo ce_img( $ce_img2, 'ce-thumb', array( 'sizes' => '240px' ) ); // phpcs:ignore ?></div>
			<?php endif; ?>
			<?php if ( $ce_badge ) : ?>
				<p class="ce-about__badge"><?php echo ce_icon( 'shield', 18 ); // phpcs:ignore ?><span><?php echo esc_html( $ce_badge ); ?></span></p>
			<?php endif; ?>
		</div>
		<div class="ce-split__content" data-reveal>
			<?php if ( ce_opt( 'about_eyebrow' ) ) : ?>
				<p class="ce-eyebrow"><?php echo esc_html( ce_opt( 'about_eyebrow' ) ); ?></p>
			<?php endif; ?>
			<h2 id="ce-about-title" class="ce-display"><?php echo ce_nl2br( ce_opt( 'about_title' ) ); // phpcs:ignore ?></h2>
			<div class="ce-prose ce-about__text"><?php echo ce_paragraphs( ce_opt( 'about_text' ) ); // phpcs:ignore ?></div>
			<?php echo ce_button( ce_opt( 'about_button' ), ce_opt( 'about_link' ), 'dark', 'arrow-right' ); // phpcs:ignore ?>
		</div>
	</div>
</section>
