<?php
/**
 * Netgsm SMS: sipariş durumlarında müşteriye, yeni siparişte yöneticiye SMS.
 * Ayarlar: Tema Ayarları → Teslimat & Sipariş → Netgsm SMS. Gönderilen her SMS siparişe not olarak düşer.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Numarayı Netgsm biçimine getir (5XXXXXXXXX).
 *
 * @param string $phone Telefon.
 * @return string
 */
function df_sms_number( $phone ) {
	$d = preg_replace( '/\D/', '', (string) $phone );
	if ( 12 === strlen( $d ) && 0 === strpos( $d, '90' ) ) {
		$d = substr( $d, 2 );
	} elseif ( 11 === strlen( $d ) && '0' === $d[0] ) {
		$d = substr( $d, 1 );
	}
	return ( 10 === strlen( $d ) && '5' === $d[0] ) ? $d : '';
}

/**
 * SMS gönder.
 *
 * @param string|string[] $to   Numara(lar).
 * @param string          $text Mesaj.
 * @return true|WP_Error
 */
function df_sms_send( $to, $text ) {
	$user = trim( (string) df_opt( 'sms_user', '' ) );
	$pass = (string) df_opt( 'sms_pass', '' );
	$head = trim( (string) df_opt( 'sms_header', '' ) );
	if ( '' === $user || '' === $pass || '' === $head ) {
		return new WP_Error( 'df_sms', 'Netgsm kullanıcı adı, şifre veya SMS başlığı girilmemiş.' );
	}
	$nums = array_values( array_filter( array_map( 'df_sms_number', (array) $to ) ) );
	if ( ! $nums ) {
		return new WP_Error( 'df_sms', 'Geçerli cep telefonu numarası yok.' );
	}
	$res = wp_remote_get(
		add_query_arg(
			array_map(
				'rawurlencode',
				array(
					'usercode'  => $user,
					'password'  => $pass,
					'gsmno'     => implode( ',', $nums ),
					'message'   => wp_strip_all_tags( $text ),
					'msgheader' => $head,
					'dil'       => 'TR',
				)
			),
			'https://api.netgsm.com.tr/sms/send/get/'
		),
		array( 'timeout' => 20 )
	);
	if ( is_wp_error( $res ) ) {
		return $res;
	}
	$body = trim( (string) wp_remote_retrieve_body( $res ) );
	$code = strtok( $body, ' ' );
	if ( in_array( $code, array( '00', '01', '02' ), true ) ) {
		return true;
	}
	$errors = array(
		'20' => 'Mesaj metni hatalı ya da çok uzun.',
		'30' => 'Kullanıcı adı / şifre hatalı ya da API erişim izni yok (Netgsm panelinde API kullanıcısı ve IP izni kontrol edin).',
		'40' => 'SMS başlığı (gönderici adı) Netgsm\'de tanımlı değil.',
		'50' => 'Abonelik hesabı İYS kontrollü gönderim yapamıyor.',
		'51' => 'İYS marka bilgisi yok.',
		'70' => 'Hatalı istek: parametreler eksik.',
		'80' => 'Gönderim sınırı aşıldı.',
		'85' => 'Aynı numaraya kısa sürede çok fazla gönderim.',
	);
	return new WP_Error( 'df_sms', 'Netgsm: ' . ( $errors[ $code ] ?? ( '' !== $body ? $body : 'yanıt yok' ) ) );
}

/**
 * Mesaj şablonundaki alanları doldur.
 *
 * @param string   $tpl   Şablon.
 * @param WC_Order $order Sipariş.
 * @return string
 */
function df_sms_fill( $tpl, $order ) {
	$name = trim( (string) $order->get_meta( '_df_sender_name' ) );
	$name = '' !== $name ? $name : trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
	$date = (string) $order->get_meta( '_df_delivery_date' );
	$map  = array(
		'{ad}'      => $name,
		'{siparis}' => $order->get_order_number(),
		'{tutar}'   => wp_strip_all_tags( html_entity_decode( wc_price( $order->get_total(), array( 'currency' => $order->get_currency() ) ), ENT_QUOTES, 'UTF-8' ) ),
		'{alici}'   => (string) $order->get_meta( '_df_recipient_name' ),
		'{tarih}'   => $date ? wp_date( 'j F', strtotime( $date . ' 12:00:00' ) ) : '',
		'{saat}'    => (string) $order->get_meta( '_df_delivery_slot' ),
		'{takip}'   => function_exists( 'df_page_url' ) ? df_page_url( 'track_page', 'siparis-takip' ) : home_url( '/siparis-takip/' ),
		'{site}'    => get_bloginfo( 'name' ),
	);
	return trim( preg_replace( '/[ \t]+/', ' ', strtr( (string) $tpl, $map ) ) );
}

/**
 * Durum → (ayar anahtarı, varsayılan mesaj).
 *
 * @return array<string,array{0:string,1:string}>
 */
