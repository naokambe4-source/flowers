<?php
/**
 * Galeri önizleme.
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

$ce_items = ce_get_items( 'ce_gallery', (int) ce_opt( 'gallery_count', 8 ), array( 'meta_key' => '_thumbnail_id' ) );
if ( ! $ce_items ) {
	return;
}
?>
<section class="ce-section ce-section--tint ce-gallery-preview" aria-labelledby="ce-gallery-title">
	<div class="ce-container">
		<?php
		ce_section_head(
			array(
				'eyebrow'    => ce_opt( 'gallery_eyebrow' ),
				'title'      => ce_opt( 'gallery_title' ),
				'link'       => ce_template_url( 'gallery', '/galeri/' ),
				'link_label' => 'Tüm Galeriyi Gör',
				'id'         => 'ce-gallery-title',
			)
		);
		get_template_part( 'template-parts/components/gallery-grid', null, array( 'items' => $ce_items, 'group' => 'home' ) );
		?>
	</div>
</section>
