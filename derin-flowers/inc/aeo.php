<?php
/**
 * AEO / GEO (yanıt ve yapay zekâ arama motorları): sitenin gerçek bilgilerinden üretilen
 * - zengin /llms.txt (işletme bilgileri, kategoriler, ürünler ve fiyatlar, teslimat bölgeleri, SSS, politikalar, blog)
 * - kategori ve ürün sayfalarında Sıkça Sorulan Sorular + FAQPage şeması (alan boşsa otomatik)
 * - işletme şemasında hizmet verilen ilçeler, sosyal hesaplar, iletişim (Rank Math kurulu olsa da)
 * Cevaplar ayarlardaki gerçek değerlerden (son sipariş saati, ilçeler, ücretler, ödeme yöntemleri) üretilir.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sitenin gerçek bilgileri.
 *
 * @return array
 */
function df_aeo_facts() {
	static $f = null;
	if ( null !== $f ) {
		return $f;
	}
	$city  = df_opt( 'df_default_city', 'İzmir' );
	$dists = function_exists( 'df_delivery_districts' ) ? df_delivery_districts() : array();
	$names = array();
	$fees  = array();
	foreach ( $dists as $d ) {
		$names[] = function_exists( 'df_loc_title_case' ) ? df_loc_title_case( $d['name'] ) : $d['name'];
		$fees[]  = (float) $d['fee'];
	}
	$pay = array();
	if ( function_exists( 'WC' ) && WC()->payment_gateways() ) {
		foreach ( WC()->payment_gateways()->get_available_payment_gateways() as $g ) {
			$pay[] = wp_strip_all_tags( $g->get_title() );
		}
	}
	$f = array(
		'site'      => get_bloginfo( 'name' ),
		'city'      => $city,
		'cutoff'    => (string) df_opt( 'df_cutoff', '16:00' ),
		'districts' => array_values( array_unique( $names ) ),
		'fee_min'   => $fees ? min( $fees ) : null,
		'fee_max'   => $fees ? max( $fees ) : null,
		'payments'  => $pay,
		'phone'     => (string) df_opt( 'contact_phone1', '' ),
		'whatsapp'  => preg_replace( '/\D+/', '', (string) df_opt( 'contact_whatsapp', '' ) ),
		'email'     => (string) df_opt( 'contact_email', '' ),
		'address'   => trim( preg_replace( '/\s*\n\s*/', ', ', (string) df_opt( 'contact_address', '' ) ) ),
		'hours'     => trim( preg_replace( '/\s*\n\s*/', ' ', (string) df_opt( 'contact_hours', '' ) ) ),
	);
	return $f;
}

/**
 * Tutar yazısı.
 *
 * @param float|null $v Tutar.
 * @return string
 */
function df_aeo_money( $v ) {
	return null === $v ? '' : ( $v <= 0 ? 'ücretsiz' : number_format_i18n( $v, 0 ) . ' TL' );
}

/**
 * Teslimat ücreti cümlesi.
 *
 * @return string
 */
function df_aeo_fee_text() {
	$f = df_aeo_facts();
	if ( null === $f['fee_min'] ) {
		return 'Teslimat ücreti adresinize göre ödeme adımında gösterilir.';
	}
	if ( $f['fee_min'] === $f['fee_max'] ) {
		return 'Teslimat ücreti ' . df_aeo_money( $f['fee_min'] ) . '.';
	}
	return 'Teslimat ücreti ilçeye göre ' . df_aeo_money( $f['fee_min'] ) . ' ile ' . df_aeo_money( $f['fee_max'] ) . ' arasındadır ve ödeme adımında gösterilir.';
}

/**
 * Bağlama göre otomatik sorular.
 *
 * @param string $name    Kategori / ürün adı.
 * @param string $context category|product|general.
 * @return array<int,array{q:string,a:string}>
 */
