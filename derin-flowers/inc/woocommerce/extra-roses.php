<?php
/**
 * Gül adedi:
 * 1. Kategori / vitrin kartlarında adet seçimi (11 · 21 · 51 · 101): kart fiyatı değişir, ürüne o
 *    adet seçili gidilir.
 * 2. Ürün detayında "Ek gül ekle": seçilen paketin üstüne +N gül (örn. 101 + 50 = 151 gül).
 *    Fiyat = paket fiyatı + ek gül × birim fiyat. Birim fiyat ürüne / ayarlara girilir; boşsa
 *    seçilen paketin gül başı fiyatı kullanılır. Fiyat her zaman sunucuda hesaplanır.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Ürünün "adet" niteliği (Adet, Gül adedi, Gül sayısı…).
 *
 * @param WC_Product $product Ürün.
 * @return array{name:string,key:string,options:array}|null
 */
function df_qty_attr( $product ) {
	if ( ! $product instanceof WC_Product || ! $product->is_type( 'variable' ) ) {
		return null;
	}
	foreach ( $product->get_variation_attributes() as $name => $options ) {
		$label = wc_attribute_label( $name, $product );
		if ( preg_match( '/adet|gül|gul|sayı|sayi|quantity|stems?/iu', $name . ' ' . $label ) ) {
			return array(
				'name'    => $name,
				'key'     => 'attribute_' . sanitize_title( $name ),
				'options' => array_values( (array) $options ),
			);
		}
	}
	return null;
}

/**
 * Seçenek değerinin görünen adı.
 *
 * @param string $attr  Nitelik.
 * @param string $value Değer.
 * @return string
 */
function df_qty_label( $attr, $value ) {
	if ( taxonomy_exists( $attr ) ) {
		$t = get_term_by( 'slug', $value, $attr );
		return $t ? $t->name : $value;
	}
	return $value;
}

/**
 * Metindeki gül sayısı ("101 Gül" → 101).
 *
 * @param string $s Metin.
 * @return int
 */
function df_qty_number( $s ) {
	return preg_match( '/(\d{1,4})/', (string) $s, $m ) ? (int) $m[1] : 0;
}

/**
 * Karttaki adet seçimi.
 *
 * @param WC_Product $product Ürün.
 */
function df_card_qty_chips( $product ) {
	if ( ! df_opt( 'card_qty_chips', 1 ) ) {
		return;
	}
	$attr = df_qty_attr( $product );
	if ( ! $attr ) {
		return;
	}
	$by_value = array();
	foreach ( $product->get_children() as $vid ) {
		$v = wc_get_product( $vid );
		if ( ! $v || ! $v->is_purchasable() || ! $v->is_in_stock() || 'publish' !== $v->get_status() ) {
			continue;
		}
		$val = (string) ( $v->get_attributes()[ sanitize_title( $attr['name'] ) ] ?? '' );
		if ( '' === $val ) {
			continue;
		}
		if ( ! isset( $by_value[ $val ] ) ) {
			$by_value[ $val ] = $v->get_price_html();
		}
	}
	if ( count( $by_value ) < 2 ) {
		return;
	}
	$default = $product->get_variation_default_attribute( $attr['name'] );
	$chips   = array();
	foreach ( $attr['options'] as $opt ) {
		if ( isset( $by_value[ $opt ] ) ) {
			$chips[ $opt ] = $by_value[ $opt ];
		}
	}
	$chips = array_slice( $chips, 0, 6, true );
	echo '<div class="df-card__qty" role="group" aria-label="' . esc_attr( wc_attribute_label( $attr['name'], $product ) ) . '" data-df-qty data-key="' . esc_attr( $attr['key'] ) . '">';
	foreach ( $chips as $val => $price ) {
		$label = df_qty_label( $attr['name'], $val );
		$num   = df_qty_number( $label );
		printf(
			'<button type="button" class="df-card__qtybtn%s" data-q="%s" data-price="%s" title="%s">%s</button>',
			$default === $val ? ' is-on' : '',
			esc_attr( $val ),
			esc_attr( wp_kses_post( $price ) ),
			esc_attr( $label ),
			esc_html( $num ? (string) $num : $label )
		);
	}
	echo '</div>';
}

/**
 * Kart seçimi betiği (bir kez).
 */
