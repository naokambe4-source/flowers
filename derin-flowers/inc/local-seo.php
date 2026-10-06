<?php
/**
 * Lokasyon SEO: ilçe sayfaları (/cicek-siparisi/bornova/), ilçe listesi,
 * site haritası, yerel işletme şeması, SEO eklentisi (Yoast / Rank Math) uyumu, llms.txt.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Adres öneki.
 *
 * @return string
 */
function df_loc_base() {
	$b = sanitize_title( (string) df_opt( 'loc_base', 'cicek-siparisi' ) );
	return '' !== $b ? $b : 'cicek-siparisi';
}

/**
 * İlçeler.
 *
 * @return array<string, array{name:string, text:string, slug:string}>
 */
function df_loc_items() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	$cache = array();
	if ( ! df_opt( 'loc_on', 1 ) ) {
		return $cache;
	}
	foreach ( df_lines( df_opt( 'loc_items' ), true ) as $row ) {
		$name = trim( $row[0] );
		$slug = sanitize_title( df_loc_ascii( $name ) );
		if ( '' === $slug ) {
			continue;
		}
		$cache[ $slug ] = array(
			'name' => $name,
			'text' => isset( $row[1] ) ? trim( implode( ' | ', array_slice( $row, 1 ) ) ) : '',
			'slug' => $slug,
		);
	}
	return $cache;
}

/**
 * Türkçe karakterleri sadeleştir.
 *
 * @param string $s Metin.
 * @return string
 */
function df_loc_ascii( $s ) {
	return strtolower( remove_accents( strtr( (string) $s, array( 'İ' => 'i', 'I' => 'i', 'ı' => 'i', 'Ş' => 's', 'ş' => 's', 'Ğ' => 'g', 'ğ' => 'g', 'Ü' => 'u', 'ü' => 'u', 'Ö' => 'o', 'ö' => 'o', 'Ç' => 'c', 'ç' => 'c' ) ) ) );
}

/**
 * İlçe sayfası adresi.
 *
 * @param string $slug İlçe (boşsa liste sayfası).
 * @return string
 */
function df_loc_url( $slug = '' ) {
	if ( ! get_option( 'permalink_structure' ) ) {
		return add_query_arg( 'df_loc', $slug ? $slug : '__index', home_url( '/' ) );
	}
	return home_url( user_trailingslashit( df_loc_base() . ( $slug ? '/' . $slug : '' ) ) );
}

/**
 * Metindeki {ilce} ve {cutoff} yer tutucuları.
 *
 * @param string $text Metin.
 * @param array  $loc  İlçe.
 * @return string
 */
function df_loc_vars( $text, $loc ) {
	$text = str_replace( '{ilce}', $loc['name'], (string) $text );
	return function_exists( 'df_vars' ) ? df_vars( $text ) : $text;
}

/**
 * Yönlendirme kuralları.
 */
function df_loc_rewrite() {
	$base = df_loc_base();
	add_rewrite_rule( '^' . $base . '/?$', 'index.php?df_loc=__index', 'top' );
	add_rewrite_rule( '^' . $base . '/sitemap\.xml$', 'index.php?df_loc=__sitemap', 'top' );
	add_rewrite_rule( '^' . $base . '/([^/]+)/?$', 'index.php?df_loc=$matches[1]', 'top' );
	add_rewrite_rule( '^llms\.txt$', 'index.php?df_loc=__llms', 'top' );
	$sig = md5( $base . '|' . DF_VERSION );
	if ( get_option( 'df_loc_rules' ) !== $sig ) {
		update_option( 'df_loc_rules', $sig );
		flush_rewrite_rules( false );
	}
}
add_action( 'init', 'df_loc_rewrite', 20 );

/**
 * Sorgu değişkeni.
 *
 * @param array $vars Değişkenler.
 * @return array
 */
function df_loc_query_vars( $vars ) {
	$vars[] = 'df_loc';
	return $vars;
}
add_filter( 'query_vars', 'df_loc_query_vars' );

/**
 * Şu anki ilçe sayfası.
 *
 * @return string '' | '__index' | slug
 */
function df_loc_current() {
	$v = (string) get_query_var( 'df_loc' );
	if ( '' === $v ) {
		return '';
	}
	if ( '__index' === $v || isset( df_loc_items()[ $v ] ) ) {
		return $v;
	}
	return '';
}

