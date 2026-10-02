<?php
/**
 * Ürün detay sayfası düzeni: özet sırası, teslimat notu, açıklama, SSS, benzer ürünler.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Varsayılan kancaları tema düzenine göre yeniden sırala.
 */
function df_single_hooks() {
	remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10 );

	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title', 5 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50 );

	add_action( 'woocommerce_single_product_summary', 'df_single_eyebrow', 3 );
	add_action( 'woocommerce_single_product_summary', 'df_single_title', 5 );
	add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 8 );
	add_action( 'woocommerce_single_product_summary', 'df_single_price', 10 );
	add_action( 'woocommerce_single_product_summary', 'df_single_delivery_note', 15 );
	add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
	add_action( 'woocommerce_single_product_summary', 'df_single_trust', 35 );
	add_action( 'woocommerce_single_product_summary', 'df_single_whatsapp', 38 );
	add_action( 'woocommerce_single_product_summary', 'df_single_meta', 45 );

	remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
	remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
	remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );

	add_action( 'woocommerce_after_single_product_summary', 'df_single_details', 10 );
	add_action( 'woocommerce_after_single_product_summary', 'df_single_reviews', 20 );
	add_action( 'woocommerce_after_single_product_summary', 'df_single_faq', 30 );
	add_action( 'woocommerce_after_single_product_summary', 'df_single_related', df_opt( 'single_related_first', 1 ) ? 5 : 40 );
}
add_action( 'init', 'df_single_hooks' );

/**
 * Kategori + alt başlık.
 */
function df_single_eyebrow() {
	global $product;
	$cat = df_product_primary_cat( $product );
	$sub = $product->get_meta( '_df_subtitle' );
	if ( ! $cat && ! $sub ) {
		return;
	}
	echo '<p class="df-single__eyebrow">';
	if ( $cat ) {
		echo '<a href="' . esc_url( get_term_link( $cat ) ) . '">' . esc_html( $cat->name ) . '</a>';
	}
	if ( $cat && $sub ) {
		echo '<span aria-hidden="true"> · </span>';
	}
	if ( $sub ) {
		echo '<span>' . esc_html( $sub ) . '</span>';
	}
	echo '</p>';
}

/**
 * Başlık.
 */
function df_single_title() {
	the_title( '<h1 class="product_title entry-title df-single__title">', '</h1>' );
}

/**
 * Fiyat + indirim etiketi.
 */
function df_single_price() {
	global $product;
	echo '<div class="df-single__price">';
	echo '<p class="price">' . wp_kses_post( $product->get_price_html() ) . '</p>';
	if ( $product->is_on_sale() ) {
		echo apply_filters( 'woocommerce_sale_flash', '', get_post( $product->get_id() ), $product ); // phpcs:ignore
	}
	echo '</div>';
}

/**
 * Aynı gün teslimat notu + geri sayım.
 */
function df_single_delivery_note() {
	if ( ! function_exists( 'df_delivery_same_day_seconds_left' ) ) {
		return;
	}
	$left = df_delivery_same_day_seconds_left();
	$note = df_vars( df_opt( 'single_delivery_note' ) );
	echo '<div class="df-single__delivery" data-df-countdown="' . esc_attr( max( 0, $left ) ) . '">';
	df_the_icon( 'truck', array( 'size' => 22 ) );
	echo '<div>';
	if ( $left > 0 ) {
		echo '<strong class="df-countdown">Bugün teslimat için <span data-df-countdown-text>' . esc_html( df_format_duration( $left ) ) . '</span> kaldı</strong>';
	} else {
		$next = df_delivery_next_date();
		echo '<strong>En erken teslimat: ' . esc_html( $next ? df_date_label( $next ) : 'yakında' ) . '</strong>';
	}
	if ( $note ) {
		echo '<span>' . esc_html( $note ) . '</span>';
	}
	echo '</div></div>';
}

/**
 * Süreyi okunur biçime çevirir.
 *
 * @param int $seconds Saniye.
 * @return string
 */
function df_format_duration( $seconds ) {
	$h = (int) floor( $seconds / 3600 );
	$m = (int) floor( ( $seconds % 3600 ) / 60 );
	return ( $h ? $h . ' sa ' : '' ) . $m . ' dk';
}

/**
 * Sepete ekle altı güven maddeleri.
 */
function df_single_trust() {
	$items = df_lines( df_opt( 'single_trust' ), true );
	if ( ! $items ) {
		return;
	}
	echo '<ul class="df-single__trust">';
	foreach ( $items as $row ) {
		$icon = count( $row ) > 1 ? $row[0] : 'check';
		$text = count( $row ) > 1 ? $row[1] : $row[0];
		echo '<li>' . df_icon( $icon, array( 'size' => 20 ) ) . '<span>' . esc_html( df_vars( $text ) ) . '</span></li>'; // phpcs:ignore
	}
	echo '</ul>';
}

