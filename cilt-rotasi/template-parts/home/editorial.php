<?php
/**
 * Öne çıkan inceleme: %50 / %50 editoryal bölünme.
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;
?>
<section class="cr-split<?php echo cr_opt( 'ed_reverse' ) ? ' cr-split--reverse' : ''; ?>"<?php echo cr_section_attr( 'editorial' ); // phpcs:ignore ?>>
	<div class="cr-container cr-split__grid">
		<a class="cr-split__media cr-reveal" href="<?php echo esc_url( cr_url( cr_opt( 'ed_url' ) ) ); ?>" tabindex="-1" aria-hidden="true"<?php echo cr_edit_img( 'ed_image' ); // phpcs:ignore ?>>
			<?php echo cr_img( cr_opt( 'ed_image' ), 'cr-hero', array( 'alt' => '', 'class' => 'cr-zoom', 'sizes' => '(max-width: 1024px) 100vw, 50vw' ) ); // phpcs:ignore ?>
		</a>
		<div class="cr-split__copy cr-reveal">
			<p class="cr-eyebrow"<?php echo cr_edit( 'ed_eyebrow' ); // phpcs:ignore ?>><?php cr_t( 'ed_eyebrow' ); ?></p>
			<h2 class="cr-h2">
				<span<?php echo cr_edit( 'ed_title' ); // phpcs:ignore ?>><?php cr_t( 'ed_title' ); ?></span><br>
				<em class="cr-accent"<?php echo cr_edit( 'ed_accent' ); // phpcs:ignore ?>><?php cr_t( 'ed_accent' ); ?></em>
			</h2>
			<p class="cr-split__text"<?php echo cr_edit( 'ed_text' ); // phpcs:ignore ?>><?php cr_t( 'ed_text' ); ?></p>
			<a class="cr-btn-line" href="<?php echo esc_url( cr_url( cr_opt( 'ed_url' ) ) ); ?>"><span<?php echo cr_edit( 'ed_cta' ); // phpcs:ignore ?>><?php cr_t( 'ed_cta' ); ?></span></a>
		</div>
	</div>
</section>
