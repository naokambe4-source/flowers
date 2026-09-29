<?php
/**
 * SEO: başlık, meta açıklama, canonical, robots, Open Graph, Twitter Card ve sayfa tipine
 * göre JSON-LD (Organization, LocalBusiness, WebSite, WebPage, Service, Article, BreadcrumbList).
 *
 * Yoast SEO, Rank Math veya AIOSEO etkinse bu modül çıktı üretmez (çakışma olmaz).
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

/**
 * Tema SEO çıktısı etkin mi?
 *
 * @return bool
 */
function ce_seo_enabled() {
	return ! defined( 'WPSEO_VERSION' ) && ! defined( 'RANK_MATH_VERSION' ) && ! defined( 'AIOSEO_VERSION' );
}

add_action( 'after_setup_theme', 'ce_seo_bootstrap', 20 );
/**
 * Kancaları bağlar.
 */
function ce_seo_bootstrap() {
	if ( ! ce_seo_enabled() ) {
		return;
	}
	remove_action( 'wp_head', 'rel_canonical' );
	add_filter( 'pre_get_document_title', 'ce_document_title', 20 );
	add_filter( 'document_title_separator', static fn() => ce_opt( 'seo_separator', '|' ) );
	add_filter( 'wp_robots', 'ce_wp_robots' );
	add_action( 'wp_head', 'ce_seo_head', 3 );
	add_action( 'wp_head', 'ce_schema_output', 30 );
}

/**
 * SEO bağlamındaki yazı (tekil sayfa veya blog sayfası).
 *
 * @return int
 */
function ce_seo_post_id() {
	if ( is_singular() ) {
		return (int) get_queried_object_id();
	}
	if ( is_home() && get_option( 'page_for_posts' ) ) {
		return (int) get_option( 'page_for_posts' );
	}
	if ( is_front_page() && get_option( 'page_on_front' ) ) {
		return (int) get_option( 'page_on_front' );
	}
	return 0;
}

/**
 * Belge başlığı.
 *
 * @param string $title Başlık.
 * @return string
 */
function ce_document_title( $title ) {
	$id = ce_seo_post_id();
	if ( $id && ce_meta( $id, 'seo_title' ) ) {
		return ce_meta( $id, 'seo_title' );
	}
	if ( is_front_page() && ce_opt( 'seo_home_title' ) ) {
		return ce_opt( 'seo_home_title' );
	}
	if ( is_post_type_archive( 'ce_service' ) ) {
		return 'Hizmetlerimiz ' . ce_opt( 'seo_separator', '|' ) . ' ' . ce_opt( 'site_name' );
	}
	return $title;
}

/**
 * Meta açıklama.
 *
 * @return string
 */
function ce_meta_description() {
	$id   = ce_seo_post_id();
	$desc = '';
	if ( $id ) {
		$desc = ce_meta( $id, 'seo_description' );
		if ( ! $desc && 'ce_service' === get_post_type( $id ) ) {
			$desc = ce_meta( $id, 'summary' );
		}
		if ( ! $desc && has_excerpt( $id ) ) {
			$desc = get_the_excerpt( $id );
		}
		if ( ! $desc && ! is_front_page() ) {
			$desc = ce_meta( $id, 'hero_subtitle' );
		}
		if ( ! $desc && ! is_front_page() && ! is_home() ) {
			$desc = wp_trim_words( wp_strip_all_tags( strip_shortcodes( (string) get_post_field( 'post_content', $id ) ) ), 28, '…' );
		}
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$desc = wp_strip_all_tags( term_description() );
	} elseif ( is_post_type_archive( 'ce_service' ) ) {
		$desc = 'Naturel, renkli ve siyah eloksal; alodin, kromat ve kuru film yağlama kaplama hizmetleri. Alüminyum yüzeyler için profesyonel çözümler.';
	}
	if ( ! $desc ) {
		$desc = ce_opt( 'seo_description' );
	}
	$desc = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) $desc ) ) );
	return mb_strlen( $desc ) > 170 ? mb_substr( $desc, 0, 167 ) . '…' : $desc;
}

/**
 * Canonical URL.
 *
 * @return string
 */
