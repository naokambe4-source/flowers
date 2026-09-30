<?php
/**
 * Ana sayfa — bülten (kompakt, footer'dan hemen önce).
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

$title = df_opt( 'nl_title' );
if ( ! $title ) {
	return;
}
$action = df_opt( 'nl_action' );
?>
<section data-df-sec="newsletter" class="df-newsletter" aria-labelledby="df-nl-title">
	<div class="df-container df-newsletter__inner">
		<div class="df-newsletter__text">
			<h2 class="df-newsletter__title" id="df-nl-title"<?php echo df_e( 'nl_title' ); // phpcs:ignore ?>><?php echo esc_html( $title ); ?></h2>
			<?php if ( df_opt( 'nl_text' ) ) : ?>
				<p<?php echo df_e( 'nl_text' ); // phpcs:ignore ?>><?php echo esc_html( df_opt( 'nl_text' ) ); ?></p>
			<?php endif; ?>
		</div>
		<form class="df-newsletter__form" method="post" <?php echo $action ? 'action="' . esc_url( $action ) . '" target="_blank"' : 'data-df-newsletter'; ?>>
			<div class="df-newsletter__field">
				<label class="screen-reader-text" for="df-nl-email">E-posta adresiniz</label>
				<input type="email" id="df-nl-email" name="<?php echo $action ? 'EMAIL' : 'email'; ?>" placeholder="<?php echo esc_attr( df_opt( 'nl_placeholder' ) ); ?>" autocomplete="email" required>
				<button type="submit" class="df-btn df-btn--solid"><span><?php echo esc_html( df_opt( 'nl_btn', 'Kaydol' ) ); ?></span></button>
			</div>
			<?php if ( df_opt( 'nl_consent' ) ) : ?>
				<p class="df-newsletter__consent"><?php echo esc_html( df_opt( 'nl_consent' ) ); ?></p>
			<?php endif; ?>
			<p class="df-newsletter__msg" data-df-newsletter-msg aria-live="polite"></p>
		</form>
	</div>
</section>
