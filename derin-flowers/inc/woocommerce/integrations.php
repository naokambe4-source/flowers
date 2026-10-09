<?php
/**
 * Entegrasyonlar ve müşteri deneyimi:
 * - Yazdırma geçmişi (kim, ne zaman, ne yazdırdı)
 * - Teslimden sonra Google yorum daveti (e-posta + WhatsApp kısayolu)
 * - Bildirim bağlantısı (webhook): SMS firması / otomasyon için sipariş olayları
 * - E-fatura bağlantı noktası: teslim edilen sipariş imzalı JSON olarak sağlayıcıya
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Yazdırma geçmişi
 * ---------------------------------------------------------------------- */

/**
 * Yazdırmayı kaydet.
 *
 * @param WC_Order $order Sipariş.
 * @param string   $type  card|slip|both.
 */
function df_print_log( $order, $type ) {
	$log   = $order->get_meta( '_df_print_log' );
	$log   = is_array( $log ) ? $log : array();
	$user  = wp_get_current_user();
	$log[] = array(
		't'    => time(),
		'type' => sanitize_key( $type ),
		'user' => $user && $user->exists() ? $user->display_name : '',
	);
	$order->update_meta_data( '_df_print_log', array_slice( $log, -20 ) );
	$order->update_meta_data( '_df_printed', time() );
	$order->save();
}

/**
 * Okunur yazdırma geçmişi.
 *
 * @param WC_Order $order Sipariş.
 * @return string[]
 */
function df_print_history( $order ) {
	$names = array(
		'card' => 'Kart',
		'slip' => 'Fiş',
		'both'  => 'Kart + fiş',
		'perfo' => 'Delikli fiş',
	);
	$out   = array();
	foreach ( (array) $order->get_meta( '_df_print_log' ) as $r ) {
		if ( is_array( $r ) && ! empty( $r['t'] ) ) {
			$out[] = wp_date( 'j M H:i', (int) $r['t'] ) . ' · ' . ( isset( $names[ $r['type'] ] ) ? $names[ $r['type'] ] : $r['type'] ) . ( ! empty( $r['user'] ) ? ' · ' . $r['user'] : '' );
		}
	}
	if ( ! $out && $order->get_meta( '_df_printed' ) ) {
		$out[] = wp_date( 'j M H:i', (int) $order->get_meta( '_df_printed' ) );
	}
	return $out;
}

/* -------------------------------------------------------------------------
 * Google yorum daveti
 * ---------------------------------------------------------------------- */

/**
 * "Siparişiniz tamamlandı" e-postasına yorum daveti.
 *
 * @param WC_Order $order         Sipariş.
 * @param bool     $sent_to_admin Yöneticiye mi.
 * @param bool     $plain_text    Düz metin.
 * @param WC_Email $email         E-posta.
 */
function df_review_email( $order, $sent_to_admin, $plain_text, $email ) {
	if ( $sent_to_admin || ! $email || 'customer_completed_order' !== $email->id || ! df_opt( 'review_on', 1 ) || ! df_opt( 'review_url' ) ) {
		return;
	}
	$text = (string) df_opt( 'review_text' );
	$url  = (string) df_opt( 'review_url' );
	if ( $plain_text ) {
		echo "\n" . esc_html( $text ) . "\n" . esc_url_raw( $url ) . "\n\n";
		return;
	}
	echo '<div style="margin:0 0 28px;padding:20px;border-radius:10px;background:#faf7f2;text-align:center">';
	echo '<p style="margin:0 0 14px">' . nl2br( esc_html( $text ) ) . '</p>';
	echo '<a href="' . esc_url( $url ) . '" style="display:inline-block;padding:12px 22px;background:#2b2522;color:#fff;text-decoration:none;border-radius:6px;font-weight:600">★★★★★ Google\'da yorum yap</a>';
	echo '</div>';
}
add_action( 'woocommerce_email_before_order_table', 'df_review_email', 5, 4 );

/**
 * Göndericiye WhatsApp yorum daveti bağlantısı.
 *
 * @param WC_Order $order Sipariş.
 * @return string
 */
