<?php
/**
 * Teslimat motoru: bölgeler & ücretler, mağazalar, saat aralıkları, takvim kuralları.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * "1.500", "1.500,00", "150,5", "₺300" gibi tutarları sayıya çevirir.
 *
 * @param string $raw Tutar.
 * @return float
 */
function df_parse_money( $raw ) {
	$v = preg_replace( '/[^0-9.,]/', '', (string) $raw );
	if ( '' === $v ) {
		return 0.0;
	}
	if ( false !== strpos( $v, ',' ) ) {
		$v = str_replace( ',', '.', str_replace( '.', '', $v ) );
	} elseif ( preg_match( '/^\d{1,3}(\.\d{3})+$/', $v ) ) {
		$v = str_replace( '.', '', $v );
	}
	return max( 0.0, (float) $v );
}

/**
 * Teslimat bölgeleri.
 *
 * @return array<string, array{key:string, city:string, name:string, fee:float}>
 */
function df_delivery_districts() {
	static $cache = null;
	if ( null !== $cache ) {
		return $cache;
	}
	$cache = array();
	foreach ( df_lines( df_opt( 'df_districts' ), true ) as $row ) {
		if ( count( $row ) < 2 ) {
			$row = array( df_opt( 'df_default_city', 'İzmir' ), $row[0], 0 );
		}
		$city = $row[0];
		$name = $row[1];
		$fee  = isset( $row[2] ) ? df_parse_money( $row[2] ) : 0;
		$key           = $city . ' — ' . $name;
		$cache[ $key ] = array(
			'key'  => $key,
			'city' => $city,
			'name' => $name,
			'fee'  => max( 0, $fee ),
		);
	}
	return $cache;
}

/**
 * Bölgeleri şehre göre gruplar.
 *
 * @return array<string, array>
 */
function df_delivery_districts_grouped() {
	$out = array();
	foreach ( df_delivery_districts() as $d ) {
		$out[ $d['city'] ][] = $d;
	}
	return $out;
}

/**
 * Mağazalar.
 *
 * @return array<int, array{name:string, address:string, hours:string}>
 */
function df_delivery_stores() {
	$out = array();
	foreach ( df_lines( df_opt( 'df_stores' ), true ) as $i => $row ) {
		$out[ $i ] = array(
			'name'    => $row[0],
			'address' => isset( $row[1] ) ? $row[1] : '',
			'hours'   => isset( $row[2] ) ? $row[2] : '',
		);
	}
	return $out;
}

/**
 * Saat aralıkları.
 *
 * @return array<string, array{key:string, label:string, start:int, end:int, fee:float}>
 */
function df_delivery_slots() {
	$out = array();
	foreach ( df_lines( df_opt( 'df_slots' ), true ) as $row ) {
		$label = $row[0];
		$fee   = isset( $row[1] ) ? df_parse_money( $row[1] ) : 0;
		$start = 0;
		$end   = 24 * 60;
		if ( preg_match( '/(\d{1,2})[:.](\d{2})\s*-\s*(\d{1,2})[:.](\d{2})/', $label, $m ) ) {
			$start = (int) $m[1] * 60 + (int) $m[2];
			$end   = (int) $m[3] * 60 + (int) $m[4];
		}
		$key         = sanitize_title( $label );
		$out[ $key ] = array(
			'key'   => $key,
			'label' => $label,
			'start' => $start,
			'end'   => $end,
			'fee'   => max( 0, $fee ),
		);
	}
	return $out;
}

/**
 * Şu an (site saat diliminde).
 *
 * @return DateTimeImmutable
 */
function df_now() {
	return current_datetime();
}

/**
 * Son sipariş saati (dakika).
 *
 * @return int
 */
function df_cutoff_minutes() {
	$cut = (string) df_opt( 'df_cutoff', '16:00' );
	if ( preg_match( '/(\d{1,2})[:.]?(\d{2})?/', $cut, $m ) ) {
		return (int) $m[1] * 60 + ( isset( $m[2] ) ? (int) $m[2] : 0 );
	}
	return 16 * 60;
}

