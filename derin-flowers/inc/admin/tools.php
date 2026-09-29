<?php
/**
 * Araçlar: kurulum yardımcısı, dışa/içe aktarma, sıfırlama, klasik ödeme dönüşümü.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Kurulumda oluşturulacak kategoriler.
 *
 * @return array
 */
function df_setup_categories() {
	return array(
		'buketler'      => array( 'Buketler', 'bouquet', 'En sevilen tasarımlar' ),
		'guller'        => array( 'Güller', 'rose', 'Zamansız bir zarafet' ),
		'orkideler'     => array( 'Orkideler', 'orchid', 'Uzun ömürlü şıklık' ),
		'kutuda-cicek'  => array( 'Kutuda Çiçek', 'box', 'Hediye etmenin en zarif hali' ),
		'vazoda-cicek'  => array( 'Vazoda Çiçek', 'vase', 'Masanıza mevsim ruhu' ),
		'ozel-gunler'   => array(
			'Özel Günler',
			'gift',
			'Her ana özel',
			array(
				'dogum-gunu'     => array( 'Doğum Günü', 'cake' ),
				'sevgiliye'      => array( 'Sevgiliye', 'heart' ),
				'tebrik-yeni-is' => array( 'Tebrik & Yeni İş', 'briefcase' ),
				'gecmis-olsun'   => array( 'Geçmiş Olsun', 'leaf' ),
				'yeni-dogan'     => array( 'Yeni Doğan', 'baby' ),
				'yil-donumu'     => array( 'Yıl Dönümü', 'star' ),
			),
		),
		'soz-nisan'     => array( 'Söz & Nişan', 'ring', 'En özel başlangıçlar' ),
		'koleksiyonlar' => array( 'Koleksiyonlar', 'crown', 'Signature tasarımlar' ),
	);
}

/**
 * Terim oluştur / getir.
 *
 * @param string $slug   Slug.
 * @param string $name   Ad.
 * @param string $icon   İkon.
 * @param int    $parent Üst.
 * @param string $sub    Alt başlık.
 * @return int
 */
function df_setup_term( $slug, $name, $icon, $parent = 0, $sub = '' ) {
	$term = get_term_by( 'slug', $slug, 'product_cat' );
	if ( $term ) {
		$id = (int) $term->term_id;
	} else {
		$res = wp_insert_term( $name, 'product_cat', array( 'slug' => $slug, 'parent' => $parent ) );
		if ( is_wp_error( $res ) ) {
			return 0;
		}
		$id = (int) $res['term_id'];
	}
	if ( ! get_term_meta( $id, 'df_icon', true ) ) {
		update_term_meta( $id, 'df_icon', $icon );
	}
	if ( $sub && ! get_term_meta( $id, 'df_subtitle', true ) ) {
		update_term_meta( $id, 'df_subtitle', $sub );
	}
	return $id;
}

/**
 * Sayfa oluştur / getir.
 *
 * @param string $slug    Slug.
 * @param string $title   Başlık.
 * @param string $content İçerik.
 * @return int
 */
function df_setup_page( $slug, $title, $content ) {
	$page = get_page_by_path( $slug );
	if ( $page ) {
		return (int) $page->ID;
	}
	return (int) wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_content' => $content,
		)
	);
}

/**
 * Menü oluştur (yoksa) ve konuma ata.
 *
 * @param string $name     Menü adı.
 * @param string $location Konum.
 * @param array  $items    Öğeler: [type, id|url, title, children?].
 */
