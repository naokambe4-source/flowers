<?php
/**
 * WordPress site haritası sağlayıcısı: ilçe sayfaları (wp-sitemap-ilceler-1.xml).
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * İlçe sayfaları sağlayıcısı.
 */
class DF_Loc_Sitemap_Provider extends WP_Sitemaps_Provider {

	/**
	 * Kurulum.
	 */
	public function __construct() {
		$this->name        = 'ilceler';
		$this->object_type = 'ilceler';
	}

	/**
	 * Adresler.
	 *
	 * @param int    $page_num       Sayfa.
	 * @param string $object_subtype Alt tür.
	 * @return array
	 */
	public function get_url_list( $page_num, $object_subtype = '' ) {
		$out = array( array( 'loc' => df_loc_url() ) );
		foreach ( df_loc_items() as $slug => $loc ) {
			$out[] = array( 'loc' => df_loc_url( $slug ) );
		}
		return $out;
	}

	/**
	 * Sayfa sayısı.
	 *
	 * @param string $object_subtype Alt tür.
	 * @return int
	 */
	public function get_max_num_pages( $object_subtype = '' ) {
		return 1;
	}
}
