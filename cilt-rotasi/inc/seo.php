<?php
/**
 * SEO motoru: başlık, meta açıklama, canonical, robots, Open Graph, Twitter, doğrulama, site haritası, breadcrumb.
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Başlık ayracı.
 *
 * @return string
 */
function cr_title_sep() {
	return cr_opt( 'seo_sep', '—' );
}
add_filter( 'document_title_separator', 'cr_title_sep' );

/**
 * Sayfa SEO başlığı.
 *
 * @return string
 */
function cr_seo_title() {
	$site = get_bloginfo( 'name' );
	$sep  = ' ' . cr_title_sep() . ' ';
	if ( is_front_page() ) {
		$t = cr_opt( 'seo_home_title' );
		return $t ? $t : $site . $sep . get_bloginfo( 'description' );
	}
	if ( is_singular() ) {
		$custom = cr_meta( '_cr_seo_title', get_queried_object_id() );
		return $custom ? $custom : single_post_title( '', false ) . $sep . $site;
	}
	if ( is_home() ) {
		$pid = (int) get_option( 'page_for_posts' );
		$c   = $pid ? cr_meta( '_cr_seo_title', $pid ) : '';
		return $c ? $c : cr_opt( 'blog_title' ) . $sep . $site;
	}
	if ( is_category() || is_tax() || is_tag() ) {
		$term = get_queried_object();
		$c    = get_term_meta( $term->term_id, 'cr_seo_title', true );
		$t    = $c ? $c : $term->name . $sep . $site;
		if ( is_paged() ) {
			$t .= $sep . 'Sayfa ' . get_query_var( 'paged' );
		}
		return $t;
	}
	if ( is_post_type_archive( 'icerik' ) ) {
		return cr_opt( 'glossary_archive_title' ) . ': Kozmetik İçerikler A–Z' . $sep . $site;
	}
	if ( is_post_type_archive( 'urun_rehberi' ) ) {
		return cr_opt( 'products_archive_title' ) . $sep . $site;
	}
	if ( is_search() ) {
		return '“' . get_search_query() . '” için arama sonuçları' . $sep . $site;
	}
	if ( is_author() ) {
		return get_the_author_meta( 'display_name', get_queried_object_id() ) . $sep . 'Yazar' . $sep . $site;
	}
	if ( is_404() ) {
		return 'Sayfa bulunamadı' . $sep . $site;
	}
	return '';
}

/**
 * WordPress başlığını değiştirir.
 *
 * @param string $title Başlık.
 * @return string
 */
function cr_pre_title( $title ) {
	if ( ! cr_seo_active() ) {
		return $title;
	}
	$t = cr_seo_title();
	return $t ? $t : $title;
}
add_filter( 'pre_get_document_title', 'cr_pre_title', 20 );

/**
 * Meta açıklama.
 *
 * @return string
 */
function cr_seo_description() {
	$desc = '';
	if ( is_front_page() ) {
		$desc = cr_opt( 'seo_home_desc' );
	} elseif ( is_singular() ) {
		$id   = get_queried_object_id();
		$desc = cr_meta( '_cr_seo_desc', $id );
		if ( ! $desc ) {
			$desc = cr_meta( '_cr_short_answer', $id );
		}
		if ( ! $desc ) {
			$desc = get_post_field( 'post_excerpt', $id );
		}
		if ( ! $desc ) {
			$desc = get_post_field( 'post_content', $id );
		}
	} elseif ( is_home() ) {
		$desc = cr_opt( 'blog_text' );
	} elseif ( is_category() || is_tax() || is_tag() ) {
		$term = get_queried_object();
		$desc = get_term_meta( $term->term_id, 'cr_seo_desc', true );
		if ( ! $desc ) {
			$desc = $term->description;
		}
		if ( ! $desc ) {
			$desc = $term->name . ' hakkında kanıta dayalı rehberler, bakım önerileri ve içerik analizleri.';
		}
	} elseif ( is_post_type_archive( 'icerik' ) ) {
		$desc = cr_opt( 'glossary_archive_text' );
	} elseif ( is_post_type_archive( 'urun_rehberi' ) ) {
		$desc = cr_opt( 'products_archive_text' );
	}
	$desc = wp_strip_all_tags( strip_shortcodes( (string) $desc ) );
	$desc = trim( preg_replace( '/\s+/', ' ', $desc ) );
	if ( function_exists( 'mb_strlen' ) && mb_strlen( $desc ) > 160 ) {
		$desc = rtrim( mb_substr( $desc, 0, 157 ), " ,.;:" ) . '…';
	}
	return $desc;
}

