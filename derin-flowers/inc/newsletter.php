<?php
/**
 * Bülten aboneleri: yerel kayıt (gizli içerik tipi) + CSV dışa aktarma.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Abone içerik tipi (yalnızca yönetimde görünür).
 */
function df_register_subscriber_cpt() {
	register_post_type(
		'df_subscriber',
		array(
			'labels'          => array(
				'name'          => 'Bülten Aboneleri',
				'singular_name' => 'Abone',
				'menu_name'     => 'Aboneler',
				'all_items'     => 'Aboneler',
				'search_items'  => 'Abone ara',
				'not_found'     => 'Henüz abone yok.',
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => 'derin-flowers',
			'supports'        => array( 'title' ),
			'capability_type' => 'post',
			'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
			'map_meta_cap'    => true,
		)
	);
}
add_action( 'init', 'df_register_subscriber_cpt' );

/**
 * AJAX abonelik.
 */
function df_ajax_subscribe() {
	check_ajax_referer( 'df_nonce', 'nonce' );
	$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	if ( ! is_email( $email ) ) {
		wp_send_json_error( array( 'message' => 'Lütfen geçerli bir e-posta adresi girin.' ) );
	}
	$exists = get_posts(
		array(
			'post_type'      => 'df_subscriber',
			'title'          => $email,
			'posts_per_page' => 1,
			'post_status'    => 'any',
			'fields'         => 'ids',
		)
	);
	if ( $exists ) {
		wp_send_json_success( array( 'message' => 'Bu e-posta zaten bültenimize kayıtlı. Teşekkürler!' ) );
	}
	$id = wp_insert_post(
		array(
			'post_type'   => 'df_subscriber',
			'post_title'  => $email,
			'post_status' => 'publish',
		)
	);
	if ( is_wp_error( $id ) ) {
		wp_send_json_error( array( 'message' => 'Kaydınız alınamadı, lütfen tekrar deneyin.' ) );
	}
	do_action( 'df_newsletter_subscribed', $email, $id );
	wp_send_json_success( array( 'message' => 'Teşekkürler! Bültenimize kaydoldunuz.' ) );
}
add_action( 'wp_ajax_df_subscribe', 'df_ajax_subscribe' );
add_action( 'wp_ajax_nopriv_df_subscribe', 'df_ajax_subscribe' );

/**
 * CSV dışa aktarma bağlantısı.
 *
 * @param string $which Konum.
 */
function df_subscriber_export_button( $which ) {
	$screen = get_current_screen();
	if ( 'top' !== $which || ! $screen || 'edit-df_subscriber' !== $screen->id ) {
		return;
	}
	$url = wp_nonce_url( admin_url( 'admin-post.php?action=df_export_subscribers' ), 'df_export_subscribers' );
	echo '<div class="alignleft actions"><a class="button" href="' . esc_url( $url ) . '">CSV olarak indir</a></div>';
}
add_action( 'manage_posts_extra_tablenav', 'df_subscriber_export_button' );

/**
 * CSV dışa aktarma.
 */
function df_export_subscribers() {
	if ( ! current_user_can( 'edit_posts' ) || ! check_admin_referer( 'df_export_subscribers' ) ) {
		wp_die( 'Yetkiniz yok.' );
	}
	$ids = get_posts(
		array(
			'post_type'      => 'df_subscriber',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'fields'         => 'ids',
		)
	);
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=bulten-aboneleri-' . gmdate( 'Y-m-d' ) . '.csv' );
	$out = fopen( 'php://output', 'w' );
	fwrite( $out, "\xEF\xBB\xBF" ); // Excel için UTF-8 BOM.
	fputcsv( $out, array( 'E-posta', 'Kayıt tarihi' ) );
	foreach ( $ids as $id ) {
		fputcsv( $out, array( get_the_title( $id ), get_the_date( 'Y-m-d H:i', $id ) ) );
	}
	fclose( $out ); // phpcs:ignore
	exit;
}
add_action( 'admin_post_df_export_subscribers', 'df_export_subscribers' );
