<?php
/**
 * İletişim ve teklif talepleri yönetimi: liste sütunları, durum filtresi, toplu durum
 * değiştirme, detay ekranı, admin notu, güvenli dosya indirme ve CSV dışa aktarma.
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

/**
 * Tür → durum grubu.
 *
 * @param string $type Tür.
 * @return string
 */
function ce_inquiry_group( $type ) {
	return 'ce_message' === $type ? 'message' : 'quote';
}

/**
 * Talep alanlarının etiketleri.
 *
 * @param string $type Tür.
 * @return array<string,string>
 */
function ce_inquiry_fields( $type ) {
	if ( 'ce_message' === $type ) {
		return array(
			'name'    => 'Ad Soyad',
			'company' => 'Firma',
			'phone'   => 'Telefon',
			'email'   => 'E-posta',
			'subject' => 'Konu',
			'message' => 'Mesaj',
		);
	}
	return array(
		'company'  => 'Firma Adı',
		'name'     => 'Yetkili Adı',
		'phone'    => 'Telefon',
		'email'    => 'E-posta',
		'service'  => 'Hizmet',
		'material' => 'Malzeme Türü',
		'quantity' => 'Parça Adedi',
		'surface'  => 'Talep Edilen Yüzey',
		'color'    => 'Renk',
		'micron'   => 'Mikron',
		'message'  => 'Açıklama',
	);
}

foreach ( array( 'ce_message', 'ce_quote' ) as $ce_inq_type ) {
	add_filter( "manage_{$ce_inq_type}_posts_columns", 'ce_inquiry_columns' );
	add_action( "manage_{$ce_inq_type}_posts_custom_column", 'ce_inquiry_column_content', 10, 2 );
	add_filter( "bulk_actions-edit-{$ce_inq_type}", 'ce_inquiry_bulk_actions' );
	add_filter( "handle_bulk_actions-edit-{$ce_inq_type}", 'ce_inquiry_handle_bulk', 10, 3 );
}
unset( $ce_inq_type );

/**
 * Sütunlar.
 *
 * @param array $cols Sütunlar.
 * @return array
 */
function ce_inquiry_columns( $cols ) {
	$type = get_current_screen() ? get_current_screen()->post_type : '';
	$new  = array(
		'cb'         => $cols['cb'],
		'title'      => 'ce_quote' === $type ? 'Firma / Hizmet' : 'Gönderen / Konu',
		'ce_status'  => 'Durum',
		'ce_contact' => 'İletişim',
	);
	if ( 'ce_quote' === $type ) {
		$new['ce_details'] = 'Detay';
		$new['ce_files']   = 'Dosya';
	}
	$new['date'] = 'Tarih';
	return $new;
}

/**
 * Sütun içerikleri.
 *
 * @param string $col Sütun.
 * @param int    $id  Kayıt.
 */
function ce_inquiry_column_content( $col, $id ) {
	$type = get_post_type( $id );
	switch ( $col ) {
		case 'ce_status':
			$status   = (string) get_post_meta( $id, '_ce_status', true );
			$statuses = ce_inquiry_statuses( ce_inquiry_group( $type ) );
			echo '<span class="ce-status ce-status--' . esc_attr( $status ) . '">' . esc_html( $statuses[ $status ] ?? '—' ) . '</span>';
			break;
		case 'ce_contact':
			$email = (string) get_post_meta( $id, '_ce_f_email', true );
			$phone = (string) get_post_meta( $id, '_ce_f_phone', true );
			if ( $email ) {
				echo '<a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a><br>';
			}
			if ( $phone ) {
				echo '<a href="' . esc_attr( ce_tel( $phone ) ) . '">' . esc_html( $phone ) . '</a>';
			}
			break;
		case 'ce_details':
			$bits = array_filter(
				array(
					get_post_meta( $id, '_ce_f_material', true ),
					get_post_meta( $id, '_ce_f_quantity', true ) ? get_post_meta( $id, '_ce_f_quantity', true ) . ' adet' : '',
					get_post_meta( $id, '_ce_f_color', true ),
					get_post_meta( $id, '_ce_f_micron', true ) ? get_post_meta( $id, '_ce_f_micron', true ) . ' µm' : '',
				)
			);
			echo esc_html( implode( ' · ', $bits ) );
			break;
		case 'ce_files':
			$files = get_post_meta( $id, '_ce_files', true );
			echo $files ? '<span class="dashicons dashicons-paperclip" aria-hidden="true"></span> ' . count( (array) $files ) : '—';
			break;
	}
}

