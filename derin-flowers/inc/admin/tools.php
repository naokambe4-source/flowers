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
	$order = array( 'hero', 'occasions', 'popular', 'categories', 'signature', 'editorial', 'banners', 'bestsellers', 'trust', 'duo', 'delivery', 'story', 'blog', 'instagram', 'newsletter', 'social' );
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
	$opts['cats_style']        = 'banner';
	$opts['header_search_bar'] = 1;
	$opts['section_space']     = 64;
	$opts['pop_align']         = 'left';
	if ( ! empty( $opts['hero_slides'][0] ) && is_array( $opts['hero_slides'][0] ) && empty( $opts['hero_slides'][0]['script'] ) ) {
		$opts['hero_slides'][0]['script'] = "Çiçeklerle\ndaha güzel bir İzmir";
	}
	$opts['__df_clean']     = 1;
	update_option( DF_OPTION, $opts );
	wp_safe_redirect( admin_url( 'admin.php?page=derin-flowers-tools&preset=1' ) );
	exit;
}
add_action( 'admin_post_df_preset', 'df_apply_preset' );

/**
 * Hazır kampanya bannerlarını ekle (var olanlar korunur, aynı başlıklı olan tekrar eklenmez).
 */
function df_apply_campaign_banners() {
	if ( ! current_user_can( 'edit_theme_options' ) || ! check_admin_referer( 'df_campaign' ) ) {
		wp_die( 'Yetkiniz yok.' );
	}
	$opts  = array_merge( df_defaults(), (array) get_option( DF_OPTION, array() ) );
	$items = is_array( $opts['ban_items'] ) ? $opts['ban_items'] : array();
	$have  = array();
	foreach ( $items as $row ) {
		$have[] = isset( $row['title'] ) ? mb_strtoupper( preg_replace( '/\s+/', ' ', $row['title'] ) ) : '';
	}
	$added = 0;
	foreach ( df_campaign_banners() as $row ) {
		if ( ! in_array( mb_strtoupper( preg_replace( '/\s+/', ' ', $row['title'] ) ), $have, true ) ) {
			$items[] = $row;
			++$added;
		}
	}
	$opts['ban_items']  = $items;
	$opts['ban_cols']   = '2';
	$opts['__df_clean'] = 1;
	update_option( DF_OPTION, $opts );
	wp_safe_redirect( admin_url( 'admin.php?page=derin-flowers-tools&campaign=' . $added ) );
	exit;
}
add_action( 'admin_post_df_campaign', 'df_apply_campaign_banners' );

/**
 * Pastel adaçayı paleti (göz yormayan yumuşak yeşil).
 *
 * @return array
 */
function df_palette_sage() {
	return array(
		'color_bg'          => '#EEF3EC',
		'color_ivory'       => '#F7FAF5',
		'color_sand'        => '#E4ECE1',
		'color_text'        => '#2E3830',
		'color_muted'       => '#6A766B',
		'color_line'        => '#D6E0D2',
		'color_accent'      => '#6D8F70',
		'color_accent_dark' => '#557458',
		'color_rose'        => '#E6EEE3',
		'color_dark'        => '#4E6A52',
		'topbar_bg'         => '#8EA68B',
		'topbar_fg'         => '#FFFFFF',
		'header_bg'         => '#EEF3EC',
		'footer_bg'         => '#E4ECE1',
		'occ_bg'            => '#F7FAF5',
		'cats_bg'           => '#F7FAF5',
		'soc_bg'            => '#F7FAF5',
	);
}

/**
 * Vitrin düzeni: küçük hero + 3 yan banner, vitrin kategorisi ve araya banner,
 * Instagram & Blog, ilçeler, güven şeridi. İsteğe bağlı pastel adaçayı paleti.
 */
