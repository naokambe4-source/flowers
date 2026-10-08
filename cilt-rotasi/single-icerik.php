<?php
/**
 * İçerik sözlüğü tekil sayfası: editoryal künye (DefinedTerm).
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$pid      = get_the_ID();
	$inci     = cr_meta( '_cr_inci' );
	$aka      = cr_meta( '_cr_aka' );
	$fn       = cr_meta( '_cr_function' );
	$short    = cr_meta( '_cr_short_answer' ) ? cr_meta( '_cr_short_answer' ) : $fn;
	$benefits = cr_lines( cr_meta( '_cr_benefits' ) );
	$pairs    = cr_lines( cr_meta( '_cr_pairs' ) );
	$avoid    = cr_lines( cr_meta( '_cr_avoid' ) );
	$group    = cr_primary_term( $pid );
	$when_map = array( 'sabah' => 'Sabah', 'aksam' => 'Akşam', 'ikisi' => 'Sabah ve akşam' );
	$preg_map = array( 'uygun' => 'Genel olarak uygun', 'danis' => 'Hekime danışılmalı', 'kacin' => 'Önerilmez' );
	$lvl      = array( '1' => 'Sınırlı', '2' => 'Orta', '3' => 'Güçlü' );
	$irr      = array( '1' => 'Düşük', '2' => 'Orta', '3' => 'Yüksek' );
	$content  = apply_filters( 'the_content', get_the_content() ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals
	$products = cr_products_on() ? cr_products_with_ingredient( get_the_title(), $inci, 3 ) : array();

	$facts = array();
	if ( cr_meta( '_cr_skin_types' ) ) {
		$facts[] = array( 'Uygun cilt tipleri', esc_html( cr_meta( '_cr_skin_types' ) ), 'wide' );
	}
	if ( cr_meta( '_cr_conc' ) ) {
		$facts[] = array( 'Etkili oran', esc_html( cr_meta( '_cr_conc' ) ), '' );
	}
	if ( isset( $when_map[ cr_meta( '_cr_when' ) ] ) ) {
		$facts[] = array( 'Ne zaman?', esc_html( $when_map[ cr_meta( '_cr_when' ) ] ), '' );
	}
	if ( isset( $preg_map[ cr_meta( '_cr_pregnancy' ) ] ) ) {
		$facts[] = array( 'Hamilelik & emzirme', esc_html( $preg_map[ cr_meta( '_cr_pregnancy' ) ] ), 'kacin' === cr_meta( '_cr_pregnancy' ) ? 'warn' : '' );
	}
	if ( isset( $lvl[ cr_meta( '_cr_evidence' ) ] ) ) {
		$facts[] = array( 'Bilimsel kanıt', cr_level_dots( cr_meta( '_cr_evidence' ) ) . esc_html( $lvl[ cr_meta( '_cr_evidence' ) ] ), '' );
	}
	if ( isset( $irr[ cr_meta( '_cr_irritation' ) ] ) ) {
		$facts[] = array( 'Tahriş potansiyeli', cr_level_dots( cr_meta( '_cr_irritation' ), 'cr-dots--warn' ) . esc_html( $irr[ cr_meta( '_cr_irritation' ) ] ), '' );
	}
	?>
	<article <?php post_class( 'cr-ingpage' ); ?>>
		<header class="cr-pagehead cr-pagehead--split cr-pagehead--ing">
			<div class="cr-container cr-pagehead__grid">
				<div class="cr-pagehead__copy">
					<?php cr_breadcrumb_html(); ?>
					<p class="cr-eyebrow cr-eyebrow--line"><?php echo esc_html( $group ? $group->name : 'İçerik Sözlüğü' ); ?></p>
					<h1 class="cr-pagehead__title cr-article__title"><?php the_title(); ?></h1>
					<?php if ( $inci || $aka ) : ?>
						<p class="cr-ingpage__inci">
							<?php if ( $inci ) : ?>
								<span><b>INCI</b> <?php echo esc_html( $inci ); ?></span>
							<?php endif; ?>
							<?php if ( $aka ) : ?>
								<span><b>Diğer adları</b> <?php echo esc_html( $aka ); ?></span>
							<?php endif; ?>
						</p>
					<?php endif; ?>
					<?php if ( $short ) : ?>
						<div class="cr-answer">
							<p class="cr-eyebrow cr-eyebrow--sm">Kısa cevap · Ne işe yarar?</p>
							<p class="cr-answer__text cr-short-answer__text"><?php echo esc_html( $short ); ?></p>
						</div>
					<?php endif; ?>
					<div class="cr-ingpage__actions">
						<?php echo cr_save_button( $pid, 'cr-linebtn', true ); // phpcs:ignore ?>
						<button type="button" class="cr-linebtn" data-share data-title="<?php echo esc_attr( get_the_title() ); ?>" data-url="<?php echo esc_url( get_permalink() ); ?>"><?php echo cr_icon( 'share', 18 ); // phpcs:ignore ?><span>Paylaş</span></button>
					</div>
				</div>
				<figure class="cr-pagehead__media">
					<?php echo cr_ing_image( $pid, 'cr-card', array( 'loading' => 'eager', 'fetchpriority' => 'high' ) ); // phpcs:ignore ?>
				</figure>
			</div>
		</header>

		<?php if ( $facts ) : ?>
			<section class="cr-spec" aria-label="İçerik künyesi">
				<div class="cr-container">
					<dl class="cr-spec__grid">
						<?php foreach ( $facts as $f ) : ?>
							<div class="cr-spec__cell<?php echo $f[2] ? ' is-' . esc_attr( $f[2] ) : ''; ?>">
								<dt class="cr-eyebrow cr-eyebrow--sm"><?php echo esc_html( $f[0] ); ?></dt>
								<dd><?php echo wp_kses_post( $f[1] ); ?></dd>
							</div>
						<?php endforeach; ?>
					</dl>
				</div>
			</section>
		<?php endif; ?>

		<div class="cr-container cr-ingpage__layout">
			<div class="cr-ingpage__main">
				<?php if ( $benefits ) : ?>
					<section class="cr-benefits">
						<h2 class="cr-h3">Faydaları</h2>
						<ol>
							<?php foreach ( $benefits as $i => $b ) : ?>
								<li><span><?php echo esc_html( str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span><?php echo esc_html( $b ); ?></li>
							<?php endforeach; ?>
						</ol>
					</section>
				<?php endif; ?>

				<div class="cr-prose entry-content">
					<?php echo $content; // phpcs:ignore ?>
				</div>

				<?php get_template_part( 'template-parts/parts/article-extras' ); ?>

				<p class="cr-updated"><?php echo cr_icon( 'refresh', 16 ); // phpcs:ignore ?> Son güncelleme: <time datetime="<?php echo esc_attr( mysql2date( 'c', cr_updated( $pid ) ) ); ?>"><?php echo esc_html( cr_date( cr_updated( $pid ) ) ); ?></time></p>
			</div>

			<aside class="cr-ingpage__aside">
				<div class="cr-ingpage__sticky">
					<?php if ( $pairs || $avoid ) : ?>
						<div class="cr-combo">
							<p class="cr-eyebrow">Uyum rehberi</p>
							<?php if ( $pairs ) : ?>
								<p class="cr-combo__title">İyi anlaşır</p>
								<ul class="cr-combo__list">
									<?php foreach ( $pairs as $p ) : ?>
										<?php $l = cr_ingredient_link( $p ); ?>
										<li class="is-good"><span aria-hidden="true">+</span><?php echo $l ? '<a href="' . esc_url( $l ) . '">' . esc_html( $p ) . '</a>' : esc_html( $p ); ?></li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
							<?php if ( $avoid ) : ?>
								<p class="cr-combo__title">Dikkatli kombinle</p>
								<ul class="cr-combo__list">
									<?php foreach ( $avoid as $p ) : ?>
										<?php $l = cr_ingredient_link( $p ); ?>
										<li class="is-warn"><span aria-hidden="true">!</span><?php echo $l ? '<a href="' . esc_url( $l ) . '">' . esc_html( $p ) . '</a>' : esc_html( $p ); ?></li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
						</div>
					<?php endif; ?>

					<?php if ( $products ) : ?>
						<div class="cr-combo">
							<p class="cr-eyebrow">Bu içeriği barındıran ürünler</p>
							<ul class="cr-minilist">
								<?php foreach ( $products as $prid ) : ?>
									<li>
										<a href="<?php echo esc_url( get_permalink( $prid ) ); ?>">
											<span class="cr-minilist__img"><?php echo cr_post_image( $prid, 'cr-thumb', array( 'alt' => '' ) ); // phpcs:ignore ?></span>
											<span><strong><?php echo esc_html( get_the_title( $prid ) ); ?></strong><small><?php echo esc_html( get_post_meta( $prid, '_cr_brand', true ) ); ?></small></span>
										</a>
									</li>
								<?php endforeach; ?>
							</ul>
						</div>
					<?php endif; ?>

					<a class="cr-asidecta" href="<?php echo esc_url( cr_url( cr_opt( 'quiz_url' ) ) ); ?>">
						<span class="cr-eyebrow cr-eyebrow--sm">Cilt testi</span>
						<strong>Bu içerik cildine uygun mu?</strong>
						<span class="cr-btn-line">Teste Başla</span>
					</a>
				</div>
			</aside>
		</div>

		<?php
		$mentions = new WP_Query(
			array(
				'post_type'      => 'post',
				's'              => get_the_title(),
				'posts_per_page' => 3,
				'no_found_rows'  => true,
			)
		);
		$siblings = get_posts(
			array(
				'post_type'      => 'icerik',
				'posts_per_page' => 6,
				'post__not_in'   => array( $pid ),
				'orderby'        => 'rand',
				'no_found_rows'  => true,
				'tax_query'      => $group ? array( array( 'taxonomy' => 'icerik_grubu', 'terms' => array( $group->term_id ) ) ) : array(), // phpcs:ignore
			)
		);
		if ( count( $siblings ) < 3 ) {
			$siblings = get_posts( array( 'post_type' => 'icerik', 'posts_per_page' => 6, 'post__not_in' => array( $pid ), 'orderby' => 'rand', 'no_found_rows' => true ) );
		}
		?>
		<?php if ( $mentions->have_posts() ) : ?>
			<section class="cr-section cr-section--alt">
				<div class="cr-container">
					<header class="cr-head cr-head--split">
						<div><p class="cr-eyebrow">Journal</p><h2 class="cr-h2"><?php the_title(); ?> geçen rehberler</h2></div>
					</header>
					<div class="cr-journal__grid">
						<?php
						while ( $mentions->have_posts() ) {
							$mentions->the_post();
							get_template_part( 'template-parts/cards/card', null, array( 'variant' => 'journal' ) );
						}
						wp_reset_postdata();
						?>
					</div>
				</div>
			</section>
		<?php endif; ?>
		<?php if ( $siblings ) : ?>
			<section class="cr-section cr-section--tight">
				<div class="cr-container">
					<header class="cr-head cr-head--split">
						<div><p class="cr-eyebrow">Sözlük</p><h2 class="cr-h2">Benzer içerikler</h2></div>
						<a class="cr-link-arrow" href="<?php echo esc_url( get_post_type_archive_link( 'icerik' ) ); ?>">Tüm sözlük <?php echo cr_icon( 'arrow-right', 16 ); // phpcs:ignore ?></a>
					</header>
					<ul class="cr-index__list cr-index__list--2">
						<?php foreach ( $siblings as $s ) : ?>
							<li class="cr-entry">
								<a href="<?php echo esc_url( get_permalink( $s ) ); ?>">
									<span class="cr-entry__head">
										<span class="cr-entry__name"><?php echo esc_html( get_the_title( $s ) ); ?></span>
										<span class="cr-entry__inci"><?php echo esc_html( get_post_meta( $s->ID, '_cr_inci', true ) ); ?></span>
									</span>
									<span class="cr-entry__fn"><?php echo esc_html( get_post_meta( $s->ID, '_cr_function', true ) ); ?></span>
									<?php echo cr_icon( 'arrow-up-right', 20, 'cr-entry__go' ); // phpcs:ignore ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			</section>
		<?php endif; ?>
	</article>
	<?php
endwhile;

get_footer();
