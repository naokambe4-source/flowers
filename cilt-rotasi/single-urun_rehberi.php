<?php
/**
 * Ürün incelemesi: ürün sergisi + künye + artılar/dikkat + içerik.
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$pid     = get_the_ID();
	$type    = cr_primary_term( $pid );
	$brand   = cr_meta( '_cr_brand' );
	$rating  = (float) cr_meta( '_cr_rating' );
	$price   = cr_meta( '_cr_price' );
	$ingr    = cr_lines( cr_meta( '_cr_key_ingr' ) );
	$pros    = cr_lines( cr_meta( '_cr_pros' ) );
	$cons    = cr_lines( cr_meta( '_cr_cons' ) );
	$indep   = '0' !== (string) get_post_meta( $pid, '_cr_independent', true );
	$content = apply_filters( 'the_content', get_the_content() ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
	$specs   = array_filter(
		array(
			'Marka'       => $brand,
			'Hacim'       => cr_meta( '_cr_size' ),
			'Doku'        => cr_meta( '_cr_texture' ),
			'Kimler için' => cr_meta( '_cr_for' ),
			'Fiyat'       => $price ? str_repeat( '₺', (int) $price ) . ' · ' . array( '1' => 'Uygun', '2' => 'Orta', '3' => 'Yüksek' )[ $price ] : '',
		)
	);
	?>
	<article <?php post_class( 'cr-prodpage' ); ?>>
		<section class="cr-prodhero">
			<div class="cr-container">
				<?php cr_breadcrumb_html(); ?>
				<div class="cr-prodhero__grid">
					<figure class="cr-prodhero__media">
						<?php echo cr_post_image( $pid, 'cr-hero', array( 'loading' => 'eager', 'fetchpriority' => 'high', 'alt' => get_the_title() ) ); // phpcs:ignore ?>
						<?php if ( $indep ) : ?>
							<figcaption class="cr-prodhero__badge"><?php echo cr_icon( 'shield', 14 ); // phpcs:ignore ?> Sponsorlu değil</figcaption>
						<?php endif; ?>
					</figure>
					<div class="cr-prodhero__info">
						<p class="cr-eyebrow cr-eyebrow--line"><?php echo esc_html( $type ? $type->name : 'Ürün rehberi' ); ?></p>
						<h1 class="cr-prodhero__title cr-article__title"><?php the_title(); ?></h1>
						<?php if ( $brand ) : ?>
							<p class="cr-prodhero__brand"><?php echo esc_html( $brand ); ?></p>
						<?php endif; ?>
						<?php if ( $rating ) : ?>
							<div class="cr-prodhero__rating" aria-label="<?php echo esc_attr( 'Editör puanı ' . $rating . ' / 5' ); ?>">
								<span class="cr-stars">
									<?php for ( $s = 1; $s <= 5; $s++ ) : ?>
										<span class="<?php echo $s <= floor( $rating ) ? 'on' : ( $s - 0.5 <= $rating ? 'half' : '' ); ?>"><?php echo cr_icon( 'star', 16 ); // phpcs:ignore ?></span>
									<?php endfor; ?>
								</span>
								<strong><?php echo esc_html( str_replace( '.', ',', (string) $rating ) ); ?></strong><span>/ 5 editör puanı</span>
							</div>
						<?php endif; ?>
						<?php if ( cr_meta( '_cr_verdict' ) ) : ?>
							<blockquote class="cr-prodhero__verdict cr-short-answer__text">“<?php echo esc_html( cr_meta( '_cr_verdict' ) ); ?>”</blockquote>
						<?php endif; ?>
						<?php if ( $specs ) : ?>
							<dl class="cr-specrows">
								<?php foreach ( $specs as $k => $v ) : ?>
									<div><dt><?php echo esc_html( $k ); ?></dt><dd><?php echo esc_html( $v ); ?></dd></div>
								<?php endforeach; ?>
							</dl>
						<?php endif; ?>
						<div class="cr-prodhero__actions">
							<?php if ( cr_meta( '_cr_info_link' ) ) : ?>
								<a class="cr-btn cr-btn--solid" href="<?php echo esc_url( cr_meta( '_cr_info_link' ) ); ?>" target="_blank" rel="nofollow noopener">Resmî Ürün Sayfası</a>
							<?php endif; ?>
							<?php echo cr_save_button( $pid, 'cr-linebtn', true ); // phpcs:ignore ?>
							<button type="button" class="cr-linebtn" data-share data-title="<?php echo esc_attr( get_the_title() ); ?>" data-url="<?php echo esc_url( get_permalink() ); ?>"><?php echo cr_icon( 'share', 18 ); // phpcs:ignore ?><span>Paylaş</span></button>
						</div>
					</div>
				</div>
			</div>
		</section>

		<?php if ( $ingr ) : ?>
			<section class="cr-section cr-section--tight cr-keyingr">
				<div class="cr-container">
					<header class="cr-head cr-head--split">
						<div><p class="cr-eyebrow">Formül</p><h2 class="cr-h2">Öne çıkan içerikler</h2></div>
						<a class="cr-link-arrow" href="<?php echo esc_url( get_post_type_archive_link( 'icerik' ) ); ?>">İçerik sözlüğü <?php echo cr_icon( 'arrow-right', 16 ); // phpcs:ignore ?></a>
					</header>
					<ul class="cr-keyingr__grid">
						<?php foreach ( $ingr as $i => $n ) : ?>
							<?php
							$l   = cr_ingredient_link( $n );
							$lid = $l ? url_to_postid( $l ) : 0;
							$fn  = $lid ? get_post_meta( $lid, '_cr_function', true ) : '';
							$tag = $l ? 'a href="' . esc_url( $l ) . '"' : 'div';
							?>
							<li>
								<<?php echo $tag; // phpcs:ignore ?> class="cr-keyingr__item">
									<span class="cr-keyingr__num"><?php echo esc_html( str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
									<span class="cr-keyingr__name"><?php echo esc_html( $n ); ?></span>
									<?php if ( $fn ) : ?>
										<span class="cr-keyingr__fn"><?php echo esc_html( $fn ); ?></span>
									<?php endif; ?>
									<?php echo $l ? cr_icon( 'arrow-up-right', 18, 'cr-keyingr__go' ) : ''; // phpcs:ignore ?>
								</<?php echo $l ? 'a' : 'div'; ?>>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( $pros || $cons ) : ?>
			<section class="cr-procon">
				<div class="cr-container cr-procon__grid">
					<?php if ( $pros ) : ?>
						<div class="cr-procon__col">
							<p class="cr-eyebrow">Artıları</p>
							<ul><?php foreach ( $pros as $p ) : ?><li><span aria-hidden="true">+</span><?php echo esc_html( $p ); ?></li><?php endforeach; ?></ul>
						</div>
					<?php endif; ?>
					<?php if ( $cons ) : ?>
						<div class="cr-procon__col cr-procon__col--con">
							<p class="cr-eyebrow">Dikkat edilmesi gerekenler</p>
							<ul><?php foreach ( $cons as $p ) : ?><li><span aria-hidden="true">−</span><?php echo esc_html( $p ); ?></li><?php endforeach; ?></ul>
						</div>
					<?php endif; ?>
				</div>
			</section>
		<?php endif; ?>

		<div class="cr-container cr-container--narrow cr-prodpage__body">
			<?php if ( cr_meta( '_cr_usage' ) ) : ?>
				<section class="cr-usage">
					<p class="cr-eyebrow">Nasıl kullanılır?</p>
					<p><?php echo esc_html( cr_meta( '_cr_usage' ) ); ?></p>
				</section>
			<?php endif; ?>
			<?php if ( trim( wp_strip_all_tags( $content ) ) ) : ?>
				<h2 class="cr-h3 cr-prodpage__h">Editör notu</h2>
				<div class="cr-prose entry-content"><?php echo $content; // phpcs:ignore ?></div>
			<?php endif; ?>
			<?php get_template_part( 'template-parts/parts/article-extras' ); ?>
			<?php if ( cr_opt( 'art_author' ) ) : ?>
				<?php get_template_part( 'template-parts/parts/author-box' ); ?>
			<?php endif; ?>
		</div>

		<?php
		$similar = get_posts(
			array(
				'post_type'      => 'urun_rehberi',
				'posts_per_page' => 4,
				'post__not_in'   => array( $pid ),
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'tax_query'      => $type ? array( array( 'taxonomy' => 'urun_turu', 'terms' => array( $type->term_id ) ) ) : array(), // phpcs:ignore
			)
		);
		if ( count( $similar ) < 4 ) {
			$similar = array_merge( $similar, get_posts( array( 'post_type' => 'urun_rehberi', 'posts_per_page' => 4 - count( $similar ), 'post__not_in' => array_merge( $similar, array( $pid ) ), 'fields' => 'ids', 'no_found_rows' => true ) ) );
		}
		?>
		<?php if ( $similar ) : ?>
			<section class="cr-section cr-section--alt">
				<div class="cr-container">
					<header class="cr-head cr-head--split">
						<div><p class="cr-eyebrow">Katalog</p><h2 class="cr-h2">Benzer ürünler</h2></div>
						<a class="cr-link-arrow" href="<?php echo esc_url( get_post_type_archive_link( 'urun_rehberi' ) ); ?>">Tüm ürünler <?php echo cr_icon( 'arrow-right', 16 ); // phpcs:ignore ?></a>
					</header>
					<div class="cr-catalog__grid">
						<?php
						foreach ( $similar as $sid ) {
							get_template_part( 'template-parts/cards/product', null, array( 'post_id' => $sid ) );
						}
						?>
					</div>
				</div>
			</section>
		<?php endif; ?>
	</article>
	<?php
endwhile;

get_footer();