function df_setup_menu( $name, $location, $items ) {
	$menu = wp_get_nav_menu_object( $name );
	if ( $menu ) {
		$menu_id = (int) $menu->term_id;
	} else {
		$menu_id = wp_create_nav_menu( $name );
		if ( is_wp_error( $menu_id ) ) {
			return;
		}
		$add = function ( $item, $parent = 0 ) use ( &$add, $menu_id ) {
			$args = array(
				'menu-item-status'    => 'publish',
				'menu-item-parent-id' => $parent,
				'menu-item-title'     => $item['title'],
			);
			if ( 'term' === $item['type'] ) {
				$args += array(
					'menu-item-type'      => 'taxonomy',
					'menu-item-object'    => 'product_cat',
					'menu-item-object-id' => $item['id'],
				);
			} elseif ( 'page' === $item['type'] ) {
				$args += array(
					'menu-item-type'      => 'post_type',
					'menu-item-object'    => 'page',
					'menu-item-object-id' => $item['id'],
				);
			} else {
				$args += array(
					'menu-item-type' => 'custom',
					'menu-item-url'  => $item['url'],
				);
			}
			$id = wp_update_nav_menu_item( $menu_id, 0, $args );
			if ( ! is_wp_error( $id ) && ! empty( $item['icon'] ) ) {
				update_post_meta( $id, '_df_icon', $item['icon'] );
			}
			if ( ! is_wp_error( $id ) && ! empty( $item['children'] ) ) {
				foreach ( $item['children'] as $child ) {
					$add( $child, $id );
				}
			}
		};
		foreach ( $items as $item ) {
			$add( $item );
		}
	}
	$locations              = get_theme_mod( 'nav_menu_locations', array() );
	$locations[ $location ] = $menu_id;
	set_theme_mod( 'nav_menu_locations', $locations );
}

/**
 * Kurulum yardımcısını çalıştırır.
 */