function df_aeo_faq( $name, $context = 'category' ) {
	$f     = df_aeo_facts();
	$lower = function_exists( 'df_ai_lower' ) ? df_ai_lower( $name ) : mb_strtolower( $name );
	$dist  = $f['districts'] ? implode( ', ', array_slice( $f['districts'], 0, 14 ) ) . ( count( $f['districts'] ) > 14 ? ' ve diğer ilçeler' : '' ) : $f['city'] . ' merkez ilçeleri';
	$pay   = $f['payments'] ? implode( ', ', $f['payments'] ) : 'Kredi/banka kartı (3D Secure) ve havale/EFT';
	$items = array();
	if ( 'product' === $context ) {
		$items[] = array( 'q' => $name . ' aynı gün teslim edilir mi?', 'a' => 'Evet. Saat ' . $f['cutoff'] . '’a kadar verilen siparişler ' . $f['city'] . ' içinde aynı gün teslim edilir. Daha sonra verilen siparişler için ertesi gün veya istediğiniz ileri bir tarih ve saat aralığını seçebilirsiniz.' );
		$items[] = array( 'q' => 'Çiçeğe not kartı ekleyebilir miyim?', 'a' => 'Evet, her siparişe ücretsiz kişiye özel not kartı eklenir. Mesajınızı sipariş sırasında yazabilir, isterseniz isminizi gizleyebilirsiniz.' );
		$items[] = array( 'q' => 'Çiçekler fotoğraftakiyle aynı mı olur?', 'a' => 'Tasarım fotoğraftaki renk, boyut ve havayı korur. Çiçekler doğal ürün olduğundan mevsime göre tür veya tonlarda küçük farklılıklar olabilir.' );
		$items[] = array( 'q' => 'Çiçeğimin ömrünü nasıl uzatırım?', 'a' => 'Vazodaki suyu iki günde bir değiştirin, sapları her seferinde 1–2 cm verev kesin, çiçekleri doğrudan güneşten, kalorifer ve meyve yanından uzak tutun. Kutudaki çiçeklerde süngeri nemli tutmanız yeterlidir.' );
		$items[] = array( 'q' => 'Hangi ödeme yöntemleri var?', 'a' => $pay . ' ile ödeme yapabilirsiniz.' );
		return $items;
	}
	$items[] = array( 'q' => $f['city'] . '’e aynı gün ' . $lower . ' gönderebilir miyim?', 'a' => 'Evet. Saat ' . $f['cutoff'] . '’a kadar verilen ' . $lower . ' siparişleri ' . $f['city'] . ' içinde aynı gün teslim edilir. Teslimat tarihini ve saat aralığını sipariş sırasında siz seçersiniz.' );
	$items[] = array( 'q' => 'Hangi ilçelere teslimat yapıyorsunuz?', 'a' => $dist . '. ' . df_aeo_fee_text() );
	$items[] = array( 'q' => $name . ' siparişine not kartı ve hediye eklenebilir mi?', 'a' => 'Her siparişte kişiye özel not kartı ücretsizdir. Ürün sayfasındaki “Hediye Ekle” adımından çikolata, pasta veya peluş gibi hediyeleri de aynı teslimata ekleyebilirsiniz.' );
	$items[] = array( 'q' => 'Ödeme nasıl yapılır, güvenli mi?', 'a' => $pay . ' ile ödeme yapabilirsiniz. Kart ödemeleri 3D Secure ile lisanslı ödeme kuruluşu üzerinden alınır; kart bilgileriniz sitemizde saklanmaz.' );
	if ( $f['phone'] ) {
		$items[] = array( 'q' => 'Siparişimle ilgili size nasıl ulaşırım?', 'a' => $f['phone'] . ( $f['whatsapp'] ? ' numaralı telefondan veya WhatsApp’tan' : ' numaralı telefondan' ) . ' bize ulaşabilirsiniz.' . ( $f['hours'] ? ' Çalışma saatlerimiz: ' . $f['hours'] . '.' : '' ) );
	}
	return $items;
}

/**
 * Kategori sayfasında SSS (ürünlerin altında).
 */
