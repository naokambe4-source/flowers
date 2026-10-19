<?php
/**
 * Kategori SEO ve yapısal veri (Schema.org / JSON-LD):
 * - Her kategori için ana başlık (H1), sayfa başlığı (title), meta açıklama, anahtar kelimeler
 *   (Rank Math alanlarına da yazılır; Yoast'ta filtreyle uygulanır; eklenti yoksa tema basar)
 * - Kategori sayfasında CollectionPage + ürün listesi (ItemList) + içerik haritası (BreadcrumbList)
 * - Ana sayfada çiçekçi işletme (Florist) ve site araması (WebSite + SearchAction)
 * Ürün sayfalarının Product verisini WooCommerce zaten üretir.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Kategori SEO alanları.
 *
 * @param WP_Term $term Terim.
 */
function df_cat_seo_fields( $term ) {
	$v = function ( $k ) use ( $term ) {
		return (string) get_term_meta( $term->term_id, $k, true );
	};
	?>
	<tr class="form-field df-catseo-head"><th colspan="2"><h2 style="margin:24px 0 0">SEO — Google'da görünecek bilgiler</h2><p class="description">Boş bırakılan alanlar otomatik doldurulur. Örnek "Kırmızı Güller" için: Sayfa başlığı "Kırmızı Gül Buketleri | İzmir Çiçek Siparişi – Derin Flowers".</p></th></tr>
	<tr class="form-field">
		<th scope="row"><label for="df_h1">Ana başlık (H1)</label></th>
		<td><input type="text" name="df_h1" id="df_h1" value="<?php echo esc_attr( $v( 'df_h1' ) ); ?>" placeholder="<?php echo esc_attr( $term->name ); ?>"><p class="description">Sayfada görünen büyük başlık. Boşsa kategori adı kullanılır.</p></td>
	</tr>
	<tr class="form-field">
		<th scope="row"><label for="df_cat_seo_title">Sayfa başlığı (title)</label></th>
		<td><input type="text" name="df_cat_seo_title" id="df_cat_seo_title" maxlength="80" value="<?php echo esc_attr( $v( 'df_seo_title' ) ); ?>" placeholder="<?php echo esc_attr( $term->name . ' | İzmir Çiçek Siparişi – ' . get_bloginfo( 'name' ) ); ?>" data-df-count="60"><p class="description"><span data-df-count-out></span> En fazla 60 karakter önerilir.</p></td>
	</tr>
	<tr class="form-field">
		<th scope="row"><label for="df_cat_seo_desc">Meta açıklama</label></th>
		<td><textarea name="df_cat_seo_desc" id="df_cat_seo_desc" rows="2" maxlength="200" data-df-count="155"><?php echo esc_textarea( $v( 'df_seo_desc' ) ); ?></textarea><p class="description"><span data-df-count-out></span> 140–155 karakter önerilir. Google arama sonucunda başlığın altında görünür.</p></td>
	</tr>
	<tr class="form-field">
		<th scope="row"><label for="df_cat_seo_kw">Anahtar kelimeler</label></th>
		<td><input type="text" name="df_cat_seo_kw" id="df_cat_seo_kw" value="<?php echo esc_attr( $v( 'df_seo_kw' ) ); ?>" placeholder="kırmızı gül buketi, İzmir kırmızı gül siparişi, kırmızı gül gönder"><p class="description">Virgülle. İlki "odak anahtar kelime" olarak Rank Math'e aktarılır. Kelimeleri açıklama metninde doğal biçimde kullanın, art arda yığmayın.</p></td>
	</tr>
	<script>
	document.querySelectorAll( '[data-df-count]' ).forEach( function ( el ) {
		var out = el.parentNode.querySelector( '[data-df-count-out]' ), max = +el.getAttribute( 'data-df-count' );
		function upd() { var n = el.value.length; out.textContent = n + ' / ' + max + ' karakter.'; out.style.color = n > max ? '#b32d2e' : ''; }
		el.addEventListener( 'input', upd ); upd();
	} );
	</script>
	<?php
}
add_action( 'product_cat_edit_form_fields', 'df_cat_seo_fields', 40 );

