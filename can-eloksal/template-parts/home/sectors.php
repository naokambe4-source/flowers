<?php
/**
 * Sektörler bölümü.
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

if ( ! ce_get_items( 'ce_sector', 1 ) ) {
	return;
}
?>
<section class="ce-section ce-section--dark ce-sectors-section" aria-labelledby="ce-sectors-title">
	<div class="ce-container">
		<?php ce_section_head( array( 'eyebrow' => ce_opt( 'sectors_eyebrow' ), 'title' => ce_opt( 'sectors_title' ), 'id' => 'ce-sectors-title', 'align' => 'left' ) ); ?>
		<?php get_template_part( 'template-parts/components/sectors' ); ?>
	</div>
</section>
