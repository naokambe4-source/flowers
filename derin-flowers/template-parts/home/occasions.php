<?php
/**
 * Ana sayfa — Özel günler: geniş banner (görsel üstünde metin) ya da kart (metin solda, görsel sağda).
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

$items = array_filter(
	(array) df_opt( 'occ_items', array() ),
	function ( $i ) {
		return ! empty( $i['title'] );
	}
);
if ( ! $items ) {
	return;
}
$style = 'card' === df_opt( 'occ_style' ) ? 'card' : 'overlay';
?>
<section data-df-sec="occasions" class="df-section df-occasions df-occasions--<?php echo esc_attr( $style ); ?>" style="--occ-bg:<?php echo esc_attr( sanitize_hex_color( df_opt( 'occ_bg', '#F6E7E4' ) ) ? sanitize_hex_color( df_opt( 'occ_bg', '#F6E7E4' ) ) : '#F6E7E4' ); ?>" aria-labelledby="df-occ-title">
	<div class="df-container">
		<?php
		df_section_head(
			array(
				'eyebrow' => df_opt( 'occ_eyebrow' ),
				'title'   => df_opt( 'occ_title' ),
				'id'      => 'df-occ-title',
				'keys'    => array(
					'eyebrow' => 'occ_eyebrow',
					'title'   => 'occ_title',
				),
			)
		);
		?>
		<div class="df-occasions__grid df-scroller">
			<?php foreach ( $items as $i => $item ) : ?>
				<?php
				$url = ! empty( $item['url'] ) ? df_url( $item['url'] ) : '';
				$k   = 'occ_items.' . $i;
				?>
				<<?php echo $url ? 'a href="' . esc_url( $url ) . '"' : 'div'; // phpcs:ignore ?> class="df-occasion">
					<span class="df-occasion__media"<?php echo df_i( $k . '.image' ); // phpcs:ignore ?>>
						<?php
						echo df_image( // phpcs:ignore
							isset( $item['image'] ) ? $item['image'] : 0,
							'card' === $style ? 'df-portrait' : 'df-wide',
							array(
								'sizes' => '(max-width: 700px) 86vw, 33vw',
								'alt'   => $item['title'],
							),
							'Özel gün görseli'
						);
						?>
					</span>
					<span class="df-occasion__body">
						<span class="df-occasion__title"<?php echo df_e( $k . '.title' ); // phpcs:ignore ?>><?php echo esc_html( $item['title'] ); ?></span>
						<?php if ( ! empty( $item['text'] ) ) : ?>
							<span class="df-occasion__text"<?php echo df_e( $k . '.text' ); // phpcs:ignore ?>><?php echo esc_html( $item['text'] ); ?></span>
						<?php endif; ?>
						<?php if ( $url && 'card' === $style ) : ?>
							<span class="df-occasion__btn"><?php echo esc_html( df_opt( 'occ_btn', 'KEŞFET' ) ); ?><?php df_the_icon( 'arrow-right', array( 'size' => 14 ) ); ?></span>
						<?php elseif ( $url ) : ?>
							<span class="df-occasion__cta" aria-hidden="true"><?php df_the_icon( 'arrow-right', array( 'size' => 18 ) ); ?></span>
						<?php endif; ?>
					</span>
				</<?php echo $url ? 'a' : 'div'; ?>>
			<?php endforeach; ?>
		</div>
	</div>
</section>
