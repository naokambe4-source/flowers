<?php
/**
 * Havale / EFT: sipariş sonunda banka bilgileri sayfanın en üstünde, tek dokunuşla kopyalanır
 * (IBAN, alıcı adı, tutar, açıklama). Dekontu WhatsApp'tan gönderme düğmesi.
 * Ayrıca sipariş sonu adresleri Türkçe: /odeme/siparis-alindi/…
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Teşekkür sayfasının üstünde banka kartı.
 *
 * @param int $order_id Sipariş.
 */
function df_bacs_card( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order || 'bacs' !== $order->get_payment_method() || ! $order->has_status( array( 'on-hold', 'pending' ) ) ) {
		return;
	}
	$accounts = array_filter(
		(array) get_option( 'woocommerce_bacs_accounts', array() ),
		function ( $a ) {
			return ! empty( $a['iban'] ) || ! empty( $a['account_number'] );
		}
	);
	// WooCommerce'in sayfa altındaki standart banka listesini kaldır (aynı bilgi üstte).
	$gateways = WC()->payment_gateways() ? WC()->payment_gateways()->payment_gateways() : array();
	if ( $accounts && isset( $gateways['bacs'] ) ) {
		remove_action( 'woocommerce_thankyou_bacs', array( $gateways['bacs'], 'thankyou_page' ) );
	}
	$total = wp_strip_all_tags( html_entity_decode( wc_price( $order->get_total(), array( 'currency' => $order->get_currency() ) ), ENT_QUOTES, 'UTF-8' ) );
	$desc  = 'Sipariş ' . $order->get_order_number();
	$wa    = preg_replace( '/\D+/', '', (string) df_opt( 'contact_whatsapp', '' ) );
	$copy  = function ( $label, $value, $show = null, $big = false ) {
		$show = null === $show ? $value : $show;
		echo '<div class="df-bacs__row' . ( $big ? ' is-big' : '' ) . '"><span class="df-bacs__k">' . esc_html( $label ) . '</span>';
		echo '<span class="df-bacs__v">' . esc_html( $show ) . '</span>';
		echo '<button type="button" class="df-bacs__copy" data-df-copy="' . esc_attr( $value ) . '">' . df_icon( 'copy', array( 'size' => 16 ) ) . '<span>Kopyala</span></button></div>'; // phpcs:ignore
	};
	?>
	<section class="df-bacs" aria-labelledby="df-bacs-title">
		<div class="df-bacs__head">
			<h2 id="df-bacs-title">Ödemenizi havale / EFT ile tamamlayın</h2>
			<p>Siparişiniz alındı ve ödemeniz bekleniyor. Aşağıdaki bilgileri tek dokunuşla kopyalayıp bankanızın uygulamasına yapıştırabilirsiniz. Ödeme hesabımıza geçince siparişiniz hazırlanmaya başlar.</p>
		</div>
		<?php if ( ! $accounts ) : ?>
			<p class="df-bacs__empty">Banka bilgilerimiz için bizi arayın veya WhatsApp'tan yazın.</p>
		<?php endif; ?>
		<?php foreach ( $accounts as $a ) : ?>
			<?php $iban = strtoupper( preg_replace( '/\s+/', '', (string) ( ! empty( $a['iban'] ) ? $a['iban'] : $a['account_number'] ) ) ); ?>
			<div class="df-bacs__acc">
				<?php if ( ! empty( $a['bank_name'] ) ) : ?>
					<div class="df-bacs__bank"><?php echo esc_html( $a['bank_name'] ); ?></div>
				<?php endif; ?>
				<?php $copy( ! empty( $a['iban'] ) ? 'IBAN' : 'Hesap no', $iban, trim( chunk_split( $iban, 4, ' ' ) ), true ); ?>
				<?php if ( ! empty( $a['account_name'] ) ) : ?>
					<?php $copy( 'Alıcı adı', $a['account_name'] ); ?>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
		<div class="df-bacs__acc df-bacs__acc--pay">
			<?php $copy( 'Tutar', $total ); ?>
			<?php $copy( 'Açıklama', $desc ); ?>
		</div>
		<p class="df-bacs__note">Açıklamaya <strong><?php echo esc_html( $desc ); ?></strong> yazmanız ödemenizi hızlı eşleştirmemizi sağlar.</p>
		<?php if ( $wa ) : ?>
			<a class="df-bacs__wa" href="<?php echo esc_url( 'https://wa.me/' . $wa . '?text=' . rawurlencode( 'Merhaba, ' . $desc . ' için havale dekontumu gönderiyorum.' ) ); ?>" target="_blank" rel="noopener"><?php df_the_icon( 'whatsapp', array( 'size' => 18 ) ); ?> Dekontu WhatsApp'tan gönder</a>
		<?php endif; ?>
	</section>
	<script>
	( function () {
		function done( b ) { var s = b.querySelector( 'span' ); b.classList.add( 'is-done' ); s.textContent = 'Kopyalandı'; setTimeout( function () { b.classList.remove( 'is-done' ); s.textContent = 'Kopyala'; }, 1800 ); }
		document.querySelectorAll( '[data-df-copy]' ).forEach( function ( b ) {
			b.addEventListener( 'click', function () {
				var t = b.getAttribute( 'data-df-copy' );
				if ( navigator.clipboard && window.isSecureContext ) { navigator.clipboard.writeText( t ).then( function () { done( b ); } ); return; }
				var a = document.createElement( 'textarea' ); a.value = t; a.setAttribute( 'readonly', '' ); a.style.position = 'fixed'; a.style.opacity = '0';
				document.body.appendChild( a ); a.select(); try { document.execCommand( 'copy' ); done( b ); } catch ( e ) {} document.body.removeChild( a );
			} );
		} );
	}() );
	</script>
	<?php
}
add_action( 'woocommerce_before_thankyou', 'df_bacs_card', 5 );

/**
 * Sipariş sonu / ödeme adreslerini Türkçeye çevir (bir kez; panelden sonra değiştirilebilir).
 */
function df_tr_checkout_endpoints() {
	if ( get_option( 'df_tr_endpoints' ) || ! class_exists( 'WooCommerce' ) ) {
		return;
	}
	$map = array(
		'woocommerce_checkout_order_received_endpoint' => array( 'order-received', 'siparis-alindi' ),
		'woocommerce_checkout_pay_endpoint'            => array( 'order-pay', 'siparis-ode' ),
	);
	foreach ( $map as $opt => $v ) {
		if ( in_array( get_option( $opt, $v[0] ), array( '', $v[0] ), true ) ) {
			update_option( $opt, $v[1] );
		}
	}
	update_option( 'df_tr_endpoints', 1, false );
	update_option( 'woocommerce_queue_flush_rewrite_rules', 'yes' );
}
add_action( 'admin_init', 'df_tr_checkout_endpoints' );