/**
 * Canonical adres.
 *
 * @return string
 */
function cr_canonical() {
	if ( is_singular() ) {
		$c = cr_meta( '_cr_canonical', get_queried_object_id() );
		if ( $c ) {
			return $c;
		}
		$url  = get_permalink( get_queried_object_id() );
		$page = (int) get_query_var( 'page' );
		return $page > 1 ? trailingslashit( $url ) . user_trailingslashit( $page, 'single_paged' ) : $url;
	}
	if ( is_front_page() ) {
		return home_url( '/' );
	}
	$url = '';
	if ( is_home() ) {
		$pid = (int) get_option( 'page_for_posts' );
		$url = $pid ? get_permalink( $pid ) : home_url( '/' );
	} elseif ( is_category() || is_tax() || is_tag() ) {
		$url = get_term_link( get_queried_object() );
	} elseif ( is_post_type_archive() ) {
		$url = get_post_type_archive_link( get_query_var( 'post_type' ) );
	} elseif ( is_author() ) {
		$url = get_author_posts_url( get_queried_object_id() );
	}
	if ( ! $url || is_wp_error( $url ) ) {
		return '';
	}
	$paged = (int) get_query_var( 'paged' );
	return $paged > 1 ? get_pagenum_link( $paged ) : $url;
}

/**
 * Robots yönergeleri.
 *
 * @param array $robots Yönergeler.
 * @return array
 */
function cr_robots( $robots ) {
	if ( ! cr_seo_active() ) {
		return $robots;
	}
	$noindex = false;
	if ( is_singular() && cr_meta( '_cr_noindex', get_queried_object_id() ) ) {
		$noindex = true;
	} elseif ( is_search() && cr_opt( 'seo_noindex_search' ) ) {
		$noindex = true;
	} elseif ( is_tag() && cr_opt( 'seo_noindex_tags' ) ) {
		$noindex = true;
	} elseif ( is_author() && cr_opt( 'seo_noindex_author' ) ) {
		$noindex = true;
	} elseif ( is_date() && cr_opt( 'seo_noindex_date' ) ) {
		$noindex = true;
	} elseif ( is_404() ) {
		$noindex = true;
	} elseif ( is_page_template( 'page-templates/template-kaydedilenler.php' ) ) {
		$noindex = true; // Ziyaretçiye özel liste; arama sonucunda boş görünür.
	}
	if ( $noindex ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
	} else {
		$robots['max-image-preview'] = 'large';
		$robots['max-snippet']       = '-1';
		$robots['max-video-preview'] = '-1';
	}
	return $robots;
}
add_filter( 'wp_robots', 'cr_robots' );

/**
 * Paylaşım görseli.
 *
 * @return string
 */
function cr_og_image() {
	if ( is_singular() ) {
		$id = get_queried_object_id();
		$og = cr_meta( '_cr_og_image', $id );
		if ( $og ) {
			return cr_img_url( $og, 'large' );
		}
		$img = cr_post_image_url( $id, 'large' );
		if ( $img ) {
			return $img;
		}
	}
	if ( is_category() || is_tax() ) {
		$ti = cr_term_image( get_queried_object() );
		if ( $ti ) {
			return cr_img_url( $ti, 'large' );
		}
	}
	return cr_img_url( cr_opt( 'seo_og_image' ), 'large' );
}

/**
 * Head meta çıktısı.
 */
