<?php
/**
 * Operasyon: çalışma modları (normal / yoğun / sadece ileri tarih / sipariş kapalı),
 * günlük ve saat aralığı kapasitesi, özel gün kapasitesi, ziyaretçi duyurusu.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Kapasiteyi dolduran sipariş durumları.
 *
 * @return string[]
 */
function df_cap_statuses() {
	return array( 'wc-on-hold', 'wc-processing', 'wc-df-preparing', 'wc-df-on-the-way', 'wc-completed', 'wc-df-archived' );
}

/**
 * Bugünden sonraki teslimatların gün / saat aralığı sayıları.
 *
 * @return array<string, array<string,int>> tarih => [ '_total' => n, 'Saat etiketi' => n ]
 */
function df_cap_counts() {
	static $cache = null;
	$fresh = doing_action( 'woocommerce_checkout_process' ) || doing_filter( 'woocommerce_add_to_cart_validation' ) || doing_action( 'woocommerce_after_checkout_validation' );
	if ( null !== $cache && ! $fresh ) {
		return $cache;
	}
	$saved = $fresh ? false : get_transient( 'df_cap_counts' );
	if ( is_array( $saved ) ) {
		$cache = $saved;
		return $cache;
	}
	global $wpdb;
	$today    = df_now()->format( 'Y-m-d' );
	$statuses = "'" . implode( "','", array_map( 'esc_sql', df_cap_statuses() ) ) . "'";
	if ( function_exists( 'df_app_hpos' ) ? df_app_hpos() : ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ) ) {
		$o    = $wpdb->prefix . 'wc_orders';
		$m    = $wpdb->prefix . 'wc_orders_meta';
		$sql  = "SELECT d.meta_value AS day, s.meta_value AS slot, COUNT(*) AS n FROM {$o} o
			INNER JOIN {$m} d ON d.order_id = o.id AND d.meta_key = '_df_delivery_date'
			LEFT JOIN {$m} s ON s.order_id = o.id AND s.meta_key = '_df_delivery_slot'
			WHERE o.type = 'shop_order' AND o.status IN ({$statuses}) AND d.meta_value >= %s
			GROUP BY d.meta_value, s.meta_value";
	} else {
		$sql = "SELECT d.meta_value AS day, s.meta_value AS slot, COUNT(*) AS n FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} d ON d.post_id = p.ID AND d.meta_key = '_df_delivery_date'
			LEFT JOIN {$wpdb->postmeta} s ON s.post_id = p.ID AND s.meta_key = '_df_delivery_slot'
			WHERE p.post_type = 'shop_order' AND p.post_status IN ({$statuses}) AND d.meta_value >= %s
			GROUP BY d.meta_value, s.meta_value";
	}
	$rows  = $wpdb->get_results( $wpdb->prepare( $sql, $today ) ); // phpcs:ignore WordPress.DB
	$cache = array();
	foreach ( (array) $rows as $r ) {
		$day = (string) $r->day;
		if ( ! isset( $cache[ $day ] ) ) {
			$cache[ $day ] = array( '_total' => 0 );
		}
		$cache[ $day ]['_total'] += (int) $r->n;
		$slot                      = (string) $r->slot;
		$cache[ $day ][ $slot ]    = ( isset( $cache[ $day ][ $slot ] ) ? $cache[ $day ][ $slot ] : 0 ) + (int) $r->n;
	}
	set_transient( 'df_cap_counts', $cache, 10 * MINUTE_IN_SECONDS );
	return $cache;
}

/**
 * Sipariş gelince / durum değişince sayıları yenile.
 */
function df_cap_flush() {
	delete_transient( 'df_cap_counts' );
}
add_action( 'woocommerce_new_order', 'df_cap_flush' );
add_action( 'woocommerce_order_status_changed', 'df_cap_flush' );
add_action( 'woocommerce_checkout_order_processed', 'df_cap_flush' );

/**
 * Günün kapasitesi (özel gün listesi önce).
 *
 * @param string $ymd Tarih.
 * @return int 0 = sınırsız.
 */
