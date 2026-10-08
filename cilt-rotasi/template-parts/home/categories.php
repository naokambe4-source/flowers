<?php
/**
 * Kategoriler: “Cildin İçin Ne Arıyorsun?” — 4 kolon, 3:4 görsel, sıfır çizgi.
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;
$items = (array) cr_opt( 'cat_items' );
if ( ! $items ) {
	return;
}
?>
<section class="cr-section cr-cats"<?php echo cr_section_attr( 'categories' ); // phpcs:ignore ?>>
	<div class="cr-container">
		<header class="cr-head cr-head--center cr-reveal">
			<p class="cr-eyebrow"<?php echo cr_edit( 'cat_eyebrow' ); // phpcs:ignore ?>><?php cr_t( 'cat_eyebrow' ); ?></p>
			<h2 class="cr-h2"<?php echo cr_edit( 'cat_title' ); // phpcs:ignore ?>><?php cr_t( 'cat_title' ); ?></h2>
		</header>
		<div class="cr-cats__grid">
			<?php foreach ( $items as $i => $it ) : ?>
				<?php $tag = ! empty( $it['url'] ) ? 'a' : 'div'; ?>
				<<?php echo $tag; // phpcs:ignore ?> class="cr-cat cr-reveal<?php echo 'div' === $tag ? ' is-static' : ''; ?>"<?php echo 'a' === $tag ? ' href="' . esc_url( cr_url( $it['url'] ) ) . '"' : ''; // phpcs:ignore ?>>
					<span class="cr-cat__media"<?php echo cr_edit_img( 'cat_items.' . $i . '.image' ); // phpcs:ignore ?>>
						<?php echo cr_img( $it['image'], 'cr-card', array( 'alt' => $it['title'], 'class' => 'cr-zoom', 'sizes' => '(max-width: 640px) 100vw, (max-width: 1100px) 50vw, 25vw' ) ); // phpcs:ignore ?>
					</span>
					<?php if ( ! empty( $it['tag'] ) ) : ?>
						<span class="cr-eyebrow cr-eyebrow--sm cr-cat__tag"<?php echo cr_edit( 'cat_items.' . $i . '.tag' ); // phpcs:ignore ?>><?php echo esc_html( $it['tag'] ); ?></span>
					<?php endif; ?>
					<h3 class="cr-cat__title"<?php echo cr_edit( 'cat_items.' . $i . '.title' ); // phpcs:ignore ?>><?php echo esc_html( $it['title'] ); ?></h3>
					<p class="cr-cat__text"<?php echo cr_edit( 'cat_items.' . $i . '.text' ); // phpcs:ignore ?>><?php echo esc_html( $it['text'] ); ?></p>
				</<?php echo $tag; // phpcs:ignore ?>>
			<?php endforeach; ?>
		</div>
	</div>
</section>
