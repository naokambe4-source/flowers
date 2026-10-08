<?php
/**
 * Template Name: Aktif İçerikler (landing)
 *
 * Ana sayfadaki “Aktif İçerikler” kartının açılış sayfası. Üstte sayfa yazısı (editörde yazılır),
 * altında içerik grupları ve sözlükten öne çıkan aktifler otomatik listelenir.
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	// Görsel: sayfanın öne çıkan görseli, yoksa ana sayfadaki Aktif İçerikler kartının görseli.
	$cr_img = has_post_thumbnail() ? get_post_thumbnail_id() : '';
	if ( ! $cr_img ) {
		foreach ( (array) cr_opt( 'cat_items' ) as $it ) {
			if ( ! empty( $it['image'] ) && false !== mb_strpos( cr_tr_lower( isset( $it['title'] ) ? $it['title'] : '' ), 'aktif' ) ) {
				$cr_img = $it['image'];
				break;
			}
		}
	}
	if ( ! $cr_img ) {
		$cr_img = cr_default_img( 'actives' );
	}
	$cr_lead   = has_excerpt() ? get_the_excerpt() : 'Asitler, vitaminler, peptitler ve bariyer lipidleri: etiketteki aktiflerin ne yaptığını, kime uygun olduğunu ve nasıl kombinleneceğini sade bir dille anlatıyoruz.';
	$cr_groups = get_terms(
		array(
			'taxonomy'   => 'icerik_grubu',
			'hide_empty' => true,
			'orderby'    => 'count',
			'order'      => 'DESC',
		)
	);
	$cr_actives = get_posts(
		array(
			'post_type'      => 'icerik',
			'posts_per_page' => 8,
			'meta_key'       => '_cr_views', // phpcs:ignore WordPress.DB.SlowDBQuery
			'orderby'        => array(
				'meta_value_num' => 'DESC',
				'title'          => 'ASC',
			),
			'no_found_rows'  => true,
		)
	);
	if ( count( $cr_actives ) < 4 ) {
		$cr_actives = get_posts(
			array(
				'post_type'      => 'icerik',
				'posts_per_page' => 8,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'no_found_rows'  => true,
			)
		);
	}
	$cr_sozluk = get_post_type_archive_link( 'icerik' );
	?>
	<article <?php post_class( 'cr-landing' ); ?>>
		<section class="cr-pagehead cr-pagehead--split">
			<div class="cr-container cr-pagehead__grid">
				<div class="cr-pagehead__copy">
					<?php cr_breadcrumb_html(); ?>
					<p class="cr-eyebrow cr-eyebrow--line">03 / Bilim</p>
					<h1 class="cr-pagehead__title"><?php the_title(); ?></h1>
					<p class="cr-pagehead__lead"><?php echo esc_html( $cr_lead ); ?></p>
					<div class="cr-hero__ctas">
						<a class="cr-btn cr-btn--solid" href="#aktif-gruplar">Grupları Keşfet</a>
						<a class="cr-btn-line" href="<?php echo esc_url( $cr_sozluk ); ?>">Sözlükte Ara</a>
					</div>
				</div>
				<figure class="cr-pagehead__media">
					<?php echo cr_img( $cr_img, 'cr-card', array( 'alt' => get_the_title(), 'loading' => 'eager', 'fetchpriority' => 'high' ) ); // phpcs:ignore ?>
				</figure>
			</div>
		</section>

		<?php if ( '' !== trim( wp_strip_all_tags( get_the_content() ) ) ) : ?>
			<section class="cr-section">
				<div class="cr-container cr-container--narrow">
					<div class="cr-prose entry-content">
						<?php the_content(); ?>
					</div>
					<?php get_template_part( 'template-parts/parts/article-extras' ); ?>
				</div>
			</section>
		<?php elseif ( cr_can_edit() ) : ?>
			<section class="cr-section cr-section--tight">
				<div class="cr-container cr-container--narrow">
					<p class="cr-empty-state">Bu alana yazacağın giriş metni burada görünecek. <a class="cr-btn-line" href="<?php echo esc_url( get_edit_post_link() ); ?>">Sayfayı düzenle</a></p>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( $cr_groups && ! is_wp_error( $cr_groups ) ) : ?>
			<section class="cr-section cr-keyingr" id="aktif-gruplar">
				<div class="cr-container">
					<header class="cr-head cr-head--split">
						<div>
							<p class="cr-eyebrow">Gruplar</p>
							<h2 class="cr-h2">Aktifleri Ailesiyle Tanı</h2>
						</div>
						<a class="cr-link-arrow" href="<?php echo esc_url( $cr_sozluk ); ?>">Tüm Sözlük <?php echo cr_icon( 'arrow-right', 16 ); // phpcs:ignore ?></a>
					</header>
					<ul class="cr-keyingr__grid">
						<?php foreach ( $cr_groups as $i => $g ) : ?>
							<li>
								<a class="cr-keyingr__item" href="<?php echo esc_url( get_term_link( $g ) ); ?>">
									<span class="cr-keyingr__num"><?php echo esc_html( str_pad( (string) ( $i + 1 ), 2, '0', STR_PAD_LEFT ) ); ?> · <?php echo esc_html( $g->count ); ?> içerik</span>
									<span class="cr-keyingr__name"><?php echo esc_html( $g->name ); ?></span>
									<?php if ( $g->description ) : ?>
										<span class="cr-keyingr__fn"><?php echo esc_html( wp_trim_words( $g->description, 18 ) ); ?></span>
									<?php endif; ?>
									<?php echo cr_icon( 'arrow-up-right', 18, 'cr-keyingr__go' ); // phpcs:ignore ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( $cr_actives ) : ?>
			<section class="cr-section cr-ing-featured">
				<div class="cr-container">
					<header class="cr-head">
						<p class="cr-eyebrow">Sözlükten</p>
						<h2 class="cr-h2">En Çok Merak Edilen Aktifler</h2>
					</header>
					<div class="cr-ing-featured__grid">
						<?php foreach ( $cr_actives as $a ) : ?>
							<?php $groups = get_the_terms( $a->ID, 'icerik_grubu' ); ?>
							<a class="cr-ingf" href="<?php echo esc_url( get_permalink( $a ) ); ?>">
								<span class="cr-ingf__media"><?php echo cr_ing_image( $a->ID, 'cr-card', array( 'alt' => '', 'class' => 'cr-zoom' ) ); // phpcs:ignore ?></span>
								<?php if ( $groups && ! is_wp_error( $groups ) ) : ?>
									<span class="cr-eyebrow cr-eyebrow--sm"><?php echo esc_html( $groups[0]->name ); ?></span>
								<?php endif; ?>
								<span class="cr-ingf__name"><?php echo esc_html( get_the_title( $a ) ); ?></span>
								<?php if ( get_post_meta( $a->ID, '_cr_function', true ) ) : ?>
									<span class="cr-ingf__fn"><?php echo esc_html( get_post_meta( $a->ID, '_cr_function', true ) ); ?></span>
								<?php endif; ?>
							</a>
						<?php endforeach; ?>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<section class="cr-newsletter">
			<div class="cr-container cr-newsletter__grid">
				<div>
					<p class="cr-eyebrow">İçerik Sözlüğü</p>
					<h2 class="cr-h2">Etiketteki Her Molekül, Bir Cümlede.</h2>
				</div>
				<div>
					<p class="cr-newsletter__text">Aradığın aktif burada yoksa sözlükte A’dan Z’ye tüm içeriklere göz at.</p>
					<a class="cr-btn cr-btn--solid cr-landing__cta" href="<?php echo esc_url( $cr_sozluk ); ?>">Sözlüğe Git</a>
				</div>
			</div>
		</section>
	</article>
	<?php
endwhile;

get_footer();
