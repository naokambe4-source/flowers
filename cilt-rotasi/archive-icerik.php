<?php
/**
 * İçerik sözlüğü: A–Z dizini, anlık filtre, gruplar.
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;

get_header();

$cr_by_letter = array();
if ( have_posts() ) {
	while ( have_posts() ) {
		the_post();
		$cr_by_letter[ cr_first_letter( get_the_title() ) ][] = get_the_ID();
	}
	rewind_posts();
}
$cr_alpha = array( 'A', 'B', 'C', 'Ç', 'D', 'E', 'F', 'G', 'H', 'I', 'İ', 'J', 'K', 'L', 'M', 'N', 'O', 'Ö', 'P', 'R', 'S', 'Ş', 'T', 'U', 'Ü', 'V', 'Y', 'Z' );
uksort(
	$cr_by_letter,
	function ( $a, $b ) use ( $cr_alpha ) {
		$ia = array_search( $a, $cr_alpha, true );
		$ib = array_search( $b, $cr_alpha, true );
		$ia = false === $ia ? 99 : $ia;
		$ib = false === $ib ? 99 : $ib;
		return $ia <=> $ib;
	}
);
$cr_groups = get_terms( array( 'taxonomy' => 'icerik_grubu', 'hide_empty' => true ) );
?>
<section class="cr-archive-hero cr-archive-hero--glossary">
	<div class="cr-container">
		<?php cr_breadcrumb_html(); ?>
		<div class="cr-archive-hero__copy cr-archive-hero__copy--center">
			<p class="cr-eyebrow"><?php echo cr_icon( 'flask', 14 ); // phpcs:ignore ?> INCI rehberi · <?php echo (int) wp_count_posts( 'icerik' )->publish; ?> içerik</p>
			<h1 class="cr-archive-hero__title"><?php cr_t( 'glossary_archive_title' ); ?></h1>
			<div class="cr-archive-hero__desc"><p><?php cr_t( 'glossary_archive_text' ); ?></p></div>
			<div class="cr-bigsearch cr-bigsearch--center">
				<?php echo cr_icon( 'search', 24 ); // phpcs:ignore ?>
				<label class="screen-reader-text" for="cr-gl-q">Sözlükte ara</label>
				<input id="cr-gl-q" type="search" placeholder="<?php echo esc_attr( cr_opt( 'glossary_placeholder' ) ); ?>" data-glossary-filter autocomplete="off">
			</div>
		</div>
		<nav class="cr-az cr-az--sticky" aria-label="Harf dizini">
			<?php foreach ( $cr_alpha as $l ) : ?>
				<?php if ( isset( $cr_by_letter[ $l ] ) ) : ?>
					<a href="#harf-<?php echo esc_attr( rawurlencode( $l ) ); ?>"><?php echo esc_html( $l ); ?></a>
				<?php else : ?>
					<span aria-hidden="true"><?php echo esc_html( $l ); ?></span>
				<?php endif; ?>
			<?php endforeach; ?>
		</nav>
		<?php if ( $cr_groups && ! is_wp_error( $cr_groups ) ) : ?>
			<div class="cr-filters cr-filters--center" data-glossary-groups>
				<button type="button" class="cr-filter is-active" data-group="">Tümü</button>
				<?php foreach ( $cr_groups as $g ) : ?>
					<button type="button" class="cr-filter" data-group="<?php echo esc_attr( $g->slug ); ?>"><?php echo esc_html( $g->name ); ?></button>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>

<section class="cr-section cr-section--tight">
	<div class="cr-container">
		<?php if ( $cr_by_letter ) : ?>
			<?php foreach ( $cr_by_letter as $letter => $ids ) : ?>
				<div class="cr-letter" id="harf-<?php echo esc_attr( rawurlencode( $letter ) ); ?>" data-letter>
					<h2 class="cr-letter__head"><?php echo esc_html( $letter ); ?></h2>
					<div class="cr-ing-grid">
						<?php foreach ( $ids as $id ) : ?>
							<?php
							$groups = wp_get_post_terms( $id, 'icerik_grubu', array( 'fields' => 'slugs' ) );
							$inci   = get_post_meta( $id, '_cr_inci', true );
							$aka    = get_post_meta( $id, '_cr_aka', true );
							?>
							<a class="cr-ing-card" href="<?php echo esc_url( get_permalink( $id ) ); ?>" data-groups="<?php echo esc_attr( is_array( $groups ) ? implode( ' ', $groups ) : '' ); ?>" data-search="<?php echo esc_attr( cr_lower( get_the_title( $id ) . ' ' . $inci . ' ' . $aka ) ); ?>">
								<span class="cr-ing-card__name"><?php echo esc_html( get_the_title( $id ) ); ?></span>
								<?php if ( $inci ) : ?>
									<span class="cr-ing-card__inci"><?php echo esc_html( $inci ); ?></span>
								<?php endif; ?>
								<span class="cr-ing-card__fn"><?php echo esc_html( get_post_meta( $id, '_cr_function', true ) ); ?></span>
								<?php echo cr_icon( 'arrow-up-right', 18, 'cr-ing-card__go' ); // phpcs:ignore ?>
							</a>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>
			<p class="cr-empty" data-glossary-empty hidden>Eşleşen içerik bulunamadı.</p>
		<?php else : ?>
			<div class="cr-empty-state"><?php echo cr_icon( 'flask', 40 ); // phpcs:ignore ?><p>Sözlük yakında dolmaya başlayacak.</p></div>
		<?php endif; ?>
	</div>
</section>
<?php
get_footer();
