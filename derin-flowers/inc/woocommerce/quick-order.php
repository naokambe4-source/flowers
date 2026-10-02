<?php
/**
 * Ürün sayfasında hızlı sipariş: teslimat, alıcı, gönderici ve kart notu ürün
 * görselinin yanında alınır; "Hemen Satın Al" doğrudan ödeme adımına geçer.
 * Ödeme sayfasında bu bilgiler özet olarak görünür, sadece ödeme yapılır.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Hızlı sipariş açık mı?
 *
 * @return bool
 */
function df_quick_order_on() {
	return df_checkout_enabled() && (bool) df_opt( 'quick_order', 1 );
}

/**
 * Bu ürün hızlı siparişe uygun mu?
 *
 * @param WC_Product|null $product Ürün.
 * @return bool
 */
function df_quick_product_ok( $product ) {
	return $product instanceof WC_Product && $product->is_purchasable() && $product->is_in_stock() && ( $product->is_type( 'simple' ) || $product->is_type( 'variable' ) );
}

/**
 * Ürün formunun içinde alanlar (sepete ekle düğmesinden önce).
 */
function df_quick_fields() {
	global $product;
	if ( ! df_quick_order_on() || ! df_quick_product_ok( $product ) ) {
		return;
	}
	echo '<div class="df-quick" data-df-quick>';
	echo '<input type="hidden" name="df_quick" value="1">';
	echo '<p class="df-quick__intro">' . df_icon( 'sparkle', array( 'size' => 16 ) ) . '<span>' . esc_html( df_opt( 'quick_intro', 'Teslimat bilgilerini burada doldurun, tek adımda ödemeye geçin.' ) ) . '</span></p>';
	if ( function_exists( 'df_gift_step' ) ) {
		df_gift_step();
	}
	get_template_part( 'template-parts/checkout/delivery-fields', null, array( 'context' => 'product' ) );
	echo '</div>';
}
add_action( 'woocommerce_before_add_to_cart_button', 'df_quick_fields', 5 );

/**
 * Hızlı siparişte düğmeler: ana düğme "Hemen Satın Al", yanında küçük "Sepete ekle".
 *
 * @param string $text Metin.
 * @return string
 */
function df_quick_cart_text( $text ) {
	global $product;
	return ( df_quick_order_on() && df_quick_product_ok( $product ) ) ? 'Sepete Ekle' : $text;
}
add_filter( 'woocommerce_product_single_add_to_cart_text', 'df_quick_cart_text', 20 );

/**
 * Body sınıfı.
 *
 * @param array $c Sınıflar.
 * @return array
 */
function df_quick_body_class( $c ) {
	if ( is_product() && df_quick_order_on() ) {
		$c[] = 'df-has-quick';
	}
	return $c;
}
add_filter( 'body_class', 'df_quick_body_class' );

/**
 * Sepete eklerken: alanları oturuma yaz, "Hemen Satın Al" ise doğrula.
 *
 * @param bool $passed Geçti mi.
 * @return bool
 */
function df_quick_validate( $passed ) {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- WooCommerce sepete ekleme akışı.
	if ( ! $passed || empty( $_POST['df_quick'] ) || ! df_quick_order_on() || ! WC()->session ) {
		return $passed;
	}
	if ( ! WC()->session->has_session() ) {
		WC()->session->set_customer_session_cookie( true );
	}
	$p          = df_checkout_posted();
	$p['email'] = isset( $_POST['billing_email'] ) ? sanitize_email( wp_unslash( $_POST['billing_email'] ) ) : '';
	$buy_now    = ! empty( $_POST['df_buy_now'] );
	// phpcs:enable
	// Sayfa yeniden açılsa da girilenler kaybolmasın; ödeme sayfası da bunlarla dolar.
	WC()->session->set( 'df_checkout_form', $p );
	WC()->session->set(
		'df_delivery',
		array(
			'type'     => $p['type'],
			'district' => $p['district'],
			'slot'     => $p['slot'],
		)
	);
	if ( ! $buy_now ) {
		WC()->session->set( 'df_quick_ready', null );
		return $passed;
	}
	$errors = new WP_Error();
	df_validate_delivery( $p, $errors );
	if ( ! is_email( $p['email'] ) ) {
		$errors->add( 'billing_email', 'Lütfen geçerli bir <strong>e-posta adresi</strong> girin.', array( 'id' => 'billing_email' ) );
	}
	if ( $errors->has_errors() ) {
		foreach ( $errors->get_error_codes() as $code ) {
			$data = $errors->get_error_data( $code );
			foreach ( $errors->get_error_messages( $code ) as $msg ) {
				wc_add_notice( $msg, 'error', is_array( $data ) ? $data : array() );
			}
		}
		WC()->session->set( 'df_quick_ready', null );
		return false;
	}
	WC()->session->set( 'df_quick_ready', 1 );
	// Tek teslimat = tek sipariş: "Hemen Satın Al" sepeti bu ürünle başlatır.
	if ( df_opt( 'quick_single', 1 ) && WC()->cart ) {
		WC()->cart->empty_cart();
	}
	return true;
}
add_filter( 'woocommerce_add_to_cart_validation', 'df_quick_validate', 5 );

