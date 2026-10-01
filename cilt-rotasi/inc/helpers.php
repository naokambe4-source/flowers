<?php
/**
 * Yardımcı fonksiyonlar: seçenekler, görseller, bağlantılar, yazı bilgileri.
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Şemadan varsayılan değerler.
 *
 * @return array
 */
function cr_defaults() {
	static $defaults = null;
	if ( null !== $defaults ) {
		return $defaults;
	}
	$defaults = array();
	foreach ( cr_schema_fields() as $id => $field ) {
		if ( 'sections' === $field['type'] ) {
			$defaults[ $id ] = cr_default_sections();
			continue;
		}
		$defaults[ $id ] = isset( $field['default'] ) ? $field['default'] : ( 'repeater' === $field['type'] ? array() : '' );
	}
	return $defaults;
}

/**
 * Varsayılan bölüm listesi.
 *
 * @return array
 */
function cr_default_sections() {
	$out = array();
	$off = cr_home_sections_off();
	foreach ( array_keys( cr_home_section_labels() ) as $id ) {
		$out[] = array( 'id' => $id, 'on' => in_array( $id, $off, true ) ? 0 : 1 );
	}
	return $out;
}

/**
 * Tüm seçenekler (varsayılanlarla birleşik).
 *
 * @return array
 */
function cr_options() {
	global $cr_options_cache;
	if ( null === $cr_options_cache ) {
		$saved            = get_option( CR_OPTION, array() );
		$cr_options_cache = array_merge( cr_defaults(), is_array( $saved ) ? $saved : array() );
	}
	return $cr_options_cache;
}

/**
 * Önbelleği temizler (kayıttan sonra).
 */
function cr_flush_options_cache() {
	global $cr_options_cache;
	$cr_options_cache = null;
}

/**
 * Tek seçenek.
 *
 * @param string $key     Anahtar.
 * @param mixed  $default Varsayılan.
 * @return mixed
 */
function cr_opt( $key, $default = '' ) {
	$o = cr_options();
	return isset( $o[ $key ] ) ? $o[ $key ] : $default;
}

/**
 * Kullanıcı canlı düzenleme yapabilir mi?
 *
 * @return bool
 */
function cr_can_edit() {
	return is_user_logged_in() && current_user_can( 'edit_theme_options' );
}

/**
 * Canlı düzenleyici için metin niteliği (yalnızca yetkili kullanıcıya).
 *
 * @param string $path Seçenek yolu (örn. hero_title, quick_items.0.title).
 * @return string
 */
function cr_edit( $path ) {
	return cr_can_edit() ? ' data-cr="' . esc_attr( $path ) . '"' : '';
}

/**
 * Canlı düzenleyici görsel niteliği.
 *
 * @param string $path Seçenek yolu.
 * @return string
 */
function cr_edit_img( $path ) {
	return cr_can_edit() ? ' data-cr-img="' . esc_attr( $path ) . '"' : '';
}

/**
 * Seçenek metnini yazdırır.
 *
 * @param string $key Anahtar.
 */
function cr_t( $key ) {
	echo esc_html( cr_opt( $key ) );
}

/**
 * Göreli adresi tam adrese çevirir.
 *
 * @param string $url Adres.
 * @return string
 */
function cr_url( $url ) {
	$url = trim( (string) $url );
	if ( '' === $url ) {
		return '';
	}
	if ( '/' === $url[0] && ( ! isset( $url[1] ) || '/' !== $url[1] ) ) {
		return home_url( $url );
	}
	if ( '#' === $url[0] ) {
		return $url;
	}
	return $url;
}

/**
 * Görsel değerinin (ek kimliği veya URL) adresini döndürür.
 *
 * @param mixed  $value Değer.
 * @param string $size  Boyut.
 * @return string
 */
function cr_img_url( $value, $size = 'large' ) {
	if ( is_numeric( $value ) && (int) $value > 0 ) {
		$src = wp_get_attachment_image_url( (int) $value, $size );
		return $src ? $src : '';
	}
	return is_string( $value ) ? esc_url_raw( $value ) : '';
}