function ce_canonical_url() {
	$id = ce_seo_post_id();
	if ( $id && ce_meta( $id, 'seo_canonical' ) ) {
		return ce_meta( $id, 'seo_canonical' );
	}
	$paged = max( 1, (int) get_query_var( 'paged' ) );
	$url   = '';
	if ( is_front_page() ) {
		$url = home_url( '/' );
	} elseif ( is_singular() ) {
		$url = wp_get_canonical_url( get_queried_object_id() );
	} elseif ( is_home() ) {
		$url = $id ? get_permalink( $id ) : home_url( '/' );
	} elseif ( is_post_type_archive() ) {
		$url = get_post_type_archive_link( get_query_var( 'post_type' ) ? get_query_var( 'post_type' ) : 'ce_service' );
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$url = get_term_link( get_queried_object() );
	}
	if ( ! $url || is_wp_error( $url ) ) {
		return '';
	}
	if ( $paged > 1 && ! is_singular() ) {
		$url = trailingslashit( $url ) . 'page/' . $paged . '/';
	}
	return $url;
}

/**
 * Robots yönergeleri.
 *
 * @param array $robots Yönergeler.
 * @return array
 */
function ce_wp_robots( $robots ) {
	$id = ce_seo_post_id();
	if ( is_search() || is_404() ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
	}
	if ( $id && ce_meta( $id, 'seo_noindex' ) ) {
		$robots['noindex'] = true;
	}
	if ( $id && ce_meta( $id, 'seo_nofollow' ) ) {
		$robots['nofollow'] = true;
		unset( $robots['follow'] );
	}
	if ( empty( $robots['noindex'] ) ) {
		$robots['max-image-preview'] = 'large';
	}
	return $robots;
}

/**
 * Paylaşım görseli [url, genişlik, yükseklik].
 *
 * @return array|null
 */
function ce_og_image() {
	$id        = ce_seo_post_id();
	$candidate = 0;
	if ( $id ) {
		$candidate = absint( ce_meta( $id, 'og_image' ) );
		if ( ! $candidate ) {
			$candidate = (int) get_post_thumbnail_id( $id );
		}
		if ( ! $candidate ) {
			$candidate = absint( ce_meta( $id, 'hero_image' ) );
		}
	}
	if ( ! $candidate ) {
		$candidate = absint( ce_opt( 'og_image' ) );
	}
	if ( ! $candidate ) {
		return null;
	}
	$src = wp_get_attachment_image_src( $candidate, 'ce-wide' );
	return $src ? array( $src[0], (int) $src[1], (int) $src[2] ) : null;
}

/**
 * Meta etiketleri.
 */
function ce_seo_head() {
	$desc  = ce_meta_description();
	$canon = ce_canonical_url();
	$id    = ce_seo_post_id();
	$title = wp_get_document_title();
	$og_t  = $id && ce_meta( $id, 'og_title' ) ? ce_meta( $id, 'og_title' ) : $title;
	$og_d  = $id && ce_meta( $id, 'og_description' ) ? ce_meta( $id, 'og_description' ) : $desc;
	$image = ce_og_image();
	$type  = is_singular( 'post' ) ? 'article' : 'website';

	echo "\n<!-- Can Eloksal SEO -->\n";
	if ( $desc ) {
		echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
	}
	if ( $canon ) {
		echo '<link rel="canonical" href="' . esc_url( $canon ) . '">' . "\n";
	}
	$og = array(
		'og:locale'    => 'tr_TR',
		'og:type'      => $type,
		'og:site_name' => ce_opt( 'site_name' ),
		'og:title'     => $og_t,
		'og:description' => $og_d,
		'og:url'       => $canon ? $canon : home_url( add_query_arg( array() ) ),
	);
	foreach ( $og as $prop => $val ) {
		if ( $val ) {
			echo '<meta property="' . esc_attr( $prop ) . '" content="' . esc_attr( $val ) . '">' . "\n";
		}
	}
	if ( $image ) {
		echo '<meta property="og:image" content="' . esc_url( $image[0] ) . '">' . "\n";
		echo '<meta property="og:image:width" content="' . (int) $image[1] . '">' . "\n";
		echo '<meta property="og:image:height" content="' . (int) $image[2] . '">' . "\n";
	}
	if ( 'article' === $type ) {
		echo '<meta property="article:published_time" content="' . esc_attr( get_the_date( 'c' ) ) . '">' . "\n";
		echo '<meta property="article:modified_time" content="' . esc_attr( get_the_modified_date( 'c' ) ) . '">' . "\n";
	}
	echo '<meta name="twitter:card" content="' . ( $image ? 'summary_large_image' : 'summary' ) . '">' . "\n";
	echo '<meta name="twitter:title" content="' . esc_attr( $og_t ) . '">' . "\n";
	if ( $og_d ) {
		echo '<meta name="twitter:description" content="' . esc_attr( $og_d ) . '">' . "\n";
	}
	if ( $image ) {
		echo '<meta name="twitter:image" content="' . esc_url( $image[0] ) . '">' . "\n";
	}
	$tw = ltrim( (string) ce_opt( 'twitter_site' ), '@' );
	if ( $tw ) {
		echo '<meta name="twitter:site" content="@' . esc_attr( $tw ) . '">' . "\n";
	}
	echo "<!-- /Can Eloksal SEO -->\n";
}

