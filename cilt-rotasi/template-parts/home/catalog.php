<?php
/**
 * Ürün kataloğu: filtreli, 4 kolon ürün sergisi (satış değil, bilgilendirme).
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;

$source = cr_opt( 'catalog_source', 'auto' );
$count  = (int) cr_opt( 'catalog_count', 8 );
$posts  = array();
if ( 'manual' !== $source ) {
	$posts = get_posts(
		array(
			'post_type'      => 'urun_rehberi',
			'posts_per_page' => $count,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);
}
$use_posts = $posts && 'manual' !== $source;
$manual    = $use_posts ? array() : array_slice( (array) cr_opt( 'catalog_items' ), 0, $count );
if ( ! $use_posts && ! $manual ) {
	return;
}

$filters = array();
if ( $use_posts ) {
	$terms = get_terms( array( 'taxonomy' => 'urun_turu', 'hide_empty' => true, 'object_ids' => $posts ) );
	if ( $terms && ! is_wp_error( $terms ) ) {
		foreach ( $terms as $t ) {
			$filters[ $t->slug ] = $t->name;
		}
	}
} else {
	foreach ( cr_pairs( cr_opt( 'catalog_filters' ) ) as $f ) {
		if ( ! empty( $f[1] ) ) {
			$filters[ $f[1] ] = $f[0];
		}
	}
}
?>
<section class="cr-section cr-catalog"<?php echo cr_section_attr( 'catalog' ); // phpcs:ignore ?>>
	<div class="cr-container">
		<header class="cr-head cr-head--split cr-reveal">
			<div>
				<p class="cr-eyebrow"<?php echo cr_edit( 'catalog_eyebrow' ); // phpcs:ignore ?>><?php cr_t( 'catalog_eyebrow' ); ?></p>
				<h2 class="cr-h2"<?php echo cr_edit( 'catalog_title' ); // phpcs:ignore ?>><?php cr_t( 'catalog_title' ); ?></h2>
			</div>
			<?php if ( $filters ) : ?>
				<div class="cr-tabs-line" role="tablist" aria-label="Ürün türü" data-catalog-filters>
					<button type="button" role="tab" class="is-active" aria-selected="true" data-type="">Tümü</button>
					<?php foreach ( $filters as $slug => $label ) : ?>
						<button type="button" role="tab" aria-selected="false" data-type="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $label ); ?></button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</header>
		<div class="cr-catalog__grid" data-catalog>
			<?php
			if ( $use_posts ) {
				foreach ( $posts as $pid ) {
					get_template_part( 'template-parts/cards/product', null, array( 'post_id' => $pid ) );
				}
			} else {
				foreach ( $manual as $i => $it ) {
					get_template_part( 'template-parts/cards/product', null, array_merge( $it, array( 'edit' => 'catalog_items.' . $i ) ) );
				}
			}
			?>
		</div>
		<p class="cr-empty" data-catalog-empty hidden>Bu türde henüz inceleme yok.</p>
		<div class="cr-center">
			<a class="cr-btn-line" href="<?php echo esc_url( get_post_type_archive_link( 'urun_rehberi' ) ); ?>">Tüm Ürün Rehberi</a>
		</div>
	</div>
</section>