/**
 * Kaydet (Rank Math alanlarına da yaz).
 *
 * @param int $term_id Terim.
 */
function df_cat_seo_save( $term_id ) {
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- WordPress terim düzenleme formu kendi nonce'unu doğrular.
	if ( ! current_user_can( 'manage_product_terms' ) && ! current_user_can( 'manage_categories' ) ) {
		return;
	}
	$map = array(
		'df_h1'            => array( 'df_h1', '' ),
		'df_cat_seo_title' => array( 'df_seo_title', 'rank_math_title' ),
		'df_cat_seo_desc'  => array( 'df_seo_desc', 'rank_math_description' ),
		'df_cat_seo_kw'    => array( 'df_seo_kw', '' ),
	);
	foreach ( $map as $field => $keys ) {
		if ( ! isset( $_POST[ $field ] ) ) {
			continue;
		}
		$val = 'df_cat_seo_desc' === $field ? sanitize_textarea_field( wp_unslash( $_POST[ $field ] ) ) : sanitize_text_field( wp_unslash( $_POST[ $field ] ) );
		update_term_meta( $term_id, $keys[0], $val );
		if ( $keys[1] ) {
			if ( '' === $val ) {
				delete_term_meta( $term_id, $keys[1] );
			} else {
				update_term_meta( $term_id, $keys[1], $val );
			}
		}
		if ( 'df_cat_seo_kw' === $field ) {
			$first = trim( (string) strtok( $val, ',' ) );
			if ( '' !== $first ) {
				update_term_meta( $term_id, 'rank_math_focus_keyword', $first );
			}
		}
	}
	// phpcs:enable
}
add_action( 'edited_product_cat', 'df_cat_seo_save', 20 );

/**
 * Şu anki kategori (ürün kategorisi / etiketi sayfası).
 *
 * @return WP_Term|null
 */
function df_cat_seo_term() {
	if ( ! function_exists( 'is_product_taxonomy' ) || ! is_product_taxonomy() ) {
		return null;
	}
	$t = get_queried_object();
	return $t instanceof WP_Term ? $t : null;
}

/**
 * Kategori sayfa başlığı ve açıklaması (otomatik yedekli).
 *
 * @param WP_Term $t Terim.
 * @return array{title:string,desc:string}
 */
function df_cat_seo_meta( $t ) {
	$title = trim( (string) get_term_meta( $t->term_id, 'df_seo_title', true ) );
	$desc  = trim( (string) get_term_meta( $t->term_id, 'df_seo_desc', true ) );
	if ( '' === $title ) {
		$title = $t->name . ' | ' . df_opt( 'df_default_city', 'İzmir' ) . ' Çiçek Siparişi – ' . get_bloginfo( 'name' );
	}
	if ( '' === $desc ) {
		$plain = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( function_exists( 'df_md_lite' ) ? df_md_lite( $t->description ) : $t->description ) ) );
		$desc  = '' !== $plain ? mb_substr( $plain, 0, 155 ) : $t->name . ' — ' . df_opt( 'df_default_city', 'İzmir' ) . ' içi aynı gün teslimat, taze çiçekler ve ücretsiz not kartı. ' . get_bloginfo( 'name' );
	}
	return array(
		'title' => $title,
		'desc'  => $desc,
	);
}

/**
 * Belge başlığı (SEO eklentisi yoksa / Yoast'ta).
 *
 * @param string $title Başlık.
 * @return string
 */
