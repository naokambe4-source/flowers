<?php
/**
 * Güven şeridi.
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

$ce_items = (array) ce_hopt( $args, 'trust_items' );
if ( ! $ce_items ) {
	return;
}
?>
<section class="ce-trust" aria-label="Öne çıkan yetkinlikler">
	<div class="ce-container">
		<ul class="ce-trust__list">
			<?php foreach ( $ce_items as $ce_i => $ce_item ) : ?>
				<li class="ce-trust__item" data-reveal style="--i:<?php echo (int) $ce_i; ?>">
					<span class="ce-trust__icon"><?php echo ce_icon( $ce_item['icon'] ?? 'check', 22 ); // phpcs:ignore ?></span>
					<span class="ce-trust__body">
						<strong<?php echo ce_ed( ce_src( $args, 'trust_items' ) . '@' . $ce_i . '.title' ); // phpcs:ignore ?>><?php echo esc_html( $ce_item['title'] ?? '' ); ?></strong>
						<?php if ( ! empty( $ce_item['text'] ) ) : ?>
							<span<?php echo ce_ed( ce_src( $args, 'trust_items' ) . '@' . $ce_i . '.text' ); // phpcs:ignore ?>><?php echo esc_html( $ce_item['text'] ); ?></span>
						<?php endif; ?>
					</span>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
