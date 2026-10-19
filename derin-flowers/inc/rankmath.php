<?php
/**
 * Rank Math kurulunca: temadaki SEO bilgilerini ve işletme ayarlarını tek tıkla Rank Math'e aktarır.
 * - Ana sayfa başlığı / açıklaması, ürün ve kategori başlık şablonları
 * - Yerel işletme (Florist): ad, logo, telefon, e-posta, adres
 * - Sayfa ve yazıların tema SEO alanları (boş olan Rank Math alanlarına)
 * - Site haritasında yazar listesi kapalı, sepet / ödeme / hesap sayfaları hariç
 * Var olan Rank Math sayfa/ürün alanlarına dokunmaz; genel ayarlarda yalnızca aşağıdaki anahtarları yazar.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Rank Math kurulu mu?
 *
 * @return bool
 */
function df_rm_active() {
	return defined( 'RANK_MATH_VERSION' );
}

/**
 * Aktarımı uygula.
 *
 * @return array<string,int>
 */
function df_rm_apply() {
	$city  = df_opt( 'df_default_city', 'İzmir' );
	$site  = get_bloginfo( 'name' );
	$stats = array( 'settings' => 0, 'pages' => 0 );

	// 1. Başlıklar ve yerel işletme.
	$t     = (array) get_option( 'rank-math-options-titles', array() );
	$logo  = df_img_url( df_opt( 'logo_image' ), 'full' );
	$phone = preg_replace( '/[^\d+]/', '', (string) df_opt( 'contact_phone1', '' ) );
	if ( preg_match( '/^0(\d{10})$/', $phone, $m ) ) {
		$phone = '+90' . $m[1];
	}
	$lines = array_values( array_filter( array_map( 'trim', explode( "\n", (string) df_opt( 'contact_address', '' ) ) ) ) );
	$set   = array(
		'title_separator'         => '–',
		'homepage_title'          => (string) df_opt( 'seo_home_title', $site . ' | ' . $city . ' Çiçek Siparişi – Aynı Gün Teslimat' ),
		'homepage_description'    => (string) df_opt( 'seo_home_desc', '' ),
		'pt_product_title'        => '%title% | ' . $city . ' Çiçek Siparişi %sep% %sitename%',
		'pt_product_description'  => '%excerpt%',
		'tax_product_cat_title'   => '%term% | ' . $city . ' Çiçek Siparişi %sep% %sitename%',
		'pt_page_title'           => '%title% %sep% %sitename%',
		'pt_post_title'           => '%title% %sep% ' . $site . ' Blog',
		'pt_post_description'     => '%excerpt%',
		'pt_post_default_rich_snippet'   => 'article',
		'pt_post_default_article_type'   => 'BlogPosting',
		'pt_product_default_rich_snippet' => 'product',
		'pt_page_default_rich_snippet'    => 'off',
		'tax_category_title'      => '%term% %sep% Çiçek Rehberi %sep% %sitename%',
		'tax_category_description' => '%term_description%',
		'disable_author_archives' => 'on',
		'knowledgegraph_type'     => 'company',
		'knowledgegraph_name'     => $site,
		'website_name'            => $site,
		'local_business_type'     => 'Florist',
		'email'                   => (string) df_opt( 'contact_email', '' ),
		'phone'                   => $phone,
		'url'                     => home_url( '/' ),
	);
	if ( $logo ) {
		$set['knowledgegraph_logo'] = $logo;
	}
	if ( $phone ) {
		$set['phone_numbers'] = array( array( 'type' => 'customer support', 'number' => $phone ) );
	}
	if ( $lines ) {
		$set['local_address'] = array(
			'streetAddress'   => implode( ', ', array_slice( $lines, 0, max( 1, count( $lines ) - 1 ) ) ),
			'addressLocality' => $city,
			'addressRegion'   => $city,
			'addressCountry'  => 'TR',
		);
	}
	$og = df_img_url( df_opt( 'seo_og_image' ), 'full' );
	if ( $og ) {
		$set['open_graph_image'] = $og;
	}
	foreach ( $set as $k => $v ) {
		if ( '' === $v || array() === $v ) {
			continue;
		}
		$t[ $k ] = $v;
		++$stats['settings'];
	}
	update_option( 'rank-math-options-titles', $t );
	// Yerel SEO modülü (işletme "Çiçekçi" olarak görünsün).
	$mods = (array) get_option( 'rank_math_modules', array() );
	if ( ! in_array( 'local-seo', $mods, true ) ) {
		$mods[] = 'local-seo';
		update_option( 'rank_math_modules', array_values( $mods ) );
	}

	// 2. Site haritası.
	$s       = (array) get_option( 'rank-math-options-sitemap', array() );
	$exclude = function_exists( 'df_site_seo_private_pages' ) ? df_site_seo_private_pages() : array();
	$old     = array_filter( array_map( 'absint', explode( ',', (string) ( $s['exclude_posts'] ?? '' ) ) ) );
	$s['authors_sitemap']         = 'off';
	$s['pt_product_sitemap']      = 'on';
	$s['pt_page_sitemap']         = 'on';
	$s['tax_product_cat_sitemap'] = 'on';
	$s['tax_product_tag_sitemap'] = 'off';
	$s['exclude_posts']   = implode( ',', array_unique( array_merge( $old, $exclude ) ) );
	$uncat = (int) get_option( 'default_product_cat' );
	if ( $uncat ) {
		$terms_ex           = array_filter( array_map( 'absint', explode( ',', (string) ( $s['exclude_terms'] ?? '' ) ) ) );
		$s['exclude_terms'] = implode( ',', array_unique( array_merge( $terms_ex, array( $uncat ) ) ) );
	}
	update_option( 'rank-math-options-sitemap', $s );
	if ( class_exists( '\RankMath\Sitemap\Cache' ) && method_exists( '\RankMath\Sitemap\Cache', 'invalidate_storage' ) ) {
		\RankMath\Sitemap\Cache::invalidate_storage();
	}

	// 3. Sayfa ve yazıların tema SEO alanları → boş Rank Math alanlarına.
	$defaults = function_exists( 'df_seo_page_defaults' ) ? df_seo_page_defaults() : array();
	$ids      = get_posts( array( 'post_type' => array( 'page', 'post' ), 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids' ) );
	foreach ( $ids as $id ) {
		$slug  = get_post_field( 'post_name', $id );
		$title = (string) get_post_meta( $id, '_df_seo_title', true );
		$desc  = (string) get_post_meta( $id, '_df_seo_desc', true );
		if ( isset( $defaults[ $slug ] ) ) {
			$title = $title ? $title : $defaults[ $slug ][0];
			$desc  = $desc ? $desc : $defaults[ $slug ][1];
		}
		$done = false;
		if ( $title && '' === (string) get_post_meta( $id, 'rank_math_title', true ) ) {
			update_post_meta( $id, 'rank_math_title', $title );
			$done = true;
		}
		if ( $desc && '' === (string) get_post_meta( $id, 'rank_math_description', true ) ) {
			update_post_meta( $id, 'rank_math_description', $desc );
			$done = true;
		}
		$stats['pages'] += $done ? 1 : 0;
	}
	// Mağaza sayfası.
	if ( function_exists( 'wc_get_page_id' ) && wc_get_page_id( 'shop' ) > 0 ) {
		$shop = wc_get_page_id( 'shop' );
		if ( '' === (string) get_post_meta( $shop, 'rank_math_title', true ) ) {
			update_post_meta( $shop, 'rank_math_title', 'Tüm Çiçekler | ' . $city . ' Çiçek Siparişi – ' . $site );
			update_post_meta( $shop, 'rank_math_description', $city . '’e aynı gün çiçek gönderin: buketler, güller, orkideler, kutuda ve vazoda çiçekler. Taze çiçekler, özenli paketleme ve ücretsiz not kartı.' );
		}
	}
	// Ana sayfa sabit bir sayfaysa Rank Math başlığı o sayfadan alır.
	$front = (int) get_option( 'page_on_front' );
	if ( 'page' === get_option( 'show_on_front' ) && $front ) {
		update_post_meta( $front, 'rank_math_rich_snippet', 'off' );
		if ( '' === (string) get_post_meta( $front, 'rank_math_title', true ) && ! empty( $set['homepage_title'] ) ) {
			update_post_meta( $front, 'rank_math_title', $set['homepage_title'] );
		}
		if ( '' === (string) get_post_meta( $front, 'rank_math_description', true ) && ! empty( $set['homepage_description'] ) ) {
			update_post_meta( $front, 'rank_math_description', $set['homepage_description'] );
		}
	}
	// Kategoriler: önce boş SEO alanlarını doldur, sonra tema alanlarını boş Rank Math alanlarına kopyala.
	if ( function_exists( 'df_cat_seo_fill' ) ) {
		df_cat_seo_fill();
	}
	$terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
	foreach ( is_wp_error( $terms ) ? array() : $terms as $term ) {
		foreach ( array( 'df_seo_title' => 'rank_math_title', 'df_seo_desc' => 'rank_math_description' ) as $from => $to ) {
			$v = trim( (string) get_term_meta( $term->term_id, $from, true ) );
			if ( '' !== $v && '' === (string) get_term_meta( $term->term_id, $to, true ) ) {
				update_term_meta( $term->term_id, $to, $v );
			}
		}
		$kw = trim( (string) strtok( (string) get_term_meta( $term->term_id, 'df_seo_kw', true ), ',' ) );
		if ( '' !== $kw && '' === (string) get_term_meta( $term->term_id, 'rank_math_focus_keyword', true ) ) {
			update_term_meta( $term->term_id, 'rank_math_focus_keyword', $kw );
		}
	}
	update_option( 'df_rm_applied', DF_VERSION, false );
	if ( function_exists( 'df_pc_flush' ) ) {
		df_pc_flush();
	}
	return $stats;
}

/**
 * Düğme işleyici.
 */
function df_rm_action() {
	if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'df_rm' ) ) {
		wp_die( 'Yetkiniz yok.' );
	}
	$r = df_rm_apply();
	set_transient( 'df_rm_done', $r, 300 );
	wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url() );
	exit;
}
add_action( 'admin_post_df_rm', 'df_rm_action' );

