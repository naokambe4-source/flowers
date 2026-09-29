<?php
/**
 * Ana sayfa: bölümler Tema Ayarları → Ana Sayfa'daki sıraya göre çizilir.
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

get_header();

$ce_sections = ce_normalize_sections( ce_opt( 'home_sections' ), ce_home_section_labels() );
foreach ( $ce_sections as $ce_section ) {
	if ( ! empty( $ce_section['on'] ) ) {
		get_template_part( 'template-parts/home/' . $ce_section['key'] );
	}
}

get_footer();