function df_review_whatsapp( $order ) {
	$url = (string) df_opt( 'review_url' );
	$d   = preg_replace( '/\D/', '', (string) $order->get_meta( '_df_sender_phone' ) );
	if ( ! $d ) {
		$d = preg_replace( '/\D/', '', (string) $order->get_billing_phone() );
	}
	if ( ! $url || ! $d ) {
		return '';
	}
	if ( 10 === strlen( $d ) && '5' === $d[0] ) {
		$d = '90' . $d;
	} elseif ( 11 === strlen( $d ) && '0' === $d[0] ) {
		$d = '9' . $d;
	}
	$name = trim( (string) $order->get_meta( '_df_sender_name' ) );
	$msg  = 'Merhaba' . ( $name ? ' ' . $name : '' ) . ', #' . $order->get_order_number() . ' numaralı çiçeğiniz teslim edildi. ' . trim( preg_replace( '/\s+/', ' ', (string) df_opt( 'review_text' ) ) ) . ' ' . $url;
	return 'https://wa.me/' . $d . '?text=' . rawurlencode( $msg );
}

/* -------------------------------------------------------------------------
 * Bildirim bağlantısı (webhook) ve e-fatura
 * ---------------------------------------------------------------------- */

/**
 * Sipariş verisi (bildirim ve e-fatura için).
 *
 * @param WC_Order $order Sipariş.
 * @return array
 */
function df_order_payload( $order ) {
	$items = array();
	foreach ( $order->get_items() as $item ) {
		$p       = $item->get_product();
		$qty     = max( 1, (int) $item->get_quantity() );
		$items[] = array(
			'name'       => $item->get_name(),
			'sku'        => $p ? $p->get_sku() : '',
			'qty'        => $qty,
			'unit_price' => round( ( (float) $item->get_total() + (float) $item->get_total_tax() ) / $qty, 2 ),
			'tax'        => round( (float) $item->get_total_tax(), 2 ),
			'total'      => round( (float) $item->get_total() + (float) $item->get_total_tax(), 2 ),
		);
	}
	return array(
		'order_id'      => $order->get_id(),
		'order_number'  => $order->get_order_number(),
		'status'        => $order->get_status(),
		'status_label'  => wc_get_order_status_name( $order->get_status() ),
		'created'       => $order->get_date_created() ? $order->get_date_created()->date( 'c' ) : '',
		'currency'      => $order->get_currency(),
		'total'         => (float) $order->get_total(),
		'tax_total'     => (float) $order->get_total_tax(),
		'shipping'      => (float) $order->get_shipping_total() + (float) array_sum( wp_list_pluck( $order->get_fees(), 'total' ) ),
		'payment'       => $order->get_payment_method_title(),
		'delivery'      => array(
			'type'     => $order->get_meta( '_df_delivery_type' ),
			'date'     => $order->get_meta( '_df_delivery_date' ),
			'slot'     => $order->get_meta( '_df_delivery_slot' ),
			'district' => $order->get_meta( '_df_district' ),
			'address'  => $order->get_meta( '_df_address' ),
		),
		'recipient'     => array(
			'name'  => $order->get_meta( '_df_recipient_name' ),
			'phone' => $order->get_meta( '_df_recipient_phone' ),
		),
		'sender'        => array(
			'name'  => $order->get_meta( '_df_sender_name' ),
			'phone' => $order->get_meta( '_df_sender_phone' ),
		),
		'billing'       => array(
			'name'       => trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
			'company'    => $order->get_billing_company(),
			'email'      => $order->get_billing_email(),
			'phone'      => $order->get_billing_phone(),
			'address'    => trim( $order->get_billing_address_1() . ' ' . $order->get_billing_address_2() ),
			'city'       => $order->get_billing_city(),
			'state'      => $order->get_billing_state(),
			'country'    => $order->get_billing_country(),
			'tax_number' => (string) $order->get_meta( '_billing_tax_number' ),
			'tax_office' => (string) $order->get_meta( '_billing_tax_office' ),
		),
		'items'         => $items,
		'admin_url'     => $order->get_edit_order_url(),
	);
}

/**
 * İmzalı JSON gönder.
 *
 * @param string $url      Adres.
 * @param string $secret   Anahtar.
 * @param array  $data     Veri.
 * @param bool   $blocking Yanıt beklensin mi.
 * @return array|WP_Error
 */
function df_hook_post( $url, $secret, $data, $blocking = false ) {
	$body    = wp_json_encode( $data );
	$headers = array(
		'Content-Type' => 'application/json; charset=utf-8',
		'User-Agent'   => 'DerinFlowers/' . DF_VERSION,
	);
	if ( $secret ) {
		$headers['X-DF-Signature'] = 'sha256=' . hash_hmac( 'sha256', $body, $secret );
	}
	return wp_safe_remote_post(
		$url,
		array(
			'body'     => $body,
			'headers'  => $headers,
			'timeout'  => $blocking ? 10 : 3,
			'blocking' => $blocking,
		)
	);
}