function df_cat_seo_title( $title ) {
	$t = df_cat_seo_term();
	if ( ! $t || ( defined( 'RANK_MATH_VERSION' ) && 'pre_get_document_title' === current_filter() ) ) {
		return $title;
	}
	$own = trim( (string) get_term_meta( $t->term_id, 'df_seo_title', true ) );
	if ( 'wpseo_title' === current_filter() && '' === $own ) {
		return $title; // Yoast kendi ayarını kullansın.
	}
	if ( defined( 'WPSEO_VERSION' ) && 'pre_get_document_title' === current_filter() ) {
		return $title;
	}
	return df_cat_seo_meta( $t )['title'] . ( is_paged() ? ' – Sayfa ' . max( 1, (int) get_query_var( 'paged' ) ) : '' );
}
add_filter( 'pre_get_document_title', 'df_cat_seo_title', 25 );
add_filter( 'wpseo_title', 'df_cat_seo_title', 25 );

/**
 * Yoast meta açıklaması.
 *
 * @param string $d Açıklama.
 * @return string
 */
function df_cat_seo_yoast_desc( $d ) {
	$t = df_cat_seo_term();
	if ( $t ) {
		$own = trim( (string) get_term_meta( $t->term_id, 'df_seo_desc', true ) );
		return '' !== $own ? $own : $d;
	}
	return $d;
}
add_filter( 'wpseo_metadesc', 'df_cat_seo_yoast_desc', 25 );

/**
 * Eklenti yoksa: meta açıklama, canonical, Open Graph.
 */
function df_cat_seo_head() {
	$t = df_cat_seo_term();
	if ( ! $t || ( function_exists( 'df_seo_plugin' ) && df_seo_plugin() ) ) {
		return;
	}
	$m   = df_cat_seo_meta( $t );
	$url = get_term_link( $t );
	if ( is_paged() ) {
		$url = get_pagenum_link( max( 1, (int) get_query_var( 'paged' ) ) );
	}
	$img = absint( get_term_meta( $t->term_id, 'df_banner', true ) );
	echo '<meta name="description" content="' . esc_attr( $m['desc'] ) . '">' . "\n";
	echo '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n";
	echo '<meta property="og:type" content="website"><meta property="og:title" content="' . esc_attr( $m['title'] ) . '"><meta property="og:description" content="' . esc_attr( $m['desc'] ) . '"><meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
	if ( $img ) {
		echo '<meta property="og:image" content="' . esc_url( (string) wp_get_attachment_image_url( $img, 'large' ) ) . '">' . "\n";
	}
}
add_action( 'wp_head', 'df_cat_seo_head', 2 );

/**
 * Kategori sayfası yapısal verisi: CollectionPage + ItemList + BreadcrumbList.
 */
function df_cat_schema() {
	$t = df_cat_seo_term();
	if ( ! $t ) {
		return;
	}
	global $wp_query;
	$m     = df_cat_seo_meta( $t );
	$url   = get_term_link( $t );
	$items = array();
	$pos   = 0;
	foreach ( (array) $wp_query->posts as $post ) {
		$p = wc_get_product( $post );
		if ( ! $p ) {
			continue;
		}
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => ++$pos,
			'url'      => get_permalink( $p->get_id() ),
			'name'     => $p->get_name(),
		);
	}
	$crumbs = array(
		array( '@type' => 'ListItem', 'position' => 1, 'name' => 'Ana Sayfa', 'item' => home_url( '/' ) ),
	);
	$anc = array_reverse( get_ancestors( $t->term_id, $t->taxonomy ) );
	foreach ( $anc as $aid ) {
		$a = get_term( $aid, $t->taxonomy );
		if ( $a && ! is_wp_error( $a ) ) {
			$crumbs[] = array( '@type' => 'ListItem', 'position' => count( $crumbs ) + 1, 'name' => $a->name, 'item' => get_term_link( $a ) );
		}
	}
	$crumbs[] = array( '@type' => 'ListItem', 'position' => count( $crumbs ) + 1, 'name' => $t->name, 'item' => $url );
	$graph    = array();
	$plugin   = function_exists( 'df_seo_plugin' ) && df_seo_plugin();
	if ( ! $plugin ) {
		$graph[] = array(
			'@type'       => 'CollectionPage',
			'@id'         => $url . '#webpage',
			'url'         => $url,
			'name'        => $m['title'],
			'description' => $m['desc'],
			'inLanguage'  => 'tr-TR',
			'isPartOf'    => array( '@id' => home_url( '/' ) . '#website' ),
		);
		// İçerik haritası (BreadcrumbList) WooCommerce tarafından zaten basılıyor; tekrarlanmaz.
		unset( $crumbs );
	}
	if ( $items ) {
		$graph[] = array(
			'@type'           => 'ItemList',
			'name'            => (string) ( get_term_meta( $t->term_id, 'df_h1', true ) ? get_term_meta( $t->term_id, 'df_h1', true ) : $t->name ),
			'numberOfItems'   => count( $items ),
			'itemListElement' => $items,
		);
	}
	if ( $graph ) {
		echo '<script type="application/ld+json">' . wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => $graph ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
	}
}
add_action( 'wp_head', 'df_cat_schema', 30 );

