<?php
/**
 * Çiçekçi ödeme akışı: teslimat türü, takvim, saat aralığı, gönderici/alıcı, bölge ücreti, çiçek notu.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Akış açık mı?
 *
 * @return bool
 */
function df_checkout_enabled() {
	return (bool) df_opt( 'df_checkout_on', 1 );
}

/**
 * Ülke kodları (telefon).
 *
 * @return array<string,string>
 */
function df_phone_codes() {
	return apply_filters(
		'df_phone_codes',
		array(
			'+90'  => 'TR +90',
			'+49'  => 'DE +49',
			'+44'  => 'UK +44',
			'+31'  => 'NL +31',
			'+33'  => 'FR +33',
			'+1'   => 'US +1',
			'+994' => 'AZ +994',
			'+7'   => 'RU +7',
			'+971' => 'AE +971',
		)
	);
}

/**
 * Not kategorileri ve hazır mesajlar.
 *
 * @return array<string, string[]>
 */
function df_note_templates() {
	$out = array();
	foreach ( df_lines( df_opt( 'note_templates' ), true ) as $row ) {
		if ( count( $row ) < 2 ) {
			continue;
		}
		$out[ $row[0] ][] = $row[1];
	}
	return $out;
}

/* -------------------------------------------------------------------------
 * Alanlar
 * ---------------------------------------------------------------------- */

/**
 * Fatura/teslimat alanlarını sadeleştir: gönderici ve alıcı tema alanlarından gelir.
 *
 * @param array $fields Alanlar.
 * @return array
 */
function df_checkout_fields( $fields ) {
	if ( ! df_checkout_enabled() ) {
		return $fields;
	}
	$keep_billing = array( 'billing_email' );
	foreach ( array_keys( $fields['billing'] ) as $key ) {
		if ( ! in_array( $key, $keep_billing, true ) && 0 === strpos( $key, 'billing_' ) && in_array( $key, df_core_billing_keys(), true ) ) {
			unset( $fields['billing'][ $key ] );
		}
	}
	if ( isset( $fields['billing']['billing_email'] ) ) {
		$fields['billing']['billing_email']['label']       = 'E-posta adresiniz';
		$fields['billing']['billing_email']['placeholder'] = 'Sipariş onayı bu adrese gönderilir';
		$fields['billing']['billing_email']['class']       = array( 'form-row-wide' );
		$fields['billing']['billing_email']['priority']    = 30;
	}
	foreach ( array_keys( $fields['shipping'] ) as $key ) {
		if ( in_array( $key, df_core_shipping_keys(), true ) ) {
			unset( $fields['shipping'][ $key ] );
		}
	}
	if ( isset( $fields['order']['order_comments'] ) ) {
		$fields['order']['order_comments']['label']       = 'Kurye için not';
		$fields['order']['order_comments']['placeholder'] = 'Ör. Kapı kodu, zile basmayın, sürpriz — önce arayın…';
		$fields['order']['order_comments']['custom_attributes'] = array( 'rows' => 2 );
	}
	return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'df_checkout_fields', 20 );

/**
 * WooCommerce çekirdek fatura alanları.
 *
 * @return string[]
 */
function df_core_billing_keys() {
	return array( 'billing_first_name', 'billing_last_name', 'billing_company', 'billing_country', 'billing_address_1', 'billing_address_2', 'billing_city', 'billing_state', 'billing_postcode', 'billing_phone' );
}

/**
 * WooCommerce çekirdek teslimat alanları.
 *
 * @return string[]
 */
function df_core_shipping_keys() {
	return array( 'shipping_first_name', 'shipping_last_name', 'shipping_company', 'shipping_country', 'shipping_address_1', 'shipping_address_2', 'shipping_city', 'shipping_state', 'shipping_postcode', 'shipping_phone' );
}

/**
 * Bölge ücreti modunda WooCommerce kargo yöntemleri devre dışı.
 *
 * @param bool $needs Gerekli mi.
 * @return bool
 */
function df_cart_needs_shipping( $needs ) {
	if ( df_checkout_enabled() && df_opt( 'df_fee_mode', 1 ) ) {
		return false;
	}
	return $needs;
}
add_filter( 'woocommerce_cart_needs_shipping', 'df_cart_needs_shipping' );
add_filter( 'woocommerce_ship_to_different_address_checked', '__return_false' );

