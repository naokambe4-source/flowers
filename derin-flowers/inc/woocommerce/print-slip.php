<?php
/**
 * Delikli fiş baskısı: A4 kâğıt 3 ya da 4 fişe bölünür. Her fişin solunda delikli çizgiden
 * koparılan not kartı (logo, not, imza), sağında ürün görseli ve teslimat bilgileri.
 * Ek ürün (hediye) alındıysa fişin en üstünde belirgin yazılır. Ölçüler panelden mm olarak ayarlanır.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Siparişin hediye kalemleri.
 *
 * @param WC_Order $o Sipariş.
 * @return string[]
 */
function df_slip_gifts( $o ) {
	$gift_ids = function_exists( 'df_gift_ids_all' ) ? (array) df_gift_ids_all() : array();
	$out      = array();
	foreach ( $o->get_items() as $item ) {
		if ( $item->get_meta( 'Hediye' ) || in_array( (int) $item->get_product_id(), array_map( 'intval', $gift_ids ), true ) ) {
			$out[] = $item->get_name() . ( $item->get_quantity() > 1 ? ' × ' . $item->get_quantity() : '' );
		}
	}
	return $out;
}

/**
 * Fiş satırları.
 *
 * @param WC_Order $o     Sipariş.
 * @param array    $gifts Hediyeler.
 * @return array<string,string>
 */
function df_slip_rows( $o, $gifts ) {
	$pickup = 'pickup' === $o->get_meta( '_df_delivery_type' );
	$date   = (string) $o->get_meta( '_df_delivery_date' );
	$items  = array();
	$qty    = 0;
	foreach ( $o->get_items() as $item ) {
		$name = $item->get_name() . ( $item->get_quantity() > 1 ? ' × ' . $item->get_quantity() : '' );
		if ( in_array( $name, $gifts, true ) ) {
			continue;
		}
		$p       = $item->get_product();
		$sku     = $p ? $p->get_sku() : '';
		$items[] = ( $sku ? $sku . ' - ' : '' ) . $name;
		$qty    += (int) $item->get_quantity();
	}
	$rows = array(
		'Teslimat Türü'       => $pickup ? 'Mağazadan Teslim' : 'Adrese Teslim',
		'Sipariş Adedi'       => max( 1, $qty ) . ' adet',
		'Sipariş No'          => $o->get_order_number(),
		'Teslimat Tarihi/Saati' => ( $date ? wp_date( 'd.m.Y', strtotime( $date . ' 12:00:00' ) ) : '' ) . ( $o->get_meta( '_df_delivery_slot' ) ? ' / ' . $o->get_meta( '_df_delivery_slot' ) : '' ),
		'Ürünler'             => implode( "\n", $items ),
		'Gönderen'            => trim( $o->get_meta( '_df_sender_name' ) . ' / ' . $o->get_meta( '_df_sender_phone' ), ' /' ),
		'Alıcının Adı/Numarası' => trim( $o->get_meta( '_df_recipient_name' ) . ' / ' . $o->get_meta( '_df_recipient_phone' ), ' /' ),
	);
	if ( $pickup ) {
		$rows['Mağaza'] = (string) $o->get_meta( '_df_store' );
	} else {
		$rows['Alıcının Adresi'] = trim( $o->get_meta( '_df_address' ) . ( $o->get_meta( '_df_address_hint' ) ? ' - ' . $o->get_meta( '_df_address_hint' ) : '' ) . ( $o->get_meta( '_df_district' ) ? ' - ' . $o->get_meta( '_df_district' ) : '' ) );
	}
	if ( $o->get_customer_note() ) {
		$rows['Not'] = $o->get_customer_note();
	}
	return $rows;
}

/**
 * Siparişin ana ürün görseli.
 *
 * @param WC_Order $o     Sipariş.
 * @param array    $gifts Hediyeler.
 * @return string
 */
