<?php
/**
 * Kurye ekranı: her kuryeye özel, şifresiz telefon sayfası (/?df_kurye=ANAHTAR).
 * Kurye kendisine atanan teslimatları görür, alıcıyı arar, haritayı açar,
 * "Yola çıktım" / "Teslim ettim" (fotoğraf + teslim alan) / "Teslim edilemedi" der.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Kuryeler (Ad | Telefon).
 *
 * @return array<int, array{name:string, phone:string, token:string}>
 */
function df_courier_list() {
	$out = array();
	foreach ( df_lines( df_opt( 'df_couriers' ), true ) as $row ) {
		$name  = $row[0];
		$phone = isset( $row[1] ) ? preg_replace( '/\D/', '', $row[1] ) : '';
		$out[] = array(
			'name'  => $name,
			'phone' => $phone,
			'token' => substr( wp_hash( 'df_courier|' . $name . '|' . $phone, 'auth' ), 0, 20 ),
		);
	}
	return $out;
}

/**
 * Kurye ekranı adresi.
 *
 * @param array $c Kurye.
 * @return string
 */
function df_courier_url( $c ) {
	return add_query_arg( 'df_kurye', $c['token'], home_url( '/' ) );
}

/**
 * Anahtardan kurye.
 *
 * @param string $token Anahtar.
 * @return array|null
 */
function df_courier_by_token( $token ) {
	foreach ( df_courier_list() as $c ) {
		if ( hash_equals( $c['token'], (string) $token ) ) {
			return $c;
		}
	}
	return null;
}

/**
 * Kuryenin siparişleri (bugün + ileri günler, yoldakiler her zaman).
 *
 * @param array $c Kurye.
 * @return WC_Order[]
 */
function df_courier_orders( $c ) {
	$today = df_now()->format( 'Y-m-d' );
	$until = df_now()->modify( '+' . max( 0, (int) df_opt( 'courier_days', 1 ) ) . ' day' )->format( 'Y-m-d' );
	$all   = wc_get_orders(
		array(
			'limit'        => 300,
			'type'         => 'shop_order',
			'status'       => array( 'on-hold', 'processing', 'df-preparing', 'df-on-the-way', 'completed' ),
			'date_created' => '>' . ( time() - 90 * DAY_IN_SECONDS ),
			'orderby'      => 'date',
			'order'        => 'DESC',
		)
	);
	$out = array();
	foreach ( $all as $o ) {
		if ( strtok( (string) $o->get_meta( '_df_courier' ), '|' ) !== $c['name'] ) {
			continue;
		}
		$date   = (string) $o->get_meta( '_df_delivery_date' );
		$status = $o->get_status();
		if ( 'completed' === $status ) {
			$done = (int) $o->get_meta( '_df_delivered_at' );
			if ( ! $done || wp_date( 'Y-m-d', $done ) !== $today ) {
				continue;
			}
		} elseif ( 'df-on-the-way' !== $status && ( $date < $today || $date > $until ) ) {
			continue;
		}
		$out[] = $o;
	}
	usort(
		$out,
		function ( $a, $b ) {
			$ka = ( 'completed' === $a->get_status() ? '1' : '0' ) . $a->get_meta( '_df_delivery_date' ) . $a->get_meta( '_df_delivery_slot' );
			$kb = ( 'completed' === $b->get_status() ? '1' : '0' ) . $b->get_meta( '_df_delivery_date' ) . $b->get_meta( '_df_delivery_slot' );
			return strcmp( $ka, $kb );
		}
	);
	return $out;
}

/**
 * Kurye işlemi (form gönderimi).
 *
 * @param array $c Kurye.
 * @return string Mesaj.
 */