function df_apply_preset_vitrin() {
	if ( ! current_user_can( 'edit_theme_options' ) || ! check_admin_referer( 'df_preset_vitrin' ) ) {
		wp_die( 'Yetkiniz yok.' );
	}
	$opts  = array_merge( df_defaults(), (array) get_option( DF_OPTION, array() ) );
	$first = array( 'hero', 'vitrin', 'social', 'districts', 'trust' );
	$list  = array();
	foreach ( $first as $id ) {
		$list[] = array( 'id' => $id, 'on' => 1 );
	}
	foreach ( array_keys( df_home_section_labels() ) as $id ) {
		if ( ! in_array( $id, $first, true ) ) {
			$list[] = array( 'id' => $id, 'on' => 0 );
		}
	}
	$opts['home_sections']        = $list;
	$opts['hero_side_on']         = 1;
	$opts['header_search_bar']    = 1;
	$opts['header_search_ph']     = 'Ürün adı veya ürün kodu ile ara';
	$opts['header_inline_labels'] = 1;
	$opts['header_cat_btn']       = 0;
	$opts['header_show_search']   = 1;
	$opts['header_show_wishlist'] = 0;
	$opts['logo_icon']            = 1;
	$opts['nav_serif']            = 1;
	$opts['nav_icons']            = 0;
	$opts['lang_on']              = 1;
	$opts['soc_style']            = 'image';
	$opts['soc_ig_title']         = "Instagram'dan\nilham alın";
	$opts['soc_blog_title']       = 'Çiçek Rehberi & Blog';
	$opts['soc_blog_text']        = 'Çiçeklerin büyülü dünyası, bakım ipuçları ve daha fazlası…';
	$opts['section_space']        = 56;
	$opts['card_show_sku']        = 1;
	$opts['card_radius']          = 8;
	$opts['btn_radius']           = 4;
	if ( ! empty( $_POST['palette'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- yukarıda doğrulandı.
		$opts = array_merge( $opts, df_palette_sage() );
	}
	$opts['__df_clean'] = 1;
	update_option( DF_OPTION, $opts );
	wp_safe_redirect( admin_url( 'admin.php?page=derin-flowers-tools&preset=1' ) );
	exit;
}
add_action( 'admin_post_df_preset_vitrin', 'df_apply_preset_vitrin' );

/**
 * Krem & zarif paleti (fildişi zemin, koyu adaçayı yeşili düğmeler).
 *
 * @return array
 */
function df_palette_cream() {
	return array(
		'color_bg'          => '#FAF7F2',
		'color_ivory'       => '#F4EFE8',
		'color_sand'        => '#EFE8DE',
		'color_text'        => '#262321',
		'color_muted'       => '#6E665F',
		'color_line'        => '#E6DED3',
		'color_accent'      => '#3F5B45',
		'color_accent_dark' => '#2F4734',
		'color_rose'        => '#F3ECE4',
		'color_dark'        => '#3F5B45',
		'topbar_bg'         => '#F1ECE4',
		'topbar_fg'         => '#4A4440',
		'header_bg'         => '#FFFFFF',
		'footer_bg'         => '#FFFFFF',
		'soc_bg'            => '#F4EFE8',
	);
}

/**
 * Krem vitrin düzeni (gönderilen son tasarım): vitrin düzeni + krem palet, çerçeveli
 * "İncele" düğmeleri, 12 ürün arada banner yok, koyu Instagram bannerı, "TL" fiyat.
 */
function df_apply_preset_cream() {
	if ( ! current_user_can( 'edit_theme_options' ) || ! check_admin_referer( 'df_preset_cream' ) ) {
		wp_die( 'Yetkiniz yok.' );
	}
	$opts  = array_merge( df_defaults(), (array) get_option( DF_OPTION, array() ) );
	$first = array( 'hero', 'vitrin', 'social', 'trust' );
	$list  = array();
	foreach ( $first as $id ) {
		$list[] = array( 'id' => $id, 'on' => 1 );
	}
	foreach ( array_keys( df_home_section_labels() ) as $id ) {
		if ( ! in_array( $id, $first, true ) ) {
			$list[] = array( 'id' => $id, 'on' => 0 );
		}
	}
	$opts = array_merge(
		$opts,
		df_palette_cream(),
		array(
			'home_sections'        => $list,
			'hero_side_on'         => 1,
			'header_search_bar'    => 1,
			'header_search_ph'     => 'Ürün adı veya ürün kodu ile ara',
			'header_inline_labels' => 1,
			'header_cat_btn'       => 0,
			'header_show_search'   => 1,
			'header_show_wishlist' => 0,
			'logo_icon'            => 1,
			'nav_serif'            => 1,
			'nav_icons'            => 0,
			'lang_on'              => 1,
			'vit_count'            => '12',
			'vit_promos_on'        => 0,
			'card_view_style'      => 'outline',
			'card_show_sku'        => 1,
			'card_show_cat'        => 0,
			'card_radius'          => 6,
			'btn_radius'           => 4,
			'soc_style'            => 'image',
			'soc_ig_dark'          => 1,
			'soc_ig_title'         => "Instagram'dan\nilham alın",
			'soc_blog_title'       => 'Çiçek Rehberi & Blog',
			'soc_blog_text'        => 'Çiçeklerin büyülü dünyası, bakım ipuçları ve daha fazlası…',
			'section_space'        => 48,
			'price_tl'             => 1,
		)
	);
	if ( isset( $opts['hero_side'][1] ) && is_array( $opts['hero_side'][1] ) ) {
		$opts['hero_side'][1]['theme'] = 'dark';
	}
	$opts['__df_clean'] = 1;
	update_option( DF_OPTION, $opts );
	if ( ! empty( $_POST['price_format'] ) && df_wc() ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- yukarıda doğrulandı.
		update_option( 'woocommerce_price_num_decimals', 0 );
		update_option( 'woocommerce_price_thousand_sep', '.' );
		update_option( 'woocommerce_price_decimal_sep', ',' );
		update_option( 'woocommerce_currency_pos', 'right_space' );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=derin-flowers-tools&preset=1' ) );
	exit;
}
add_action( 'admin_post_df_preset_cream', 'df_apply_preset_cream' );

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
		<?php if ( isset( $_GET['campaign'] ) ) : ?>
			<div class="notice notice-success"><p><?php echo (int) $_GET['campaign']; ?> kampanya bannerı eklendi. Fotoğraflarını <a href="<?php echo esc_url( admin_url( 'admin.php?page=df-studio' ) ); ?>">Tasarım Stüdyosu</a>'nda "Renkli kategori bannerları" bölümünden ekleyin; fotoğrafı olmayan banner sitede görünmez.</p></div>
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
					<p class="df-group__desc">Gönderilen tasarımdaki kurguyu uygular: hero (el yazısı notlu) → pembe özel gün kartları → yuvarlak popüler kategoriler → yatay kategori bannerları → 5'li Signature Collection (sepet ikonu + ürün kodu) → ortalı Söz & Nişan banner'ı → renkli kampanya bannerları → diğer bölümler → Instagram · Blog · Sosyal şeridi. Header'a arama kutusu eklenir, bölüm boşlukları sıkılaştırılır. Yazılarınız, görselleriniz ve ürün seçimleriniz değişmez; kapattığınız bölümler kapalı kalır.</p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('Ana sayfa düzeni ve bölüm sırası değişecek. Devam edilsin mi?');">
						<?php wp_nonce_field( 'df_preset' ); ?>
						<input type="hidden" name="action" value="df_preset">
						<p><button class="button button-primary">Düzeni uygula</button></p>
					</form>
				</div>
			</div>

			<div class="df-group">
				<div class="df-group__head"><h3>Krem & zarif vitrin (en son tasarım)</h3></div>
				<div class="df-group__body">
					<p class="df-group__desc">Fildişi/krem zemin, koyu adaçayı yeşili düğmeler. Küçük hero + sağda 3 kategori bannerı (ortadaki koyu), 12 ürünlük Vitrin Koleksiyonu (ince çerçeveli "İncele" düğmeleri), koyu Instagram + açık Blog bannerı, güven şeridi, footer. Diğer bölümler kapatılır, silinmez. Vitrin kategorisini Ana Sayfa → Vitrin Koleksiyonu'ndan seçin.</p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('Ana sayfa düzeni ve renkler değişecek. Devam edilsin mi?');">
						<?php wp_nonce_field( 'df_preset_cream' ); ?>
						<input type="hidden" name="action" value="df_preset_cream">
						<label class="df-check"><input type="checkbox" name="price_format" value="1" checked> Fiyatları "2.490 TL" biçiminde göster (WooCommerce: kuruş yok, binlik nokta, TL sağda)</label>
						<p><button class="button button-primary">Krem vitrini uygula</button></p>
					</form>
				</div>
			</div>

			<div class="df-group">
				<div class="df-group__head"><h3>Vitrin düzeni (son tasarım)</h3></div>
				<div class="df-group__body">
					<p class="df-group__desc">Üst bantta dil seçici; solda "ürün adı veya kodu" arama kutusu, ortada ikonlu logo, sağda Hesabım / Sepetim. Küçük hero ve sağında 3 kategori bannerı → Vitrin Koleksiyonu (seçtiğiniz kategori, 4'lü satırlar, araya 3 banner, "İncele" düğmeleri) → Instagram & Blog görselli bannerlar → ilçe kısayolları → güven şeridi → footer. Diğer bölümler silinmez, kapatılır; istediğinizi stüdyodan tekrar açabilirsiniz. <strong>Vitrin kategorisini</strong> Ana Sayfa → Vitrin Koleksiyonu'ndan seçin.</p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('Ana sayfa düzeni değişecek. Devam edilsin mi?');">
						<?php wp_nonce_field( 'df_preset_vitrin' ); ?>
						<input type="hidden" name="action" value="df_preset_vitrin">
						<label class="df-check"><input type="checkbox" name="palette" value="1" checked> Göz yormayan pastel adaçayı yeşili renkleri de uygula</label>
						<p><button class="button button-primary">Vitrin düzenini uygula</button></p>
					</form>
				</div>
			</div>

			<div class="df-group">
				<div class="df-group__head"><h3>Hazır kampanya bannerları</h3></div>
				<div class="df-group__body">
					<p class="df-group__desc">10 renkli banner ekler: Sevgiliye, Söz/Nişan/Düğün, Açılış, Ev Hediyesi, Özür, Geçmiş Olsun, Anneler Günü, Mevsim, Saksı, İndirimli. Renkler, ikonlar, başlıklar ve "Aynı Gün Teslimat" düğmesi hazır gelir; sadece fotoğraflarını eklersiniz. Mevcut bannerlarınız silinmez, aynı başlıklı olan tekrar eklenmez. Fotoğrafı eklenmemiş banner sitede görünmez.</p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'df_campaign' ); ?>
						<input type="hidden" name="action" value="df_campaign">
						<p><button class="button button-primary">Bannerları ekle</button></p>
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