/**
 * Ödeme bölümü sol sütunda gösterilir (şablon içinde çağrılır).
 */
function df_checkout_move_payment() {
	if ( df_checkout_enabled() ) {
		remove_action( 'woocommerce_checkout_order_review', 'woocommerce_checkout_payment', 20 );
	}
}
add_action( 'wp', 'df_checkout_move_payment' );

/**
 * Sipariş butonu metni.
 *
 * @return string
 */
function df_order_button_text() {
	return 'Siparişi Onayla ve Öde';
}
add_filter( 'woocommerce_order_button_text', 'df_order_button_text' );

/* -------------------------------------------------------------------------
 * Değerler
 * ---------------------------------------------------------------------- */

/**
 * Gönderilen tema alanları (temizlenmiş).
 *
 * @param array|null $src Kaynak ($_POST veya ayrıştırılmış post_data).
 * @return array
 */
function df_checkout_posted( $src = null ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce ödeme nonce'u işlemden önce doğrular.
	$src = null === $src ? wp_unslash( $_POST ) : $src;
	$get = function ( $k ) use ( $src ) {
		return isset( $src[ $k ] ) && is_scalar( $src[ $k ] ) ? (string) $src[ $k ] : '';
	};
	$codes = df_phone_codes();
	$cc    = function ( $k ) use ( $get, $codes ) {
		$v = $get( $k );
		return isset( $codes[ $v ] ) ? $v : '+90';
	};
	$type = $get( 'df_type' );
	return array(
		'type'            => in_array( $type, array( 'address', 'pickup' ), true ) ? $type : 'address',
		'date'            => preg_match( '/^\d{4}-\d{2}-\d{2}$/', $get( 'df_date' ) ) ? $get( 'df_date' ) : '',
		'slot'            => sanitize_title( $get( 'df_slot' ) ),
		'sender_name'     => sanitize_text_field( $get( 'df_sender_name' ) ),
		'sender_cc'       => $cc( 'df_sender_cc' ),
		'sender_phone'    => preg_replace( '/\D/', '', $get( 'df_sender_phone' ) ),
		'recipient_name'  => sanitize_text_field( $get( 'df_recipient_name' ) ),
		'recipient_cc'    => $cc( 'df_recipient_cc' ),
		'recipient_phone' => preg_replace( '/\D/', '', $get( 'df_recipient_phone' ) ),
		'district'        => sanitize_text_field( $get( 'df_district' ) ),
		'address'         => sanitize_textarea_field( $get( 'df_address' ) ),
		'address_hint'    => sanitize_text_field( $get( 'df_address_hint' ) ),
		'store'           => $get( 'df_store' ) === '' ? '' : absint( $get( 'df_store' ) ),
		'note_cat'        => sanitize_text_field( $get( 'df_note_cat' ) ),
		'note'            => sanitize_textarea_field( $get( 'df_note' ) ),
		'note_from'       => sanitize_text_field( $get( 'df_note_from' ) ),
		'anon'            => $get( 'df_anon' ) ? 1 : 0,
		'no_note'         => $get( 'df_no_note' ) ? 1 : 0,
	);
}

/**
 * Formda gösterilecek değer (önce oturum, sonra kullanıcı bilgisi).
 *
 * @param string $key Anahtar.
 * @return string
 */
function df_checkout_value( $key ) {
	$session = WC()->session ? (array) WC()->session->get( 'df_checkout_form', array() ) : array();
	if ( isset( $session[ $key ] ) && '' !== $session[ $key ] ) {
		return (string) $session[ $key ];
	}
	if ( is_user_logged_in() ) {
		$customer = WC()->customer;
		if ( 'sender_name' === $key && $customer ) {
			return trim( $customer->get_billing_first_name() . ' ' . $customer->get_billing_last_name() );
		}
		if ( 'sender_phone' === $key && $customer ) {
			$phone = preg_replace( '/\D/', '', $customer->get_billing_phone() );
			return preg_replace( '/^(90|0)/', '', $phone );
		}
	}
	return '';
}

/* -------------------------------------------------------------------------
 * Ücret
 * ---------------------------------------------------------------------- */

