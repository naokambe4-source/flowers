<?php
/**
 * İhtiyacınıza göre rotalar: 3 kolon, 16:10 görsel.
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;
$items = (array) cr_opt( 'needs_items' );
if ( ! $items ) {
	return;
}
?>
<section class="cr-section cr-needs"<?php echo cr_section_attr( 'needs' ); // phpcs:ignore ?>>
	<div class="cr-container">
		<header class="cr-head cr-head--center cr-reveal">
			<p class="cr-eyebrow"<?php echo cr_edit( 'needs_eyebrow' ); // phpcs:ignore ?>><?php cr_t( 'needs_eyebrow' ); ?></p>
			<h2 class="cr-h2"<?php echo cr_edit( 'needs_title' ); // phpcs:ignore ?>><?php cr_t( 'needs_title' ); ?></h2>
		</header>
		<div class="cr-needs__grid">
			<?php foreach ( $items as $i => $it ) : ?>
				<?php $tag = ! empty( $it['url'] ) ? 'a' : 'div'; ?>
				<<?php echo $tag; // phpcs:ignore ?> class="cr-need cr-reveal<?php echo 'div' === $tag ? ' is-static' : ''; ?>"<?php echo 'a' === $tag ? ' href="' . esc_url( cr_url( $it['url'] ) ) . '"' : ''; // phpcs:ignore ?>>
					<span class="cr-need__media"<?php echo cr_edit_img( 'needs_items.' . $i . '.image' ); // phpcs:ignore ?>>
						<?php echo cr_img( $it['image'], 'cr-wide', array( 'alt' => $it['title'], 'class' => 'cr-zoom', 'sizes' => '(max-width: 640px) 100vw, 33vw' ) ); // phpcs:ignore ?>
					</span>
					<h3 class="cr-need__title"<?php echo cr_edit( 'needs_items.' . $i . '.title' ); // phpcs:ignore ?>><?php echo esc_html( $it['title'] ); ?></h3>
					<p class="cr-need__text"<?php echo cr_edit( 'needs_items.' . $i . '.text' ); // phpcs:ignore ?>><?php echo esc_html( $it['text'] ); ?></p>
				</<?php echo $tag; // phpcs:ignore ?>>
			<?php endforeach; ?>
		</div>
	</div>
</section>
