<?php
/**
 * Çiçekçi sipariş durumları: Hazırlanıyor, Yolda (Teslim edildi = Tamamlandı).
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Durum tanımları.
 *
 * @return array<string,string>
 */
function df_custom_statuses() {
	return array(
		'wc-df-preparing' => 'Hazırlanıyor',
		'wc-df-on-the-way' => 'Yolda',
	);
}

/**
 * Kayıt.
 */
function df_register_statuses() {
	foreach ( df_custom_statuses() as $status => $label ) {
		register_post_status(
			$status,
			array(
				'label'                     => $label,
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				/* translators: %s: sayı */
				'label_count'               => _n_noop( $label . ' <span class="count">(%s)</span>', $label . ' <span class="count">(%s)</span>' ), // phpcs:ignore WordPress.WP.I18n
			)
		);
	}
}
add_action( 'init', 'df_register_statuses' );

/**
 * WooCommerce listesine ekle (İşleniyor'dan sonra).
 *
 * @param array $statuses Durumlar.
 * @return array
 */
function df_add_statuses( $statuses ) {
	$out = array();
	foreach ( $statuses as $key => $label ) {
		$out[ $key ] = $label;
		if ( 'wc-processing' === $key ) {
			$out = array_merge( $out, df_custom_statuses() );
		}
	}
	if ( isset( $out['wc-completed'] ) ) {
		$out['wc-completed'] = 'Teslim Edildi';
	}
	return $out;
}
add_filter( 'wc_order_statuses', 'df_add_statuses' );

/**
 * Ödenmiş sayılan durumlar (raporlar, indirmeler, stok).
 *
 * @param array $statuses Durumlar.
 * @return array
 */
function df_paid_statuses( $statuses ) {
	return array_merge( $statuses, array( 'df-preparing', 'df-on-the-way' ) );
}
add_filter( 'woocommerce_order_is_paid_statuses', 'df_paid_statuses' );
add_filter(
	'woocommerce_reports_order_statuses',
	function ( $statuses ) {
		return is_array( $statuses ) ? array_merge( $statuses, array( 'df-preparing', 'df-on-the-way' ) ) : $statuses;
	}
);
add_filter(
	'woocommerce_valid_order_statuses_for_order_again',
	function ( $statuses ) {
		return array_merge( (array) $statuses, array( 'df-preparing', 'df-on-the-way' ) );
	}
);

/**
 * Toplu işlemler.
 *
 * @param array $actions İşlemler.
 * @return array
 */
function df_bulk_actions( $actions ) {
	$actions['mark_df-preparing']  = 'Durumu "Hazırlanıyor" yap';
	$actions['mark_df-on-the-way'] = 'Durumu "Yolda" yap';
	return $actions;
}
add_filter( 'bulk_actions-edit-shop_order', 'df_bulk_actions', 20 );
add_filter( 'bulk_actions-woocommerce_page_wc-orders', 'df_bulk_actions', 20 );

/**
 * Durum değiştiğinde müşteriye not düş (Hesabım ve e-posta bildirimi).
 *
 * @param int      $order_id Sipariş.
 * @param string   $from     Eski.
 * @param string   $to       Yeni.
 * @param WC_Order $order    Sipariş.
 */
function df_status_customer_note( $order_id, $from, $to, $order ) {
	$messages = array(
		'df-preparing'  => 'Siparişiniz floristlerimiz tarafından özenle hazırlanıyor.',
		'df-on-the-way' => 'pickup' === $order->get_meta( '_df_delivery_type' ) ? 'Siparişiniz hazır, mağazamızdan teslim alabilirsiniz.' : 'Siparişiniz yola çıktı, kısa süre içinde teslim edilecek.',
	);
	if ( isset( $messages[ $to ] ) ) {
		$order->add_order_note( $messages[ $to ], true );
	}
}
add_action( 'woocommerce_order_status_changed', 'df_status_customer_note', 10, 4 );

/**
 * Yönetim listesinde durum renkleri.
 */
function df_status_admin_css() {
	echo '<style>.order-status.status-df-preparing{background:#f3e7d8;color:#8a5a2b}.order-status.status-df-on-the-way{background:#dfe9f2;color:#2f5d86}</style>';
}
add_action( 'admin_head', 'df_status_admin_css' );