function df_card_qty_js() {
	if ( ! df_opt( 'card_qty_chips', 1 ) || is_admin() ) {
		return;
	}
	?>
	<script>
	document.addEventListener( 'click', function ( e ) {
		var b = e.target.closest( '.df-card__qtybtn' );
		if ( ! b ) { return; }
		e.preventDefault();
		var box = b.closest( '[data-df-qty]' ), card = b.closest( '.df-card' );
		box.querySelectorAll( '.df-card__qtybtn' ).forEach( function ( x ) { x.classList.toggle( 'is-on', x === b ); } );
		var price = card && card.querySelector( '.df-card__price' );
		if ( price ) { price.innerHTML = b.getAttribute( 'data-price' ); }
		card.querySelectorAll( 'a[href]' ).forEach( function ( a ) {
			if ( a.closest( '.df-card__cat' ) || a.classList.contains( 'add_to_cart_button' ) ) { return; }
			try { var u = new URL( a.href, location.href ); if ( u.origin !== location.origin ) { return; } u.searchParams.set( box.getAttribute( 'data-key' ), b.getAttribute( 'data-q' ) ); a.href = u.toString(); } catch ( err ) {}
		} );
	} );
	</script>
	<?php
}
add_action( 'wp_footer', 'df_card_qty_js', 30 );

/* ------------------------------------------------------------------------- */
/* Ek gül (ürün detayı)                                                      */
/* ------------------------------------------------------------------------- */

/**
 * Üründe ek gül açık mı ve ayarlar.
 *
 * @param WC_Product $product Ürün.
 * @return array{unit:float,max:int,step:int}|null
 */
function df_extra_cfg( $product ) {
	if ( ! $product instanceof WC_Product || ! df_opt( 'extra_on', 1 ) ) {
		return null;
	}
	$mode = (string) $product->get_meta( '_df_extra' );
	if ( 'no' === $mode ) {
		return null;
	}
	if ( 'yes' !== $mode ) {
		$cats = array_filter( array_map( 'trim', explode( ',', (string) df_opt( 'extra_cats', 'guller' ) ) ) );
		if ( ! $cats || ! has_term( $cats, 'product_cat', $product->get_id() ) ) {
			// Alt kategorilerde de çalışsın.
			$ok = false;
			foreach ( $cats as $slug ) {
				$parent = get_term_by( 'slug', $slug, 'product_cat' );
				if ( $parent ) {
					foreach ( wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'ids' ) ) as $tid ) {
						if ( term_is_ancestor_of( $parent->term_id, $tid, 'product_cat' ) ) {
							$ok = true;
							break 2;
						}
					}
				}
			}
			if ( ! $ok ) {
				return null;
			}
		}
	}
	return array(
		'unit' => (float) ( $product->get_meta( '_df_extra_unit' ) ? $product->get_meta( '_df_extra_unit' ) : df_opt( 'extra_unit', 0 ) ),
		'max'  => max( 1, (int) ( $product->get_meta( '_df_extra_max' ) ? $product->get_meta( '_df_extra_max' ) : df_opt( 'extra_max', 100 ) ) ),
		'step' => max( 1, (int) df_opt( 'extra_step', 1 ) ),
		'chips' => array_values( array_filter( array_map( 'absint', explode( ',', (string) ( $product->get_meta( '_df_extra_chips' ) ? $product->get_meta( '_df_extra_chips' ) : df_opt( 'extra_chips', '10, 20, 50' ) ) ) ) ) ),
	);
}

/**
 * Seçilen paket için ek gül birim fiyatı: girilen fiyat, yoksa paketin gül başı fiyatı.
 *
 * @param WC_Product      $product   Ana ürün.
 * @param WC_Product|null $variation Seçilen paket.
 * @return float
 */
function df_extra_unit_for( $product, $variation = null ) {
	$cfg = df_extra_cfg( $product );
	if ( ! $cfg ) {
		return 0;
	}
	if ( $cfg['unit'] > 0 ) {
		return $cfg['unit'];
	}
	$p     = $variation ? $variation : $product;
	$attr  = df_qty_attr( $product );
	$count = 0;
	if ( $variation && $attr ) {
		$count = df_qty_number( df_qty_label( $attr['name'], (string) ( $variation->get_attributes()[ sanitize_title( $attr['name'] ) ] ?? '' ) ) );
	}
	if ( ! $count ) {
		$count = df_qty_number( $p->get_name() );
	}
	$price = (float) $p->get_price();
	return ( $count > 0 && $price > 0 ) ? round( $price / $count ) : 0;
}

