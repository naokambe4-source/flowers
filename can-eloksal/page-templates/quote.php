<?php
/**
 * Template Name: Teklif Al
 *
 * Dosya yüklemeli teklif formu. Dosyalar sunucuda doğrulanır (uzantı + MIME + içerik imzası)
 * ve web'den erişilemeyen gizli klasöre rastgele adla kaydedilir.
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;


get_header();
while ( have_posts() ) :
	the_post();
	$ce_id = get_the_ID();
	get_template_part(
		'template-parts/components/page-hero',
		null,
		array(
			'title'    => ce_page_title( $ce_id ),
			'src'      => ce_page_hero_src( $ce_id ),
			'subtitle' => ce_page_subtitle( $ce_id ),
			'eyebrow'  => ce_meta( $ce_id, 'hero_eyebrow' ),
			'image'    => ce_meta( $ce_id, 'hero_image' ),
		)
	);
	?>
	<section class="ce-section">
		<div class="ce-container ce-quote">
			<?php get_template_part( 'template-parts/components/form-quote' ); ?>

			<aside class="ce-quote__aside">
				<div class="ce-aside-card">
					<h2 class="ce-aside-card__title"<?php echo ce_ed( 'meta:' . $ce_id . ':aside_title' ); // phpcs:ignore ?>><?php echo esc_html( ce_meta( $ce_id, 'aside_title', 'Teklif süreci' ) ); ?></h2>
					<?php $ce_steps = ce_lines( ce_meta( $ce_id, 'aside_steps' ) ); ?>
					<?php if ( $ce_steps ) : ?>
						<ol class="ce-mini-steps">
							<?php foreach ( $ce_steps as $ce_i => $ce_step ) : ?>
								<li><span><?php echo esc_html( sprintf( '%02d', $ce_i + 1 ) ); ?></span><?php echo esc_html( $ce_step ); ?></li>
							<?php endforeach; ?>
						</ol>
					<?php endif; ?>
					<?php if ( ce_meta( $ce_id, 'aside_note' ) ) : ?>
						<p class="ce-aside-card__note"><?php echo ce_icon( 'info', 18 ); // phpcs:ignore ?><span<?php echo ce_ed( 'meta:' . $ce_id . ':aside_note' ); // phpcs:ignore ?>><?php echo esc_html( ce_meta( $ce_id, 'aside_note' ) ); ?></span></p>
					<?php endif; ?>
					<?php if ( get_the_content() ) : ?>
						<div class="ce-prose"><?php the_content(); ?></div>
					<?php endif; ?>
				</div>
				<div class="ce-aside-card ce-aside-card--dark">
					<p class="ce-eyebrow ce-eyebrow--light">Hızlı iletişim</p>
					<?php if ( ce_opt( 'phone' ) ) : ?>
						<a class="ce-aside-card__phone" href="<?php echo esc_attr( ce_tel() ); ?>"><?php echo ce_icon( 'phone', 20 ); // phpcs:ignore ?><?php echo esc_html( ce_opt( 'phone' ) ); ?></a>
					<?php endif; ?>
					<?php if ( ce_whatsapp_url() ) : ?>
						<a class="ce-btn ce-btn--ghost-light ce-btn--block" href="<?php echo esc_url( ce_whatsapp_url() ); ?>" target="_blank" rel="noopener"><?php echo ce_icon( 'whatsapp', 18, 'ce-btn__icon' ); // phpcs:ignore ?><span>WhatsApp ile yazın</span></a>
					<?php endif; ?>
				</div>
			</aside>
		</div>
	</section>
	<?php
endwhile;
get_footer();
