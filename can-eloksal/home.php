<?php
/**
 * Blog sayfası (/blog) — Teknik Bilgi Merkezi.
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

get_header();
$ce_blog = (int) get_option( 'page_for_posts' );
get_template_part(
	'template-parts/components/page-hero',
	null,
	array(
		'title'    => $ce_blog ? ce_page_title( $ce_blog ) : 'Blog',
		'subtitle' => $ce_blog ? ce_page_subtitle( $ce_blog ) : '',
		'eyebrow'  => $ce_blog ? ce_meta( $ce_blog, 'hero_eyebrow', 'Teknik Bilgi Merkezi' ) : 'Teknik Bilgi Merkezi',
		'image'    => $ce_blog ? ( ce_meta( $ce_blog, 'hero_image' ) ? ce_meta( $ce_blog, 'hero_image' ) : get_post_thumbnail_id( $ce_blog ) ) : 0,
		'tone'     => 'blue',
	)
);
get_template_part( 'template-parts/components/blog-list' );
get_footer();
