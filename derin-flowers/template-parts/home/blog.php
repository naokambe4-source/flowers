<?php
/**
 * Ana sayfa — Çiçek Rehberi (son 3 yazı).
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

$args = array(
	'post_type'           => 'post',
	'posts_per_page'      => 3,
	'ignore_sticky_posts' => true,
	'no_found_rows'       => true,
);
if ( df_opt( 'blog_cat' ) ) {
	$args['cat'] = absint( df_opt( 'blog_cat' ) );
}
$query = new WP_Query( $args );
if ( ! $query->have_posts() ) {
	return;
}
$blog_url = get_option( 'page_for_posts' ) ? get_permalink( get_option( 'page_for_posts' ) ) : '';
?>
<section class="df-section df-blog" aria-labelledby="df-blog-title">
	<div class="df-container">
		<?php
		df_section_head(
			array(
				'title'     => df_opt( 'blog_title' ),
				'sub'       => df_opt( 'blog_sub' ),
				'align'     => 'split',
				'link'      => $blog_url,
				'link_text' => $blog_url ? 'Tüm yazılar' : '',
				'id'        => 'df-blog-title',
			)
		);
		?>
		<div class="df-blog__grid">
			<?php
			while ( $query->have_posts() ) {
				$query->the_post();
				get_template_part( 'template-parts/content/post-card' );
			}
			wp_reset_postdata();
			?>
		</div>
	</div>
</section>