/**
 * Görsel etiketi üretir. Ek kimliğinde srcset/sizes, URL'de basit img.
 *
 * @param mixed  $value Değer.
 * @param string $size  Boyut.
 * @param array  $attr  Nitelikler (alt, class, sizes, loading, fetchpriority, width, height).
 * @return string
 */
function cr_img( $value, $size = 'large', $attr = array() ) {
	$attr = wp_parse_args(
		$attr,
		array(
			'alt'      => '',
			'class'    => '',
			'loading'  => 'lazy',
			'decoding' => 'async',
		)
	);
	if ( is_numeric( $value ) && (int) $value > 0 ) {
		if ( '' === $attr['alt'] ) {
			unset( $attr['alt'] );
		}
		$html = wp_get_attachment_image( (int) $value, $size, false, $attr );
		if ( $html ) {
			return $html;
		}
	}
	$src = cr_img_url( $value, $size );
	if ( ! $src ) {
		return '<span class="cr-img-empty ' . esc_attr( $attr['class'] ) . '" aria-hidden="true">' . cr_icon( 'leaf', 30 ) . '</span>';
	}
	$out = '<img src="' . esc_url( $src ) . '"';
	foreach ( $attr as $k => $v ) {
		if ( '' === $v && 'alt' !== $k ) {
			continue;
		}
		$out .= ' ' . esc_attr( $k ) . '="' . esc_attr( $v ) . '"';
	}
	return $out . '>';
}

/**
 * Satır listesini diziye çevirir.
 *
 * @param string $text Metin.
 * @return array
 */
function cr_lines( $text ) {
	$lines = preg_split( '/\r\n|\r|\n/', (string) $text );
	return array_values( array_filter( array_map( 'trim', $lines ), 'strlen' ) );
}

/**
 * "A | B | C" biçimli satırları diziye çevirir.
 *
 * @param string $text Metin.
 * @return array
 */
function cr_pairs( $text ) {
	$out = array();
	foreach ( cr_lines( $text ) as $line ) {
		$out[] = array_map( 'trim', explode( '|', $line ) );
	}
	return $out;
}

/**
 * Ana sayfa bölümleri (sıralı, açık/kapalı).
 *
 * @return array
 */
function cr_sections() {
	$saved  = cr_opt( 'sections' );
	$labels = cr_home_section_labels();
	$rename = array( 'quick' => 'categories', 'guides' => 'journal', 'products' => 'catalog' );
	$off    = cr_home_sections_off();
	$out    = array();
	$seen   = array();
	if ( is_array( $saved ) ) {
		foreach ( $saved as $s ) {
			if ( ! isset( $s['id'] ) ) {
				continue;
			}
			$id = isset( $rename[ $s['id'] ] ) ? $rename[ $s['id'] ] : $s['id'];
			if ( isset( $labels[ $id ] ) && ! isset( $seen[ $id ] ) ) {
				$out[]       = array( 'id' => $id, 'on' => empty( $s['on'] ) ? 0 : 1 );
				$seen[ $id ] = true;
			}
		}
	}
	foreach ( array_keys( $labels ) as $id ) {
		if ( ! isset( $seen[ $id ] ) ) {
			$out[] = array( 'id' => $id, 'on' => in_array( $id, $off, true ) ? 0 : 1 );
		}
	}
	return $out;
}

/**
 * Bölüm sarmalayıcı nitelikleri (canlı düzenleyici için).
 *
 * @param string $id Bölüm kimliği.
 * @return string
 */
function cr_section_attr( $id ) {
	$labels = cr_home_section_labels();
	$out    = ' id="cr-' . esc_attr( $id ) . '"';
	if ( cr_can_edit() ) {
		$out .= ' data-cr-section="' . esc_attr( $id ) . '" data-cr-label="' . esc_attr( isset( $labels[ $id ] ) ? $labels[ $id ] : $id ) . '"';
	}
	return $out;
}

/**
 * Logo çıktısı.
 *
 * @param string $context header|footer.
 * @return string
 */
