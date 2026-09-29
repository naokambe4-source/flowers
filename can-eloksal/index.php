<?php
/**
 * Yedek şablon.
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

get_header();
get_template_part(
	'template-parts/components/page-hero',
	null,
	array(
		'title' => wp_strip_all_tags( get_the_archive_title() ),
		'tone'  => 'steel',
	)
);
get_template_part( 'template-parts/components/blog-list' );
get_footer();
