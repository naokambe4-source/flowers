<?php
/**
 * Derin Flowers Yönetim Paneli: genel bakış, teslimat takvimi, yazdırma merkezi,
 * kurye yönetimi, sistem durumu. Tüm veriler gerçek WooCommerce siparişlerinden gelir.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Panel sayfaları.
 *
 * @return array<string, array{0:string,1:string}> slug => [başlık, callback]
 */
function df_app_pages() {
	return array(
		'df-dashboard'  => array( 'Genel Bakış', 'df_app_dashboard' ),
		'df-deliveries' => array( 'İzmir Teslimat', 'df_app_deliveries' ),
		'df-print'      => array( 'Yazdırma Merkezi', 'df_app_print' ),
		'df-couriers'   => array( 'Kurye Yönetimi', 'df_app_couriers' ),
		'df-system'     => array( 'Sistem Durumu', 'df_app_system' ),
	);
}

/**
 * Menü kaydı.
 */
function df_app_menu() {
	if ( ! df_wc() ) {
		return;
	}
	$badge = (int) wc_orders_count( 'processing' );
	add_menu_page(
		'Yönetim Paneli',
		'Yönetim Paneli' . ( $badge ? ' <span class="awaiting-mod">' . $badge . '</span>' : '' ),
		'edit_shop_orders',
		'df-dashboard',
		'df_app_dashboard',
		'dashicons-store',
		2
	);
	foreach ( df_app_pages() as $slug => $page ) {
		add_submenu_page( 'df-dashboard', $page[0], $page[0], 'edit_shop_orders', $slug, $page[1] );
	}
}
add_action( 'admin_menu', 'df_app_menu', 8 );

/**
 * Panel sayfasında mıyız?
 *
 * @return bool
 */
function df_app_is_page() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	return isset( $_GET['page'] ) && array_key_exists( sanitize_key( wp_unslash( $_GET['page'] ) ), df_app_pages() );
}

/**
 * Body sınıfı (WordPress menüsünü gizleyip kendi kenar menüsünü gösterir).
 *
 * @param string $classes Sınıflar.
 * @return string
 */
function df_app_body_class( $classes ) {
	return df_app_is_page() ? $classes . ' df-app-screen' : $classes;
}
add_filter( 'admin_body_class', 'df_app_body_class' );

/**
 * Varlıklar.
 */
function df_app_assets() {
	if ( ! df_app_is_page() ) {
		return;
	}
	wp_enqueue_style( 'df-app', DF_URI . '/assets/admin/dashboard.css', array(), DF_VERSION );
	wp_enqueue_script( 'df-app', DF_URI . '/assets/admin/dashboard.js', array( 'jquery' ), DF_VERSION, true );
	wp_localize_script(
		'df-app',
		'DFApp',
		array(
			'ajax'  => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'df_app' ),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'df_app_assets' );

/**
 * WordPress Başlangıç ekranı yerine Yönetim Paneli.
 */
function df_app_replace_dashboard() {
	if ( df_opt( 'admin_replace_dashboard', 1 ) && df_wc() && current_user_can( 'edit_shop_orders' ) ) {
		wp_safe_redirect( admin_url( 'admin.php?page=df-dashboard' ) );
		exit;
	}
}
add_action( 'load-index.php', 'df_app_replace_dashboard' );

/**
 * Girişten sonra Yönetim Paneli.
 *
 * @param string           $to   Hedef.
 * @param string           $req  İstenen.
 * @param WP_User|WP_Error $user Kullanıcı.
 * @return string
 */
function df_app_login_redirect( $to, $req, $user ) {
	if ( df_opt( 'admin_redirect', 1 ) && df_wc() && $user instanceof WP_User && $user->has_cap( 'edit_shop_orders' ) && ( ! $req || admin_url() === $req || false !== strpos( $req, 'wp-admin/index.php' ) ) ) {
		return admin_url( 'admin.php?page=df-dashboard' );
	}
	return $to;
}
add_filter( 'login_redirect', 'df_app_login_redirect', 20, 3 );

/* -------------------------------------------------------------------------
 * Yardımcılar
 * ---------------------------------------------------------------------- */

/**
 * Sipariş listesi adresi (HPOS / klasik).
 *
 * @param string $status Durum (wc- olmadan).
 * @return string
 */
function df_app_orders_url( $status = '' ) {
	$hpos = class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
	$url  = $hpos ? admin_url( 'admin.php?page=wc-orders' ) : admin_url( 'edit.php?post_type=shop_order' );
	if ( $status ) {
		$url = add_query_arg( $hpos ? 'status' : 'post_status', 'wc-' . $status, $url );
	}
	return $url;
}

/**
 * Durum rozeti.
 *
 * @param WC_Order $order Sipariş.
 * @return string
 */
function df_app_status_badge( $order ) {
	$status = $order->get_status();
	return '<span class="df-app-status is-' . esc_attr( $status ) . '">' . esc_html( wc_get_order_status_name( $status ) ) . '</span>';
}

/**
 * Teslimattaki aktif durumlar.
 *
 * @return string[]
 */
function df_app_active_statuses() {
	return array( 'on-hold', 'processing', 'df-preparing', 'df-on-the-way', 'completed' );
}

/**
 * Kuryeler.
 *
 * @return array<int, array{name:string, phone:string}>
 */
function df_app_couriers_list() {
	$out = array();
	foreach ( df_lines( df_opt( 'df_couriers' ), true ) as $row ) {
		$out[] = array(
			'name'  => $row[0],
			'phone' => isset( $row[1] ) ? preg_replace( '/\D/', '', $row[1] ) : '',
		);
	}
	return $out;
}

/**
 * HPOS (yüksek performanslı sipariş tablosu) açık mı?
 *
 * @return bool
 */
function df_app_hpos() {
	return class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
}

/**
 * Klasik sipariş depolamasında teslimat tarihine göre sorgu (meta_query orada desteklenmez).
 *
 * @param array $query WP_Query argümanları.
 * @param array $vars  Sipariş sorgu değişkenleri.
 * @return array
 */
function df_app_cpt_delivery_query( $query, $vars ) {
	if ( ! empty( $vars['df_delivery_date'] ) ) {
		$query['meta_query'][] = array( // phpcs:ignore WordPress.DB.SlowDBQuery
			'key'   => '_df_delivery_date',
			'value' => sanitize_text_field( $vars['df_delivery_date'] ),
		);
	}
	return $query;
}
add_filter( 'woocommerce_order_data_store_cpt_get_orders_query', 'df_app_cpt_delivery_query', 10, 2 );

/**
 * Bir güne ait teslimat siparişleri.
 *
 * @param string $ymd Tarih.
 * @return WC_Order[]
 */
function df_app_orders_for_date( $ymd ) {
	$args = array(
		'limit'            => 200,
		'status'           => df_app_active_statuses(),
		'df_delivery_date' => $ymd,
	);
	if ( df_app_hpos() ) {
		$args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery
			array(
				'key'   => '_df_delivery_date',
				'value' => $ymd,
			),
		);
	}
	$orders = wc_get_orders( $args );
	// Güvenlik ağı: hangi depolama olursa olsun yalnızca o günün siparişleri.
	$orders = array_values(
		array_filter(
			$orders,
			function ( $o ) use ( $ymd ) {
				return $ymd === $o->get_meta( '_df_delivery_date' );
			}
		)
	);
	usort(
		$orders,
		function ( $a, $b ) {
			return strcmp( (string) $a->get_meta( '_df_delivery_slot' ), (string) $b->get_meta( '_df_delivery_slot' ) );
		}
	);
	return $orders;
}

