<?php
/**
 * Journal: editoryal makale kartları + kategori filtreleri.
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;
$filters = cr_opt( 'journal_filters_on' ) ? cr_pairs( cr_opt( 'guides_filters' ) ) : array();
$all     = cr_blog_url();
?>
<section class="cr-section cr-journal"<?php echo cr_section_attr( 'journal' ); // phpcs:ignore ?>>
	<div class="cr-container">
		<header class="cr-head cr-head--split cr-reveal">
			<div>
				<p class="cr-eyebrow"<?php echo cr_edit( 'journal_eyebrow' ); // phpcs:ignore ?>><?php cr_t( 'journal_eyebrow' ); ?></p>
				<h2 class="cr-h2"<?php echo cr_edit( 'journal_title' ); // phpcs:ignore ?>><?php cr_t( 'journal_title' ); ?></h2>
			</div>
			<a class="cr-link-arrow cr-hide-mobile" href="<?php echo esc_url( $all ); ?>"><span<?php echo cr_edit( 'journal_link' ); // phpcs:ignore ?>><?php cr_t( 'journal_link' ); ?></span> <?php echo cr_icon( 'arrow-right', 16 ); // phpcs:ignore ?></a>
		</header>
		<?php if ( $filters ) : ?>
			<div class="cr-tabs-line cr-tabs-line--mb" role="tablist" aria-label="Blog filtreleri" data-guides-filters>
				<button type="button" role="tab" class="is-active" aria-selected="true" data-cat="">Tümü</button>
				<?php foreach ( $filters as $f ) : ?>
					<?php
					if ( empty( $f[1] ) ) {
						continue;
					}
					?>
					<button type="button" role="tab" aria-selected="false" data-cat="<?php echo esc_attr( $f[1] ); ?>"><?php echo esc_html( $f[0] ); ?></button>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<div class="cr-journal__wrap" data-guides aria-live="polite">
			<?php echo cr_guides_grid( '', (int) cr_opt( 'guides_count', 6 ) ); // phpcs:ignore ?>
		</div>
		<div class="cr-center cr-show-mobile">
			<a class="cr-btn-line" href="<?php echo esc_url( $all ); ?>"><?php cr_t( 'journal_link' ); ?></a>
		</div>
	</div>
</section>
