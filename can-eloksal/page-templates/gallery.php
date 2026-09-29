<?php
/**
 * Template Name: Galeri
 *
 * Görseller "Galeri" menüsünden (tekli veya Toplu Ekle) yönetilir; kategoriler filtre olarak listelenir.
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

get_header();
while ( have_posts() ) :
	the_post();
	$ce_id = get_the_ID();
	get_template_part(
		'template-parts/components/page-hero',
		null,
		array(
			'title'    => ce_page_title( $ce_id ),
			'subtitle' => ce_page_subtitle( $ce_id ),
			'eyebrow'  => ce_meta( $ce_id, 'hero_eyebrow' ),
			'image'    => ce_meta( $ce_id, 'hero_image' ) ? ce_meta( $ce_id, 'hero_image' ) : get_post_thumbnail_id(),
		)
	);
	$ce_items = ce_get_items( 'ce_gallery', 300, array( 'meta_key' => '_thumbnail_id' ) );
	?>
	<section class="ce-section">
		<div class="ce-container">
			<?php if ( get_the_content() ) : ?>
				<div class="ce-prose ce-entry ce-lead-block"><?php the_content(); ?></div>
			<?php endif; ?>
			<?php if ( $ce_items ) : ?>
				<?php get_template_part( 'template-parts/components/gallery-grid', null, array( 'items' => $ce_items, 'filter' => true, 'group' => 'gallery' ) ); ?>
			<?php else : ?>
				<div class="ce-empty-state">
					<?php echo ce_icon( 'image', 28 ); // phpcs:ignore ?>
					<p>Galeriye henüz görsel eklenmedi.</p>
					<?php if ( current_user_can( 'upload_files' ) ) : ?>
						<a class="ce-btn ce-btn--dark ce-btn--sm" href="<?php echo esc_url( admin_url( 'edit.php?post_type=ce_gallery&page=ce-gallery-bulk' ) ); ?>">Görsel ekle</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
	</section>
	<?php
endwhile;
get_footer();