/**
 * Kısa bölge adı.
 *
 * @param WC_Order $order Sipariş.
 * @return string
 */
function df_app_district( $order ) {
	if ( 'pickup' === $order->get_meta( '_df_delivery_type' ) ) {
		return 'Mağazadan teslim';
	}
	$parts = explode( ' — ', (string) $order->get_meta( '_df_district' ) );
	return (string) end( $parts );
}

/**
 * Kuryeye WhatsApp mesajı bağlantısı.
 *
 * @param WC_Order $order Sipariş.
 * @param string   $phone Kurye telefonu.
 * @return string
 */
function df_app_courier_whatsapp( $order, $phone ) {
	$lines = array(
		'Sipariş #' . $order->get_order_number(),
		'Teslimat: ' . wp_date( 'j F l', strtotime( $order->get_meta( '_df_delivery_date' ) . ' 12:00:00' ) ) . ' ' . $order->get_meta( '_df_delivery_slot' ),
		'Alıcı: ' . $order->get_meta( '_df_recipient_name' ) . ' ' . $order->get_meta( '_df_recipient_phone' ),
		'Adres: ' . $order->get_meta( '_df_address' ) . ' (' . df_app_district( $order ) . ')',
	);
	if ( $order->get_meta( '_df_address_hint' ) ) {
		$lines[] = 'Tarif: ' . $order->get_meta( '_df_address_hint' );
	}
	if ( $order->get_customer_note() ) {
		$lines[] = 'Not: ' . $order->get_customer_note();
	}
	return 'https://wa.me/' . rawurlencode( $phone ) . '?text=' . rawurlencode( implode( "\n", $lines ) );
}

/**
 * Uygulama kabuğu (kenar menü + üst bilgi) — sayfa içeriğini sarar.
 *
 * @param string   $current Aktif sayfa.
 * @param string   $title   Başlık.
 * @param string   $sub     Alt başlık.
 * @param callable $content İçerik.
 */
function df_app_shell( $current, $title, $sub, $content ) {
	$user = wp_get_current_user();
	?>
	<div class="df-app">
		<main class="df-app__main">
			<header class="df-app__top">
				<div>
					<h1><?php echo esc_html( $title ); ?></h1>
					<?php if ( $sub ) : ?>
						<p><?php echo esc_html( $sub ); ?></p>
					<?php endif; ?>
				</div>
				<div class="df-app__who">
					<span class="df-app__date"><?php echo esc_html( wp_date( 'j F Y, l · H:i' ) ); ?></span>
					<span class="df-app__user"><?php echo get_avatar( $user->ID, 34 ); ?><span>Merhaba, <?php echo esc_html( $user->display_name ); ?></span></span>
				</div>
			</header>
			<?php call_user_func( $content ); ?>
		</main>
	</div>
	<?php
}

/* -------------------------------------------------------------------------
 * Genel bakış
 * ---------------------------------------------------------------------- */

/**
 * Son 7 gün satış verisi.
 *
 * @return array<int, array{day:string,label:string,count:int,total:float}>
 */
function df_app_sales_7days() {
	$start  = df_now()->setTime( 0, 0 )->modify( '-6 day' );
	$orders = wc_get_orders(
		array(
			'limit'        => 1000,
			'status'       => array( 'on-hold', 'processing', 'df-preparing', 'df-on-the-way', 'completed' ),
			'date_created' => '>=' . $start->getTimestamp(),
			'type'         => 'shop_order',
		)
	);
	$days = array();
	for ( $i = 0; $i < 7; $i++ ) {
		$d                     = $start->modify( '+' . $i . ' day' );
		$days[ $d->format( 'Y-m-d' ) ] = array(
			'day'   => $d->format( 'Y-m-d' ),
			'label' => wp_date( 'j M', $d->getTimestamp() ),
			'count' => 0,
			'total' => 0.0,
		);
	}
	foreach ( $orders as $order ) {
		$created = $order->get_date_created();
		if ( ! $created ) {
			continue;
		}
		$key = wp_date( 'Y-m-d', $created->getTimestamp() );
		if ( isset( $days[ $key ] ) ) {
			++$days[ $key ]['count'];
			$days[ $key ]['total'] += (float) $order->get_total();
		}
	}
	return array_values( $days );
}

/**
 * Tek eksenli satış grafiği (ciro). Sipariş sayısı tooltip ve tabloda.
 *
 * @param array $days Veri.
 */
function df_app_sales_chart( $days ) {
	$max   = max( 1, max( wp_list_pluck( $days, 'total' ) ) );
	$w     = 560;
	$h     = 220;
	$pad_l = 56;
	$pad_b = 28;
	$pad_t = 12;
	$plot  = $h - $pad_b - $pad_t;
	$slot  = ( $w - $pad_l ) / count( $days );
	$bar   = min( 34, $slot * .5 );
	$nice  = df_app_nice_max( $max );
	?>
	<svg class="df-app-chart" viewBox="0 0 <?php echo (int) $w; ?> <?php echo (int) $h; ?>" role="img" aria-label="Son 7 gün ciro grafiği">
		<?php for ( $g = 0; $g <= 4; $g++ ) : ?>
			<?php $y = $pad_t + $plot - ( $plot * $g / 4 ); ?>
			<line x1="<?php echo (int) $pad_l; ?>" x2="<?php echo (int) $w; ?>" y1="<?php echo esc_attr( round( $y, 1 ) ); ?>" y2="<?php echo esc_attr( round( $y, 1 ) ); ?>" class="df-app-chart__grid<?php echo 0 === $g ? ' is-base' : ''; ?>"/>
			<text x="<?php echo (int) ( $pad_l - 8 ); ?>" y="<?php echo esc_attr( round( $y + 4, 1 ) ); ?>" class="df-app-chart__axis" text-anchor="end"><?php echo esc_html( df_app_short_money( $nice * $g / 4 ) ); ?></text>
		<?php endfor; ?>
		<?php foreach ( $days as $i => $d ) : ?>
			<?php
			$bh = $d['total'] > 0 ? max( 2, $plot * $d['total'] / $nice ) : 0;
			$x  = $pad_l + $slot * $i + ( $slot - $bar ) / 2;
			$y  = $pad_t + $plot - $bh;
			$r  = min( 4, $bh );
			?>
			<g class="df-app-chart__col" tabindex="0">
				<title><?php echo esc_html( $d['label'] . ': ' . wp_strip_all_tags( wc_price( $d['total'] ) ) . ' · ' . $d['count'] . ' sipariş' ); ?></title>
				<rect class="df-app-chart__hit" x="<?php echo esc_attr( round( $pad_l + $slot * $i, 1 ) ); ?>" y="<?php echo (int) $pad_t; ?>" width="<?php echo esc_attr( round( $slot, 1 ) ); ?>" height="<?php echo (int) $plot; ?>"/>
				<?php if ( $bh > 0 ) : ?>
					<path class="df-app-chart__bar" d="<?php echo esc_attr( sprintf( 'M%1$.1f,%2$.1f v%3$.1f a%4$.1f,%4$.1f 0 0 1 %4$.1f,-%4$.1f h%5$.1f a%4$.1f,%4$.1f 0 0 1 %4$.1f,%4$.1f v%6$.1f z', $x, $pad_t + $plot, -( $bh - $r ), $r, $bar - 2 * $r, $bh - $r ) ); ?>"/>
				<?php endif; ?>
				<text x="<?php echo esc_attr( round( $x + $bar / 2, 1 ) ); ?>" y="<?php echo (int) ( $h - 8 ); ?>" class="df-app-chart__axis" text-anchor="middle"><?php echo esc_html( $d['label'] ); ?></text>
			</g>
		<?php endforeach; ?>
	</svg>
	<?php
}