/**
 * Olay bildir.
 *
 * @param string   $event Olay.
 * @param WC_Order $order Sipariş.
 */
function df_hook_event( $event, $order ) {
	do_action( 'df_order_event', $event, $order ); // Eklentiler (SMS vb.) için kanca.
	$url = (string) df_opt( 'hook_url' );
	if ( ! $url ) {
		return;
	}
	df_hook_post( $url, (string) df_opt( 'hook_secret' ), array( 'event' => $event, 'site' => home_url( '/' ), 'order' => df_order_payload( $order ) ) );
}

/**
 * Yeni sipariş.
 *
 * @param int $order_id Sipariş.
 */
function df_hook_new( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( $order && ! $order->get_meta( '_df_hook_new' ) ) {
		$order->update_meta_data( '_df_hook_new', 1 );
		$order->save();
		df_hook_event( 'order.created', $order );
	}
}
add_action( 'woocommerce_checkout_order_processed', 'df_hook_new', 50 );

/**
 * Durum değişti.
 *
 * @param int      $order_id Sipariş.
 * @param string   $from     Eski.
 * @param string   $to       Yeni.
 * @param WC_Order $order    Sipariş.
 */
function df_hook_status( $order_id, $from, $to, $order ) {
	if ( 'df-archived' === $to || 'df-archived' === $from ) {
		return;
	}
	$events = array(
		'processing'    => 'order.paid',
		'df-preparing'  => 'order.preparing',
		'df-on-the-way' => 'order.on_the_way',
		'completed'     => 'order.delivered',
		'cancelled'     => 'order.cancelled',
		'refunded'      => 'order.refunded',
	);
	if ( isset( $events[ $to ] ) ) {
		df_hook_event( $events[ $to ], $order );
	}
	if ( 'completed' === $to && df_opt( 'efatura_url' ) && ! $order->get_meta( '_df_efatura_sent' ) ) {
		wp_schedule_single_event( time(), 'df_efatura_send', array( $order_id ) );
	}
}
add_action( 'woocommerce_order_status_changed', 'df_hook_status', 20, 4 );

/**
 * E-fatura sağlayıcısına aktar.
 *
 * @param int $order_id Sipariş.
 */
function df_efatura_send( $order_id ) {
	$order = wc_get_order( $order_id );
	$url   = (string) df_opt( 'efatura_url' );
	if ( ! $order || ! $url || $order->get_meta( '_df_efatura_sent' ) ) {
		return;
	}
	$res  = df_hook_post( $url, (string) df_opt( 'efatura_secret' ), array( 'event' => 'invoice.create', 'site' => home_url( '/' ), 'order' => df_order_payload( $order ) ), true );
	$code = is_wp_error( $res ) ? 0 : (int) wp_remote_retrieve_response_code( $res );
	if ( $code >= 200 && $code < 300 ) {
		$order->update_meta_data( '_df_efatura_sent', time() );
		$order->add_order_note( 'E-fatura sağlayıcısına aktarıldı.' );
	} else {
		$order->add_order_note( 'E-fatura aktarımı başarısız (' . ( is_wp_error( $res ) ? $res->get_error_message() : 'HTTP ' . $code ) . '). Sipariş tekrar "Teslim edildi" yapılarak yeniden denenebilir.' );
	}
	$order->save();
}
add_action( 'df_efatura_send', 'df_efatura_send' );

/* -------------------------------------------------------------------------
 * WhatsApp değerlendirme mesajı (WasenderAPI)
 * ---------------------------------------------------------------------- */

/**
 * Mesaj gidecek numara (+905xxxxxxxxx): gönderici, yoksa fatura telefonu.
 *
 * @param WC_Order $order Sipariş.
 * @return string
 */
function df_wa_phone( $order ) {
	$d = preg_replace( '/\D/', '', (string) $order->get_meta( '_df_sender_phone' ) );
	if ( strlen( $d ) < 10 ) {
		$d = preg_replace( '/\D/', '', (string) $order->get_billing_phone() );
	}
	if ( 10 === strlen( $d ) && '5' === $d[0] ) {
		$d = '90' . $d;
	} elseif ( 11 === strlen( $d ) && '0' === $d[0] ) {
		$d = '9' . $d;
	}
	return strlen( $d ) >= 11 ? '+' . $d : '';
}

