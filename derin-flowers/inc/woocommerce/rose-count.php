<?php
/**
 * "Derin Flowers — Özel Gül Adedi" eklentisiyle uyum.
 *
 * Eklenti kendi kutusunu ana sipariş formunun dışında, ayrı bir form olarak basar; o yoldan sepete
 * eklenen siparişte teslimat tarihi, alıcı ve kart notu adımları atlanır. Tema bu kutuyu kaldırır ve
 * özel adet alanını ana formun içine (Adet seçiminin altına) koyar. Fiyat, sepet ve sipariş satırı
 * yine eklentinin sunucu tarafı hesabıyla yürür (sepet satırına aynı `df_rose` verisi yazılır).
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Eklentinin çalışan nesnesi (kancalardan bulunur).
 *
 * @return object|null
 */
function df_rose_plugin() {
	static $inst = false;
	if ( false !== $inst ) {
		return $inst;
	}
	$inst = null;
	global $wp_filter;
	if ( class_exists( 'DF_Rose_Count' ) && isset( $wp_filter['woocommerce_after_add_to_cart_form'] ) ) {
		foreach ( $wp_filter['woocommerce_after_add_to_cart_form']->callbacks as $cbs ) {
			foreach ( $cbs as $cb ) {
				if ( is_array( $cb['function'] ) && $cb['function'][0] instanceof DF_Rose_Count ) {
					$inst = $cb['function'][0];
					break 2;
				}
			}
		}
	}
	return $inst;
}

/**
 * Üründe özel adet açık mı?
 *
 * @param WC_Product|null $product Ürün.
 * @return bool
 */
function df_rose_on( $product ) {
	return $product instanceof WC_Product && df_rose_plugin() && 'yes' === $product->get_meta( '_df_rose_enabled' ) && (float) $product->get_meta( '_df_rose_unit' ) > 0 && $product->is_type( array( 'simple', 'variable' ) );
}

/**
 * Eklentinin ayrı formunu kaldır.
 */
function df_rose_unhook() {
	$inst = df_rose_plugin();
	if ( $inst ) {
		remove_action( 'woocommerce_after_add_to_cart_form', array( $inst, 'form' ) );
	}
}
add_action( 'wp', 'df_rose_unhook' );

/**
 * Ana formun içinde özel adet alanı.
 */
