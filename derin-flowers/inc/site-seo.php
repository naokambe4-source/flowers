<?php
/**
 * Genel SEO (SEO eklentisi kurulu değilken): ana sayfa, sayfalar, yazılar, ürünler ve mağaza için
 * sayfa başlığı, Google açıklaması ve sosyal paylaşım (Open Graph) etiketleri; temiz site haritası.
 * Kategori ve ilçe sayfaları kendi modüllerindedir (category-seo.php, local-seo.php).
 * Rank Math / Yoast kurulursa bu modül başlık ve açıklamaya karışmaz.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'df_seo_plugin' ) ) {
	/**
	 * SEO eklentisi var mı? (WooCommerce kapalıyken.)
	 *
	 * @return bool
	 */
	function df_seo_plugin() {
		return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' );
	}
}

/**
 * Hazır sayfalar için başlık / açıklama (alan boşsa kullanılır).
 *
 * @return array<string,array{0:string,1:string}>
 */
function df_seo_page_defaults() {
	$city = df_opt( 'df_default_city', 'İzmir' );
	$site = get_bloginfo( 'name' );
	return array(
		'hakkimizda'          => array( 'Hakkımızda | ' . $site . ' – ' . $city . ' Çiçekçi', $site . ' hakkında: ' . $city . '’de özenle hazırlanan buketler, güller ve aranjmanlarla sevdiklerinizin özel anlarına eşlik eden çiçek atölyesi.' ),
		'iletisim'            => array( 'İletişim | ' . $site . ' ' . $city, $site . ' iletişim bilgileri: telefon, WhatsApp, adres ve çalışma saatleri. ' . $city . ' çiçek siparişleriniz için bize hemen ulaşın.' ),
		'teslimat-bolgeleri'  => array( 'Teslimat Bölgeleri ve Ücretleri | ' . $city . ' – ' . $site, $city . ' içinde çiçek teslimatı yaptığımız ilçeler, teslimat ücretleri ve saat aralıkları. Aynı gün teslimat için son sipariş saatini öğrenin.' ),
		'blog'                => array( 'Blog | Çiçek Bakımı ve Hediye Önerileri – ' . $site, 'Çiçek bakımı, özel günler için hediye önerileri, çiçeklerin anlamları ve ' . $city . '’den çiçek haberleri ' . $site . ' blogunda.' ),
		'siparis-takip'       => array( 'Sipariş Takip | ' . $site, 'Sipariş numaranız ve e-posta adresinizle çiçek siparişinizin durumunu anında takip edin.' ),
	);
}

/**
 * Görüntülenen sayfa için SEO başlık ve açıklaması (yalnızca bu modülün kapsadığı sayfalarda dolu).
 *
 * @return array{title:string,desc:string,image:string,type:string,url:string}|null
 */
