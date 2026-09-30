<?php
/**
 * Yazı meta alanları tanımı (SEO, AEO, GEO, içerik sözlüğü, ürün rehberi) ve okuma yardımcıları.
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Meta alanları. Tip: text, textarea, url, date, select, lines, faq, steps, image, number, toggle.
 *
 * @return array
 */
function cr_meta_schema() {
	return array(
		'seo'     => array(
			'title'  => 'SEO · AEO · GEO',
			'types'  => array( 'post', 'page', 'icerik', 'urun_rehberi' ),
			'fields' => array(
				'_cr_focus_kw'      => array( 'type' => 'text', 'label' => 'Odak anahtar kelime', 'tab' => 'seo' ),
				'_cr_seo_title'     => array( 'type' => 'text', 'label' => 'SEO başlığı', 'desc' => 'Boşsa yazı başlığı kullanılır. 50–60 karakter.', 'tab' => 'seo' ),
				'_cr_seo_desc'      => array( 'type' => 'textarea', 'label' => 'Meta açıklama', 'desc' => '140–160 karakter. Boşsa özet/kısa cevap kullanılır.', 'tab' => 'seo' ),
				'_cr_canonical'     => array( 'type' => 'url', 'label' => 'Canonical adres', 'desc' => 'Yalnızca içerik başka bir adreste de varsa.', 'tab' => 'seo' ),
				'_cr_noindex'       => array( 'type' => 'toggle', 'label' => 'Arama motorlarında gösterme (noindex)', 'tab' => 'seo' ),
				'_cr_og_image'      => array( 'type' => 'image', 'label' => 'Paylaşım görseli (1200×630)', 'desc' => 'Boşsa öne çıkan görsel.', 'tab' => 'seo' ),
				'_cr_short_answer'  => array( 'type' => 'textarea', 'label' => 'Kısa cevap (AEO)', 'desc' => 'Başlıktaki soruya 40–60 kelimelik doğrudan yanıt. Yazının en üstünde vurgulanır; öne çıkan snippet ve yapay zekâ yanıtları için.', 'tab' => 'aeo' ),
				'_cr_takeaways'     => array( 'type' => 'lines', 'label' => 'Akılda kalsın (öne çıkanlar)', 'desc' => 'Her satır bir madde (3–5 madde).', 'tab' => 'aeo' ),
				'_cr_faq'           => array( 'type' => 'faq', 'label' => 'Sık sorulan sorular', 'desc' => 'Yazı sonunda akordeon + FAQPage şeması.', 'tab' => 'aeo' ),
				'_cr_steps_title'   => array( 'type' => 'text', 'label' => 'Adım adım başlığı (HowTo)', 'desc' => 'Örn. “Sabah rutini nasıl uygulanır?”', 'tab' => 'aeo' ),
				'_cr_steps'         => array( 'type' => 'steps', 'label' => 'Adımlar', 'tab' => 'aeo' ),
				'_cr_steps_time'    => array( 'type' => 'number', 'label' => 'Toplam süre (dakika)', 'tab' => 'aeo' ),
				'_cr_reviewer'      => array( 'type' => 'text', 'label' => 'Uzman kontrolü', 'desc' => 'Örn. “Uzm. Dr. Ayşe Yılmaz, Dermatolog”.', 'tab' => 'geo' ),
				'_cr_reviewer_url'  => array( 'type' => 'url', 'label' => 'Uzman profil bağlantısı', 'tab' => 'geo' ),
				'_cr_reviewed_date' => array( 'type' => 'date', 'label' => 'Son inceleme tarihi', 'tab' => 'geo' ),
				'_cr_sources'       => array( 'type' => 'lines', 'label' => 'Kaynaklar', 'desc' => 'Her satır: Kaynak adı | URL. Yapay zekâ motorları ve okur güveni için.', 'tab' => 'geo' ),
				'_cr_ext_image'     => array( 'type' => 'url', 'label' => 'Harici görsel adresi', 'desc' => 'Öne çıkan görsel yoksa kullanılır.', 'tab' => 'geo' ),
			),
		),
		'icerik'  => array(
			'title'  => 'İçerik kartı',
			'types'  => array( 'icerik' ),
			'fields' => array(
				'_cr_inci'       => array( 'type' => 'text', 'label' => 'INCI adı', 'desc' => 'Örn. Niacinamide' ),
				'_cr_aka'        => array( 'type' => 'text', 'label' => 'Diğer adları', 'desc' => 'Virgülle ayırın. Örn. B3 vitamini, nikotinamid' ),
				'_cr_function'   => array( 'type' => 'text', 'label' => 'Tek cümlede ne işe yarar?' ),
				'_cr_benefits'   => array( 'type' => 'lines', 'label' => 'Faydaları', 'desc' => 'Her satır bir fayda.' ),
				'_cr_skin_types' => array( 'type' => 'text', 'label' => 'Uygun cilt tipleri', 'desc' => 'Örn. Tüm cilt tipleri, özellikle yağlı ve karma' ),
				'_cr_conc'       => array( 'type' => 'text', 'label' => 'Etkili konsantrasyon', 'desc' => 'Örn. %2–5' ),
				'_cr_when'       => array( 'type' => 'select', 'label' => 'Ne zaman?', 'choices' => array( '' => '—', 'sabah' => 'Sabah', 'aksam' => 'Akşam', 'ikisi' => 'Sabah ve akşam' ) ),
				'_cr_pregnancy'  => array( 'type' => 'select', 'label' => 'Hamilelik & emzirme', 'choices' => array( '' => '—', 'uygun' => 'Genel olarak uygun kabul edilir', 'danis' => 'Hekime danışılmalı', 'kacin' => 'Önerilmez' ) ),
				'_cr_evidence'   => array( 'type' => 'select', 'label' => 'Bilimsel kanıt düzeyi', 'choices' => array( '' => '—', '1' => 'Sınırlı', '2' => 'Orta', '3' => 'Güçlü' ) ),
				'_cr_irritation' => array( 'type' => 'select', 'label' => 'Tahriş potansiyeli', 'choices' => array( '' => '—', '1' => 'Düşük', '2' => 'Orta', '3' => 'Yüksek' ) ),
				'_cr_pairs'      => array( 'type' => 'lines', 'label' => 'İyi anlaştığı içerikler', 'desc' => 'Her satır bir içerik.' ),
				'_cr_avoid'      => array( 'type' => 'lines', 'label' => 'Dikkatli kombinlenmeli', 'desc' => 'Her satır bir içerik.' ),
			),
		),
		'product' => array(
			'title'  => 'Ürün kartı',
			'types'  => array( 'urun_rehberi' ),
			'fields' => array(
				'_cr_brand'       => array( 'type' => 'text', 'label' => 'Marka' ),
				'_cr_size'        => array( 'type' => 'text', 'label' => 'Hacim' ),
				'_cr_texture'     => array( 'type' => 'text', 'label' => 'Doku', 'desc' => 'Örn. Hafif jel' ),
				'_cr_for'         => array( 'type' => 'text', 'label' => 'Kimler için?' ),
				'_cr_key_ingr'    => array( 'type' => 'lines', 'label' => 'Öne çıkan içerikler', 'desc' => 'Her satır: içerik adı (sözlükte varsa otomatik bağlanır).' ),
				'_cr_usage'       => array( 'type' => 'textarea', 'label' => 'Nasıl kullanılır?' ),
				'_cr_pros'        => array( 'type' => 'lines', 'label' => 'Artıları' ),
				'_cr_cons'        => array( 'type' => 'lines', 'label' => 'Dikkat edilmesi gerekenler' ),
				'_cr_price'       => array( 'type' => 'select', 'label' => 'Fiyat aralığı', 'choices' => array( '' => '—', '1' => '₺ Uygun', '2' => '₺₺ Orta', '3' => '₺₺₺ Yüksek' ) ),
				'_cr_rating'      => array( 'type' => 'select', 'label' => 'Editör puanı', 'desc' => 'Boş bırakılırsa puan ve inceleme şeması gösterilmez.', 'choices' => array( '' => '—', '3' => '3 / 5', '3.5' => '3,5 / 5', '4' => '4 / 5', '4.5' => '4,5 / 5', '5' => '5 / 5' ) ),
				'_cr_verdict'     => array( 'type' => 'textarea', 'label' => 'Editör özeti' ),
				'_cr_info_link'   => array( 'type' => 'url', 'label' => 'Resmî ürün sayfası', 'desc' => 'nofollow olarak eklenir.' ),
				'_cr_independent' => array( 'type' => 'toggle', 'label' => '“Sponsorlu değil” rozeti', 'default' => 1 ),
			),
		),
	);
}