/**
 * Ödeme özeti güncellenirken seçimleri oturuma yaz.
 *
 * @param string $post_data Serileştirilmiş form.
 */
function df_checkout_update_order_review( $post_data ) {
	if ( ! df_checkout_enabled() ) {
		return;
	}
	parse_str( (string) $post_data, $data );
	$posted = df_checkout_posted( is_array( $data ) ? $data : array() );
	WC()->session->set(
		'df_delivery',
		array(
			'type'     => $posted['type'],
			'district' => $posted['district'],
			'slot'     => $posted['slot'],
		)
	);
	// Sayfa yenilense de doldurulan alanlar korunsun.
	WC()->session->set( 'df_checkout_form', $posted );
}
add_action( 'woocommerce_checkout_update_order_review', 'df_checkout_update_order_review' );

/**
 * Teslimat ücretini sepete ekle.
 *
 * @param WC_Cart $cart Sepet.
 */
function df_add_delivery_fee( $cart ) {
	if ( ! df_checkout_enabled() || ! df_opt( 'df_fee_mode', 1 ) || ! WC()->session ) {
		return;
	}
	if ( ! is_checkout() && ! defined( 'WOOCOMMERCE_CHECKOUT' ) ) {
		return;
	}
	$sel = (array) WC()->session->get( 'df_delivery', array() );
	$sel = wp_parse_args(
		$sel,
		array(
			'type'     => 'address',
			'district' => '',
			'slot'     => '',
		)
	);
	$fee = df_delivery_fee( $sel['type'], $sel['district'], $sel['slot'] );
	if ( $fee <= 0 ) {
		return;
	}
	$label     = df_opt( 'df_fee_label', 'Teslimat Ücreti' );
	$districts = df_delivery_districts();
	if ( 'pickup' !== $sel['type'] && isset( $districts[ $sel['district'] ] ) ) {
		$label .= ' (' . $districts[ $sel['district'] ]['name'] . ')';
	}
	$cart->add_fee( $label, $fee, false );
}
add_action( 'woocommerce_cart_calculate_fees', 'df_add_delivery_fee' );

/**
 * Ücretsiz teslimat satırı (ödeme özetinde).
 */
function df_review_free_delivery_row() {
	if ( ! df_checkout_enabled() || ! WC()->session ) {
		return;
	}
	$sel = (array) WC()->session->get( 'df_delivery', array() );
	if ( empty( $sel['type'] ) ) {
		return;
	}
	$fee = df_delivery_fee( $sel['type'], isset( $sel['district'] ) ? $sel['district'] : '', isset( $sel['slot'] ) ? $sel['slot'] : '' );
	if ( $fee > 0 ) {
		return;
	}
	if ( 'pickup' === $sel['type'] ) {
		echo '<tr class="df-free-delivery"><th>Mağazadan teslim</th><td>Ücretsiz</td></tr>';
	} elseif ( ! empty( $sel['district'] ) ) {
		echo '<tr class="df-free-delivery"><th>' . esc_html( df_opt( 'df_fee_label', 'Teslimat Ücreti' ) ) . '</th><td>Ücretsiz</td></tr>';
	}
}
add_action( 'woocommerce_review_order_before_order_total', 'df_review_free_delivery_row' );

/* -------------------------------------------------------------------------
 * Doğrulama
 * ---------------------------------------------------------------------- */

/**
 * Sipariş gönderilirken oturumu güncelle ve toplamları yeniden hesapla.
 */
function df_checkout_process_session() {
	if ( ! df_checkout_enabled() ) {
		return;
	}
	$p = df_checkout_posted();
	WC()->session->set(
		'df_delivery',
		array(
			'type'     => $p['type'],
			'district' => $p['district'],
			'slot'     => $p['slot'],
		)
	);
	WC()->cart->calculate_totals();
}
add_action( 'woocommerce_checkout_process', 'df_checkout_process_session' );

/**
 * Telefon geçerli mi?
 *
 * @param string $cc     Ülke kodu.
 * @param string $digits Rakamlar.
 * @return bool
 */
function df_valid_phone( $cc, $digits ) {
	if ( '+90' === $cc ) {
		$digits = preg_replace( '/^0/', '', $digits );
		return (bool) preg_match( '/^[2-5]\d{9}$/', $digits );
	}
	return strlen( $digits ) >= 6 && strlen( $digits ) <= 14;
}

