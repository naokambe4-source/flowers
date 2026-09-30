<?php
/**
 * Ana sayfa — tam genişlik hero (tek görsel ya da slider).
 * Görseller panelden gelir; PHP içinde sabit görsel yoktur.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

$slides = array_values( array_filter( (array) df_opt( 'hero_slides', array() ), 'is_array' ) );
if ( ! $slides ) {
	return;
}
$count    = count( $slides );
$height   = df_opt( 'hero_height', 'tall' );
$autoplay = df_opt( 'hero_autoplay' ) && $count > 1;
?>
<section data-df-sec="hero" class="df-hero df-hero--<?php echo esc_attr( $height ); ?><?php echo $count > 1 ? ' is-slider' : ''; ?>"
	data-df-hero
	data-autoplay="<?php echo $autoplay ? '1' : '0'; ?>"
	data-interval="<?php echo esc_attr( max( 3, (int) df_opt( 'hero_interval', 7 ) ) * 1000 ); ?>"
	<?php if ( $count > 1 ) : ?>aria-roledescription="carousel"<?php endif; ?>
	aria-label="Öne çıkanlar">

	<div class="df-hero__track">
		<?php foreach ( $slides as $i => $s ) : ?>
			<?php
			$s        = wp_parse_args(
				$s,
				array(
					'mode'         => 'text',
					'image'        => '',
					'image_mobile' => '',
					'video'        => '',
					'link'         => '',
					'alt'          => '',
					'eyebrow'      => '',
					'title'        => '',
					'text'         => '',
					'btn1_text'    => '',
					'btn1_url'     => '',
					'btn2_text'    => '',
					'btn2_url'     => '',
					'script'       => '',
					'align'        => 'left',
					'overlay'      => 'soft',
					'theme'        => 'dark',
					'position'     => 'center center',
				)
			);
			$desk     = df_img_url( $s['image'], 'df-hero' );
			$mob      = $s['image_mobile'] ? df_img_url( $s['image_mobile'], 'df-hero-mobile' ) : '';
			$vars     = '';
			if ( $desk ) {
				$vars .= '--df-hero-d:url(' . esc_url( $desk ) . ');';
				$vars .= '--df-hero-m:url(' . esc_url( $mob ? $mob : $desk ) . ');';
			}
			$vars    .= '--df-hero-pos:' . esc_attr( $s['position'] ) . ';';
			$classes  = array(
				'df-hero__slide',
				'df-hero__slide--' . sanitize_html_class( $s['align'] ),
				'df-hero__slide--' . sanitize_html_class( $s['theme'] ),
				'df-hero__slide--ov-' . sanitize_html_class( $s['overlay'] ),
				'is-' . sanitize_html_class( $s['mode'] ),
			);
			if ( 0 === $i ) {
				$classes[] = 'is-active';
			}
			if ( ! $desk ) {
				$classes[] = 'is-empty';
			}
			$alt       = $s['alt'] ? $s['alt'] : wp_strip_all_tags( $s['title'] );
			$title_tag = 0 === $i ? 'h1' : 'h2';
			?>
			<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
				<?php echo 0 === $i ? 'style="' . esc_attr( $vars ) . '"' : 'data-style="' . esc_attr( $vars ) . '"'; ?>
				<?php if ( $count > 1 ) : ?>role="group" aria-roledescription="slide" aria-label="<?php echo esc_attr( ( $i + 1 ) . ' / ' . $count ); ?>"<?php endif; ?>
				<?php echo 0 === $i ? '' : 'aria-hidden="true"'; ?>>

				<div class="df-hero__bg"<?php echo df_i( 'hero_slides.' . $i . '.image' ); // phpcs:ignore ?><?php echo $alt && $desk ? ' role="img" aria-label="' . esc_attr( $alt ) . '"' : ''; ?>>
					<?php if ( ! $desk ) : ?>
						<?php echo df_placeholder( 'Hero görseli ekleyin: Derin Flowers → Hero' ); // phpcs:ignore ?>
					<?php endif; ?>
				</div>

				<?php if ( $s['video'] ) : ?>
					<video class="df-hero__video" muted loop playsinline preload="none" <?php echo $desk ? 'poster="' . esc_url( $desk ) . '"' : ''; ?> data-src="<?php echo esc_url( $s['video'] ); ?>"></video>
				<?php endif; ?>

				<?php if ( 'image' === $s['mode'] ) : ?>
					<?php if ( $s['link'] ) : ?>
						<a class="df-hero__link" href="<?php echo esc_url( df_url( $s['link'] ) ); ?>"<?php echo 0 === $i ? '' : ' tabindex="-1"'; ?>><span class="screen-reader-text"><?php echo esc_html( $alt ? $alt : 'Koleksiyonu keşfet' ); ?></span></a>
					<?php endif; ?>
				<?php else : ?>
					<div class="df-hero__content">
						<div class="df-hero__inner">
							<?php if ( $s['eyebrow'] ) : ?>
								<p class="df-hero__eyebrow"<?php echo df_e( 'hero_slides.' . $i . '.eyebrow' ); // phpcs:ignore ?>><?php echo esc_html( $s['eyebrow'] ); ?></p>
							<?php endif; ?>
							<?php if ( $s['title'] ) : ?>
								<<?php echo $title_tag; // phpcs:ignore ?> class="df-hero__title"<?php echo df_e( 'hero_slides.' . $i . '.title' ); // phpcs:ignore ?>><?php echo df_nl2br( $s['title'] ); // phpcs:ignore ?></<?php echo $title_tag; // phpcs:ignore ?>>
							<?php endif; ?>
							<?php if ( $s['text'] ) : ?>
								<p class="df-hero__text"<?php echo df_e( 'hero_slides.' . $i . '.text' ); // phpcs:ignore ?>><?php echo df_nl2br( $s['text'] ); // phpcs:ignore ?></p>
							<?php endif; ?>
							<?php if ( ( $s['btn1_text'] && $s['btn1_url'] ) || ( $s['btn2_text'] && $s['btn2_url'] ) ) : ?>
								<div class="df-hero__actions">
									<?php echo df_button( $s['btn1_text'], $s['btn1_url'], 'light' === $s['theme'] ? 'white' : 'solid' ); // phpcs:ignore ?>
									<?php echo df_button( $s['btn2_text'], $s['btn2_url'], 'light' === $s['theme'] ? 'ghost-light' : 'outline' ); // phpcs:ignore ?>
								</div>
							<?php endif; ?>
						</div>
						<?php if ( $s['script'] ) : ?>
							<p class="df-hero__script"<?php echo df_e( 'hero_slides.' . $i . '.script' ); // phpcs:ignore ?>><?php echo df_nl2br( $s['script'] ); // phpcs:ignore ?></p>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>

	<?php if ( $count > 1 ) : ?>
		<div class="df-hero__controls">
			<button type="button" class="df-hero__arrow" data-df-hero-prev aria-label="Önceki"><?php df_the_icon( 'arrow-left', array( 'size' => 20 ) ); ?></button>
			<div class="df-hero__dots" role="tablist" aria-label="Slaytlar">
				<?php for ( $i = 0; $i < $count; $i++ ) : ?>
					<button type="button" class="df-hero__dot<?php echo 0 === $i ? ' is-active' : ''; ?>" data-df-hero-dot="<?php echo (int) $i; ?>" aria-label="<?php echo esc_attr( ( $i + 1 ) . '. slayt' ); ?>"<?php echo 0 === $i ? ' aria-current="true"' : ''; ?>><span></span></button>
				<?php endfor; ?>
			</div>
			<button type="button" class="df-hero__arrow" data-df-hero-next aria-label="Sonraki"><?php df_the_icon( 'arrow-right', array( 'size' => 20 ) ); ?></button>
		</div>
	<?php endif; ?>
</section>