add_action( 'restrict_manage_posts', 'ce_inquiry_status_filter' );
/**
 * Durum filtresi ve CSV butonu.
 *
 * @param string $type Tür.
 */
function ce_inquiry_status_filter( $type ) {
	if ( ! in_array( $type, array( 'ce_message', 'ce_quote' ), true ) ) {
		return;
	}
	$current = isset( $_GET['ce_status'] ) ? sanitize_key( wp_unslash( $_GET['ce_status'] ) ) : ''; // phpcs:ignore
	echo '<label class="screen-reader-text" for="ce-status-filter">Duruma göre filtrele</label><select name="ce_status" id="ce-status-filter"><option value="">Tüm durumlar</option>';
	foreach ( ce_inquiry_statuses( ce_inquiry_group( $type ) ) as $k => $v ) {
		echo '<option value="' . esc_attr( $k ) . '" ' . selected( $current, $k, false ) . '>' . esc_html( $v ) . '</option>';
	}
	echo '</select>';
	$csv = wp_nonce_url( admin_url( 'admin-post.php?action=ce_export_inquiries&type=' . $type ), 'ce_export_' . $type );
	echo '<a class="button" href="' . esc_url( $csv ) . '" style="margin-left:6px">CSV dışa aktar</a>';
}

add_action( 'pre_get_posts', 'ce_inquiry_filter_query' );
/**
 * Durum filtresini uygular; arşiv varsayılan listede gizlenmez.
 *
 * @param WP_Query $query Sorgu.
 */
function ce_inquiry_filter_query( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}
	$type = $query->get( 'post_type' );
	if ( ! in_array( $type, array( 'ce_message', 'ce_quote' ), true ) ) {
		return;
	}
	$status = isset( $_GET['ce_status'] ) ? sanitize_key( wp_unslash( $_GET['ce_status'] ) ) : ''; // phpcs:ignore
	if ( $status && isset( ce_inquiry_statuses( ce_inquiry_group( $type ) )[ $status ] ) ) {
		$query->set( 'meta_key', '_ce_status' );
		$query->set( 'meta_value', $status );
	}
	// Arama meta alanlarında da çalışsın.
	$search = $query->get( 's' );
	if ( $search ) {
		$ids = get_posts(
			array(
				'post_type'   => $type,
				'fields'      => 'ids',
				'numberposts' => 200,
				'meta_query'  => array(
					'relation' => 'OR',
					array( 'key' => '_ce_f_email', 'value' => $search, 'compare' => 'LIKE' ),
					array( 'key' => '_ce_f_phone', 'value' => $search, 'compare' => 'LIKE' ),
					array( 'key' => '_ce_f_company', 'value' => $search, 'compare' => 'LIKE' ),
					array( 'key' => '_ce_f_message', 'value' => $search, 'compare' => 'LIKE' ),
				),
			)
		);
		if ( $ids ) {
			$query->set( 's', '' );
			$title_ids = get_posts( array( 'post_type' => $type, 'fields' => 'ids', 'numberposts' => 200, 's' => $search ) );
			$query->set( 'post__in', array_unique( array_merge( $ids, $title_ids ) ) );
		}
	}
}

/**
 * Toplu işlemler.
 *
 * @param array $actions İşlemler.
 * @return array
 */
function ce_inquiry_bulk_actions( $actions ) {
	$type = get_current_screen() ? get_current_screen()->post_type : 'ce_quote';
	foreach ( ce_inquiry_statuses( ce_inquiry_group( $type ) ) as $k => $v ) {
		$actions[ 'ce_status_' . $k ] = 'Durum: ' . $v;
	}
	return $actions;
}

/**
 * Toplu durum değişikliği.
 *
 * @param string $redirect Yönlendirme.
 * @param string $action   İşlem.
 * @param int[]  $ids      Kayıtlar.
 * @return string
 */
function ce_inquiry_handle_bulk( $redirect, $action, $ids ) {
	if ( 0 !== strpos( $action, 'ce_status_' ) || ! current_user_can( 'ce_manage_inquiries' ) ) {
		return $redirect;
	}
	$status = substr( $action, 10 );
	$count  = 0;
	foreach ( $ids as $id ) {
		$type = get_post_type( $id );
		if ( isset( ce_inquiry_statuses( ce_inquiry_group( $type ) )[ $status ] ) ) {
			update_post_meta( $id, '_ce_status', $status );
			ce_audit( 'status', ce_inquiry_group( $type ), $id, get_the_title( $id ) . ' → ' . $status );
			++$count;
		}
	}
	wp_cache_delete( 'ce_new_ce_message', 'ce' );
	wp_cache_delete( 'ce_new_ce_quote', 'ce' );
	return add_query_arg( 'ce_bulk', $count, $redirect );
}