function cr_logo( $context = 'header' ) {
	$img  = cr_opt( 'logo_image' );
	$name = cr_opt( 'logo_text', get_bloginfo( 'name' ) );
	$html = '<a class="cr-logo cr-logo--' . esc_attr( $context ) . '" href="' . esc_url( home_url( '/' ) ) . '" rel="home" aria-label="' . esc_attr( $name . ' — Ana sayfa' ) . '">';
	if ( $img && 'header' === $context ) {
		$h     = (int) cr_opt( 'logo_height', 38 );
		$html .= cr_img(
			$img,
			'medium',
			array(
				'alt'     => $name,
				'class'   => 'cr-logo__img',
				'loading' => 'eager',
				'style'   => 'height:' . $h . 'px;width:auto',
			)
		);
	} else {
		if ( cr_opt( 'logo_mark' ) ) {
			$html .= '<span class="cr-logo__mark" aria-hidden="true"><svg viewBox="0 0 32 32" width="26" height="26"><path d="M6 26C6 14 13 6 27 5c-1 14-9 21-21 21z" fill="currentColor" opacity=".18"/><path d="M6 26C6 14 13 6 27 5c-1 14-9 21-21 21zM6 26 19 13" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg></span>';
		}
		$html .= '<span class="cr-logo__text' . ( cr_opt( 'logo_upper' ) ? ' is-upper' : '' ) . '"' . ( 'header' === $context ? cr_edit( 'logo_text' ) : '' ) . '>' . esc_html( $name ) . '</span>';
	}
	return $html . '</a>';
}

/**
 * Yazının görseli (öne çıkan görsel ya da demo harici görsel).
 *
 * @param int    $post_id Yazı.
 * @param string $size    Boyut.
 * @param array  $attr    Nitelikler.
 * @return string
 */
function cr_post_image( $post_id, $size = 'cr-card', $attr = array() ) {
	$attr = wp_parse_args( $attr, array( 'alt' => '' ) );
	if ( has_post_thumbnail( $post_id ) ) {
		$thumb_attr = $attr;
		if ( '' === $thumb_attr['alt'] ) {
			unset( $thumb_attr['alt'] );
		}
		return get_the_post_thumbnail( $post_id, $size, $thumb_attr );
	}
	$ext = get_post_meta( $post_id, '_cr_ext_image', true );
	if ( '' === $attr['alt'] ) {
		$attr['alt'] = get_the_title( $post_id );
	}
	return cr_img( $ext, $size, $attr );
}

/**
 * Yazı görseli adresi.
 *
 * @param int    $post_id Yazı.
 * @param string $size    Boyut.
 * @return string
 */
function cr_post_image_url( $post_id, $size = 'large' ) {
	if ( has_post_thumbnail( $post_id ) ) {
		return (string) get_the_post_thumbnail_url( $post_id, $size );
	}
	return (string) get_post_meta( $post_id, '_cr_ext_image', true );
}

/**
 * Tahmini okuma süresi (dakika).
 *
 * @param int $post_id Yazı.
 * @return int
 */
function cr_reading_time( $post_id = 0 ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	$words   = cr_word_count( get_post_field( 'post_content', $post_id ) );
	return max( 1, (int) ceil( $words / 200 ) );
}

/**
 * Kelime sayısı (Türkçe karakterler dahil).
 *
 * @param string $content İçerik.
 * @return int
 */
function cr_word_count( $content ) {
	$text = wp_strip_all_tags( strip_shortcodes( (string) $content ) );
	$m    = array();
	return (int) preg_match_all( '/[\p{L}\p{N}’\']+/u', $text, $m );
}

/**
 * Yazının ana kategorisi.
 *
 * @param int $post_id Yazı.
 * @return WP_Term|null
 */
