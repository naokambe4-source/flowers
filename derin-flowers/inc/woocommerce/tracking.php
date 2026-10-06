<?php
/**
 * Sipariş takip: [derin_siparis_takip] kısa kodu.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Takip adımları.
 *
 * @param WC_Order $order Sipariş.
 * @return array
 */
function df_tracking_steps( $order ) {
	$pickup = 'pickup' === $order->get_meta( '_df_delivery_type' );
	$status = $order->get_status();
	$steps  = array(
		'received'  => array( 'Sipariş alındı', 'Siparişiniz bize ulaştı.', 'check' ),
		'preparing' => array( 'Hazırlanıyor', 'Floristlerimiz çiçeklerinizi hazırlıyor.', 'hand' ),
		'way'       => $pickup ? array( 'Teslime hazır', 'Mağazamızdan teslim alabilirsiniz.', 'store' ) : array( 'Yolda', 'Kuryemiz yola çıktı.', 'truck' ),
		'done'      => array( 'Teslim edildi', 'Çiçekleriniz sahibine ulaştı.', 'heart' ),
	);
	$map    = array(
		'pending'       => 0,
		'on-hold'       => 0,
		'processing'    => 0,
		'df-preparing'  => 1,
		'df-on-the-way' => 2,
		'completed'     => 3,
		'df-archived'   => 3,
	);
	$current = isset( $map[ $status ] ) ? $map[ $status ] : -1;
	$out     = array();
	$i       = 0;
	foreach ( $steps as $key => $s ) {
		$out[] = array(
			'key'   => $key,
			'title' => $s[0],
			'text'  => $s[1],
			'icon'  => $s[2],
			'state' => $i < $current ? 'done' : ( $i === $current ? 'current' : 'todo' ),
		);
		++$i;
	}
	return $out;
}

/**
 * Siparişi bul ve doğrula.
 *
 * @param string $number  Sipariş no.
 * @param string $contact E-posta veya telefon.
 * @return WC_Order|WP_Error
 */
function df_tracking_find( $number, $contact ) {
	$number  = preg_replace( '/[^0-9]/', '', $number );
	$contact = trim( $contact );
	if ( ! $number || ! $contact ) {
		return new WP_Error( 'empty', 'Lütfen sipariş numaranızı ve e-posta / telefon bilginizi girin.' );
	}
	$order = wc_get_order( absint( $number ) );
	// Sipariş numarası eklentileri (ör. sıralı numaralar) için filtre.
	$order = apply_filters( 'df_tracking_find_order', $order, $number );
	if ( ! $order instanceof WC_Order ) {
		return new WP_Error( 'notfound', 'Bu bilgilerle eşleşen bir sipariş bulunamadı.' );
	}
	$match = false;
	if ( is_email( $contact ) ) {
		$match = 0 === strcasecmp( $order->get_billing_email(), $contact );
	} else {
		$digits = substr( preg_replace( '/\D/', '', $contact ), -10 );
		if ( strlen( $digits ) >= 7 ) {
			foreach ( array( $order->get_billing_phone(), $order->get_meta( '_df_sender_phone' ), $order->get_meta( '_df_recipient_phone' ) ) as $phone ) {
				if ( $phone && substr( preg_replace( '/\D/', '', $phone ), -10 ) === $digits ) {
					$match = true;
					break;
				}
			}
		}
	}
	if ( ! $match ) {
		return new WP_Error( 'notfound', 'Bu bilgilerle eşleşen bir sipariş bulunamadı.' );
	}
	return $order;
}

/**
 * Basit hız sınırlama (IP başına 10 dk'da 15 deneme).
 *
 * @return bool
 */
function df_tracking_rate_ok() {
	$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'x';
	$key = 'df_track_' . md5( $ip );
	$n   = (int) get_transient( $key );
	if ( $n >= 15 ) {
		return false;
	}
	set_transient( $key, $n + 1, 10 * MINUTE_IN_SECONDS );
	return true;
}

/**
 * Kısa kod.
 *
 * @return string
 */
