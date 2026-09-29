<?php
/**
 * Kategori, etiket ve tarih arşivleri.
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

get_header();
get_template_part(
	'template-parts/components/page-hero',
	null,
	array(
		'title'    => wp_strip_all_tags( single_term_title( '', false ) ? single_term_title( '', false ) : get_the_archive_title() ),
		'subtitle' => wp_strip_all_tags( (string) get_the_archive_description() ),
		'eyebrow'  => is_category() ? 'Kategori' : ( is_tag() ? 'Etiket' : 'Arşiv' ),
		'tone'     => 'blue',
	)
);
get_template_part( 'template-parts/components/blog-list' );
get_footer();