function cr_primary_term( $post_id = 0 ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	$type    = get_post_type( $post_id );
	$tax     = 'post' === $type ? 'category' : ( 'icerik' === $type ? 'icerik_grubu' : ( 'urun_rehberi' === $type ? 'urun_turu' : '' ) );
	if ( ! $tax ) {
		return null;
	}
	$primary = (int) get_post_meta( $post_id, '_cr_primary_term', true );
	if ( $primary ) {
		$t = get_term( $primary, $tax );
		if ( $t && ! is_wp_error( $t ) ) {
			return $t;
		}
	}
	$terms = get_the_terms( $post_id, $tax );
	if ( $terms && ! is_wp_error( $terms ) ) {
		foreach ( $terms as $t ) {
			if ( 'uncategorized' !== $t->slug && 'genel' !== $t->slug ) {
				return $t;
			}
		}
		return $terms[0];
	}
	return null;
}

/**
 * Kategori/etiket rozeti.
 *
 * @param int    $post_id Yazı.
 * @param string $class   Sınıf.
 * @return string
 */
function cr_term_badge( $post_id = 0, $class = '' ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	$type    = get_post_type( $post_id );
	$term    = cr_primary_term( $post_id );
	if ( $term ) {
		return '<span class="cr-badge ' . esc_attr( $class ) . '">' . esc_html( $term->name ) . '</span>';
	}
	$labels = array( 'icerik' => 'İçerik', 'urun_rehberi' => 'Ürün rehberi', 'page' => 'Sayfa' );
	return isset( $labels[ $type ] ) ? '<span class="cr-badge ' . esc_attr( $class ) . '">' . esc_html( $labels[ $type ] ) . '</span>' : '';
}

/**
 * Yazı özeti (kısa).
 *
 * @param int $post_id Yazı.
 * @param int $words   Kelime.
 * @return string
 */
function cr_excerpt( $post_id = 0, $words = 22 ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	$post    = get_post( $post_id );
	if ( ! $post ) {
		return '';
	}
	$text = has_excerpt( $post_id ) ? $post->post_excerpt : get_post_meta( $post_id, '_cr_short_answer', true );
	if ( ! $text ) {
		$text = $post->post_content;
	}
	return wp_trim_words( wp_strip_all_tags( strip_shortcodes( $text ) ), $words, '…' );
}

/**
 * Okunma sayısı.
 *
 * @param int $post_id Yazı.
 * @return int
 */
function cr_views( $post_id ) {
	return (int) get_post_meta( $post_id, '_cr_views', true );
}

/**
 * Sosyal bağlantılar.
 *
 * @return array<string,string> ikon => url
 */
function cr_social_links() {
	$out = array();
	foreach ( array( 'instagram', 'pinterest', 'tiktok', 'youtube', 'x' ) as $k ) {
		$u = cr_opt( 'social_' . $k );
		if ( $u ) {
			$out[ $k ] = $u;
		}
	}
	return $out;
}

/**
 * Tarih (Türkçe biçim).
 *
 * @param string $date MySQL tarih ya da zaman damgası.
 * @return string
 */
function cr_date( $date ) {
	$ts = is_numeric( $date ) ? (int) $date : strtotime( $date );
	return date_i18n( 'j F Y', $ts );
}

/**
 * Yazının son güncelleme tarihi (elle girilmiş inceleme tarihi öncelikli).
 *
 * @param int $post_id Yazı.
 * @return string Y-m-d H:i:s
 */
function cr_updated( $post_id ) {
	$reviewed = get_post_meta( $post_id, '_cr_reviewed_date', true );
	$modified = get_post_field( 'post_modified', $post_id );
	if ( $reviewed && strtotime( $reviewed ) > strtotime( $modified ) ) {
		return $reviewed;
	}
	return $modified;
}

/**
 * Bir sayfa şablonunu kullanan ilk sayfanın bağlantısı.
 *
 * @param string $template Şablon dosyası.
 * @param string $fallback Yedek yol.
 * @return string
 */
function cr_template_url( $template, $fallback ) {
	$pages = get_posts(
		array(
			'post_type'      => 'page',
			'posts_per_page' => 1,
			'meta_key'       => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'     => $template, // phpcs:ignore WordPress.DB.SlowDBQuery
			'fields'         => 'ids',
		)
	);
	return $pages ? get_permalink( $pages[0] ) : home_url( $fallback );
}

