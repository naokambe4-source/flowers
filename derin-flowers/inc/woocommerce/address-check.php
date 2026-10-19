<?php
/**
 * Teslimat adresi doğrulama: teslimat ücreti müşterinin listeden seçtiği bölgeye göre değil,
 * çiçeğin gideceği adresin bulunduğu bölgeye göre uygulanır.
 *
 * 1. Adres metnindeki semt / ilçe adları teslimat bölgesi listesiyle eşleştirilir (her zaman, ücretsiz).
 * 2. İsteğe bağlı harita doğrulaması: Google Geocoding (anahtar girilirse) ya da OpenStreetMap (ücretsiz)
 *    adresi ilçe / mahalleye çevirir.
 * 3. Adres daha pahalı bir bölgedeyse sipariş düşük ücretle tamamlanmaz; müşteriye doğru bölge ve ücret
 *    gösterilir, tek tıkla düzeltir.
 * 4. Adres doğrulanamazsa sipariş "yönetici incelemesi" işaretiyle alınır (isteğe bağlı: Beklemede durumu).
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Karşılaştırma için sadeleştir (ascii, küçük harf, "mahallesi" vb. atılır).
 *
 * @param string $s Metin.
 * @return string
 */
function df_addr_norm( $s ) {
	$s = function_exists( 'df_loc_ascii' ) ? df_loc_ascii( $s ) : strtolower( remove_accents( $s ) );
	$s = preg_replace( '/\b(mahallesi|mahalle|mah|mh|ilcesi|ilce|merkez|koyu|beldesi|izmir|turkiye|turkey)\b\.?/u', ' ', $s );
	return trim( preg_replace( '/[^a-z0-9]+/', ' ', $s ) );
}

/**
 * Metinde geçen teslimat bölgeleri (kelime sınırıyla).
 *
 * @param string $text Metin.
 * @return array<string,array> bölge anahtarı => bölge
 */
function df_addr_zones_in( $text ) {
	$hay = ' ' . df_addr_norm( $text ) . ' ';
	$out = array();
	if ( '  ' === $hay ) {
		return $out;
	}
	foreach ( df_delivery_districts() as $key => $d ) {
		$n = df_addr_norm( $d['name'] );
		if ( strlen( $n ) >= 3 && false !== strpos( $hay, ' ' . $n . ' ' ) ) {
			$out[ $key ] = $d;
		}
	}
	// "ÇİĞLİ ATAKENT" gibi uzun ad eşleştiyse içindeki kısa adı ("ÇİĞLİ") ayrıca sayma.
	foreach ( $out as $k => $d ) {
		foreach ( $out as $k2 => $d2 ) {
			if ( $k !== $k2 && false !== strpos( ' ' . df_addr_norm( $d2['name'] ) . ' ', ' ' . df_addr_norm( $d['name'] ) . ' ' ) ) {
				unset( $out[ $k ] );
				break;
			}
		}
	}
	return $out;
}

/**
 * Harita servisiyle adresin ilçe / mahallesini bul (1 gün önbellekli).
 *
 * @param string $address Adres.
 * @return array{ok:bool, district:string, suburb:string, label:string}|null null = servis kapalı / ulaşılamadı.
 */
