<?php
/**
 * Teknik Bilgi Merkezi — son 3 yazı.
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

$ce_posts = get_posts( array( 'numberposts' => (int) ce_hopt( $args, 'blog_count', 3 ), 'post_status' => 'publish', 'ignore_sticky_posts' => true, 'category_name' => (string) ( $args['category'] ?? '' ) ) );
if ( ! $ce_posts ) {
	return;
}
$ce_blog = (int) get_option( 'page_for_posts' );
?>
<section class="ce-section ce-blog-preview" aria-labelledby="<?php echo esc_attr( ce_uid( $args, 'ce-blog-title' ) ); ?>">
	<div class="ce-container">
		<?php
		ce_section_head(
			array(
				'eyebrow'    => ce_hopt( $args, 'blog_eyebrow' ),
				'title'      => ce_hopt( $args, 'blog_title' ),
				'link'       => $ce_blog ? get_permalink( $ce_blog ) : home_url( '/blog/' ),
				'link_label' => 'Tüm yazılar',
				'id'         => ce_uid( $args, 'ce-blog-title' ),
				'src'        => array( 'eyebrow' => ce_src( $args, 'blog_eyebrow' ), 'title' => ce_src( $args, 'blog_title' ) ),
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