/**
 * Eksen için yuvarlak üst sınır.
 *
 * @param float $max En büyük değer.
 * @return float
 */
function df_app_nice_max( $max ) {
	$pow  = pow( 10, floor( log10( $max ) ) );
	foreach ( array( 1, 2, 2.5, 5, 10 ) as $m ) {
		if ( $m * $pow >= $max ) {
			return $m * $pow;
		}
	}
	return 10 * $pow;
}

/**
 * Kısa para biçimi (eksen).
 *
 * @param float $v Değer.
 * @return string
 */
function df_app_short_money( $v ) {
	if ( $v >= 1000 ) {
		return rtrim( rtrim( number_format( $v / 1000, 1, ',', '.' ), '0' ), ',' ) . ' B';
	}
	return (string) round( $v );
}

/**
 * Son 30 günün en çok satanları.
 *
 * @return array<int, array{name:string,qty:int,url:string}>
 */
function df_app_top_products() {
	$orders = wc_get_orders(
		array(
			'limit'        => 500,
			'status'       => array( 'processing', 'df-preparing', 'df-on-the-way', 'completed' ),
			'date_created' => '>=' . ( time() - 30 * DAY_IN_SECONDS ),
			'type'         => 'shop_order',
		)
	);
	$agg = array();
	foreach ( $orders as $order ) {
		foreach ( $order->get_items() as $item ) {
			$pid = $item->get_product_id();
			if ( ! isset( $agg[ $pid ] ) ) {
				$agg[ $pid ] = array(
					'name' => $item->get_name(),
					'qty'  => 0,
					'url'  => $pid ? get_edit_post_link( $pid, 'raw' ) : '',
				);
			}
			$agg[ $pid ]['qty'] += (int) $item->get_quantity();
		}
	}
	uasort(
		$agg,
		function ( $a, $b ) {
			return $b['qty'] <=> $a['qty'];
		}
	);
	return array_slice( array_values( $agg ), 0, 5 );
}

/**
 * Sistem durum satırları.
 *
 * @return array<int, array{label:string,value:string,ok:bool|null}>
 */
function df_app_system_rows() {
	global $wpdb;
	$gateways = array();
	if ( WC()->payment_gateways() ) {
		foreach ( WC()->payment_gateways()->payment_gateways() as $gw ) {
			if ( 'yes' === $gw->enabled ) {
				$gateways[] = wp_strip_all_tags( $gw->get_title() );
			}
		}
	}
	$checkout_id = wc_get_page_id( 'checkout' );
	$classic     = $checkout_id > 0 && has_shortcode( (string) get_post_field( 'post_content', $checkout_id ), 'woocommerce_checkout' );
	return array(
		array( 'label' => 'WordPress', 'value' => get_bloginfo( 'version' ), 'ok' => true ),
		array( 'label' => 'WooCommerce', 'value' => defined( 'WC_VERSION' ) ? WC_VERSION : '—', 'ok' => true ),
		array( 'label' => 'PHP', 'value' => PHP_VERSION, 'ok' => version_compare( PHP_VERSION, '7.4', '>=' ) ),
		array( 'label' => 'Veritabanı', 'value' => method_exists( $wpdb, 'db_server_info' ) ? preg_replace( '/^5\.5\.5-/', '', (string) $wpdb->db_server_info() ) : '—', 'ok' => true ),
		array( 'label' => 'Ödeme yöntemleri', 'value' => $gateways ? implode( ', ', $gateways ) : 'Etkin yöntem yok', 'ok' => (bool) $gateways ),
		array( 'label' => 'Çiçekçi ödeme sayfası', 'value' => $classic ? 'Aktif (klasik)' : 'Blok ödeme — dönüştürün', 'ok' => $classic ),
		array( 'label' => 'Zamanlanmış görevler', 'value' => ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) ? 'WP-Cron kapalı (sunucu cron gerekli)' : 'Aktif', 'ok' => ! ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) ? true : null ),
		array( 'label' => 'SSL', 'value' => is_ssl() || 0 === strpos( home_url(), 'https' ) ? 'Aktif' : 'Kapalı', 'ok' => is_ssl() || 0 === strpos( home_url(), 'https' ) ),
		array( 'label' => 'E-posta bildirimi', 'value' => get_option( 'admin_email' ), 'ok' => null ),
	);
}

/**
 * SEO durum satırları.
 *
 * @return array<int, array{label:string,value:string,ok:bool|null,url?:string}>
 */
function df_app_seo_rows() {
	$public   = '1' === (string) get_option( 'blog_public' );
	$plugin   = defined( 'WPSEO_VERSION' ) ? 'Yoast SEO' : ( defined( 'RANK_MATH_VERSION' ) ? 'Rank Math' : '' );
	$sitemap  = $plugin ? home_url( 'Yoast SEO' === $plugin ? '/sitemap_index.xml' : '/sitemap_index.xml' ) : home_url( '/wp-sitemap.xml' );
	$core_map = function_exists( 'wp_sitemaps_get_server' ) && wp_sitemaps_get_server()->sitemaps_enabled();
	return array(
		array( 'label' => 'Arama motorlarına açık', 'value' => $public ? 'Evet' : 'Hayır — Ayarlar → Okuma', 'ok' => $public ),
		array( 'label' => 'XML site haritası', 'value' => ( $plugin || $core_map ) ? 'Aktif' : 'Kapalı', 'ok' => $plugin || $core_map, 'url' => $sitemap ),
		array( 'label' => 'robots.txt', 'value' => 'Görüntüle', 'ok' => null, 'url' => home_url( '/robots.txt' ) ),
		array( 'label' => 'Kalıcı bağlantılar', 'value' => get_option( 'permalink_structure' ) ? 'Okunaklı' : 'Düz (?p=) — değiştirin', 'ok' => (bool) get_option( 'permalink_structure' ) ),
		array( 'label' => 'SEO eklentisi', 'value' => $plugin ? $plugin : 'Yok (Yoast / Rank Math önerilir)', 'ok' => $plugin ? true : null ),
		array( 'label' => 'Site açıklaması', 'value' => get_bloginfo( 'description' ) ? 'Girilmiş' : 'Boş — Ayarlar → Genel', 'ok' => (bool) get_bloginfo( 'description' ) ),
	);
}

