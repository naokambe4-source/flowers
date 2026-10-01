<?php
/**
 * Bülten: koyu yeşil, iki kolon, alt çizgili minimal form.
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;
?>
<section class="cr-newsletter"<?php echo cr_section_attr( 'newsletter' ); // phpcs:ignore ?>>
	<div class="cr-container cr-newsletter__grid">
		<div class="cr-reveal">
			<p class="cr-eyebrow"<?php echo cr_edit( 'news_eyebrow' ); // phpcs:ignore ?>><?php cr_t( 'news_eyebrow' ); ?></p>
			<h2 class="cr-h2"<?php echo cr_edit( 'news_title' ); // phpcs:ignore ?>><?php cr_t( 'news_title' ); ?></h2>
		</div>
		<div class="cr-reveal">
			<p class="cr-newsletter__text"<?php echo cr_edit( 'news_text' ); // phpcs:ignore ?>><?php cr_t( 'news_text' ); ?></p>
			<?php cr_news_form( 'home', 'cr-news-form--line' ); ?>
			<p class="cr-newsletter__note"<?php echo cr_edit( 'news_note' ); // phpcs:ignore ?>><?php cr_t( 'news_note' ); ?></p>
		</div>
	</div>
</section>
