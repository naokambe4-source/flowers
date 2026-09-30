<?php
/**
 * Sipariş düzenleme ekranında "Çiçek Siparişi" kartı: teslimat günü ve saati, gönderici,
 * alıcı, adres, kart notu; arama / WhatsApp / harita / yazdırma / kurye atama kısayolları.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sipariş ekranı kimlikleri (HPOS + klasik).
 *
 * @return string[]
 */
function df_obox_screens() {
	$screens = array( 'shop_order' );
	if ( function_exists( 'wc_get_page_screen_id' ) ) {
		$screens[] = wc_get_page_screen_id( 'shop-order' );
	}
	return array_unique( $screens );
}

/**
 * Kutu kaydı.
 */
function df_obox_register() {
	foreach ( df_obox_screens() as $screen ) {
		add_meta_box( 'df-flower-order', 'Çiçek Siparişi', 'df_obox_render', $screen, 'normal', 'high' );
	}
}
add_action( 'add_meta_boxes', 'df_obox_register', 5 );

/**
 * Telefon → uluslararası rakamlar (905xxxxxxxxx).
 *
 * @param string $phone Telefon.
 * @return string
 */
function df_obox_phone_digits( $phone ) {
	$d = preg_replace( '/\D/', '', (string) $phone );
	if ( 10 === strlen( $d ) && '5' === $d[0] ) {
		$d = '90' . $d;
	} elseif ( 11 === strlen( $d ) && '0' === $d[0] ) {
		$d = '9' . $d;
	}
	return $d;
}

/**
 * Kişi kartı.
 *
 * @param string $role  Başlık.
 * @param string $icon  Dashicon.
 * @param string $name  Ad.
 * @param string $phone Telefon.
 */
function df_obox_person( $role, $icon, $name, $phone ) {
	$d = df_obox_phone_digits( $phone );
	echo '<div class="dfo-card"><div class="dfo-card__label"><span class="dashicons ' . esc_attr( $icon ) . '"></span>' . esc_html( $role ) . '</div>';
	echo '<div class="dfo-card__name">' . esc_html( $name ? $name : '—' ) . '</div>';
	if ( $phone ) {
		echo '<div class="dfo-card__phone">' . esc_html( $phone ) . '</div><div class="dfo-card__acts">';
		echo '<a href="tel:+' . esc_attr( $d ) . '"><span class="dashicons dashicons-phone"></span>Ara</a>';
		echo '<a href="https://wa.me/' . esc_attr( $d ) . '" target="_blank" rel="noopener"><span class="dashicons dashicons-whatsapp"></span>WhatsApp</a>';
		echo '</div>';
	}
	echo '</div>';
}

/**
 * Kutu içeriği.
 *
 * @param WP_Post|WC_Order $post_or_order Sipariş.
 */
