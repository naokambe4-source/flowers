<?php
/**
 * Ana sayfa — tam genişlik lifestyle banner (Söz & Nişan).
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

$desk  = df_img_url( df_opt( 'ed_image' ), 'df-banner' );
$mob   = df_opt( 'ed_image_mobile' ) ? df_img_url( df_opt( 'ed_image_mobile' ), 'df-hero-mobile' ) : $desk;
$title = df_opt( 'ed_title' );
if ( ! $desk && ! $title ) {
	return;
}
$vars = $desk ? '--df-ed-d:url(' . esc_url( $desk ) . ');--df-ed-m:url(' . esc_url( $mob ) . ');' : '';
?>
<section data-df-sec="editorial" class="df-editorial df-editorial--<?php echo esc_attr( df_opt( 'ed_align', 'left' ) ); ?> df-editorial--<?php echo esc_attr( df_opt( 'ed_theme', 'dark' ) ); ?><?php echo $desk ? '' : ' is-empty'; ?>" style="<?php echo esc_attr( $vars ); ?>" aria-labelledby="df-ed-title">
	<div class="df-editorial__bg" aria-hidden="true"<?php echo df_i( 'ed_image' ); // phpcs:ignore ?>>
		<?php if ( ! $desk ) : ?>
			<?php echo df_placeholder( 'Editorial banner görseli (2400×1100)' ); // phpcs:ignore ?>
		<?php endif; ?>
	</div>
	<div class="df-editorial__content">
		<div class="df-editorial__inner">
			<?php if ( df_opt( 'ed_eyebrow' ) ) : ?>
				<p class="df-eyebrow"<?php echo df_e( 'ed_eyebrow' ); // phpcs:ignore ?>><?php echo esc_html( df_opt( 'ed_eyebrow' ) ); ?></p>
			<?php endif; ?>
			<?php if ( $title ) : ?>
				<h2 class="df-editorial__title" id="df-ed-title"<?php echo df_e( 'ed_title' ); // phpcs:ignore ?>><?php echo df_nl2br( $title ); // phpcs:ignore ?></h2>
			<?php endif; ?>
			<?php if ( df_opt( 'ed_text' ) ) : ?>
				<p class="df-editorial__text"<?php echo df_e( 'ed_text' ); // phpcs:ignore ?>><?php echo esc_html( df_opt( 'ed_text' ) ); ?></p>
			<?php endif; ?>
			<?php if ( df_opt( 'ed_btn' ) && df_opt( 'ed_url' ) ) : ?>
				<a class="df-link-arrow df-link-arrow--lg" href="<?php echo esc_url( df_url( df_opt( 'ed_url' ) ) ); ?>"><?php echo esc_html( df_opt( 'ed_btn' ) ); ?><?php df_the_icon( 'arrow-right', array( 'size' => 20 ) ); ?></a>
			<?php endif; ?>
		</div>
	</div>
</section>
