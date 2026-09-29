<?php
/**
 * Ana sayfa hero / slider. Slaytlar "Hero / Slider" menüsünden yönetilir.
 * ?ce_preview_slide=ID (nonce'lu) ile taslak slayt yalnızca yetkili kullanıcıya önizlenir.
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

$ce_slides  = array();
$ce_preview = isset( $_GET['ce_preview_slide'] ) ? absint( $_GET['ce_preview_slide'] ) : 0; // phpcs:ignore
$ce_nonce   = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : ''; // phpcs:ignore

if ( $ce_preview && current_user_can( 'edit_post', $ce_preview ) && wp_verify_nonce( $ce_nonce, 'ce_preview_slide_' . $ce_preview ) && 'ce_slide' === get_post_type( $ce_preview ) ) {
	$ce_posts = array( get_post( $ce_preview ) );
} else {
	$ce_posts = ce_get_items( 'ce_slide', 8 );
}

foreach ( $ce_posts as $ce_p ) {
	$ce_slides[] = array(
		'title'   => $ce_p->post_title,
		'eyebrow' => ce_meta( $ce_p->ID, 'eyebrow' ),
		'text'    => ce_meta( $ce_p->ID, 'description' ),
		'tags'    => ce_lines( ce_meta( $ce_p->ID, 'tags' ) ),
		'image'   => (int) get_post_thumbnail_id( $ce_p ),
		'mobile'  => absint( ce_meta( $ce_p->ID, 'mobile_image' ) ),
		'video'   => ce_meta( $ce_p->ID, 'video' ),
		'btn1'    => array( ce_meta( $ce_p->ID, 'btn1_label' ), ce_meta( $ce_p->ID, 'btn1_url' ) ),
		'btn2'    => array( ce_meta( $ce_p->ID, 'btn2_label' ), ce_meta( $ce_p->ID, 'btn2_url' ) ),
		'overlay' => (int) ce_meta( $ce_p->ID, 'overlay', 55 ),
		'align'   => ce_meta( $ce_p->ID, 'align', 'left' ),
	);
}

// Yayınlanmış slayt yoksa Tema Ayarları'ndaki hero içeriği.
if ( ! $ce_slides ) {
	$ce_slides[] = array(
		'title'   => str_replace( "\n", '|', ce_opt( 'hero_title' ) ),
		'eyebrow' => ce_opt( 'hero_eyebrow' ),
		'text'    => ce_opt( 'hero_text' ),
		'tags'    => ce_lines( ce_opt( 'hero_tags' ) ),
		'image'   => absint( ce_opt( 'hero_image' ) ),
		'mobile'  => 0,
		'video'   => '',
		'btn1'    => array( 'Hizmetleri İncele', ce_services_url() ),
		'btn2'    => array( 'Teklif Al', ce_quote_url() ),
		'overlay' => 55,
		'align'   => 'left',
	);
}

$ce_count = count( $ce_slides );
?>
<section class="ce-hero" data-hero <?php echo $ce_count > 1 ? 'aria-roledescription="carousel" aria-label="Öne çıkanlar"' : ''; ?>>
	<div class="ce-hero__slides">
		<?php foreach ( $ce_slides as $ce_i => $ce_s ) : ?>
			<?php
			$ce_lines_t = array_filter( array_map( 'trim', explode( '|', $ce_s['title'] ) ) );
			$ce_tag     = 0 === $ce_i ? 'h1' : 'h2';
			$ce_align   = 'center' === $ce_s['align'] ? 'center' : 'left';
			?>
			<article class="ce-hero__slide ce-hero__slide--<?php echo esc_attr( $ce_align ); ?><?php echo 0 === $ce_i ? ' is-active' : ''; ?>"
				style="--ce-overlay:<?php echo esc_attr( (string) ( max( 0, min( 90, $ce_s['overlay'] ) ) / 100 ) ); ?>"
				<?php echo $ce_count > 1 ? 'aria-roledescription="slide" aria-label="' . esc_attr( ( $ce_i + 1 ) . ' / ' . $ce_count ) . '"' : ''; ?>
				<?php echo 0 === $ce_i ? '' : 'aria-hidden="true"'; ?>>
				<div class="ce-hero__media">
					<?php if ( $ce_s['image'] ) : ?>
						<picture>
							<?php if ( $ce_s['mobile'] ) : ?>
								<source media="(max-width: 767px)" srcset="<?php echo esc_attr( (string) wp_get_attachment_image_srcset( $ce_s['mobile'], 'large' ) ); ?>" sizes="100vw">
							<?php endif; ?>
							<?php
							echo ce_img( // phpcs:ignore
								$ce_s['image'],
								'ce-hero',
								array(
									'class'        => 'ce-hero__img',
									'sizes'        => '100vw',
									'loading'      => 0 === $ce_i ? 'eager' : 'lazy',
									'fallback_alt' => wp_strip_all_tags( implode( ' ', $ce_lines_t ) ),
								)
							);
							?>
						</picture>
					<?php else : ?>
						<div class="ce-hero__art" aria-hidden="true"><span></span><span></span><span></span></div>
					<?php endif; ?>
					<?php if ( $ce_s['video'] && preg_match( '/\.(mp4|webm)(\?.*)?$/i', $ce_s['video'] ) ) : ?>
						<video class="ce-hero__video" muted loop playsinline preload="none" data-src="<?php echo esc_url( $ce_s['video'] ); ?>" aria-hidden="true"></video>
					<?php endif; ?>
				</div>

				<div class="ce-hero__content">
					<?php if ( $ce_s['eyebrow'] ) : ?>
						<p class="ce-eyebrow ce-eyebrow--light ce-hero__eyebrow"><?php echo esc_html( $ce_s['eyebrow'] ); ?></p>
					<?php endif; ?>
					<<?php echo esc_html( $ce_tag ); ?> class="ce-hero__title">
						<?php foreach ( $ce_lines_t as $ce_li => $ce_line ) : ?>
							<span class="ce-hero__line" style="--d:<?php echo (int) $ce_li; ?>"><?php echo esc_html( $ce_line ); ?></span>
						<?php endforeach; ?>
					</<?php echo esc_html( $ce_tag ); ?>>
					<?php if ( $ce_s['text'] ) : ?>
						<p class="ce-hero__text"><?php echo esc_html( $ce_s['text'] ); ?></p>
					<?php endif; ?>
					<div class="ce-hero__actions">
						<?php echo ce_button( $ce_s['btn1'][0], $ce_s['btn1'][1], 'primary', 'arrow-right' ); // phpcs:ignore ?>
						<?php echo ce_button( $ce_s['btn2'][0], $ce_s['btn2'][1], 'ghost-light', 'arrow-up-right' ); // phpcs:ignore ?>
					</div>
					<?php if ( $ce_s['tags'] ) : ?>
						<ul class="ce-hero__tags" aria-label="Sektörler">
							<?php foreach ( $ce_s['tags'] as $ce_t ) : ?>
								<li><?php echo esc_html( $ce_t ); ?></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
			</article>
		<?php endforeach; ?>
	</div>

	<div class="ce-hero__bar">
		<?php if ( $ce_count > 1 ) : ?>
			<div class="ce-hero__controls">
				<button type="button" class="ce-hero__arrow" data-hero-prev><?php echo ce_icon( 'arrow-left', 18 ); // phpcs:ignore ?><span class="screen-reader-text">Önceki slayt</span></button>
				<div class="ce-hero__dots" role="tablist" aria-label="Slayt seç">
					<?php for ( $ce_d = 0; $ce_d < $ce_count; $ce_d++ ) : ?>
						<button type="button" role="tab" class="ce-hero__dot<?php echo 0 === $ce_d ? ' is-active' : ''; ?>" aria-selected="<?php echo 0 === $ce_d ? 'true' : 'false'; ?>" data-hero-dot="<?php echo (int) $ce_d; ?>"><span class="screen-reader-text"><?php echo (int) $ce_d + 1; ?>. slayt</span><i></i></button>
					<?php endfor; ?>
				</div>
				<button type="button" class="ce-hero__arrow" data-hero-next><?php echo ce_icon( 'arrow-right', 18 ); // phpcs:ignore ?><span class="screen-reader-text">Sonraki slayt</span></button>
				<button type="button" class="ce-hero__arrow ce-hero__pause" data-hero-pause aria-pressed="false"><?php echo ce_icon( 'pause', 16 ); // phpcs:ignore ?><span class="screen-reader-text">Otomatik geçişi durdur</span></button>
			</div>
		<?php endif; ?>
		<a class="ce-hero__scroll" href="#ce-after-hero"><span>Keşfet</span><i aria-hidden="true"></i></a>
	</div>
</section>
<span id="ce-after-hero" class="ce-anchor"></span>