/**
 * WhatsApp ile sipariş.
 */
function df_single_whatsapp() {
	global $product;
	if ( ! df_opt( 'single_whatsapp' ) || ! df_whatsapp_url() ) {
		return;
	}
	$url = df_whatsapp_url( sprintf( 'Merhaba, "%s" ürünü hakkında bilgi almak istiyorum: %s', $product->get_name(), $product->get_permalink() ) );
	echo '<a class="df-single__wa" href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . df_icon( 'whatsapp', array( 'size' => 20 ) ) . '<span>WhatsApp ile sipariş verin veya soru sorun</span></a>'; // phpcs:ignore
}

/**
 * SKU, favori ve paylaş.
 */
function df_single_meta() {
	global $product;
	echo '<div class="df-single__meta">';
	echo df_wishlist_button( $product->get_id(), 'df-fav--text' ); // phpcs:ignore
	if ( df_opt( 'single_share' ) ) {
		$url   = rawurlencode( $product->get_permalink() );
		$title = rawurlencode( $product->get_name() );
		echo '<div class="df-share"><span>Paylaş</span>';
		printf( '<a href="https://wa.me/?text=%1$s%%20%2$s" target="_blank" rel="noopener" aria-label="WhatsApp ile paylaş">%3$s</a>', $title, $url, df_icon( 'whatsapp', array( 'size' => 18 ) ) ); // phpcs:ignore
		printf( '<a href="https://www.facebook.com/sharer/sharer.php?u=%1$s" target="_blank" rel="noopener" aria-label="Facebook\'ta paylaş">%2$s</a>', $url, df_icon( 'facebook', array( 'size' => 18 ) ) ); // phpcs:ignore
		printf( '<a href="https://pinterest.com/pin/create/button/?url=%1$s&description=%2$s" target="_blank" rel="noopener" aria-label="Pinterest\'te paylaş">%3$s</a>', $url, $title, df_icon( 'pinterest', array( 'size' => 18 ) ) ); // phpcs:ignore
		echo '</div>';
	}
	if ( wc_product_sku_enabled() && $product->get_sku() ) {
		echo '<span class="df-single__sku">Ürün kodu: ' . esc_html( $product->get_sku() ) . '</span>';
	}
	echo '</div>';
}

/**
 * Tam genişlik ürün açıklaması + içerik, ölçü, bakım, teslimat.
 */