function df_obox_render( $post_or_order ) {
	$order = $post_or_order instanceof WC_Order ? $post_or_order : wc_get_order( $post_or_order->ID );
	if ( ! $order ) {
		return;
	}
	if ( ! $order->get_meta( '_df_delivery_type' ) ) {
		echo '<p class="dfo-empty">Bu siparişte çiçek teslimat bilgisi yok (tema kurulmadan önce ya da elle oluşturulmuş olabilir).</p>';
		return;
	}
	$pickup  = 'pickup' === $order->get_meta( '_df_delivery_type' );
	$date    = (string) $order->get_meta( '_df_delivery_date' );
	$ts      = $date ? strtotime( $date . ' 12:00:00' ) : 0;
	$today   = wp_date( 'Y-m-d' );
	$diff    = $ts ? (int) round( ( strtotime( $date ) - strtotime( $today ) ) / DAY_IN_SECONDS ) : null;
	$when    = null === $diff ? '' : ( 0 === $diff ? 'Bugün' : ( 1 === $diff ? 'Yarın' : ( $diff < 0 ? abs( $diff ) . ' gün önce' : $diff . ' gün sonra' ) ) );
	$printed = $order->get_meta( '_df_printed' );
	$courier = (string) $order->get_meta( '_df_courier' );
	$note    = (string) $order->get_meta( '_df_note' );
	$from    = 'yes' === $order->get_meta( '_df_note_anon' ) ? 'İsimsiz' : (string) $order->get_meta( '_df_note_from' );
	$address = (string) $order->get_meta( '_df_address' );
	$region  = (string) $order->get_meta( '_df_district' );
	$print   = wp_nonce_url( admin_url( 'admin-post.php?action=df_print_bulk&type=both&ids[]=' . $order->get_id() ), 'df_print_bulk' );
	$slip    = wp_nonce_url( admin_url( 'admin-post.php?action=df_print_bulk&type=slip&ids[]=' . $order->get_id() ), 'df_print_bulk' );
	$card    = wp_nonce_url( admin_url( 'admin-post.php?action=df_print_card&order=' . $order->get_id() ), 'df_print_card_' . $order->get_id() );
	?>
	<div class="dfo">
		<div class="dfo-hero<?php echo 0 === $diff ? ' is-today' : ( null !== $diff && $diff < 0 ? ' is-past' : '' ); ?>">
			<div class="dfo-hero__date">
				<?php if ( $ts ) : ?>
					<span class="dfo-hero__day"><?php echo esc_html( wp_date( 'j', $ts ) ); ?></span>
					<span class="dfo-hero__month"><?php echo esc_html( wp_date( 'F', $ts ) ); ?><small><?php echo esc_html( wp_date( 'l', $ts ) ); ?></small></span>
				<?php endif; ?>
			</div>
			<div class="dfo-hero__main">
				<div class="dfo-hero__slot"><span class="dashicons dashicons-clock"></span><?php echo esc_html( $order->get_meta( '_df_delivery_slot' ) ? $order->get_meta( '_df_delivery_slot' ) : 'Saat seçilmedi' ); ?></div>
				<div class="dfo-chips">
					<?php if ( $when ) : ?>
						<span class="dfo-chip dfo-chip--when"><?php echo esc_html( $when ); ?></span>
					<?php endif; ?>
					<span class="dfo-chip"><span class="dashicons <?php echo $pickup ? 'dashicons-store' : 'dashicons-location'; ?>"></span><?php echo $pickup ? 'Mağazadan teslim' : 'Adrese teslim'; ?></span>
					<?php if ( ! $pickup && $region ) : ?>
						<span class="dfo-chip"><?php echo esc_html( $region ); ?></span>
					<?php endif; ?>
					<span class="dfo-chip <?php echo $printed ? 'is-ok' : 'is-warn'; ?>"><span class="dashicons dashicons-printer"></span><?php echo $printed ? 'Yazdırıldı' : 'Yazdırılmadı'; ?></span>
				</div>
			</div>
			<div class="dfo-hero__acts">
				<a class="dfo-btn" href="<?php echo esc_url( $print ); ?>" target="_blank"><span class="dashicons dashicons-printer"></span>Kart + fiş yazdır</a>
				<a class="dfo-btn dfo-btn--ghost" href="<?php echo esc_url( $slip ); ?>" target="_blank">Sadece fiş</a>
				<?php if ( $note ) : ?>
					<a class="dfo-btn dfo-btn--ghost" href="<?php echo esc_url( $card ); ?>" target="_blank">Sadece kart</a>
				<?php endif; ?>
			</div>
		</div>

		<div class="dfo-grid">
			<?php
			df_obox_person( 'Gönderici', 'dashicons-admin-users', (string) $order->get_meta( '_df_sender_name' ), (string) $order->get_meta( '_df_sender_phone' ) );
			df_obox_person( 'Alıcı', 'dashicons-heart', (string) $order->get_meta( '_df_recipient_name' ), (string) $order->get_meta( '_df_recipient_phone' ) );
			?>
			<div class="dfo-card dfo-card--wide">
				<div class="dfo-card__label"><span class="dashicons <?php echo $pickup ? 'dashicons-store' : 'dashicons-location-alt'; ?>"></span><?php echo $pickup ? 'Teslim alınacak mağaza' : 'Teslimat adresi'; ?></div>
				<?php if ( $pickup ) : ?>
					<div class="dfo-card__addr"><?php echo esc_html( $order->get_meta( '_df_store' ) ); ?></div>
				<?php else : ?>
					<div class="dfo-card__addr"><?php echo esc_html( $address ); ?></div>
					<?php if ( $region ) : ?>
						<div class="dfo-card__sub"><?php echo esc_html( $region ); ?></div>
					<?php endif; ?>
					<?php if ( $order->get_meta( '_df_address_hint' ) ) : ?>
						<div class="dfo-card__hint"><span class="dashicons dashicons-flag"></span><?php echo esc_html( $order->get_meta( '_df_address_hint' ) ); ?></div>
					<?php endif; ?>
					<div class="dfo-card__acts">
						<a href="<?php echo esc_url( 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $address . ' ' . str_replace( ' — ', ' ', $region ) ) ); ?>" target="_blank" rel="noopener"><span class="dashicons dashicons-location"></span>Haritada aç</a>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<div class="dfo-grid dfo-grid--2">
			<div class="dfo-note">
				<div class="dfo-card__label"><span class="dashicons dashicons-email-alt"></span>Kart notu<?php echo $order->get_meta( '_df_note_cat' ) ? ' · ' . esc_html( $order->get_meta( '_df_note_cat' ) ) : ''; ?></div>
				<?php if ( $note ) : ?>
					<blockquote><?php echo nl2br( esc_html( $note ) ); ?></blockquote>
					<?php if ( $from ) : ?>
						<div class="dfo-note__from">— <?php echo esc_html( $from ); ?></div>
					<?php endif; ?>
				<?php else : ?>
					<p class="dfo-muted">Kart notu yazılmamış.</p>
				<?php endif; ?>
			</div>
			<?php if ( ! $pickup ) : ?>
				<div class="dfo-card dfo-courier" data-order="<?php echo (int) $order->get_id(); ?>">
					<div class="dfo-card__label"><span class="dashicons dashicons-car"></span>Kurye</div>
					<?php $list = function_exists( 'df_app_couriers_list' ) ? df_app_couriers_list() : array(); ?>
					<?php if ( $list ) : ?>
						<select class="dfo-courier__select">
							<option value="">— Kurye seçin —</option>
							<?php foreach ( $list as $i => $c ) : ?>
								<option value="<?php echo (int) $i; ?>" <?php selected( $courier, $c['name'] . '|' . $c['phone'] ); ?>><?php echo esc_html( $c['name'] ); ?></option>
							<?php endforeach; ?>
						</select>
						<?php
						$wa = '';
						if ( $courier && function_exists( 'df_app_courier_whatsapp' ) ) {
							$parts = explode( '|', $courier );
							$wa    = ! empty( $parts[1] ) ? df_app_courier_whatsapp( $order, $parts[1] ) : '';
						}
						?>
						<a class="dfo-btn dfo-btn--wa" data-wa href="<?php echo esc_url( $wa ? $wa : '#' ); ?>" target="_blank" rel="noopener"<?php echo $wa ? '' : ' hidden'; ?>><span class="dashicons dashicons-whatsapp"></span>Adresi kuryeye gönder</a>
						<span class="dfo-courier__saved" hidden>Kaydedildi ✓</span>
					<?php else : ?>
						<p class="dfo-muted">Henüz kurye eklenmemiş. <a href="<?php echo esc_url( admin_url( 'admin.php?page=derin-flowers#delivery' ) ); ?>">Kurye ekle →</a></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php if ( $order->get_customer_note() ) : ?>
			<div class="dfo-customer-note"><span class="dashicons dashicons-format-chat"></span><div><strong>Müşteri notu</strong><?php echo nl2br( esc_html( $order->get_customer_note() ) ); ?></div></div>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Stil ve kurye betiği (yalnızca sipariş ekranında).
 */
function df_obox_assets() {
	$screen = get_current_screen();
	if ( ! $screen || ! in_array( $screen->id, df_obox_screens(), true ) ) {
		return;
	}
	wp_enqueue_style( 'df-order-box', DF_URI . '/assets/admin/order-box.css', array(), DF_VERSION );
	wp_enqueue_script( 'jquery' );
	wp_add_inline_script(
		'jquery',
		'jQuery(function($){$(document).on("change",".dfo-courier__select",function(){var $b=$(this).closest(".dfo-courier"),$s=$(this);$s.prop("disabled",true);$.post(ajaxurl,{action:"df_assign_courier",nonce:' . wp_json_encode( wp_create_nonce( 'df_app' ) ) . ',order:$b.data("order"),courier:$s.val()}).done(function(r){if(r&&r.success){var $w=$b.find("[data-wa]");if(r.data.wa){$w.attr("href",r.data.wa).prop("hidden",false);}else{$w.prop("hidden",true);}$b.find(".dfo-courier__saved").prop("hidden",false).delay(1500).queue(function(n){$(this).prop("hidden",true);n();});}}).always(function(){$s.prop("disabled",false);});});});'
	);
}
add_action( 'admin_enqueue_scripts', 'df_obox_assets' );
