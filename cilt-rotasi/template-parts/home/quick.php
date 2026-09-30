<?php
/**
 * Hızlı keşif: 3 büyük kategori, farklı kompozisyonlar.
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;
$items = array_slice( (array) cr_opt( 'quick_items' ), 0, 3 );
if ( ! $items ) {
	return;
}
$icons = array( 'layers', 'face', 'drop' );
?>
<section class="cr-section cr-quick"<?php echo cr_section_attr( 'quick' ); // phpcs:ignore ?>>
	<div class="cr-container">
		<div class="cr-head cr-head--split cr-reveal">
			<h2 class="cr-h2"<?php echo cr_edit( 'quick_title' ); // phpcs:ignore ?>><?php cr_t( 'quick_title' ); ?></h2>
		</div>
		<div class="cr-quick__grid">
			<?php foreach ( $items as $i => $it ) : ?>
				<a class="cr-quick__item cr-quick__item--<?php echo (int) $i + 1; ?> cr-reveal" href="<?php echo esc_url( cr_url( $it['url'] ) ); ?>">
					<span class="cr-quick__media"<?php echo cr_edit_img( 'quick_items.' . $i . '.image' ); // phpcs:ignore ?>>
						<?php echo cr_img( $it['image'], 0 === $i ? 'cr-card' : 'cr-wide', array( 'alt' => '', 'class' => 'cr-zoom', 'sizes' => '(max-width: 768px) 100vw, 45vw' ) ); // phpcs:ignore ?>
					</span>
					<span class="cr-quick__body">
						<span class="cr-quick__icon" aria-hidden="true"><?php echo cr_icon( $icons[ $i ], 22 ); // phpcs:ignore ?></span>
						<span class="cr-quick__num" aria-hidden="true">0<?php echo (int) $i + 1; ?></span>
						<span class="cr-quick__title"<?php echo cr_edit( 'quick_items.' . $i . '.title' ); // phpcs:ignore ?>><?php echo esc_html( $it['title'] ); ?></span>
						<span class="cr-quick__text"<?php echo cr_edit( 'quick_items.' . $i . '.text' ); // phpcs:ignore ?>><?php echo esc_html( $it['text'] ); ?></span>
						<span class="cr-quick__go" aria-hidden="true"><?php echo cr_icon( 'arrow-right', 20 ); // phpcs:ignore ?></span>
					</span>
				</a>
			<?php endforeach; ?>
		</div>
	</div>
</section>