/**
 * Tarihte teslimat yapılıyor mu (kapalı gün / tatil)?
 *
 * @param string $ymd Tarih (Y-m-d).
 * @return bool
 */
function df_delivery_day_open( $ymd ) {
	$date = DateTimeImmutable::createFromFormat( '!Y-m-d', $ymd, wp_timezone() );
	if ( ! $date ) {
		return false;
	}
	$closed  = array_map( 'strval', (array) df_opt( 'df_closed_days', array() ) );
	$blocked = df_lines( df_opt( 'df_blocked_dates' ) );
	if ( in_array( $date->format( 'w' ), $closed, true ) || in_array( $ymd, $blocked, true ) ) {
		return false;
	}
	return true;
}

/**
 * Bugün için kalan süre (aynı gün teslimat), kapalıysa 0.
 *
 * @return int saniye
 */
function df_delivery_same_day_seconds_left() {
	$now = df_now();
	if ( ! df_delivery_day_open( $now->format( 'Y-m-d' ) ) ) {
		return 0;
	}
	$mins = (int) $now->format( 'G' ) * 60 + (int) $now->format( 'i' );
	$left = df_cutoff_minutes() - $mins;
	if ( $left <= 0 || ! df_delivery_available_slots( $now->format( 'Y-m-d' ) ) ) {
		return 0;
	}
	return $left * 60 - (int) $now->format( 's' );
}

/**
 * Bir gün için seçilebilir saat aralıkları.
 *
 * @param string $ymd Tarih.
 * @return array
 */
function df_delivery_available_slots( $ymd ) {
	if ( ! df_delivery_day_open( $ymd ) ) {
		return array();
	}
	$slots = df_delivery_slots();
	$now   = df_now();
	if ( $ymd !== $now->format( 'Y-m-d' ) ) {
		return $slots;
	}
	$mins = (int) $now->format( 'G' ) * 60 + (int) $now->format( 'i' );
	if ( $mins >= df_cutoff_minutes() ) {
		return array();
	}
	$ready = $mins + absint( df_opt( 'df_prep_hours', 2 ) ) * 60;
	return array_filter(
		$slots,
		function ( $s ) use ( $ready ) {
			return $s['end'] - 30 >= $ready; // Aralık bitmeden en az 30 dk önce hazır olmalı.
		}
	);
}

/**
 * En erken teslimat tarihi.
 *
 * @return string|null Y-m-d
 */
function df_delivery_next_date() {
	$day = df_now()->setTime( 0, 0 );
	$max = absint( df_opt( 'df_max_days', 60 ) );
	for ( $i = 0; $i <= $max; $i++ ) {
		$ymd = $day->modify( '+' . $i . ' day' )->format( 'Y-m-d' );
		if ( df_delivery_available_slots( $ymd ) ) {
			return $ymd;
		}
	}
	return null;
}

/**
 * Tarih etiketi: "Bugün", "Yarın" ya da "3 Ekim Cuma".
 *
 * @param string $ymd Tarih.
 * @return string
 */
function df_date_label( $ymd ) {
	$date = DateTimeImmutable::createFromFormat( '!Y-m-d', $ymd, wp_timezone() );
	if ( ! $date ) {
		return $ymd;
	}
	$today = df_now()->format( 'Y-m-d' );
	$tmrw  = df_now()->modify( '+1 day' )->format( 'Y-m-d' );
	$label = wp_date( 'j F l', $date->getTimestamp() );
	if ( $ymd === $today ) {
		return 'Bugün, ' . wp_date( 'j F', $date->getTimestamp() );
	}
	if ( $ymd === $tmrw ) {
		return 'Yarın, ' . wp_date( 'j F', $date->getTimestamp() );
	}
	return $label;
}

/**
 * Takvim için istemci verisi.
 *
 * @return array
 */