function cr_seo_head() {
	if ( ! cr_seo_active() ) {
		return;
	}
	$desc  = cr_seo_description();
	$canon = cr_canonical();
	$title = wp_get_document_title();
	$img   = cr_og_image();
	$type  = is_singular( array( 'post', 'icerik', 'urun_rehberi' ) ) ? 'article' : 'website';

	echo "\n<!-- Cilt Rotası SEO -->\n";
	if ( $desc ) {
		echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
	}
	if ( $canon ) {
		echo '<link rel="canonical" href="' . esc_url( $canon ) . '">' . "\n";
	}
	echo '<meta property="og:locale" content="tr_TR">' . "\n";
	echo '<meta property="og:type" content="' . esc_attr( $type ) . '">' . "\n";
	echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '">' . "\n";
	echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
	if ( $desc ) {
		echo '<meta property="og:description" content="' . esc_attr( $desc ) . '">' . "\n";
	}
	if ( $canon ) {
		echo '<meta property="og:url" content="' . esc_url( $canon ) . '">' . "\n";
	}
	if ( $img ) {
		echo '<meta property="og:image" content="' . esc_url( $img ) . '">' . "\n";
		echo '<meta property="og:image:alt" content="' . esc_attr( $title ) . '">' . "\n";
	}
	if ( 'article' === $type ) {
		$id = get_queried_object_id();
		echo '<meta property="article:published_time" content="' . esc_attr( get_post_time( 'c', true, $id ) ) . '">' . "\n";
		echo '<meta property="article:modified_time" content="' . esc_attr( mysql2date( 'c', cr_updated( $id ) ) ) . '">' . "\n";
		$term = cr_primary_term( $id );
		if ( $term ) {
			echo '<meta property="article:section" content="' . esc_attr( $term->name ) . '">' . "\n";
		}
		echo '<meta name="author" content="' . esc_attr( get_the_author_meta( 'display_name', get_post_field( 'post_author', $id ) ) ) . '">' . "\n";
	}
	echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
	$tw = cr_opt( 'seo_twitter' );
	if ( $tw ) {
		echo '<meta name="twitter:site" content="' . esc_attr( $tw ) . '">' . "\n";
	}
	echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '">' . "\n";
	if ( $desc ) {
		echo '<meta name="twitter:description" content="' . esc_attr( $desc ) . '">' . "\n";
	}
	if ( $img ) {
		echo '<meta name="twitter:image" content="' . esc_url( $img ) . '">' . "\n";
	}
	if ( is_singular( array( 'post', 'icerik', 'urun_rehberi' ) ) ) {
		$id = get_queried_object_id();
		echo '<meta name="twitter:label1" content="Okuma süresi">' . "\n";
		echo '<meta name="twitter:data1" content="' . esc_attr( cr_reading_time( $id ) . ' dakika' ) . '">' . "\n";
	}
	// Sayfalanmış arşivlerde önceki/sonraki.
	if ( ! is_singular() ) {
		global $wp_query;
		$paged = max( 1, (int) get_query_var( 'paged' ) );
		if ( $paged > 1 ) {
			echo '<link rel="prev" href="' . esc_url( get_pagenum_link( $paged - 1 ) ) . '">' . "\n";
		}
		if ( $paged < (int) $wp_query->max_num_pages ) {
			echo '<link rel="next" href="' . esc_url( get_pagenum_link( $paged + 1 ) ) . '">' . "\n";
		}
	}
	echo "<!-- / Cilt Rotası SEO -->\n";
}
add_action( 'wp_head', 'cr_seo_head', 2 );

/**
 * Doğrulama kodları (SEO eklentisi olsa da çalışır).
 */
function cr_verification() {
	$map = array(
		'verify_google'    => 'google-site-verification',
		'verify_bing'      => 'msvalidate.01',
		'verify_yandex'    => 'yandex-verification',
		'verify_pinterest' => 'p:domain_verify',
	);
	foreach ( $map as $opt => $name ) {
		$v = trim( (string) cr_opt( $opt ) );
		if ( $v ) {
			if ( preg_match( '/content="([^"]+)"/', $v, $m ) ) {
				$v = $m[1];
			}
			echo '<meta name="' . esc_attr( $name ) . '" content="' . esc_attr( $v ) . '">' . "\n";
		}
	}
}
add_action( 'wp_head', 'cr_verification', 3 );