/**
 * Logo URL'si (schema).
 *
 * @return string
 */
function ce_schema_logo() {
	foreach ( array( 'logo_dark', 'logo_light' ) as $key ) {
		$id = absint( ce_opt( $key ) );
		if ( $id ) {
			$url = wp_get_attachment_image_url( $id, 'full' );
			if ( $url ) {
				return $url;
			}
		}
	}
	if ( has_site_icon() ) {
		return get_site_icon_url( 512 );
	}
	return CE_URI . '/assets/images/logo-mark.png';
}

/**
 * Adres (schema PostalAddress).
 *
 * @return array
 */
function ce_schema_address() {
	$lines  = ce_lines( ce_opt( 'address' ) );
	$street = $lines[0] ?? '';
	$city   = '';
	$region = '';
	if ( isset( $lines[1] ) ) {
		$parts  = array_map( 'trim', explode( '/', $lines[1] ) );
		$city   = $parts[0] ?? '';
		$region = isset( $parts[1] ) ? mb_convert_case( $parts[1], MB_CASE_TITLE, 'UTF-8' ) : '';
	}
	return array_filter(
		array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => $street,
			'addressLocality' => $city,
			'addressRegion'   => $region,
			'addressCountry'  => 'TR',
		)
	);
}

/**
 * Organization düğümü.
 *
 * @return array
 */
function ce_schema_organization() {
	return array_filter(
		array(
			'@type'     => 'Organization',
			'@id'       => home_url( '/#organization' ),
			'name'      => ce_opt( 'site_name' ),
			'legalName' => ce_opt( 'company_name' ),
			'url'       => home_url( '/' ),
			'logo'      => array( '@type' => 'ImageObject', 'url' => ce_schema_logo() ),
			'email'     => ce_opt( 'email' ),
			'telephone' => ce_opt( 'phone' ),
			'address'   => ce_schema_address(),
			'sameAs'    => array_values( ce_socials() ),
		)
	);
}

/**
 * LocalBusiness düğümü.
 *
 * @return array
 */
function ce_schema_local_business() {
	$node = array(
		'@type'              => 'LocalBusiness',
		'@id'                => home_url( '/#localbusiness' ),
		'name'               => ce_opt( 'site_name' ),
		'url'                => home_url( '/' ),
		'image'              => ce_schema_logo(),
		'telephone'          => ce_opt( 'phone' ),
		'email'              => ce_opt( 'email' ),
		'address'            => ce_schema_address(),
		'parentOrganization' => array( '@id' => home_url( '/#organization' ) ),
	);
	if ( ce_opt( 'geo_lat' ) && ce_opt( 'geo_lng' ) ) {
		$node['geo'] = array(
			'@type'     => 'GeoCoordinates',
			'latitude'  => (float) ce_opt( 'geo_lat' ),
			'longitude' => (float) ce_opt( 'geo_lng' ),
		);
	}
	if ( ce_opt( 'maps_link' ) ) {
		$node['hasMap'] = ce_opt( 'maps_link' );
	}
	return array_filter( $node );
}

/**
 * BreadcrumbList düğümü.
 *
 * @param string $url Sayfa URL'si.
 * @return array|null
 */
function ce_schema_breadcrumb( $url ) {
	$items = ce_breadcrumb_items();
	if ( count( $items ) < 2 ) {
		return null;
	}
	$list = array();
	foreach ( $items as $i => $item ) {
		$list[] = array(
			'@type'    => 'ListItem',
			'position' => $i + 1,
			'name'     => $item['label'],
			'item'     => $item['url'] ? $item['url'] : $url,
		);
	}
	return array(
		'@type'           => 'BreadcrumbList',
		'@id'             => $url . '#breadcrumb',
		'itemListElement' => $list,
	);
}

/**
 * Sayfa tipine göre JSON-LD grafiği.
 */