function df_delivery_calendar_data() {
	$now   = df_now();
	$max   = absint( df_opt( 'df_max_days', 60 ) );
	$days  = array();
	$start = $now->setTime( 0, 0 );
	for ( $i = 0; $i <= $max; $i++ ) {
		$ymd = $start->modify( '+' . $i . ' day' )->format( 'Y-m-d' );
		$av  = df_delivery_available_slots( $ymd );
		if ( $av ) {
			$days[ $ymd ] = array_keys( $av );
		}
	}
	$slots = array();
	foreach ( df_delivery_slots() as $key => $s ) {
		$slots[ $key ] = array(
			'label' => $s['label'],
			'fee'   => $s['fee'],
		);
	}
	return array(
		'today'  => $now->format( 'Y-m-d' ),
		'max'    => $start->modify( '+' . $max . ' day' )->format( 'Y-m-d' ),
		'days'   => $days,
		'slots'  => $slots,
		'months' => array( 'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık' ),
		'wdays'  => array( 'Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz' ),
		'wlong'  => array( 'Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi' ),
	);
}

/**
 * Teslimat ücreti hesaplama.
 *
 * @param string $type     address|pickup.
 * @param string $district Bölge anahtarı.
 * @param string $slot     Saat aralığı anahtarı.
 * @return float
 */
function df_delivery_fee( $type, $district, $slot = '' ) {
	$fee = 0.0;
	if ( 'pickup' !== $type ) {
		$districts = df_delivery_districts();
		if ( isset( $districts[ $district ] ) ) {
			$fee += (float) $districts[ $district ]['fee'];
		}
		$free_over = (float) df_opt( 'df_free_over', 0 );
		if ( $free_over > 0 && WC()->cart && (float) WC()->cart->get_subtotal() >= $free_over ) {
			$fee = 0.0;
		}
	}
	$slots = df_delivery_slots();
	if ( $slot && isset( $slots[ $slot ] ) ) {
		$fee += (float) $slots[ $slot ]['fee'];
	}
	return (float) apply_filters( 'df_delivery_fee', $fee, $type, $district, $slot );
}

/**
 * [derin_teslimat_bolgeleri] — bölge/ücret tablosu.
 *
 * @return string
 */
function df_districts_shortcode() {
	$groups = df_delivery_districts_grouped();
	if ( ! $groups ) {
		return '';
	}
	ob_start();
	echo '<div class="df-zones">';
	echo '<div class="df-zones__search"><label class="screen-reader-text" for="df-zone-q">Bölge ara</label>' . df_icon( 'search', array( 'size' => 18 ) ) . '<input type="search" id="df-zone-q" placeholder="Bölgenizi arayın (ör. Karşıyaka)" data-df-zone-search></div>'; // phpcs:ignore
	foreach ( $groups as $city => $items ) {
		usort(
			$items,
			function ( $a, $b ) {
				return $a['fee'] <=> $b['fee'] ?: strcoll( $a['name'], $b['name'] );
			}
		);
		echo '<h3 class="df-zones__city">' . esc_html( $city ) . '</h3><ul class="df-zones__list">';
		foreach ( $items as $d ) {
			printf(
				'<li data-name="%1$s"><span>%2$s</span><strong>%3$s</strong></li>',
				esc_attr( mb_strtolower( $d['name'] ) ),
				esc_html( $d['name'] ),
				$d['fee'] > 0 ? wp_kses_post( wc_price( $d['fee'] ) ) : '<em>Ücretsiz</em>'
			);
		}
		echo '</ul>';
	}
	echo '<p class="df-zones__note">' . esc_html( df_vars( 'Aynı gün teslimat için son sipariş saati {cutoff}. Listede olmayan bölgeler için bizi arayın.' ) ) . '</p>';
	echo '</div>';
	return ob_get_clean();
}
add_shortcode( 'derin_teslimat_bolgeleri', 'df_districts_shortcode' );
