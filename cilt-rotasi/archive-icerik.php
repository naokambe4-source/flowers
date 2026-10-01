<?php
/**
 * İçerik sözlüğü: editoryal dizin (A–Z), öne çıkanlar, anlık filtre, gruplar.
 * İçerik grubu (icerik_grubu) arşivleri de bu şablonu kullanır.
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;

get_header();

$cr_term   = is_tax( 'icerik_grubu' ) ? get_queried_object() : null;
$cr_letter = array();
$cr_all    = array();
if ( have_posts() ) {
	while ( have_posts() ) {
		the_post();
		$cr_letter[ cr_first_letter( get_the_title() ) ][] = get_the_ID();
		$cr_all[] = get_the_ID();
	}
	rewind_posts();
}
$cr_alpha = array( 'A', 'B', 'C', 'Ç', 'D', 'E', 'F', 'G', 'H', 'I', 'İ', 'J', 'K', 'L', 'M', 'N', 'O', 'Ö', 'P', 'R', 'S', 'Ş', 'T', 'U', 'Ü', 'V', 'Y', 'Z' );
uksort(
	$cr_letter,
	function ( $a, $b ) use ( $cr_alpha ) {
		$ia = array_search( $a, $cr_alpha, true );
		$ib = array_search( $b, $cr_alpha, true );
		return ( false === $ia ? 99 : $ia ) <=> ( false === $ib ? 99 : $ib );
	}
);
$cr_groups = get_terms( array( 'taxonomy' => 'icerik_grubu', 'hide_empty' => true ) );
$cr_when   = array( 'sabah' => 'Sabah', 'aksam' => 'Akşam', 'ikisi' => 'Sabah & akşam' );

// Öne çıkanlar: en çok okunan 4 içerik.
$cr_featured = array();
if ( ! $cr_term && count( $cr_all ) > 4 ) {
	$cr_featured = get_posts(
		array(
			'post_type'      => 'icerik',
			'posts_per_page' => 4,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery
				'relation' => 'OR',
				'v'        => array( 'key' => '_cr_views', 'type' => 'NUMERIC' ),
				array( 'key' => '_cr_views', 'compare' => 'NOT EXISTS' ),
			),
			'orderby'        => array( 'v' => 'DESC', 'title' => 'ASC' ),
		)
	);
}
?>
<section class="cr-pagehead cr-pagehead--split">
	<div class="cr-container cr-pagehead__grid">
		<div class="cr-pagehead__copy">
			<?php cr_breadcrumb_html(); ?>
			<p class="cr-eyebrow cr-eyebrow--line">INCI Rehberi · <?php echo (int) count( $cr_all ); ?> içerik</p>
			<h1 class="cr-pagehead__title">
				<?php if ( $cr_term ) : ?>
					<?php echo esc_html( $cr_term->name ); ?>
				<?php else : ?>
					<?php cr_t( 'glossary_archive_title' ); ?><br><em class="cr-accent">A’dan Z’ye</em>
				<?php endif; ?>
			</h1>
			<p class="cr-pagehead__lead"><?php echo esc_html( $cr_term && $cr_term->description ? $cr_term->description : cr_opt( 'glossary_archive_text' ) ); ?></p>
			<div class="cr-linesearch cr-linesearch--light">
				<?php echo cr_icon( 'search', 22 ); // phpcs:ignore ?>
				<label class="screen-reader-text" for="cr-gl-q">Sözlükte ara</label>
				<input id="cr-gl-q" type="search" placeholder="<?php echo esc_attr( cr_opt( 'glossary_placeholder' ) ); ?>" data-glossary-filter autocomplete="off">
			</div>
		</div>
		<div class="cr-pagehead__media">
			<?php echo cr_img( $cr_term && cr_term_image( $cr_term ) ? cr_term_image( $cr_term ) : cr_default_img( 'actives' ), 'cr-card', array( 'alt' => '', 'loading' => 'eager', 'fetchpriority' => 'high' ) ); // phpcs:ignore ?>
		</div>
	</div>
</section>

<?php if ( $cr_featured ) : ?>
	<section class="cr-section cr-section--tight cr-ing-featured">
		<div class="cr-container">
			<header class="cr-head cr-head--split">
				<div>
					<p class="cr-eyebrow">Öne Çıkanlar</p>
					<h2 class="cr-h2">En çok merak edilenler</h2>
				</div>
			</header>
			<div class="cr-ing-featured__grid">
				<?php foreach ( $cr_featured as $fid ) : ?>
					<?php $fg = cr_primary_term( $fid ); ?>
					<a class="cr-ingf" href="<?php echo esc_url( get_permalink( $fid ) ); ?>">
						<span class="cr-ingf__media"><?php echo cr_ing_image( $fid, 'cr-card', array( 'alt' => '', 'class' => 'cr-zoom' ) ); // phpcs:ignore ?></span>
						<span class="cr-eyebrow cr-eyebrow--sm"><?php echo esc_html( $fg ? $fg->name : 'İçerik' ); ?></span>
						<span class="cr-ingf__name"><?php echo esc_html( get_the_title( $fid ) ); ?></span>
						<span class="cr-ingf__fn"><?php echo esc_html( get_post_meta( $fid, '_cr_function', true ) ); ?></span>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</section>
<?php endif; ?>

<div class="cr-filterbar" data-sticky-bar>
	<div class="cr-container cr-filterbar__inner">
		<nav class="cr-az" aria-label="Harf dizini">
			<?php foreach ( $cr_alpha as $l ) : ?>
				<?php if ( isset( $cr_letter[ $l ] ) ) : ?>
					<a href="#harf-<?php echo esc_attr( rawurlencode( $l ) ); ?>"><?php echo esc_html( $l ); ?></a>
				<?php else : ?>
					<span aria-hidden="true"><?php echo esc_html( $l ); ?></span>
				<?php endif; ?>
			<?php endforeach; ?>
		</nav>
		<?php if ( $cr_groups && ! is_wp_error( $cr_groups ) && ! $cr_term ) : ?>
			<div class="cr-tabs-line cr-tabs-line--sm" data-glossary-groups>
				<button type="button" class="is-active" data-group="">Tümü</button>
				<?php foreach ( $cr_groups as $g ) : ?>
					<button type="button" data-group="<?php echo esc_attr( $g->slug ); ?>"><?php echo esc_html( $g->name ); ?></button>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</div>

<section class="cr-section cr-section--tight cr-index">
	<div class="cr-container">
		<?php if ( $cr_letter ) : ?>
			<?php foreach ( $cr_letter as $letter => $ids ) : ?>
				<div class="cr-index__block" id="harf-<?php echo esc_attr( rawurlencode( $letter ) ); ?>" data-letter>
					<h2 class="cr-index__letter"><?php echo esc_html( $letter ); ?></h2>
					<ul class="cr-index__list">
						<?php foreach ( $ids as $id ) : ?>
							<?php
							$groups = wp_get_post_terms( $id, 'icerik_grubu' );
							$inci   = get_post_meta( $id, '_cr_inci', true );
							$aka    = get_post_meta( $id, '_cr_aka', true );
							$ev     = get_post_meta( $id, '_cr_evidence', true );
							$when   = get_post_meta( $id, '_cr_when', true );
							$slugs  = array();
							foreach ( (array) $groups as $g ) {
								if ( is_object( $g ) ) {
									$slugs[] = $g->slug;
								}
							}
							?>
							<li class="cr-entry" data-groups="<?php echo esc_attr( implode( ' ', $slugs ) ); ?>" data-search="<?php echo esc_attr( cr_lower( get_the_title( $id ) . ' ' . $inci . ' ' . $aka ) ); ?>">
								<a href="<?php echo esc_url( get_permalink( $id ) ); ?>">
									<span class="cr-entry__head">
										<span class="cr-entry__name"><?php echo esc_html( get_the_title( $id ) ); ?></span>
										<?php if ( $inci ) : ?>
											<span class="cr-entry__inci"><?php echo esc_html( $inci ); ?></span>
										<?php endif; ?>
									</span>
									<span class="cr-entry__fn"><?php echo esc_html( get_post_meta( $id, '_cr_function', true ) ); ?></span>
									<span class="cr-entry__meta">
										<?php if ( $groups && ! is_wp_error( $groups ) ) : ?>
											<span class="cr-entry__group"><?php echo esc_html( $groups[0]->name ); ?></span>
										<?php endif; ?>
										<?php if ( $ev ) : ?>
											<span class="cr-entry__ev" title="Kanıt düzeyi">Kanıt <?php echo cr_level_dots( $ev ); // phpcs:ignore ?></span>
										<?php endif; ?>
										<?php if ( isset( $cr_when[ $when ] ) ) : ?>
											<span><?php echo esc_html( $cr_when[ $when ] ); ?></span>
										<?php endif; ?>
									</span>
									<?php echo cr_icon( 'arrow-up-right', 20, 'cr-entry__go' ); // phpcs:ignore ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endforeach; ?>
			<p class="cr-empty" data-glossary-empty hidden>Eşleşen içerik bulunamadı. Başka bir yazımla dene (ör. “niacinamide” yerine “niasinamid”).</p>
		<?php else : ?>
			<div class="cr-empty-state"><?php echo cr_icon( 'flask', 40 ); // phpcs:ignore ?><p>Sözlük yakında dolmaya başlayacak.</p></div>
		<?php endif; ?>
	</div>
</section>

<section class="cr-section cr-section--tight cr-legend">
	<div class="cr-container cr-legend__grid">
		<div><p class="cr-eyebrow">Nasıl okunur?</p><h2 class="cr-h3">Her içerik aynı künyeyle</h2></div>
		<dl>
			<div><dt>INCI adı</dt><dd>Etiketteki uluslararası içerik adı; ürün listesinde bunu ararsın.</dd></div>
			<div><dt>Kanıt düzeyi</dt><dd><?php echo cr_level_dots( 3 ); // phpcs:ignore ?> Hakemli çalışmaların gücü: sınırlı, orta, güçlü.</dd></div>
			<div><dt>Etkili oran</dt><dd>Araştırmalarda fayda gösteren konsantrasyon aralığı.</dd></div>
		</dl>
	</div>
</section>
<?php
get_footer();