function df_addr_geocode( $address ) {
	$mode = df_opt( 'addr_geo', 'osm' );
	if ( 'off' === $mode || mb_strlen( trim( $address ) ) < 8 ) {
		return null;
	}
	$city  = df_opt( 'df_default_city', 'İzmir' );
	$query = trim( $address ) . ', ' . $city . ', Türkiye';
	$ckey  = 'df_geo_' . md5( $mode . '|' . $query );
	$hit   = get_transient( $ckey );
	if ( is_array( $hit ) ) {
		return $hit;
	}
	$res = array(
		'ok'       => false,
		'district' => '',
		'suburb'   => '',
		'label'    => '',
	);
	if ( 'google' === $mode && df_opt( 'addr_google_key' ) ) {
		$r = wp_remote_get(
			add_query_arg(
				array(
					'address'  => $query,
					'region'   => 'tr',
					'language' => 'tr',
					'key'      => trim( (string) df_opt( 'addr_google_key' ) ),
				),
				'https://maps.googleapis.com/maps/api/geocode/json'
			),
			array( 'timeout' => 8 )
		);
		if ( is_wp_error( $r ) ) {
			return null;
		}
		$b = json_decode( wp_remote_retrieve_body( $r ), true );
		if ( ! empty( $b['results'][0] ) ) {
			$top = $b['results'][0];
			foreach ( (array) $top['address_components'] as $c ) {
				$types = (array) $c['types'];
				if ( in_array( 'administrative_area_level_2', $types, true ) ) {
					$res['district'] = $c['long_name'];
				}
				if ( in_array( 'administrative_area_level_4', $types, true ) || in_array( 'neighborhood', $types, true ) || in_array( 'sublocality', $types, true ) ) {
					$res['suburb'] = $res['suburb'] ? $res['suburb'] : $c['long_name'];
				}
			}
			// Sokak düzeyinde değil de sadece şehir düzeyinde bulduysa güvenilir sayılmaz.
			$precise    = ! in_array( 'locality', (array) $top['types'], true ) && ! in_array( 'administrative_area_level_1', (array) $top['types'], true );
			$res['ok']  = $precise && '' !== $res['district'];
			$res['label'] = (string) $top['formatted_address'];
		}
	} else {
		$r = wp_remote_get(
			add_query_arg(
				array(
					'q'               => $query,
					'format'          => 'jsonv2',
					'addressdetails'  => 1,
					'limit'           => 1,
					'countrycodes'    => 'tr',
					'accept-language' => 'tr',
				),
				'https://nominatim.openstreetmap.org/search'
			),
			array(
				'timeout' => 8,
				'headers' => array( 'User-Agent' => 'DerinFlowers/' . DF_VERSION . ' (' . home_url( '/' ) . '; ' . get_option( 'admin_email' ) . ')' ),
			)
		);
		if ( is_wp_error( $r ) || 200 !== (int) wp_remote_retrieve_response_code( $r ) ) {
			return null;
		}
		$b = json_decode( wp_remote_retrieve_body( $r ), true );
		if ( ! empty( $b[0]['address'] ) ) {
			$a               = $b[0]['address'];
			$res['district'] = isset( $a['town'] ) ? $a['town'] : ( isset( $a['city_district'] ) ? $a['city_district'] : ( isset( $a['county'] ) ? $a['county'] : '' ) );
			$res['suburb']   = isset( $a['suburb'] ) ? $a['suburb'] : ( isset( $a['quarter'] ) ? $a['quarter'] : ( isset( $a['neighbourhood'] ) ? $a['neighbourhood'] : '' ) );
			$res['ok']       = '' !== $res['district'] && ! in_array( $b[0]['addresstype'] ?? '', array( 'city', 'province', 'state', 'country' ), true );
			$res['label']    = (string) $b[0]['display_name'];
		}
	}
	set_transient( $ckey, $res, DAY_IN_SECONDS );
	return $res;
}

/**
 * Adresin bölgesini belirle ve seçilen bölgeyle karşılaştır.
 *
 * @param string $district_key Seçilen bölge anahtarı.
 * @param string $address      Adres.
 * @param string $hint         Adres tarifi.
 * @return array{status:string, zone:array|null, selected:array|null, where:string}
 *   status: ok | mismatch (adres daha pahalı bölgede) | unverified (emin olunamadı)
 */
