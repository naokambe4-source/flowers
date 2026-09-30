<?php
/**
 * Cilt Rotası Rehberleri: filtreler + asimetrik ızgara.
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;
$filters = cr_pairs( cr_opt( 'guides_filters' ) );
?>
<section class="cr-section cr-guides"<?php echo cr_section_attr( 'guides' ); // phpcs:ignore ?>>
	<div class="cr-container">
		<div class="cr-head cr-head--split cr-reveal">
			<div>
				<h2 class="cr-h2"<?php echo cr_edit( 'guides_title' ); // phpcs:ignore ?>><?php cr_t( 'guides_title' ); ?></h2>
				<p class="cr-lead"<?php echo cr_edit( 'guides_text' ); // phpcs:ignore ?>><?php cr_t( 'guides_text' ); ?></p>
			</div>
			<a class="cr-link cr-hide-mobile" href="<?php echo esc_url( cr_url( cr_opt( 'bn_explore_url' ) ) ); ?>">Tümünü gör <?php echo cr_icon( 'arrow-right', 16 ); // phpcs:ignore ?></a>
		</div>
		<div class="cr-filters" role="tablist" aria-label="Rehber filtreleri" data-guides-filters>
			<button type="button" role="tab" class="cr-filter is-active" aria-selected="true" data-cat="">Tümü</button>
			<?php foreach ( $filters as $f ) : ?>
				<?php
				if ( empty( $f[1] ) ) {
					continue;
				}
				?>
				<button type="button" role="tab" class="cr-filter" aria-selected="false" data-cat="<?php echo esc_attr( $f[1] ); ?>"><?php echo esc_html( $f[0] ); ?></button>
			<?php endforeach; ?>
		</div>
		<div class="cr-guides__wrap" data-guides aria-live="polite">
			<?php echo cr_guides_grid( '', (int) cr_opt( 'guides_count', 6 ) ); // phpcs:ignore ?>
		</div>
		<div class="cr-center cr-show-mobile">
			<a class="cr-btn cr-btn--ghost" href="<?php echo esc_url( cr_url( cr_opt( 'bn_explore_url' ) ) ); ?>">Tüm rehberler</a>
		</div>
	</div>
</section>