function df_aeo_category_faq() {
	if ( ! df_opt( 'aeo_faq', 1 ) || is_paged() || ! function_exists( 'is_product_taxonomy' ) || ! is_product_taxonomy() ) {
		return;
	}
	$t = get_queried_object();
	if ( ! $t instanceof WP_Term ) {
		return;
	}
	$h1 = trim( (string) get_term_meta( $t->term_id, 'df_h1', true ) );
	echo '<section class="df-faq-section df-faq-section--cat" id="sss"><div class="df-container df-faq-section__grid">';
	echo '<div class="df-faq-section__head"><p class="df-eyebrow">Merak edilenler</p><h2>' . esc_html( ( $h1 ? $h1 : $t->name ) . ' hakkında sıkça sorulanlar' ) . '</h2></div>';
	df_faq_list( df_aeo_faq( $t->name, 'category' ) );
	echo '</div></section>';
}

/**
 * Rank Math'in işletme şemasına hizmet bölgeleri, sosyal hesaplar ve iletişim ekle.
 *
 * @param array $data Şema.
 * @return array
 */
function df_aeo_rank_math_schema( $data ) {
	foreach ( $data as $k => $entity ) {
		$type = isset( $entity['@type'] ) ? (array) $entity['@type'] : array();
		if ( ! array_intersect( $type, array( 'Florist', 'LocalBusiness', 'Organization', 'Store' ) ) ) {
			continue;
		}
		$data[ $k ]['areaServed'] = df_aeo_area_served();
		$same                     = function_exists( 'df_social_links' ) ? array_values( df_social_links() ) : array();
		if ( $same ) {
			$data[ $k ]['sameAs'] = array_values( array_unique( array_merge( (array) ( $entity['sameAs'] ?? array() ), $same ) ) );
		}
		$f = df_aeo_facts();
		if ( $f['phone'] && empty( $entity['telephone'] ) ) {
			$data[ $k ]['telephone'] = $f['phone'];
		}
		if ( empty( $entity['priceRange'] ) ) {
			$data[ $k ]['priceRange'] = '₺₺';
		}
		$data[ $k ]['paymentAccepted'] = $f['payments'] ? implode( ', ', $f['payments'] ) : 'Kredi kartı, Banka kartı, Havale/EFT';
		$data[ $k ]['currenciesAccepted'] = 'TRY';
	}
	return $data;
}
add_filter( 'rank_math/json_ld', 'df_aeo_rank_math_schema', 99 );

/**
 * Hizmet verilen yerler (şema).
 *
 * @return array
 */
function df_aeo_area_served() {
	$f   = df_aeo_facts();
	$out = array( array( '@type' => 'City', 'name' => $f['city'] ) );
	foreach ( $f['districts'] as $d ) {
		$out[] = array(
			'@type'            => 'AdministrativeArea',
			'name'             => $d . ', ' . $f['city'],
			'containedInPlace' => array( '@type' => 'City', 'name' => $f['city'] ),
		);
	}
	return $out;
}

/**
 * llms.txt içeriği.
 *
 * @return string
 */
