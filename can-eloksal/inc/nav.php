<?php
/**
 * Menüler: veritabanı (Görünüm → Menüler) üzerinden yönetilir. Atanmamış konumlar için
 * mantıklı varsayılan menü üretilir. "Hizmetler" öğesinin alt menüsü aktif hizmetlerden
 * otomatik doldurulur (öğeye elle alt öğe eklenmişse onlar kullanılır).
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

/**
 * Konumdaki menü öğelerini ağaç olarak döndürür.
 *
 * @param string $location Konum.
 * @return array
 */
function ce_menu_tree( $location ) {
	static $cache = array();
	if ( isset( $cache[ $location ] ) ) {
		return $cache[ $location ];
	}

	$items     = array();
	$locations = get_nav_menu_locations();
	if ( ! empty( $locations[ $location ] ) ) {
		$menu_items = wp_get_nav_menu_items( $locations[ $location ], array( 'update_post_term_cache' => false ) );
		if ( $menu_items ) {
			foreach ( $menu_items as $mi ) {
				$items[] = array(
					'id'      => (int) $mi->ID,
					'parent'  => (int) $mi->menu_item_parent,
					'label'   => $mi->title,
					'url'     => $mi->url,
					'target'  => $mi->target,
					'classes' => array_filter( (array) $mi->classes ),
					'object'  => $mi->object,
					'obj_id'  => (int) $mi->object_id,
					'type'    => $mi->type,
				);
			}
		}
	} else {
		$items = ce_default_menu( $location );
	}

	// Ağaç kur.
	$by_parent = array();
	foreach ( $items as $item ) {
		$by_parent[ $item['parent'] ][] = $item;
	}
	$build = static function ( $parent ) use ( &$build, $by_parent ) {
		$out = array();
		foreach ( $by_parent[ $parent ] ?? array() as $item ) {
			$item['children'] = $build( $item['id'] );
			$out[]            = $item;
		}
		return $out;
	};
	$tree = $build( 0 );

	// Hizmetler öğesine otomatik alt menü.
	if ( 'primary' === $location ) {
		foreach ( $tree as &$node ) {
			$is_services = in_array( 'ce-auto-services', $node['classes'], true )
				|| ( 'post_type_archive' === $node['type'] && 'ce_service' === $node['object'] )
				|| untrailingslashit( $node['url'] ) === untrailingslashit( ce_services_url() );
			if ( $is_services && ! $node['children'] ) {
				foreach ( ce_get_items( 'ce_service', 30 ) as $i => $service ) {
					$node['children'][] = array(
						'id'       => -1000 - $i,
						'parent'   => $node['id'],
						'label'    => get_the_title( $service ),
						'url'      => get_permalink( $service ),
						'target'   => '',
						'classes'  => array(),
						'object'   => 'ce_service',
						'obj_id'   => $service->ID,
						'type'     => 'post_type',
						'children' => array(),
					);
				}
			}
		}
		unset( $node );
	}

	$cache[ $location ] = $tree;
	return $tree;
}

/**
 * Menü atanmamış konumlar için varsayılan öğeler.
 *
 * @param string $location Konum.
 * @return array
 */
