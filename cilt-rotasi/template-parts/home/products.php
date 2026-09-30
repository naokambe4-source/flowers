<?php
/**
 * Ürün rehberi: satış değil bilgilendirme. Solda sabit metin, sağda kategori karoları.
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;
$items = (array) cr_opt( 'products_items' );
if ( ! $items ) {
	return;
}
?>
<section class="cr-section cr-products"<?php echo cr_section_attr( 'products' ); // phpcs:ignore ?>>
	<div class="cr-container cr-products__grid">
		<div class="cr-products__intro cr-reveal">
			<p class="cr-eyebrow"><?php echo cr_icon( 'drop', 14 ); // phpcs:ignore ?> Ürün rehberi</p>
			<h2 class="cr-h2"<?php echo cr_edit( 'products_title' ); // phpcs:ignore ?>><?php cr_t( 'products_title' ); ?></h2>
			<p class="cr-lead"<?php echo cr_edit( 'products_text' ); // phpcs:ignore ?>><?php cr_t( 'products_text' ); ?></p>
			<p class="cr-pill-note"><?php echo cr_icon( 'shield', 18 ); // phpcs:ignore ?><span<?php echo cr_edit( 'products_note' ); // phpcs:ignore ?>><?php cr_t( 'products_note' ); ?></span></p>
			<a class="cr-btn cr-btn--ghost" href="<?php echo esc_url( get_post_type_archive_link( 'urun_rehberi' ) ); ?>">Ürün rehberine git <?php echo cr_icon( 'arrow-right', 18 ); // phpcs:ignore ?></a>
		</div>
		<ul class="cr-products__list" role="list">
			<?php foreach ( $items as $i => $it ) : ?>
				<li class="cr-reveal">
					<a class="cr-ptile" href="<?php echo esc_url( cr_url( $it['url'] ) ); ?>">
						<span class="cr-ptile__media"<?php echo cr_edit_img( 'products_items.' . $i . '.image' ); // phpcs:ignore ?>>
							<?php echo cr_img( $it['image'], 'cr-card', array( 'alt' => '', 'class' => 'cr-zoom', 'sizes' => '(max-width: 768px) 50vw, 20vw' ) ); // phpcs:ignore ?>
						</span>
						<span class="cr-ptile__body">
							<span class="cr-ptile__title"<?php echo cr_edit( 'products_items.' . $i . '.title' ); // phpcs:ignore ?>><?php echo esc_html( $it['title'] ); ?></span>
							<span class="cr-ptile__text"<?php echo cr_edit( 'products_items.' . $i . '.text' ); // phpcs:ignore ?>><?php echo esc_html( $it['text'] ); ?></span>
						</span>
						<span class="cr-ptile__go" aria-hidden="true"><?php echo cr_icon( 'arrow-up-right', 18 ); // phpcs:ignore ?></span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