/**
 * Kaydedilenler sayfasının adresi.
 *
 * @return string
 */
function cr_saved_url() {
	static $u = null;
	if ( null === $u ) {
		$u = cr_template_url( 'page-templates/template-kaydedilenler.php', '/kaydedilenler/' );
	}
	return $u;
}

/**
 * Kaydet düğmesi.
 *
 * @param int    $post_id Yazı.
 * @param string $class   Sınıf.
 * @param bool   $label   Etiket göster.
 * @return string
 */
function cr_save_button( $post_id, $class = '', $label = false ) {
	$data = array(
		'id'    => $post_id,
		'url'   => get_permalink( $post_id ),
		'title' => get_the_title( $post_id ),
		'img'   => cr_post_image_url( $post_id, 'cr-card' ),
		'cat'   => wp_strip_all_tags( cr_term_badge( $post_id ) ),
	);
	return sprintf(
		'<button type="button" class="cr-save %1$s" data-save="%2$s" aria-pressed="false" aria-label="%3$s">%4$s%5$s%6$s</button>',
		esc_attr( $class ),
		esc_attr( wp_json_encode( $data ) ),
		esc_attr( 'Kaydet: ' . get_the_title( $post_id ) ),
		cr_icon( 'bookmark', 20, 'cr-save__off' ),
		cr_icon( 'bookmarked', 20, 'cr-save__on' ),
		$label ? '<span class="cr-save__label">Kaydet</span>' : ''
	);
}

/**
 * Dizide nokta yoluyla değer alır.
 *
 * @param array  $arr  Dizi.
 * @param string $path Yol.
 * @return mixed
 */
function cr_array_get( $arr, $path ) {
	foreach ( explode( '.', $path ) as $k ) {
		if ( ! is_array( $arr ) || ! array_key_exists( $k, $arr ) ) {
			return null;
		}
		$arr = $arr[ $k ];
	}
	return $arr;
}

/**
 * SEO eklentisi etkin mi?
 *
 * @return string Eklenti adı ya da boş.
 */
function cr_seo_plugin() {
	if ( defined( 'WPSEO_VERSION' ) ) {
		return 'Yoast SEO';
	}
	if ( defined( 'RANK_MATH_VERSION' ) ) {
		return 'Rank Math';
	}
	if ( defined( 'AIOSEO_VERSION' ) ) {
		return 'All in One SEO';
	}
	if ( defined( 'SEOPRESS_VERSION' ) ) {
		return 'SEOPress';
	}
	if ( defined( 'THE_SEO_FRAMEWORK_VERSION' ) ) {
		return 'The SEO Framework';
	}
	return '';
}

/**
 * Tema SEO motoru meta etiket basmalı mı?
 *
 * @return bool
 */
function cr_seo_active() {
	return cr_opt( 'seo_enable' ) && '' === cr_seo_plugin();
}

/**
 * Rehberler asimetrik ızgarası (ana sayfa ve filtre REST yanıtı ortak).
 *
 * @param string $cat   Kategori slug'ı (boş = tümü).
 * @param int    $count Yazı sayısı.
 * @return string
 */
function cr_guides_grid( $cat = '', $count = 6 ) {
	$args = array(
		'post_type'           => 'post',
		'posts_per_page'      => $count,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	);
	if ( $cat ) {
		$args['category_name'] = $cat;
	}
	$q = new WP_Query( $args );
	if ( ! $q->have_posts() ) {
		return '<p class="cr-empty">Bu başlıkta henüz rehber yok. Yakında burada olacak.</p>';
	}
	ob_start();
	echo '<div class="cr-journal__grid">';
	while ( $q->have_posts() ) {
		$q->the_post();
		get_template_part( 'template-parts/cards/card', null, array( 'variant' => 'journal' ) );
	}
	echo '</div>';
	wp_reset_postdata();
	return ob_get_clean();
}

/**
 * REST: filtreli rehber ızgarası.
 */
