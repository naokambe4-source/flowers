<?php
/**
 * Hizmet detayı: hero, özet, kapak, detaylı açıklama, avantajlar, uygulama alanları,
 * teknik bilgiler, galeri, ilgili hizmetler ve teklif CTA.
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

get_header();
while ( have_posts() ) :
	the_post();
	$ce_id      = get_the_ID();
	$ce_tone    = ce_service_tone( $ce_id );
	$ce_terms   = get_the_terms( $ce_id, 'ce_service_cat' );
	$ce_cat     = ( $ce_terms && ! is_wp_error( $ce_terms ) ) ? $ce_terms[0] : null;
	$ce_adv     = ce_lines( ce_meta( $ce_id, 'advantages' ) );
	$ce_app     = ce_lines( ce_meta( $ce_id, 'applications' ) );
	$ce_specs   = (array) ce_meta( $ce_id, 'specs', array() );
	$ce_tech    = ce_meta( $ce_id, 'technical' );
	$ce_gallery = (array) ce_meta( $ce_id, 'gallery', array() );
	$ce_hero    = absint( ce_meta( $ce_id, 'hero_image' ) );
	$ce_hero    = $ce_hero ? $ce_hero : get_post_thumbnail_id();
	$ce_quote   = add_query_arg( 'hizmet', get_post_field( 'post_name', $ce_id ), ce_quote_url() ) . '#form';

	get_template_part(
		'template-parts/components/page-hero',
		null,
		array(
			'title'    => get_the_title(),
			'subtitle' => ce_meta( $ce_id, 'summary' ),
			'eyebrow'  => $ce_cat ? $ce_cat->name : 'Hizmet',
			'image'    => $ce_hero,
			'tone'     => $ce_tone,
			'size'     => 'lg',
			'meta'     => ce_button( 'Teklif Al', $ce_quote, 'primary', 'arrow-right' ) . ( ce_opt( 'phone' ) ? '<a class="ce-btn ce-btn--ghost-light" href="' . esc_attr( ce_tel() ) . '">' . ce_icon( 'phone', 18, 'ce-btn__icon' ) . '<span>' . esc_html( ce_opt( 'phone' ) ) . '</span></a>' : '' ),
		)
	);
	?>

	<section class="ce-section">
		<div class="ce-container ce-service">
			<div class="ce-service__main">
				<div class="ce-service__cover" data-reveal>
					<?php echo ce_post_visual( $ce_id, 'ce-wide', $ce_tone, array( 'sizes' => '(min-width: 1024px) 60vw, 100vw', 'loading' => 'eager' ) ); // phpcs:ignore ?>
				</div>
				<article class="ce-prose ce-entry" data-reveal>
					<?php the_content(); ?>
				</article>

				<?php if ( $ce_adv || $ce_app ) : ?>
					<div class="ce-service__lists">
						<?php if ( $ce_adv ) : ?>
							<div class="ce-listcard" data-reveal>
								<h2 class="ce-listcard__title"><?php echo ce_icon( 'badge-check', 22 ); // phpcs:ignore ?>Avantajlar</h2>
								<ul class="ce-checklist">
									<?php foreach ( $ce_adv as $ce_a ) : ?>
										<li><?php echo ce_icon( 'check', 16 ); // phpcs:ignore ?><span><?php echo esc_html( $ce_a ); ?></span></li>
									<?php endforeach; ?>
								</ul>
							</div>
						<?php endif; ?>
						<?php if ( $ce_app ) : ?>
							<div class="ce-listcard ce-listcard--dark" data-reveal>
								<h2 class="ce-listcard__title"><?php echo ce_icon( 'factory', 22 ); // phpcs:ignore ?>Uygulama alanları</h2>
								<ul class="ce-checklist">
									<?php foreach ( $ce_app as $ce_a ) : ?>
										<li><?php echo ce_icon( 'arrow-right', 16 ); // phpcs:ignore ?><span><?php echo esc_html( $ce_a ); ?></span></li>
									<?php endforeach; ?>
								</ul>
							</div>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<?php if ( $ce_specs || $ce_tech ) : ?>
					<section class="ce-specs" data-reveal aria-labelledby="ce-specs-title">
						<h2 id="ce-specs-title" class="ce-specs__title">Teknik bilgiler</h2>
						<?php if ( $ce_specs ) : ?>
							<dl class="ce-specs__table">
								<?php foreach ( $ce_specs as $ce_s ) : ?>
									<?php if ( ! empty( $ce_s['label'] ) ) : ?>
										<div><dt><?php echo esc_html( $ce_s['label'] ); ?></dt><dd><?php echo esc_html( $ce_s['value'] ?? '' ); ?></dd></div>
									<?php endif; ?>
								<?php endforeach; ?>
							</dl>
						<?php endif; ?>
						<?php if ( $ce_tech ) : ?>
							<div class="ce-prose ce-specs__content"><?php echo wp_kses_post( wpautop( $ce_tech ) ); ?></div>
						<?php endif; ?>
					</section>
				<?php endif; ?>

				<?php if ( $ce_gallery ) : ?>
					<section class="ce-service__gallery" aria-labelledby="ce-sg-title">
						<h2 id="ce-sg-title" class="ce-specs__title">Galeri</h2>
						<?php get_template_part( 'template-parts/components/gallery-grid', null, array( 'items' => $ce_gallery, 'group' => 'service-' . $ce_id ) ); ?>
					</section>
				<?php endif; ?>
			</div>

			<aside class="ce-service__aside">
				<div class="ce-aside-card ce-aside-card--sticky">
					<p class="ce-eyebrow">Teklif alın</p>
					<h2 class="ce-aside-card__title"><?php echo esc_html( ce_meta( $ce_id, 'cta_title', ce_opt( 'service_cta_title' ) ) ); ?></h2>
					<p>Parça bilgilerinizi ve varsa teknik resminizi paylaşın, size dönüş yapalım.</p>
					<?php echo ce_button( 'Teklif Al', $ce_quote, 'primary', 'arrow-right' ); // phpcs:ignore ?>
					<ul class="ce-aside-card__links">
						<?php if ( ce_opt( 'phone' ) ) : ?>
							<li><a href="<?php echo esc_attr( ce_tel() ); ?>"><?php echo ce_icon( 'phone', 18 ); // phpcs:ignore ?><?php echo esc_html( ce_opt( 'phone' ) ); ?></a></li>
						<?php endif; ?>
						<?php if ( ce_whatsapp_url() ) : ?>
							<li><a href="<?php echo esc_url( ce_whatsapp_url( 'Merhaba, ' . get_the_title() . ' hizmetiniz hakkında bilgi almak istiyorum.' ) ); ?>" target="_blank" rel="noopener"><?php echo ce_icon( 'whatsapp', 18 ); // phpcs:ignore ?>WhatsApp ile sorun</a></li>
						<?php endif; ?>
					</ul>
				</div>
				<?php
				$ce_all = ce_get_items( 'ce_service', 20, array( 'fields' => 'ids' ) );
				if ( count( $ce_all ) > 1 ) :
					?>
					<nav class="ce-aside-card ce-aside-card--list" aria-label="Diğer hizmetler">
						<p class="ce-eyebrow">Tüm hizmetler</p>
						<ul>
							<?php foreach ( $ce_all as $ce_sid ) : ?>
								<li><a href="<?php echo esc_url( get_permalink( $ce_sid ) ); ?>" <?php echo $ce_sid === $ce_id ? 'aria-current="page" class="is-active"' : ''; ?>><?php echo esc_html( get_the_title( $ce_sid ) ); ?><?php echo ce_icon( 'arrow-right', 14 ); // phpcs:ignore ?></a></li>
							<?php endforeach; ?>
						</ul>
					</nav>
				<?php endif; ?>
			</aside>
		</div>
	</section>

	<?php
	// İlgili hizmetler: aynı kategoriden, yoksa diğerlerinden.
	$ce_related_args = array( 'post__not_in' => array( $ce_id ) );
	if ( $ce_cat ) {
		$ce_related_args['tax_query'] = array( array( 'taxonomy' => 'ce_service_cat', 'terms' => $ce_cat->term_id ) ); // phpcs:ignore
	}
	$ce_related = ce_get_items( 'ce_service', 3, $ce_related_args );
	if ( count( $ce_related ) < 3 ) {
		$ce_more    = ce_get_items( 'ce_service', 3 - count( $ce_related ), array( 'post__not_in' => array_merge( array( $ce_id ), wp_list_pluck( $ce_related, 'ID' ) ) ) );
		$ce_related = array_merge( $ce_related, $ce_more );
	}
	if ( $ce_related ) :
		?>
		<section class="ce-section ce-section--tint">
			<div class="ce-container">
				<?php ce_section_head( array( 'eyebrow' => 'İlgili hizmetler', 'title' => 'Diğer yüzey işlem çözümlerimiz', 'link' => ce_services_url(), 'link_label' => 'Tüm hizmetler' ) ); ?>
				<div class="ce-services__grid ce-services__grid--archive">
					<?php
					foreach ( $ce_related as $ce_i => $ce_r ) {
						get_template_part( 'template-parts/components/service-card', null, array( 'post' => $ce_r, 'size' => 'md', 'index' => $ce_i ) );
					}
					?>
				</div>
			</div>
		</section>
	<?php endif; ?>
	<?php
endwhile;
get_footer();
