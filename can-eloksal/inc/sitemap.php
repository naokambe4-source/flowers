<?php
/**
 * Dinamik /sitemap.xml ve /robots.txt.
 * Taslak, şifreli ve "noindex" işaretli içerikler sitemap'e girmez.
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'ce_sitemap_rewrite' );
/**
 * /sitemap.xml kuralı.
 */
function ce_sitemap_rewrite() {
	if ( ! ce_seo_enabled() ) {
		return;
	}
	add_rewrite_rule( '^sitemap\.xml$', 'index.php?ce_sitemap=1', 'top' );
}

add_filter(
	'query_vars',
	static function ( $vars ) {
		$vars[] = 'ce_sitemap';
		return $vars;
	}
);

add_filter( 'wp_sitemaps_enabled', static fn( $enabled ) => ce_seo_enabled() ? false : $enabled );

add_action( 'template_redirect', 'ce_render_sitemap', 0 );
/**
 * Sitemap çıktısı.
 */
function ce_render_sitemap() {
	if ( ! get_query_var( 'ce_sitemap' ) ) {
		return;
	}
	$urls = ce_sitemap_urls();
	status_header( 200 );
	header( 'Content-Type: application/xml; charset=UTF-8' );
	header( 'X-Robots-Tag: noindex, follow' );
	echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
	foreach ( $urls as $u ) {
		echo "\t<url><loc>" . esc_url( $u['loc'] ) . '</loc>';
		if ( ! empty( $u['lastmod'] ) ) {
			echo '<lastmod>' . esc_html( $u['lastmod'] ) . '</lastmod>';
		}
		echo "</url>\n";
	}
	echo '</urlset>';
	exit;
}

/**
 * Sitemap adresleri.
 *
 * @return array<int,array{loc:string,lastmod:string}>
 */
function ce_sitemap_urls() {
	$urls  = array();
	$seen  = array();
	$front = (int) get_option( 'page_on_front' );
	$add   = static function ( $loc, $lastmod = '' ) use ( &$urls, &$seen ) {
		$key = untrailingslashit( $loc );
		if ( isset( $seen[ $key ] ) ) {
			return;
		}
		$seen[ $key ] = true;
		$urls[]       = array( 'loc' => $loc, 'lastmod' => $lastmod );
	};

	$add( home_url( '/' ), $front ? get_post_modified_time( 'c', true, $front ) : '' );

	$archive = get_post_type_archive_link( 'ce_service' );
	if ( $archive ) {
		$add( $archive );
	}

	$posts = get_posts(
		array(
			'post_type'              => array( 'page', 'ce_service', 'post' ),
			'post_status'            => 'publish',
			'numberposts'            => 2000,
			'has_password'           => false,
			'orderby'                => array( 'post_type' => 'ASC', 'menu_order' => 'ASC', 'date' => 'DESC' ),
			'update_post_term_cache' => false,
			'meta_query'             => array(
				'relation' => 'OR',
				array( 'key' => '_ce_seo_noindex', 'compare' => 'NOT EXISTS' ),
				array( 'key' => '_ce_seo_noindex', 'value' => '1', 'compare' => '!=' ),
			),
		)
	);
	foreach ( $posts as $p ) {
		if ( $p->ID === $front ) {
			continue;
		}
		$add( get_permalink( $p ), get_post_modified_time( 'c', true, $p ) );
	}

	$terms = get_terms( array( 'taxonomy' => array( 'ce_service_cat', 'category' ), 'hide_empty' => true ) );
	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term ) {
			if ( 'uncategorized' === $term->slug || 'genel' === $term->slug ) {
				continue;
			}
			$link = get_term_link( $term );
			if ( ! is_wp_error( $link ) ) {
				$add( $link );
			}
		}
	}
	return $urls;
}

add_filter( 'robots_txt', 'ce_robots_txt', 20, 2 );
/**
 * robots.txt (sunucuda fiziksel robots.txt dosyası yoksa kullanılır).
 *
 * @param string $output Çıktı.
 * @param bool   $public Arama motorlarına açık mı.
 * @return string
 */
function ce_robots_txt( $output, $public ) {
	if ( ! $public ) {
		return "User-agent: *\nDisallow: /\n";
	}
	$path  = wp_parse_url( home_url( '/' ), PHP_URL_PATH );
	$path  = $path ? trailingslashit( $path ) : '/';
	$lines = array(
		'User-agent: *',
		'Disallow: ' . $path . 'wp-admin/',
		'Allow: ' . $path . 'wp-admin/admin-ajax.php',
		'Disallow: ' . $path . 'wp-login.php',
		'Disallow: ' . $path . 'wp-content/uploads/ce-private/',
		'Disallow: ' . $path . '?s=',
		'Disallow: ' . $path . 'search/',
		'',
		'Sitemap: ' . ( ce_seo_enabled() ? home_url( '/sitemap.xml' ) : home_url( '/wp-sitemap.xml' ) ),
	);
	return implode( "\n", $lines ) . "\n";
}