function df_addr_check( $district_key, $address, $hint = '' ) {
	$zones    = df_delivery_districts();
	$selected = isset( $zones[ $district_key ] ) ? $zones[ $district_key ] : null;
	$text_found = df_addr_zones_in( $address . ' ' . $hint );
	$found      = $text_found;
	$where      = '';
	$geo        = df_addr_geocode( $address );
	if ( $geo && $geo['ok'] ) {
		$where = trim( $geo['district'] . ( $geo['suburb'] ? ' / ' . preg_replace( '/\s*Mahallesi$/u', '', $geo['suburb'] ) : '' ) );
		// Haritanın bulduğu yer önceliklidir (adres tarifine yazılan semt adı ücreti düşüremez).
		$by_sub  = $geo['suburb'] ? df_addr_zones_in( $geo['suburb'] ) : array();
		$by_dist = df_addr_zones_in( $geo['district'] );
		$geo_found = $by_sub + $by_dist;
		if ( $geo_found ) {
			$found = $geo_found;
		}
	}
	if ( ! $selected ) {
		return array( 'status' => 'unverified', 'zone' => null, 'selected' => null, 'where' => $where );
	}
	if ( ! $found ) {
		return array( 'status' => df_opt( 'addr_strict', 1 ) ? 'unverified' : 'ok', 'zone' => null, 'selected' => $selected, 'where' => $where );
	}
	if ( isset( $found[ $district_key ] ) ) {
		return array( 'status' => 'ok', 'zone' => $selected, 'selected' => $selected, 'where' => $where );
	}
	// Adreste geçen bölgelerden en pahalısı seçilenden pahalıysa uyuşmazlık.
	uasort(
		$found,
		function ( $a, $b ) {
			return $b['fee'] <=> $a['fee'];
		}
	);
	$top = reset( $found );
	if ( $top['fee'] > $selected['fee'] ) {
		return array( 'status' => 'mismatch', 'zone' => $top, 'selected' => $selected, 'where' => $where ? $where : $top['name'] );
	}
	return array( 'status' => 'ok', 'zone' => $selected, 'selected' => $selected, 'where' => $where );
}

/**
 * Sipariş doğrulaması (ödeme / Hemen Satın Al).
 *
 * @param array    $p      Teslimat bilgileri.
 * @param WP_Error $errors Hatalar.
 */
function df_addr_validate( $p, $errors ) {
	if ( ! df_opt( 'addr_check', 1 ) ) {
		return;
	}
	$c = df_addr_check( $p['district'], $p['address'], $p['address_hint'] );
	if ( WC()->session ) {
		WC()->session->set( 'df_addr_check', array( 'status' => $c['status'], 'where' => $c['where'], 'zone' => $c['zone'] ? $c['zone']['key'] : '' ) );
	}
	if ( 'mismatch' === $c['status'] ) {
		$fee = $c['zone']['fee'] > 0 ? wp_strip_all_tags( wc_price( $c['zone']['fee'] ) ) : 'ücretsiz';
		$errors->add(
			'df_district',
			'Teslimat adresiniz <strong>' . esc_html( df_addr_title( $c['zone']['name'] ) ) . '</strong> bölgesinde görünüyor (teslimat ücreti ' . esc_html( $fee ) . '). Lütfen teslimat bölgesini <strong>' . esc_html( df_addr_title( $c['zone']['name'] ) ) . '</strong> olarak seçin.',
			array( 'id' => 'df_district' )
		);
	}
}

/**
 * "ÇİĞLİ" → "Çiğli".
 *
 * @param string $s Ad.
 * @return string
 */
function df_addr_title( $s ) {
	return function_exists( 'df_loc_title_case' ) ? df_loc_title_case( $s ) : $s;
}

/**
 * AJAX: adres yazılırken canlı kontrol.
 */