/**
 * Ana sayfa yapısal verisi: çiçekçi işletme + site araması (SEO eklentisi yoksa).
 */
function df_home_schema() {
	if ( ! is_front_page() || ( function_exists( 'df_seo_plugin' ) && df_seo_plugin() ) ) {
		return;
	}
	$home = home_url( '/' );
	$biz  = array(
		'@type' => 'Florist',
		'@id'   => $home . '#florist',
		'name'  => get_bloginfo( 'name' ),
		'url'   => $home,
	);
	if ( get_bloginfo( 'description' ) ) {
		$biz['description'] = get_bloginfo( 'description' );
	}
	if ( df_opt( 'contact_phone1' ) ) {
		$biz['telephone'] = df_opt( 'contact_phone1' );
	}
	if ( df_opt( 'contact_email' ) ) {
		$biz['email'] = df_opt( 'contact_email' );
	}
	if ( df_opt( 'contact_address' ) ) {
		$biz['address'] = array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( df_opt( 'contact_address' ) ) ) ),
			'addressLocality' => df_opt( 'df_default_city', 'İzmir' ),
			'addressCountry'  => 'TR',
		);
	}
	$logo = absint( df_opt( 'logo_image' ) );
	if ( $logo ) {
		$biz['logo']  = wp_get_attachment_image_url( $logo, 'full' );
		$biz['image'] = $biz['logo'];
	}
	$same = array_values( function_exists( 'df_social_links' ) ? df_social_links() : array() );
	if ( $same ) {
		$biz['sameAs'] = $same;
	}
	$biz['areaServed'] = array( '@type' => 'City', 'name' => df_opt( 'df_default_city', 'İzmir' ) );
	$site              = array(
		'@type'           => 'WebSite',
		'@id'             => $home . '#website',
		'url'             => $home,
		'name'            => get_bloginfo( 'name' ),
		'inLanguage'      => 'tr-TR',
		'publisher'       => array( '@id' => $home . '#florist' ),
		'potentialAction' => array(
			'@type'       => 'SearchAction',
			'target'      => add_query_arg( array( 's' => '{search_term_string}', 'post_type' => 'product' ), $home ),
			'query-input' => 'required name=search_term_string',
		),
	);
	echo '<script type="application/ld+json">' . str_replace( '%7Bsearch_term_string%7D', '{search_term_string}', wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => array( $biz, $site ) ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ) . '</script>' . "\n";
}
add_action( 'wp_head', 'df_home_schema', 30 );

/**
 * Hazır SEO metni (data/category-seo.php): önce adres (slug), sonra sadeleştirilmiş ad, sonra yakın eşleşme.
 * Listede olmayan kategoriler için ada göre değişen bir şablon üretilir.
 *
 * @param WP_Term $t Terim.
 * @return array{h1:string,title:string,desc:string,kw:string,text:string}
 */