function ce_default_menu( $location ) {
	$i    = 0;
	$make = static function ( $label, $url, $parent = 0, $classes = array() ) use ( &$i ) {
		++$i;
		return array( 'id' => $i, 'parent' => $parent, 'label' => $label, 'url' => $url, 'target' => '', 'classes' => $classes, 'object' => '', 'obj_id' => 0, 'type' => 'custom' );
	};
	$page = static function ( $path ) {
		$p = get_page_by_path( $path );
		return $p ? get_permalink( $p ) : home_url( '/' . $path . '/' );
	};
	$blog = get_option( 'page_for_posts' ) ? get_permalink( (int) get_option( 'page_for_posts' ) ) : home_url( '/blog/' );

	switch ( $location ) {
		case 'primary':
			$items   = array( $make( 'Ana Sayfa', home_url( '/' ) ) );
			$corp    = $make( 'Kurumsal', $page( 'hakkimizda' ) );
			$items[] = $corp;
			$items[] = $make( 'Hakkımızda', $page( 'hakkimizda' ), $corp['id'] );
			$items[] = $make( 'Kalite Politikamız', $page( 'kalite-politikamiz' ), $corp['id'] );
			$items[] = $make( 'Hizmetler', ce_services_url(), 0, array( 'ce-auto-services' ) );
			$items[] = $make( 'Galeri', $page( 'galeri' ) );
			$items[] = $make( 'Blog', $blog );
			$items[] = $make( 'Banka Hesapları', $page( 'banka-hesaplari' ) );
			$items[] = $make( 'İletişim', $page( 'iletisim' ) );
			return $items;
		case 'footer_corporate':
			return array( $make( 'Hakkımızda', $page( 'hakkimizda' ) ), $make( 'Kalite Politikamız', $page( 'kalite-politikamiz' ) ), $make( 'Teklif Al', ce_quote_url() ) );
		case 'footer_services':
			$out = array();
			foreach ( ce_get_items( 'ce_service', 6 ) as $s ) {
				$out[] = $make( get_the_title( $s ), get_permalink( $s ) );
			}
			$out[] = $make( 'Tüm Hizmetler', ce_services_url() );
			return $out;
		case 'footer_quick':
			return array( $make( 'Blog', $blog ), $make( 'Galeri', $page( 'galeri' ) ), $make( 'Banka Hesapları', $page( 'banka-hesaplari' ) ), $make( 'İletişim', $page( 'iletisim' ) ) );
		case 'footer_legal':
			$out = array();
			foreach ( array( 'kvkk_page' => 'KVKK', 'privacy_page' => 'Gizlilik Politikası', 'cookie_page' => 'Çerez Politikası' ) as $key => $label ) {
				$id = absint( ce_opt( $key ) );
				if ( $id && 'publish' === get_post_status( $id ) ) {
					$out[] = $make( $label, get_permalink( $id ) );
				}
			}
			return $out;
	}
	return array();
}

/**
 * Geçerli istek adresi (karşılaştırma için).
 *
 * @return string
 */
function ce_current_url() {
	global $wp;
	return untrailingslashit( home_url( $wp->request ?? '' ) );
}

/**
 * Öğe aktif mi?
 *
 * @param array $item Öğe.
 * @return bool
 */
