<?php
/**
 * Çiçekçi ödeme sayfası.
 *
 * 1 Teslimat (tür, takvim, saat) · 2 Gönderici · 3 Alıcı & adres · 4 Çiçek notu · 5 Ödeme | Sipariş özeti
 *
 * @package DerinFlowers
 * @version 9.4.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'df_checkout_enabled' ) || ! df_checkout_enabled() ) {
	// Akış kapalıysa WooCommerce'in varsayılan şablonu.
	include WC()->plugin_path() . '/templates/checkout/form-checkout.php';
	return;
}

do_action( 'woocommerce_before_checkout_form', $checkout );

if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', 'Ödeme yapabilmek için giriş yapmalısınız.' ) );
	return;
}

?>

<form name="checkout" method="post" class="checkout woocommerce-checkout df-checkout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data" aria-label="Ödeme" novalidate>
	<input type="hidden" id="billing_country" value="TR">

	<div class="df-checkout__grid">
		<div class="df-checkout__main">

			<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

			<div id="customer_details" class="df-checkout__steps">

				<?php get_template_part( 'template-parts/checkout/delivery-fields', null, array( 'context' => 'checkout' ) ); ?>
			</div>

			<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>

			<?php /* 5 — Ödeme */ ?>
			<section class="df-step df-step--payment" id="df-step-payment">
				<h2 class="df-step__title"><span class="df-step__num">5</span>Ödeme</h2>
				<?php woocommerce_checkout_payment(); ?>
			</section>
		</div>

		<aside class="df-checkout__aside">
			<div class="df-summary">
				<button type="button" class="df-summary__toggle" data-df-summary-toggle aria-expanded="false">
					<span><?php df_the_icon( 'bag', array( 'size' => 20 ) ); ?>Sipariş özeti</span>
					<strong data-df-summary-total><?php wc_cart_totals_order_total_html(); ?></strong>
				</button>
				<h3 id="order_review_heading" class="df-summary__title">Sipariş Özeti</h3>
				<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>
				<div id="order_review" class="woocommerce-checkout-review-order">
					<?php do_action( 'woocommerce_checkout_order_review' ); ?>
				</div>
				<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>
				<ul class="df-summary__trust">
					<li><?php df_the_icon( 'lock', array( 'size' => 18 ) ); ?>256-bit SSL ile korunan ödeme</li>
					<li><?php df_the_icon( 'shield', array( 'size' => 18 ) ); ?>3D Secure güvenli ödeme</li>
					<li><?php df_the_icon( 'leaf', array( 'size' => 18 ) ); ?>Günlük taze çiçeklerle hazırlanır</li>
				</ul>
				<?php if ( df_opt( 'contact_phone1' ) ) : ?>
					<p class="df-summary__help">Yardım mı lazım? <a href="<?php echo esc_url( df_tel( df_opt( 'contact_phone1' ) ) ); ?>"><?php echo esc_html( df_opt( 'contact_phone1' ) ); ?></a></p>
				<?php endif; ?>
			</div>
		</aside>
	</div>
</form>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