/**
 * Ana sorguyu sade tut (yazı listesi çekilmez).
 *
 * @param WP_Query $q Sorgu.
 */
function df_loc_parse_query( $q ) {
	if ( $q->is_main_query() && '' !== (string) $q->get( 'df_loc' ) ) {
		$q->is_home       = false;
		$q->is_front_page = false;
		$q->is_archive    = false;
		$q->is_singular   = false;
		$q->is_page       = false;
		$q->is_404        = false;
	}
}
add_action( 'parse_query', 'df_loc_parse_query' );

/**
 * Ana sorgu veritabanına gitmesin.
 *
 * @param array|null $posts Yazılar.
 * @param WP_Query   $q     Sorgu.
 * @return array|null
 */
function df_loc_no_posts( $posts, $q ) {
	if ( $q->is_main_query() && '' !== (string) $q->get( 'df_loc' ) ) {
		$q->found_posts = 0;
		return array();
	}
	return $posts;
}
add_filter( 'posts_pre_query', 'df_loc_no_posts', 10, 2 );

/**
 * Geçerli ilçe sayfası 404 sayılmasın.
 *
 * @param bool $pre Önceden işlendi mi.
 * @return bool
 */
function df_loc_pre_404( $pre ) {
	$v = (string) get_query_var( 'df_loc' );
	if ( '' !== $v && ( '' !== df_loc_current() || in_array( $v, array( '__sitemap', '__llms' ), true ) ) ) {
		status_header( 200 );
		return true;
	}
	return $pre;
}
add_filter( 'pre_handle_404', 'df_loc_pre_404' );

/**
 * Site haritası, llms.txt ve bulunamayan ilçe.
 */
function df_loc_template_redirect() {
	$v = (string) get_query_var( 'df_loc' );
	if ( '' === $v ) {
		return;
	}
	if ( '__sitemap' === $v ) {
		df_loc_sitemap();
	}
	if ( '__llms' === $v ) {
		df_loc_llms();
	}
	if ( '' === df_loc_current() ) {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
	}
}
add_action( 'template_redirect', 'df_loc_template_redirect', 5 );

/**
 * Şablon.
 *
 * @param string $template Şablon.
 * @return string
 */
function df_loc_template( $template ) {
	return '' !== df_loc_current() ? DF_DIR . '/template-parts/location/page.php' : $template;
}
add_filter( 'template_include', 'df_loc_template', 99 );

/**
 * Stil.
 */
function df_loc_assets() {
	if ( '' === df_loc_current() ) {
		return;
	}
	wp_enqueue_style( 'df-home', DF_URI . '/assets/css/home.css', array( 'df-main' ), DF_VERSION );
	wp_enqueue_style( 'df-loc', DF_URI . '/assets/css/location.css', array( 'df-main' ), DF_VERSION );
}
add_action( 'wp_enqueue_scripts', 'df_loc_assets', 20 );

/**
 * Body sınıfı.
 *
 * @param array $c Sınıflar.
 * @return array
 */
function df_loc_body_class( $c ) {
	if ( '' !== df_loc_current() ) {
		$c   = array_diff( $c, array( 'home', 'blog' ) );
		$c[] = 'df-loc-page';
	}
	return $c;
}
add_filter( 'body_class', 'df_loc_body_class' );

/* ---------------- Başlık, açıklama, canonical ---------------- */

/**
 * Sayfa başlığı ve açıklaması.
 *
 * @return array{title:string, desc:string, url:string}
 */
function df_loc_meta() {
	$cur = df_loc_current();
	if ( '__index' === $cur ) {
		$city = df_opt( 'df_default_city', 'İzmir' );
		return array(
			'title' => $city . ' İlçelerine Çiçek Siparişi',
			'desc'  => $city . ' genelinde ilçe ilçe çiçek siparişi ve aynı gün teslimat. Size en yakın ilçeyi seçin.',
			'url'   => df_loc_url(),
		);
	}
	$loc = df_loc_items()[ $cur ];
	return array(
		'title' => df_loc_vars( df_opt( 'loc_title', '{ilce} Çiçek Siparişi' ), $loc ),
		'desc'  => wp_strip_all_tags( df_loc_vars( df_opt( 'loc_meta' ), $loc ) ),
		'url'   => df_loc_url( $cur ),
	);
}

/**
 * Belge başlığı.
 *
 * @param string $title Başlık.
 * @return string
 */
