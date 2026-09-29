<?php
/**
 * Özel içerik türleri ve taksonomiler.
 *
 * URL yapısı:
 *   /hizmetler/             hizmet arşivi
 *   /hizmet/{slug}/         hizmet detayı
 *   /hizmetler/kategori/{slug}/
 *   /blog/{slug}/           yazılar (Kurulum aracı kalıcı bağlantıyı ayarlar)
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'ce_register_post_types', 5 );
/**
 * İçerik türlerini kaydeder.
 */
function ce_register_post_types() {
	$labels = static function ( $singular, $plural, $extra = array() ) {
		return array_merge(
			array(
				'name'               => $plural,
				'singular_name'      => $singular,
				'menu_name'          => $plural,
				'add_new'            => 'Yeni ekle',
				'add_new_item'       => 'Yeni ' . mb_strtolower( $singular ) . ' ekle',
				'edit_item'          => $singular . ' düzenle',
				'new_item'           => 'Yeni ' . mb_strtolower( $singular ),
				'view_item'          => $singular . ' görüntüle',
				'search_items'       => $plural . ' içinde ara',
				'not_found'          => 'Kayıt bulunamadı.',
				'not_found_in_trash' => 'Çöp kutusunda kayıt yok.',
				'all_items'          => 'Tüm ' . mb_strtolower( $plural ),
				'featured_image'     => 'Kapak görseli',
				'set_featured_image' => 'Kapak görseli ayarla',
				'remove_featured_image' => 'Kapak görselini kaldır',
				'use_featured_image' => 'Kapak görseli olarak kullan',
			),
			$extra
		);
	};

	// Hizmetler.
	register_post_type(
		'ce_service',
		array(
			'labels'        => $labels( 'Hizmet', 'Hizmetler' ),
			'public'        => true,
			'show_in_rest'  => true,
			'menu_icon'     => 'dashicons-admin-generic',
			'menu_position' => 21,
			'supports'      => array( 'title', 'editor', 'thumbnail', 'page-attributes', 'revisions' ),
			'has_archive'   => 'hizmetler',
			'rewrite'       => array( 'slug' => 'hizmet', 'with_front' => false ),
			'hierarchical'  => false,
		)
	);
	register_taxonomy(
		'ce_service_cat',
		'ce_service',
		array(
			'labels'            => $labels( 'Hizmet kategorisi', 'Hizmet Kategorileri', array( 'menu_name' => 'Kategoriler' ) ),
			'hierarchical'      => true,
			'public'            => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => 'hizmetler/kategori', 'with_front' => false ),
		)
	);

	// Hero slaytları.
	register_post_type(
		'ce_slide',
		array(
			'labels'              => $labels( 'Slayt', 'Hero / Slider' ),
			'public'              => false,
			'show_ui'             => true,
			'show_in_rest'        => false,
			'exclude_from_search' => true,
			'menu_icon'           => 'dashicons-slides',
			'menu_position'       => 20,
			'supports'            => array( 'title', 'thumbnail', 'page-attributes' ),
		)
	);

	// Sektörler.
	register_post_type(
		'ce_sector',
		array(
			'labels'              => $labels( 'Sektör', 'Sektörler' ),
			'public'              => false,
			'show_ui'             => true,
			'exclude_from_search' => true,
			'menu_icon'           => 'dashicons-building',
			'menu_position'       => 22,
			'supports'            => array( 'title', 'thumbnail', 'excerpt', 'page-attributes' ),
		)
	);

	// Galeri.
	register_post_type(
		'ce_gallery',
		array(
			'labels'              => $labels( 'Galeri görseli', 'Galeri', array( 'all_items' => 'Tüm görseller' ) ),
			'public'              => false,
			'show_ui'             => true,
			'exclude_from_search' => true,
			'menu_icon'           => 'dashicons-format-gallery',
			'menu_position'       => 23,
			'supports'            => array( 'title', 'thumbnail', 'excerpt', 'page-attributes' ),
		)
	);
	register_taxonomy(
		'ce_gallery_cat',
		'ce_gallery',
		array(
			'labels'            => $labels( 'Galeri kategorisi', 'Galeri Kategorileri', array( 'menu_name' => 'Kategoriler' ) ),
			'hierarchical'      => true,
			'public'            => false,
			'show_ui'           => true,
			'show_admin_column' => true,
			'rewrite'           => false,
		)
	);

	// Referanslar.
	register_post_type(
		'ce_reference',
		array(
			'labels'              => $labels( 'Referans', 'Referanslar', array( 'featured_image' => 'Logo', 'set_featured_image' => 'Logo ayarla', 'remove_featured_image' => 'Logoyu kaldır' ) ),
			'public'              => false,
			'show_ui'             => true,
			'exclude_from_search' => true,
			'menu_icon'           => 'dashicons-awards',
			'menu_position'       => 24,
			'supports'            => array( 'title', 'thumbnail', 'page-attributes' ),
		)
	);

	// Banka hesapları.
	register_post_type(
		'ce_bank',
		array(
			'labels'              => $labels( 'Banka hesabı', 'Banka Hesapları', array( 'featured_image' => 'Banka logosu', 'set_featured_image' => 'Logo ayarla', 'remove_featured_image' => 'Logoyu kaldır' ) ),
			'public'              => false,
			'show_ui'             => true,
			'exclude_from_search' => true,
			'menu_icon'           => 'dashicons-bank',
			'menu_position'       => 25,
			'supports'            => array( 'title', 'thumbnail', 'page-attributes' ),
		)
	);

	// Gelen talepler (yalnızca yönetim).
	$inquiry_caps = array(
		'edit_post'              => 'ce_manage_inquiries',
		'read_post'              => 'ce_manage_inquiries',
		'delete_post'            => 'ce_manage_inquiries',
		'edit_posts'             => 'ce_manage_inquiries',
		'edit_others_posts'      => 'ce_manage_inquiries',
		'delete_posts'           => 'ce_manage_inquiries',
		'delete_others_posts'    => 'ce_manage_inquiries',
		'delete_published_posts' => 'ce_manage_inquiries',
		'delete_private_posts'   => 'ce_manage_inquiries',
		'edit_published_posts'   => 'ce_manage_inquiries',
		'edit_private_posts'     => 'ce_manage_inquiries',
		'publish_posts'          => 'ce_manage_inquiries',
		'read_private_posts'     => 'ce_manage_inquiries',
		'create_posts'           => 'do_not_allow',
	);
	register_post_type(
		'ce_message',
		array(
			'labels'              => $labels( 'İletişim talebi', 'İletişim Talepleri', array( 'all_items' => 'Tüm mesajlar', 'menu_name' => 'İletişim Talepleri' ) ),
			'public'              => false,
			'show_ui'             => true,
			'exclude_from_search' => true,
			'menu_icon'           => 'dashicons-email',
			'menu_position'       => 26,
			'supports'            => array( 'title' ),
			'capabilities'        => $inquiry_caps,
			'map_meta_cap'        => false,
		)
	);
	register_post_type(
		'ce_quote',
		array(
			'labels'              => $labels( 'Teklif talebi', 'Teklif Talepleri', array( 'all_items' => 'Tüm teklifler', 'menu_name' => 'Teklif Talepleri' ) ),
			'public'              => false,
			'show_ui'             => true,
			'exclude_from_search' => true,
			'menu_icon'           => 'dashicons-clipboard',
			'menu_position'       => 27,
			'supports'            => array( 'title' ),
			'capabilities'        => $inquiry_caps,
			'map_meta_cap'        => false,
		)
	);
}

