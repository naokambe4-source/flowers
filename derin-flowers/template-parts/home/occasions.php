<?php
/**
 * Ana sayfa — Özel günler: 3 geniş editorial banner (16:10).
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
?>
<section class="df-section df-occasions" aria-labelledby="df-occ-title">
	<div class="df-container">
		<?php
		df_section_head(
			array(
				'eyebrow' => df_opt( 'occ_eyebrow' ),
				'title'   => df_opt( 'occ_title' ),
				'id'      => 'df-occ-title',
			)
		);
		?>
		<div class="df-occasions__grid df-scroller">
			<?php foreach ( $items as $item ) : ?>
				<?php $url = ! empty( $item['url'] ) ? df_url( $item['url'] ) : ''; ?>
				<<?php echo $url ? 'a href="' . esc_url( $url ) . '"' : 'div'; ?> class="df-occasion">
					<span class="df-occasion__media">
						<?php
						echo df_image( // phpcs:ignore
							isset( $item['image'] ) ? $item['image'] : 0,
							'df-wide',
							array(
								'sizes' => '(max-width: 700px) 86vw, 33vw',
								'alt'   => $item['title'],
							),
							'Özel gün görseli (16:10)'
						);
						?>
					</span>
					<span class="df-occasion__body">
						<span class="df-occasion__title"><?php echo esc_html( $item['title'] ); ?></span>
						<?php if ( ! empty( $item['text'] ) ) : ?>
							<span class="df-occasion__text"><?php echo esc_html( $item['text'] ); ?></span>
						<?php endif; ?>
						<?php if ( $url ) : ?>
							<span class="df-occasion__cta" aria-hidden="true"><?php df_the_icon( 'arrow-right', array( 'size' => 18 ) ); ?></span>
						<?php endif; ?>
					</span>
				</<?php echo $url ? 'a' : 'div'; ?>>
			<?php endforeach; ?>
		</div>
	</div>
</section>