function df_site_seo_data() {
	static $cache = false;
	if ( false !== $cache ) {
		return $cache;
	}
	$cache = null;
	if ( is_admin() || is_feed() || ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) || ( function_exists( 'df_loc_current' ) && '' !== df_loc_current() ) ) {
		return $cache;
	}
	$city  = df_opt( 'df_default_city', 'İzmir' );
	$site  = get_bloginfo( 'name' );
	$image = df_img_url( df_opt( 'seo_og_image' ), 'large' );
	$d     = array(
		'title' => '',
		'desc'  => '',
		'image' => '',
		'type'  => 'website',
		'url'   => '',
	);
	if ( is_front_page() ) {
		$d['title'] = (string) df_opt( 'seo_home_title', '' );
		$d['desc']  = (string) df_opt( 'seo_home_desc', '' );
		$d['url']   = home_url( '/' );
	} elseif ( function_exists( 'is_shop' ) && is_shop() ) {
		$id         = wc_get_page_id( 'shop' );
		$d['title'] = (string) get_post_meta( $id, '_df_seo_title', true );
		$d['desc']  = (string) get_post_meta( $id, '_df_seo_desc', true );
		$d['title'] = $d['title'] ? $d['title'] : 'Tüm Çiçekler | ' . $city . ' Çiçek Siparişi – ' . $site;
		$d['desc']  = $d['desc'] ? $d['desc'] : $city . '’e aynı gün çiçek gönderin: buketler, güller, orkideler, kutuda ve vazoda çiçekler. Taze çiçekler, özenli paketleme ve ücretsiz not kartı.';
		$d['url']   = get_permalink( $id );
	} elseif ( is_singular() ) {
		$id         = get_queried_object_id();
		$post       = get_post( $id );
		$d['title'] = (string) get_post_meta( $id, '_df_seo_title', true );
		$d['desc']  = (string) get_post_meta( $id, '_df_seo_desc', true );
		$d['url']   = get_permalink( $id );
		$d['type']  = 'post' === $post->post_type ? 'article' : ( 'product' === $post->post_type ? 'product' : 'website' );
		$defaults   = df_seo_page_defaults();
		if ( 'page' === $post->post_type && isset( $defaults[ $post->post_name ] ) ) {
			$d['title'] = $d['title'] ? $d['title'] : $defaults[ $post->post_name ][0];
			$d['desc']  = $d['desc'] ? $d['desc'] : $defaults[ $post->post_name ][1];
		}
		if ( '' === $d['desc'] ) {
			$text      = (string) $post->post_excerpt;
			$text      = '' !== trim( $text ) ? $text : strip_shortcodes( (string) $post->post_content );
			$text      = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $text ) ) );
			$d['desc'] = $text;
		}
		if ( '' === $d['title'] && 'product' === $post->post_type ) {
			$long       = get_the_title( $id ) . ' | ' . $city . ' Çiçek Siparişi – ' . $site;
			$d['title'] = mb_strlen( $long ) <= 65 ? $long : get_the_title( $id ) . ' – ' . $site;
		}
		if ( '' === $d['desc'] && 'product' === $post->post_type ) {
			$d['desc'] = get_the_title( $id ) . ' — ' . $city . '’e aynı gün teslimat, taze çiçekler ve ücretsiz not kartı. ' . $site;
		}
		if ( has_post_thumbnail( $id ) ) {
			$image = (string) get_the_post_thumbnail_url( $id, 'large' );
		}
	} else {
		return $cache;
	}
	if ( '' !== $d['desc'] && mb_strlen( $d['desc'] ) > 160 ) {
		$cut       = mb_substr( $d['desc'], 0, 157 );
		$d['desc'] = preg_replace( '/\s+\S*$/u', '', $cut ) . '…';
	}
	$d['image'] = $image ? $image : df_img_url( df_opt( 'logo_image' ), 'large' );
	$cache      = $d;
	return $cache;
}

/**
 * Sayfa başlığı.
 *
 * @param string $title Başlık.
 * @return string
 */
function df_site_seo_title( $title ) {
	if ( df_seo_plugin() ) {
		return $title;
	}
	$d = df_site_seo_data();
	return ( $d && '' !== $d['title'] ) ? $d['title'] : $title;
}
add_filter( 'pre_get_document_title', 'df_site_seo_title', 30 );

/**
 * Açıklama ve sosyal paylaşım etiketleri.
 */
function df_site_seo_head() {
	if ( df_seo_plugin() ) {
		return;
	}
	$d = df_site_seo_data();
	if ( ! $d ) {
		return;
	}
	$title = '' !== $d['title'] ? $d['title'] : wp_get_document_title();
	// Ürün açıklamasını ürün modülü zaten yazıyorsa tekrar yazma.
	$product_desc = is_singular( 'product' ) && ( get_post_meta( get_queried_object_id(), '_df_seo_desc', true ) || get_post_field( 'post_excerpt', get_queried_object_id() ) );
	if ( '' !== $d['desc'] && ! $product_desc ) {
		echo '<meta name="description" content="' . esc_attr( $d['desc'] ) . '">' . "\n";
	}
	echo '<meta property="og:locale" content="tr_TR">' . "\n";
	echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '">' . "\n";
	echo '<meta property="og:type" content="' . esc_attr( 'product' === $d['type'] ? 'product' : $d['type'] ) . '">' . "\n";
	echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
	if ( '' !== $d['desc'] ) {
		echo '<meta property="og:description" content="' . esc_attr( $d['desc'] ) . '">' . "\n";
	}
	if ( $d['url'] ) {
		echo '<meta property="og:url" content="' . esc_url( $d['url'] ) . '">' . "\n";
	}
	if ( $d['image'] ) {
		echo '<meta property="og:image" content="' . esc_url( $d['image'] ) . '">' . "\n";
	}
	echo '<meta name="twitter:card" content="' . ( $d['image'] ? 'summary_large_image' : 'summary' ) . '">' . "\n";
}
add_action( 'wp_head', 'df_site_seo_head', 3 );

/**
 * Sayfa / yazı düzenleme ekranında "Google'da görünecek bilgiler" kutusu.
 */
function df_site_seo_metabox() {
	if ( df_seo_plugin() ) {
		return;
	}
	foreach ( array( 'page', 'post' ) as $type ) {
		add_meta_box( 'df_site_seo', 'Google’da görünecek bilgiler', 'df_site_seo_metabox_html', $type, 'normal', 'low' );
	}
}
add_action( 'add_meta_boxes', 'df_site_seo_metabox' );

