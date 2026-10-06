<?php
/**
 * Sipariş arşivi: teslim edilen siparişler belirli gün sonra "Arşiv" durumuna alınır,
 * aktif sipariş listesinden çıkar; raporlarda ve müşteri geçmişinde kalır.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Durum kaydı.
 */
function df_archive_register() {
	register_post_status(
		'wc-df-archived',
		array(
			'label'                     => 'Arşiv',
			'public'                    => false,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => false, // "Tümü" listesinde görünmez.
			'show_in_admin_status_list' => true,
			/* translators: %s: sayı */
			'label_count'               => _n_noop( 'Arşiv <span class="count">(%s)</span>', 'Arşiv <span class="count">(%s)</span>' ), // phpcs:ignore WordPress.WP.I18n
		)
	);
}
add_action( 'init', 'df_archive_register' );

/**
 * WooCommerce durum listesine ekle.
 *
 * @param array $statuses Durumlar.
 * @return array
 */
function df_archive_status( $statuses ) {
	$statuses['wc-df-archived'] = is_admin() && ! wp_doing_ajax() ? 'Arşiv (teslim edildi)' : 'Teslim Edildi';
	return $statuses;
}
add_filter( 'wc_order_statuses', 'df_archive_status', 20 );

add_filter(
	'woocommerce_order_is_paid_statuses',
	function ( $s ) {
		return array_merge( (array) $s, array( 'df-archived' ) );
	}
);
add_filter(
	'woocommerce_reports_order_statuses',
	function ( $s ) {
		return is_array( $s ) ? array_merge( $s, array( 'df-archived' ) ) : $s;
	}
);
add_filter(
	'woocommerce_valid_order_statuses_for_order_again',
	function ( $s ) {
		return array_merge( (array) $s, array( 'df-archived' ) );
	}
);

/**
 * Toplu işlem: arşive taşı.
 *
 * @param array $actions İşlemler.
 * @return array
 */
function df_archive_bulk( $actions ) {
	$actions['mark_df-archived'] = 'Arşive taşı';
	return $actions;
}
add_filter( 'bulk_actions-edit-shop_order', 'df_archive_bulk', 30 );
add_filter( 'bulk_actions-woocommerce_page_wc-orders', 'df_archive_bulk', 30 );

/**
 * Otomatik arşiv görevini planla.
 */
function df_archive_schedule() {
	if ( ! wp_next_scheduled( 'df_archive_cron' ) ) {
		wp_schedule_event( time() + 300, 'hourly', 'df_archive_cron' );
	}
}
add_action( 'init', 'df_archive_schedule' );

/**
 * Teslim edilenleri arşivle.
 *
 * @param bool $force Ayar kapalı olsa da çalıştır (elle).
 * @return int Arşivlenen sayı.
 */
function df_archive_run( $force = false ) {
	if ( ! $force && ! df_opt( 'archive_on', 1 ) ) {
		return 0;
	}
	$days = max( 0, (int) df_opt( 'archive_days', 2 ) );
	$ids  = wc_get_orders(
		array(
			'status'         => 'completed',
			'limit'          => 200,
			'return'         => 'ids',
			'type'           => 'shop_order',
			'date_completed' => '<' . ( time() - $days * DAY_IN_SECONDS ),
		)
	);
	$n = 0;
	foreach ( $ids as $id ) {
		$o = wc_get_order( $id );
		if ( $o && 'completed' === $o->get_status() ) {
			$o->update_status( 'df-archived', 'Teslim edildi, otomatik arşivlendi.' );
			++$n;
		}
	}
	return $n;
}
add_action( 'df_archive_cron', 'df_archive_run' );

/**
 * Elle "Şimdi arşivle".
 */
function df_archive_now() {
	if ( ! current_user_can( 'edit_shop_orders' ) || ! check_admin_referer( 'df_archive_now' ) ) {
		wp_die( 'Yetkiniz yok.' );
	}
	$n = df_archive_run( true );
	wp_safe_redirect( add_query_arg( 'df_archived', $n, wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=df-dashboard' ) ) );
	exit;
}
add_action( 'admin_post_df_archive_now', 'df_archive_now' );

/**
 * Bilgi.
 */
function df_archive_notice() {
	if ( isset( $_GET['df_archived'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="notice notice-success is-dismissible"><p>' . (int) $_GET['df_archived'] . ' teslim edilmiş sipariş arşive taşındı.</p></div>'; // phpcs:ignore
	}
}
add_action( 'admin_notices', 'df_archive_notice' );

/**
 * Arşiv bağlantısı (sipariş listesi).
 *
 * @return string
 */
function df_archive_url() {
	return function_exists( 'df_app_hpos' ) && df_app_hpos() ? admin_url( 'admin.php?page=wc-orders&status=wc-df-archived' ) : admin_url( 'edit.php?post_status=wc-df-archived&post_type=shop_order' );
}

/**
 * Renk.
 */
function df_archive_css() {
	echo '<style>.order-status.status-df-archived{background:#ece9e6;color:#6b625c}</style>';
}
add_action( 'admin_head', 'df_archive_css' );