function df_loc_doc_title( $title ) {
	return '' !== df_loc_current() ? df_loc_meta()['title'] . ' | ' . get_bloginfo( 'name' ) : $title;
}
add_filter( 'pre_get_document_title', 'df_loc_doc_title', 99 );
add_filter( 'wpseo_title', 'df_loc_doc_title', 99 );
add_filter( 'rank_math/frontend/title', 'df_loc_doc_title', 99 );

/**
 * Açıklama (SEO eklentileri).
 *
 * @param string $d Açıklama.
 * @return string
 */
function df_loc_seo_desc( $d ) {
	return '' !== df_loc_current() ? df_loc_meta()['desc'] : $d;
}
add_filter( 'wpseo_metadesc', 'df_loc_seo_desc', 99 );
add_filter( 'rank_math/frontend/description', 'df_loc_seo_desc', 99 );

/**
 * Canonical (SEO eklentileri).
 *
 * @param string $u Adres.
 * @return string
 */
function df_loc_seo_canonical( $u ) {
	return '' !== df_loc_current() ? df_loc_meta()['url'] : $u;
}
add_filter( 'wpseo_canonical', 'df_loc_seo_canonical', 99 );
add_filter( 'rank_math/frontend/canonical', 'df_loc_seo_canonical', 99 );
add_filter( 'wpseo_opengraph_url', 'df_loc_seo_canonical', 99 );

/**
 * SEO eklentisi yoksa açıklama, canonical, Open Graph ve şema.
 */
function df_loc_head() {
	$cur = df_loc_current();
	if ( '' === $cur ) {
		return;
	}
	$m = df_loc_meta();
	if ( ! defined( 'WPSEO_VERSION' ) && ! defined( 'RANK_MATH_VERSION' ) ) {
		echo '<meta name="description" content="' . esc_attr( $m['desc'] ) . '">' . "\n";
		echo '<link rel="canonical" href="' . esc_url( $m['url'] ) . '">' . "\n";
		echo '<meta property="og:type" content="website"><meta property="og:title" content="' . esc_attr( $m['title'] ) . '"><meta property="og:description" content="' . esc_attr( $m['desc'] ) . '"><meta property="og:url" content="' . esc_url( $m['url'] ) . '">' . "\n";
	}
	$city   = df_opt( 'df_default_city', 'İzmir' );
	$crumbs = array(
		array( '@type' => 'ListItem', 'position' => 1, 'name' => 'Ana Sayfa', 'item' => home_url( '/' ) ),
		array( '@type' => 'ListItem', 'position' => 2, 'name' => $city . ' Çiçek Siparişi', 'item' => df_loc_url() ),
	);
	$graph  = array();
	if ( '__index' !== $cur ) {
		$loc      = df_loc_items()[ $cur ];
		$crumbs[] = array( '@type' => 'ListItem', 'position' => 3, 'name' => $m['title'], 'item' => $m['url'] );
		$biz      = array(
			'@type'      => 'Florist',
			'name'       => get_bloginfo( 'name' ),
			'url'        => home_url( '/' ),
			'areaServed' => array(
				'@type' => 'AdministrativeArea',
				'name'  => $loc['name'] . ', ' . $city,
			),
		);
		if ( df_opt( 'contact_phone1' ) ) {
			$biz['telephone'] = df_opt( 'contact_phone1' );
		}
		if ( df_opt( 'contact_address' ) ) {
			$biz['address'] = array(
				'@type'           => 'PostalAddress',
				'streetAddress'   => wp_strip_all_tags( df_opt( 'contact_address' ) ),
				'addressLocality' => $city,
				'addressCountry'  => 'TR',
			);
		}
		$logo = absint( df_opt( 'logo_image' ) );
		if ( $logo ) {
			$biz['image'] = wp_get_attachment_image_url( $logo, 'full' );
		}
		$graph[] = $biz;
		$faq     = array();
		foreach ( df_loc_faq( $loc ) as $qa ) {
			$faq[] = array(
				'@type'          => 'Question',
				'name'           => $qa[0],
				'acceptedAnswer' => array( '@type' => 'Answer', 'text' => $qa[1] ),
			);
		}
		if ( $faq ) {
			$graph[] = array( '@type' => 'FAQPage', 'mainEntity' => $faq );
		}
	}
	$graph[] = array( '@type' => 'BreadcrumbList', 'itemListElement' => $crumbs );
	echo '<script type="application/ld+json">' . wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => $graph ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}