/**
 * Alan doğrulama.
 *
 * @param array    $data   WC verisi.
 * @param WP_Error $errors Hatalar.
 */
function df_checkout_validate( $data, $errors ) {
	if ( ! df_checkout_enabled() ) {
		return;
	}
	$p = df_checkout_posted();

	if ( mb_strlen( $p['sender_name'] ) < 3 ) {
		$errors->add( 'df_sender_name', '<strong>Gönderici adı soyadı</strong> zorunludur.', array( 'id' => 'df_sender_name' ) );
	}
	if ( ! df_valid_phone( $p['sender_cc'], $p['sender_phone'] ) ) {
		$errors->add( 'df_sender_phone', 'Lütfen geçerli bir <strong>gönderici telefon numarası</strong> girin.', array( 'id' => 'df_sender_phone' ) );
	}
	if ( mb_strlen( $p['recipient_name'] ) < 3 ) {
		$errors->add( 'df_recipient_name', '<strong>Alıcı adı soyadı</strong> zorunludur.', array( 'id' => 'df_recipient_name' ) );
	}
	if ( ! df_valid_phone( $p['recipient_cc'], $p['recipient_phone'] ) ) {
		$errors->add( 'df_recipient_phone', 'Lütfen geçerli bir <strong>alıcı telefon numarası</strong> girin.', array( 'id' => 'df_recipient_phone' ) );
	}

	if ( 'pickup' === $p['type'] ) {
		if ( ! df_opt( 'df_pickup_on' ) ) {
			$errors->add( 'df_type', 'Mağazadan teslim şu anda kullanılamıyor.' );
		}
		$stores = df_delivery_stores();
		if ( $stores && ( '' === $p['store'] || ! isset( $stores[ $p['store'] ] ) ) ) {
			$errors->add( 'df_store', 'Lütfen <strong>teslim alınacak mağazayı</strong> seçin.', array( 'id' => 'df_store' ) );
		}
	} else {
		$districts = df_delivery_districts();
		if ( ! isset( $districts[ $p['district'] ] ) ) {
			$errors->add( 'df_district', 'Lütfen <strong>teslimat bölgesini</strong> seçin.', array( 'id' => 'df_district' ) );
		}
		if ( mb_strlen( $p['address'] ) < 10 ) {
			$errors->add( 'df_address', 'Lütfen <strong>alıcı adresini</strong> eksiksiz yazın (mahalle, sokak, bina ve daire no).', array( 'id' => 'df_address' ) );
		}
	}

	if ( ! $p['date'] ) {
		$errors->add( 'df_date', 'Lütfen takvimden <strong>teslimat tarihini</strong> seçin.', array( 'id' => 'df_date' ) );
	} else {
		$slots = df_delivery_available_slots( $p['date'] );
		if ( ! $slots ) {
			$errors->add( 'df_date', 'Seçtiğiniz tarihte teslimat yapılamıyor, lütfen başka bir gün seçin.', array( 'id' => 'df_date' ) );
		} elseif ( ! isset( $slots[ $p['slot'] ] ) ) {
			$errors->add( 'df_slot', 'Lütfen uygun bir <strong>teslimat saat aralığı</strong> seçin.', array( 'id' => 'df_slot' ) );
		}
		$max = df_now()->setTime( 0, 0 )->modify( '+' . absint( df_opt( 'df_max_days', 60 ) ) . ' day' )->format( 'Y-m-d' );
		if ( $p['date'] < df_now()->format( 'Y-m-d' ) || $p['date'] > $max ) {
			$errors->add( 'df_date', 'Seçtiğiniz teslimat tarihi geçerli değil.', array( 'id' => 'df_date' ) );
		}
	}

	$max_note = absint( df_opt( 'note_max', 300 ) );
	if ( mb_strlen( $p['note'] ) > $max_note ) {
		$errors->add( 'df_note', sprintf( 'Çiçek notu en fazla %d karakter olabilir.', $max_note ), array( 'id' => 'df_note' ) );
	}
}
add_action( 'woocommerce_after_checkout_validation', 'df_checkout_validate', 10, 2 );