function ce_menu_is_active( $item ) {
	$url = untrailingslashit( (string) $item['url'] );
	if ( $url && $url === ce_current_url() ) {
		return true;
	}
	if ( is_singular( 'ce_service' ) && $url === untrailingslashit( ce_services_url() ) ) {
		return true;
	}
	if ( is_singular( 'post' ) || is_category() || is_tag() ) {
		$blog = get_option( 'page_for_posts' );
		if ( $blog && untrailingslashit( get_permalink( (int) $blog ) ) === $url ) {
			return true;
		}
	}
	foreach ( $item['children'] ?? array() as $child ) {
		if ( ce_menu_is_active( $child ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Header menüsü.
 */
function ce_render_header_menu() {
	$tree = ce_menu_tree( 'primary' );
	echo '<ul class="ce-nav__list">';
	foreach ( $tree as $item ) {
		$has    = ! empty( $item['children'] );
		$active = ce_menu_is_active( $item );
		$sub_id = 'ce-sub-' . abs( $item['id'] );
		echo '<li class="ce-nav__item' . ( $has ? ' has-sub' : '' ) . ( $active ? ' is-active' : '' ) . '">';
		printf(
			'<a class="ce-nav__link" href="%1$s"%2$s%3$s>%4$s</a>',
			esc_url( $item['url'] ),
			$item['target'] ? ' target="' . esc_attr( $item['target'] ) . '" rel="noopener"' : '',
			$active && ! $has ? ' aria-current="page"' : '',
			esc_html( $item['label'] )
		);
		if ( $has ) {
			echo '<button class="ce-nav__toggle" type="button" aria-expanded="false" aria-controls="' . esc_attr( $sub_id ) . '"><span class="screen-reader-text">' . esc_html( $item['label'] ) . ' alt menüsü</span>' . ce_icon( 'chevron-down', 14 ) . '</button>'; // phpcs:ignore
			$wide = count( $item['children'] ) > 5;
			echo '<div class="ce-nav__sub' . ( $wide ? ' ce-nav__sub--wide' : '' ) . '" id="' . esc_attr( $sub_id ) . '"><ul>';
			foreach ( $item['children'] as $child ) {
				$c_active = ce_menu_is_active( $child );
				printf(
					'<li><a href="%1$s"%2$s%3$s><span>%4$s</span>%5$s</a></li>',
					esc_url( $child['url'] ),
					$child['target'] ? ' target="' . esc_attr( $child['target'] ) . '" rel="noopener"' : '',
					$c_active ? ' aria-current="page" class="is-active"' : '',
					esc_html( $child['label'] ),
					ce_icon( 'arrow-up-right', 14 ) // phpcs:ignore
				);
			}
			echo '</ul></div>';
		}
		echo '</li>';
	}
	echo '</ul>';
}

/**
 * Mobil çekmece menüsü (akordeon).
 */
function ce_render_drawer_menu() {
	$tree = ce_menu_tree( 'primary' );
	echo '<ul class="ce-drawer__list">';
	foreach ( $tree as $n => $item ) {
		$has    = ! empty( $item['children'] );
		$active = ce_menu_is_active( $item );
		$sub_id = 'ce-dsub-' . abs( $item['id'] );
		echo '<li class="ce-drawer__item' . ( $active ? ' is-active' : '' ) . '" style="--i:' . (int) $n . '">';
		echo '<div class="ce-drawer__row">';
		printf( '<a href="%1$s"%3$s><span class="ce-drawer__num">%4$02d</span>%2$s</a>', esc_url( $item['url'] ), esc_html( $item['label'] ), $active && ! $has ? ' aria-current="page"' : '', (int) $n + 1 );
		if ( $has ) {
			echo '<button type="button" class="ce-drawer__toggle" aria-expanded="false" aria-controls="' . esc_attr( $sub_id ) . '"><span class="screen-reader-text">' . esc_html( $item['label'] ) . ' alt menüsünü aç</span>' . ce_icon( 'plus', 18 ) . '</button>'; // phpcs:ignore
		}
		echo '</div>';
		if ( $has ) {
			echo '<ul class="ce-drawer__sub" id="' . esc_attr( $sub_id ) . '" hidden>';
			foreach ( $item['children'] as $child ) {
				printf( '<li><a href="%1$s">%2$s</a></li>', esc_url( $child['url'] ), esc_html( $child['label'] ) );
			}
			echo '</ul>';
		}
		echo '</li>';
	}
	echo '</ul>';
}

/**
 * Basit liste menüsü (footer).
 *
 * @param string $location Konum.
 * @param string $class    Sınıf.
 */
function ce_render_list_menu( $location, $class = 'ce-footer__links' ) {
	$tree = ce_menu_tree( $location );
	if ( ! $tree ) {
		return;
	}
	echo '<ul class="' . esc_attr( $class ) . '">';
	foreach ( $tree as $item ) {
		printf(
			'<li><a href="%1$s"%3$s>%2$s</a></li>',
			esc_url( $item['url'] ),
			esc_html( $item['label'] ),
			$item['target'] ? ' target="' . esc_attr( $item['target'] ) . '" rel="noopener"' : ''
		);
	}
	echo '</ul>';
}

/**
 * Footer menü başlığı (atanan menünün adı veya varsayılan).
 *
 * @param string $location Konum.
 * @param string $fallback Varsayılan.
 * @return string
 */
function ce_menu_title( $location, $fallback ) {
	$locations = get_nav_menu_locations();
	if ( ! empty( $locations[ $location ] ) ) {
		$menu = wp_get_nav_menu_object( $locations[ $location ] );
		if ( $menu && $menu->name && ! preg_match( '/^(footer|menu)/i', $menu->name ) ) {
			return $menu->name;
		}
	}
	return $fallback;
}