add_action( 'wp_head', 'df_loc_head', 2 );

/* ---------------- İçerik yardımcıları ---------------- */

/**
 * İlçenin teslimat bölgeleri (bölge listesinden ad eşleşmesi) ve en düşük ücret.
 *
 * @param array $loc İlçe.
 * @return array{zones:string[], min:float|null}
 */
function df_loc_zones( $loc ) {
	$out = array(
		'zones' => array(),
		'min'   => null,
	);
	if ( ! function_exists( 'df_delivery_districts' ) ) {
		return $out;
	}
	$needle = df_loc_ascii( $loc['name'] );
	foreach ( df_delivery_districts() as $d ) {
		if ( false !== strpos( df_loc_ascii( $d['name'] ), $needle ) ) {
			$out['zones'][] = $d['name'];
			$out['min']     = null === $out['min'] ? $d['fee'] : min( $out['min'], $d['fee'] );
		}
	}
	return $out;
}

/**
 * İlçe SSS (gerçek ayarlardan).
 *
 * @param array $loc İlçe.
 * @return array<int, array{0:string,1:string}>
 */
function df_loc_faq( $loc ) {
	$n     = $loc['name'];
	$cut   = (string) df_opt( 'df_cutoff', '16:00' );
	$zones = df_loc_zones( $loc );
	$faq   = array(
		array( $n . "'ye aynı gün çiçek gönderebilir miyim?", 'Evet. Saat ' . $cut . "'a kadar verdiğiniz siparişler " . $n . ' adreslerine aynı gün teslim edilir. Teslimat tarihini ve saat aralığını sipariş sırasında siz seçersiniz.' ),
	);
	if ( null !== $zones['min'] ) {
		$faq[] = array( $n . ' teslimat ücreti ne kadar?', $zones['min'] > 0 ? $n . ' için teslimat ücreti ' . wp_strip_all_tags( wc_price( $zones['min'] ) ) . "'den başlar; kesin ücret adres seçildiğinde otomatik hesaplanır." : $n . ' bölgesine teslimat ücretsizdir.' );
	}
	$faq[] = array( 'Çiçeğe not ekleyebilir miyim?', 'Her siparişe ücretsiz, kişiye özel not kartı ekliyoruz. Ne yazacağınızı bilemezseniz yapay zekâ destekli not önerilerimizden seçebilirsiniz.' );
	return $faq;
}

/* ---------------- Ana sayfa ilçe kısayolları ---------------- */

/**
 * Ana sayfadaki ilçe adının sayfası (varsa).
 *
 * @param string $name İlçe adı.
 * @return string
 */
function df_loc_link_for( $name ) {
	if ( ! df_opt( 'loc_home_links', 1 ) ) {
		return '';
	}
	$slug = sanitize_title( df_loc_ascii( $name ) );
	return isset( df_loc_items()[ $slug ] ) ? df_loc_url( $slug ) : '';
}

/* ---------------- Site haritası, robots, llms.txt ---------------- */

/**
 * İlçe site haritası.
 */
function df_loc_sitemap() {
	header( 'Content-Type: application/xml; charset=utf-8' );
	header( 'X-Robots-Tag: noindex' );
	echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
	echo '<url><loc>' . esc_url( df_loc_url() ) . '</loc><changefreq>weekly</changefreq><priority>0.7</priority></url>' . "\n";
	foreach ( df_loc_items() as $slug => $loc ) {
		echo '<url><loc>' . esc_url( df_loc_url( $slug ) ) . '</loc><changefreq>weekly</changefreq><priority>0.8</priority></url>' . "\n";
	}
	echo '</urlset>';
	exit;
}

/**
 * robots.txt'ye ilçe site haritası.
 *
 * @param string $out    İçerik.
 * @param bool   $public Açık mı.
 * @return string
 */
function df_loc_robots( $out, $public ) {
	$core = function_exists( 'wp_sitemaps_get_server' ) && wp_sitemaps_get_server()->sitemaps_enabled();
	if ( $public && ! $core && df_loc_items() && get_option( 'permalink_structure' ) ) {
		$out .= "\nSitemap: " . esc_url_raw( home_url( '/' . df_loc_base() . '/sitemap.xml' ) ) . "\n";
	}
	return $out;
}
add_filter( 'robots_txt', 'df_loc_robots', 20, 2 );

