<?php
/**
 * Ana sayfa — Instagram (6 kare görsel, sade).
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

$images = array_slice(
	array_values(
		array_filter(
			(array) df_opt( 'ig_images', array() ),
			function ( $i ) {
				return ! empty( $i['image'] );
			}
		)
	),
	0,
	6
);
if ( ! $images && ! current_user_can( 'edit_theme_options' ) ) {
	return;
}
$url = df_opt( 'ig_url' );
?>
<section class="df-section df-insta" aria-labelledby="df-ig-title">
	<div class="df-container">
		<header class="df-head df-head--center">
			<div class="df-head__text">
				<h2 class="df-head__title df-insta__handle" id="df-ig-title">
					<?php if ( $url ) : ?>
						<a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( df_opt( 'ig_handle' ) ); ?></a>
					<?php else : ?>
						<?php echo esc_html( df_opt( 'ig_handle' ) ); ?>
					<?php endif; ?>
				</h2>
				<?php if ( df_opt( 'ig_sub' ) ) : ?>
					<p class="df-head__sub"><?php echo esc_html( df_opt( 'ig_sub' ) ); ?></p>
				<?php endif; ?>
			</div>
		</header>
		<?php if ( $images ) : ?>
			<ul class="df-insta__grid">
				<?php foreach ( $images as $img ) : ?>
					<?php $link = ! empty( $img['link'] ) ? $img['link'] : $url; ?>
					<li>
						<a href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener" aria-label="Instagram gönderisini aç">
							<?php echo df_image( $img['image'], 'df-square', array( 'sizes' => '(max-width: 700px) 33vw, 16vw', 'alt' => '' ) ); // phpcs:ignore ?>
							<span class="df-insta__icon" aria-hidden="true"><?php df_the_icon( 'instagram', array( 'size' => 24 ) ); ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<p class="df-admin-hint">Instagram görselleri eklenmedi — Derin Flowers → Ana Sayfa → Instagram. (Bu uyarıyı yalnızca yöneticiler görür.)</p>
		<?php endif; ?>
	</div>
</section>