function df_courier_handle( $c ) {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- aşağıda doğrulanıyor.
	if ( empty( $_POST['df_act'] ) ) {
		return '';
	}
	if ( empty( $_POST['_df_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_df_nonce'] ), 'df_kurye_' . $c['token'] ) ) {
		return 'Sayfanın süresi doldu, yenileyip tekrar deneyin.';
	}
	$act   = sanitize_key( $_POST['df_act'] );
	$order = wc_get_order( isset( $_POST['order'] ) ? absint( $_POST['order'] ) : 0 );
	if ( ! $order || strtok( (string) $order->get_meta( '_df_courier' ), '|' ) !== $c['name'] ) {
		return 'Sipariş bulunamadı.';
	}
	$no = '#' . $order->get_order_number();
	if ( 'way' === $act ) {
		if ( in_array( $order->get_status(), array( 'on-hold', 'processing', 'df-preparing' ), true ) ) {
			$order->update_status( 'df-on-the-way', 'Kurye yola çıktı: ' . $c['name'] );
		}
		return $no . ' yolda olarak işaretlendi.';
	}
	if ( 'fail' === $act ) {
		$reason = isset( $_POST['reason'] ) ? sanitize_text_field( wp_unslash( $_POST['reason'] ) ) : '';
		$order->update_meta_data( '_df_delivery_fail', $reason . ' · ' . wp_date( 'j M H:i' ) );
		$order->add_order_note( 'TESLİM EDİLEMEDİ (' . $c['name'] . '): ' . $reason );
		$order->save();
		return $no . ' için "teslim edilemedi" kaydedildi, mağazaya bilgi düştü.';
	}
	if ( 'done' !== $act ) {
		return '';
	}
	$receiver = isset( $_POST['receiver'] ) ? sanitize_text_field( wp_unslash( $_POST['receiver'] ) ) : '';
	$photo    = 0;
	if ( ! empty( $_FILES['photo']['name'] ) && empty( $_FILES['photo']['error'] ) ) {
		if ( (int) $_FILES['photo']['size'] > 12 * MB_IN_BYTES ) {
			return 'Fotoğraf çok büyük (en fazla 12 MB).';
		}
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$_FILES['photo']['name'] = 'teslim-' . $order->get_order_number() . '-' . wp_generate_password( 6, false ) . '.jpg';
		$check                   = wp_check_filetype_and_ext( $_FILES['photo']['tmp_name'], $_FILES['photo']['name'] ); // phpcs:ignore
		if ( empty( $check['type'] ) || 0 !== strpos( $check['type'], 'image/' ) ) {
			return 'Lütfen fotoğraf yükleyin.';
		}
		$photo = media_handle_upload( 'photo', 0, array( 'post_title' => 'Teslim fotoğrafı ' . $no ), array( 'test_form' => false ) );
		if ( is_wp_error( $photo ) ) {
			return 'Fotoğraf yüklenemedi: ' . $photo->get_error_message();
		}
	}
	// phpcs:enable
	if ( ! $photo && df_opt( 'courier_photo_req', 0 ) ) {
		return 'Teslim fotoğrafı zorunlu. Lütfen fotoğraf çekin.';
	}
	if ( $photo ) {
		$order->update_meta_data( '_df_proof', $photo );
	}
	$order->update_meta_data( '_df_delivered_at', time() );
	$order->update_meta_data( '_df_delivered_by', $c['name'] );
	$order->update_meta_data( '_df_delivered_to', $receiver );
	$order->delete_meta_data( '_df_delivery_fail' );
	$order->save();
	$order->update_status( 'completed', 'Teslim edildi' . ( $receiver ? ' — teslim alan: ' . $receiver : '' ) . ' (kurye: ' . $c['name'] . ( $photo ? ', fotoğraflı' : '' ) . ').' );
	return $no . ' teslim edildi. Teşekkürler!';
}

/**
 * Kurye ekranı.
 */
