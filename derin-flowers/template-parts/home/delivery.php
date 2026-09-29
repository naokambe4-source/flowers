<?php
/**
 * Ana sayfa — İzmir teslimat (sol tipografi, sağ görsel).
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

$title = df_opt( 'del_title' );
if ( ! $title ) {
	return;
}
$points = array_slice( df_lines( df_opt( 'del_points' ) ), 0, 3 );
$left   = function_exists( 'df_delivery_same_day_seconds_left' ) ? df_delivery_same_day_seconds_left() : 0;
?>
<section class="df-section df-delivery" aria-labelledby="df-del-title">
	<div class="df-container df-delivery__grid">
		<div class="df-delivery__text">
			<?php if ( df_opt( 'del_eyebrow' ) ) : ?>
				<p class="df-eyebrow"<?php echo df_e( 'del_eyebrow' ); // phpcs:ignore ?>><?php echo esc_html( df_opt( 'del_eyebrow' ) ); ?></p>
			<?php endif; ?>
			<h2 class="df-delivery__title" id="df-del-title"<?php echo df_e( 'del_title' ); // phpcs:ignore ?>><?php echo esc_html( $title ); ?></h2>
			<?php if ( df_opt( 'del_text' ) ) : ?>
				<p class="df-delivery__lead"<?php echo df_e( 'del_text' ); // phpcs:ignore ?>><?php echo df_nl2br( df_vars( df_opt( 'del_text' ) ) ); // phpcs:ignore ?></p>
			<?php endif; ?>
			<?php if ( $points ) : ?>
				<ol class="df-delivery__points">
					<?php foreach ( $points as $i => $point ) : ?>
						<li><span class="df-delivery__num"><?php echo esc_html( sprintf( '%02d', $i + 1 ) ); ?></span><span><?php echo esc_html( df_vars( $point ) ); ?></span></li>
					<?php endforeach; ?>
				</ol>
			<?php endif; ?>
			<?php if ( df_opt( 'del_btn' ) && df_opt( 'del_url' ) ) : ?>
				<?php echo df_button( df_opt( 'del_btn' ), df_opt( 'del_url' ), 'solid' ); // phpcs:ignore ?>
			<?php endif; ?>
		</div>
		<figure class="df-delivery__media"<?php echo df_i( 'del_image' ); // phpcs:ignore ?>>
			<?php echo df_image( df_opt( 'del_image' ), 'df-portrait', array( 'sizes' => '(max-width: 900px) 100vw, 50vw', 'alt' => $title ), 'Teslimat / hazırlık görseli' ); // phpcs:ignore ?>
			<?php if ( $left > 0 ) : ?>
				<figcaption class="df-delivery__badge" data-df-countdown="<?php echo esc_attr( $left ); ?>">
					<?php df_the_icon( 'clock', array( 'size' => 20 ) ); ?>
					<span>Bugün teslimat için<br><strong data-df-countdown-text><?php echo esc_html( df_format_duration( $left ) ); ?></strong> kaldı</span>
				</figcaption>
			<?php endif; ?>
		</figure>
	</div>
</section>