add_action( 'pre_get_posts', 'ce_service_archive_query' );
/**
 * Hizmet arşivi: tüm hizmetler sıralı.
 *
 * @param WP_Query $query Sorgu.
 */
function ce_service_archive_query( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( $query->is_post_type_archive( 'ce_service' ) || $query->is_tax( 'ce_service_cat' ) ) {
		$query->set( 'posts_per_page', 60 );
		$query->set( 'orderby', array( 'menu_order' => 'ASC', 'title' => 'ASC' ) );
	}
}

/**
 * Sıralı, yayınlanmış içerikler (yardımcı).
 *
 * @param string $type  Tür.
 * @param int    $limit Sınır.
 * @param array  $extra Ek argümanlar.
 * @return WP_Post[]
 */
function ce_get_items( $type, $limit = -1, $extra = array() ) {
	return get_posts(
		array_merge(
			array(
				'post_type'        => $type,
				'post_status'      => 'publish',
				'numberposts'      => $limit,
				'orderby'          => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
				'suppress_filters' => false,
			),
			$extra
		)
	);
}

// Yönetim listelerinde görsel ve sıra sütunları.
foreach ( array( 'ce_service', 'ce_slide', 'ce_sector', 'ce_gallery', 'ce_reference', 'ce_bank' ) as $ce_type ) {
	add_filter( "manage_{$ce_type}_posts_columns", 'ce_admin_columns' );
	add_action( "manage_{$ce_type}_posts_custom_column", 'ce_admin_column_content', 10, 2 );
	add_filter( "manage_edit-{$ce_type}_sortable_columns", 'ce_admin_sortable_columns' );
}
unset( $ce_type );

