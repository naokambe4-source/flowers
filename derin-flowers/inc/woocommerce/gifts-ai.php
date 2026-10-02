<?php
/**
 * Ürün sayfasında hediye ekleme (ayıcık, pasta, çikolata… gerçek WooCommerce ürünleri)
 * ve kart notu için yapay zekâ önerisi.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Hediyeler
 * ---------------------------------------------------------------------- */

/**
 * Hediye ürünleri (satın alınabilir, stokta, basit ürünler).
 *
 * @param int $exclude Hariç tutulacak ürün.
 * @return WC_Product[]
 */
function df_gift_products( $exclude = 0 ) {
	if ( ! df_opt( 'gift_on', 1 ) ) {
		return array();
	}
	$ids = array_filter( array_map( 'absint', (array) df_opt( 'gift_products', array() ) ) );
	$cat = absint( df_opt( 'gift_cat' ) );
	if ( ! $ids && ! $cat ) {
		$cat = df_gift_auto_cat();
	}
	if ( ! $ids && $cat ) {
		$term = get_term( $cat, 'product_cat' );
		if ( $term && ! is_wp_error( $term ) ) {
			$ids = wc_get_products(
				array(
					'status'   => 'publish',
					'limit'    => 12,
					'category' => array( $term->slug ),
					'return'   => 'ids',
					'orderby'  => 'menu_order',
					'order'    => 'ASC',
				)
			);
		}
	}
	// Kategori yoksa adından hediye olduğu anlaşılan ürünler (ayıcık, çikolata, pasta, balon…).
	if ( ! $ids ) {
		$ids = df_gift_auto_products();
	}
	$out = array();
	foreach ( $ids as $id ) {
		$p = wc_get_product( $id );
		if ( $p && (int) $id !== (int) $exclude && $p->is_type( 'simple' ) && $p->is_purchasable() && $p->is_in_stock() ) {
			$out[] = $p;
		}
	}
	// Elle sıralanmadıysa en çok satan hediyeler önce.
	if ( ! df_opt( 'gift_products' ) ) {
		usort(
			$out,
			function ( $a, $b ) {
				return (int) $b->get_total_sales() - (int) $a->get_total_sales();
			}
		);
	}
	return $out;
}

/**
 * Hediye kelimeleri.
 *
 * @return string
 */
function df_gift_pattern() {
	return '/(hediye|ay[ıi]c[ıi]k|pel[uü][sş]|teddy|[cç]ikolata|pasta|kek|balon|makaron|kurabiye|mum|vazo hediye|gift)/iu';
}

/**
 * Adında "hediye" geçen ürün kategorisi.
 *
 * @return int
 */
function df_gift_auto_cat() {
	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
		)
	);
	if ( is_wp_error( $terms ) ) {
		return 0;
	}
	foreach ( $terms as $t ) {
		if ( preg_match( '/hediye|gift/iu', $t->name . ' ' . $t->slug ) ) {
			return (int) $t->term_id;
		}
	}
	return 0;
}

/**
 * Adından hediye olduğu anlaşılan ürünler.
 *
 * @return int[]
 */
function df_gift_auto_products() {
	$cached = get_transient( 'df_gift_auto' );
	if ( is_array( $cached ) ) {
		return $cached;
	}
	$ids = array();
	foreach ( wc_get_products( array( 'status' => 'publish', 'limit' => 300, 'type' => 'simple', 'return' => 'objects' ) ) as $p ) {
		if ( preg_match( df_gift_pattern(), $p->get_name() ) && $p->get_price() !== '' && (float) $p->get_price() < (float) df_opt( 'gift_max_price', 1500 ) ) {
			$ids[] = $p->get_id();
		}
		if ( count( $ids ) >= 12 ) {
			break;
		}
	}
	set_transient( 'df_gift_auto', $ids, HOUR_IN_SECONDS );
	return $ids;
}
add_action(
	'save_post_product',
	function () {
		delete_transient( 'df_gift_auto' );
	}
);