function df_single_details() {
	global $product;
	$content  = apply_filters( 'the_content', get_post_field( 'post_content', $product->get_id() ) );
	$contents = df_lines( $product->get_meta( '_df_contents' ) );
	$size     = $product->get_meta( '_df_size' );
	$care     = $product->get_meta( '_df_care' ) ? $product->get_meta( '_df_care' ) : df_opt( 'care_default' );
	$delivery = df_vars( df_opt( 'delivery_info' ) );
	$gallery  = $product->get_gallery_image_ids();
	$side_img = count( $gallery ) > 1 ? $gallery[1] : ( $gallery ? $gallery[0] : 0 );
	$attrs    = array_filter(
		$product->get_attributes(),
		function ( $a ) {
			return $a->get_visible();
		}
	);
	?>
	<section class="df-pdetails" id="urun-aciklamasi" aria-labelledby="df-pdetails-title">
		<div class="df-container df-pdetails__grid<?php echo $side_img ? ' has-image' : ''; ?>">
			<div class="df-pdetails__main">
				<p class="df-eyebrow">Tasarım Hakkında</p>
				<h2 class="df-pdetails__title" id="df-pdetails-title"><?php echo esc_html( df_opt( 'desc_title', 'Ürün Açıklaması' ) ); ?></h2>
				<div class="df-prose">
					<?php
					if ( trim( wp_strip_all_tags( $content ) ) ) {
						echo $content; // phpcs:ignore
					} else {
						echo '<p>' . esc_html( wp_strip_all_tags( $product->get_short_description() ) ) . '</p>';
					}
					?>
				</div>

				<?php if ( $contents || $size || $attrs ) : ?>
					<dl class="df-specs">
						<?php if ( $contents ) : ?>
							<div class="df-specs__row">
								<dt>Ürün içeriği</dt>
								<dd><ul><?php foreach ( $contents as $line ) : ?><li><?php echo esc_html( $line ); ?></li><?php endforeach; ?></ul></dd>
							</div>
						<?php endif; ?>
						<?php if ( $size ) : ?>
							<div class="df-specs__row"><dt>Ölçüler</dt><dd><?php echo esc_html( $size ); ?></dd></div>
						<?php endif; ?>
						<?php foreach ( $attrs as $attr ) : ?>
							<div class="df-specs__row">
								<dt><?php echo esc_html( wc_attribute_label( $attr->get_name() ) ); ?></dt>
								<dd>
									<?php
									$values = $attr->is_taxonomy() ? wc_get_product_terms( $product->get_id(), $attr->get_name(), array( 'fields' => 'names' ) ) : $attr->get_options();
									echo esc_html( implode( ', ', $values ) );
									?>
								</dd>
							</div>
						<?php endforeach; ?>
					</dl>
				<?php endif; ?>

				<div class="df-accordion">
					<?php if ( $care ) : ?>
						<details class="df-accordion__item" open>
							<summary><?php df_the_icon( 'leaf', array( 'size' => 20 ) ); ?><span><?php echo esc_html( df_opt( 'care_title', 'Çiçek Bakımı' ) ); ?></span><?php df_the_icon( 'plus', array( 'size' => 18, 'class' => 'df-accordion__sign' ) ); ?></summary>
							<div class="df-accordion__body"><?php echo wp_kses_post( wpautop( esc_html( $care ) ) ); ?></div>
						</details>
					<?php endif; ?>
					<?php if ( $delivery ) : ?>
						<details class="df-accordion__item">
							<summary><?php df_the_icon( 'truck', array( 'size' => 20 ) ); ?><span>Teslimat Bilgisi</span><?php df_the_icon( 'plus', array( 'size' => 18, 'class' => 'df-accordion__sign' ) ); ?></summary>
							<div class="df-accordion__body"><?php echo wp_kses_post( wpautop( esc_html( $delivery ) ) ); ?></div>
						</details>
					<?php endif; ?>
					<details class="df-accordion__item">
						<summary><?php df_the_icon( 'note', array( 'size' => 20 ) ); ?><span>Çiçek Notu & Hediye Paketi</span><?php df_the_icon( 'plus', array( 'size' => 18, 'class' => 'df-accordion__sign' ) ); ?></summary>
						<div class="df-accordion__body"><p><?php echo esc_html( df_opt( 'note_intro' ) ); ?> Ödeme adımında hazır mesaj şablonlarından seçebilir ya da kendi notunuzu yazabilirsiniz.</p></div>
					</details>
				</div>
			</div>
			<?php if ( $side_img ) : ?>
				<figure class="df-pdetails__media">
					<?php echo wp_get_attachment_image( $side_img, 'df-portrait', false, array( 'loading' => 'lazy', 'sizes' => '(max-width: 900px) 100vw, 40vw' ) ); ?>
				</figure>
			<?php endif; ?>
		</div>
	</section>
	<?php
}

/**
 * Değerlendirmeler.
 */
function df_single_reviews() {
	global $product;
	if ( ! comments_open( $product->get_id() ) && ! $product->get_review_count() ) {
		return;
	}
	echo '<section class="df-previews" id="degerlendirmeler"><div class="df-container df-container--narrow">';
	if ( $product->get_review_count() ) {
		comments_template();
	} else {
		// Henüz yorum yoksa form katlanmış gelir; sayfayı uzatmaz.
		echo '<details class="df-review-toggle"><summary><span>Değerlendirmeler</span><small>Bu tasarımı ilk değerlendiren siz olun</small>' . df_icon( 'plus', array( 'size' => 18 ) ) . '</summary>'; // phpcs:ignore
		comments_template();
		echo '</details>';
	}
	echo '</div></section>';
}

/**
 * Sıkça sorulan sorular (ürüne özel + genel).
 */
function df_single_faq() {
	global $product;
	$items = $product->get_meta( '_df_faq' );
	$items = is_array( $items ) ? $items : array();
	if ( df_opt( 'faq_on_product' ) ) {
		$items = array_merge( $items, (array) df_opt( 'faq_items', array() ) );
	}
	if ( ! $items ) {
		return;
	}
	echo '<section class="df-faq-section" id="sss"><div class="df-container df-faq-section__grid">';
	echo '<div class="df-faq-section__head"><p class="df-eyebrow">Yardım</p><h2>' . esc_html( df_opt( 'faq_title', 'Sıkça Sorulan Sorular' ) ) . '</h2>';
	echo '<p>Aradığınız cevabı bulamadınız mı? Bize her zaman ulaşabilirsiniz.</p>';
	if ( df_opt( 'contact_phone1' ) ) {
		echo '<a class="df-link-arrow" href="' . esc_url( df_tel( df_opt( 'contact_phone1' ) ) ) . '">' . esc_html( df_opt( 'contact_phone1' ) ) . df_icon( 'arrow-right', array( 'size' => 18 ) ) . '</a>'; // phpcs:ignore
	}
	echo '</div>';
	df_faq_list( $items );
	echo '</div></section>';
}