/**
 * Ürün sayfasında "Ek gül ekle" alanı.
 */
function df_extra_field() {
	global $product;
	$cfg = df_extra_cfg( $product );
	if ( ! $cfg ) {
		return;
	}
	$attr  = df_qty_attr( $product );
	$units = array();
	if ( $product->is_type( 'variable' ) ) {
		foreach ( $product->get_children() as $vid ) {
			$v = wc_get_product( $vid );
			if ( $v ) {
				$units[ $vid ] = array(
					'u' => df_extra_unit_for( $product, $v ),
					'n' => $attr ? df_qty_number( df_qty_label( $attr['name'], (string) ( $v->get_attributes()[ sanitize_title( $attr['name'] ) ] ?? '' ) ) ) : 0,
				);
			}
		}
	} else {
		$units[0] = array( 'u' => df_extra_unit_for( $product ), 'n' => df_qty_number( $product->get_name() ) );
	}
	if ( ! array_filter( wp_list_pluck( $units, 'u' ) ) ) {
		return;
	}
	?>
	<div class="df-extra" data-df-extra data-units="<?php echo esc_attr( wp_json_encode( $units ) ); ?>" data-step="<?php echo (int) $cfg['step']; ?>" data-max="<?php echo (int) $cfg['max']; ?>" data-cur="<?php echo esc_attr( html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ) ); ?>">
		<div class="df-extra__head">
			<span class="df-extra__title"><?php df_the_icon( 'flower', array( 'size' => 18 ) ); ?> Buketinize ek gül ekleyin</span>
			<span class="df-extra__unit" data-df-extra-unit></span>
		</div>
		<div class="df-extra__row">
			<button type="button" class="df-rosec__step" data-df-extra-step="-1" aria-label="Azalt">−</button>
			<input type="number" name="df_extra_roses" value="0" min="0" max="<?php echo (int) $cfg['max']; ?>" step="<?php echo (int) $cfg['step']; ?>" inputmode="numeric" aria-label="Ek gül adedi">
			<button type="button" class="df-rosec__step" data-df-extra-step="1" aria-label="Artır">+</button>
			<div class="df-extra__quick">
				<?php foreach ( array_slice( $cfg['chips'], 0, 5 ) as $q ) : ?>
					<?php if ( $q <= $cfg['max'] ) : ?>
						<button type="button" class="df-extra__chip" data-df-extra-set="<?php echo (int) $q; ?>">+<?php echo (int) $q; ?></button>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		</div>
		<p class="df-extra__sum" data-df-extra-sum aria-live="polite"></p>
	</div>
	<script>
	( function () {
		var box = document.currentScript.previousElementSibling, form = box.closest( 'form' ), input = box.querySelector( 'input' );
		var units = JSON.parse( box.dataset.units || '{}' ), step = +box.dataset.step || 1, max = +box.dataset.max || 100, cur = box.dataset.cur || '₺';
		function vid() { var v = form && form.querySelector( 'input[name=variation_id]' ); return v && +v.value ? v.value : ( units[0] ? '0' : '' ); }
		function fmt( n ) { return Math.round( n ).toLocaleString( 'tr-TR' ) + ' ' + cur; }
		function upd() {
			var n = Math.max( 0, Math.min( max, parseInt( input.value || '0', 10 ) || 0 ) ), id = vid(), u = id !== '' && units[ id ] ? units[ id ] : null;
			input.value = n;
			box.querySelector( '[data-df-extra-unit]' ).textContent = u && u.u ? 'Gül başı +' + fmt( u.u ) : '';
			var sum = box.querySelector( '[data-df-extra-sum]' );
			if ( ! u ) { sum.textContent = n ? 'Önce paket seçin.' : ''; return; }
			sum.innerHTML = n ? '<strong>+' + n + ' gül</strong> = +' + fmt( n * u.u ) + ( u.n ? ' · Buketinizde toplam <strong>' + ( u.n + n ) + ' gül</strong>' : '' ) : '';
			box.classList.toggle( 'is-on', n > 0 );
		}
		box.addEventListener( 'click', function ( e ) {
			var s = e.target.closest( '[data-df-extra-step]' ), q = e.target.closest( '[data-df-extra-set]' );
			if ( s ) { input.value = ( parseInt( input.value || '0', 10 ) || 0 ) + step * ( +s.dataset.dfExtraStep ); upd(); }
			if ( q ) { input.value = +q.dataset.dfExtraSet; upd(); }
		} );
		input.addEventListener( 'input', upd );
		if ( window.jQuery && form ) { jQuery( form ).on( 'found_variation reset_data', function () { setTimeout( upd ); } ); }
		upd();
	}() );
	</script>
	<?php
}
add_action( 'woocommerce_before_add_to_cart_button', 'df_extra_field', 2 );