/**
 * Tema canonical'ı yönettiği için çekirdek canonical'ı kaldır.
 */
function cr_remove_core_canonical() {
	if ( cr_seo_active() ) {
		remove_action( 'wp_head', 'rel_canonical' );
	}
}
add_action( 'wp', 'cr_remove_core_canonical' );

/**
 * Site haritası: noindex içerikleri çıkar, kullanıcı haritasını kaldır.
 *
 * @param array  $args Sorgu.
 * @param string $type Tür.
 * @return array
 */
function cr_sitemap_posts( $args, $type ) {
	$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery
		'relation' => 'OR',
		array(
			'key'     => '_cr_noindex',
			'compare' => 'NOT EXISTS',
		),
		array(
			'key'     => '_cr_noindex',
			'value'   => '1',
			'compare' => '!=',
		),
	);
	return $args;
}
add_filter( 'wp_sitemaps_posts_query_args', 'cr_sitemap_posts', 10, 2 );

/**
 * Site haritasına son değişiklik tarihi ekle.
 *
 * @param array   $entry Kayıt.
 * @param WP_Post $post  Yazı.
 * @return array
 */
function cr_sitemap_lastmod( $entry, $post ) {
	$entry['lastmod'] = mysql2date( 'c', cr_updated( $post->ID ) );
	return $entry;
}
add_filter( 'wp_sitemaps_posts_entry', 'cr_sitemap_lastmod', 10, 2 );

/**
 * Yazar ve etiket (noindex ise) site haritalarını kaldır.
 *
 * @param WP_Sitemaps_Provider $provider Sağlayıcı.
 * @param string               $name     Ad.
 * @return WP_Sitemaps_Provider|false
 */
function cr_sitemap_providers( $provider, $name ) {
	if ( 'users' === $name && cr_opt( 'seo_noindex_author' ) ) {
		return false;
	}
	return $provider;
}
add_filter( 'wp_sitemaps_add_provider', 'cr_sitemap_providers', 10, 2 );

/**
 * Etiket taksonomisini haritadan çıkar (noindex ise).
 *
 * @param array $taxonomies Taksonomiler.
 * @return array
 */
function cr_sitemap_taxonomies( $taxonomies ) {
	if ( cr_opt( 'seo_noindex_tags' ) ) {
		unset( $taxonomies['post_tag'] );
	}
	return $taxonomies;
}
add_filter( 'wp_sitemaps_taxonomies', 'cr_sitemap_taxonomies' );

/**
 * Breadcrumb öğeleri (HTML ve şema ortak).
 *
 * @return array Her öğe: name, url.
 */
