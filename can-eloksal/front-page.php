<?php
/**
 * Ana sayfa: bölümler Tema Ayarları → Ana Sayfa'daki sıraya göre çizilir.
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

get_header();

// Ana sayfa içeriği Builder blokları veya Elementor ile hazırlanmışsa onu göster.
$ce_front = (int) get_option( 'page_on_front' );
if ( 'page' === get_option( 'show_on_front' ) && ce_is_builder_content( $ce_front ) ) {
	ce_render_canvas();
	get_footer();
	return;
}

$ce_sections = ce_normalize_sections( ce_opt( 'home_sections' ), ce_home_section_labels() );
foreach ( $ce_sections as $ce_section ) {
	if ( ! empty( $ce_section['on'] ) ) {
		echo '<div class="ce-editable">';
		ce_section_edit_link( $ce_section['key'] );
		get_template_part( 'template-parts/home/' . $ce_section['key'] );
		echo '</div>';
	}
}

get_footer();