/**
 * Sepet satırına ek gül.
 *
 * @param array $data         Veri.
 * @param int   $product_id   Ürün.
 * @param int   $variation_id Varyasyon.
 * @return array
 */
function df_extra_cart_data( $data, $product_id, $variation_id ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	$n = isset( $_POST['df_extra_roses'] ) ? absint( $_POST['df_extra_roses'] ) : 0;
	if ( $n < 1 || ! empty( $data['df_rose'] ) ) {
		return $data;
	}
	$product = wc_get_product( $product_id );
	$cfg     = df_extra_cfg( $product );
	if ( ! $cfg ) {
		return $data;
	}
	$data['df_extra'] = array( 'n' => min( $n, $cfg['max'] ) );
	return $data;
}
add_filter( 'woocommerce_add_cart_item_data', 'df_extra_cart_data', 12, 3 );

/**
 * Fiyat: paket + ek gül × birim (her hesaplamada paketin güncel fiyatından).
 *
 * @param WC_Cart $cart Sepet.
 */
function df_extra_totals( $cart ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return;
	}
	foreach ( $cart->get_cart() as $key => $item ) {
		if ( empty( $item['df_extra']['n'] ) ) {
			continue;
		}
		$parent = wc_get_product( $item['product_id'] );
		$base   = wc_get_product( $item['variation_id'] ? $item['variation_id'] : $item['product_id'] );
		if ( ! $parent || ! $base ) {
			continue;
		}
		$unit = df_extra_unit_for( $parent, $item['variation_id'] ? $base : null );
		$n    = (int) $item['df_extra']['n'];
		$cart->cart_contents[ $key ]['df_extra']['unit'] = $unit;
		$cart->cart_contents[ $key ]['data']->set_price( (float) $base->get_price() + $n * $unit );
	}
}
add_action( 'woocommerce_before_calculate_totals', 'df_extra_totals', 20 );

/**
 * Sepette / siparişte görünen bilgi.
 *
 * @param array $data Satırlar.
 * @param array $item Sepet satırı.
 * @return array
 */
function df_extra_item_data( $data, $item ) {
	if ( ! empty( $item['df_extra']['n'] ) ) {
		$data[] = array(
			'key'   => 'Ek gül',
			'value' => (int) $item['df_extra']['n'] . ' adet' . ( ! empty( $item['df_extra']['unit'] ) ? ' (+' . wp_strip_all_tags( wc_price( $item['df_extra']['n'] * $item['df_extra']['unit'] ) ) . ')' : '' ),
		);
	}
	return $data;
}
add_filter( 'woocommerce_get_item_data', 'df_extra_item_data', 10, 2 );

/**
 * Sipariş satırı: ad ve hazırlama bilgisi.
 *
 * @param WC_Order_Item_Product $line Satır.
 * @param string                $key  Anahtar.
 * @param array                 $item Sepet satırı.
 */
function df_extra_order_item( $line, $key, $item ) {
	if ( empty( $item['df_extra']['n'] ) ) {
		return;
	}
	$n = (int) $item['df_extra']['n'];
	$line->set_name( $line->get_name() . ' + ' . $n . ' ek gül' );
	$line->add_meta_data( 'Ek gül', $n . ' adet', true );
	$attr = df_qty_attr( wc_get_product( $item['product_id'] ) );
	$v    = $item['variation_id'] ? wc_get_product( $item['variation_id'] ) : null;
	if ( $attr && $v ) {
		$base = df_qty_number( df_qty_label( $attr['name'], (string) ( $v->get_attributes()[ sanitize_title( $attr['name'] ) ] ?? '' ) ) );
		if ( $base ) {
			$line->add_meta_data( 'Hazırlanacak toplam gül', ( $base + $n ) . ' adet', true );
		}
	}
}
add_action( 'woocommerce_checkout_create_order_line_item', 'df_extra_order_item', 12, 3 );