/**
 * Tema alanlarından WooCommerce fatura/teslimat verilerini doldur (ödeme altyapıları için).
 *
 * @param array $data Veri.
 * @return array
 */
function df_checkout_posted_data( $data ) {
	if ( ! df_checkout_enabled() ) {
		return $data;
	}
	$p        = df_checkout_posted();
	$split    = function ( $full ) {
		$parts = preg_split( '/\s+/', trim( $full ) );
		$last  = count( $parts ) > 1 ? array_pop( $parts ) : '';
		return array( implode( ' ', $parts ), $last );
	};
	$sender   = $split( $p['sender_name'] );
	$recip    = $split( $p['recipient_name'] );
	$district = df_delivery_districts();
	$d        = isset( $district[ $p['district'] ] ) ? $district[ $p['district'] ] : null;
	$stores   = df_delivery_stores();
	$store    = ( '' !== $p['store'] && isset( $stores[ $p['store'] ] ) ) ? $stores[ $p['store'] ] : null;
	$city     = $d ? $d['city'] : df_opt( 'df_default_city', 'İzmir' );
	$address  = 'pickup' === $p['type'] ? ( $store ? $store['name'] . ' — ' . $store['address'] : 'Mağazadan teslim' ) : $p['address'];
	$state    = df_tr_state_code( $city );

	$data['billing_first_name'] = $sender[0];
	$data['billing_last_name']  = $sender[1];
	$data['billing_phone']      = $p['sender_cc'] . $p['sender_phone'];
	$data['billing_country']    = 'TR';
	$data['billing_state']      = $state;
	$data['billing_city']       = $city;
	$data['billing_address_1']  = mb_substr( str_replace( array( "\r", "\n" ), ' ', $address ), 0, 200 );
	$data['billing_postcode']   = isset( $data['billing_postcode'] ) ? $data['billing_postcode'] : '';

	$data['shipping_first_name'] = $recip[0];
	$data['shipping_last_name']  = $recip[1];
	$data['shipping_phone']      = $p['recipient_cc'] . $p['recipient_phone'];
	$data['shipping_country']    = 'TR';
	$data['shipping_state']      = $state;
	$data['shipping_city']       = 'pickup' === $p['type'] ? $city : ( $d ? $d['name'] . ', ' . $d['city'] : $city );
	$data['shipping_address_1']  = mb_substr( str_replace( array( "\r", "\n" ), ' ', $address ), 0, 200 );
	$data['shipping_address_2']  = $p['address_hint'];
	return $data;
}
add_filter( 'woocommerce_checkout_posted_data', 'df_checkout_posted_data' );

/**
 * Şehir adından WooCommerce il kodu (TR35 = İzmir).
 *
 * @param string $city Şehir.
 * @return string
 */
function df_tr_state_code( $city ) {
	$states = WC()->countries ? WC()->countries->get_states( 'TR' ) : array();
	$needle = mb_strtolower( trim( $city ) );
	foreach ( (array) $states as $code => $name ) {
		if ( mb_strtolower( $name ) === $needle ) {
			return $code;
		}
	}
	return '';
}

/* -------------------------------------------------------------------------
 * Kayıt
 * ---------------------------------------------------------------------- */

/**
 * Siparişe teslimat bilgilerini kaydet.
 *
 * @param WC_Order $order Sipariş.
 */