add_action( 'admin_notices', 'ce_inquiry_bulk_notice' );
/**
 * Toplu işlem bildirimi.
 */
function ce_inquiry_bulk_notice() {
	if ( isset( $_GET['ce_bulk'] ) ) { // phpcs:ignore
		echo '<div class="notice notice-success is-dismissible"><p>' . (int) $_GET['ce_bulk'] . ' kaydın durumu güncellendi.</p></div>'; // phpcs:ignore
	}
}

add_action( 'add_meta_boxes', 'ce_inquiry_meta_boxes', 10, 2 );
/**
 * Detay ekranı kutuları.
 *
 * @param string  $type Tür.
 * @param WP_Post $post Kayıt.
 */
function ce_inquiry_meta_boxes( $type, $post ) {
	if ( ! in_array( $type, array( 'ce_message', 'ce_quote' ), true ) ) {
		return;
	}
	add_meta_box( 'ce_inquiry_detail', 'ce_quote' === $type ? 'Teklif Talebi' : 'İletişim Mesajı', 'ce_render_inquiry_detail', $type, 'normal', 'high' );
	add_meta_box( 'ce_inquiry_manage', 'Durum & Not', 'ce_render_inquiry_manage', $type, 'side', 'high' );

	// Mesaj açıldığında "Okundu" yap.
	if ( 'ce_message' === $type && 'new' === get_post_meta( $post->ID, '_ce_status', true ) ) {
		update_post_meta( $post->ID, '_ce_status', 'read' );
		wp_cache_delete( 'ce_new_ce_message', 'ce' );
	}
}

/**
 * Detay tablosu.
 *
 * @param WP_Post $post Kayıt.
 */
function ce_render_inquiry_detail( $post ) {
	echo '<table class="ce-detail">';
	foreach ( ce_inquiry_fields( $post->post_type ) as $key => $label ) {
		$value = (string) get_post_meta( $post->ID, '_ce_f_' . $key, true );
		if ( '' === $value ) {
			continue;
		}
		if ( 'email' === $key ) {
			$html = '<a href="mailto:' . esc_attr( $value ) . '">' . esc_html( $value ) . '</a>';
		} elseif ( 'phone' === $key ) {
			$html = '<a href="' . esc_attr( ce_tel( $value ) ) . '">' . esc_html( $value ) . '</a>';
		} else {
			$html = nl2br( esc_html( $value ) );
		}
		echo '<tr><th scope="row">' . esc_html( $label ) . '</th><td>' . $html . '</td></tr>'; // phpcs:ignore
	}
	echo '</table>';

	$files = (array) get_post_meta( $post->ID, '_ce_files', true );
	$files = array_filter( $files );
	if ( $files ) {
		echo '<h3 class="ce-detail__h">Ekler</h3><ul class="ce-files">';
		foreach ( array_values( $files ) as $i => $file ) {
			$url = wp_nonce_url( admin_url( 'admin-post.php?action=ce_download&post=' . $post->ID . '&i=' . $i ), 'ce_download_' . $post->ID . '_' . $i );
			printf(
				'<li><span class="dashicons %4$s" aria-hidden="true"></span><a href="%1$s">%2$s</a><small>%3$s</small></li>',
				esc_url( $url ),
				esc_html( $file['name'] ),
				esc_html( size_format( (int) $file['size'] ) ),
				'application/pdf' === $file['mime'] ? 'dashicons-pdf' : 'dashicons-format-image'
			);
		}
		echo '</ul>';
	}

	$email   = (string) get_post_meta( $post->ID, '_ce_f_email', true );
	$subject = rawurlencode( 'Re: ' . $post->post_title );
	echo '<p class="ce-detail__actions">';
	if ( $email ) {
		echo '<a class="button button-primary" href="mailto:' . esc_attr( $email ) . '?subject=' . esc_attr( $subject ) . '">E-posta ile yanıtla</a> ';
	}
	$phone = (string) get_post_meta( $post->ID, '_ce_f_phone', true );
	if ( $phone ) {
		$wa = preg_replace( '/\D/', '', $phone );
		if ( 0 === strpos( $wa, '0' ) ) {
			$wa = '9' . $wa;
		}
		echo '<a class="button" href="' . esc_url( 'https://wa.me/' . $wa ) . '" target="_blank" rel="noopener">WhatsApp</a>';
	}
	echo '</p>';

	echo '<p class="ce-detail__meta">Gönderim: ' . esc_html( get_the_date( 'd.m.Y H:i', $post ) ) . ' · IP: ' . esc_html( (string) get_post_meta( $post->ID, '_ce_ip', true ) ) . ' · KVKK onayı: ' . esc_html( (string) get_post_meta( $post->ID, '_ce_kvkk', true ) ) . ' · Bildirim e-postası: ' . ( get_post_meta( $post->ID, '_ce_mail_sent', true ) ? 'gönderildi' : 'gönderilemedi' ) . '</p>';
}

