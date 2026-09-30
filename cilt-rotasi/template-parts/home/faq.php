<?php
/**
 * Kısa cevaplar (FAQPage şemalı).
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;
$items = array_filter(
	(array) cr_opt( 'faq_items' ),
	function ( $it ) {
		return ! empty( $it['q'] ) && ! empty( $it['a'] );
	}
);
if ( ! $items ) {
	return;
}
?>
<section class="cr-section cr-faq"<?php echo cr_section_attr( 'faq' ); // phpcs:ignore ?>>
	<div class="cr-container cr-faq__grid">
		<div class="cr-faq__head cr-reveal">
			<p class="cr-eyebrow"><?php echo cr_icon( 'bulb', 14 ); // phpcs:ignore ?> SSS</p>
			<h2 class="cr-h2"<?php echo cr_edit( 'faq_title' ); // phpcs:ignore ?>><?php cr_t( 'faq_title' ); ?></h2>
			<p class="cr-lead">En sık sorulan sorulara net ve kısa yanıtlar.</p>
		</div>
		<div class="cr-accordion cr-reveal">
			<?php foreach ( $items as $i => $it ) : ?>
				<details class="cr-acc"<?php echo 0 === $i ? ' open' : ''; ?>>
					<summary><span<?php echo cr_edit( 'faq_items.' . $i . '.q' ); // phpcs:ignore ?>><?php echo esc_html( $it['q'] ); ?></span><span class="cr-acc__icon" aria-hidden="true"><?php echo cr_icon( 'plus', 20 ); // phpcs:ignore ?></span></summary>
					<div class="cr-acc__body"><p<?php echo cr_edit( 'faq_items.' . $i . '.a' ); // phpcs:ignore ?>><?php echo esc_html( $it['a'] ); ?></p></div>
				</details>
			<?php endforeach; ?>
		</div>
	</div>
</section>