/**
 * Durum satırları çıktısı.
 *
 * @param array $rows Satırlar.
 */
function df_app_rows( $rows ) {
	echo '<ul class="df-app-rows">';
	foreach ( $rows as $r ) {
		$icon  = true === $r['ok'] ? 'yes-alt' : ( false === $r['ok'] ? 'warning' : 'info-outline' );
		$class = true === $r['ok'] ? 'is-ok' : ( false === $r['ok'] ? 'is-warn' : 'is-info' );
		$val   = ! empty( $r['url'] ) ? '<a href="' . esc_url( $r['url'] ) . '" target="_blank" rel="noopener">' . esc_html( $r['value'] ) . '</a>' : esc_html( $r['value'] );
		printf( '<li><span class="df-app-rows__label">%s</span><span class="df-app-rows__value">%s</span><span class="dashicons dashicons-%s %s" aria-label="%s"></span></li>', esc_html( $r['label'] ), $val, esc_attr( $icon ), esc_attr( $class ), esc_attr( true === $r['ok'] ? 'Sorun yok' : ( false === $r['ok'] ? 'Dikkat' : 'Bilgi' ) ) ); // phpcs:ignore
	}
	echo '</ul>';
}

/**
 * Bildirimler (gerçek olaylar).
 *
 * @return array<int, array{icon:string,text:string,time:int,url:string,tone:string}>
 */
function df_app_notifications() {
	$out = array();
	foreach ( wc_get_orders( array( 'limit' => 4, 'orderby' => 'date', 'order' => 'DESC', 'type' => 'shop_order' ) ) as $o ) {
		$out[] = array(
			'icon' => 'cart',
			'text' => 'Yeni sipariş #' . $o->get_order_number() . ' — ' . trim( $o->get_billing_first_name() . ' ' . $o->get_billing_last_name() ),
			'time' => $o->get_date_created() ? $o->get_date_created()->getTimestamp() : 0,
			'url'  => $o->get_edit_order_url(),
			'tone' => 'info',
		);
	}
	$low_amount = absint( get_option( 'woocommerce_notify_low_stock_amount', 2 ) );
	$low        = array_filter(
		wc_get_products(
			array(
				'limit'        => 50,
				'manage_stock' => true,
				'stock_status' => 'instock',
			)
		),
		function ( $p ) use ( $low_amount ) {
			return null !== $p->get_stock_quantity() && $p->get_stock_quantity() <= $low_amount;
		}
	);
	$low        = array_slice( $low, 0, 3 );
	foreach ( $low as $p ) {
		$out[] = array(
			'icon' => 'warning',
			'text' => 'Stok azaldı: ' . $p->get_name() . ' (' . (int) $p->get_stock_quantity() . ')',
			'time' => time(),
			'url'  => get_edit_post_link( $p->get_id(), 'raw' ),
			'tone' => 'warn',
		);
	}
	$pending_reviews = (int) get_comments(
		array(
			'type'   => 'review',
			'status' => 'hold',
			'count'  => true,
		)
	);
	if ( $pending_reviews ) {
		$out[] = array(
			'icon' => 'testimonial',
			'text' => $pending_reviews . ' yorum onay bekliyor',
			'time' => time(),
			'url'  => admin_url( 'edit.php?post_type=product&page=product-reviews&comment_status=moderated' ),
			'tone' => 'warn',
		);
	}
	usort(
		$out,
		function ( $a, $b ) {
			return $b['time'] <=> $a['time'];
		}
	);
	return array_slice( $out, 0, 6 );
}

/**
 * Genel bakış sayfası.
 */