function df_checkout_save_order( $order ) {
	if ( ! df_checkout_enabled() ) {
		return;
	}
	$p         = df_checkout_posted();
	$districts = df_delivery_districts();
	$slots     = df_delivery_slots();
	$stores    = df_delivery_stores();
	$meta      = array(
		'_df_delivery_type'  => $p['type'],
		'_df_delivery_date'  => $p['date'],
		'_df_delivery_slot'  => isset( $slots[ $p['slot'] ] ) ? $slots[ $p['slot'] ]['label'] : '',
		'_df_sender_name'    => $p['sender_name'],
		'_df_sender_phone'   => $p['sender_cc'] . ' ' . $p['sender_phone'],
		'_df_recipient_name' => $p['recipient_name'],
		'_df_recipient_phone'=> $p['recipient_cc'] . ' ' . $p['recipient_phone'],
		'_df_district'       => 'pickup' === $p['type'] ? '' : ( isset( $districts[ $p['district'] ] ) ? $districts[ $p['district'] ]['key'] : '' ),
		'_df_address'        => 'pickup' === $p['type'] ? '' : $p['address'],
		'_df_address_hint'   => 'pickup' === $p['type'] ? '' : $p['address_hint'],
		'_df_store'          => ( 'pickup' === $p['type'] && '' !== $p['store'] && isset( $stores[ $p['store'] ] ) ) ? $stores[ $p['store'] ]['name'] . ' — ' . $stores[ $p['store'] ]['address'] : '',
		'_df_note_cat'       => $p['note_cat'],
		'_df_note'           => $p['no_note'] ? '' : $p['note'],
		'_df_note_from'      => $p['anon'] ? '' : $p['note_from'],
		'_df_note_anon'      => $p['anon'] ? 'yes' : '',
	);
	foreach ( $meta as $key => $value ) {
		$order->update_meta_data( $key, $value );
	}
	if ( WC()->session ) {
		WC()->session->set( 'df_checkout_form', null );
	}
}
add_action( 'woocommerce_checkout_create_order', 'df_checkout_save_order' );

/**
 * Sipariş teslimat bilgileri (görüntüleme için).
 *
 * @param WC_Order $order Sipariş.
 * @return array<string,string> etiket => değer
 */
function df_order_delivery_rows( $order ) {
	if ( ! $order->get_meta( '_df_delivery_type' ) ) {
		return array();
	}
	$pickup = 'pickup' === $order->get_meta( '_df_delivery_type' );
	$date   = $order->get_meta( '_df_delivery_date' );
	$rows   = array(
		'Teslimat türü'  => $pickup ? 'Mağazadan teslim' : 'Adrese teslim',
		'Teslimat tarihi'=> $date ? wp_date( 'j F Y, l', strtotime( $date . ' 12:00:00' ) ) : '',
		'Saat aralığı'   => $order->get_meta( '_df_delivery_slot' ),
		'Gönderici'      => trim( $order->get_meta( '_df_sender_name' ) . ' · ' . $order->get_meta( '_df_sender_phone' ), ' ·' ),
		'Alıcı'          => trim( $order->get_meta( '_df_recipient_name' ) . ' · ' . $order->get_meta( '_df_recipient_phone' ), ' ·' ),
	);
	if ( $pickup ) {
		$rows['Mağaza'] = $order->get_meta( '_df_store' );
	} else {
		$rows['Bölge']        = $order->get_meta( '_df_district' );
		$rows['Adres']        = $order->get_meta( '_df_address' );
		$rows['Adres tarifi'] = $order->get_meta( '_df_address_hint' );
	}
	$note = $order->get_meta( '_df_note' );
	if ( $note ) {
		$rows['Kart notu'] = $note;
		$rows['Kartta imza'] = 'yes' === $order->get_meta( '_df_note_anon' ) ? 'İsimsiz' : $order->get_meta( '_df_note_from' );
		if ( $order->get_meta( '_df_note_cat' ) ) {
			$rows['Not kategorisi'] = $order->get_meta( '_df_note_cat' );
		}
	}
	return array_filter( $rows );
}

/**
 * Teslimat bilgileri tablosu.
 *
 * @param WC_Order $order Sipariş.
 * @param string   $context front|admin|email.
 */