/**
 * Ödeme sayfasında e-posta alanını hızlı siparişten doldur.
 *
 * @param mixed  $value Değer.
 * @param string $input Alan.
 * @return mixed
 */
function df_quick_checkout_value( $value, $input ) {
	if ( 'billing_email' === $input && ! $value && WC()->session ) {
		$f = (array) WC()->session->get( 'df_checkout_form', array() );
		if ( ! empty( $f['email'] ) ) {
			return $f['email'];
		}
	}
	return $value;
}
add_filter( 'woocommerce_checkout_get_value', 'df_quick_checkout_value', 10, 2 );

/**
 * Ödeme sayfası hızlı modda mı (bilgiler ürün sayfasında alındı)?
 *
 * @return bool
 */
function df_quick_checkout_ready() {
	return df_quick_order_on() && WC()->session && WC()->session->get( 'df_quick_ready' ) && WC()->cart && ! WC()->cart->is_empty();
}

/**
 * Ödeme sayfasında teslimat özeti satırları.
 *
 * @return array<string,string>
 */
function df_quick_summary_rows() {
	$p = (array) WC()->session->get( 'df_checkout_form', array() );
	if ( ! $p ) {
		return array();
	}
	$slots     = df_delivery_slots();
	$districts = df_delivery_districts();
	$stores    = df_delivery_stores();
	$rows      = array();
	if ( ! empty( $p['date'] ) ) {
		$rows['Teslimat'] = wp_date( 'j F Y, l', strtotime( $p['date'] . ' 12:00:00' ) ) . ( isset( $slots[ $p['slot'] ] ) ? ' · ' . $slots[ $p['slot'] ]['label'] : '' );
	}
	if ( 'pickup' === $p['type'] ) {
		$rows['Mağazadan teslim'] = ( '' !== $p['store'] && isset( $stores[ $p['store'] ] ) ) ? $stores[ $p['store'] ]['name'] : 'Mağaza';
	} else {
		$rows['Adres'] = trim( $p['address'] . ( isset( $districts[ $p['district'] ] ) ? ' — ' . $districts[ $p['district'] ]['name'] . ', ' . $districts[ $p['district'] ]['city'] : '' ) );
	}
	$rows['Alıcı']     = trim( $p['recipient_name'] . ' · ' . $p['recipient_cc'] . ' ' . $p['recipient_phone'] );
	$rows['Gönderici'] = trim( $p['sender_name'] . ' · ' . $p['sender_cc'] . ' ' . $p['sender_phone'] );
	if ( ! empty( $p['email'] ) ) {
		$rows['E-posta'] = $p['email'];
	}
	$rows['Kart notu'] = ! empty( $p['no_note'] ) ? 'Not kartı istenmedi' : ( $p['note'] ? $p['note'] . ( $p['anon'] ? ' — İsimsiz' : ( $p['note_from'] ? ' — ' . $p['note_from'] : '' ) ) : '—' );
	return $rows;
}

/**
 * Ödeme sayfasında özet kartı.
 */
function df_quick_checkout_summary() {
	if ( ! df_quick_checkout_ready() ) {
		return;
	}
	$rows = df_quick_summary_rows();
	if ( ! $rows ) {
		return;
	}
	echo '<section class="df-step df-quick-sum" id="df-quick-sum">';
	echo '<div class="df-quick-sum__head"><h2 class="df-step__title"><span class="df-step__num">' . df_icon( 'check', array( 'size' => 16 ) ) . '</span>Teslimat bilgileriniz hazır</h2>'; // phpcs:ignore
	echo '<button type="button" class="df-link" data-df-quick-edit>Düzenle</button></div>';
	echo '<dl class="df-quick-sum__list">';
	foreach ( $rows as $k => $v ) {
		echo '<div><dt>' . esc_html( $k ) . '</dt><dd>' . esc_html( $v ) . '</dd></div>';
	}
	echo '</dl></section>';
}
add_action( 'woocommerce_checkout_before_customer_details', 'df_quick_checkout_summary', 5 );

/**
 * Ödeme formuna hızlı mod sınıfı.
 *
 * @param array $c Sınıflar.
 * @return array
 */
function df_quick_checkout_body_class( $c ) {
	if ( function_exists( 'is_checkout' ) && is_checkout() && ! is_order_received_page() && df_quick_checkout_ready() ) {
		$c[] = 'df-checkout-quick';
	}
	return $c;
}
add_filter( 'body_class', 'df_quick_checkout_body_class' );

/**
 * "Hemen Satın Al" sonrası ödeme sayfasında "sepete eklendi" uyarısı gösterilmez.
 *
 * @param string $message Mesaj.
 * @return string
 */
function df_quick_hide_added_msg( $message ) {
	return ! empty( $_REQUEST['df_buy_now'] ) ? '' : $message; // phpcs:ignore WordPress.Security.NonceVerification
}
add_filter( 'wc_add_to_cart_message_html', 'df_quick_hide_added_msg', 99 );