function df_slip_image( $o, $gifts ) {
	foreach ( $o->get_items() as $item ) {
		$name = $item->get_name() . ( $item->get_quantity() > 1 ? ' × ' . $item->get_quantity() : '' );
		$p    = $item->get_product();
		if ( $p && ! in_array( $name, $gifts, true ) && $p->get_image_id() ) {
			return (string) wp_get_attachment_image_url( $p->get_image_id(), 'medium_large' );
		}
	}
	return '';
}

/**
 * Delikli fiş çıktısı.
 *
 * @param int[] $ids Siparişler.
 */
function df_print_perfo( $ids ) {
	$per    = '4' === (string) df_opt( 'slip_per_page', '3' ) ? 4 : 3;
	$h      = round( 297 / $per, 2 );
	$card   = max( 40, min( 120, (int) df_opt( 'slip_card_mm', 70 ) ) );
	$pad    = max( 2, min( 15, (int) df_opt( 'slip_margin_mm', 6 ) ) );
	$sx     = (int) df_opt( 'slip_shift_x', 0 );
	$sy     = (int) df_opt( 'slip_shift_y', 0 );
	$small  = 4 === $per;
	$logo   = absint( df_opt( 'slip_logo' ) ) ? absint( df_opt( 'slip_logo' ) ) : absint( df_opt( 'logo_image' ) );
	$logo_h = $logo ? wp_get_attachment_image( $logo, 'medium', false, array( 'class' => 'logo-img' ) ) : '<div class="logo-txt">' . esc_html( df_opt( 'logo_text', get_bloginfo( 'name' ) ) ) . '</div>';
	$fonts  = df_fonts_url();
	$orders = array();
	foreach ( $ids as $id ) {
		$o = wc_get_order( $id );
		if ( $o ) {
			$orders[] = $o;
		}
	}
	?>
	<!doctype html>
	<html lang="tr"><head><meta charset="utf-8"><title>Delikli fiş — <?php echo count( $orders ); ?> sipariş</title>
	<?php if ( $fonts ) : ?><link rel="stylesheet" href="<?php echo esc_url( $fonts ); ?>"><?php endif; // phpcs:ignore ?>
	<style>
		@page { size: A4 portrait; margin: 0; }
		* { box-sizing: border-box; }
		body { margin: 0; color: #2b2522; font-family: <?php echo '"' . esc_html( df_opt( 'font_body' ) ) . '"'; ?>, Roboto, Arial, sans-serif; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
		.sheet { width: 210mm; height: 297mm; overflow: hidden; page-break-after: always; break-after: page; position: relative; }
		.sheet:last-child { page-break-after: auto; break-after: auto; }
		.inner { position: absolute; left: <?php echo (int) $sx; ?>mm; top: <?php echo (int) $sy; ?>mm; width: 210mm; }
		.slip { height: <?php echo esc_attr( $h ); ?>mm; display: flex; overflow: hidden; }
		.card { width: <?php echo (int) $card; ?>mm; flex: 0 0 auto; padding: <?php echo (int) $pad; ?>mm; display: flex; flex-direction: column; align-items: center; text-align: center; }
		.card .logo-img { max-width: 70%; max-height: <?php echo $small ? 11 : 15; ?>mm; width: auto; height: auto; }
		.card .logo-txt { font-family: <?php echo '"' . esc_html( df_opt( 'font_heading' ) ) . '"'; ?>, serif; letter-spacing: .2em; font-size: <?php echo $small ? '8pt' : '10pt'; ?>; }
		.card .msg { flex: 1; display: flex; flex-direction: column; justify-content: center; width: 100%; }
		.card .note { font-style: italic; line-height: 1.35; white-space: pre-wrap; word-wrap: break-word; }
		.card .sign { margin-top: 3mm; font-weight: 700; text-transform: uppercase; font-size: <?php echo $small ? '7.5pt' : '9pt'; ?>; letter-spacing: .02em; }
		.info { flex: 1; padding: <?php echo (int) $pad; ?>mm <?php echo (int) $pad; ?>mm <?php echo (int) $pad; ?>mm <?php echo max( 3, (int) $pad - 1 ); ?>mm; display: flex; flex-direction: column; min-width: 0; }
		.gift { border: 1.5px solid #2b2522; background: #f3ede6; font-weight: 700; padding: 1.2mm 3mm; margin-bottom: 2mm; font-size: <?php echo $small ? '8pt' : '9.5pt'; ?>; text-transform: uppercase; letter-spacing: .02em; }
		.body { flex: 1; display: flex; gap: 4mm; min-height: 0; }
		.img { width: <?php echo $small ? 30 : 42; ?>mm; flex: 0 0 auto; }
		.img img { width: 100%; height: 100%; max-height: <?php echo esc_attr( $h - 2 * $pad - 2 ); ?>mm; object-fit: cover; display: block; }
		table { align-self: flex-start; border-collapse: collapse; width: 100%; font-size: <?php echo $small ? '7.6pt' : '9.8pt'; ?>; line-height: 1.32; }
		th { text-align: left; font-weight: 700; white-space: nowrap; vertical-align: top; padding: 0 2mm <?php echo $small ? '.7' : '1.6'; ?>mm 0; }
		td { vertical-align: top; padding: 0 0 <?php echo $small ? '.7' : '1.6'; ?>mm; }
		td::before { content: ": "; }
		@media screen {
			body { background: #ddd; }
			.sheet { background: #fff; margin: 20px auto; box-shadow: 0 4px 24px rgba(0,0,0,.15); }
			.slip { outline: 1px dashed #c9bdb2; }
			.card { border-right: 1px dashed #c9bdb2; }
			.bar { position: sticky; top: 0; z-index: 2; background: #2b2522; color: #fff; padding: 10px 16px; font: 14px system-ui, sans-serif; text-align: center; }
			.bar button { margin-left: 10px; padding: 6px 14px; }
		}
		@media print { .bar { display: none; } }
	</style></head>
	<body>
	<div class="bar">Kesik çizgiler sadece ekranda görünür, yazdırılmaz. Önce düz kâğıda deneme baskısı alıp delikli kâğıdın üstüne koyarak kontrol edin. <button onclick="window.print()">Yazdır</button></div>
	<?php
	foreach ( array_chunk( $orders, $per ) as $chunk ) {
		echo '<div class="sheet"><div class="inner">';
		foreach ( $chunk as $o ) {
			$gifts = df_slip_gifts( $o );
			$note  = (string) $o->get_meta( '_df_note' );
			$sign  = 'yes' === $o->get_meta( '_df_note_anon' ) ? '' : (string) $o->get_meta( '_df_note_from' );
			$len   = mb_strlen( $note );
			$fs    = $len > 260 ? 7.4 : ( $len > 170 ? 8.4 : ( $len > 90 ? 9.6 : 11 ) );
			if ( $small ) {
				$fs -= 1.2;
			}
			$img = df_opt( 'slip_image', 1 ) ? df_slip_image( $o, $gifts ) : '';
			echo '<div class="slip"><div class="card">' . $logo_h . '<div class="msg">'; // phpcs:ignore
			if ( '' !== $note ) {
				echo '<div class="note" style="font-size:' . esc_attr( $fs ) . 'pt">' . esc_html( $note ) . '</div>';
			}
			echo $sign ? '<div class="sign">' . esc_html( $sign ) . '</div>' : '';
			echo '</div></div><div class="info">';
			if ( $gifts ) {
				echo '<div class="gift">★ Ek ürün: ' . esc_html( implode( ', ', $gifts ) ) . '</div>';
			}
			echo '<div class="body">';
			if ( $img ) {
				echo '<div class="img"><img src="' . esc_url( $img ) . '" alt=""></div>';
			}
			echo '<table>';
			foreach ( df_slip_rows( $o, $gifts ) as $label => $value ) {
				echo '<tr><th>' . esc_html( $label ) . '</th><td>' . nl2br( esc_html( $value ) ) . '</td></tr>';
			}
			echo '</table></div></div></div>';
			if ( function_exists( 'df_print_log' ) ) {
				df_print_log( $o, 'perfo' );
			}
		}
		echo '</div></div>';
	}
	?>
	<script>window.addEventListener( 'load', function () { setTimeout( function () { window.print(); }, 400 ); } );</script>
	</body></html>
	<?php
	exit;
}