/**
 * Rank Math kurulumu tamamlanınca bir kez kendiliğinden uygula.
 */
function df_rm_auto() {
	if ( ! function_exists( 'df_rank_math_live' ) || ! df_rank_math_live() || get_option( 'df_rm_applied' ) || ! current_user_can( 'manage_options' ) || wp_doing_ajax() ) {
		return;
	}
	set_transient( 'df_rm_done', df_rm_apply(), 300 );
}
add_action( 'admin_init', 'df_rm_auto', 30 );

/**
 * Rank Math kuruluysa bildirim ve düğme.
 */
function df_rm_notice() {
	if ( ! df_rm_active() || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( function_exists( 'df_rank_math_live' ) && ! df_rank_math_live() ) {
		echo '<div class="notice notice-warning"><p><strong>Rank Math henüz çalışmıyor:</strong> Rank Math → Kurulum Sihirbazı’nı açıp hesap bağlama adımında <em>Skip / Atla</em> deyin ve sihirbazı bitirin. O zamana kadar tema kendi SEO’sunu kullanmaya devam ediyor; sihirbaz bitince Derin Flowers SEO ayarları Rank Math’e kendiliğinden aktarılır.</p></div>';
		return;
	}
	$done = get_transient( 'df_rm_done' );
	if ( $done ) {
		delete_transient( 'df_rm_done' );
		echo '<div class="notice notice-success is-dismissible"><p><strong>Rank Math ayarlandı.</strong> ' . (int) $done['settings'] . ' genel ayar yazıldı, ' . (int) $done['pages'] . ' sayfanın SEO bilgisi aktarıldı. Kategoriler ve ürünler zaten Rank Math alanlarıyla eşleşiyor.</p></div>';
		return;
	}
	if ( get_option( 'df_rm_applied' ) ) {
		return;
	}
	$url = wp_nonce_url( admin_url( 'admin-post.php?action=df_rm' ), 'df_rm' );
	echo '<div class="notice notice-info"><p><strong>Derin Flowers + Rank Math:</strong> Ana sayfa, ürün ve kategori başlıkları, yerel işletme (Çiçekçi) bilgileri, site haritası ve sayfaların SEO metinleri tek tıkla Rank Math\'e aktarılsın mı? <a class="button button-primary" style="margin-left:8px" href="' . esc_url( $url ) . '">Rank Math\'i Derin Flowers\'a göre ayarla</a></p></div>';
}
add_action( 'admin_notices', 'df_rm_notice' );