function df_addr_ajax() {
	check_ajax_referer( 'df_addr', 'nonce' );
	$district = isset( $_POST['district'] ) ? sanitize_text_field( wp_unslash( $_POST['district'] ) ) : '';
	$address  = isset( $_POST['address'] ) ? sanitize_textarea_field( wp_unslash( $_POST['address'] ) ) : '';
	$hint     = isset( $_POST['hint'] ) ? sanitize_text_field( wp_unslash( $_POST['hint'] ) ) : '';
	$ip       = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$tkey     = 'df_addr_rl_' . md5( $ip );
	$n        = (int) get_transient( $tkey );
	if ( $n > 40 ) {
		wp_send_json_success( array( 'status' => 'skip' ) );
	}
	set_transient( $tkey, $n + 1, HOUR_IN_SECONDS );
	$c   = df_addr_check( $district, $address, $hint );
	$out = array(
		'status' => $c['status'],
		'where'  => $c['where'],
	);
	if ( $c['zone'] ) {
		$out['zone'] = array(
			'key'  => $c['zone']['key'],
			'name' => df_addr_title( $c['zone']['name'] ),
			'fee'  => $c['zone']['fee'] > 0 ? html_entity_decode( wp_strip_all_tags( wc_price( $c['zone']['fee'] ) ) ) : 'Ücretsiz',
		);
	}
	wp_send_json_success( $out );
}
add_action( 'wp_ajax_df_addr_check', 'df_addr_ajax' );
add_action( 'wp_ajax_nopriv_df_addr_check', 'df_addr_ajax' );

/**
 * Sonucu siparişe yaz; doğrulanamayan adres yönetici incelemesine düşer.
 *
 * @param WC_Order $order Sipariş.
 */
function df_addr_order_meta( $order ) {
	if ( ! WC()->session || 'pickup' === $order->get_meta( '_df_delivery_type' ) ) {
		return;
	}
	$c = (array) WC()->session->get( 'df_addr_check', array() );
	if ( empty( $c['status'] ) ) {
		return;
	}
	$order->update_meta_data( '_df_addr_check', $c['status'] );
	if ( ! empty( $c['where'] ) ) {
		$order->update_meta_data( '_df_addr_where', $c['where'] );
	}
	WC()->session->set( 'df_addr_check', null );
}
add_action( 'woocommerce_checkout_create_order', 'df_addr_order_meta', 30 );

/**
 * Doğrulanamayan adresli sipariş ödeme sonrası "Beklemede" (inceleme) durumuna alınır.
 *
 * @param string   $status   Durum.
 * @param int      $order_id Sipariş.
 * @param WC_Order $order    Sipariş.
 * @return string
 */
function df_addr_hold( $status, $order_id, $order = null ) {
	$order = $order ? $order : wc_get_order( $order_id );
	if ( $order && df_opt( 'addr_hold', 1 ) && 'unverified' === $order->get_meta( '_df_addr_check' ) ) {
		$order->add_order_note( 'Teslimat adresi otomatik doğrulanamadı; bölge ve teslimat ücretini kontrol edip siparişi "Hazırlanıyor" yapın.' );
		return 'on-hold';
	}
	return $status;
}
add_filter( 'woocommerce_payment_complete_order_status', 'df_addr_hold', 20, 3 );

/**
 * Yönetim: sipariş ekranında uyarı.
 *
 * @param WC_Order $order Sipariş.
 * @return string
 */
function df_addr_admin_badge( $order ) {
	$s = (string) $order->get_meta( '_df_addr_check' );
	$w = (string) $order->get_meta( '_df_addr_where' );
	if ( 'unverified' === $s ) {
		return '<p style="margin:0 0 10px;padding:8px 12px;border-radius:6px;background:#fff4e5;color:#7a5200"><strong>⚠ Adres doğrulanamadı.</strong> Seçilen bölge ve teslimat ücretini adresle karşılaştırın.</p>';
	}
	if ( $w ) {
		return '<p style="margin:0 0 10px;padding:8px 12px;border-radius:6px;background:#e9f2ea;color:#2f5236">✓ Adres doğrulandı: ' . esc_html( $w ) . '</p>';
	}
	return '';
}