/**
 * Durum ve admin notu.
 *
 * @param WP_Post $post Kayıt.
 */
function ce_render_inquiry_manage( $post ) {
	wp_nonce_field( 'ce_inquiry_' . $post->ID, 'ce_inquiry_nonce' );
	$status = (string) get_post_meta( $post->ID, '_ce_status', true );
	echo '<p><label for="ce-inq-status"><strong>Durum</strong></label><br><select id="ce-inq-status" name="ce_inq_status" class="widefat">';
	foreach ( ce_inquiry_statuses( ce_inquiry_group( $post->post_type ) ) as $k => $v ) {
		echo '<option value="' . esc_attr( $k ) . '" ' . selected( $status, $k, false ) . '>' . esc_html( $v ) . '</option>';
	}
	echo '</select></p>';
	echo '<p><label for="ce-inq-note"><strong>Admin notu</strong></label><textarea id="ce-inq-note" name="ce_inq_note" class="widefat" rows="6">' . esc_textarea( (string) get_post_meta( $post->ID, '_ce_note', true ) ) . '</textarea></p>';
	echo '<p class="description">Kaydetmek için "Güncelle" butonunu kullanın.</p>';
}

add_action( 'save_post_ce_message', 'ce_save_inquiry_manage' );
add_action( 'save_post_ce_quote', 'ce_save_inquiry_manage' );
/**
 * Durum/not kaydı.
 *
 * @param int $post_id Kayıt.
 */
function ce_save_inquiry_manage( $post_id ) {
	$nonce = isset( $_POST['ce_inquiry_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['ce_inquiry_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'ce_inquiry_' . $post_id ) || ! current_user_can( 'ce_manage_inquiries' ) ) {
		return;
	}
	$type   = get_post_type( $post_id );
	$status = isset( $_POST['ce_inq_status'] ) ? sanitize_key( wp_unslash( $_POST['ce_inq_status'] ) ) : '';
	if ( isset( ce_inquiry_statuses( ce_inquiry_group( $type ) )[ $status ] ) ) {
		$old = get_post_meta( $post_id, '_ce_status', true );
		update_post_meta( $post_id, '_ce_status', $status );
		if ( $old !== $status ) {
			ce_audit( 'status', ce_inquiry_group( $type ), $post_id, get_the_title( $post_id ) . ' → ' . $status );
		}
	}
	if ( isset( $_POST['ce_inq_note'] ) ) {
		update_post_meta( $post_id, '_ce_note', sanitize_textarea_field( wp_unslash( $_POST['ce_inq_note'] ) ) );
	}
	wp_cache_delete( 'ce_new_' . $type, 'ce' );
}

add_action( 'admin_post_ce_download', 'ce_download_inquiry_file' );
/**
 * Gizli klasördeki dosyayı yetkili kullanıcıya indirir.
 */