function df_aeo_llms() {
	$f     = df_aeo_facts();
	$L     = array();
	$L[]   = '# ' . $f['site'];
	$L[]   = '';
	$L[]   = '> ' . $f['site'] . ', ' . $f['city'] . '’de online çiçek siparişi ve aynı gün çiçek teslimatı yapan çiçek atölyesidir. Buketler, güller, orkideler, kutuda ve vazoda çiçekler; doğum günü, sevgiliye, yıl dönümü, söz–nişan, geçmiş olsun ve yeni doğan gibi özel günler için tasarımlar sunar.';
	$L[]   = '';
	$L[]   = '## Temel bilgiler';
	$L[]   = '- Hizmet bölgesi: ' . $f['city'] . ( $f['districts'] ? ' (' . implode( ', ', $f['districts'] ) . ')' : '' );
	$L[]   = '- Aynı gün teslimat: saat ' . $f['cutoff'] . '’a kadar verilen siparişler aynı gün teslim edilir; teslimat tarihi ve saat aralığı müşteri tarafından seçilir.';
	$L[]   = '- ' . df_aeo_fee_text();
	$L[]   = '- Her siparişte ücretsiz kişiye özel not kartı.';
	$L[]   = '- Ödeme: ' . ( $f['payments'] ? implode( ', ', $f['payments'] ) : 'kredi/banka kartı (3D Secure), havale/EFT' ) . '.';
	if ( $f['phone'] ) {
		$L[] = '- Telefon: ' . $f['phone'] . ( $f['whatsapp'] ? ' · WhatsApp: +' . $f['whatsapp'] : '' );
	}
	if ( $f['email'] ) {
		$L[] = '- E-posta: ' . $f['email'];
	}
	if ( $f['address'] ) {
		$L[] = '- Adres: ' . $f['address'];
	}
	if ( $f['hours'] ) {
		$L[] = '- Çalışma saatleri: ' . $f['hours'];
	}
	$L[] = '- Web sitesi: ' . home_url( '/' );

	$terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => true, 'number' => 40 ) );
	if ( ! is_wp_error( $terms ) && $terms ) {
		$L[] = '';
		$L[] = '## Çiçek kategorileri';
		foreach ( $terms as $t ) {
			if ( 'uncategorized' === $t->slug || (int) get_option( 'default_product_cat' ) === (int) $t->term_id ) {
				continue;
			}
			$d   = function_exists( 'df_cat_seo_meta' ) ? df_cat_seo_meta( $t )['desc'] : '';
			$L[] = '- [' . html_entity_decode( $t->name, ENT_QUOTES, 'UTF-8' ) . '](' . get_term_link( $t ) . ')' . ( $d ? ': ' . $d : '' );
		}
	}
	if ( function_exists( 'wc_get_products' ) ) {
		$products = wc_get_products( array( 'status' => 'publish', 'limit' => 40, 'orderby' => 'popularity', 'visibility' => 'catalog' ) );
		if ( $products ) {
			$L[] = '';
			$L[] = '## Ürünler (fiyatlar KDV dahil)';
			foreach ( $products as $p ) {
				$price = $p->get_price();
				$L[]   = '- [' . html_entity_decode( $p->get_name(), ENT_QUOTES, 'UTF-8' ) . '](' . $p->get_permalink() . ')' . ( '' !== (string) $price ? ': ' . ( $p->is_type( 'variable' ) ? number_format_i18n( (float) $p->get_variation_price( 'min' ), 0 ) . ' TL’den başlayan' : number_format_i18n( (float) $price, 0 ) . ' TL' ) : '' ) . ( $p->is_in_stock() ? '' : ' (tükendi)' );
			}
		}
	}
	if ( function_exists( 'df_loc_items' ) && df_loc_items() ) {
		$L[] = '';
		$L[] = '## İlçe sayfaları';
		foreach ( df_loc_items() as $slug => $loc ) {
			$L[] = '- [' . df_loc_vars( df_opt( 'loc_title', '{ilce} Çiçek Siparişi' ), $loc ) . '](' . df_loc_url( $slug ) . ')';
		}
	}
	$L[] = '';
	$L[] = '## Sıkça sorulan sorular';
	foreach ( df_aeo_faq( 'Çiçek', 'category' ) as $qa ) {
		$L[] = '- **' . $qa['q'] . '** ' . $qa['a'];
	}
	$pages = array();
	foreach ( array( 'teslimat-bolgeleri', 'hakkimizda', 'iletisim', 'mesafeli-satis-sozlesmesi', 'iade-ve-iptal-kosullari', 'kvkk-aydinlatma-metni', 'gizlilik-ve-cerez-politikasi' ) as $slug ) {
		$pg = get_page_by_path( $slug );
		if ( $pg && 'publish' === $pg->post_status ) {
			$pages[] = '- [' . get_the_title( $pg ) . '](' . get_permalink( $pg ) . ')';
		}
	}
	if ( $pages ) {
		$L[] = '';
		$L[] = '## Sayfalar ve politikalar';
		$L   = array_merge( $L, $pages );
	}
	$posts = get_posts( array( 'numberposts' => 15, 'post_status' => 'publish' ) );
	if ( $posts ) {
		$L[] = '';
		$L[] = '## Blog';
		foreach ( $posts as $post ) {
			$L[] = '- [' . html_entity_decode( get_the_title( $post ), ENT_QUOTES, 'UTF-8' ) . '](' . get_permalink( $post ) . ')';
		}
	}
	return implode( "\n", $L ) . "\n";
}