function df_sms_events() {
	return array(
		'processing'    => array( 'sms_t_new', 'Sayın {ad}, {siparis} numaralı siparişiniz alındı. Teslimat: {tarih} {saat}. Sipariş takibi: {takip} - {site}' ),
		'on-hold'       => array( 'sms_t_hold', 'Sayın {ad}, {siparis} numaralı siparişiniz alındı. Havale/EFT ödemeniz hesabımıza geçince hazırlanmaya başlanacaktır. - {site}' ),
		'df-preparing'  => array( 'sms_t_prep', 'Sayın {ad}, {siparis} numaralı siparişiniz atölyemizde hazırlanıyor. - {site}' ),
		'df-on-the-way' => array( 'sms_t_way', 'Sayın {ad}, {siparis} numaralı siparişiniz kuryemizle yola çıktı. - {site}' ),
		'completed'     => array( 'sms_t_done', 'Sayın {ad}, {siparis} numaralı siparişiniz {alici} adlı alıcıya teslim edildi. Bizi tercih ettiğiniz için teşekkürler. - {site}' ),
		'cancelled'     => array( 'sms_t_cancel', 'Sayın {ad}, {siparis} numaralı siparişiniz iptal edilmiştir. Bilgi için bize ulaşabilirsiniz. - {site}' ),
	);
}

/**
 * Durum değişince müşteriye SMS (her durum için bir kez).
 *
 * @param int      $order_id Sipariş.
 * @param string   $from     Eski durum.
 * @param string   $to       Yeni durum.
 * @param WC_Order $order    Sipariş.
 */
function df_sms_on_status( $order_id, $from, $to, $order ) {
	if ( ! df_opt( 'sms_on', 0 ) || ! $order instanceof WC_Order ) {
		return;
	}
	$ev = df_sms_events();
	if ( ! isset( $ev[ $to ] ) || ! df_opt( $ev[ $to ][0] . '_on', 'cancelled' === $to ? 0 : 1 ) ) {
		return;
	}
	$sent = (array) $order->get_meta( '_df_sms_sent' );
	if ( in_array( $to, $sent, true ) ) {
		return;
	}
	$phone = (string) $order->get_meta( '_df_sender_phone' );
	$phone = df_sms_number( $phone ) ? $phone : $order->get_billing_phone();
	$text  = df_sms_fill( df_opt( $ev[ $to ][0], $ev[ $to ][1] ), $order );
	$r     = df_sms_send( $phone, $text );
	if ( true === $r ) {
		$sent[] = $to;
		$order->update_meta_data( '_df_sms_sent', $sent );
		$order->save_meta_data();
		$order->add_order_note( 'SMS gönderildi (' . df_sms_number( $phone ) . '): ' . $text );
	} else {
		$order->add_order_note( 'SMS gönderilemedi: ' . $r->get_error_message() );
	}
}
add_action( 'woocommerce_order_status_changed', 'df_sms_on_status', 25, 4 );

/**
 * Yeni siparişte yöneticiye SMS.
 *
 * @param int $order_id Sipariş.
 */
function df_sms_admin_new( $order_id ) {
	$to = (string) df_opt( 'sms_admin_phones', '' );
	if ( ! df_opt( 'sms_on', 0 ) || ! df_opt( 'sms_admin_on', 1 ) || '' === trim( $to ) ) {
		return;
	}
	$order = wc_get_order( $order_id );
	if ( ! $order || $order->get_meta( '_df_sms_admin' ) ) {
		return;
	}
	$text = df_sms_fill( df_opt( 'sms_t_admin', 'Yeni sipariş {siparis}: {tutar}, teslimat {tarih} {saat}, alıcı {alici}.' ), $order );
	$r    = df_sms_send( preg_split( '/[\s,;]+/', $to ), $text );
	$order->update_meta_data( '_df_sms_admin', 1 );
	$order->save_meta_data();
	if ( is_wp_error( $r ) ) {
		$order->add_order_note( 'Yönetici SMS gönderilemedi: ' . $r->get_error_message() );
	}
}
add_action( 'woocommerce_checkout_order_processed', 'df_sms_admin_new', 60 );
add_action( 'woocommerce_order_status_processing', 'df_sms_admin_new', 60 );
add_action( 'woocommerce_order_status_on-hold', 'df_sms_admin_new', 60 );

/**
 * Deneme SMS'i (Tema Ayarları'ndaki bağlantıdan).
 */
function df_sms_test() {
	if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'df_sms_test' ) ) {
		wp_die( 'Yetkiniz yok.' );
	}
	$to = preg_split( '/[\s,;]+/', (string) df_opt( 'sms_admin_phones', '' ) );
	$r  = df_sms_send( $to, 'Derin Flowers Netgsm deneme mesajı. SMS bağlantısı çalışıyor.' );
	wp_die(
		true === $r ? '<h2>✓ Deneme SMS\'i gönderildi.</h2><p>Yönetici telefon numaralarına mesaj gitmiş olmalı.</p>' : '<h2>SMS gönderilemedi</h2><p>' . esc_html( $r->get_error_message() ) . '</p>',
		'Netgsm deneme',
		array( 'back_link' => true )
	);
}
add_action( 'admin_post_df_sms_test', 'df_sms_test' );