function df_app_dashboard() {
	$today     = df_now()->format( 'Y-m-d' );
	$today_ts  = df_now()->setTime( 0, 0 )->getTimestamp();
	$delivered = wc_get_orders(
		array(
			'limit'          => -1,
			'return'         => 'ids',
			'status'         => 'completed',
			'date_completed' => '>=' . $today_ts,
			'type'           => 'shop_order',
		)
	);
	$today_deliveries = df_app_orders_for_date( $today );
	$stats            = array(
		array( 'Yeni Sipariş', (int) wc_orders_count( 'processing' ) + (int) wc_orders_count( 'on-hold' ), 'Onay / hazırlık bekliyor', 'clipboard', 'rose', df_app_orders_url( 'processing' ) ),
		array( 'Hazırlanıyor', (int) wc_orders_count( 'df-preparing' ), 'Atölyede', 'admin-customizer', 'sand', df_app_orders_url( 'df-preparing' ) ),
		array( 'Kuryede', (int) wc_orders_count( 'df-on-the-way' ), 'Yolda', 'car', 'sage', df_app_orders_url( 'df-on-the-way' ) ),
		array( 'Teslim Edildi', count( $delivered ), 'Bugün · ' . count( $today_deliveries ) . ' teslimat planlı', 'yes-alt', 'mint', admin_url( 'admin.php?page=df-deliveries' ) ),
	);
	$recent = wc_get_orders(
		array(
			'limit'   => 8,
			'orderby' => 'date',
			'order'   => 'DESC',
			'type'    => 'shop_order',
		)
	);
	$days   = df_app_sales_7days();
	$top    = df_app_top_products();
	$notes  = df_app_notifications();
	$week   = array_sum( wp_list_pluck( $days, 'total' ) );
	$wcount = array_sum( wp_list_pluck( $days, 'count' ) );

	df_app_shell(
		'df-dashboard',
		'Yönetim Paneli',
		get_bloginfo( 'name' ) . "'a hoş geldiniz. Bugün neler oluyor?",
		function () use ( $stats, $recent, $days, $top, $notes, $week, $wcount ) {
			?>
			<section class="df-app-stats">
				<?php foreach ( $stats as $s ) : ?>
					<a class="df-app-stat is-<?php echo esc_attr( $s[4] ); ?>" href="<?php echo esc_url( $s[5] ); ?>">
						<span class="df-app-stat__icon dashicons dashicons-<?php echo esc_attr( $s[3] ); ?>"></span>
						<span class="df-app-stat__body">
							<span class="df-app-stat__label"><?php echo esc_html( $s[0] ); ?></span>
							<strong><?php echo (int) $s[1]; ?></strong>
							<small><?php echo esc_html( $s[2] ); ?></small>
						</span>
					</a>
				<?php endforeach; ?>
			</section>

			<div class="df-app-grid">
				<section class="df-app-card df-app-card--wide">
					<header class="df-app-card__head"><h2>Son Siparişler</h2><a class="df-app-link" href="<?php echo esc_url( df_app_orders_url() ); ?>">Tümünü Gör →</a></header>
					<?php if ( $recent ) : ?>
						<div class="df-app-table-wrap">
							<table class="df-app-table">
								<thead><tr><th>#</th><th>Müşteri</th><th>Teslimat</th><th>Durum</th><th>Yazdırma</th><th class="num">Tutar</th></tr></thead>
								<tbody>
								<?php foreach ( $recent as $o ) : ?>
									<?php $date = $o->get_meta( '_df_delivery_date' ); ?>
									<tr>
										<td><a href="<?php echo esc_url( $o->get_edit_order_url() ); ?>">#<?php echo esc_html( $o->get_order_number() ); ?></a></td>
										<td><?php echo esc_html( trim( $o->get_billing_first_name() . ' ' . $o->get_billing_last_name() ) ); ?></td>
										<td><?php echo esc_html( df_app_district( $o ) ); ?><?php if ( $date ) : ?><small><?php echo esc_html( wp_date( 'j M', strtotime( $date . ' 12:00:00' ) ) . ' · ' . $o->get_meta( '_df_delivery_slot' ) ); ?></small><?php endif; ?></td>
										<td><?php echo df_app_status_badge( $o ); // phpcs:ignore ?></td>
										<td><?php echo $o->get_meta( '_df_printed' ) ? '<span class="df-app-pill is-ok">Yazdırıldı</span>' : '<span class="df-app-pill is-warn">Yazdırılmadı</span>'; ?></td>
										<td class="num"><?php echo wp_kses_post( $o->get_formatted_order_total() ); ?></td>
									</tr>
								<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					<?php else : ?>
						<p class="df-app-empty">Henüz sipariş yok.</p>
					<?php endif; ?>
				</section>

				<section class="df-app-card">
					<header class="df-app-card__head"><h2>Hızlı İşlemler</h2></header>
					<div class="df-app-quick">
						<?php
						$quick = array(
							array( 'plus-alt2', 'Yeni Ürün Ekle', admin_url( 'post-new.php?post_type=product' ) ),
							array( 'printer', 'Toplu Yazdırma', admin_url( 'admin.php?page=df-print' ) ),
							array( 'tickets-alt', 'Kupon Oluştur', admin_url( 'post-new.php?post_type=shop_coupon' ) ),
							array( 'art', 'Siteyi Tasarla', admin_url( 'admin.php?page=df-studio' ) ),
							array( 'location', 'İlçe / Saat Ayarları', admin_url( 'admin.php?page=derin-flowers#delivery' ) ),
							array( 'edit', 'Blog Yazısı Ekle', admin_url( 'post-new.php' ) ),
							array( 'car', 'Kurye Ata', admin_url( 'admin.php?page=df-couriers' ) ),
							array( 'chart-bar', 'Raporlar', admin_url( 'admin.php?page=wc-reports' ) ),
						);
						foreach ( $quick as $q ) :
							?>
							<a href="<?php echo esc_url( $q[2] ); ?>"><span class="dashicons dashicons-<?php echo esc_attr( $q[0] ); ?>"></span><?php echo esc_html( $q[1] ); ?></a>
						<?php endforeach; ?>
					</div>
				</section>

				<section class="df-app-card">
					<header class="df-app-card__head"><h2>Sistem Durumu</h2><a class="df-app-link" href="<?php echo esc_url( admin_url( 'admin.php?page=df-system' ) ); ?>">Detay →</a></header>
					<?php df_app_rows( array_slice( df_app_system_rows(), 0, 8 ) ); ?>
				</section>

				<section class="df-app-card df-app-card--wide">
					<header class="df-app-card__head">
						<h2>Son 7 Gün Ciro</h2>
						<span class="df-app-kpi"><?php echo wp_kses_post( wc_price( $week ) ); ?> · <?php echo (int) $wcount; ?> sipariş</span>
					</header>
					<?php df_app_sales_chart( $days ); ?>
					<details class="df-app-tableview">
						<summary>Tablo görünümü</summary>
						<table class="df-app-table">
							<thead><tr><th>Gün</th><th class="num">Sipariş</th><th class="num">Ciro</th></tr></thead>
							<tbody>
								<?php foreach ( $days as $d ) : ?>
									<tr><td><?php echo esc_html( $d['label'] ); ?></td><td class="num"><?php echo (int) $d['count']; ?></td><td class="num"><?php echo wp_kses_post( wc_price( $d['total'] ) ); ?></td></tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</details>
				</section>

				<section class="df-app-card">
					<header class="df-app-card__head"><h2>En Çok Satanlar</h2><span class="df-app-muted">Son 30 gün</span></header>
					<?php if ( $top ) : ?>
						<ol class="df-app-top">
							<?php foreach ( $top as $i => $t ) : ?>
								<li><span class="df-app-top__n"><?php echo (int) $i + 1; ?></span><a href="<?php echo esc_url( $t['url'] ); ?>"><?php echo esc_html( $t['name'] ); ?></a><strong><?php echo (int) $t['qty']; ?></strong></li>
							<?php endforeach; ?>
						</ol>
					<?php else : ?>
						<p class="df-app-empty">Son 30 günde satış yok.</p>
					<?php endif; ?>
				</section>

				<section class="df-app-card">
					<header class="df-app-card__head"><h2>SEO & İndeksleme</h2></header>
					<?php df_app_rows( df_app_seo_rows() ); ?>
				</section>

				<section class="df-app-card df-app-card--wide">
					<header class="df-app-card__head"><h2>Bildirimler</h2></header>
					<?php if ( $notes ) : ?>
						<ul class="df-app-notes">
							<?php foreach ( $notes as $n ) : ?>
								<li class="is-<?php echo esc_attr( $n['tone'] ); ?>">
									<span class="dashicons dashicons-<?php echo esc_attr( $n['icon'] ); ?>"></span>
									<a href="<?php echo esc_url( $n['url'] ); ?>"><?php echo esc_html( $n['text'] ); ?></a>
									<time><?php echo esc_html( $n['time'] ? human_time_diff( $n['time'] ) . ' önce' : '' ); ?></time>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php else : ?>
						<p class="df-app-empty">Yeni bildirim yok.</p>
					<?php endif; ?>
				</section>
			</div>
			<?php
		}
	);
}

/* -------------------------------------------------------------------------
 * İzmir Teslimat (günlük takvim)
 * ---------------------------------------------------------------------- */

/**
 * Seçili tarih (?date=).
 *
 * @return string
 */
function df_app_req_date() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$d = isset( $_GET['date'] ) ? sanitize_text_field( wp_unslash( $_GET['date'] ) ) : '';
	return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $d ) ? $d : df_now()->format( 'Y-m-d' );
}

/**
 * Tarih gezinme çubuğu.
 *
 * @param string $page Sayfa.
 * @param string $date Tarih.
 */
