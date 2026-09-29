<?php
/**
 * Galeri önizleme.
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

$ce_extra = array( 'meta_key' => '_thumbnail_id' );
if ( ! empty( $args['category'] ) ) {
	$ce_extra['tax_query'] = array( array( 'taxonomy' => 'ce_gallery_cat', 'field' => 'slug', 'terms' => $args['category'] ) ); // phpcs:ignore
}
$ce_items = ce_get_items( 'ce_gallery', (int) ce_hopt( $args, 'gallery_count', 8 ), $ce_extra );
if ( ! $ce_items ) {
	return;
}
?>
<section class="ce-section ce-section--tint ce-gallery-preview" aria-labelledby="<?php echo esc_attr( ce_uid( $args, 'ce-gallery-title' ) ); ?>">
	<div class="ce-container">
		<?php
		ce_section_head(
			array(
				'eyebrow'    => ce_hopt( $args, 'gallery_eyebrow' ),
				'title'      => ce_hopt( $args, 'gallery_title' ),
				'link'       => ce_template_url( 'gallery', '/galeri/' ),
				'link_label' => 'Tüm Galeriyi Gör',
				'id'         => ce_uid( $args, 'ce-gallery-title' ),
				'src'        => array( 'eyebrow' => ce_src( $args, 'gallery_eyebrow' ), 'title' => ce_src( $args, 'gallery_title' ) ),
			)
		);
		get_template_part( 'template-parts/components/gallery-grid', null, array( 'items' => $ce_items, 'group' => ce_uid( $args, 'home' ), 'filter' => ! empty( $args['filter'] ) ) );
		?>
	</div>
</section>