/**
 * Yoast / Rank Math site haritası dizinine ekle.
 *
 * @param string $xml Ek XML.
 * @return string
 */
function df_loc_sitemap_index( $xml ) {
	if ( df_loc_items() && get_option( 'permalink_structure' ) ) {
		$xml .= '<sitemap><loc>' . esc_url( home_url( '/' . df_loc_base() . '/sitemap.xml' ) ) . '</loc></sitemap>';
	}
	return $xml;
}
add_filter( 'wpseo_sitemap_index', 'df_loc_sitemap_index' );
add_filter( 'rank_math/sitemap/index', 'df_loc_sitemap_index' );

/**
 * WordPress site haritasına ilçe sayfaları.
 */
function df_loc_core_sitemap() {
	if ( ! class_exists( 'WP_Sitemaps_Provider' ) || ! df_loc_items() ) {
		return;
	}
	require_once DF_DIR . '/inc/local-seo-sitemap.php';
	wp_register_sitemap_provider( 'ilceler', new DF_Loc_Sitemap_Provider() );
}
add_action( 'init', 'df_loc_core_sitemap', 30 );

/**
 * llms.txt: yapay zekâ arama motorları için site özeti.
 */
function df_loc_llms() {
	if ( ! df_opt( 'loc_llms', 1 ) ) {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		return;
	}
	header( 'Content-Type: text/plain; charset=utf-8' );
	$city  = df_opt( 'df_default_city', 'İzmir' );
	$lines = array( '# ' . get_bloginfo( 'name' ), '', '> ' . ( get_bloginfo( 'description' ) ? get_bloginfo( 'description' ) : $city . ' çiçekçi — online çiçek siparişi ve aynı gün teslimat.' ), '' );
	$lines[] = 'Şehir: ' . $city . '. Aynı gün teslimat için son sipariş saati: ' . df_opt( 'df_cutoff', '16:00' ) . '.';
	if ( df_opt( 'contact_phone1' ) ) {
		$lines[] = 'Telefon: ' . df_opt( 'contact_phone1' );
	}
	if ( df_opt( 'contact_address' ) ) {
		$lines[] = 'Adres: ' . wp_strip_all_tags( df_opt( 'contact_address' ) );
	}
	$lines[] = '';
	$lines[] = '## Sayfalar';
	$lines[] = '- [Ana sayfa](' . home_url( '/' ) . ')';
	if ( function_exists( 'wc_get_page_permalink' ) ) {
		$lines[] = '- [Tüm çiçekler](' . wc_get_page_permalink( 'shop' ) . ')';
	}
	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'parent'     => 0,
			'number'     => 30,
		)
	);
	if ( ! is_wp_error( $terms ) && $terms ) {
		$lines[] = '';
		$lines[] = '## Kategoriler';
		foreach ( $terms as $t ) {
			if ( 'uncategorized' !== $t->slug ) {
				$lines[] = '- [' . html_entity_decode( $t->name, ENT_QUOTES, 'UTF-8' ) . '](' . get_term_link( $t ) . ')';
			}
		}
	}
	if ( df_loc_items() ) {
		$lines[] = '';
		$lines[] = '## Teslimat yapılan ilçeler';
		foreach ( df_loc_items() as $slug => $loc ) {
			$lines[] = '- [' . df_loc_vars( df_opt( 'loc_title', '{ilce} Çiçek Siparişi' ), $loc ) . '](' . df_loc_url( $slug ) . ')';
		}
	}
	echo implode( "\n", $lines ) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput
	exit;
}

/**
 * "BAYRAKLI ŞEHİR HASTANESİ" → "Bayraklı Şehir Hastanesi".
 *
 * @param string $s Metin.
 * @return string
 */
function df_loc_title_case( $s ) {
	$s     = mb_strtolower( strtr( (string) $s, array( 'I' => 'ı', 'İ' => 'i' ) ), 'UTF-8' );
	$words = explode( ' ', $s );
	foreach ( $words as &$w ) {
		if ( '' === $w ) {
			continue;
		}
		$first = mb_substr( $w, 0, 1, 'UTF-8' );
		$first = 'i' === $first ? 'İ' : ( 'ı' === $first ? 'I' : mb_strtoupper( $first, 'UTF-8' ) );
		$w     = $first . mb_substr( $w, 1, null, 'UTF-8' );
	}
	return implode( ' ', $words );
}
