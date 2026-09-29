<?php
/**
 * Galeriye toplu görsel ekleme: Ortam Kütüphanesi'nden çoklu seçim → her görsel için
 * kategorili bir galeri kaydı. Galeride zaten olan görseller atlanır (tekrar yok).
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', 'ce_gallery_bulk_menu' );
/**
 * Alt menü.
 */
function ce_gallery_bulk_menu() {
	add_submenu_page( 'edit.php?post_type=ce_gallery', 'Toplu Ekle', 'Toplu Ekle', 'upload_files', 'ce-gallery-bulk', 'ce_render_gallery_bulk' );
}

/**
 * Ekran.
 */
function ce_render_gallery_bulk() {
	if ( ! current_user_can( 'upload_files' ) || ! current_user_can( 'edit_posts' ) ) {
		wp_die( 'Yetkiniz yok.' );
	}
	$terms = get_terms( array( 'taxonomy' => 'ce_gallery_cat', 'hide_empty' => false ) );
	echo '<div class="wrap ce-admin">';
	ce_admin_header( 'Galeriye Toplu Ekle', 'Birden fazla görseli tek seferde seçip kategoriye ekleyin. Alt metin, başlık ve açıklama Ortam Kütüphanesi\'nden gelir.' );

	if ( isset( $_GET['added'] ) ) { // phpcs:ignore
		printf( '<div class="ce-toast ce-toast--success" role="status"><span class="dashicons dashicons-yes-alt" aria-hidden="true"></span> %d görsel eklendi, %d görsel zaten galeride olduğu için atlandı.</div>', (int) $_GET['added'], (int) ( $_GET['skipped'] ?? 0 ) ); // phpcs:ignore
	}

	echo '<form class="ce-card ce-card--form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	wp_nonce_field( 'ce_gallery_bulk', 'ce_gallery_nonce' );
	echo '<input type="hidden" name="action" value="ce_gallery_bulk"><div class="ce-fields">';
	echo '<div class="ce-field ce-field--half"><label class="ce-field__label" for="ce-gb-term">Kategori</label><select id="ce-gb-term" name="term" class="ce-input"><option value="0">— Kategori yok —</option>';
	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $t ) {
			echo '<option value="' . (int) $t->term_id . '">' . esc_html( $t->name ) . '</option>';
		}
	}
	echo '</select></div>';
	echo '<div class="ce-field ce-field--half"><label class="ce-field__label" for="ce-gb-new">veya yeni kategori</label><input id="ce-gb-new" type="text" name="new_term" class="ce-input" placeholder="Ör. Üretim"></div>';
	ce_render_field( array( 'type' => 'gallery', 'label' => 'Görseller' ), array(), 'ids', 'ce-gb-ids' );
	echo '</div><div class="ce-savebar"><button type="submit" class="button button-primary button-hero">Galeriye ekle</button></div></form></div>';
}

add_action( 'admin_post_ce_gallery_bulk', 'ce_handle_gallery_bulk' );
/**
 * Kayıt.
 */
function ce_handle_gallery_bulk() {
	check_admin_referer( 'ce_gallery_bulk', 'ce_gallery_nonce' );
	if ( ! current_user_can( 'upload_files' ) || ! current_user_can( 'edit_posts' ) ) {
		wp_die( 'Yetkiniz yok.', 403 );
	}
	$ids  = isset( $_POST['ids'] ) ? array_filter( array_map( 'absint', explode( ',', sanitize_text_field( wp_unslash( $_POST['ids'] ) ) ) ) ) : array();
	$term = isset( $_POST['term'] ) ? absint( $_POST['term'] ) : 0;
	$new  = isset( $_POST['new_term'] ) ? sanitize_text_field( wp_unslash( $_POST['new_term'] ) ) : '';
	if ( $new && current_user_can( 'manage_categories' ) ) {
		$created = wp_insert_term( $new, 'ce_gallery_cat' );
		if ( ! is_wp_error( $created ) ) {
			$term = (int) $created['term_id'];
		} elseif ( $created->get_error_data( 'term_exists' ) ) {
			$term = (int) $created->get_error_data( 'term_exists' );
		}
	}

	$order   = (int) wp_count_posts( 'ce_gallery' )->publish;
	$added   = 0;
	$skipped = 0;
	foreach ( $ids as $id ) {
		if ( ! wp_attachment_is_image( $id ) ) {
			continue;
		}
		$exists = get_posts( array( 'post_type' => 'ce_gallery', 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids', 'meta_key' => '_thumbnail_id', 'meta_value' => $id ) );
		if ( $exists ) {
			++$skipped;
			continue;
		}
		$att     = get_post( $id );
		$title   = $att->post_title ? $att->post_title : ( get_post_meta( $id, '_wp_attachment_image_alt', true ) ? get_post_meta( $id, '_wp_attachment_image_alt', true ) : 'Galeri görseli' );
		$post_id = wp_insert_post(
			array(
				'post_type'    => 'ce_gallery',
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_excerpt' => $att->post_excerpt,
				'menu_order'   => ++$order,
			)
		);
		if ( $post_id && ! is_wp_error( $post_id ) ) {
			set_post_thumbnail( $post_id, $id );
			if ( $term ) {
				wp_set_object_terms( $post_id, array( $term ), 'ce_gallery_cat' );
			}
			++$added;
		}
	}
	wp_safe_redirect( admin_url( 'edit.php?post_type=ce_gallery&page=ce-gallery-bulk&added=' . $added . '&skipped=' . $skipped ) );
	exit;
}
