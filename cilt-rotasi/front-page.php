<?php
/**
 * Ana sayfa: bölümler panelden sıralanır ve açılıp kapatılır.
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;

get_header();

foreach ( cr_sections() as $cr_section ) {
	if ( empty( $cr_section['on'] ) && ! cr_can_edit() ) {
		continue;
	}
	if ( empty( $cr_section['on'] ) ) {
		// Yönetici gizli bölümü canlı düzenleyicide görebilsin diye gizli işaretle.
		echo '<div class="cr-hidden-section" data-cr-hidden="' . esc_attr( $cr_section['id'] ) . '" hidden>';
		get_template_part( 'template-parts/home/' . $cr_section['id'] );
		echo '</div>';
		continue;
	}
	get_template_part( 'template-parts/home/' . $cr_section['id'] );
}

get_footer();
