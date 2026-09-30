<?php
/**
 * İçerik türleri: İçerik sözlüğü, Ürün rehberi; taksonomiler: Cilt sorunu, Cilt tipi, İçerik grubu, Ürün türü.
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Kayıt.
 */
function cr_register_types() {
	register_post_type(
		'icerik',
		array(
			'labels'        => array(
				'name'               => 'İçerik Sözlüğü',
				'singular_name'      => 'İçerik',
				'add_new'            => 'Yeni içerik',
				'add_new_item'       => 'Yeni içerik ekle',
				'edit_item'          => 'İçeriği düzenle',
				'new_item'           => 'Yeni içerik',
				'view_item'          => 'İçeriği görüntüle',
				'search_items'       => 'İçerik ara',
				'not_found'          => 'İçerik bulunamadı',
				'all_items'          => 'Tüm içerikler',
				'menu_name'          => 'İçerik Sözlüğü',
			),
			'description'   => 'Kozmetik içerikler (INCI) sözlüğü.',
			'public'        => true,
			'show_in_rest'  => true,
			'has_archive'   => 'icerik',
			'rewrite'       => array( 'slug' => 'icerik', 'with_front' => false ),
			'menu_icon'     => 'dashicons-book-alt',
			'menu_position' => 6,
			'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'author', 'custom-fields' ),
		)
	);

	register_post_type(
		'urun_rehberi',
		array(
			'labels'        => array(
				'name'          => 'Ürün Rehberi',
				'singular_name' => 'Ürün incelemesi',
				'add_new'       => 'Yeni ürün',
				'add_new_item'  => 'Yeni ürün incelemesi ekle',
				'edit_item'     => 'Ürün incelemesini düzenle',
				'view_item'     => 'Ürünü görüntüle',
				'search_items'  => 'Ürün ara',
				'not_found'     => 'Ürün bulunamadı',
				'all_items'     => 'Tüm ürünler',
				'menu_name'     => 'Ürün Rehberi',
			),
			'description'   => 'Satış amacı olmayan, içerik odaklı ürün tanıtımları.',
			'public'        => true,
			'show_in_rest'  => true,
			'has_archive'   => 'urun-rehberi',
			'rewrite'       => array( 'slug' => 'urun-rehberi', 'with_front' => false ),
			'menu_icon'     => 'dashicons-products',
			'menu_position' => 7,
			'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'author', 'custom-fields' ),
		)
	);

	register_taxonomy(
		'cilt_sorunu',
		array( 'post', 'icerik', 'urun_rehberi' ),
		array(
			'labels'            => array(
				'name'          => 'Cilt Sorunları',
				'singular_name' => 'Cilt sorunu',
				'add_new_item'  => 'Yeni cilt sorunu',
				'edit_item'     => 'Cilt sorununu düzenle',
				'search_items'  => 'Cilt sorunu ara',
				'all_items'     => 'Tüm cilt sorunları',
				'menu_name'     => 'Cilt Sorunları',
			),
			'hierarchical'      => true,
			'public'            => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'rewrite'           => array( 'slug' => 'cilt-sorunu', 'with_front' => false ),
		)
	);

	register_taxonomy(
		'cilt_tipi',
		array( 'post', 'icerik', 'urun_rehberi' ),
		array(
			'labels'            => array(
				'name'          => 'Cilt Tipleri',
				'singular_name' => 'Cilt tipi',
				'add_new_item'  => 'Yeni cilt tipi',
				'menu_name'     => 'Cilt Tipleri',
			),
			'hierarchical'      => true,
			'public'            => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'rewrite'           => array( 'slug' => 'cilt-tipi', 'with_front' => false ),
		)
	);

	register_taxonomy(
		'icerik_grubu',
		array( 'icerik' ),
		array(
			'labels'            => array(
				'name'          => 'İçerik Grupları',
				'singular_name' => 'İçerik grubu',
				'add_new_item'  => 'Yeni içerik grubu',
				'menu_name'     => 'Gruplar',
			),
			'hierarchical'      => true,
			'public'            => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'rewrite'           => array( 'slug' => 'icerik-grubu', 'with_front' => false ),
		)
	);

	register_taxonomy(
		'urun_turu',
		array( 'urun_rehberi' ),
		array(
			'labels'            => array(
				'name'          => 'Ürün Türleri',
				'singular_name' => 'Ürün türü',
				'add_new_item'  => 'Yeni ürün türü',
				'menu_name'     => 'Ürün Türleri',
			),
			'hierarchical'      => true,
			'public'            => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'rewrite'           => array( 'slug' => 'urun-turu', 'with_front' => false ),
		)
	);
}
add_action( 'init', 'cr_register_types' );

/**
 * Tema etkinleşince kalıcı bağlantıları yenile.
 */
function cr_after_switch() {
	cr_register_types();
	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'cr_after_switch' );

/**
 * Taksonomi terimlerinin görseli ve hero başlığı.
 *
 * @return array
 */
function cr_term_meta_taxonomies() {
	return array( 'category', 'cilt_sorunu', 'cilt_tipi', 'icerik_grubu', 'urun_turu' );
}

/**
 * Terim görseli.
 *
 * @param WP_Term|int $term Terim.
 * @return string
 */
function cr_term_image( $term ) {
	$id = is_object( $term ) ? $term->term_id : (int) $term;
	return (string) get_term_meta( $id, 'cr_image', true );
}