function df_cat_seo_preset( $t ) {
	static $data = null;
	if ( null === $data ) {
		$data = (array) include DF_DIR . '/data/category-seo.php';
	}
	$alias = array(
		'buket'           => 'buketler',
		'gul'             => 'guller',
		'gul-buketleri'   => 'guller',
		'orkide'          => 'orkideler',
		'kutuda-cicekler' => 'kutuda-cicek',
		'vazoda-cicekler' => 'vazoda-cicek',
		'ozel-gun'        => 'ozel-gunler',
		'dogum-gunu-cicekleri' => 'dogum-gunu',
		'sevgiliye-cicek' => 'sevgiliye',
		'tebrik'          => 'tebrik-yeni-is',
		'yeni-is'         => 'tebrik-yeni-is',
		'yeni-dogan-cicekleri' => 'yeni-dogan',
		'yildonumu'       => 'yil-donumu',
		'soz-ve-nisan'    => 'soz-nisan',
		'nisan'           => 'soz-nisan',
		'koleksiyon'      => 'koleksiyonlar',
		'hediye'          => 'hediyeler',
		'saksi'           => 'saksi-cicekleri',
		'acilis-cicekleri' => 'acilis',
		'kirmizi-gul'     => 'kirmizi-guller',
		'beyaz-gul'       => 'beyaz-guller',
		'pembe-gul'       => 'pembe-guller',
	);
	foreach ( array( $t->slug, sanitize_title( $t->name ) ) as $k ) {
		$k = isset( $alias[ $k ] ) ? $alias[ $k ] : $k;
		if ( isset( $data[ $k ] ) ) {
			return $data[ $k ];
		}
	}
	$city = df_opt( 'df_default_city', 'İzmir' );
	$site = get_bloginfo( 'name' );
	$n    = $t->name;
	$lo   = function_exists( 'df_ai_lower' ) ? df_ai_lower( $n ) : mb_strtolower( $n );
	$v    = abs( crc32( $t->slug ) ) % 3;
	$titles = array(
		$n . ' | ' . $city . ' Çiçek Siparişi – ' . $site,
		$n . ' | ' . $city . ' Aynı Gün Teslimat – ' . $site,
		$n . ' | ' . $city . ' Online Çiçek – ' . $site,
	);
	$descs = array(
		$city . '’e aynı gün ' . $lo . ' gönderin. Taze çiçekler, özenli paketleme ve ücretsiz kişiye özel not kartı ile ' . $site . '.',
		$n . ' seçenekleri ' . $site . '’ta: ' . $city . ' içi aynı gün teslimat, teslimat gününü ve saatini siz seçin, not kartı ücretsiz.',
		$site . ' atölyesinde elde hazırlanan ' . $lo . '. ' . $city . ' içi aynı gün teslimat ve ücretsiz not kartıyla online sipariş.',
	);
	return array(
		'h1'    => $n,
		'title' => mb_strlen( $titles[ $v ] ) > 65 ? $n . ' | ' . $city . ' – ' . $site : $titles[ $v ],
		'desc'  => mb_substr( $descs[ $v ], 0, 158 ),
		'kw'    => $lo . ', ' . $city . ' ' . $lo . ', ' . $lo . ' gönder',
		'text'  => '',
	);
}

/**
 * Boş SEO alanlarını hazır metinlerle doldur (dolu alana dokunmaz). Rank Math alanlarına da yazar.
 *
 * @return int Güncellenen kategori sayısı.
 */
