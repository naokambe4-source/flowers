<?php
/**
 * Ana sayfa. Bölümler Derin Flowers → Ana Sayfa panelinden ya da ön yüzde
 * "Canlı Düzenle" ile açılıp kapatılır ve sıralanır.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( df_live() ) {
	// Canlı düzenlemede kapalı bölümler de (soluk) gösterilir; tekrar açılabilsin.
	foreach ( df_all_sections_ordered() as $df_row ) {
		$df_id = sanitize_key( $df_row['id'] );
		echo '<div class="df-live-section' . ( $df_row['on'] ? '' : ' is-off' ) . '"' . df_s( $df_id ) . ' data-df-on="' . ( $df_row['on'] ? '1' : '0' ) . '">'; // phpcs:ignore
		ob_start();
		get_template_part( 'template-parts/home/' . $df_id );
		$df_html = ob_get_clean();
		echo trim( $df_html ) ? $df_html : '<div class="df-live-empty">Bu bölümde gösterilecek içerik yok (görsel/ürün eklenmemiş). Paneldeki ayarlarından doldurabilirsiniz.</div>'; // phpcs:ignore
		echo '</div>';
	}
} else {
	foreach ( df_active_sections() as $df_section ) {
		get_template_part( 'template-parts/home/' . sanitize_key( $df_section ) );
	}
}

get_footer();