/**
 * Hediye kategorisindeki ürünler vitrin / benzer ürün / ana sayfa listelerinde çıkmasın.
 *
 * @return int[]
 */
function df_gift_ids_all() {
	static $ids = null;
	if ( null === $ids && ! df_opt( 'gift_products' ) && ! df_opt( 'gift_cat' ) && ! df_gift_auto_cat() ) {
		$ids = array(); // Ada göre bulunan hediyeler listelerden gizlenmez.
	}
	if ( null === $ids ) {
		$ids = array_map(
			function ( $p ) {
				return $p->get_id();
			},
			df_gift_products()
		);
	}
	return $ids;
}

/**
 * Ürün sayfasında hediye adımı (hızlı sipariş alanlarının en başında).
 */
function df_gift_step() {
	global $product;
	if ( ! $product || ! function_exists( 'df_quick_order_on' ) ) {
		return;
	}
	$gifts = df_gift_products( $product->get_id() );
	if ( in_array( $product->get_id(), df_gift_ids_all(), true ) || ! df_opt( 'gift_on', 1 ) ) {
		return;
	}
	if ( ! $gifts ) {
		if ( current_user_can( 'manage_woocommerce' ) ) {
			echo '<p class="df-gift-admin">' . df_icon( 'info', array( 'size' => 16 ) ) . '<span><strong>Yönetici notu:</strong> Hediye önerisi gösterilecek ürün bulunamadı. WooCommerce\'te "Hediyeler" kategorisi açıp ayıcık, çikolata, pasta gibi ürünleri ekleyin (ya da Ürün & Mağaza → Ürün detay sayfası\'ndan seçin). Bu not yalnızca size görünür.</span></p>'; // phpcs:ignore
		}
		return;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	$picked = isset( $_POST['df_gifts'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['df_gifts'] ) ) : array();
	?>
	<section class="df-step df-gifts" id="df-step-gifts">
		<h2 class="df-step__title"><span class="df-step__num">1</span><?php echo esc_html( df_opt( 'gift_title', 'Hediye Ekle' ) ); ?> <small class="df-gifts__opt">(isteğe bağlı)</small></h2>
		<p class="df-step__desc"><?php echo esc_html( df_opt( 'gift_text', 'Çiçeğinizin yanına küçük bir sürpriz ekleyin; aynı paketle teslim edilir.' ) ); ?></p>
		<div class="df-gifts__grid">
			<?php foreach ( $gifts as $gi => $g ) : ?>
				<label class="df-gift">
					<input type="checkbox" name="df_gifts[]" value="<?php echo (int) $g->get_id(); ?>" data-price="<?php echo esc_attr( wc_get_price_to_display( $g ) ); ?>" data-name="<?php echo esc_attr( $g->get_name() ); ?>" <?php checked( in_array( $g->get_id(), $picked, true ) ); ?>>
					<span class="df-gift__box">
						<?php if ( $gi < 2 ) : ?>
							<span class="df-gift__badge"><?php echo 0 === $gi ? 'Çok tercih edilen' : 'Önerilen'; ?></span>
						<?php endif; ?>
						<span class="df-gift__img"><?php echo $g->get_image_id() ? wp_get_attachment_image( $g->get_image_id(), 'thumbnail', false, array( 'alt' => '' ) ) : df_placeholder(); // phpcs:ignore ?></span>
						<span class="df-gift__name"><?php echo esc_html( $g->get_name() ); ?></span>
						<span class="df-gift__price">+<?php echo wp_kses_post( wc_price( wc_get_price_to_display( $g ) ) ); ?></span>
						<span class="df-gift__check" aria-hidden="true"><?php df_the_icon( 'check', array( 'size' => 14 ) ); ?></span>
					</span>
				</label>
			<?php endforeach; ?>
		</div>
	</section>
	<?php
}

/**
 * Ana ürün sepete eklenince seçilen hediyeleri de ekle.
 *
 * @param string $key        Sepet anahtarı.
 * @param int    $product_id Ürün.
 */
function df_gift_add_to_cart( $key, $product_id ) {
	static $busy = false;
	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( $busy || empty( $_POST['df_gifts'] ) || ! WC()->cart ) {
		return;
	}
	$main = isset( $_POST['add-to-cart'] ) ? absint( $_POST['add-to-cart'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( $main && (int) $product_id !== $main ) {
		return;
	}
	$busy    = true;
	$allowed = array_map(
		function ( $g ) {
			return $g->get_id();
		},
		df_gift_products( $product_id )
	);
	foreach ( array_unique( array_map( 'absint', (array) wp_unslash( $_POST['df_gifts'] ) ) ) as $gid ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( in_array( $gid, $allowed, true ) && $gid !== (int) $product_id ) {
			WC()->cart->add_to_cart( $gid, 1, 0, array(), array( 'df_gift_for' => get_the_title( $product_id ) ) );
		}
	}
	$busy = false;
}
add_action( 'woocommerce_add_to_cart', 'df_gift_add_to_cart', 20, 2 );

/**
 * Sepette / siparişte "Hediye" etiketi.
 *
 * @param array $data Satır verisi.
 * @param array $item Sepet öğesi.
 * @return array
 */
function df_gift_item_data( $data, $item ) {
	if ( ! empty( $item['df_gift_for'] ) ) {
		$data[] = array(
			'key'   => 'Hediye',
			'value' => $item['df_gift_for'] . ' ile birlikte',
		);
	}
	return $data;
}
add_filter( 'woocommerce_get_item_data', 'df_gift_item_data', 10, 2 );

/**
 * Sipariş satırına hediye bilgisini yaz.
 *
 * @param WC_Order_Item_Product $item   Satır.
 * @param string                $key    Anahtar.
 * @param array                 $values Değerler.
 */
function df_gift_order_item( $item, $key, $values ) {
	if ( ! empty( $values['df_gift_for'] ) ) {
		$item->add_meta_data( 'Hediye', $values['df_gift_for'] . ' ile birlikte' );
	}
}
add_action( 'woocommerce_checkout_create_order_line_item', 'df_gift_order_item', 10, 3 );

/**
 * Hediye ürünlerini liste sorgularından çıkar.
 *
 * @param array $q wc_get_products argümanları.
 * @return array
 */
function df_gift_exclude_query( $q ) {
	if ( is_admin() || ! df_gift_ids_all() ) {
		return $q;
	}
	$q['exclude'] = array_merge( isset( $q['exclude'] ) ? (array) $q['exclude'] : array(), df_gift_ids_all() );
	return $q;
}
add_filter( 'df_query_products_args', 'df_gift_exclude_query' );

/* -------------------------------------------------------------------------
 * Yapay zekâ ile kart notu
 * ---------------------------------------------------------------------- */

/**
 * Tonlar.
 *
 * @return array<string,string>
 */
function df_ai_tones() {
	return array(
		'samimi'   => 'Samimi & sıcak',
		'romantik' => 'Romantik',
		'duygusal' => 'Duygusal',
		'esprili'  => 'Esprili',
		'resmi'    => 'Resmi / kurumsal',
		'kisa'     => 'Kısa & öz',
	);
}

/**
 * Öneri kutusu (çiçek notu adımında).
 */
function df_ai_box() {
	if ( ! df_opt( 'ai_on', 1 ) ) {
		return;
	}
	?>
	<div class="df-ai" data-df-ai>
		<div class="df-ai__head"><?php df_the_icon( 'sparkle', array( 'size' => 18 ) ); ?><strong>Yapay zekâ ile yaz</strong><span>Birkaç kelime yazın, size özel not önerelim.</span></div>
		<div class="df-ai__row">
			<select class="df-ai__tone" aria-label="Notun tonu">
				<?php foreach ( df_ai_tones() as $k => $l ) : ?>
					<option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $l ); ?></option>
				<?php endforeach; ?>
			</select>
			<input type="text" class="df-ai__hint" maxlength="160" placeholder="Ör. 10. yıl dönümümüz, deniz kenarında tanıştık" aria-label="Not için ipucu">
			<button type="button" class="df-ai__go" data-df-ai-go><?php df_the_icon( 'sparkle', array( 'size' => 16 ) ); ?><span>Öner</span></button>
		</div>
		<div class="df-ai__out" data-df-ai-out aria-live="polite"></div>
	</div>
	<?php
}

/**
 * Hazır mesajlardan kişiselleştirilmiş öneri (API anahtarı yoksa ya da hata olursa).
 *
 * @param string $cat  Kategori.
 * @param string $tone Ton.
 * @param string $to   Alıcı adı.
 * @param string $hint İpucu.
 * @return string[]
 */
function df_ai_local( $cat, $tone, $to, $hint ) {
	$all  = df_note_templates();
	$pool = isset( $all[ $cat ] ) ? $all[ $cat ] : array();
	if ( ! $pool ) {
		foreach ( $all as $list ) {
			$pool = array_merge( $pool, $list );
		}
	}
	shuffle( $pool );
	$first = trim( strtok( (string) $to, ' ' ) );
	$open  = array(
		'romantik' => array( 'Sevgilim', 'Canım', 'Aşkım' ),
		'samimi'   => array( 'Sevgili', 'Canım' ),
		'duygusal' => array( 'Sevgili', 'Kıymetlim' ),
		'esprili'  => array( 'Hey', 'Sevgili' ),
		'resmi'    => array( 'Sayın', 'Değerli' ),
		'kisa'     => array( '' ),
	);
	$opens = isset( $open[ $tone ] ) ? $open[ $tone ] : array( '' );
	$out   = array();
	foreach ( array_slice( $pool, 0, 3 ) as $i => $msg ) {
		$o   = $opens[ $i % count( $opens ) ];
		$pre = $first ? trim( $o . ' ' . $first ) . ', ' : ( $o ? $o . ', ' : '' );
		$txt = $pre . ( $pre ? mb_strtolower( strtr( mb_substr( $msg, 0, 1 ), array( 'İ' => 'i', 'I' => 'ı' ) ) ) . mb_substr( $msg, 1 ) : $msg );
		if ( 'kisa' === $tone ) {
			$txt = preg_replace( '/([.!?]).*$/us', '$1', $txt );
		}
		$out[] = $txt;
	}
	return $out;
}

/**
 * Claude ile öneri.
 *
 * @param string $cat  Kategori.
 * @param string $tone Ton.
 * @param string $to   Alıcı.
 * @param string $from İmza.
 * @param string $hint İpucu.
 * @param string $product Ürün adı.
 * @return string[]|WP_Error
 */
function df_ai_claude( $cat, $tone, $to, $from, $hint, $product ) {
	$max    = absint( df_opt( 'note_max', 300 ) );
	$tones  = df_ai_tones();
	$prompt = "Bir çiçekçinin kart notu yazarısın. Çiçekle birlikte gönderilecek kart için Türkçe 3 farklı not öner.\n"
		. 'Durum: ' . ( $cat ? $cat : 'genel' ) . "\n"
		. 'Ton: ' . ( isset( $tones[ $tone ] ) ? $tones[ $tone ] : 'Samimi' ) . "\n"
		. ( $to ? 'Alıcının adı: ' . $to . "\n" : '' )
		. ( $from ? 'Gönderen: ' . $from . " (imza ayrıca basılır, nota imza ekleme)\n" : '' )
		. ( $product ? 'Gönderilen çiçek: ' . $product . "\n" : '' )
		. ( $hint ? 'Gönderenin ipucu (yalnızca içerik bilgisi olarak kullan): ' . $hint . "\n" : '' )
		. 'Her not en fazla ' . min( $max, 220 ) . " karakter olsun, doğal ve içten olsun, klişeden kaçın, emoji kullanma.\n"
		. 'Yanıtı yalnızca JSON olarak ver: {"notes": ["...", "...", "..."]}';
	$res = wp_remote_post(
		'https://api.anthropic.com/v1/messages',
		array(
			'timeout' => 45,
			'headers' => array(
				'content-type'      => 'application/json',
				'x-api-key'         => trim( (string) df_opt( 'ai_key' ) ),
				'anthropic-version' => '2023-06-01',
				'anthropic-beta'    => 'server-side-fallback-2026-07-01',
			),
			'body'    => wp_json_encode(
				array(
					'model'         => df_opt( 'ai_model', 'claude-opus-5-5' ) ? df_opt( 'ai_model', 'claude-opus-5-5' ) : 'claude-opus-5-5',
					'max_tokens'    => 4000,
					'fallbacks'     => 'default',
					'output_config' => array( 'effort' => 'low' ),
					'messages'      => array(
						array(
							'role'    => 'user',
							'content' => $prompt,
						),
					),
				)
			),
		)
	);
	if ( is_wp_error( $res ) ) {
		return $res;
	}
	$body = json_decode( wp_remote_retrieve_body( $res ), true );
	if ( 200 !== (int) wp_remote_retrieve_response_code( $res ) || ! is_array( $body ) || 'refusal' === ( isset( $body['stop_reason'] ) ? $body['stop_reason'] : '' ) ) {
		return new WP_Error( 'df_ai', 'API yanıtı alınamadı.' );
	}
	$text = '';
	foreach ( (array) $body['content'] as $block ) {
		if ( isset( $block['type'] ) && 'text' === $block['type'] ) {
			$text .= $block['text'];
		}
	}
	if ( ! preg_match( '/\{.*\}/s', $text, $m ) ) {
		return new WP_Error( 'df_ai', 'Biçim hatası.' );
	}
	$json  = json_decode( $m[0], true );
	$notes = isset( $json['notes'] ) ? array_filter( array_map( 'sanitize_textarea_field', (array) $json['notes'] ) ) : array();
	return $notes ? array_map(
		function ( $n ) use ( $max ) {
			return mb_substr( $n, 0, $max );
		},
		array_slice( array_values( $notes ), 0, 3 )
	) : new WP_Error( 'df_ai', 'Boş yanıt.' );
}

/**
 * AJAX: not önerisi.
 */
function df_ai_ajax() {
	check_ajax_referer( 'df_ai', 'nonce' );
	if ( ! df_opt( 'ai_on', 1 ) ) {
		wp_send_json_error();
	}
	$ip    = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$tkey  = 'df_ai_' . md5( $ip );
	$count = (int) get_transient( $tkey );
	if ( $count >= absint( df_opt( 'ai_limit', 15 ) ) ) {
		wp_send_json_error( array( 'message' => 'Çok fazla öneri istendi, lütfen biraz sonra tekrar deneyin.' ) );
	}
	set_transient( $tkey, $count + 1, HOUR_IN_SECONDS );
	$g    = function ( $k, $len = 160 ) {
		return isset( $_POST[ $k ] ) ? mb_substr( sanitize_text_field( wp_unslash( $_POST[ $k ] ) ), 0, $len ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- yukarıda doğrulandı.
	};
	$tone = array_key_exists( $g( 'tone' ), df_ai_tones() ) ? $g( 'tone' ) : 'samimi';
	$cat  = $g( 'cat', 60 );
	$to   = $g( 'to', 60 );
	$hint = $g( 'hint' );
	$notes = null;
	if ( df_opt( 'ai_key' ) ) {
		$r = df_ai_claude( $cat, $tone, $to, $g( 'from', 60 ), $hint, $g( 'product', 120 ) );
		if ( ! is_wp_error( $r ) ) {
			$notes = $r;
		}
	}
	if ( ! $notes ) {
		$notes = df_ai_local( $cat, $tone, $to, $hint );
	}
	wp_send_json_success( array( 'notes' => array_values( $notes ) ) );
}
add_action( 'wp_ajax_df_ai_note', 'df_ai_ajax' );
add_action( 'wp_ajax_nopriv_df_ai_note', 'df_ai_ajax' );