function cr_guides_route() {
	register_rest_route(
		'cilt-rotasi/v1',
		'/guides',
		array(
			'methods'             => 'GET',
			'permission_callback' => '__return_true',
			'callback'            => function ( $req ) {
				$cat = sanitize_title( (string) $req['cat'] );
				return new WP_REST_Response( array( 'html' => cr_guides_grid( $cat, (int) cr_opt( 'guides_count', 6 ) ) ), 200 );
			},
		)
	);
}
add_action( 'rest_api_init', 'cr_guides_route' );

/**
 * Sürüm geçişi: v1.0 varsayılanlarıyla kayıtlı kalmış değerleri kaldırır; böylece yeni editoryal
 * tasarımın varsayılanları devreye girer. Kullanıcının değiştirdiği değerlere dokunulmaz.
 */
function cr_migrate() {
	if ( version_compare( (string) get_option( 'cr_theme_version', '1.0.0' ), CR_VERSION, '>=' ) ) {
		return;
	}
	$saved = get_option( CR_OPTION, array() );
	if ( is_array( $saved ) && $saved ) {
		$old = array(
			'c_green' => '#24483F', 'c_green2' => '#3B5E53', 'c_sage' => '#AAB7A2', 'c_sage_light' => '#E6ECE1',
			'c_cream' => '#FBF8F2', 'c_white' => '#FFFDFC', 'c_beige' => '#E9DDCE', 'c_gold' => '#C9A06B',
			'c_text' => '#28322E', 'c_muted' => '#68716D', 'font_heading' => 'DM Serif Display', 'radius' => 22,
			'logo_mark' => 1, 'header_cta_text' => 'Cildini Keşfet', 'header_cta_url' => '/cilt-testi/',
			'hero_eyebrow' => 'Bağımsız cilt bilgisi platformu', 'hero_title' => 'Cildini tanı.',
			'hero_title_accent' => 'Bakımını bilinçle şekillendir.', 'hero_cta1_text' => 'Cildini Keşfet',
			'hero_cta1_url' => '/cilt-testi/', 'hero_cta2_text' => 'Rehberlere Göz At', 'hero_chip_on' => 1,
			'hero_text' => 'Cilt yapısından bakım rutinlerine, içeriklerden dermokozmetik ürünlere kadar ihtiyacın olan bilgiyi sade ve anlaşılır şekilde keşfet.',
			'hero_image_alt' => 'Krem ve yeşil tonlarda doğal cilt bakım kompozisyonu',
			'manifesto_quote' => 'Cilt bakımı, bedeninle kurduğun en dürüst iletişimdir.', 'manifesto_by' => 'Cilt Rotası Manifestosu',
			'glossary_title' => 'İçerik sözlüğü', 'glossary_text' => 'INCI listesindeki her molekülün ne yaptığını saniyeler içinde öğren.',
			'news_title' => 'Cilt bakımında bilgi karmaşasını azalt.', 'news_text' => 'Yeni rehberleri ve güncel içerikleri kaçırma.',
			'news_placeholder' => 'E-posta adresin', 'news_button' => 'Katıl', 'news_success' => 'Teşekkürler! Listeye eklendin.',
			'news_note' => 'Ayda en fazla iki e-posta. İstediğin an tek tıkla ayrılabilirsin.',
			'footer_text' => 'Cilt bakımını daha anlaşılır hale getiren bağımsız bilgi platformu.', 'footer_col1' => 'Keşfet',
			'footer_copy' => '© {yil} Cilt Rotası. Tüm hakları saklıdır.', 'quiz_points' => "Alın · sebum\nYanaklar · nem\nT bölgesi · gözenek\nÇene · hassasiyet",
		);
		foreach ( $old as $k => $v ) {
			if ( array_key_exists( $k, $saved ) && (string) $saved[ $k ] === (string) $v ) {
				unset( $saved[ $k ] );
			}
		}
		unset( $saved['sections'] );
		update_option( CR_OPTION, $saved );
		cr_flush_options_cache();
	}
	update_option( 'cr_theme_version', CR_VERSION );
}
add_action( 'init', 'cr_migrate', 1 );