function ce_download_inquiry_file() {
	$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:ignore
	$index   = isset( $_GET['i'] ) ? absint( $_GET['i'] ) : 0; // phpcs:ignore
	check_admin_referer( 'ce_download_' . $post_id . '_' . $index );
	if ( ! current_user_can( 'ce_manage_inquiries' ) || 'ce_quote' !== get_post_type( $post_id ) ) {
		wp_die( 'Yetkiniz yok.', 403 );
	}
	$files = array_values( array_filter( (array) get_post_meta( $post_id, '_ce_files', true ) ) );
	if ( empty( $files[ $index ] ) ) {
		wp_die( 'Dosya bulunamadı.', 404 );
	}
	$file = $files[ $index ];
	$base = realpath( ce_private_upload_dir() );
	$path = realpath( $base . '/' . $file['path'] );
	if ( ! $path || 0 !== strpos( $path, $base . DIRECTORY_SEPARATOR ) || ! is_file( $path ) ) {
		wp_die( 'Dosya bulunamadı.', 404 );
	}
	ce_audit( 'download', 'quote', $post_id, $file['name'] );
	nocache_headers();
	header( 'Content-Type: ' . ( in_array( $file['mime'], array( 'application/pdf', 'image/jpeg', 'image/png', 'image/webp' ), true ) ? $file['mime'] : 'application/octet-stream' ) );
	header( 'Content-Disposition: attachment; filename="' . str_replace( '"', '', sanitize_file_name( $file['name'] ) ) . '"' );
	header( 'Content-Length: ' . filesize( $path ) );
	header( 'X-Content-Type-Options: nosniff' );
	readfile( $path ); // phpcs:ignore
	exit;
}

add_action( 'admin_post_ce_export_inquiries', 'ce_export_inquiries' );
/**
 * CSV dışa aktarma (Excel uyumlu, UTF-8 BOM).
 */
function ce_export_inquiries() {
	$type = isset( $_GET['type'] ) ? sanitize_key( wp_unslash( $_GET['type'] ) ) : ''; // phpcs:ignore
	if ( ! in_array( $type, array( 'ce_message', 'ce_quote' ), true ) ) {
		wp_die( 'Geçersiz tür.' );
	}
	check_admin_referer( 'ce_export_' . $type );
	if ( ! current_user_can( 'ce_manage_inquiries' ) ) {
		wp_die( 'Yetkiniz yok.', 403 );
	}
	$fields   = ce_inquiry_fields( $type );
	$statuses = ce_inquiry_statuses( ce_inquiry_group( $type ) );
	ce_audit( 'export', ce_inquiry_group( $type ), 0, 'CSV' );

	nocache_headers();
	header( 'Content-Type: text/csv; charset=UTF-8' );
	header( 'Content-Disposition: attachment; filename="' . ( 'ce_quote' === $type ? 'teklif-talepleri' : 'iletisim-talepleri' ) . '-' . gmdate( 'Y-m-d' ) . '.csv"' );
	$out = fopen( 'php://output', 'w' );
	fwrite( $out, "\xEF\xBB\xBF" ); // phpcs:ignore
	fputcsv( $out, array_merge( array( 'Tarih', 'Durum' ), array_values( $fields ), array( 'Admin notu' ) ), ';' );
	$paged = 1;
	do {
		$posts = get_posts( array( 'post_type' => $type, 'post_status' => 'publish', 'numberposts' => 200, 'paged' => $paged ) );
		foreach ( $posts as $p ) {
			$row = array( get_the_date( 'd.m.Y H:i', $p ), $statuses[ get_post_meta( $p->ID, '_ce_status', true ) ] ?? '' );
			foreach ( array_keys( $fields ) as $k ) {
				$row[] = ce_csv_safe( (string) get_post_meta( $p->ID, '_ce_f_' . $k, true ) );
			}
			$row[] = ce_csv_safe( (string) get_post_meta( $p->ID, '_ce_note', true ) );
			fputcsv( $out, $row, ';' );
		}
		++$paged;
	} while ( count( $posts ) === 200 );
	fclose( $out ); // phpcs:ignore
	exit;
}

/**
 * CSV formül enjeksiyonunu önler.
 *
 * @param string $value Değer.
 * @return string
 */
function ce_csv_safe( $value ) {
	return preg_match( '/^[=+\-@\t\r]/', $value ) ? "'" . $value : $value;
}

add_filter( 'post_row_actions', 'ce_inquiry_row_actions', 10, 2 );
/**
 * Talep satırlarında "Görüntüle"/"Hızlı düzenle" kaldırılır.
 *
 * @param array   $actions İşlemler.
 * @param WP_Post $post    Kayıt.
 * @return array
 */
function ce_inquiry_row_actions( $actions, $post ) {
	if ( in_array( $post->post_type, array( 'ce_message', 'ce_quote' ), true ) ) {
		unset( $actions['inline hide-if-no-js'], $actions['view'] );
		$actions['edit'] = '<a href="' . esc_url( get_edit_post_link( $post->ID ) ) . '">Aç</a>';
	}
	return $actions;
}
