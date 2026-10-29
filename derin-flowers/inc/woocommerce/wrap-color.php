<?php
/**
 * Ürün sayfasında ambalaj rengi seçimi (renkli yuvarlak düğmeler). Seçilen renk sepette,
 * siparişte ve fişte "Ambalaj rengi" olarak görünür. Renkler Tema Ayarları'ndan değiştirilir.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renkler: ad => hex.
 *
 * @return array<string,string>
 */
function df_wrap_colors() {
	$out = array();
	foreach ( preg_split( '/\r\n|\r|\n/', (string) df_opt( 'wrap_colors', "Beyaz | #FFFFFF\nPembe | #F4A7C0\nSiyah | #1F1B1A\nAdaçayı | #9CAF88\nFuşya | #D6246E\nSarı | #F5C842\nMavi | #6FA8DC" ) ) as $line ) {
		$p = array_map( 'trim', explode( '|', $line ) );
		if ( '' !== $p[0] ) {
			$hex          = isset( $p[1] ) ? sanitize_hex_color( $p[1] ) : '';
			$out[ $p[0] ] = $hex ? $hex : '#DDDDDD';
		}
	}
	return $out;
}

/**
 * Bu üründe gösterilsin mi? (Hediye ürünlerinde gösterilmez.)
 *
 * @param WC_Product $product Ürün.
 * @return bool
 */
function df_wrap_on( $product ) {
	if ( ! df_opt( 'wrap_on', 1 ) || ! $product instanceof WC_Product || 'no' === $product->get_meta( '_df_wrap' ) ) {
		return false;
	}
	$skip = array_filter( array_map( 'trim', explode( ',', (string) df_opt( 'wrap_skip_cats', 'hediyeler' ) ) ) );
	return ! ( $skip && has_term( $skip, 'product_cat', $product->get_id() ) ) && df_wrap_colors();
}

/**
 * Seçim alanı.
 */
function df_wrap_field() {
	global $product;
	if ( ! df_wrap_on( $product ) ) {
		return;
	}
	$req = (bool) df_opt( 'wrap_required', 0 );
	?>
	<fieldset class="df-wrap">
		<legend class="df-wrap__title">Ambalaj rengi <?php echo $req ? '<abbr title="zorunlu">*</abbr>' : '<small>(isteğe bağlı)</small>'; ?> <span class="df-wrap__name" data-df-wrap-name></span></legend>
		<div class="df-wrap__list">
			<?php foreach ( df_wrap_colors() as $name => $hex ) : ?>
				<label class="df-wrap__opt" title="<?php echo esc_attr( $name ); ?>">
					<input type="radio" name="df_wrap" value="<?php echo esc_attr( $name ); ?>"<?php echo $req ? ' required' : ''; ?>>
					<span class="df-wrap__dot" style="--c:<?php echo esc_attr( $hex ); ?>"></span>
					<span class="df-wrap__label"><?php echo esc_html( $name ); ?></span>
				</label>
			<?php endforeach; ?>
		</div>
	</fieldset>
	<script>
	( function () {
		var box = document.currentScript.previousElementSibling, out = box.querySelector( '[data-df-wrap-name]' );
		box.addEventListener( 'change', function ( e ) { if ( e.target.name === 'df_wrap' ) { out.textContent = '· ' + e.target.value; } } );
	}() );
	</script>
	<?php
}
add_action( 'woocommerce_before_add_to_cart_button', 'df_wrap_field', 1 );

/**
 * Zorunluysa kontrol.
 *
 * @param bool $passed Geçti mi.
 * @param int  $pid    Ürün.
 * @return bool
 */
function df_wrap_validate( $passed, $pid ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( $passed && df_opt( 'wrap_required', 0 ) && empty( $_POST['df_wrap'] ) && df_wrap_on( wc_get_product( $pid ) ) ) {
		wc_add_notice( 'Lütfen ambalaj rengini seçin.', 'error' );
		return false;
	}
	return $passed;
}
add_filter( 'woocommerce_add_to_cart_validation', 'df_wrap_validate', 3, 2 );

/**
 * Sepet satırı.
 *
 * @param array $data Veri.
 * @return array
 */
function df_wrap_cart_data( $data ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	$v = isset( $_POST['df_wrap'] ) ? sanitize_text_field( wp_unslash( $_POST['df_wrap'] ) ) : '';
	if ( '' !== $v && isset( df_wrap_colors()[ $v ] ) ) {
		$data['df_wrap'] = $v;
	}
	return $data;
}
add_filter( 'woocommerce_add_cart_item_data', 'df_wrap_cart_data', 11 );

/**
 * Sepette göster.
 *
 * @param array $d    Satırlar.
 * @param array $item Sepet satırı.
 * @return array
 */
function df_wrap_item_data( $d, $item ) {
	if ( ! empty( $item['df_wrap'] ) ) {
		$d[] = array( 'key' => 'Ambalaj rengi', 'value' => $item['df_wrap'] );
	}
	return $d;
}
add_filter( 'woocommerce_get_item_data', 'df_wrap_item_data', 10, 2 );

/**
 * Siparişe yaz.
 *
 * @param WC_Order_Item_Product $line Satır.
 * @param string                $key  Anahtar.
 * @param array                 $item Sepet satırı.
 */
function df_wrap_order_item( $line, $key, $item ) {
	if ( ! empty( $item['df_wrap'] ) ) {
		$line->add_meta_data( 'Ambalaj rengi', $item['df_wrap'], true );
	}
}
add_action( 'woocommerce_checkout_create_order_line_item', 'df_wrap_order_item', 11, 3 );