function df_cat_seo_fill() {
	$terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
	if ( is_wp_error( $terms ) ) {
		return 0;
	}
	$done = 0;
	foreach ( $terms as $t ) {
		if ( 'uncategorized' === $t->slug || (int) get_option( 'default_product_cat' ) === (int) $t->term_id ) {
			continue;
		}
		$p   = df_cat_seo_preset( $t );
		$set = array(
			'df_h1'        => $p['h1'],
			'df_seo_title' => $p['title'],
			'df_seo_desc'  => $p['desc'],
			'df_seo_kw'    => $p['kw'],
		);
		$mirror = array(
			'df_seo_title' => 'rank_math_title',
			'df_seo_desc'  => 'rank_math_description',
		);
		$changed = false;
		foreach ( $set as $key => $val ) {
			if ( '' === $val || '' !== trim( (string) get_term_meta( $t->term_id, $key, true ) ) ) {
				continue;
			}
			update_term_meta( $t->term_id, $key, $val );
			if ( isset( $mirror[ $key ] ) && '' === (string) get_term_meta( $t->term_id, $mirror[ $key ], true ) ) {
				update_term_meta( $t->term_id, $mirror[ $key ], $val );
			}
			if ( 'df_seo_kw' === $key && '' === (string) get_term_meta( $t->term_id, 'rank_math_focus_keyword', true ) ) {
				update_term_meta( $t->term_id, 'rank_math_focus_keyword', trim( (string) strtok( $val, ',' ) ) );
			}
			$changed = true;
		}
		if ( '' !== $p['text'] && '' === trim( (string) $t->description ) ) {
			wp_update_term( $t->term_id, 'product_cat', array( 'description' => $p['text'] ) );
			$changed = true;
		}
		$done += $changed ? 1 : 0;
	}
	return $done;
}

/**
 * Tema güncellenince bir kez otomatik doldur.
 */
function df_cat_seo_autofill() {
	if ( get_option( 'df_cat_seo_seeded' ) || ! current_user_can( 'manage_options' ) || ! taxonomy_exists( 'product_cat' ) ) {
		return;
	}
	update_option( 'df_cat_seo_seeded', DF_VERSION, false );
	df_cat_seo_fill();
}
add_action( 'admin_init', 'df_cat_seo_autofill' );

/**
 * Kategoriler listesinde "SEO alanlarını doldur" düğmesi.
 */
function df_cat_seo_fill_button() {
	$url = wp_nonce_url( admin_url( 'admin-post.php?action=df_cat_seo_fill' ), 'df_cat_seo_fill' );
	echo '<div class="notice notice-info inline" style="margin:12px 0;padding:10px 12px"><p style="margin:0"><strong>Kategori SEO’ları:</strong> Boş kalan H1, sayfa başlığı, Google açıklaması, anahtar kelimeler ve alt açıklama her kategori için hazır, özgün metinlerle doldurulur. Sizin yazdıklarınıza dokunulmaz. <a class="button button-primary" style="margin-left:8px" href="' . esc_url( $url ) . '">Boş SEO alanlarını doldur</a></p></div>';
}
add_action( 'product_cat_pre_add_form', 'df_cat_seo_fill_button' );

/**
 * Düğme işleyici.
 */
function df_cat_seo_fill_action() {
	if ( ! current_user_can( 'manage_product_terms' ) && ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Yetkiniz yok.' );
	}
	check_admin_referer( 'df_cat_seo_fill' );
	$n = df_cat_seo_fill();
	wp_safe_redirect( add_query_arg( array( 'taxonomy' => 'product_cat', 'post_type' => 'product', 'df_seo_filled' => $n ), admin_url( 'edit-tags.php' ) ) );
	exit;
}
add_action( 'admin_post_df_cat_seo_fill', 'df_cat_seo_fill_action' );

/**
 * Sonuç bildirimi.
 */
function df_cat_seo_fill_notice() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- yalnızca bilgi mesajı.
	if ( isset( $_GET['df_seo_filled'] ) ) {
		$n = absint( $_GET['df_seo_filled'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $n ? $n . ' kategorinin boş SEO alanları dolduruldu.' : 'Doldurulacak boş alan bulunamadı — tüm kategoriler zaten dolu.' ) . '</p></div>';
	}
}
add_action( 'admin_notices', 'df_cat_seo_fill_notice' );
