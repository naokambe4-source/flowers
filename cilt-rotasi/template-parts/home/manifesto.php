<?php
/**
 * Manifesto: tam genişlik görsel mola.
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;
?>
<section class="cr-manifesto"<?php echo cr_section_attr( 'manifesto' ); // phpcs:ignore ?>>
	<div class="cr-manifesto__media" data-parallax<?php echo cr_edit_img( 'manifesto_image' ); // phpcs:ignore ?>>
		<?php echo cr_img( cr_opt( 'manifesto_image' ), 'cr-hero', array( 'alt' => '', 'sizes' => '100vw' ) ); // phpcs:ignore ?>
	</div>
	<blockquote class="cr-manifesto__quote cr-reveal">
		<p>“<span<?php echo cr_edit( 'manifesto_quote' ); // phpcs:ignore ?>><?php cr_t( 'manifesto_quote' ); ?></span>”</p>
		<cite class="cr-eyebrow"<?php echo cr_edit( 'manifesto_by' ); // phpcs:ignore ?>><?php cr_t( 'manifesto_by' ); ?></cite>
	</blockquote>
</section>