function df_run_setup() {
	if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'df_setup' ) ) {
		wp_die( 'Yetkiniz yok.' );
	}
	$do   = isset( $_POST['df_do'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['df_do'] ) ) : array();
	$log  = array();
	$opts = get_option( DF_OPTION, array() );
	$opts = array_merge( df_defaults(), is_array( $opts ) ? $opts : array() );
	$ids  = array();

	// 1. Kategoriler.
	if ( in_array( 'cats', $do, true ) && df_wc() ) {
		foreach ( df_setup_categories() as $slug => $cat ) {
			$ids[ $slug ] = df_setup_term( $slug, $cat[0], $cat[1], 0, $cat[2] );
			if ( ! empty( $cat[3] ) ) {
				foreach ( $cat[3] as $cslug => $child ) {
					$ids[ $cslug ] = df_setup_term( $cslug, $child[0], $child[1], $ids[ $slug ] );
				}
			}
		}
		// Ana sayfa bağlantılarını gerçek kategori adreslerine bağla.
		$link = function ( $slug ) use ( $ids ) {
			if ( empty( $ids[ $slug ] ) ) {
				return '';
			}
			$l = get_term_link( (int) $ids[ $slug ], 'product_cat' );
			return is_wp_error( $l ) ? '' : wp_make_link_relative( $l );
		};
		$cats = array();
		foreach ( array( 'buketler', 'guller', 'orkideler', 'kutuda-cicek' ) as $i => $slug ) {
			$prev   = isset( $opts['cats_items'][ $i ] ) ? $opts['cats_items'][ $i ] : array();
			$cats[] = array_merge(
				array( 'image' => '', 'title' => '', 'text' => '' ),
				$prev,
				array( 'cat' => $ids[ $slug ] )
			);
		}
		$opts['cats_items'] = $cats;
		foreach ( array( 'dogum-gunu', 'sevgiliye', 'tebrik-yeni-is' ) as $i => $slug ) {
			if ( isset( $opts['occ_items'][ $i ] ) ) {
				$opts['occ_items'][ $i ]['url'] = $link( $slug );
			}
		}
		$opts['ed_url']   = $link( 'soz-nisan' );
		$opts['duo1_url'] = $link( 'soz-nisan' );
		$opts['duo2_url'] = $link( 'orkideler' );
		$log[]            = 'Ürün kategorileri ikonlarıyla oluşturuldu ve ana sayfa kartlarına bağlandı.';
	}

	// 2. Sayfalar.
	$pages = array();
	if ( in_array( 'pages', $do, true ) ) {
		$pages['home']     = df_setup_page( 'ana-sayfa', 'Ana Sayfa', '' );
		$pages['blog']     = df_setup_page( 'blog', 'Blog', '' );
		$pages['track']    = df_setup_page( 'siparis-takip', 'Sipariş Takip', '<!-- wp:shortcode -->[derin_siparis_takip]<!-- /wp:shortcode -->' );
		$pages['wishlist'] = df_setup_page( 'favorilerim', 'Favorilerim', '<!-- wp:shortcode -->[derin_favoriler]<!-- /wp:shortcode -->' );
		$pages['zones']    = df_setup_page( 'teslimat-bolgeleri', 'Teslimat Bölgeleri', '<!-- wp:paragraph --><p>Siparişlerinizi İzmir\'in aşağıdaki bölgelerine, seçtiğiniz gün ve saat aralığında teslim ediyoruz. Teslimat ücreti ödeme adımında bölgenize göre otomatik hesaplanır.</p><!-- /wp:paragraph --><!-- wp:shortcode -->[derin_teslimat_bolgeleri]<!-- /wp:shortcode -->' );
		$pages['about']    = df_setup_page( 'hakkimizda', 'Hakkımızda', '<!-- wp:paragraph --><p>Derin Flowers, İzmir Alsancak\'taki atölyesinde her gün taze seçilen çiçeklerle özel anlar için tasarımlar hazırlar.</p><!-- /wp:paragraph -->' );
		$pages['contact']  = df_setup_page( 'iletisim', 'İletişim', '<!-- wp:shortcode -->[derin_iletisim]<!-- /wp:shortcode -->' );
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $pages['home'] );
		update_option( 'page_for_posts', $pages['blog'] );
		$opts['track_page']    = $pages['track'];
		$opts['wishlist_page'] = $pages['wishlist'];
		$opts['del_url']       = wp_make_link_relative( get_permalink( $pages['zones'] ) );
		$opts['story_url']     = wp_make_link_relative( get_permalink( $pages['about'] ) );
		if ( isset( $opts['hero_slides'][0] ) ) {
			$opts['hero_slides'][0]['btn2_url'] = wp_make_link_relative( get_permalink( $pages['zones'] ) );
		}
		$log[] = 'Sayfalar oluşturuldu (Ana Sayfa, Blog, Sipariş Takip, Favorilerim, Teslimat Bölgeleri, Hakkımızda, İletişim).';
	}

	// 3. Menüler.
	if ( in_array( 'menus', $do, true ) && df_wc() ) {
		$t = function ( $slug, $title ) {
			$term = get_term_by( 'slug', $slug, 'product_cat' );
			return $term ? array( 'type' => 'term', 'id' => $term->term_id, 'title' => $title ) : null;
		};
		$p = function ( $slug, $title ) {
			$page = get_page_by_path( $slug );
			return $page ? array( 'type' => 'page', 'id' => $page->ID, 'title' => $title ) : null;
		};
		$special = $t( 'ozel-gunler', 'Özel Günler' );
		if ( $special ) {
			$special['children'] = array_values( array_filter( array( $t( 'dogum-gunu', 'Doğum Günü' ), $t( 'sevgiliye', 'Sevgiliye' ), $t( 'tebrik-yeni-is', 'Tebrik & Yeni İş' ), $t( 'gecmis-olsun', 'Geçmiş Olsun' ), $t( 'yeni-dogan', 'Yeni Doğan' ), $t( 'yil-donumu', 'Yıl Dönümü' ) ) ) );
		}
		$primary = array_values(
			array_filter(
				array(
					array( 'type' => 'custom', 'url' => wc_get_page_permalink( 'shop' ), 'title' => 'Tüm Ürünler', 'icon' => 'grid' ),
					$t( 'buketler', 'Buketler' ),
					$t( 'guller', 'Güller' ),
					$t( 'orkideler', 'Orkideler' ),
					$t( 'kutuda-cicek', 'Kutuda Çiçek' ),
					$t( 'vazoda-cicek', 'Vazoda Çiçek' ),
					$special,
					$t( 'soz-nisan', 'Söz & Nişan' ),
					$t( 'koleksiyonlar', 'Koleksiyonlar' ),
					$p( 'blog', 'Blog' ),
					$p( 'iletisim', 'İletişim' ),
				)
			)
		);
		df_setup_menu( 'Ana Menü', 'primary', $primary );
		df_setup_menu( 'Footer — Hakkımızda', 'footer_1', array_values( array_filter( array( $p( 'hakkimizda', 'Hikayemiz' ), $p( 'blog', 'Çiçek Rehberi' ), $p( 'teslimat-bolgeleri', 'Teslimat Bölgeleri' ), $p( 'iletisim', 'İletişim' ) ) ) ) );
		df_setup_menu(
			'Footer — Müşteri Hizmetleri',
			'footer_2',
			array_values(
				array_filter(
					array(
						$p( 'siparis-takip', 'Sipariş Takip' ),
						array( 'type' => 'custom', 'url' => wc_get_page_permalink( 'myaccount' ), 'title' => 'Hesabım' ),
						$p( 'favorilerim', 'Favorilerim' ),
						array( 'type' => 'custom', 'url' => wc_get_page_permalink( 'cart' ), 'title' => 'Sepetim' ),
					)
				)
			)
		);
		$log[] = 'Ana menü ve footer menüleri oluşturuldu.';
	}

	// 4. Klasik sepet / ödeme ve üyelik.
	if ( in_array( 'checkout', $do, true ) && df_wc() ) {
		df_convert_to_classic_checkout();
		update_option( 'woocommerce_enable_myaccount_registration', 'yes' );
		update_option( 'woocommerce_enable_guest_checkout', 'yes' );
		update_option( 'woocommerce_enable_checkout_login_reminder', 'yes' );
		update_option( 'woocommerce_registration_generate_password', 'no' );
		$log[] = 'Sepet ve ödeme sayfaları çiçekçi akışına (klasik kısa kod) çevrildi, üyelik açıldı.';
	}

	$opts['__df_clean'] = 1;
	update_option( DF_OPTION, $opts );
	set_transient( 'df_setup_log', $log, 60 );
	wp_safe_redirect( admin_url( 'admin.php?page=derin-flowers-tools&setup=1' ) );
	exit;
}
add_action( 'admin_post_df_setup', 'df_run_setup' );