/**
 * Benzer ürünler: önce yukarı satışlar, sonra ilgili ürünler.
 */
function df_single_related() {
	global $product;
	$limit = absint( df_opt( 'related_count', 8 ) );
	$ids   = $product->get_upsell_ids();
	if ( count( $ids ) < $limit ) {
		$ids = array_unique( array_merge( $ids, wc_get_related_products( $product->get_id(), $limit * 2, $ids ) ) );
	}
	$ids = array_slice( array_diff( $ids, array( $product->get_id() ) ), 0, $limit );
	if ( ! $ids ) {
		return;
	}
	echo '<section class="df-related"><div class="df-container">';
	df_section_head(
		array(
			'eyebrow' => 'Sizin İçin Seçtiklerimiz',
			'title'   => df_opt( 'related_title', 'Bunları da Beğenebilirsiniz' ),
		)
	);
	echo '<div class="df-vitrin df-vitrin--r' . esc_attr( sanitize_key( df_opt( 'vit_ratio', '1-1' ) ) ) . '"><div class="df-vitrin__grid">';
	foreach ( $ids as $id ) {
		df_product_card( $id, array( 'variant' => 'vitrin' ) );
	}
	echo '</div></div></div></section>';
}

/**
 * Sepete ekle metni.
 *
 * @return string
 */
function df_single_add_to_cart_text() {
	return 'Sepete Ekle';
}
add_filter( 'woocommerce_product_single_add_to_cart_text', 'df_single_add_to_cart_text' );

/**
 * "Hemen Al" butonu (sepete ekler ve ödemeye geçer).
 */
function df_buy_now_button() {
	global $product;
	if ( ! $product || ! $product->is_purchasable() || ! $product->is_in_stock() || $product->is_type( 'external' ) || $product->is_type( 'grouped' ) ) {
		return;
	}
	if ( $product->is_type( 'simple' ) ) {
		// Basit ürünlerde ürün ID'si sepete ekle butonunun değeridir; Hemen Al ile gönderimde de iletilsin.
		echo '<input type="hidden" name="add-to-cart" value="' . esc_attr( $product->get_id() ) . '">';
	}
	if ( function_exists( 'df_quick_order_on' ) && df_quick_order_on() && df_quick_product_ok( $product ) ) {
		echo '<button type="submit" name="df_buy_now" value="1" class="df-btn df-btn--solid df-buy-now df-buy-now--main">' . esc_html( df_opt( 'quick_btn', 'Hemen Satın Al — Ödemeye Geç' ) ) . df_icon( 'arrow-right', array( 'size' => 18 ) ) . '</button>'; // phpcs:ignore
		return;
	}
	echo '<button type="submit" name="df_buy_now" value="1" class="df-btn df-btn--outline df-buy-now">Hemen Al</button>';
}
add_action( 'woocommerce_after_add_to_cart_button', 'df_buy_now_button' );

/**
 * Hemen Al → ödeme sayfası.
 *
 * @param string $url URL.
 * @return string
 */
function df_buy_now_redirect( $url ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
	if ( ! empty( $_REQUEST['df_buy_now'] ) ) {
		return wc_get_checkout_url();
	}
	return $url;
}
add_filter( 'woocommerce_add_to_cart_redirect', 'df_buy_now_redirect', 99 );

/**
 * Tekli ürün sayfasında sepete ekleyince mini sepeti aç (sayfa yeniden yüklendiğinde).
 *
 * @param string $message Mesaj.
 * @return string
 */
function df_add_to_cart_message( $message ) {
	return '<span class="df-added-msg">' . wp_kses_post( wp_strip_all_tags( $message ) ) . '</span> <a href="' . esc_url( wc_get_checkout_url() ) . '" class="df-link-arrow">Siparişi tamamla ' . df_icon( 'arrow-right', array( 'size' => 16 ) ) . '</a>';
}
add_filter( 'wc_add_to_cart_message_html', 'df_add_to_cart_message' );

/**
 * Yorum formu metinleri.
 *
 * @param array $form Form.
 * @return array
 */
function df_review_form_args( $form ) {
	$form['title_reply']        = 'Değerlendirme yazın';
	$form['title_reply_before'] = '<h3 id="reply-title" class="comment-reply-title">';
	$form['title_reply_after']  = '</h3>';
	$form['label_submit']       = 'Gönder';
	return $form;
}
add_filter( 'woocommerce_product_review_comment_form_args', 'df_review_form_args' );