function df_rose_field() {
	global $product;
	if ( ! df_rose_on( $product ) ) {
		return;
	}
	$min = max( 1, (int) ( $product->get_meta( '_df_rose_min' ) ? $product->get_meta( '_df_rose_min' ) : 10 ) );
	$max = max( $min, (int) ( $product->get_meta( '_df_rose_max' ) ? $product->get_meta( '_df_rose_max' ) : 151 ) );
	?>
	<div class="df-rosec" data-df-rose data-endpoint="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" data-product="<?php echo (int) $product->get_id(); ?>" data-variable="<?php echo $product->is_type( 'variable' ) ? '1' : '0'; ?>">
		<label class="df-rosec__toggle">
			<input type="checkbox" data-df-rose-on>
			<span><?php df_the_icon( 'flower', array( 'size' => 18 ) ); ?> Farklı bir gül adedi istiyorum</span>
		</label>
		<div class="df-rosec__body" hidden>
			<label class="df-rosec__label" for="df_rose_count">Kaç gül olsun? <small>(<?php echo (int) $min; ?>–<?php echo (int) $max; ?> arası)</small></label>
			<div class="df-rosec__row">
				<button type="button" class="df-rosec__step" data-df-rose-step="-1" aria-label="Azalt">−</button>
				<input type="number" id="df_rose_count" name="df_rose_count" min="<?php echo (int) $min; ?>" max="<?php echo (int) $max; ?>" step="1" inputmode="numeric" placeholder="Örn. 35" disabled>
				<button type="button" class="df-rosec__step" data-df-rose-step="1" aria-label="Artır">+</button>
				<output class="df-rosec__price" data-df-rose-out aria-live="polite"></output>
			</div>
			<p class="df-rosec__hint"><?php echo $product->is_type( 'variable' ) ? 'Renk ve tasarım yukarıda seçtiğiniz paketten alınır; buket yazdığınız adet kadar gülle hazırlanır.' : 'Buket yazdığınız adet kadar gülle hazırlanır.'; ?> Ambalaj ücretsizdir.</p>
		</div>
	</div>
	<script>
	( function () {
		var box = document.currentScript.previousElementSibling, form = box.closest( 'form' );
		if ( ! form ) { return; }
		var on = box.querySelector( '[data-df-rose-on]' ), body = box.querySelector( '.df-rosec__body' ), input = box.querySelector( 'input[type=number]' ), out = box.querySelector( '[data-df-rose-out]' ), timer, seq = 0;
		function base() { var v = form.querySelector( 'input[name=variation_id]' ); return v ? v.value : '0'; }
		function quote() {
			var my = ++seq; clearTimeout( timer );
			if ( ! on.checked ) { out.textContent = ''; return; }
			if ( '1' === box.dataset.variable && ( ! base() || '0' === base() ) ) { out.textContent = 'Önce yukarıdan tasarımı seçin.'; return; }
			if ( ! input.value || ! input.checkValidity() ) { out.textContent = 'Adet: ' + input.min + '–' + input.max; return; }
			out.textContent = 'Hesaplanıyor…';
			timer = setTimeout( function () {
				var u = new URL( box.dataset.endpoint );
				u.search = new URLSearchParams( { action: 'df_rose_quote', product: box.dataset.product, base: base(), count: input.value } ).toString();
				fetch( u, { credentials: 'same-origin', cache: 'no-store' } ).then( function ( r ) { return r.json(); } ).then( function ( r ) {
					if ( my !== seq ) { return; }
					out.innerHTML = r.success ? r.data.html : ( r.data && r.data.message ? r.data.message : 'Fiyat hesaplanamadı.' );
				} ).catch( function () { if ( my === seq ) { out.textContent = 'Bağlantı kurulamadı.'; } } );
			}, 300 );
		}
		on.addEventListener( 'change', function () {
			body.hidden = ! on.checked; input.disabled = ! on.checked; input.required = on.checked;
			box.classList.toggle( 'is-on', on.checked );
			form.classList.toggle( 'df-rose-active', on.checked );
			if ( on.checked ) { input.focus(); }
			quote();
		} );
		box.querySelectorAll( '[data-df-rose-step]' ).forEach( function ( b ) {
			b.addEventListener( 'click', function () {
				var v = parseInt( input.value || input.min, 10 ) + parseInt( b.dataset.dfRoseStep, 10 );
				input.value = Math.min( parseInt( input.max, 10 ), Math.max( parseInt( input.min, 10 ), v ) ); quote();
			} );
		} );
		input.addEventListener( 'input', quote );
		if ( window.jQuery ) { window.jQuery( form ).on( 'found_variation reset_data', quote ); }
	}() );
	</script>
	<?php
}
add_action( 'woocommerce_before_add_to_cart_button', 'df_rose_field', 3 );

/**
 * Sepete eklerken özel adeti doğrula (fiyat eklentinin sunucu hesabından).
 *
 * @param bool $passed     Geçti mi.
 * @param int  $product_id Ürün.
 * @return bool
 */
function df_rose_validate( $passed, $product_id ) {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- WooCommerce sepete ekleme akışı.
	if ( ! $passed || empty( $_POST['df_rose_count'] ) || ! df_rose_plugin() ) {
		return $passed;
	}
	try {
		df_rose_plugin()->quote( absint( $product_id ), isset( $_POST['variation_id'] ) ? absint( $_POST['variation_id'] ) : 0, wc_clean( wp_unslash( $_POST['df_rose_count'] ) ) );
	} catch ( Exception $e ) {
		wc_add_notice( $e->getMessage(), 'error' );
		return false;
	}
	// phpcs:enable
	return $passed;
}
add_filter( 'woocommerce_add_to_cart_validation', 'df_rose_validate', 4, 2 );

/**
 * Sepet satırına eklentinin beklediği veriyi yaz.
 *
 * @param array $data         Satır verisi.
 * @param int   $product_id   Ürün.
 * @param int   $variation_id Varyasyon.
 * @return array
 */
function df_rose_cart_data( $data, $product_id, $variation_id ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( empty( $_POST['df_rose_count'] ) || ! df_rose_plugin() ) {
		return $data;
	}
	$data['df_rose'] = array(
		'count'  => absint( $_POST['df_rose_count'] ), // phpcs:ignore WordPress.Security.NonceVerification.Missing
		'base'   => (int) $variation_id,
		'parent' => (int) $product_id,
	);
	return $data;
}
add_filter( 'woocommerce_add_cart_item_data', 'df_rose_cart_data', 10, 3 );