function df_cap_for_day( $ymd ) {
	foreach ( df_lines( df_opt( 'df_cap_dates' ), true ) as $row ) {
		if ( isset( $row[1] ) && trim( $row[0] ) === $ymd ) {
			return max( 0, (int) $row[1] );
		}
	}
	return max( 0, (int) df_opt( 'df_cap_day', 0 ) );
}

/**
 * Çalışma modu ve kapasiteye göre saat aralıklarını süz.
 *
 * @param array  $slots Aralıklar.
 * @param string $ymd   Tarih.
 * @return array
 */
function df_ops_filter_slots( $slots, $ymd ) {
	if ( ! $slots ) {
		return $slots;
	}
	$mode = df_opt( 'df_mode', 'normal' );
	if ( 'closed' === $mode ) {
		return array();
	}
	if ( $ymd === df_now()->format( 'Y-m-d' ) && ( 'future' === $mode || df_opt( 'df_sameday_off', 0 ) ) ) {
		return array();
	}
	$cap_day  = df_cap_for_day( $ymd );
	$cap_slot = max( 0, (int) df_opt( 'df_cap_slot', 0 ) );
	if ( ! $cap_day && ! $cap_slot ) {
		return $slots;
	}
	$counts = df_cap_counts();
	$day    = isset( $counts[ $ymd ] ) ? $counts[ $ymd ] : array( '_total' => 0 );
	if ( $cap_day && $day['_total'] >= $cap_day ) {
		return array();
	}
	if ( $cap_slot ) {
		$slots = array_filter(
			$slots,
			function ( $s ) use ( $day, $cap_slot ) {
				return ( isset( $day[ $s['label'] ] ) ? $day[ $s['label'] ] : 0 ) < $cap_slot;
			}
		);
	}
	return $slots;
}
add_filter( 'df_available_slots', 'df_ops_filter_slots', 10, 2 );

/**
 * Sipariş alımı kapalıysa ürünler satın alınamaz.
 *
 * @param bool $ok Satın alınabilir.
 * @return bool
 */
function df_ops_purchasable( $ok ) {
	return ( ! is_admin() || wp_doing_ajax() ) && 'closed' === df_opt( 'df_mode', 'normal' ) ? false : $ok;
}
add_filter( 'woocommerce_is_purchasable', 'df_ops_purchasable', 50 );

/**
 * Ziyaretçi duyurusu metni.
 *
 * @return string
 */
function df_ops_notice_text() {
	$mode = df_opt( 'df_mode', 'normal' );
	$note = trim( (string) df_opt( 'df_mode_note' ) );
	if ( 'normal' === $mode && ! df_opt( 'df_sameday_off', 0 ) ) {
		return $note;
	}
	if ( $note ) {
		return $note;
	}
	$defaults = array(
		'busy'   => 'Yoğun bir gün! Siparişleriniz özenle hazırlanıyor; teslimat saatleri normalden biraz uzun olabilir.',
		'future' => 'Bugün için sipariş kapasitemiz doldu. İleri tarihli siparişlerinizi almaya devam ediyoruz.',
		'closed' => 'Şu anda online sipariş alamıyoruz. Bilgi ve sipariş için bize telefon veya WhatsApp ile ulaşabilirsiniz.',
		'normal' => 'Bugün için aynı gün teslimat kapalıdır, ileri tarihli siparişlerinizi alıyoruz.',
	);
	return isset( $defaults[ $mode ] ) ? $defaults[ $mode ] : '';
}

/**
 * Sitenin en üstünde duyuru bandı.
 */
function df_ops_notice_bar() {
	$text = df_ops_notice_text();
	if ( '' === $text || is_admin() ) {
		return;
	}
	$mode = df_opt( 'df_mode', 'normal' );
	echo '<div class="df-opsbar df-opsbar--' . esc_attr( $mode ) . '" role="status"><div class="df-container">' . esc_html( $text ) . '</div></div>';
	echo '<style>.df-opsbar{background:#2b2522;color:#fff;font-size:14px;line-height:1.4;text-align:center;padding:9px 0}.df-opsbar--busy{background:#8e5e52}.df-opsbar--closed{background:#a2574a}</style>';
}
add_action( 'wp_body_open', 'df_ops_notice_bar', 1 );
