<?php
/**
 * Ana sayfa — ince güven şeridi (ikon + metin, kart yok).
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

$items = array_filter(
	(array) df_opt( 'trust_items', array() ),
	function ( $i ) {
		return ! empty( $i['title'] );
	}
);
if ( ! $items ) {
	return;
}
?>
<section data-df-sec="trust" class="df-trust" aria-label="Neden Derin Flowers">
	<div class="df-container">
		<ul class="df-trust__list">
			<?php foreach ( $items as $ti => $item ) : ?>
				<li class="df-trust__item">
					<?php if ( ! empty( $item['icon'] ) ) : ?>
						<span class="df-trust__icon"><?php df_the_icon( $item['icon'], array( 'size' => 26 ) ); ?></span>
					<?php endif; ?>
					<span class="df-trust__text">
						<strong<?php echo df_e( 'trust_items.' . $ti . '.title' ); // phpcs:ignore ?>><?php echo esc_html( $item['title'] ); ?></strong>
						<?php if ( ! empty( $item['text'] ) ) : ?>
							<span<?php echo df_e( 'trust_items.' . $ti . '.text' ); // phpcs:ignore ?>><?php echo esc_html( $item['text'] ); ?></span>
						<?php endif; ?>
					</span>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
