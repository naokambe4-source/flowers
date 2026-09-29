<?php
/**
 * Breadcrumb: HTML çıktısı ve BreadcrumbList şeması aynı veri kaynağını kullanır.
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

/**
 * Geçerli sayfanın breadcrumb öğeleri.
 *
 * @return array<int,array{label:string,url:string}>
 */
function ce_breadcrumb_items() {
	static $items = null;
	if ( null !== $items ) {
		return $items;
	}
	$items = array( array( 'label' => 'Ana Sayfa', 'url' => home_url( '/' ) ) );
	$blog  = (int) get_option( 'page_for_posts' );
	$blog_item = $blog ? array( 'label' => get_the_title( $blog ), 'url' => get_permalink( $blog ) ) : null;

	if ( is_front_page() ) {
		return $items;
	}

	if ( is_home() ) {
		$items[] = array( 'label' => $blog ? get_the_title( $blog ) : 'Blog', 'url' => '' );
	} elseif ( is_singular( 'ce_service' ) ) {
		$items[] = array( 'label' => 'Hizmetler', 'url' => ce_services_url() );
		$items[] = array( 'label' => get_the_title(), 'url' => '' );
	} elseif ( is_post_type_archive( 'ce_service' ) ) {
		$items[] = array( 'label' => 'Hizmetler', 'url' => '' );
	} elseif ( is_tax( 'ce_service_cat' ) ) {
		$items[] = array( 'label' => 'Hizmetler', 'url' => ce_services_url() );
		$items[] = array( 'label' => single_term_title( '', false ), 'url' => '' );
	} elseif ( is_singular( 'post' ) ) {
		if ( $blog_item ) {
			$items[] = $blog_item;
		}
		$cats = get_the_category();
		if ( $cats ) {
			$items[] = array( 'label' => $cats[0]->name, 'url' => get_category_link( $cats[0] ) );
		}
		$items[] = array( 'label' => get_the_title(), 'url' => '' );
	} elseif ( is_page() ) {
		$ancestors = array_reverse( get_post_ancestors( get_queried_object_id() ) );
		foreach ( $ancestors as $ancestor ) {
			$items[] = array( 'label' => get_the_title( $ancestor ), 'url' => get_permalink( $ancestor ) );
		}
		$items[] = array( 'label' => get_the_title(), 'url' => '' );
	} elseif ( is_category() || is_tag() || is_tax() ) {
		if ( $blog_item && ( is_category() || is_tag() ) ) {
			$items[] = $blog_item;
		}
		$items[] = array( 'label' => single_term_title( '', false ), 'url' => '' );
	} elseif ( is_search() ) {
		$items[] = array( 'label' => 'Arama: ' . get_search_query(), 'url' => '' );
	} elseif ( is_404() ) {
		$items[] = array( 'label' => 'Sayfa bulunamadı', 'url' => '' );
	} elseif ( is_archive() ) {
		if ( $blog_item ) {
			$items[] = $blog_item;
		}
		$items[] = array( 'label' => wp_strip_all_tags( get_the_archive_title() ), 'url' => '' );
	}
	return $items;
}

/**
 * Breadcrumb HTML.
 *
 * @param string $class Ek sınıf.
 */
function ce_breadcrumbs( $class = '' ) {
	$items = ce_breadcrumb_items();
	if ( count( $items ) < 2 ) {
		return;
	}
	echo '<nav class="ce-breadcrumb ' . esc_attr( $class ) . '" aria-label="Sayfa konumu"><ol>';
	$last = count( $items ) - 1;
	foreach ( $items as $i => $item ) {
		echo '<li>';
		if ( $i < $last && $item['url'] ) {
			echo '<a href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['label'] ) . '</a>';
			echo '<span class="ce-breadcrumb__sep" aria-hidden="true">/</span>';
		} else {
			echo '<span aria-current="page">' . esc_html( $item['label'] ) . '</span>';
		}
		echo '</li>';
	}
	echo '</ol></nav>';
}
