<?php
/**
 * Ana sayfa — asimetrik ikili editorial koleksiyon (%58 / %42).
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

$cards = array();
foreach ( array( 1, 2 ) as $n ) {
	$title = df_opt( 'duo' . $n . '_title' );
	if ( ! $title ) {
		continue;
	}
	$cards[ $n ] = array(
		'n'       => $n,
		'image'   => df_opt( 'duo' . $n . '_image' ),
		'eyebrow' => df_opt( 'duo' . $n . '_eyebrow' ),
		'title'   => $title,
		'text'    => df_opt( 'duo' . $n . '_text' ),
		'btn'     => df_opt( 'duo' . $n . '_btn' ),
		'url'     => df_opt( 'duo' . $n . '_url' ),
	);
}
if ( ! $cards ) {
	return;
}
?>
<section class="df-section df-duo" aria-label="Koleksiyonlar">
	<div class="df-container">
		<div class="df-duo__grid">
			<?php foreach ( $cards as $n => $card ) : ?>
				<article class="df-duo__card df-duo__card--<?php echo 1 === $n ? 'wide' : 'narrow'; ?>">
					<a class="df-duo__media" href="<?php echo esc_url( df_url( $card['url'] ) ); ?>" tabindex="-1" aria-hidden="true"<?php echo df_i( 'duo' . $n . '_image' ); // phpcs:ignore ?>>
						<?php
						echo df_image( // phpcs:ignore
							$card['image'],
							1 === $n ? 'df-wide' : 'df-portrait',
							array(
								'sizes' => 1 === $n ? '(max-width: 800px) 100vw, 58vw' : '(max-width: 800px) 100vw, 42vw',
								'alt'   => $card['title'],
							),
							1 === $n ? 'Sol kart görseli (yatay)' : 'Sağ kart görseli (dikey)'
						);
						?>
					</a>
					<div class="df-duo__body">
						<?php if ( $card['eyebrow'] ) : ?>
							<p class="df-eyebrow"<?php echo df_e( 'duo' . $n . '_eyebrow' ); // phpcs:ignore ?>><?php echo esc_html( $card['eyebrow'] ); ?></p>
						<?php endif; ?>
						<h2 class="df-duo__title"><a<?php echo df_e( 'duo' . $n . '_title' ); // phpcs:ignore ?> href="<?php echo esc_url( df_url( $card['url'] ) ); ?>"><?php echo esc_html( $card['title'] ); ?></a></h2>
						<?php if ( $card['text'] ) : ?>
							<p class="df-duo__text"<?php echo df_e( 'duo' . $n . '_text' ); // phpcs:ignore ?>><?php echo esc_html( $card['text'] ); ?></p>
						<?php endif; ?>
						<?php if ( $card['btn'] && $card['url'] ) : ?>
							<a class="df-link-arrow" href="<?php echo esc_url( df_url( $card['url'] ) ); ?>"><?php echo esc_html( $card['btn'] ); ?><?php df_the_icon( 'arrow-right', array( 'size' => 18 ) ); ?></a>
						<?php endif; ?>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