function df_courier_screen() {
	if ( ! isset( $_GET['df_kurye'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return;
	}
	nocache_headers();
	header( 'X-Robots-Tag: noindex, nofollow' );
	$c = df_opt( 'courier_screen', 1 ) ? df_courier_by_token( sanitize_key( wp_unslash( $_GET['df_kurye'] ) ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! $c ) {
		status_header( 403 );
		wp_die( 'Bu kurye bağlantısı geçerli değil. Mağazadan yeni bağlantı isteyin.', 'Kurye ekranı', array( 'response' => 403 ) );
	}
	$msg = df_courier_handle( $c );
	if ( $msg && 'POST' === $_SERVER['REQUEST_METHOD'] && empty( $_POST['ajax'] ) ) { // phpcs:ignore
		wp_safe_redirect( add_query_arg( array( 'df_kurye' => $c['token'], 'm' => rawurlencode( $msg ) ), home_url( '/' ) ) );
		exit;
	}
	if ( ! empty( $_POST['ajax'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		wp_send_json( array( 'msg' => $msg ) );
	}
	if ( isset( $_GET['m'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$msg = sanitize_text_field( wp_unslash( $_GET['m'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
	$orders = df_courier_orders( $c );
	$nonce  = wp_create_nonce( 'df_kurye_' . $c['token'] );
	$today  = df_now()->format( 'Y-m-d' );
	$open   = array_filter(
		$orders,
		function ( $o ) {
			return 'completed' !== $o->get_status();
		}
	);
	?>
	<!doctype html>
	<html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="robots" content="noindex,nofollow">
	<meta name="theme-color" content="#2b2522"><title>Kurye · <?php echo esc_html( $c['name'] ); ?></title>
	<style>
		*{box-sizing:border-box}body{margin:0;background:#f4efe9;color:#2b2522;font:15px/1.45 -apple-system,system-ui,Segoe UI,Roboto,sans-serif;padding-bottom:40px}
		header{position:sticky;top:0;z-index:5;background:#2b2522;color:#fff;padding:14px 16px;display:flex;justify-content:space-between;align-items:center}
		header b{font-size:17px}header small{opacity:.75}header a{color:#fff;text-decoration:none;border:1px solid rgba(255,255,255,.4);padding:6px 10px;border-radius:8px;font-size:13px}
		main{padding:12px;max-width:640px;margin:0 auto}.msg{background:#5e7b61;color:#fff;padding:12px 14px;border-radius:12px;margin-bottom:12px}
		h2{font-size:13px;letter-spacing:.08em;text-transform:uppercase;color:#7a6e68;margin:18px 4px 8px}
		.o{background:#fff;border-radius:16px;padding:14px;margin-bottom:12px;box-shadow:0 1px 3px rgba(0,0,0,.06)}
		.o.is-done{opacity:.6}.o.is-way{outline:2px solid #2f5d86}
		.o__top{display:flex;justify-content:space-between;gap:8px;align-items:center;margin-bottom:8px}
		.slot{font-weight:700;font-size:16px}.st{font-size:12px;padding:3px 8px;border-radius:20px;background:#f3e7d8;color:#8a5a2b;white-space:nowrap}
		.st--way{background:#dfe9f2;color:#2f5d86}.st--done{background:#e3eee4;color:#3e6a43}
		.who{font-size:17px;font-weight:600;margin:2px 0}.addr{margin:6px 0;color:#4a403b}.hint{font-size:13px;color:#7a6e68}
		.items{font-size:13px;color:#7a6e68;margin:6px 0 10px}.fail{background:#fdecea;color:#8a1f11;font-size:13px;padding:6px 10px;border-radius:8px;margin:6px 0}
		.btns{display:grid;grid-template-columns:1fr 1fr;gap:8px}.btn{display:flex;align-items:center;justify-content:center;gap:6px;min-height:46px;border-radius:12px;border:1px solid #d9cfc6;background:#fff;color:#2b2522;font:600 15px system-ui,sans-serif;text-decoration:none;cursor:pointer;width:100%}
		.btn--dark{background:#2b2522;color:#fff;border-color:#2b2522}.btn--green{background:#5e7b61;color:#fff;border-color:#5e7b61}.btn--blue{background:#2f5d86;color:#fff;border-color:#2f5d86}
		.full{grid-column:1/-1}details{grid-column:1/-1}summary{list-style:none}summary::-webkit-details-marker{display:none}
		.panel{margin-top:8px;display:grid;gap:8px;background:#faf7f2;padding:10px;border-radius:12px}
		.panel input[type=text],.panel select{width:100%;min-height:44px;border:1px solid #d9cfc6;border-radius:10px;padding:0 12px;font-size:16px;background:#fff}
		.cam{display:flex;align-items:center;justify-content:center;min-height:90px;border:2px dashed #c9bdb2;border-radius:12px;background:#fff;font-weight:600;color:#7a6e68;cursor:pointer;overflow:hidden;position:relative}
		.cam input{position:absolute;inset:0;opacity:0}.cam img{max-height:180px;max-width:100%;display:block}
		.empty{text-align:center;color:#7a6e68;padding:40px 10px}
	</style></head>
	<body>
	<header><div><b><?php echo esc_html( $c['name'] ); ?></b><br><small><?php echo esc_html( wp_date( 'j F l' ) ); ?> · <?php echo count( $open ); ?> teslimat</small></div><a href="<?php echo esc_url( df_courier_url( $c ) ); ?>">↻ Yenile</a></header>
	<main>
		<?php if ( $msg ) : ?>
			<div class="msg" role="status"><?php echo esc_html( $msg ); ?></div>
		<?php endif; ?>
		<?php if ( ! $orders ) : ?>
			<p class="empty">Şu an size atanmış teslimat yok.</p>
		<?php endif; ?>
		<?php
		$last = null;
		foreach ( $orders as $o ) :
			$st   = $o->get_status();
			$date = (string) $o->get_meta( '_df_delivery_date' );
			$head = 'completed' === $st ? 'Bugün teslim edilenler' : ( $date === $today ? 'Bugün' : ( $date < $today ? 'Gecikenler' : wp_date( 'j F l', strtotime( $date . ' 12:00:00' ) ) ) );
			if ( $head !== $last ) {
				echo '<h2>' . esc_html( $head ) . '</h2>';
				$last = $head;
			}
			$addr  = trim( (string) $o->get_meta( '_df_address' ) );
			$dist  = (string) $o->get_meta( '_df_district' );
			$map   = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $addr . ' ' . str_replace( ' — ', ' ', $dist ) );
			$phone = (string) $o->get_meta( '_df_recipient_phone' );
			$items = array();
			foreach ( $o->get_items() as $it ) {
				$items[] = $it->get_name() . ' ×' . $it->get_quantity();
			}
			$cls = 'completed' === $st ? 'is-done' : ( 'df-on-the-way' === $st ? 'is-way' : '' );
			?>
			<article class="o <?php echo esc_attr( $cls ); ?>">
				<div class="o__top">
					<span class="slot"><?php echo esc_html( $o->get_meta( '_df_delivery_slot' ) ); ?> <small style="font-weight:400;color:#7a6e68">#<?php echo esc_html( $o->get_order_number() ); ?></small></span>
					<span class="st <?php echo 'df-on-the-way' === $st ? 'st--way' : ( 'completed' === $st ? 'st--done' : '' ); ?>"><?php echo esc_html( 'completed' === $st ? 'Teslim edildi' : ( 'df-on-the-way' === $st ? 'Yolda' : 'Hazırlanıyor' ) ); ?></span>
				</div>
				<div class="who"><?php echo esc_html( $o->get_meta( '_df_recipient_name' ) ); ?></div>
				<div class="addr"><strong><?php echo esc_html( df_loc_title_case( preg_replace( '/^.* — /u', '', $dist ) ) ); ?></strong> · <?php echo esc_html( $addr ); ?></div>
				<?php if ( $o->get_meta( '_df_address_hint' ) ) : ?>
					<div class="hint">Tarif: <?php echo esc_html( $o->get_meta( '_df_address_hint' ) ); ?></div>
				<?php endif; ?>
				<div class="items"><?php echo esc_html( implode( ', ', $items ) ); ?></div>
				<?php if ( $o->get_meta( '_df_delivery_fail' ) && 'completed' !== $st ) : ?>
					<div class="fail">Teslim edilemedi: <?php echo esc_html( $o->get_meta( '_df_delivery_fail' ) ); ?></div>
				<?php endif; ?>
				<?php if ( 'completed' !== $st ) : ?>
					<div class="btns">
						<a class="btn" href="<?php echo esc_url( df_tel( $phone ) ); ?>">📞 Alıcıyı ara</a>
						<a class="btn" href="<?php echo esc_url( $map ); ?>" target="_blank" rel="noopener">📍 Harita</a>
						<?php if ( 'df-on-the-way' !== $st ) : ?>
							<form method="post" class="full" data-df-k>
								<input type="hidden" name="_df_nonce" value="<?php echo esc_attr( $nonce ); ?>"><input type="hidden" name="order" value="<?php echo (int) $o->get_id(); ?>">
								<input type="hidden" name="df_act" value="way"><button class="btn btn--blue">🚚 Yola çıktım</button>
							</form>
						<?php endif; ?>
						<details>
							<summary class="btn btn--green">✓ Teslim ettim</summary>
							<form method="post" enctype="multipart/form-data" class="panel" data-df-k data-df-photo>
								<input type="hidden" name="_df_nonce" value="<?php echo esc_attr( $nonce ); ?>"><input type="hidden" name="order" value="<?php echo (int) $o->get_id(); ?>"><input type="hidden" name="df_act" value="done">
								<label class="cam"><span data-cam-label>📷 Teslim fotoğrafı çek<?php echo df_opt( 'courier_photo_req', 0 ) ? ' (zorunlu)' : ''; ?></span><input type="file" name="photo" accept="image/*" capture="environment"></label>
								<input type="text" name="receiver" placeholder="Teslim alan kişi (ör. kendisi, kapıcı)" autocomplete="off">
								<button class="btn btn--dark">Teslimi onayla</button>
							</form>
						</details>
						<details>
							<summary class="btn">✕ Teslim edilemedi</summary>
							<form method="post" class="panel" data-df-k>
								<input type="hidden" name="_df_nonce" value="<?php echo esc_attr( $nonce ); ?>"><input type="hidden" name="order" value="<?php echo (int) $o->get_id(); ?>"><input type="hidden" name="df_act" value="fail">
								<select name="reason"><option>Adreste kimse yok</option><option>Adres bulunamadı</option><option>Alıcıya ulaşılamadı</option><option>Alıcı teslim almadı</option><option>Diğer</option></select>
								<button class="btn btn--dark">Mağazaya bildir</button>
							</form>
						</details>
					</div>
				<?php endif; ?>
			</article>
		<?php endforeach; ?>
	</main>
	<script>
	( function () {
		// Fotoğraf önizleme ve yüklemeden önce küçültme (mobil veride hızlı).
		document.querySelectorAll( '[data-df-photo] input[type=file]' ).forEach( function ( inp ) {
			inp.addEventListener( 'change', function () {
				var f = inp.files && inp.files[0], lab = inp.parentNode;
				if ( ! f ) { return; }
				var img = new Image();
				img.onload = function () { lab.querySelector( '[data-cam-label]' ).replaceWith( img ); };
				img.src = URL.createObjectURL( f );
			} );
		} );
		function shrink( file ) {
			return new Promise( function ( ok ) {
				if ( ! file || ! /^image\//.test( file.type ) || ! window.createImageBitmap ) { ok( file ); return; }
				createImageBitmap( file ).then( function ( bmp ) {
					var max = 1600, s = Math.min( 1, max / Math.max( bmp.width, bmp.height ) );
					var cv = document.createElement( 'canvas' );
					cv.width = Math.round( bmp.width * s ); cv.height = Math.round( bmp.height * s );
					cv.getContext( '2d' ).drawImage( bmp, 0, 0, cv.width, cv.height );
					cv.toBlob( function ( b ) { ok( b || file ); }, 'image/jpeg', 0.82 );
				} ).catch( function () { ok( file ); } );
			} );
		}
		document.querySelectorAll( '[data-df-k]' ).forEach( function ( form ) {
			form.addEventListener( 'submit', function ( e ) {
				e.preventDefault();
				var fd = new FormData( form );
				fd.set( 'ajax', '1' );
				form.querySelectorAll( 'button' ).forEach( function ( b ) { b.disabled = true; b.textContent = 'Gönderiliyor…'; } );
				var file = form.querySelector( 'input[type=file]' );
				( file && file.files[0] ? shrink( file.files[0] ) : Promise.resolve( null ) ).then( function ( blob ) {
					if ( blob ) { fd.set( 'photo', blob, 'teslim.jpg' ); }
					return fetch( location.href.split( '&m=' )[0], { method: 'POST', body: fd, credentials: 'same-origin' } );
				} ).then( function ( r ) { return r.json(); } ).then( function ( r ) {
					location.href = location.href.split( '&m=' )[0] + '&m=' + encodeURIComponent( r.msg || '' );
				} ).catch( function () { form.submit(); } );
			} );
		} );
	}() );
	</script>
	</body></html>
	<?php
	exit;
}
add_action( 'template_redirect', 'df_courier_screen', 1 );

/**
 * Teslim kanıtı (yönetim sipariş ekranı).
 *
 * @param WC_Order $order Sipariş.
 */
function df_courier_proof_html( $order ) {
	$at = (int) $order->get_meta( '_df_delivered_at' );
	$ph = absint( $order->get_meta( '_df_proof' ) );
	if ( ! $at && ! $ph && ! $order->get_meta( '_df_delivery_fail' ) ) {
		return '';
	}
	$html = '<div class="dfo-proof">';
	if ( $order->get_meta( '_df_delivery_fail' ) && ! $at ) {
		$html .= '<p class="dfo-proof__fail"><strong>Teslim edilemedi:</strong> ' . esc_html( $order->get_meta( '_df_delivery_fail' ) ) . '</p>';
	}
	if ( $at ) {
		$html .= '<p><strong>Teslim edildi:</strong> ' . esc_html( wp_date( 'j F Y H:i', $at ) ) . ( $order->get_meta( '_df_delivered_to' ) ? ' · Teslim alan: ' . esc_html( $order->get_meta( '_df_delivered_to' ) ) : '' ) . ( $order->get_meta( '_df_delivered_by' ) ? ' · Kurye: ' . esc_html( $order->get_meta( '_df_delivered_by' ) ) : '' ) . '</p>';
	}
	if ( $ph ) {
		$html .= '<a href="' . esc_url( wp_get_attachment_url( $ph ) ) . '" target="_blank" rel="noopener">' . wp_get_attachment_image( $ph, 'medium', false, array( 'style' => 'max-width:100%;height:auto;border-radius:10px' ) ) . '</a>';
	}
	return $html . '</div>';
}

/**
 * Kutu kaydı.
 */
function df_courier_proof_box() {
	if ( ! function_exists( 'df_obox_screens' ) ) {
		return;
	}
	foreach ( df_obox_screens() as $screen ) {
		add_meta_box( 'df-proof', 'Teslim & Yazdırma', 'df_courier_proof_render', $screen, 'side', 'high' );
	}
}
add_action( 'add_meta_boxes', 'df_courier_proof_box', 6 );

/**
 * Kutu içeriği.
 *
 * @param WP_Post|WC_Order $post Sipariş.
 */
function df_courier_proof_render( $post ) {
	$order = $post instanceof WC_Order ? $post : wc_get_order( $post->ID );
	$html  = $order ? df_courier_proof_html( $order ) : '';
	echo $html ? $html : '<p style="color:#777">Kurye teslim ettiğinde fotoğraf ve teslim bilgisi burada görünür.</p>'; // phpcs:ignore
	if ( $order && in_array( $order->get_status(), array( 'completed', 'df-archived' ), true ) && function_exists( 'df_review_whatsapp' ) ) {
		$wa = df_review_whatsapp( $order );
		if ( $wa ) {
			echo '<p><a class="button" href="' . esc_url( $wa ) . '" target="_blank" rel="noopener">★ Google yorum daveti (WhatsApp)</a></p>';
		}
	}
	if ( $order && function_exists( 'df_print_history' ) ) {
		$log = df_print_history( $order );
		if ( $log ) {
			echo '<p style="margin-top:12px"><strong>Yazdırma geçmişi</strong><br>' . implode( '<br>', array_map( 'esc_html', $log ) ) . '</p>'; // phpcs:ignore
		}
	}
}

/**
 * Müşteri sipariş takipte teslim fotoğrafı.
 *
 * @param WC_Order $order Sipariş.
 */
function df_courier_proof_tracking( $order ) {
	if ( ! df_opt( 'courier_photo_customer', 0 ) ) {
		return;
	}
	$ph = absint( $order->get_meta( '_df_proof' ) );
	if ( $ph && in_array( $order->get_status(), array( 'completed', 'df-archived' ), true ) ) {
		echo '<figure class="df-track__proof" style="margin:20px 0;text-align:center">' . wp_get_attachment_image( $ph, 'medium', false, array( 'style' => 'border-radius:12px;max-width:100%;height:auto' ) ) . '<figcaption style="font-size:13px;color:var(--df-muted);margin-top:6px">Teslim anı · ' . esc_html( wp_date( 'j F H:i', (int) $order->get_meta( '_df_delivered_at' ) ) ) . '</figcaption></figure>';
	}
}
add_action( 'df_tracking_after_timeline', 'df_courier_proof_tracking' );