function df_render_order_delivery( $order, $context = 'front' ) {
	$rows = df_order_delivery_rows( $order );
	if ( ! $rows ) {
		return;
	}
	if ( 'email' === $context ) {
		echo '<h2 style="margin-top:24px">Teslimat Bilgileri</h2><table cellspacing="0" cellpadding="6" border="1" style="width:100%;border-collapse:collapse;border:1px solid #e5e5e5;margin-bottom:24px">';
		foreach ( $rows as $label => $value ) {
			echo '<tr><th style="text-align:left;width:34%;border:1px solid #e5e5e5">' . esc_html( $label ) . '</th><td style="text-align:left;border:1px solid #e5e5e5">' . nl2br( esc_html( $value ) ) . '</td></tr>';
		}
		echo '</table>';
		return;
	}
	echo '<section class="df-order-delivery' . ( 'admin' === $context ? ' df-order-delivery--admin' : '' ) . '">';
	echo 'admin' === $context ? '<h3>Teslimat Bilgileri</h3>' : '<h2 class="df-order-delivery__title">Teslimat Bilgileri</h2>';
	echo '<dl>';
	foreach ( $rows as $label => $value ) {
		echo '<div><dt>' . esc_html( $label ) . '</dt><dd>' . nl2br( esc_html( $value ) ) . '</dd></div>';
	}
	echo '</dl>';
	if ( 'admin' === $context && $order->get_meta( '_df_note' ) ) {
		$url = wp_nonce_url( admin_url( 'admin-post.php?action=df_print_card&order=' . $order->get_id() ), 'df_print_card_' . $order->get_id() );
		echo '<p><a class="button" target="_blank" href="' . esc_url( $url ) . '">Not kartını yazdır</a></p>';
	}
	echo '</section>';
}

add_action(
	'woocommerce_admin_order_data_after_shipping_address',
	function ( $order ) {
		// Ayrıntılar sayfanın üstündeki "Çiçek Siparişi" kartında; burada kısa özet.
		$date = $order->get_meta( '_df_delivery_date' );
		if ( $date ) {
			echo '<p class="df-order-delivery-mini"><strong>Teslimat:</strong> ' . esc_html( wp_date( 'j F Y, l', strtotime( $date . ' 12:00:00' ) ) . ' · ' . $order->get_meta( '_df_delivery_slot' ) ) . '<br><a href="#df-flower-order">Çiçek siparişi ayrıntıları ↑</a></p>';
		}
	}
);
add_action(
	'woocommerce_order_details_after_order_table',
	function ( $order ) {
		df_render_order_delivery( $order, 'front' );
	}
);
add_action(
	'woocommerce_email_after_order_table',
	function ( $order, $sent_to_admin, $plain_text ) {
		if ( $plain_text ) {
			foreach ( df_order_delivery_rows( $order ) as $label => $value ) {
				echo esc_html( $label ) . ': ' . esc_html( $value ) . "\n";
			}
			return;
		}
		df_render_order_delivery( $order, 'email' );
	},
	10,
	3
);

/**
 * Sipariş listesinde teslimat sütunu (klasik + HPOS).
 *
 * @param array $cols Sütunlar.
 * @return array
 */
function df_order_list_columns( $cols ) {
	$new = array();
	foreach ( $cols as $key => $label ) {
		$new[ $key ] = $label;
		if ( 'order_date' === $key ) {
			$new['df_delivery'] = 'Teslimat';
		}
	}
	return $new;
}
add_filter( 'manage_edit-shop_order_columns', 'df_order_list_columns', 20 );
add_filter( 'manage_woocommerce_page_wc-orders_columns', 'df_order_list_columns', 20 );

/**
 * Teslimat sütunu içeriği.
 *
 * @param string       $col   Sütun.
 * @param int|WC_Order $order Sipariş.
 */
function df_order_list_column_content( $col, $order ) {
	if ( 'df_delivery' !== $col ) {
		return;
	}
	$order = $order instanceof WC_Order ? $order : wc_get_order( $order );
	if ( ! $order || ! $order->get_meta( '_df_delivery_date' ) ) {
		echo '—';
		return;
	}
	$date = $order->get_meta( '_df_delivery_date' );
	$ts   = strtotime( $date . ' 12:00:00' );
	echo '<strong>' . esc_html( wp_date( 'j M, D', $ts ) ) . '</strong><br><small>' . esc_html( $order->get_meta( '_df_delivery_slot' ) ) . '</small>';
	if ( 'pickup' === $order->get_meta( '_df_delivery_type' ) ) {
		echo '<br><small>Mağazadan teslim</small>';
	} elseif ( $order->get_meta( '_df_district' ) ) {
		$parts = explode( ' — ', $order->get_meta( '_df_district' ) );
		echo '<br><small>' . esc_html( end( $parts ) ) . '</small>';
	}
}
add_action( 'manage_shop_order_posts_custom_column', 'df_order_list_column_content', 20, 2 );
add_action( 'manage_woocommerce_page_wc-orders_custom_column', 'df_order_list_column_content', 20, 2 );

/**
 * Not kartı yazdırma.
 */