/**
 * Sepet ve ödeme sayfalarını klasik kısa koda çevirir (tema alanları blok ödemede çalışmaz).
 */
function df_convert_to_classic_checkout() {
	$map = array(
		'checkout' => '<!-- wp:shortcode -->[woocommerce_checkout]<!-- /wp:shortcode -->',
		'cart'     => '<!-- wp:shortcode -->[woocommerce_cart]<!-- /wp:shortcode -->',
	);
	foreach ( $map as $page => $content ) {
		$id = wc_get_page_id( $page );
		if ( $id > 0 ) {
			wp_update_post(
				array(
					'ID'           => $id,
					'post_content' => $content,
				)
			);
		}
	}
}

/**
 * Ödeme sayfası blok ise uyarı.
 */
function df_block_checkout_notice() {
	if ( ! df_wc() || ! current_user_can( 'manage_options' ) || ! df_opt( 'df_checkout_on' ) ) {
		return;
	}
	$id = wc_get_page_id( 'checkout' );
	if ( $id <= 0 || ! has_block( 'woocommerce/checkout', $id ) ) {
		return;
	}
	$url = wp_nonce_url( admin_url( 'admin-post.php?action=df_classic_checkout' ), 'df_classic_checkout' );
	echo '<div class="notice notice-warning"><p><strong>Derin Flowers:</strong> Ödeme sayfanız blok ödeme kullanıyor. Teslimat takvimi, alıcı bilgileri ve çiçek notu alanları için klasik ödeme gerekir. <a class="button button-primary" style="margin-left:8px" href="' . esc_url( $url ) . '">Tek tıkla dönüştür</a></p></div>';
}
add_action( 'admin_notices', 'df_block_checkout_notice' );