/**
 * Ürün verileri: "Ek Gül" sekmesi.
 *
 * @param array $tabs Sekmeler.
 * @return array
 */
function df_extra_admin_tab( $tabs ) {
	$tabs['df_extra'] = array( 'label' => 'Ek Gül', 'target' => 'df_extra_panel', 'class' => array( 'show_if_simple', 'show_if_variable' ), 'priority' => 64 );
	return $tabs;
}
add_filter( 'woocommerce_product_data_tabs', 'df_extra_admin_tab' );

/**
 * Sekme içeriği.
 */
function df_extra_admin_fields() {
	global $post;
	$product = wc_get_product( $post );
	$auto    = $product ? df_extra_unit_for( $product, $product->is_type( 'variable' ) && $product->get_children() ? wc_get_product( $product->get_children()[0] ) : null ) : 0;
	echo '<div id="df_extra_panel" class="panel woocommerce_options_panel hidden"><div class="options_group">';
	echo '<p class="form-field" style="margin:0;padding-top:12px"><strong>Ürün sayfasında "Buketinize ek gül ekleyin"</strong> — müşteri seçtiği paketin üstüne gül ekler (örn. 101 + 50 = 151 gül). Fiyat = paket fiyatı + ek gül × gül başı fiyat.</p>';
	woocommerce_wp_select(
		array(
			'id'      => '_df_extra',
			'label'   => 'Ek gül',
			'options' => array( '' => 'Genel ayara göre (Güller kategorisinde açık)', 'yes' => 'Bu üründe açık', 'no' => 'Bu üründe kapalı' ),
		)
	);
	woocommerce_wp_text_input( array( 'id' => '_df_extra_unit', 'label' => 'Gül başı fiyat (₺)', 'type' => 'number', 'custom_attributes' => array( 'min' => '0', 'step' => '0.01' ), 'placeholder' => df_opt( 'extra_unit', 0 ) ? (string) df_opt( 'extra_unit' ) : 'Otomatik', 'description' => 'Boş bırakılırsa Tema Ayarları’ndaki fiyat; o da 0 ise seçilen paketin gül başı fiyatı kullanılır' . ( $auto ? ' (şu an ilk paket için ≈ ' . wp_strip_all_tags( wc_price( $auto ) ) . ')' : '' ) . '.' ) );
	woocommerce_wp_text_input( array( 'id' => '_df_extra_max', 'label' => 'En fazla ek gül (adet)', 'type' => 'number', 'custom_attributes' => array( 'min' => '1', 'step' => '1' ), 'placeholder' => (string) df_opt( 'extra_max', 100 ) ) );
	woocommerce_wp_text_input( array( 'id' => '_df_extra_chips', 'label' => 'Hızlı ekleme düğmeleri', 'placeholder' => (string) df_opt( 'extra_chips', '10, 20, 50' ), 'description' => 'Virgülle adetler, örn: 10, 25, 50. Müşteri tek dokunuşla ekler.' ) );
	echo '</div></div>';
}
add_action( 'woocommerce_product_data_panels', 'df_extra_admin_fields' );

/**
 * Kaydet.
 *
 * @param WC_Product $product Ürün.
 */
function df_extra_admin_save( $product ) {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- WooCommerce ürün kaydı.
	if ( isset( $_POST['_df_extra'] ) ) {
		$m = sanitize_key( wp_unslash( $_POST['_df_extra'] ) );
		$product->update_meta_data( '_df_extra', in_array( $m, array( 'yes', 'no' ), true ) ? $m : '' );
	}
	if ( isset( $_POST['_df_extra_unit'] ) ) {
		$u = wc_format_decimal( wp_unslash( $_POST['_df_extra_unit'] ) );
		$product->update_meta_data( '_df_extra_unit', '' === $u ? '' : max( 0, (float) $u ) );
	}
	if ( isset( $_POST['_df_extra_chips'] ) ) {
		$product->update_meta_data( '_df_extra_chips', implode( ', ', array_filter( array_map( 'absint', explode( ',', sanitize_text_field( wp_unslash( $_POST['_df_extra_chips'] ) ) ) ) ) ) );
	}
	if ( isset( $_POST['_df_extra_max'] ) ) {
		$x = absint( $_POST['_df_extra_max'] );
		$product->update_meta_data( '_df_extra_max', $x ? $x : '' );
	}
	// phpcs:enable
}
add_action( 'woocommerce_admin_process_product_object', 'df_extra_admin_save' );