function df_app_date_nav( $page, $date ) {
	$ts   = strtotime( $date . ' 12:00:00' );
	$prev = gmdate( 'Y-m-d', $ts - DAY_IN_SECONDS );
	$next = gmdate( 'Y-m-d', $ts + DAY_IN_SECONDS );
	$base = admin_url( 'admin.php?page=' . $page );
	?>
	<form class="df-app-datenav" method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>">
		<input type="hidden" name="page" value="<?php echo esc_attr( $page ); ?>">
		<a class="button" href="<?php echo esc_url( add_query_arg( 'date', $prev, $base ) ); ?>">← Önceki gün</a>
		<input type="date" name="date" value="<?php echo esc_attr( $date ); ?>" onchange="this.form.submit()">
		<a class="button" href="<?php echo esc_url( add_query_arg( 'date', $next, $base ) ); ?>">Sonraki gün →</a>
		<a class="button button-link" href="<?php echo esc_url( $base ); ?>">Bugün</a>
		<strong class="df-app-datenav__label"><?php echo esc_html( wp_date( 'j F Y, l', $ts ) ); ?></strong>
	</form>
	<?php
}

/**
 * Teslimat takvimi.
 */
function df_app_deliveries() {
	$date   = df_app_req_date();
	$orders = df_app_orders_for_date( $date );
	$groups = array();
	$by_district = array();
	foreach ( $orders as $o ) {
		$slot                = $o->get_meta( '_df_delivery_slot' ) ? $o->get_meta( '_df_delivery_slot' ) : 'Saat belirtilmemiş';
		$groups[ $slot ][]   = $o;
		$dist                = df_app_district( $o );
		$by_district[ $dist ] = isset( $by_district[ $dist ] ) ? $by_district[ $dist ] + 1 : 1;
	}
	ksort( $groups );
	arsort( $by_district );
	$couriers = df_app_couriers_list();

	df_app_shell(
		'df-deliveries',
		'İzmir Teslimat',
		count( $orders ) . ' teslimat · ' . count( $by_district ) . ' bölge',
		function () use ( $date, $groups, $by_district, $couriers ) {
			df_app_date_nav( 'df-deliveries', $date );
			if ( ! $groups ) {
				echo '<div class="df-app-card"><p class="df-app-empty">Bu tarihte teslimat yok.</p></div>';
				return;
			}
			?>
			<div class="df-app-chips">
				<?php foreach ( $by_district as $d => $n ) : ?>
					<span class="df-app-chip"><?php echo esc_html( $d ); ?> <strong><?php echo (int) $n; ?></strong></span>
				<?php endforeach; ?>
			</div>
			<?php foreach ( $groups as $slot => $list ) : ?>
				<section class="df-app-card">
					<header class="df-app-card__head"><h2><span class="dashicons dashicons-clock"></span> <?php echo esc_html( $slot ); ?></h2><span class="df-app-muted"><?php echo count( $list ); ?> teslimat</span></header>
					<div class="df-app-deliveries">
						<?php foreach ( $list as $o ) : ?>
							<?php $courier = $o->get_meta( '_df_courier' ); ?>
							<article class="df-app-delivery">
								<div class="df-app-delivery__top">
									<a href="<?php echo esc_url( $o->get_edit_order_url() ); ?>"><strong>#<?php echo esc_html( $o->get_order_number() ); ?></strong></a>
									<?php echo df_app_status_badge( $o ); // phpcs:ignore ?>
								</div>
								<p class="df-app-delivery__who"><span class="dashicons dashicons-admin-users"></span><?php echo esc_html( $o->get_meta( '_df_recipient_name' ) ); ?> <a href="<?php echo esc_url( df_tel( $o->get_meta( '_df_recipient_phone' ) ) ); ?>"><?php echo esc_html( $o->get_meta( '_df_recipient_phone' ) ); ?></a></p>
								<p class="df-app-delivery__addr"><span class="dashicons dashicons-location"></span><span><strong><?php echo esc_html( df_app_district( $o ) ); ?></strong> <?php echo esc_html( $o->get_meta( '_df_address' ) ); ?><?php echo $o->get_meta( '_df_address_hint' ) ? ' · ' . esc_html( $o->get_meta( '_df_address_hint' ) ) : ''; ?></span></p>
								<p class="df-app-delivery__items"><span class="dashicons dashicons-products"></span><?php echo esc_html( implode( ', ', array_map( function ( $i ) { return $i->get_name() . ' ×' . $i->get_quantity(); }, $o->get_items() ) ) ); ?></p>
								<?php if ( $o->get_meta( '_df_note' ) ) : ?>
									<p class="df-app-delivery__note">“<?php echo esc_html( wp_trim_words( $o->get_meta( '_df_note' ), 18 ) ); ?>”</p>
								<?php endif; ?>
								<div class="df-app-delivery__foot">
									<span class="df-app-muted"><?php echo $courier ? 'Kurye: ' . esc_html( strtok( $courier, '|' ) ) : 'Kurye atanmadı'; ?></span>
									<span>
										<a class="button button-small" target="_blank" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=df_print_bulk&type=both&ids[]=' . $o->get_id() ), 'df_print_bulk' ) ); ?>">Yazdır</a>
										<a class="button button-small" href="<?php echo esc_url( $o->get_edit_order_url() ); ?>">Aç</a>
									</span>
								</div>
							</article>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endforeach; ?>
			<?php
		}
	);
}

/* -------------------------------------------------------------------------
 * Yazdırma Merkezi
 * ---------------------------------------------------------------------- */

/**
 * Yazdırma merkezi.
 */
function df_app_print() {
	$date   = df_app_req_date();
	$orders = df_app_orders_for_date( $date );
	df_app_shell(
		'df-print',
		'Yazdırma Merkezi',
		'Not kartlarını ve teslimat fişlerini toplu yazdırın.',
		function () use ( $date, $orders ) {
			df_app_date_nav( 'df-print', $date );
			?>
			<form class="df-app-card" method="get" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" target="_blank" data-df-print-form>
				<input type="hidden" name="action" value="df_print_bulk">
				<?php wp_nonce_field( 'df_print_bulk', '_wpnonce', false ); ?>
				<header class="df-app-card__head">
					<h2><?php echo count( $orders ); ?> sipariş</h2>
					<div class="df-app-actions">
						<label class="df-app-select-all"><input type="checkbox" data-df-check-all> Tümünü seç</label>
						<button class="button" name="type" value="card">Kartları yazdır</button>
						<button class="button" name="type" value="slip">Fişleri yazdır</button>
						<button class="button button-primary" name="type" value="both">Kart + fiş</button>
					</div>
				</header>
				<?php if ( $orders ) : ?>
					<div class="df-app-table-wrap">
						<table class="df-app-table">
							<thead><tr><th></th><th>#</th><th>Saat</th><th>Alıcı</th><th>Bölge</th><th>Kart notu</th><th>Durum</th></tr></thead>
							<tbody>
								<?php foreach ( $orders as $o ) : ?>
									<tr>
										<td><input type="checkbox" name="ids[]" value="<?php echo (int) $o->get_id(); ?>" <?php checked( ! $o->get_meta( '_df_printed' ) ); ?>></td>
										<td><a href="<?php echo esc_url( $o->get_edit_order_url() ); ?>">#<?php echo esc_html( $o->get_order_number() ); ?></a></td>
										<td><?php echo esc_html( $o->get_meta( '_df_delivery_slot' ) ); ?></td>
										<td><?php echo esc_html( $o->get_meta( '_df_recipient_name' ) ); ?></td>
										<td><?php echo esc_html( df_app_district( $o ) ); ?></td>
										<td class="df-app-clip"><?php echo $o->get_meta( '_df_note' ) ? esc_html( wp_trim_words( $o->get_meta( '_df_note' ), 10 ) ) : '<span class="df-app-muted">Not yok</span>'; ?></td>
										<td><?php echo $o->get_meta( '_df_printed' ) ? '<span class="df-app-pill is-ok">Yazdırıldı</span>' : '<span class="df-app-pill is-warn">Bekliyor</span>'; ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php else : ?>
					<p class="df-app-empty">Bu tarihte teslimat yok.</p>
				<?php endif; ?>
			</form>
			<?php
		}
	);
}