/**
 * Değerlendirme mesajı metni.
 *
 * @param WC_Order $order Sipariş.
 * @return string
 */
function df_wa_text( $order ) {
	$name = trim( (string) $order->get_meta( '_df_sender_name' ) );
	if ( '' === $name ) {
		$name = $order->get_billing_first_name();
	}
	$link = (string) df_opt( 'review_url' );
	$text = (string) df_opt( 'wa_text', "Merhaba {ad}, {siparis} numaralı siparişiniz {alici} adlı alıcıya teslim edildi.\nAldığınız hizmeti değerlendirir misiniz? {link}" );
	$text = strtr(
		$text,
		array(
			'{ad}'      => $name,
			'{siparis}' => '#' . $order->get_order_number(),
			'{alici}'   => (string) $order->get_meta( '_df_recipient_name' ),
			'{link}'    => $link,
		)
	);
	return trim( preg_replace( '/[ \t]+/', ' ', $text ) );
}

/**
 * WasenderAPI ile mesaj gönder.
 *
 * @param string $to   +905xxxxxxxxx.
 * @param string $text Mesaj.
 * @return true|WP_Error
 */
function df_wa_send( $to, $text ) {
	$key = trim( (string) df_opt( 'wa_key' ) );
	if ( '' === $key || '' === $to ) {
		return new WP_Error( 'df_wa', 'API anahtarı ya da telefon yok.' );
	}
	$res = wp_remote_post(
		'https://www.wasenderapi.com/api/send-message',
		array(
			'timeout' => 20,
			'headers' => array(
				'Authorization' => 'Bearer ' . $key,
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode(
				array(
					'to'   => $to,
					'text' => $text,
				)
			),
		)
	);
	if ( is_wp_error( $res ) ) {
		return $res;
	}
	$code = (int) wp_remote_retrieve_response_code( $res );
	$body = json_decode( wp_remote_retrieve_body( $res ), true );
	if ( $code >= 200 && $code < 300 && ( ! is_array( $body ) || ! isset( $body['success'] ) || $body['success'] ) ) {
		return true;
	}
	return new WP_Error( 'df_wa', 'WhatsApp gönderilemedi (HTTP ' . $code . ( isset( $body['message'] ) ? ': ' . sanitize_text_field( (string) $body['message'] ) : '' ) . ').' );
}

/**
 * Teslim edilince değerlendirme mesajını sıraya al (kuryenin ekranı beklemesin).
 *
 * @param int $order_id Sipariş.
 */
function df_wa_on_completed( $order_id ) {
	if ( df_opt( 'wa_on', 0 ) && df_opt( 'wa_key' ) ) {
		wp_schedule_single_event( time(), 'df_wa_review', array( (int) $order_id ) );
	}
}
add_action( 'woocommerce_order_status_completed', 'df_wa_on_completed', 30 );

/**
 * Değerlendirme mesajını gönder (bir sipariş için bir kez).
 *
 * @param int  $order_id Sipariş.
 * @param bool $force    Daha önce gönderildiyse de gönder.
 * @return true|WP_Error
 */
function df_wa_review( $order_id, $force = false ) {
	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return new WP_Error( 'df_wa', 'Sipariş yok.' );
	}
	if ( ! $force && $order->get_meta( '_df_wa_review' ) ) {
		return true;
	}
	$to  = df_wa_phone( $order );
	$res = df_wa_send( $to, df_wa_text( $order ) );
	if ( true === $res ) {
		$order->update_meta_data( '_df_wa_review', time() );
		$order->add_order_note( 'WhatsApp değerlendirme mesajı gönderildi (' . $to . ').' );
	} else {
		$order->add_order_note( 'WhatsApp değerlendirme mesajı gönderilemedi: ' . $res->get_error_message() );
	}
	$order->save();
	return $res;
}
add_action( 'df_wa_review', 'df_wa_review' );

/**
 * Elle "WhatsApp'tan gönder" (sipariş ekranı).
 */
function df_wa_review_manual() {
	$id = isset( $_GET['order'] ) ? absint( $_GET['order'] ) : 0;
	if ( ! current_user_can( 'edit_shop_orders' ) || ! check_admin_referer( 'df_wa_review_' . $id ) ) {
		wp_die( 'Yetkiniz yok.' );
	}
	df_wa_review( $id, true );
	wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url() );
	exit;
}
add_action( 'admin_post_df_wa_review', 'df_wa_review_manual' );