function ce_schema_output() {
	if ( is_404() || is_search() ) {
		return;
	}
	$url   = ce_canonical_url();
	$url   = $url ? $url : home_url( '/' );
	$id    = ce_seo_post_id();
	$graph = array();
	$tpl   = $id ? get_page_template_slug( $id ) : '';

	$website = array(
		'@type'      => 'WebSite',
		'@id'        => home_url( '/#website' ),
		'url'        => home_url( '/' ),
		'name'       => ce_opt( 'site_name' ),
		'inLanguage' => 'tr-TR',
		'publisher'  => array( '@id' => home_url( '/#organization' ) ),
	);

	$page_type = 'WebPage';
	if ( 'page-templates/contact.php' === $tpl ) {
		$page_type = 'ContactPage';
	} elseif ( 'page-templates/about.php' === $tpl ) {
		$page_type = 'AboutPage';
	} elseif ( is_home() || is_archive() ) {
		$page_type = 'CollectionPage';
	}
	$webpage = array_filter(
		array(
			'@type'       => $page_type,
			'@id'         => $url . '#webpage',
			'url'         => $url,
			'name'        => wp_get_document_title(),
			'description' => ce_meta_description(),
			'inLanguage'  => 'tr-TR',
			'isPartOf'    => array( '@id' => home_url( '/#website' ) ),
		)
	);
	$crumb = ce_schema_breadcrumb( $url );
	if ( $crumb ) {
		$webpage['breadcrumb'] = array( '@id' => $crumb['@id'] );
	}

	if ( is_front_page() ) {
		$graph[] = ce_schema_organization();
		$graph[] = ce_schema_local_business();
		$graph[] = $website;
		$graph[] = $webpage;
	} elseif ( in_array( $page_type, array( 'ContactPage', 'AboutPage' ), true ) ) {
		$graph[] = ce_schema_organization();
		$graph[] = ce_schema_local_business();
		$graph[] = $webpage;
	} elseif ( is_singular( 'ce_service' ) ) {
		$image   = ce_og_image();
		$graph[] = $webpage;
		$graph[] = array_filter(
			array(
				'@type'            => 'Service',
				'@id'              => $url . '#service',
				'name'             => get_the_title( $id ),
				'serviceType'      => get_the_title( $id ),
				'description'      => ce_meta_description(),
				'url'              => $url,
				'image'            => $image ? $image[0] : '',
				'areaServed'       => array( '@type' => 'Country', 'name' => 'Türkiye' ),
				'provider'         => array(
					'@type'     => 'LocalBusiness',
					'@id'       => home_url( '/#localbusiness' ),
					'name'      => ce_opt( 'site_name' ),
					'telephone' => ce_opt( 'phone' ),
					'address'   => ce_schema_address(),
				),
				'mainEntityOfPage' => array( '@id' => $url . '#webpage' ),
			)
		);
	} elseif ( is_singular( 'post' ) ) {
		$image   = ce_og_image();
		$graph[] = $webpage;
		$graph[] = array_filter(
			array(
				'@type'            => 'BlogPosting',
				'@id'              => $url . '#article',
				'headline'         => mb_substr( get_the_title( $id ), 0, 110 ),
				'description'      => ce_meta_description(),
				'datePublished'    => get_the_date( 'c', $id ),
				'dateModified'     => get_the_modified_date( 'c', $id ),
				'image'            => $image ? $image[0] : '',
				'inLanguage'       => 'tr-TR',
				'author'           => array( '@type' => 'Organization', 'name' => ce_opt( 'site_name' ), 'url' => home_url( '/' ) ),
				'publisher'        => array(
					'@type' => 'Organization',
					'@id'   => home_url( '/#organization' ),
					'name'  => ce_opt( 'site_name' ),
					'logo'  => array( '@type' => 'ImageObject', 'url' => ce_schema_logo() ),
				),
				'mainEntityOfPage' => array( '@id' => $url . '#webpage' ),
			)
		);
	} elseif ( is_post_type_archive( 'ce_service' ) ) {
		$list = array();
		foreach ( ce_get_items( 'ce_service', 50 ) as $i => $service ) {
			$list[] = array( '@type' => 'ListItem', 'position' => $i + 1, 'url' => get_permalink( $service ), 'name' => get_the_title( $service ) );
		}
		$webpage['mainEntity'] = array( '@type' => 'ItemList', 'itemListElement' => $list );
		$graph[]               = $webpage;
	} else {
		$graph[] = $webpage;
	}

	if ( $crumb ) {
		$graph[] = $crumb;
	}

	echo '<script type="application/ld+json">' . wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => array_values( array_filter( $graph ) ) ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG ) . '</script>' . "\n"; // phpcs:ignore

	if ( $id && ce_meta( $id, 'schema_json' ) ) {
		$custom = json_decode( ce_meta( $id, 'schema_json' ), true );
		if ( is_array( $custom ) ) {
			echo '<script type="application/ld+json">' . wp_json_encode( $custom, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG ) . '</script>' . "\n"; // phpcs:ignore
		}
	}
}