/**
 * Sütunlar.
 *
 * @param array $cols Sütunlar.
 * @return array
 */
function ce_admin_columns( $cols ) {
	$new = array();
	foreach ( $cols as $k => $v ) {
		if ( 'title' === $k ) {
			$new['ce_thumb'] = '<span class="screen-reader-text">Görsel</span>';
		}
		$new[ $k ] = $v;
	}
	$new['ce_order'] = 'Sıra';
	return $new;
}

/**
 * Sütun içerikleri.
 *
 * @param string $col Sütun.
 * @param int    $id  Yazı.
 */
function ce_admin_column_content( $col, $id ) {
	if ( 'ce_thumb' === $col ) {
		$thumb = get_post_thumbnail_id( $id );
		if ( $thumb ) {
			echo wp_get_attachment_image( $thumb, array( 64, 64 ), false, array( 'class' => 'ce-admin-thumb' ) );
		} else {
			echo '<span class="ce-admin-thumb ce-admin-thumb--empty dashicons dashicons-format-image" aria-hidden="true"></span>';
		}
	} elseif ( 'ce_order' === $col ) {
		echo (int) get_post_field( 'menu_order', $id );
	}
}

/**
 * Sıralanabilir sütunlar.
 *
 * @param array $cols Sütunlar.
 * @return array
 */
function ce_admin_sortable_columns( $cols ) {
	$cols['ce_order'] = 'menu_order';
	return $cols;
}

add_action( 'pre_get_posts', 'ce_admin_default_order' );
/**
 * Yönetim listelerinde varsayılan sıra: menu_order.
 *
 * @param WP_Query $query Sorgu.
 */
function ce_admin_default_order( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}
	$type = $query->get( 'post_type' );
	if ( in_array( $type, array( 'ce_service', 'ce_slide', 'ce_sector', 'ce_gallery', 'ce_reference', 'ce_bank' ), true ) && ! $query->get( 'orderby' ) ) {
		$query->set( 'orderby', array( 'menu_order' => 'ASC', 'date' => 'DESC' ) );
	}
}

add_filter( 'enter_title_here', 'ce_title_placeholder', 10, 2 );
/**
 * Başlık alanı ipuçları.
 *
 * @param string  $text Metin.
 * @param WP_Post $post Yazı.
 * @return string
 */
function ce_title_placeholder( $text, $post ) {
	$map = array(
		'ce_service'   => 'Hizmet adı (ör. Naturel Eloksal)',
		'ce_slide'     => 'Slayt başlığı (satır için | kullanın)',
		'ce_sector'    => 'Sektör adı',
		'ce_gallery'   => 'Görsel başlığı / açıklaması',
		'ce_reference' => 'Firma adı',
		'ce_bank'      => 'Banka adı (ör. HALK BANKASI)',
	);
	return $map[ $post->post_type ] ?? $text;
}
