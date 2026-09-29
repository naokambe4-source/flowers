<?php
/**
 * Ana sayfa. Bölümler Derin Flowers → Ana Sayfa panelinden açılıp kapatılır ve sıralanır.
 *
 * Varsayılan sıra: Hero → Güven şeridi → Kategoriler → Özel günler → Signature Collection →
 * Editorial banner → En çok sevilenler → İkili koleksiyon → İzmir teslimat → Marka hikayesi →
 * Blog → Instagram → Bülten.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

get_header();

foreach ( df_active_sections() as $df_section ) {
	get_template_part( 'template-parts/home/' . sanitize_key( $df_section ) );
}

get_footer();