function cr_breadcrumbs() {
	$items = array( array( 'name' => 'Ana Sayfa', 'url' => home_url( '/' ) ) );
	if ( is_front_page() ) {
		return array();
	}
	$blog_page = (int) get_option( 'page_for_posts' );
	$blog      = $blog_page ? array( 'name' => get_the_title( $blog_page ), 'url' => get_permalink( $blog_page ) ) : null;

	if ( is_singular( 'post' ) ) {
		$term = cr_primary_term();
		if ( $term ) {
			foreach ( array_reverse( get_ancestors( $term->term_id, 'category' ) ) as $anc ) {
				$a       = get_term( $anc, 'category' );
				$items[] = array( 'name' => $a->name, 'url' => get_term_link( $a ) );
			}
			$items[] = array( 'name' => $term->name, 'url' => get_term_link( $term ) );
		} elseif ( $blog ) {
			$items[] = $blog;
		}
		$items[] = array( 'name' => get_the_title(), 'url' => get_permalink() );
	} elseif ( is_singular( array( 'icerik', 'urun_rehberi' ) ) ) {
		$pt      = get_post_type();
		$items[] = array( 'name' => 'icerik' === $pt ? cr_opt( 'glossary_archive_title' ) : cr_opt( 'products_archive_title' ), 'url' => get_post_type_archive_link( $pt ) );
		$term    = cr_primary_term();
		if ( $term && 'urun_rehberi' === $pt ) {
			$items[] = array( 'name' => $term->name, 'url' => get_term_link( $term ) );
		}
		$items[] = array( 'name' => get_the_title(), 'url' => get_permalink() );
	} elseif ( is_page() ) {
		foreach ( array_reverse( get_post_ancestors( get_the_ID() ) ) as $anc ) {
			$items[] = array( 'name' => get_the_title( $anc ), 'url' => get_permalink( $anc ) );
		}
		$items[] = array( 'name' => get_the_title(), 'url' => get_permalink() );
	} elseif ( is_home() ) {
		if ( $blog ) {
			$items[] = $blog;
		}
	} elseif ( is_category() || is_tax() || is_tag() ) {
		$term = get_queried_object();
		if ( 'urun_turu' === $term->taxonomy ) {
			$items[] = array( 'name' => cr_opt( 'products_archive_title' ), 'url' => get_post_type_archive_link( 'urun_rehberi' ) );
		} elseif ( 'icerik_grubu' === $term->taxonomy ) {
			$items[] = array( 'name' => cr_opt( 'glossary_archive_title' ), 'url' => get_post_type_archive_link( 'icerik' ) );
		} elseif ( 'cilt_sorunu' === $term->taxonomy ) {
			$pc = get_category_by_slug( 'cilt-problemleri' );
			if ( $pc ) {
				$items[] = array( 'name' => $pc->name, 'url' => get_category_link( $pc ) );
			}
		}
		foreach ( array_reverse( get_ancestors( $term->term_id, $term->taxonomy ) ) as $anc ) {
			$a       = get_term( $anc, $term->taxonomy );
			$items[] = array( 'name' => $a->name, 'url' => get_term_link( $a ) );
		}
		$items[] = array( 'name' => $term->name, 'url' => get_term_link( $term ) );
	} elseif ( is_post_type_archive() ) {
		$pt      = get_query_var( 'post_type' );
		$pt      = is_array( $pt ) ? reset( $pt ) : $pt;
		$items[] = array( 'name' => 'icerik' === $pt ? cr_opt( 'glossary_archive_title' ) : cr_opt( 'products_archive_title' ), 'url' => get_post_type_archive_link( $pt ) );
	} elseif ( is_search() ) {
		$items[] = array( 'name' => 'Arama: ' . get_search_query(), 'url' => get_search_link() );
	} elseif ( is_author() ) {
		$items[] = array( 'name' => get_the_author_meta( 'display_name', get_queried_object_id() ), 'url' => get_author_posts_url( get_queried_object_id() ) );
	} elseif ( is_404() ) {
		$items[] = array( 'name' => 'Sayfa bulunamadı', 'url' => '' );
	}
	return $items;
}

/**
 * Breadcrumb HTML.
 *
 * @param string $class Ek sınıf.
 */
function cr_breadcrumb_html( $class = '' ) {
	if ( ! cr_opt( 'seo_breadcrumbs' ) ) {
		return;
	}
	$items = cr_breadcrumbs();
	if ( count( $items ) < 2 ) {
		return;
	}
	echo '<nav class="cr-crumbs ' . esc_attr( $class ) . '" aria-label="Sayfa konumu"><ol>';
	$last = count( $items ) - 1;
	foreach ( $items as $i => $it ) {
		echo '<li>';
		if ( $i < $last && $it['url'] ) {
			echo '<a href="' . esc_url( $it['url'] ) . '">' . esc_html( $it['name'] ) . '</a>';
		} else {
			echo '<span aria-current="page">' . esc_html( $it['name'] ) . '</span>';
		}
		echo '</li>';
	}
	echo '</ol></nav>';
}