/**
 * Kutu içeriği.
 *
 * @param WP_Post $post Yazı.
 */
function df_site_seo_metabox_html( $post ) {
	wp_nonce_field( 'df_site_seo', 'df_site_seo_nonce' );
	$def = df_seo_page_defaults();
	$ph  = isset( $def[ $post->post_name ] ) ? $def[ $post->post_name ] : array( get_the_title( $post ) . ' – ' . get_bloginfo( 'name' ), 'Boş bırakılırsa sayfa metninin başı kullanılır.' );
	?>
	<p><label for="df_seo_t"><strong>Sayfa başlığı</strong> <small>(en fazla 60 karakter)</small></label><br>
	<input type="text" id="df_seo_t" name="df_seo_t" class="widefat" maxlength="80" value="<?php echo esc_attr( get_post_meta( $post->ID, '_df_seo_title', true ) ); ?>" placeholder="<?php echo esc_attr( $ph[0] ); ?>"></p>
	<p><label for="df_seo_d"><strong>Google açıklaması</strong> <small>(140–155 karakter)</small></label><br>
	<textarea id="df_seo_d" name="df_seo_d" class="widefat" rows="2" maxlength="200" placeholder="<?php echo esc_attr( $ph[1] ); ?>"><?php echo esc_textarea( get_post_meta( $post->ID, '_df_seo_desc', true ) ); ?></textarea></p>
	<p class="description">Boş bırakılan alanlar otomatik doldurulur.</p>
	<?php
}

/**
 * Kaydet.
 *
 * @param int $post_id Yazı.
 */
function df_site_seo_save( $post_id ) {
	if ( ! isset( $_POST['df_site_seo_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['df_site_seo_nonce'] ) ), 'df_site_seo' ) || ! current_user_can( 'edit_post', $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}
	update_post_meta( $post_id, '_df_seo_title', isset( $_POST['df_seo_t'] ) ? sanitize_text_field( wp_unslash( $_POST['df_seo_t'] ) ) : '' );
	update_post_meta( $post_id, '_df_seo_desc', isset( $_POST['df_seo_d'] ) ? sanitize_textarea_field( wp_unslash( $_POST['df_seo_d'] ) ) : '' );
}
add_action( 'save_post', 'df_site_seo_save' );

/**
 * Site haritası: kullanıcı (yazar) listesini çıkar — yönetici kullanıcı adını göstermez.
 *
 * @param WP_Sitemaps_Provider $provider Sağlayıcı.
 * @param string               $name     Ad.
 * @return WP_Sitemaps_Provider|false
 */
function df_site_seo_no_users( $provider, $name ) {
	return 'users' === $name ? false : $provider;
}
add_filter( 'wp_sitemaps_add_provider', 'df_site_seo_no_users', 10, 2 );

/**
 * Site haritasına girmemesi gereken sayfalar (sepet, ödeme, hesap, favoriler, sipariş takip).
 *
 * @return int[]
 */
function df_site_seo_private_pages() {
	$ids = array();
	if ( function_exists( 'wc_get_page_id' ) ) {
		foreach ( array( 'cart', 'checkout', 'myaccount' ) as $p ) {
			$ids[] = (int) wc_get_page_id( $p );
		}
	}
	foreach ( array( 'favorilerim', 'siparis-takip' ) as $slug ) {
		$p = get_page_by_path( $slug );
		if ( $p ) {
			$ids[] = (int) $p->ID;
		}
	}
	return array_values( array_filter( $ids, fn( $i ) => $i > 0 ) );
}

/**
 * Sayfalar site haritasından çıkarılır.
 *
 * @param array  $args      Sorgu.
 * @param string $post_type Tür.
 * @return array
 */
function df_site_seo_sitemap_pages( $args, $post_type ) {
	if ( 'page' === $post_type ) {
		$args['post__not_in'] = array_merge( isset( $args['post__not_in'] ) ? (array) $args['post__not_in'] : array(), df_site_seo_private_pages() );
	}
	return $args;
}
add_filter( 'wp_sitemaps_posts_query_args', 'df_site_seo_sitemap_pages', 10, 2 );

/**
 * Yazar arşivleri ile favoriler / sipariş takip sayfası Google'da listelenmesin.
 *
 * @param array $robots Robots.
 * @return array
 */
function df_site_seo_robots( $robots ) {
	if ( is_author() || ( is_page() && in_array( get_queried_object_id(), df_site_seo_private_pages(), true ) ) ) {
		$robots['noindex'] = true;
		$robots['follow']  = true;
	}
	return $robots;
}
add_filter( 'wp_robots', 'df_site_seo_robots' );
