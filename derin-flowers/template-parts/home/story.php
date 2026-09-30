<?php
/**
 * Ana sayfa — marka hikayesi (asimetrik, bol boşluk).
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

$title = df_opt( 'story_title' );
if ( ! $title ) {
	return;
}
$second = absint( df_opt( 'story_image2' ) );
?>
<section data-df-sec="story" class="df-section df-story" aria-labelledby="df-story-title">
	<div class="df-container df-story__grid<?php echo $second ? ' has-second' : ''; ?>">
		<div class="df-story__media">
			<div class="df-story__main"<?php echo df_i( 'story_image' ); // phpcs:ignore ?>>
				<?php echo df_image( df_opt( 'story_image' ), 'df-portrait', array( 'sizes' => '(max-width: 900px) 100vw, 45vw', 'alt' => $title ), 'Marka hikayesi görseli' ); // phpcs:ignore ?>
			</div>
			<?php if ( $second ) : ?>
				<div class="df-story__second">
					<?php echo df_image( $second, 'df-square', array( 'sizes' => '(max-width: 900px) 40vw, 18vw', 'alt' => '' ) ); // phpcs:ignore ?>
				</div>
			<?php endif; ?>
		</div>
		<div class="df-story__text">
			<?php if ( df_opt( 'story_eyebrow' ) ) : ?>
				<p class="df-eyebrow"<?php echo df_e( 'story_eyebrow' ); // phpcs:ignore ?>><?php echo esc_html( df_opt( 'story_eyebrow' ) ); ?></p>
			<?php endif; ?>
			<h2 class="df-story__title" id="df-story-title"<?php echo df_e( 'story_title' ); // phpcs:ignore ?>><?php echo esc_html( $title ); ?></h2>
			<?php if ( df_opt( 'story_text' ) ) : ?>
				<p class="df-story__lead"<?php echo df_e( 'story_text' ); // phpcs:ignore ?>><?php echo df_nl2br( df_opt( 'story_text' ) ); // phpcs:ignore ?></p>
			<?php endif; ?>
			<?php if ( df_opt( 'story_signature' ) ) : ?>
				<p class="df-story__sign"<?php echo df_e( 'story_signature' ); // phpcs:ignore ?>><?php echo esc_html( df_opt( 'story_signature' ) ); ?></p>
			<?php endif; ?>
			<?php if ( df_opt( 'story_btn' ) && df_opt( 'story_url' ) ) : ?>
				<a class="df-link-arrow df-link-arrow--lg" href="<?php echo esc_url( df_url( df_opt( 'story_url' ) ) ); ?>"><?php echo esc_html( df_opt( 'story_btn' ) ); ?><?php df_the_icon( 'arrow-right', array( 'size' => 20 ) ); ?></a>
			<?php endif; ?>
		</div>
	</div>
</section>