/**
 * Tek tıkla dönüştürme.
 */
function df_classic_checkout_action() {
	if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'df_classic_checkout' ) ) {
		wp_die( 'Yetkiniz yok.' );
	}
	df_convert_to_classic_checkout();
	wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url() );
	exit;
}
add_action( 'admin_post_df_classic_checkout', 'df_classic_checkout_action' );

/**
 * Dışa aktar.
 */
function df_export_settings() {
	if ( ! current_user_can( 'edit_theme_options' ) || ! check_admin_referer( 'df_export' ) ) {
		wp_die( 'Yetkiniz yok.' );
	}
	nocache_headers();
	header( 'Content-Type: application/json; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=derin-flowers-ayarlar-' . gmdate( 'Y-m-d' ) . '.json' );
	echo wp_json_encode( get_option( DF_OPTION, array() ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
	exit;
}
add_action( 'admin_post_df_export', 'df_export_settings' );

/**
 * İçe aktar / sıfırla.
 */
function df_import_settings() {
	if ( ! current_user_can( 'edit_theme_options' ) || ! check_admin_referer( 'df_import' ) ) {
		wp_die( 'Yetkiniz yok.' );
	}
	$mode = isset( $_POST['df_mode'] ) ? sanitize_key( wp_unslash( $_POST['df_mode'] ) ) : '';
	if ( 'reset' === $mode ) {
		delete_option( DF_OPTION );
		wp_safe_redirect( admin_url( 'admin.php?page=derin-flowers-tools&reset=1' ) );
		exit;
	}
	$json = isset( $_POST['df_json'] ) ? wp_unslash( $_POST['df_json'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- JSON aşağıda şemaya göre temizlenir.
	$data = json_decode( (string) $json, true );
	if ( ! is_array( $data ) ) {
		wp_safe_redirect( admin_url( 'admin.php?page=derin-flowers-tools&import=0' ) );
		exit;
	}
	$clean               = df_sanitize_options( $data );
	$clean['__df_clean'] = 1;
	update_option( DF_OPTION, $clean );
	wp_safe_redirect( admin_url( 'admin.php?page=derin-flowers-tools&import=1' ) );
	exit;
}
add_action( 'admin_post_df_import', 'df_import_settings' );

/**
 * Hazır düzen: görseldeki ana sayfa kurgusunu uygular (içerik ve görseller korunur).
 */
function df_apply_preset() {
	if ( ! current_user_can( 'edit_theme_options' ) || ! check_admin_referer( 'df_preset' ) ) {
		wp_die( 'Yetkiniz yok.' );
	}
	$opts  = array_merge( df_defaults(), (array) get_option( DF_OPTION, array() ) );
	$order = array( 'hero', 'occasions', 'popular', 'banners', 'categories', 'signature', 'editorial', 'bestsellers', 'trust', 'duo', 'delivery', 'story', 'blog', 'instagram', 'newsletter' );
	$on    = array();
	foreach ( (array) $opts['home_sections'] as $row ) {
		if ( ! empty( $row['id'] ) ) {
			$on[ $row['id'] ] = ! empty( $row['on'] );
		}
	}
	$list = array();
	foreach ( $order as $id ) {
		$list[] = array(
			'id' => $id,
			'on' => isset( $on[ $id ] ) ? (int) $on[ $id ] : 1,
		);
	}
	$opts['home_sections']  = $list;
	$opts['occ_style']      = 'card';
	$opts['sig_count']      = '5';
	$opts['card_cart_icon'] = 1;
	$opts['card_show_sku']  = 1;
	$opts['ed_align']       = 'center';
	$opts['__df_clean']     = 1;
	update_option( DF_OPTION, $opts );
	wp_safe_redirect( admin_url( 'admin.php?page=derin-flowers-tools&preset=1' ) );
	exit;
}
add_action( 'admin_post_df_preset', 'df_apply_preset' );

/**
 * Araçlar sayfası.
 */
function df_tools_page() {
	$log = get_transient( 'df_setup_log' );
	?>
	<div class="wrap df-panel df-tools">
		<div class="df-panel__top">
			<div class="df-panel__brand">
				<span class="df-panel__logo"><?php df_the_icon( 'bouquet', array( 'size' => 30 ) ); ?></span>
				<div><strong>Araçlar & Kurulum</strong><span>Derin Flowers</span></div>
			</div>
			<div class="df-panel__links"><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=derin-flowers' ) ); ?>">Tema Ayarları</a></div>
		</div>
		<?php // phpcs:disable WordPress.Security.NonceVerification.Recommended ?>
		<?php if ( isset( $_GET['setup'] ) && $log ) : ?>
			<div class="notice notice-success"><p><strong>Kurulum tamamlandı.</strong></p><ul style="list-style:disc;padding-left:20px"><?php foreach ( (array) $log as $l ) : ?><li><?php echo esc_html( $l ); ?></li><?php endforeach; ?></ul></div>
			<?php delete_transient( 'df_setup_log' ); ?>
		<?php endif; ?>
		<?php if ( isset( $_GET['import'] ) ) : ?>
			<div class="notice <?php echo '1' === $_GET['import'] ? 'notice-success' : 'notice-error'; ?>"><p><?php echo '1' === $_GET['import'] ? 'Ayarlar içe aktarıldı.' : 'Geçersiz JSON dosyası.'; ?></p></div>
		<?php endif; ?>
		<?php if ( isset( $_GET['preset'] ) ) : ?>
			<div class="notice notice-success"><p>Hazır ana sayfa düzeni uygulandı. <a href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank">Siteyi görüntüle</a></p></div>
		<?php endif; ?>
		<?php if ( isset( $_GET['reset'] ) ) : ?>
			<div class="notice notice-success"><p>Tüm tema ayarları varsayılana döndürüldü.</p></div>
		<?php endif; ?>
		<?php // phpcs:enable ?>

		<div class="df-tools__grid">
			<div class="df-group">
				<div class="df-group__head"><h3>Kurulum yardımcısı</h3></div>
				<div class="df-group__body">
					<p class="df-group__desc">Temayı ilk kez kuruyorsanız aşağıdaki adımları tek seferde uygulayabilirsiniz. Var olan kategori, sayfa ve menüler tekrar oluşturulmaz. <strong>Ürün oluşturulmaz</strong> — ürünlerinizi WooCommerce'ten ekleyin.</p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'df_setup' ); ?>
						<input type="hidden" name="action" value="df_setup">
						<label class="df-check"><input type="checkbox" name="df_do[]" value="cats" checked> Ürün kategorilerini ikonlarıyla oluştur (Buketler, Güller, Orkideler, Kutuda Çiçek, Vazoda Çiçek, Özel Günler, Söz & Nişan, Koleksiyonlar)</label>
						<label class="df-check"><input type="checkbox" name="df_do[]" value="pages" checked> Sayfaları oluştur ve ana sayfayı ayarla (Sipariş Takip, Favorilerim, Teslimat Bölgeleri, Hakkımızda, İletişim, Blog)</label>
						<label class="df-check"><input type="checkbox" name="df_do[]" value="menus" checked> Ana menü ve footer menülerini oluştur</label>
						<label class="df-check"><input type="checkbox" name="df_do[]" value="checkout" checked> Sepet/ödeme sayfalarını çiçekçi akışına çevir ve üyeliği aç</label>
						<p><button class="button button-primary button-hero">Kurulumu çalıştır</button></p>
					</form>
				</div>
			</div>

			<div class="df-group">
				<div class="df-group__head"><h3>Hazır ana sayfa düzeni</h3></div>
				<div class="df-group__body">
					<p class="df-group__desc">Gönderilen tasarımdaki kurguyu uygular: hero → kart tipi özel gün bannerları → yuvarlak popüler kategoriler → renkli kategori bannerları → kategoriler → 5'li Signature Collection (sepet ikonu + ürün kodu) → ortalı Söz & Nişan banner'ı → diğer bölümler. Yazılarınız, görselleriniz ve ürün seçimleriniz değişmez; kapattığınız bölümler kapalı kalır.</p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('Ana sayfa düzeni ve bölüm sırası değişecek. Devam edilsin mi?');">
						<?php wp_nonce_field( 'df_preset' ); ?>
						<input type="hidden" name="action" value="df_preset">
						<p><button class="button button-primary">Düzeni uygula</button></p>
					</form>
				</div>
			</div>

			<div class="df-group">
				<div class="df-group__head"><h3>Ayarları dışa / içe aktar</h3></div>
				<div class="df-group__body">
					<p><a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=df_export' ), 'df_export' ) ); ?>">Ayarları JSON olarak indir</a></p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'df_import' ); ?>
						<input type="hidden" name="action" value="df_import">
						<input type="hidden" name="df_mode" value="import">
						<textarea name="df_json" rows="6" class="large-text code" placeholder="Dışa aktarılan JSON içeriğini buraya yapıştırın"></textarea>
						<p><button class="button">İçe aktar</button></p>
					</form>
					<hr>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('Tüm tema ayarları varsayılana dönecek. Emin misiniz?');">
						<?php wp_nonce_field( 'df_import' ); ?>
						<input type="hidden" name="action" value="df_import">
						<input type="hidden" name="df_mode" value="reset">
						<button class="button button-link-delete">Tüm ayarları sıfırla</button>
					</form>
				</div>
			</div>

			<div class="df-group">
				<div class="df-group__head"><h3>Kısa kodlar</h3></div>
				<div class="df-group__body">
					<table class="widefat striped">
						<tbody>
							<tr><td><code>[derin_siparis_takip]</code></td><td>Sipariş takip formu ve durum zaman çizelgesi</td></tr>
							<tr><td><code>[derin_favoriler]</code></td><td>Favori ürünler listesi</td></tr>
							<tr><td><code>[derin_teslimat_bolgeleri]</code></td><td>Teslimat bölgeleri ve ücret tablosu</td></tr>
							<tr><td><code>[derin_iletisim]</code></td><td>İletişim bilgileri kartı</td></tr>
							<tr><td><code>[derin_sss]</code></td><td>Genel sıkça sorulan sorular</td></tr>
						</tbody>
					</table>
				</div>
			</div>

			<div class="df-group">
				<div class="df-group__head"><h3>Görsel rehberi</h3></div>
				<div class="df-group__body">
					<ul class="df-guide">
						<li><strong>Hero:</strong> 2400×1400 yatay, buket sağda, sol taraf sade (metin için). Mobil: 1080×1600 dikey.</li>
						<li><strong>Kategori kartları:</strong> 3:4 dikey editorial çekim — her kategori için farklı fotoğraf.</li>
						<li><strong>Özel gün bannerları:</strong> 16:10 yatay, güçlü ve birbirinden farklı.</li>
						<li><strong>Ürün görselleri:</strong> 4:5 (ör. 1200×1500), temiz stüdyo zemini, çiçek büyük ve net.</li>
						<li><strong>Editorial banner:</strong> 2400×1100 lifestyle fotoğraf, metin tarafı sade.</li>
						<li><strong>Instagram:</strong> 6 adet kare, birbirinden farklı görsel.</li>
					</ul>
				</div>
			</div>
		</div>
	</div>
	<?php
}