function df_tracking_shortcode() {
	if ( ! df_wc() ) {
		return '';
	}
	$number  = '';
	$contact = '';
	$result  = null;
	if ( isset( $_POST['df_track_nonce'] ) ) {
		$number  = isset( $_POST['df_track_no'] ) ? sanitize_text_field( wp_unslash( $_POST['df_track_no'] ) ) : '';
		$contact = isset( $_POST['df_track_contact'] ) ? sanitize_text_field( wp_unslash( $_POST['df_track_contact'] ) ) : '';
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['df_track_nonce'] ) ), 'df_track' ) ) {
			$result = new WP_Error( 'nonce', 'Oturum süresi doldu, lütfen tekrar deneyin.' );
		} elseif ( ! df_tracking_rate_ok() ) {
			$result = new WP_Error( 'rate', 'Çok fazla deneme yaptınız. Lütfen birkaç dakika sonra tekrar deneyin.' );
		} else {
			$result = df_tracking_find( $number, $contact );
		}
	}

	ob_start();
	?>
	<div class="df-track">
		<div class="df-track__intro">
			<?php df_the_icon( 'package', array( 'size' => 34 ) ); ?>
			<h2<?php echo df_e( 'track_title' ); // phpcs:ignore ?>><?php echo esc_html( df_opt( 'track_title', 'Siparişim Nerede?' ) ); ?></h2>
			<p<?php echo df_e( 'track_text' ); // phpcs:ignore ?>><?php echo esc_html( df_opt( 'track_text' ) ); ?></p>
		</div>
		<form class="df-track__form" method="post" action="">
			<?php wp_nonce_field( 'df_track', 'df_track_nonce' ); ?>
			<div class="df-float">
				<input type="text" id="df_track_no" name="df_track_no" inputmode="numeric" placeholder=" " value="<?php echo esc_attr( $number ); ?>" required>
				<label for="df_track_no">Sipariş numarası</label>
			</div>
			<div class="df-float">
				<input type="text" id="df_track_contact" name="df_track_contact" placeholder=" " value="<?php echo esc_attr( $contact ); ?>" required>
				<label for="df_track_contact">E-posta veya telefon</label>
			</div>
			<button type="submit" class="df-btn df-btn--solid">Siparişi Sorgula</button>
		</form>

		<?php if ( is_wp_error( $result ) ) : ?>
			<div class="df-alert df-alert--error" role="alert"><?php echo esc_html( $result->get_error_message() ); ?></div>
		<?php elseif ( $result instanceof WC_Order ) : ?>
			<?php
			$order    = $result;
			$status   = $order->get_status();
			$canceled = in_array( $status, array( 'cancelled', 'refunded', 'failed' ), true );
			?>
			<div class="df-track__result" aria-live="polite">
				<div class="df-track__head">
					<div>
						<span class="df-eyebrow">Sipariş #<?php echo esc_html( $order->get_order_number() ); ?></span>
						<h3><?php echo esc_html( wc_get_order_status_name( $status ) ); ?></h3>
					</div>
					<div class="df-track__date">
						<?php if ( $order->get_meta( '_df_delivery_date' ) ) : ?>
							<span>Teslimat</span>
							<strong><?php echo esc_html( wp_date( 'j F l', strtotime( $order->get_meta( '_df_delivery_date' ) . ' 12:00:00' ) ) ); ?></strong>
							<small><?php echo esc_html( $order->get_meta( '_df_delivery_slot' ) ); ?></small>
						<?php endif; ?>
					</div>
				</div>
				<?php if ( $canceled ) : ?>
					<div class="df-alert">Bu sipariş <?php echo esc_html( mb_strtolower( wc_get_order_status_name( $status ) ) ); ?> durumunda. Bilgi için bizi arayabilirsiniz: <a href="<?php echo esc_url( df_tel( df_opt( 'contact_phone1' ) ) ); ?>"><?php echo esc_html( df_opt( 'contact_phone1' ) ); ?></a></div>
				<?php else : ?>
					<ol class="df-timeline">
						<?php foreach ( df_tracking_steps( $order ) as $step ) : ?>
							<li class="df-timeline__step is-<?php echo esc_attr( $step['state'] ); ?>">
								<span class="df-timeline__dot"><?php df_the_icon( 'done' === $step['state'] ? 'check' : $step['icon'], array( 'size' => 20 ) ); ?></span>
								<strong><?php echo esc_html( $step['title'] ); ?></strong>
								<span><?php echo esc_html( $step['text'] ); ?></span>
							</li>
						<?php endforeach; ?>
					</ol>
					<?php do_action( 'df_tracking_after_timeline', $order ); ?>
				<?php endif; ?>
				<div class="df-track__items">
					<?php foreach ( $order->get_items() as $item ) : ?>
						<?php $p = $item->get_product(); ?>
						<div class="df-track__item">
							<?php echo $p ? $p->get_image( 'thumbnail' ) : ''; // phpcs:ignore ?>
							<span><?php echo esc_html( $item->get_name() ); ?> × <?php echo (int) $item->get_quantity(); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
				<?php
				$notes = $order->get_customer_order_notes();
				if ( $notes ) :
					?>
					<div class="df-track__notes">
						<h4>Bilgilendirmeler</h4>
						<ul>
							<?php foreach ( $notes as $note ) : ?>
								<li><time><?php echo esc_html( wp_date( 'j M H:i', strtotime( $note->comment_date_gmt . ' UTC' ) ) ); ?></time><?php echo wp_kses_post( wpautop( wptexturize( $note->comment_content ) ) ); ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'derin_siparis_takip', 'df_tracking_shortcode' );