function df_print_card() {
	$order_id = isset( $_GET['order'] ) ? absint( $_GET['order'] ) : 0;
	if ( ! $order_id || ! current_user_can( 'edit_shop_orders' ) || ! check_admin_referer( 'df_print_card_' . $order_id ) ) {
		wp_die( 'Yetkiniz yok.' );
	}
	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		wp_die( 'Sipariş bulunamadı.' );
	}
	$order->update_meta_data( '_df_printed', time() );
	$order->save();
	$note = $order->get_meta( '_df_note' );
	$from = 'yes' === $order->get_meta( '_df_note_anon' ) ? '' : $order->get_meta( '_df_note_from' );
	$fonts = df_fonts_url();
	?>
	<!doctype html>
	<html lang="tr"><head><meta charset="utf-8"><title>Not Kartı #<?php echo esc_html( $order->get_order_number() ); ?></title>
	<?php if ( $fonts ) : ?><link rel="stylesheet" href="<?php echo esc_url( $fonts ); ?>"><?php endif; // phpcs:ignore ?>
	<style>
		@page { size: A6 landscape; margin: 0; }
		body { margin: 0; font-family: <?php echo '"' . esc_html( df_opt( 'font_heading' ) ) . '"'; ?>, serif; color: #2B2522; }
		.card { width: 148mm; height: 105mm; box-sizing: border-box; padding: 16mm 18mm; display: flex; flex-direction: column; justify-content: center; text-align: center; }
		.note { font-size: 20pt; line-height: 1.45; font-style: italic; white-space: pre-wrap; }
		.from { margin-top: 10mm; font-size: 13pt; letter-spacing: .08em; }
		.brand { position: fixed; bottom: 8mm; left: 0; right: 0; text-align: center; font-size: 8pt; letter-spacing: .3em; color: #8a7f79; }
		@media screen { body { background: #eee; } .card { background: #fff; margin: 30px auto; box-shadow: 0 4px 20px rgba(0,0,0,.1); } .brand { position: static; margin-top: -60px; } }
	</style></head>
	<body onload="window.print()">
		<div class="card"><div class="note"><?php echo esc_html( $note ); ?></div><?php if ( $from ) : ?><div class="from">— <?php echo esc_html( $from ); ?></div><?php endif; ?></div>
		<div class="brand"><?php echo esc_html( df_opt( 'logo_text' ) ); ?></div>
	</body></html>
	<?php
	exit;
}
add_action( 'admin_post_df_print_card', 'df_print_card' );

/* -------------------------------------------------------------------------
 * Script
 * ---------------------------------------------------------------------- */

/**
 * Ödeme sayfası scriptleri.
 */
function df_checkout_assets() {
	if ( ! df_checkout_enabled() || ! is_checkout() || is_order_received_page() || is_checkout_pay_page() ) {
		return;
	}
	if ( wp_script_is( 'selectWoo', 'registered' ) ) {
		wp_enqueue_script( 'selectWoo' );
		wp_enqueue_style( 'select2' );
	}
	wp_enqueue_script( 'df-checkout', DF_URI . '/assets/js/checkout.js', array( 'jquery', 'wc-checkout' ), DF_VERSION, true );
	$districts = array();
	foreach ( df_delivery_districts() as $key => $d ) {
		$districts[ $key ] = $d['fee'];
	}
	wp_localize_script(
		'df-checkout',
		'DFCheckout',
		array_merge(
			df_delivery_calendar_data(),
			array(
				'templates' => df_note_templates(),
				'noteMax'   => absint( df_opt( 'note_max', 300 ) ),
				'districts' => $districts,
				'currency'  => html_entity_decode( get_woocommerce_currency_symbol() ),
				'i18n'      => array(
					'noSlots'   => 'Bu gün için uygun saat kalmadı.',
					'pickDate'  => 'Önce teslimat tarihini seçin.',
					'today'     => 'Bugün',
					'tomorrow'  => 'Yarın',
					'search'    => 'Bölge ara…',
					'free'      => 'Ücretsiz',
					'noResults' => 'Bölge bulunamadı — bizi arayın',
				),
			)
		)
	);
}
add_action( 'wp_enqueue_scripts', 'df_checkout_assets', 30 );