/**
 * Tüm meta anahtarları → alan.
 *
 * @return array
 */
function cr_meta_fields_flat() {
	$out = array();
	foreach ( cr_meta_schema() as $box ) {
		foreach ( $box['fields'] as $k => $f ) {
			$f['types'] = $box['types'];
			$out[ $k ]  = $f;
		}
	}
	$out['_cr_primary_term'] = array( 'type' => 'number', 'types' => array( 'post', 'icerik', 'urun_rehberi' ) );
	return $out;
}

/**
 * Meta anahtarlarını REST için kaydeder (Gutenberg ile uyum).
 */
function cr_register_meta() {
	foreach ( cr_meta_fields_flat() as $key => $f ) {
		$is_arr = in_array( $f['type'], array( 'faq', 'steps' ), true );
		foreach ( $f['types'] as $type ) {
			register_post_meta(
				$type,
				$key,
				array(
					'single'        => true,
					'type'          => $is_arr ? 'array' : 'string',
					'show_in_rest'  => $is_arr ? false : true,
					'auth_callback' => function () {
						return current_user_can( 'edit_posts' );
					},
				)
			);
		}
	}
}
add_action( 'init', 'cr_register_meta', 20 );

/**
 * Meta değeri.
 *
 * @param string $key     Anahtar.
 * @param int    $post_id Yazı.
 * @return mixed
 */
