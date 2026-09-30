<?php
/**
 * Ana sayfa — Renkli kategori bannerları (renkli kart ya da hazır banner görseli).
 * Görseli olmayan bannerlar gösterilmez (canlı düzenleyicide yöneticiye görünür).
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

$items = array();
foreach ( (array) df_opt( 'ban_items', array() ) as $i => $row ) {
	if ( ! is_array( $row ) ) {
		continue;
	}
	if ( empty( $row['image'] ) && ! df_live() ) {
		continue;
	}
	$row['_i'] = $i;
	$items[]   = $row;
}
if ( ! $items ) {
	return;
}
$cols   = max( 1, min( 3, (int) df_opt( 'ban_cols', 2 ) ) );
$radius = absint( df_opt( 'ban_radius', 18 ) );
?>
<section data-df-sec="banners" class="df-section df-banners" style="--df-ban-radius:<?php echo (int) $radius; ?>px" aria-label="Kategoriler">
	<div class="df-container">
		<?php if ( df_opt( 'ban_title' ) ) : ?>
			<?php df_section_head( array( 'title' => df_opt( 'ban_title' ), 'keys' => array( 'title' => 'ban_title' ) ) ); ?>
		<?php endif; ?>
		<div class="df-banners__grid df-banners__grid--<?php echo (int) $cols; ?>">
			<?php foreach ( $items as $b ) : ?>
				<?php
				$b    = wp_parse_args( $b, array( 'mode' => 'card', 'url' => '', 'image' => 0, 'image_mobile' => 0, 'title' => '', 'text' => '', 'pill' => '', 'badge' => '', 'icon' => '', 'bg' => '#F3E7E1', 'color' => '#6F463C', 'accent' => '', 'font' => 'sans', 'size' => 'm' ) );
				$k    = 'ban_items.' . $b['_i'];
				$url  = $b['url'] ? df_url( $b['url'] ) : '';
				$tag  = $url ? 'a' : 'div';
				$href = $url ? ' href="' . esc_url( $url ) . '"' : '';
				$bg   = sanitize_hex_color( $b['bg'] ) ? sanitize_hex_color( $b['bg'] ) : '#F3E7E1';
				$fg   = sanitize_hex_color( $b['color'] ) ? sanitize_hex_color( $b['color'] ) : '#6F463C';
				?>
				<?php if ( 'image' === $b['mode'] ) : ?>
					<<?php echo $tag . $href; // phpcs:ignore ?> class="df-banner df-banner--image"<?php echo df_i( $k . '.image' ); // phpcs:ignore ?>>
						<?php if ( $b['image_mobile'] ) : ?>
							<picture>
								<source media="(max-width: 767px)" srcset="<?php echo esc_url( df_img_url( $b['image_mobile'], 'large' ) ); ?>">
								<?php echo df_image( $b['image'], 'df-banner', array( 'alt' => wp_strip_all_tags( $b['title'] ) ) ); // phpcs:ignore ?>
							</picture>
						<?php else : ?>
							<?php echo df_image( $b['image'], 'df-banner', array( 'alt' => wp_strip_all_tags( $b['title'] ), 'sizes' => '(max-width: 767px) 100vw, ' . round( 100 / $cols ) . 'vw' ), 'Hazır banner görseli ekleyin' ); // phpcs:ignore ?>
						<?php endif; ?>
					</<?php echo $tag; // phpcs:ignore ?>>
				<?php else : ?>
					<?php
					$ac    = sanitize_hex_color( $b['accent'] ) ? sanitize_hex_color( $b['accent'] ) : $fg;
					$lines = preg_split( '/\r\n|\r|\n/', trim( (string) $b['title'] ) );
					$last  = count( $lines ) > 1 ? array_pop( $lines ) : '';
					$cls   = 'df-banner df-banner--card df-banner--' . ( 'serif' === $b['font'] ? 'serif' : 'sans' ) . ' df-banner--size-' . sanitize_key( $b['size'] ? $b['size'] : 'm' );
					?>
					<<?php echo $tag . $href; // phpcs:ignore ?> class="<?php echo esc_attr( $cls ); ?>" style="--b-bg:<?php echo esc_attr( $bg ); ?>;--b-fg:<?php echo esc_attr( $fg ); ?>;--b-ac:<?php echo esc_attr( $ac ); ?>">
						<span class="df-banner__media"<?php echo df_i( $k . '.image' ); // phpcs:ignore ?>>
							<?php echo df_image( $b['image'], 'df-portrait', array( 'sizes' => '(max-width: 767px) 55vw, 30vw', 'alt' => '' ), 'Fotoğraf' ); // phpcs:ignore ?>
						</span>
						<span class="df-banner__body">
							<?php if ( $b['icon'] ) : ?>
								<span class="df-banner__icon" aria-hidden="true"><?php df_the_icon( 'sprig', array( 'class' => 'df-banner__sprig' ) ); ?><?php df_the_icon( $b['icon'], array( 'size' => 40 ) ); ?><?php df_the_icon( 'sprig', array( 'class' => 'df-banner__sprig is-flip' ) ); ?></span>
							<?php endif; ?>
							<span class="df-banner__head">
								<?php df_the_icon( 'sprig', array( 'class' => 'df-banner__sprig df-banner__sprig--side' ) ); ?>
								<span class="df-banner__title"<?php echo df_e( $k . '.title' ); // phpcs:ignore ?>><?php foreach ( $lines as $line ) : ?><span class="df-banner__line"><?php echo esc_html( $line ); ?></span><?php endforeach; ?><?php if ( '' !== $last ) : ?><span class="df-banner__line df-banner__line--ac"><?php echo esc_html( $last ); ?></span><?php endif; ?></span>
								<?php df_the_icon( 'sprig', array( 'class' => 'df-banner__sprig df-banner__sprig--side is-flip' ) ); ?>
							</span>
							<span class="df-banner__rule" aria-hidden="true"><?php df_the_icon( 'heart', array( 'size' => 12 ) ); ?></span>
							<?php if ( $b['text'] || df_live() ) : ?>
								<span class="df-banner__text"<?php echo df_e( $k . '.text' ); // phpcs:ignore ?>><?php echo esc_html( $b['text'] ); ?></span>
							<?php endif; ?>
							<?php if ( $b['pill'] ) : ?>
								<span class="df-banner__pill"><?php df_the_icon( 'truck', array( 'size' => 22 ) ); ?><span<?php echo df_e( $k . '.pill' ); // phpcs:ignore ?>><?php echo esc_html( $b['pill'] ); ?></span></span>
							<?php endif; ?>
						</span>
						<?php if ( $b['badge'] ) : ?>
							<?php $bl = preg_split( '/\r\n|\r|\n/', trim( (string) $b['badge'] ) ); ?>
							<span class="df-banner__badge"><?php foreach ( $bl as $bi => $line ) : ?><span class="<?php echo count( $bl ) - 1 === $bi ? 'df-banner__badge-big' : 'df-banner__badge-sm'; ?>"><?php echo esc_html( $line ); ?></span><?php endforeach; ?></span>
						<?php endif; ?>
					</<?php echo $tag; // phpcs:ignore ?>>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
	</div>
</section>
