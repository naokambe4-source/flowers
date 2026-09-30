<?php
/**
 * Bülten.
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;
?>
<section class="cr-section cr-newsletter"<?php echo cr_section_attr( 'newsletter' ); // phpcs:ignore ?>>
	<div class="cr-container">
		<div class="cr-newsletter__box cr-reveal">
			<span class="cr-newsletter__icon" aria-hidden="true"><?php echo cr_icon( 'mail', 26 ); // phpcs:ignore ?></span>
			<h2 class="cr-h2"<?php echo cr_edit( 'news_title' ); // phpcs:ignore ?>><?php cr_t( 'news_title' ); ?></h2>
			<p class="cr-lead"<?php echo cr_edit( 'news_text' ); // phpcs:ignore ?>><?php cr_t( 'news_text' ); ?></p>
			<?php cr_news_form( 'home' ); ?>
			<p class="cr-newsletter__note"<?php echo cr_edit( 'news_note' ); // phpcs:ignore ?>><?php cr_t( 'news_note' ); ?></p>
		</div>
	</div>
</section>
