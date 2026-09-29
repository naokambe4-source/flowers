<?php
/**
 * Teknik Bilgi Merkezi — son 3 yazı.
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

$ce_posts = get_posts( array( 'numberposts' => 3, 'post_status' => 'publish', 'ignore_sticky_posts' => true ) );
if ( ! $ce_posts ) {
	return;
}
$ce_blog = (int) get_option( 'page_for_posts' );
?>
<section class="ce-section ce-blog-preview" aria-labelledby="ce-blog-title">
	<div class="ce-container">
		<?php
		ce_section_head(
			array(
				'eyebrow'    => ce_opt( 'blog_eyebrow' ),
				'title'      => ce_opt( 'blog_title' ),
				'link'       => $ce_blog ? get_permalink( $ce_blog ) : home_url( '/blog/' ),
				'link_label' => 'Tüm yazılar',
				'id'         => 'ce-blog-title',
			)
		);
		?>
		<div class="ce-cards ce-cards--3">
			<?php
			foreach ( $ce_posts as $ce_i => $ce_p ) {
				get_template_part( 'template-parts/components/post-card', null, array( 'post' => $ce_p, 'index' => $ce_i ) );
			}
			?>
		</div>
	</div>
</section>
