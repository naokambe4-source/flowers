<?php
/**
 * Arama sonuçları.
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

get_header();
global $wp_query;
get_template_part(
	'template-parts/components/page-hero',
	null,
	array(
		'title'    => '“' . get_search_query() . '”',
		'subtitle' => sprintf( '%d sonuç bulundu.', (int) $wp_query->found_posts ),
		'eyebrow'  => 'Arama sonuçları',
	)
);
get_template_part( 'template-parts/components/blog-list' );
get_footer();