function cr_meta( $key, $post_id = 0 ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	return get_post_meta( $post_id, $key, true );
}

/**
 * SSS maddeleri.
 *
 * @param int $post_id Yazı.
 * @return array
 */
function cr_faq_items( $post_id = 0 ) {
	$items = cr_meta( '_cr_faq', $post_id );
	$out   = array();
	if ( is_array( $items ) ) {
		foreach ( $items as $it ) {
			if ( ! empty( $it['q'] ) && ! empty( $it['a'] ) ) {
				$out[] = array( 'q' => $it['q'], 'a' => $it['a'] );
			}
		}
	}
	return $out;
}

/**
 * HowTo adımları.
 *
 * @param int $post_id Yazı.
 * @return array
 */
function cr_steps( $post_id = 0 ) {
	$items = cr_meta( '_cr_steps', $post_id );
	$out   = array();
	if ( is_array( $items ) ) {
		foreach ( $items as $it ) {
			if ( ! empty( $it['name'] ) ) {
				$out[] = array( 'name' => $it['name'], 'text' => isset( $it['text'] ) ? $it['text'] : '' );
			}
		}
	}
	return $out;
}

/**
 * Kaynaklar.
 *
 * @param int $post_id Yazı.
 * @return array
 */
function cr_sources( $post_id = 0 ) {
	$out = array();
	foreach ( cr_pairs( cr_meta( '_cr_sources', $post_id ) ) as $p ) {
		$out[] = array(
			'title' => $p[0],
			'url'   => isset( $p[1] ) ? $p[1] : '',
		);
	}
	return $out;
}

/**
 * Sözlükte adı geçen içeriğe bağlantı.
 *
 * @param string $name İçerik adı.
 * @return string Bağlantı ya da boş.
 */
function cr_ingredient_link( $name ) {
	static $map = null;
	if ( null === $map ) {
		$map   = array();
		$items = get_posts(
			array(
				'post_type'      => 'icerik',
				'posts_per_page' => 400,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		foreach ( $items as $id ) {
			$map[ cr_lower( get_the_title( $id ) ) ] = get_permalink( $id );
			$inci = get_post_meta( $id, '_cr_inci', true );
			if ( $inci ) {
				$map[ cr_lower( $inci ) ] = get_permalink( $id );
			}
		}
	}
	$k = cr_lower( trim( $name ) );
	return isset( $map[ $k ] ) ? $map[ $k ] : '';
}

/**
 * Türkçe uyumlu küçük harf.
 *
 * @param string $s Metin.
 * @return string
 */
function cr_lower( $s ) {
	$s = str_replace( array( 'I', 'İ' ), array( 'ı', 'i' ), (string) $s );
	return function_exists( 'mb_strtolower' ) ? mb_strtolower( $s, 'UTF-8' ) : strtolower( $s );
}

/**
 * Türkçe uyumlu büyük ilk harf (sözlük harf dizini için).
 *
 * @param string $s Metin.
 * @return string
 */
function cr_first_letter( $s ) {
	$s = trim( (string) $s );
	if ( '' === $s ) {
		return '#';
	}
	$c = function_exists( 'mb_substr' ) ? mb_substr( $s, 0, 1, 'UTF-8' ) : substr( $s, 0, 1 );
	$c = str_replace( array( 'i', 'ı' ), array( 'İ', 'I' ), $c );
	$c = function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $c, 'UTF-8' ) : strtoupper( $c );
	return preg_match( '/\p{L}/u', $c ) ? $c : '#';
}
