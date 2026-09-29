<?php
/**
 * Template Name: Banka Hesapları
 *
 * Hesaplar "Banka Hesapları" menüsünden yönetilir. IBAN kopyalama butonu içerir.
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
			'subtitle' => ce_page_subtitle( $ce_id ),
			'eyebrow'  => ce_meta( $ce_id, 'hero_eyebrow' ),
			'image'    => ce_meta( $ce_id, 'hero_image' ),
		)
	);
	$ce_banks = ce_get_items( 'ce_bank', 20 );
	?>
	<section class="ce-section">
		<div class="ce-container">
			<?php if ( $ce_banks ) : ?>
				<div class="ce-banks">
					<?php foreach ( $ce_banks as $ce_i => $ce_b ) : ?>
						<?php
						$ce_iban = ce_meta( $ce_b->ID, 'iban' );
						$ce_raw  = preg_replace( '/\s+/', '', (string) $ce_iban );
						$ce_logo = get_post_thumbnail_id( $ce_b );
						$ce_rows = array(
							'Ünvan'   => ce_meta( $ce_b->ID, 'holder' ),
							'Şube'    => ce_meta( $ce_b->ID, 'branch' ),
							'Şube No' => ce_meta( $ce_b->ID, 'branch_code' ),
							'Hesap No' => ce_meta( $ce_b->ID, 'account_no' ),
							'Para birimi' => ce_meta( $ce_b->ID, 'currency', 'TRY' ),
						);
						?>
						<article class="ce-bank ce-bank--<?php echo esc_attr( ce_meta( $ce_b->ID, 'color', 'dark' ) ); ?>" data-reveal style="--i:<?php echo (int) $ce_i; ?>">
							<header class="ce-bank__head">
								<?php if ( $ce_logo ) : ?>
									<span class="ce-bank__logo"><?php echo ce_img( $ce_logo, 'ce-logo', array( 'alt' => get_the_title( $ce_b ) ) ); // phpcs:ignore ?></span>
								<?php else : ?>
									<span class="ce-bank__icon"><?php echo ce_icon( 'bank', 24 ); // phpcs:ignore ?></span>
								<?php endif; ?>
								<h2 class="ce-bank__name"><?php echo esc_html( get_the_title( $ce_b ) ); ?></h2>
								<span class="ce-bank__chip" aria-hidden="true"></span>
							</header>
							<dl class="ce-bank__rows">
								<?php foreach ( $ce_rows as $ce_label => $ce_val ) : ?>
									<?php if ( $ce_val ) : ?>
										<div><dt><?php echo esc_html( $ce_label ); ?></dt><dd><?php echo esc_html( $ce_val ); ?></dd></div>
									<?php endif; ?>
								<?php endforeach; ?>
							</dl>
							<?php if ( $ce_iban ) : ?>
								<div class="ce-bank__iban">
									<span class="ce-bank__iban-label">IBAN</span>
									<code class="ce-bank__iban-value" translate="no"><?php echo esc_html( $ce_iban ); ?></code>
									<button type="button" class="ce-bank__copy" data-copy="<?php echo esc_attr( $ce_raw ); ?>" aria-label="<?php echo esc_attr( get_the_title( $ce_b ) . ' IBAN kopyala' ); ?>">
										<?php echo ce_icon( 'copy', 18 ); // phpcs:ignore ?><span>KOPYALA</span>
									</button>
								</div>
							<?php endif; ?>
						</article>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<div class="ce-empty-state"><?php echo ce_icon( 'bank', 28 ); // phpcs:ignore ?><p>Henüz banka hesabı eklenmedi.</p></div>
			<?php endif; ?>

			<?php if ( get_the_content() ) : ?>
				<div class="ce-note" data-reveal><?php echo ce_icon( 'info', 20 ); // phpcs:ignore ?><div class="ce-prose"><?php the_content(); ?></div></div>
			<?php endif; ?>
		</div>
	</section>
	<?php
endwhile;
get_footer();