/**
 * Toplu yazdırma çıktısı (kart A6 yatay, fiş A5).
 */
function df_print_bulk() {
	if ( ! current_user_can( 'edit_shop_orders' ) || ! check_admin_referer( 'df_print_bulk' ) ) {
		wp_die( 'Yetkiniz yok.' );
	}
	$ids  = isset( $_GET['ids'] ) ? array_map( 'absint', (array) wp_unslash( $_GET['ids'] ) ) : array();
	$type = isset( $_GET['type'] ) ? sanitize_key( wp_unslash( $_GET['type'] ) ) : 'both';
	$ids  = array_filter( $ids );
	if ( ! $ids ) {
		wp_die( 'Yazdırmak için en az bir sipariş seçin.' );
	}
	$fonts = df_fonts_url();
	?>
	<!doctype html>
	<html lang="tr"><head><meta charset="utf-8"><title>Yazdır — <?php echo count( $ids ); ?> sipariş</title>
	<?php if ( $fonts ) : ?><link rel="stylesheet" href="<?php echo esc_url( $fonts ); ?>"><?php endif; // phpcs:ignore ?>
	<style>
		body { margin: 0; color: #2B2522; font-family: <?php echo '"' . esc_html( df_opt( 'font_body' ) ) . '"'; ?>, system-ui, sans-serif; }
		.page { page-break-after: always; box-sizing: border-box; }
		.card { width: 148mm; height: 105mm; padding: 16mm 18mm; display: flex; flex-direction: column; justify-content: center; text-align: center; font-family: <?php echo '"' . esc_html( df_opt( 'font_heading' ) ) . '"'; ?>, serif; position: relative; }
		.card .note { font-size: 19pt; line-height: 1.45; font-style: italic; white-space: pre-wrap; }
		.card .from { margin-top: 8mm; font-size: 13pt; letter-spacing: .06em; }
		.card .brand { position: absolute; bottom: 7mm; left: 0; right: 0; font-size: 7.5pt; letter-spacing: .3em; color: #8a7f79; font-family: system-ui, sans-serif; }
		.slip { width: 148mm; min-height: 200mm; padding: 12mm; font-size: 10.5pt; line-height: 1.5; }
		.slip h1 { font-size: 16pt; margin: 0 0 2mm; }
		.slip .big { font-size: 13pt; font-weight: 700; }
		.slip table { width: 100%; border-collapse: collapse; margin: 4mm 0; }
		.slip td, .slip th { border-bottom: 1px solid #ddd; padding: 2mm 0; text-align: left; vertical-align: top; }
		.slip th { width: 32%; color: #666; font-weight: 500; }
		.slip .box { border: 1.5px solid #2B2522; padding: 4mm; margin: 4mm 0; }
		@page { margin: 0; }
		@media screen { body { background: #eee; } .page { background: #fff; margin: 20px auto; box-shadow: 0 4px 20px rgba(0,0,0,.1); } }
	</style></head>
	<body onload="window.print()">
	<?php
	foreach ( $ids as $id ) {
		$o = wc_get_order( $id );
		if ( ! $o ) {
			continue;
		}
		if ( in_array( $type, array( 'card', 'both' ), true ) && $o->get_meta( '_df_note' ) ) {
			$from = 'yes' === $o->get_meta( '_df_note_anon' ) ? '' : $o->get_meta( '_df_note_from' );
			echo '<div class="page card"><div class="note">' . esc_html( $o->get_meta( '_df_note' ) ) . '</div>';
			echo $from ? '<div class="from">— ' . esc_html( $from ) . '</div>' : '';
			echo '<div class="brand">' . esc_html( df_opt( 'logo_text' ) ) . '</div></div>';
		}
		if ( in_array( $type, array( 'slip', 'both' ), true ) ) {
			$rows = df_order_delivery_rows( $o );
			unset( $rows['Kart notu'], $rows['Kartta imza'], $rows['Not kategorisi'] );
			echo '<div class="page slip"><h1>' . esc_html( df_opt( 'logo_text' ) ) . ' — Sipariş #' . esc_html( $o->get_order_number() ) . '</h1>';
			echo '<div class="box"><div class="big">' . esc_html( $o->get_meta( '_df_recipient_name' ) ) . ' · ' . esc_html( $o->get_meta( '_df_recipient_phone' ) ) . '</div>';
			echo '<div>' . esc_html( df_app_district( $o ) ) . ' — ' . esc_html( $o->get_meta( '_df_address' ) ) . '</div>';
			echo $o->get_meta( '_df_address_hint' ) ? '<div>Tarif: ' . esc_html( $o->get_meta( '_df_address_hint' ) ) . '</div>' : '';
			echo '</div><table>';
			foreach ( $rows as $label => $value ) {
				echo '<tr><th>' . esc_html( $label ) . '</th><td>' . nl2br( esc_html( $value ) ) . '</td></tr>';
			}
			echo '<tr><th>Ürünler</th><td>';
			foreach ( $o->get_items() as $item ) {
				echo esc_html( $item->get_name() . ' × ' . $item->get_quantity() ) . '<br>';
			}
			echo '</td></tr>';
			if ( $o->get_customer_note() ) {
				echo '<tr><th>Kurye notu</th><td>' . esc_html( $o->get_customer_note() ) . '</td></tr>';
			}
			if ( $o->get_meta( '_df_courier' ) ) {
				echo '<tr><th>Kurye</th><td>' . esc_html( str_replace( '|', ' · ', $o->get_meta( '_df_courier' ) ) ) . '</td></tr>';
			}
			echo '</table><p>Teslim alan imza: ____________________</p></div>';
		}
		$o->update_meta_data( '_df_printed', time() );
		$o->save();
	}
	?>
	</body></html>
	<?php
	exit;
}
add_action( 'admin_post_df_print_bulk', 'df_print_bulk' );

/* -------------------------------------------------------------------------
 * Kurye yönetimi
 * ---------------------------------------------------------------------- */

/**
 * Kurye sayfası.
 */
function df_app_couriers() {
	$date     = df_app_req_date();
	$orders   = array_filter(
		df_app_orders_for_date( $date ),
		function ( $o ) {
			return 'pickup' !== $o->get_meta( '_df_delivery_type' ) && 'completed' !== $o->get_status();
		}
	);
	$couriers = df_app_couriers_list();
	df_app_shell(
		'df-couriers',
		'Kurye Yönetimi',
		'Teslimatları kuryelere atayın, adresi WhatsApp ile tek dokunuşla gönderin.',
		function () use ( $date, $orders, $couriers ) {
			df_app_date_nav( 'df-couriers', $date );
			if ( ! $couriers ) {
				echo '<div class="df-app-card"><p class="df-app-empty">Henüz kurye tanımlı değil. <a href="' . esc_url( admin_url( 'admin.php?page=derin-flowers#delivery' ) ) . '">Derin Flowers → Teslimat & Ödeme → Kuryeler</a> alanına "Ad Soyad | Telefon" satırları ekleyin.</p></div>';
			}
			$load = array();
			foreach ( $orders as $o ) {
				$c = strtok( (string) $o->get_meta( '_df_courier' ), '|' );
				if ( $c ) {
					$load[ $c ] = isset( $load[ $c ] ) ? $load[ $c ] + 1 : 1;
				}
			}
			if ( $couriers ) :
				?>
				<div class="df-app-chips">
					<?php foreach ( $couriers as $c ) : ?>
						<span class="df-app-chip"><span class="dashicons dashicons-car"></span><?php echo esc_html( $c['name'] ); ?> <strong><?php echo (int) ( isset( $load[ $c['name'] ] ) ? $load[ $c['name'] ] : 0 ); ?></strong></span>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<section class="df-app-card">
				<?php if ( $orders ) : ?>
					<div class="df-app-table-wrap">
						<table class="df-app-table">
							<thead><tr><th>#</th><th>Saat</th><th>Bölge</th><th>Alıcı</th><th>Durum</th><th>Kurye</th><th></th></tr></thead>
							<tbody>
							<?php foreach ( $orders as $o ) : ?>
								<?php
								$cur = (string) $o->get_meta( '_df_courier' );
								$cph = strpos( $cur, '|' ) ? substr( $cur, strpos( $cur, '|' ) + 1 ) : '';
								?>
								<tr data-order="<?php echo (int) $o->get_id(); ?>">
									<td><a href="<?php echo esc_url( $o->get_edit_order_url() ); ?>">#<?php echo esc_html( $o->get_order_number() ); ?></a></td>
									<td><?php echo esc_html( $o->get_meta( '_df_delivery_slot' ) ); ?></td>
									<td><?php echo esc_html( df_app_district( $o ) ); ?></td>
									<td><?php echo esc_html( $o->get_meta( '_df_recipient_name' ) ); ?></td>
									<td data-status><?php echo df_app_status_badge( $o ); // phpcs:ignore ?></td>
									<td>
										<select data-df-courier <?php disabled( ! $couriers ); ?>>
											<option value="">— Seçin —</option>
											<?php foreach ( $couriers as $i => $c ) : ?>
												<option value="<?php echo (int) $i; ?>" <?php selected( strtok( $cur, '|' ), $c['name'] ); ?>><?php echo esc_html( $c['name'] ); ?></option>
											<?php endforeach; ?>
										</select>
									</td>
									<td>
										<a class="button button-small df-app-wa" data-df-wa target="_blank" rel="noopener" href="<?php echo $cph ? esc_url( df_app_courier_whatsapp( $o, $cph ) ) : '#'; ?>" <?php echo $cph ? '' : 'hidden'; ?>>WhatsApp ile gönder</a>
									</td>
								</tr>
							<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php else : ?>
					<p class="df-app-empty">Bu tarihte kurye gerektiren teslimat yok.</p>
				<?php endif; ?>
			</section>
			<?php
		}
	);
}

/**
 * AJAX: kurye ata.
 */
function df_ajax_assign_courier() {
	check_ajax_referer( 'df_app', 'nonce' );
	if ( ! current_user_can( 'edit_shop_orders' ) ) {
		wp_send_json_error( null, 403 );
	}
	$order = wc_get_order( isset( $_POST['order'] ) ? absint( $_POST['order'] ) : 0 );
	if ( ! $order ) {
		wp_send_json_error();
	}
	$idx      = isset( $_POST['courier'] ) ? sanitize_text_field( wp_unslash( $_POST['courier'] ) ) : '';
	$couriers = df_app_couriers_list();
	if ( '' === $idx || ! isset( $couriers[ (int) $idx ] ) ) {
		$order->delete_meta_data( '_df_courier' );
		$order->save();
		wp_send_json_success( array( 'wa' => '' ) );
	}
	$c = $couriers[ (int) $idx ];
	$order->update_meta_data( '_df_courier', $c['name'] . '|' . $c['phone'] );
	$order->add_order_note( 'Kurye atandı: ' . $c['name'] );
	if ( df_opt( 'courier_status' ) && in_array( $order->get_status(), array( 'processing', 'on-hold', 'df-preparing' ), true ) ) {
		$order->set_status( 'df-on-the-way' );
	}
	$order->save();
	wp_send_json_success(
		array(
			'wa'     => $c['phone'] ? df_app_courier_whatsapp( $order, $c['phone'] ) : '',
			'status' => df_app_status_badge( $order ),
		)
	);
}
add_action( 'wp_ajax_df_assign_courier', 'df_ajax_assign_courier' );

/* -------------------------------------------------------------------------
 * Sistem durumu
 * ---------------------------------------------------------------------- */

/**
 * Sistem sayfası.
 */
function df_app_system() {
	df_app_shell(
		'df-system',
		'Sistem Durumu',
		'Sunucu, mağaza ve SEO kontrolleri.',
		function () {
			$hpos = class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
			$more = array(
				array( 'label' => 'Tema', 'value' => 'Derin Flowers ' . DF_VERSION, 'ok' => true ),
				array( 'label' => 'Sipariş depolama', 'value' => $hpos ? 'Yüksek performans (HPOS)' : 'Klasik (yazı tablosu)', 'ok' => true ),
				array( 'label' => 'Bellek limiti', 'value' => WP_MEMORY_LIMIT, 'ok' => wp_convert_hr_to_bytes( WP_MEMORY_LIMIT ) >= 128 * MB_IN_BYTES ? true : null ),
				array( 'label' => 'En büyük yükleme', 'value' => size_format( wp_max_upload_size() ), 'ok' => wp_max_upload_size() >= 32 * MB_IN_BYTES ? true : null ),
				array( 'label' => 'Hata ayıklama', 'value' => WP_DEBUG ? 'Açık (canlıda kapatın)' : 'Kapalı', 'ok' => ! WP_DEBUG ),
				array( 'label' => 'Etkin eklenti', 'value' => (string) count( (array) get_option( 'active_plugins', array() ) ), 'ok' => null ),
			);
			?>
			<div class="df-app-grid">
				<section class="df-app-card"><header class="df-app-card__head"><h2>Sistem</h2></header><?php df_app_rows( array_merge( df_app_system_rows(), $more ) ); ?></section>
				<section class="df-app-card"><header class="df-app-card__head"><h2>SEO & İndeksleme</h2></header><?php df_app_rows( df_app_seo_rows() ); ?></section>
			</div>
			<?php
		}
	);
}
