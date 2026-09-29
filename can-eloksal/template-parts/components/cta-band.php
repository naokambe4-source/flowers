<?php
/**
 * Sayfa içi CTA bandı.
 *
 * @package CanEloksal
 *
 * @var array $args title, text, button, url.
 */

defined( 'ABSPATH' ) || exit;

$ce_title = $args['title'] ?? '';
if ( ! $ce_title ) {
	return;
}
?>
<section class="ce-section ce-section--compact">
	<div class="ce-container">
		<div class="ce-band" data-reveal>
			<div>
				<h2 class="ce-band__title"<?php echo ce_ed( isset( $args['__src'] ) ? $args['__src'] . ':title' : '' ); // phpcs:ignore ?>><?php echo esc_html( $ce_title ); ?></h2>
				<?php if ( ! empty( $args['text'] ) ) : ?>
					<p class="ce-band__text"<?php echo ce_ed( isset( $args['__src'] ) ? $args['__src'] . ':text' : '' ); // phpcs:ignore ?>><?php echo esc_html( $args['text'] ); ?></p>
				<?php endif; ?>
			</div>
			<div class="ce-btn-row">
				<?php echo ce_button( $args['button'] ?? 'Teklif Al', $args['url'] ?? ce_quote_url(), 'primary', 'arrow-right' ); // phpcs:ignore ?>
				<?php if ( ce_opt( 'phone' ) ) : ?>
					<a class="ce-btn ce-btn--ghost-light" href="<?php echo esc_attr( ce_tel() ); ?>"><?php echo ce_icon( 'phone', 18, 'ce-btn__icon' ); // phpcs:ignore ?><span><?php echo esc_html( ce_opt( 'phone' ) ); ?></span></a>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>
