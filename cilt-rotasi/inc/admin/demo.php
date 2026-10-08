<?php
/**
 * Kurulum sihirbazı: taksonomiler, sayfalar, menüler, kalıcı bağlantılar ve örnek içerikler.
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Terim oluştur (varsa atla) ve meta yaz.
 *
 * @param string $tax  Taksonomi.
 * @param string $name Ad.
 * @param string $slug Slug.
 * @param array  $meta desc, image, hero.
 * @return int
 */
function cr_demo_term( $tax, $name, $slug, $meta = array() ) {
	$t = get_term_by( 'slug', $slug, $tax );
	if ( ! $t ) {
		$r = wp_insert_term( $name, $tax, array( 'slug' => $slug, 'description' => isset( $meta['desc'] ) ? $meta['desc'] : '' ) );
		if ( is_wp_error( $r ) ) {
			return 0;
		}
		$id = (int) $r['term_id'];
	} else {
		$id = (int) $t->term_id;
		if ( ! $t->description && ! empty( $meta['desc'] ) ) {
			wp_update_term( $id, $tax, array( 'description' => $meta['desc'] ) );
		}
	}
	if ( ! empty( $meta['image'] ) && ! get_term_meta( $id, 'cr_image', true ) ) {
		update_term_meta( $id, 'cr_image', $meta['image'] );
	}
	if ( ! empty( $meta['hero'] ) && ! get_term_meta( $id, 'cr_hero_title', true ) ) {
		update_term_meta( $id, 'cr_hero_title', $meta['hero'] );
	}
	return $id;
}

/**
 * Sayfa oluştur (varsa atla).
 *
 * @param string $title    Başlık.
 * @param string $slug     Slug.
 * @param string $content  İçerik.
 * @param string $template Şablon.
 * @return int
 */
function cr_demo_page( $title, $slug, $content = '', $template = '' ) {
	$p = get_page_by_path( $slug );
	if ( $p ) {
		return (int) $p->ID;
	}
	$id = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_content' => $content,
		)
	);
	if ( $id && $template ) {
		update_post_meta( $id, '_wp_page_template', $template );
	}
	return (int) $id;
}

/**
 * Görsel ata (medya kütüphanesine indir ya da harici adres).
 *
 * @param int    $post_id  Yazı.
 * @param string $url      Adres.
 * @param bool   $sideload İndir.
 */
function cr_demo_image( $post_id, $url, $sideload ) {
	if ( ! $url || has_post_thumbnail( $post_id ) ) {
		return;
	}
	if ( $sideload ) {
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$att = media_sideload_image( $url, $post_id, get_the_title( $post_id ), 'id' );
		if ( ! is_wp_error( $att ) ) {
			set_post_thumbnail( $post_id, $att );
			return;
		}
	}
	update_post_meta( $post_id, '_cr_ext_image', esc_url_raw( $url ) );
}

/**
 * İçerik oluştur (aynı başlık varsa atla).
 *
 * @param array $d Veri.
 * @param bool  $sideload Görsel indir.
 * @return int
 */
function cr_demo_post( $d, $sideload ) {
	$type = isset( $d['type'] ) ? $d['type'] : 'post';
	$slug = isset( $d['slug'] ) ? $d['slug'] : sanitize_title( $d['title'] );
	if ( get_page_by_path( $slug, OBJECT, $type ) ) {
		return 0;
	}
	$id = wp_insert_post(
		array(
			'post_type'    => $type,
			'post_status'  => 'publish',
			'post_title'   => $d['title'],
			'post_name'    => $slug,
			'post_excerpt' => isset( $d['excerpt'] ) ? $d['excerpt'] : '',
			'post_content' => isset( $d['content'] ) ? $d['content'] : '',
			'post_author'  => get_current_user_id(),
		)
	);
	if ( ! $id || is_wp_error( $id ) ) {
		return 0;
	}
	foreach ( isset( $d['terms'] ) ? $d['terms'] : array() as $tax => $slugs ) {
		$ids = array();
		foreach ( (array) $slugs as $s ) {
			$t = get_term_by( 'slug', $s, $tax );
			if ( $t ) {
				$ids[] = (int) $t->term_id;
			}
		}
		if ( $ids ) {
			wp_set_object_terms( $id, $ids, $tax );
		}
	}
	foreach ( isset( $d['meta'] ) ? $d['meta'] : array() as $k => $v ) {
		update_post_meta( $id, $k, $v );
	}
	update_post_meta( $id, '_cr_reviewed_date', current_time( 'Y-m-d' ) );
	if ( ! empty( $d['views'] ) ) {
		update_post_meta( $id, '_cr_views', (int) $d['views'] );
	}
	cr_demo_image( $id, isset( $d['image'] ) ? $d['image'] : '', $sideload );
	return $id;
}

/**
 * Kurulumu çalıştır.
 *
 * @param bool $samples  Örnek içerik.
 * @param bool $sideload Görselleri indir.
 * @return array Özet.
 */
function cr_run_setup( $samples = true, $sideload = false ) {
	@set_time_limit( 300 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	$done = array();
	cr_register_types();

	/* Kalıcı bağlantılar */
	if ( ! get_option( 'permalink_structure' ) ) {
		update_option( 'permalink_structure', '/%postname%/' );
	}
	if ( ! get_option( 'category_base' ) ) {
		update_option( 'category_base', 'kategori' );
	}
	$desc = get_option( 'blogdescription' );
	if ( ! $desc || false !== stripos( $desc, 'WordPress' ) ) {
		update_option( 'blogdescription', 'Cilt bakımı için bağımsız bilgi platformu' );
	}

	/* Kategoriler */
	$cats = array(
		'cilt-yapisi'       => array( 'Cilt Yapısı', 'Cildin katmanları, bariyer, sebum dengesi ve mikrobiyom hakkında temel bilgiler.', 'structure', 'Cildin nasıl çalıştığını keşfet.' ),
		'cilt-problemleri'  => array( 'Cilt Problemleri', 'Akne, leke, kızarıklık, hassasiyet ve kuruluk: nedenleri ve bakım yaklaşımları.', 'problems', 'Cildin verdiği sinyalleri anlamaya başla.' ),
		'cilt-bakim-rutini' => array( 'Cilt Bakım Rutini', 'Sabah ve akşam rutinleri, doğru sıralama ve cilt tipine göre sade protokoller.', 'routine', 'İhtiyacına uygun bakım adımlarını öğren.' ),
		'icerikler'         => array( 'İçerikler', 'Aktif içeriklerin nasıl çalıştığı, kimlere uygun olduğu ve nasıl kombinleneceği.', 'actives', 'Etiketteki molekülleri tanı.' ),
		'urunler'           => array( 'Ürünler', 'Ürün kategorilerini içerik ve kullanım amacına göre inceleyen bağımsız rehberler.', 'derm', 'Ürünü değil, ihtiyacı seç.' ),
	);
	foreach ( $cats as $slug => $c ) {
		cr_demo_term( 'category', $c[0], $slug, array( 'desc' => $c[1], 'image' => cr_default_img( $c[2] ), 'hero' => $c[3] ) );
	}
	$done[] = 'kategoriler';

	/* Cilt sorunları */
	$probs = array(
		'akne'          => array( 'Akne', 'Sebum, gözenek tıkanıklığı, bakteri ve iltihabın birlikte rol oynadığı yaygın bir cilt durumu.', 'acne' ),
		'siyah-nokta'   => array( 'Siyah Nokta', 'Açık komedonlar: gözenekteki sebum ve ölü hücrelerin havayla temas edip koyulaşması.', 'blog2' ),
		'leke'          => array( 'Leke', 'Güneş, hormonlar veya iltihap sonrası oluşan ton eşitsizliği ve hiperpigmentasyon.', 'spot' ),
		'kizariklik'    => array( 'Kızarıklık', 'Hassasiyet, rozasea veya tahrişle ortaya çıkan kızarıklık ve yanma hissi.', 'sensitive' ),
		'hassasiyet'    => array( 'Hassasiyet', 'Ürünlere ve çevresel etkenlere hızla tepki veren, bariyeri zayıflamış cilt.', 'barrier' ),
		'kuruluk'       => array( 'Kuruluk', 'Lipid ve nem eksikliğiyle gelen gerginlik, pullanma ve pürüzlülük.', 'dry' ),
		'ince-cizgiler' => array( 'İnce Çizgiler', 'Kolajen kaybı, güneş hasarı ve nem kaybıyla belirginleşen yaşlanma belirtileri.', 'aging' ),
	);
	foreach ( $probs as $slug => $p ) {
		cr_demo_term( 'cilt_sorunu', $p[0], $slug, array( 'desc' => $p[1], 'image' => cr_default_img( $p[2] ) ) );
	}
	foreach ( array( 'kuru' => 'Kuru', 'yagli' => 'Yağlı', 'karma' => 'Karma', 'normal' => 'Normal', 'hassas' => 'Hassas' ) as $slug => $n ) {
		cr_demo_term( 'cilt_tipi', $n, $slug );
	}
	foreach ( array( 'nem-tutucular' => 'Nem tutucular', 'bariyer-lipidleri' => 'Bariyer lipidleri', 'eksfolyanlar' => 'Eksfolyanlar (asitler)', 'retinoidler' => 'Retinoidler', 'antioksidanlar' => 'Antioksidanlar', 'yatistiricilar' => 'Yatıştırıcılar', 'vitaminler' => 'Vitaminler' ) as $slug => $n ) {
		cr_demo_term( 'icerik_grubu', $n, $slug );
	}
	$ptypes = array(
		'temizleyiciler'    => array( 'Temizleyiciler', 'Bariyeri bozmadan kir, makyaj ve fazla sebumu uzaklaştıran ürünler.', 'cleanser' ),
		'serumlar'          => array( 'Serumlar', 'Belirli bir amaca yönelik, aktif içerik yoğunluğu yüksek hafif formüller.', 'serum' ),
		'nemlendiriciler'   => array( 'Nemlendiriciler', 'Nem tutucular, yumuşatıcılar ve kapatıcılarla bariyeri destekleyen ürünler.', 'cream' ),
		'gunes-koruyucular' => array( 'Güneş Koruyucular', 'UVA ve UVB ışınlarına karşı geniş spektrumlu koruma sağlayan ürünler.', 'spf' ),
		'tonikler'          => array( 'Tonikler', 'Temizlik sonrası cildi hazırlayan, nemlendiren ya da nazikçe eksfoliye eden ürünler.', 'blog5' ),
	);
	foreach ( $ptypes as $slug => $p ) {
		cr_demo_term( 'urun_turu', $p[0], $slug, array( 'desc' => $p[1], 'image' => cr_default_img( $p[2] ) ) );
	}
	$done[] = 'taksonomiler';

	/* Sayfalar */
	$home    = cr_demo_page( 'Ana Sayfa', 'ana-sayfa' );
	$blog    = cr_demo_page( 'Rehberler', 'rehberler' );
	$quiz    = cr_demo_page( 'Cilt Testi', 'cilt-testi', '', 'page-templates/template-cilt-testi.php' );
	$saved   = cr_demo_page( 'Kaydedilenler', 'kaydedilenler', '', 'page-templates/template-kaydedilenler.php' );
	$pages   = cr_demo_legal_pages();
	$page_id = array();
	foreach ( $pages as $slug => $p ) {
		$page_id[ $slug ] = cr_demo_page( $p[0], $slug, $p[1] );
		if ( ! empty( $p[2] ) && ! get_post_meta( $page_id[ $slug ], '_cr_short_answer', true ) ) {
			update_post_meta( $page_id[ $slug ], '_cr_short_answer', $p[2] );
		}
	}
	update_post_meta( $quiz, '_cr_seo_title', 'Cilt Testi: Cilt Tipini 2 Dakikada Öğren — Cilt Rotası' );
	update_post_meta( $quiz, '_cr_seo_desc', 'Altı kısa soruyla cilt tipini ve cildinin bugünkü ihtiyacını öğren; sana uygun bakım rotasını ücretsiz, kayıt olmadan keşfet.' );
	update_post_meta( $saved, '_cr_noindex', '1' );
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $home );
	update_option( 'page_for_posts', $blog );
	$done[] = 'sayfalar';

	/* Menüler */
	cr_demo_menus( $page_id );
	$done[] = 'menüler';

	/* Yazar profili */
	$uid = get_current_user_id();
	if ( ! get_the_author_meta( 'description', $uid ) ) {
		wp_update_user( array( 'ID' => $uid, 'description' => 'Cilt Rotası editörü. Kozmetik içerikleri ve cilt bakımını hakemli çalışmalar ile dermatoloji kılavuzları ışığında, sade bir dille anlatır.' ) );
	}
	if ( ! get_user_meta( $uid, 'cr_job', true ) ) {
		update_user_meta( $uid, 'cr_job', 'Editör' );
	}

	if ( $samples ) {
		$n = 0;
		foreach ( cr_demo_posts() as $d ) {
			$n += cr_demo_post( $d, $sideload ) ? 1 : 0;
		}
		foreach ( cr_demo_ingredients() as $d ) {
			$n += cr_demo_post( $d, $sideload ) ? 1 : 0;
		}
		foreach ( cr_demo_products() as $d ) {
			$n += cr_demo_post( $d, $sideload ) ? 1 : 0;
		}
		$done[] = $n . ' örnek içerik';
	}

	flush_rewrite_rules();
	cr_geo_flush();
	return $done;
}

/**
 * Menüleri kur.
 *
 * @param array $page_id Sayfa kimlikleri.
 */
function cr_demo_menus( $page_id ) {
	$locations = get_theme_mod( 'nav_menu_locations', array() );
	$make      = function ( $name, $items ) {
		$menu = wp_get_nav_menu_object( $name );
		if ( $menu ) {
			return (int) $menu->term_id;
		}
		$mid = wp_create_nav_menu( $name );
		if ( is_wp_error( $mid ) ) {
			return 0;
		}
		foreach ( $items as $pos => $it ) {
			$args = array(
				'menu-item-title'    => $it['title'],
				'menu-item-status'   => 'publish',
				'menu-item-position' => $pos + 1,
			);
			if ( isset( $it['cat'] ) ) {
				$t = get_category_by_slug( $it['cat'] );
				if ( ! $t ) {
					continue;
				}
				$args['menu-item-type']      = 'taxonomy';
				$args['menu-item-object']    = 'category';
				$args['menu-item-object-id'] = $t->term_id;
			} elseif ( isset( $it['page'] ) ) {
				if ( ! $it['page'] ) {
					continue;
				}
				$args['menu-item-type']      = 'post_type';
				$args['menu-item-object']    = 'page';
				$args['menu-item-object-id'] = $it['page'];
			} else {
				$args['menu-item-type'] = 'custom';
				$args['menu-item-url']  = $it['url'];
			}
			wp_update_nav_menu_item( $mid, 0, $args );
		}
		return (int) $mid;
	};
	if ( empty( $locations['primary'] ) ) {
		$locations['primary'] = $make(
			'Cilt Rotası — Ana menü',
			array(
				array( 'title' => 'Ana Sayfa', 'url' => home_url( '/' ) ),
				array( 'title' => 'Cilt Yapısı', 'cat' => 'cilt-yapisi' ),
				array( 'title' => 'Cilt Problemleri', 'cat' => 'cilt-problemleri' ),
				array( 'title' => 'Cilt Bakım Rutini', 'cat' => 'cilt-bakim-rutini' ),
				array( 'title' => 'Sözlük', 'url' => home_url( '/icerik/' ) ),
				array( 'title' => 'Blog', 'url' => cr_blog_url() ),
			)
		);
	}
	if ( empty( $locations['footer_1'] ) ) {
		$locations['footer_1'] = $make(
			'Cilt Rotası — Footer Kütüphane',
			array(
				array( 'title' => 'Cilt Yapısı', 'cat' => 'cilt-yapisi' ),
				array( 'title' => 'Cilt Problemleri', 'cat' => 'cilt-problemleri' ),
				array( 'title' => 'Cilt Bakım Rutini', 'cat' => 'cilt-bakim-rutini' ),
				array( 'title' => 'Sözlük', 'url' => home_url( '/icerik/' ) ),
				array( 'title' => 'Blog', 'url' => cr_blog_url() ),
			)
		);
	}
	if ( empty( $locations['footer_2'] ) ) {
		$locations['footer_2'] = $make(
			'Cilt Rotası — Footer Kurumsal',
			array(
				array( 'title' => 'Hakkımızda', 'page' => isset( $page_id['hakkimizda'] ) ? $page_id['hakkimizda'] : 0 ),
				array( 'title' => 'İletişim', 'page' => isset( $page_id['iletisim'] ) ? $page_id['iletisim'] : 0 ),
			)
		);
	}
	if ( empty( $locations['footer_3'] ) ) {
		$locations['footer_3'] = $make(
			'Cilt Rotası — Footer Yasal',
			array(
				array( 'title' => 'Gizlilik', 'page' => isset( $page_id['gizlilik'] ) ? $page_id['gizlilik'] : 0 ),
				array( 'title' => 'Çerez Politikası', 'page' => isset( $page_id['cerez-politikasi'] ) ? $page_id['cerez-politikasi'] : 0 ),
				array( 'title' => 'Kullanım Koşulları', 'page' => isset( $page_id['kullanim-kosullari'] ) ? $page_id['kullanim-kosullari'] : 0 ),
			)
		);
	}
	set_theme_mod( 'nav_menu_locations', $locations );
}

/**
 * Kurumsal ve yasal sayfalar.
 *
 * @return array slug => [başlık, içerik, kısa cevap]
 */
function cr_demo_legal_pages() {
	$note = '<!-- wp:group {"className":"is-style-cr-cream"} --><div class="wp-block-group is-style-cr-cream"><!-- wp:paragraph --><p><strong>Not:</strong> Bu metin bir şablondur. Yayınlamadan önce kendi süreçlerinize ve KVKK/GDPR yükümlülüklerinize göre bir hukukçuyla gözden geçirin.</p><!-- /wp:paragraph --></div><!-- /wp:group -->';
	return array(
		'hakkimizda'         => array(
			'Hakkımızda',
			'<!-- wp:paragraph --><p>Cilt Rotası, cilt bakımındaki bilgi karmaşasını azaltmak için kurulan bağımsız bir Türkçe bilgi platformudur. Amacımız; cilt yapısını, cilt problemlerini, bakım rutinlerini ve kozmetik içerikleri sade, doğru ve uygulanabilir bir dille anlatmak.</p><!-- /wp:paragraph --><!-- wp:heading --><h2>Neden varız?</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Raflarda binlerce ürün, sosyal medyada sayısız öneri var. Biz ürünü değil ihtiyacı merkeze alıyor; her önerinin arkasındaki bilimsel gerekçeyi açıkça paylaşıyoruz.</p><!-- /wp:paragraph --><!-- wp:heading --><h2>Nasıl çalışıyoruz?</h2><!-- /wp:heading --><!-- wp:list {"className":"is-style-cr-check"} --><ul class="is-style-cr-check"><!-- wp:list-item --><li>Her rehber hakemli çalışmalara ve dermatoloji kılavuzlarına dayanır.</li><!-- /wp:list-item --><!-- wp:list-item --><li>Sağlıkla ilgili içerikler uzman kontrolünden geçer.</li><!-- /wp:list-item --><!-- wp:list-item --><li>Sponsorlu sıralama yapmayız; marka iş birlikleri açıkça belirtilir.</li><!-- /wp:list-item --></ul><!-- /wp:list -->',
			'Cilt Rotası; cilt yapısı, cilt problemleri, bakım rutinleri ve kozmetik içerikler hakkında kanıta dayalı, bağımsız ve sade Türkçe rehberler yayınlayan bir bilgi platformudur.',
		),
		'yayin-ilkeleri'     => array(
			'Yayın İlkeleri',
			'<!-- wp:paragraph --><p>Okurlarımızın sağlığı ve güveni her şeyden önce gelir. Bu sayfa, içeriklerimizi nasıl hazırladığımızı, kontrol ettiğimizi ve güncellediğimizi açıklar.</p><!-- /wp:paragraph --><!-- wp:heading --><h2>Kaynak kullanımı</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Rehberlerimizde hakemli dergilerde yayımlanmış çalışmalara, dermatoloji derneklerinin kılavuzlarına ve resmî sağlık kurumlarının yayınlarına dayanırız. Kaynaklar her yazının sonunda listelenir.</p><!-- /wp:paragraph --><!-- wp:heading --><h2>Uzman kontrolü</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Sağlıkla ilgili içerikler yayın öncesinde alanında uzman bir editör tarafından gözden geçirilir; kontrol eden kişi ve tarih yazının başında belirtilir.</p><!-- /wp:paragraph --><!-- wp:heading --><h2>Güncelleme</h2><!-- /wp:heading --><!-- wp:paragraph --><p>İçerikler düzenli aralıklarla ve yeni bilgiler ışığında güncellenir. Son güncelleme tarihi her sayfada görünür.</p><!-- /wp:paragraph --><!-- wp:heading --><h2>Bağımsızlık ve reklam</h2><!-- /wp:heading --><!-- wp:paragraph --><p>Ürün incelemelerimiz sponsorlu değildir. Olası bir iş birliği ya da bağlantı ortaklığı açıkça belirtilir ve değerlendirmemizi etkilemez.</p><!-- /wp:paragraph --><!-- wp:heading --><h2>Tıbbi uyarı</h2><!-- /wp:heading --><!-- wp:paragraph --><p>İçeriklerimiz bilgilendirme amaçlıdır; tıbbi tanı veya tedavinin yerine geçmez.</p><!-- /wp:paragraph -->',
			'Cilt Rotası içerikleri hakemli kaynaklara dayanır, uzman kontrolünden geçer, düzenli güncellenir ve sponsorluktan bağımsız hazırlanır.',
		),
		'iletisim'           => array(
			'İletişim',
			'<!-- wp:paragraph --><p>Soruların, düzeltme önerilerin ya da iş birliği teklifin için bize yazabilirsin. Genellikle iki iş günü içinde yanıt veriyoruz.</p><!-- /wp:paragraph --><!-- wp:paragraph --><p><strong>E-posta:</strong> merhaba@ornek.com</p><!-- /wp:paragraph --><!-- wp:group {"className":"is-style-cr-green"} --><div class="wp-block-group is-style-cr-green"><!-- wp:paragraph --><p>Kişisel cilt sorunların için tanı ya da tedavi önerisi veremiyoruz; bu konularda bir dermatoloğa başvurmanı öneririz.</p><!-- /wp:paragraph --></div><!-- /wp:group -->',
			'',
		),
		'gizlilik'           => array( 'Gizlilik Politikası', $note . '<!-- wp:paragraph --><p>Bu politika, Cilt Rotası’nı ziyaret ettiğinde hangi kişisel verilerin işlendiğini ve haklarını açıklar. Bülten için paylaştığın e-posta adresi yalnızca bülten gönderimi için kullanılır ve üçüncü kişilerle satılmaz. Kaydettiğin içerikler yalnızca kendi tarayıcında saklanır.</p><!-- /wp:paragraph -->', '' ),
		'cerez-politikasi'   => array( 'Çerez Politikası', $note . '<!-- wp:paragraph --><p>Sitemiz, temel işlevler için zorunlu çerezleri ve yalnızca izin vermen hâlinde analitik çerezleri kullanır. Tarayıcı ayarlarından çerezleri dilediğin zaman silebilir veya engelleyebilirsin.</p><!-- /wp:paragraph -->', '' ),
		'kullanim-kosullari' => array( 'Kullanım Koşulları', $note . '<!-- wp:paragraph --><p>Cilt Rotası’ndaki içerikler bilgilendirme amaçlıdır ve tıbbi tavsiye niteliği taşımaz. İçeriklerin izinsiz kopyalanması yasaktır; kaynak göstererek kısa alıntı yapılabilir.</p><!-- /wp:paragraph -->', '' ),
	);
}

/**
 * Paragraf bloğu.
 *
 * @param string $t Metin.
 * @return string
 */
function cr_p( $t ) {
	return '<!-- wp:paragraph --><p>' . $t . '</p><!-- /wp:paragraph -->';
}

/**
 * Başlık bloğu.
 *
 * @param string $t Metin.
 * @param int    $l Seviye.
 * @return string
 */
function cr_h( $t, $l = 2 ) {
	return 2 === $l ? '<!-- wp:heading --><h2>' . $t . '</h2><!-- /wp:heading -->' : '<!-- wp:heading {"level":' . $l . '} --><h' . $l . '>' . $t . '</h' . $l . '><!-- /wp:heading -->';
}

/**
 * Liste bloğu.
 *
 * @param array $items Maddeler.
 * @param bool  $check Onaylı stil.
 * @return string
 */
function cr_ul( $items, $check = false ) {
	$out = $check ? '<!-- wp:list {"className":"is-style-cr-check"} --><ul class="is-style-cr-check">' : '<!-- wp:list --><ul>';
	foreach ( $items as $i ) {
		$out .= '<!-- wp:list-item --><li>' . $i . '</li><!-- /wp:list-item -->';
	}
	return $out . '</ul><!-- /wp:list -->';
}

/**
 * Bilgi kutusu bloğu.
 *
 * @param string $title Başlık.
 * @param string $t     Metin.
 * @param string $style green|cream|warn.
 * @return string
 */
function cr_boxb( $title, $t, $style = 'green' ) {
	return '<!-- wp:group {"className":"is-style-cr-' . $style . '"} --><div class="wp-block-group is-style-cr-' . $style . '">' . cr_p( '<strong>' . $title . '</strong>' ) . cr_p( $t ) . '</div><!-- /wp:group -->';
}

/**
 * Örnek rehberler.
 *
 * @return array
 */
function cr_demo_posts() {
	$src_aad   = 'American Academy of Dermatology — Skin care basics | https://www.aad.org/public/everyday-care/skin-care-basics';
	$src_nhs_a = 'NHS — Acne | https://www.nhs.uk/conditions/acne/';
	$src_dn    = 'DermNet — Acne vulgaris | https://dermnetnz.org/topics/acne-vulgaris';
	$src_sun   = 'American Academy of Dermatology — Sunscreen FAQs | https://www.aad.org/media/stats-sunscreen';
	$src_nhs_s = 'NHS — Sunscreen and sun safety | https://www.nhs.uk/live-well/seasonal-health/sunscreen-and-sun-safety/';
	$ing       = function ( $slug, $name ) {
		return '<a href="' . esc_url( home_url( '/icerik/' . $slug . '/' ) ) . '">' . $name . '</a>';
	};
	$cat = function ( $slug, $name ) {
		return '<a href="' . esc_url( home_url( '/kategori/' . $slug . '/' ) ) . '">' . $name . '</a>';
	};
	return array(
		array(
			'title'   => 'Cilt bariyeri nedir ve neden önemlidir?',
			'slug'    => 'cilt-bariyeri-nedir',
			'excerpt' => 'Cildin en dış katmanı, suyu içeride tutan ve dış etkenleri dışarıda bırakan akıllı bir kalkan gibi çalışır. Bariyeri tanımak, bakımın geri kalanını kolaylaştırır.',
			'image'   => cr_default_img( 'barrier' ),
			'views'   => 940,
			'terms'   => array( 'category' => array( 'cilt-yapisi' ), 'cilt_sorunu' => array( 'hassasiyet', 'kuruluk' ), 'cilt_tipi' => array( 'kuru', 'hassas' ) ),
			'content' => cr_p( 'Cilt bariyeri, derinin en üst katmanı olan <strong>stratum corneum</strong> ile bu katmandaki hücrelerin arasını dolduran lipidlerden oluşur. Sık kullanılan benzetmeyle hücreler “tuğla”, aralarındaki ' . $ing( 'seramid', 'seramidler' ) . ', kolesterol ve serbest yağ asitleri ise “harç”tır.' ) .
				cr_h( 'Bariyer ne işe yarar?' ) .
				cr_ul( array( 'Ciltteki suyun buharlaşarak kaybolmasını (TEWL) azaltır.', 'Tahriş edici maddelerin, alerjenlerin ve mikropların içeri girmesini zorlaştırır.', 'Cildin pH dengesini ve mikrobiyomunu destekler.' ) ) .
				cr_h( 'Bariyer hasarının belirtileri' ) .
				cr_p( 'Gerginlik, yanma, batma, pullanma, alışık olduğun ürünlerin birden yakmaya başlaması ve kızarıklık bariyerin zorlandığını gösterebilir. Bu durum sık peeling, sert temizleyiciler, aşırı sıcak su ve kuru hava ile tetiklenebilir.' ) .
				cr_boxb( 'Bilmekte fayda var', 'Bariyeri zayıflamış cilt hem kurur hem de daha kolay tahriş olur. Bu yüzden “sivilce çıktı, daha güçlü asit kullanayım” yaklaşımı çoğu zaman tabloyu kötüleştirir.' ) .
				cr_h( 'Bariyeri nasıl desteklersin?' ) .
				cr_h( 'Rutini sadeleştir', 3 ) .
				cr_p( 'Birkaç haftalığına aktif içerikleri azalt; nazik bir temizleyici, bariyer odaklı bir nemlendirici ve gündüz güneş koruyucu ile yetin.' ) .
				cr_h( 'Doğru içerikleri seç', 3 ) .
				cr_p( $ing( 'seramid', 'Seramid' ) . ', kolesterol ve yağ asitlerini bir arada içeren formüller; ' . $ing( 'pantenol', 'pantenol' ) . ' ve ' . $ing( 'niasinamid', 'niasinamid' ) . ' gibi destekleyici içerikler bariyerin toparlanmasına yardımcı olabilir. Nem tutucu ' . $ing( 'hyaluronik-asit', 'hyaluronik asit' ) . ' ise mutlaka bir nemlendiriciyle kapatılmalı.' ) .
				cr_p( 'Cildinin genel yapısını daha iyi tanımak için ' . $cat( 'cilt-yapisi', 'Cilt Yapısı' ) . ' rehberlerine göz atabilirsin.' ),
			'meta'    => array(
				'_cr_focus_kw'     => 'cilt bariyeri',
				'_cr_seo_desc'     => 'Cilt bariyeri nedir, nasıl bozulur ve nasıl onarılır? Seramid, kolesterol ve yağ asitlerinin rolünü ve bariyer dostu sade bakımı öğren.',
				'_cr_short_answer' => 'Cilt bariyeri, derinin en üst katmanındaki hücreler ile aralarındaki seramid, kolesterol ve yağ asitlerinden oluşan koruyucu yapıdır. Su kaybını azaltır, tahriş edicilerin ve mikropların içeri girmesini zorlaştırır. Bozulduğunda cilt hem kurur hem de kolayca hassaslaşır.',
				'_cr_takeaways'    => "Bariyer, cildin su kaybını azaltan ve dış etkenlere karşı koruyan katmandır.\nSık peeling ve sert temizleyiciler bariyeri zayıflatabilir.\nSeramid, kolesterol ve yağ asitleri bariyerin yapı taşlarıdır.\nBariyer zorlandığında rutini sadeleştirmek en etkili adımdır.",
				'_cr_faq'          => array(
					array( 'q' => 'Cilt bariyerinin onarılması ne kadar sürer?', 'a' => 'Hafif hasarlar sade bir rutinle genellikle iki ila dört hafta içinde belirgin şekilde toparlanır. Şikâyetler sürüyorsa bir dermatoloğa başvurmak gerekir.' ),
					array( 'q' => 'Bariyer bozukken aktif içerik kullanılır mı?', 'a' => 'Retinoid, yüksek oranlı asitler ve C vitamini gibi güçlü aktiflere bariyer toparlanana kadar ara vermek genellikle önerilir. Niasinamid ve pantenol gibi destekleyici içerikler ise çoğu zaman iyi tolere edilir.' ),
					array( 'q' => 'Yağlı ciltte de bariyer bozulur mu?', 'a' => 'Evet. Yağlı cilt, sebum fazla olsa bile su kaybı yaşayabilir. Özellikle kurutucu ürünlerle agresif bakım yapılan yağlı ciltlerde bariyer hasarı sık görülür.' ),
				),
				'_cr_sources'      => $src_aad . "\nDermNet — Skin barrier function | https://dermnetnz.org/topics/skin-barrier-function",
			),
		),
		array(
			'title'   => 'Cilt tipim değişebilir mi?',
			'slug'    => 'cilt-tipi-degisir-mi',
			'excerpt' => 'Cilt tipi genetik bir temele sahip olsa da mevsim, hormonlar, yaş ve kullandığın ürünler cildin davranışını değiştirebilir.',
			'image'   => cr_default_img( 'blog1' ),
			'views'   => 720,
			'terms'   => array( 'category' => array( 'cilt-yapisi' ) ),
			'content' => cr_p( 'Kuru, yağlı, karma, normal… Cilt tipini bir kez öğrenip hep aynı kalacağını düşünmek çok yaygın. Oysa cilt; hormonlara, iklime, yaşa ve bakım alışkanlıklarına göre sürekli uyum sağlayan canlı bir organ.' ) .
				cr_h( 'Cilt tipi ile cilt durumu arasındaki fark' ) .
				cr_p( '<strong>Cilt tipi</strong> büyük ölçüde genetiktir ve sebum üretimiyle ilişkilidir. <strong>Cilt durumu</strong> ise o anki ihtiyaçtır: dehidrasyon, hassasiyet, akneye eğilim ya da leke gibi. Yağlı bir cilt, aynı zamanda susuz (dehidrate) olabilir.' ) .
				cr_h( 'Cildi değiştiren başlıca etkenler' ) .
				cr_ul( array( '<strong>Mevsim ve iklim:</strong> Kışın kuru hava ve kalorifer, yazın nem ve terleme.', '<strong>Hormonlar:</strong> Ergenlik, adet döngüsü, gebelik ve menopoz.', '<strong>Yaş:</strong> Sebum üretimi yaşla azalma eğilimindedir.', '<strong>Ürünler:</strong> Aşırı temizlik ve güçlü aktifler cildi kurutabilir ya da hassaslaştırabilir.' ) ) .
				cr_boxb( 'İpucu', 'Bakımını mevsim geçişlerinde gözden geçir. Aynı rutin her ay aynı sonucu vermeyebilir.', 'cream' ) .
				cr_h( 'Cildini nasıl yeniden değerlendirirsin?' ) .
				cr_p( 'Yüzünü nazik bir temizleyiciyle yıkadıktan bir saat sonra hiçbir şey sürmeden gözlemle: gerginlik, parlama ve kızarıklık nerede? Daha yapılandırılmış bir değerlendirme için <a href="' . esc_url( home_url( '/cilt-testi/' ) ) . '">2 dakikalık cilt testini</a> çözebilirsin.' ),
			'meta'    => array(
				'_cr_focus_kw'     => 'cilt tipi',
				'_cr_seo_desc'     => 'Cilt tipi değişir mi? Mevsim, hormonlar, yaş ve ürünlerin cildi nasıl etkilediğini; cilt tipi ile cilt durumu arasındaki farkı öğren.',
				'_cr_short_answer' => 'Evet, cilt tipi zamanla değişebilir. Genetik bir temeli olsa da hormonlar, yaş, mevsim, iklim ve kullandığın ürünler cildin yağ ve nem dengesini etkiler. Bu yüzden bakımını yalnızca cilt tipine değil, cildinin o anki durumuna göre de ayarlamalısın.',
				'_cr_takeaways'    => "Cilt tipi genetik, cilt durumu ise anlıktır.\nYağlı cilt de susuz (dehidrate) olabilir.\nMevsim geçişlerinde rutini gözden geçirmek iyi bir alışkanlıktır.",
				'_cr_faq'          => array(
					array( 'q' => 'Yağlı cilt yaşla kuruya döner mi?', 'a' => 'Sebum üretimi yaşla azalma eğiliminde olduğu için yağlı cilt yıllar içinde daha dengeli ya da kuruya yakın bir hâle gelebilir.' ),
					array( 'q' => 'Cilt tipimi nasıl anlarım?', 'a' => 'Nazik bir temizlikten bir saat sonra, hiçbir ürün sürmeden cildindeki gerginlik, parlama ve kızarıklığı gözlemle. Sitedeki kısa cilt testi de başlangıç için yardımcı olur.' ),
				),
				'_cr_sources'      => $src_aad,
			),
		),
		array(
			'title'   => 'Yağlı mı, karma mı? Cilt tipini doğru anlamanın yolu',
			'slug'    => 'yagli-mi-karma-mi',
			'excerpt' => 'T bölgen parlıyor ama yanakların gergin mi? Yağlı ve karma cilt arasındaki farkı basit gözlemlerle ayırt etmeyi öğren.',
			'image'   => cr_default_img( 'blog3' ),
			'views'   => 610,
			'terms'   => array( 'category' => array( 'cilt-yapisi' ), 'cilt_tipi' => array( 'yagli', 'karma' ) ),
			'content' => cr_p( 'Yağlı ve karma cilt sıkça karıştırılır, çünkü ikisinde de parlama görülür. Farkı anlamak, doğru ürün dokusunu seçmenin anahtarıdır.' ) .
				cr_h( 'Yağlı cildin işaretleri' ) .
				cr_ul( array( 'Gün ortasında tüm yüzde belirgin parlama', 'Yüzün geneline yayılmış, belirgin gözenekler', 'Siyah nokta ve sivilceye eğilim' ) ) .
				cr_h( 'Karma cildin işaretleri' ) .
				cr_ul( array( 'Alın, burun ve çenede (T bölgesi) parlama', 'Yanaklarda normal ya da kuru his', 'Gözeneklerin daha çok burun çevresinde belirgin olması' ) ) .
				cr_h( 'Bakımda ne değişir?' ) .
				cr_p( 'Yağlı ciltte jel veya hafif losyon dokuları, ' . $ing( 'niasinamid', 'niasinamid' ) . ' ve gerekirse ' . $ing( 'salisilik-asit', 'salisilik asit' ) . ' iyi bir başlangıçtır. Karma ciltte ise bölgesel yaklaşım işe yarar: hafif bir nemlendirici tüm yüze, BHA yalnızca T bölgesine.' ) .
				cr_boxb( 'Sık yapılan hata', 'Parlamayı azaltmak için nemlendiriciyi atlamak, cildin daha fazla sebum üretmesine ve bariyerin zayıflamasına yol açabilir.', 'warn' ),
			'meta'    => array(
				'_cr_focus_kw'     => 'karma cilt',
				'_cr_seo_desc'     => 'Yağlı cilt ile karma cilt arasındaki farkı basit gözlemlerle ayırt et; her biri için doğru doku ve içerik seçimini öğren.',
				'_cr_short_answer' => 'Yağlı ciltte parlama ve belirgin gözenekler yüzün geneline yayılırken, karma ciltte parlama alın, burun ve çeneden oluşan T bölgesinde yoğunlaşır; yanaklar normal ya da kurudur. Karma ciltte bölgesel bakım, yağlı ciltte ise hafif ve dengeleyici dokular öne çıkar.',
				'_cr_takeaways'    => "Yağlı ciltte parlama tüm yüze yayılır.\nKarma ciltte parlama T bölgesindedir.\nNemlendiriciyi atlamak parlamayı azaltmaz.",
				'_cr_faq'          => array(
					array( 'q' => 'Karma cilt için tek nemlendirici yeterli mi?', 'a' => 'Çoğu karma cilt için hafif, dengeleyici bir nemlendirici yeterlidir. Yanaklar çok kuruyorsa bu bölgeye biraz daha zengin bir krem eklenebilir.' ),
					array( 'q' => 'Yağlı cilt yağ içeren ürün kullanabilir mi?', 'a' => 'Evet. Komedojenik olmayan hafif yağlar ve skualan gibi içerikler yağlı ciltte de kullanılabilir; önemli olan formülün toplam dokusudur.' ),
				),
				'_cr_sources'      => $src_aad,
			),
		),
		array(
			'title'   => 'Cildim neden gergin hissediyor?',
			'slug'    => 'cildim-neden-gergin',
			'excerpt' => 'Yüzünü yıkadıktan sonra gelen gerginlik hissi; temizleyici seçiminden bariyer hasarına kadar birçok nedene işaret edebilir.',
			'image'   => cr_default_img( 'dry' ),
			'views'   => 830,
			'terms'   => array( 'category' => array( 'cilt-problemleri' ), 'cilt_sorunu' => array( 'kuruluk', 'hassasiyet' ) ),
			'content' => cr_p( 'Gerginlik, cildin “bir şeyler eksik” deme biçimidir. Çoğu zaman nem ya da lipid eksikliğine, bazen de bariyerin zorlandığına işaret eder.' ) .
				cr_h( 'En yaygın nedenler' ) .
				cr_ul( array( 'Yüksek pH’lı, yoğun köpüren temizleyiciler', 'Çok sıcak su ve uzun duşlar', 'Kuru hava, rüzgâr ve klima', 'Sık eksfoliasyon veya retinoid başlangıcı', 'Nemlendiriciyi atlamak' ) ) .
				cr_h( 'Dehidrasyon mu, kuruluk mu?' ) .
				cr_p( '<strong>Kuruluk</strong> lipid eksikliğidir ve genellikle kalıcıdır. <strong>Dehidrasyon</strong> ise su eksikliğidir ve yağlı ciltte bile görülebilir. İkisinde de gerginlik hissedilir; kurulukta pullanma, dehidrasyonda ise matlık ve ince çizgilerin belirginleşmesi öne çıkar.' ) .
				cr_h( 'Ne yapabilirsin?' ) .
				cr_ul( array( 'Köpürmeyen, nazik bir temizleyiciye geç.', 'Yüzünü ılık suyla yıka, havluyla bastırarak kurula.', 'Nemli cilde nemlendirici uygula; ' . $ing( 'hyaluronik-asit', 'hyaluronik asit' ) . ' ve gliserini ' . $ing( 'seramid', 'seramidli' ) . ' bir kremle kapat.', 'Aktifleri geçici olarak azalt.' ), true ) .
				cr_boxb( 'Ne zaman uzmana gitmeli?', 'Gerginliğe şiddetli kaşıntı, çatlak, akıntı veya yaygın kızarıklık eşlik ediyorsa egzama gibi bir durumun olup olmadığını değerlendirmek için dermatoloğa başvur.', 'warn' ),
			'meta'    => array(
				'_cr_focus_kw'     => 'cilt gerginliği',
				'_cr_seo_desc'     => 'Yüz yıkadıktan sonra gelen cilt gerginliğinin nedenleri: sert temizleyiciler, sıcak su, dehidrasyon ve bariyer hasarı. Rahatlatan sade bakımı öğren.',
				'_cr_short_answer' => 'Cilt gerginliği çoğunlukla nem ya da lipid kaybına işaret eder. Sert temizleyiciler, sıcak su, kuru hava, sık eksfoliasyon ve nemlendiriciyi atlamak başlıca nedenlerdir. Nazik bir temizleyiciye geçmek ve nemli cilde bariyer destekleyici bir nemlendirici uygulamak genellikle hızlı rahatlama sağlar.',
				'_cr_takeaways'    => "Gerginlik genellikle nem veya lipid eksikliğinin sinyalidir.\nKuruluk lipid, dehidrasyon su eksikliğidir.\nNemlendiriciyi nemli cilde uygulamak etkiyi artırır.",
				'_cr_faq'          => array(
					array( 'q' => 'Yüz yıkadıktan sonra gerginlik normal mi?', 'a' => 'Hafif ve kısa süreli bir his olağan sayılabilir; ancak her yıkamadan sonra belirgin gerginlik temizleyicinin cildine fazla geldiğini gösterir.' ),
					array( 'q' => 'Su içmek cilt gerginliğini geçirir mi?', 'a' => 'Yeterli sıvı alımı genel sağlık için önemlidir ama cildin dış katmanındaki su kaybını tek başına çözmez. Topikal nemlendirme ve bariyer desteği gerekir.' ),
				),
				'_cr_sources'      => $src_aad . "\nNHS — Dry skin | https://www.nhs.uk/conditions/dry-skin/",
			),
		),
		array(
			'title'   => 'Akneye eğilimli cilt için sade bakım rehberi',
			'slug'    => 'akneye-egilimli-cilt-bakimi',
			'excerpt' => 'Akneyle mücadelede daha fazla ürün değil, doğru ve düzenli adımlar işe yarar. Kanıta dayalı içerikleri ve sade bir rutini keşfet.',
			'image'   => cr_default_img( 'acne' ),
			'views'   => 1180,
			'terms'   => array( 'category' => array( 'cilt-problemleri' ), 'cilt_sorunu' => array( 'akne', 'siyah-nokta' ), 'cilt_tipi' => array( 'yagli', 'karma' ) ),
			'content' => cr_p( 'Akne; fazla sebum, gözeneklerde ölü hücre birikimi, <em>C. acnes</em> bakterisi ve iltihabın bir araya gelmesiyle oluşur. Bu yüzden etkili bakım, bu dört halkayı nazikçe hedefler.' ) .
				cr_h( 'Kanıtı güçlü içerikler' ) .
				cr_ul( array( $ing( 'salisilik-asit', 'Salisilik asit (BHA)' ) . ': Yağda çözünür, gözenek içini temizlemeye yardımcı olur.', $ing( 'azelaik-asit', 'Azelaik asit' ) . ': İltihap ve iz görünümünü azaltmaya yardımcı olur.', $ing( 'niasinamid', 'Niasinamid' ) . ': Sebum dengesini ve bariyeri destekler.', $ing( 'retinol', 'Retinoidler' ) . ': Hücre yenilenmesini düzenler; reçeteli seçenekler için hekime danış.' ) ) .
				cr_h( 'Sade bir akne rutini' ) .
				cr_p( 'Sabah: nazik temizleyici, hafif nemlendirici, güneş koruyucu. Akşam: temizleyici, tek bir aktif (ör. BHA ya da azelaik asit), nemlendirici. Yeni aktifleri tek tek ve haftada birkaç geceyle başlat.' ) .
				cr_boxb( 'Dikkat', 'Sivilceleri sıkmak iz ve leke riskini artırır. Yaygın, ağrılı veya iz bırakan aknede reçeteli tedaviler için dermatoloğa başvur.', 'warn' ) .
				cr_h( 'Sonuç ne zaman görülür?' ) .
				cr_p( 'Çoğu bakım adımının etkisi 8–12 haftada değerlendirilir. İlk haftalarda sabırlı olmak ve rutini sık değiştirmemek önemlidir.' ),
			'meta'    => array(
				'_cr_focus_kw'     => 'akne bakımı',
				'_cr_seo_desc'     => 'Akneye eğilimli cilt için kanıta dayalı sade bakım: salisilik asit, azelaik asit, niasinamid ve retinoidlerin rolü, örnek sabah ve akşam rutini.',
				'_cr_short_answer' => 'Akneye eğilimli ciltte sade ve düzenli bir rutin en iyi sonucu verir: nazik temizlik, tek bir kanıtlı aktif (salisilik asit, azelaik asit veya retinoid), hafif bir nemlendirici ve her gün güneş koruyucu. Etki genellikle 8–12 haftada değerlendirilir.',
				'_cr_takeaways'    => "Akne dört halkalı bir süreçtir: sebum, tıkanma, bakteri ve iltihap.\nAynı anda tek bir yeni aktif başlat.\nNemlendirici ve güneş koruyucu akne rutininin parçasıdır.\nYaygın ve iz bırakan aknede dermatoloğa başvur.",
				'_cr_steps_title'  => 'Akneye eğilimli cilt için akşam rutini',
				'_cr_steps_time'   => '5',
				'_cr_steps'        => array(
					array( 'name' => 'Nazikçe temizle', 'text' => 'Ilık su ve düşük pH’lı, köpüğü hafif bir temizleyiciyle yüzünü 30–60 saniye yıka.' ),
					array( 'name' => 'Tek bir aktif uygula', 'text' => 'Salisilik asit ya da azelaik asit içeren ürünü kuru cilde ince bir katman olarak sür.' ),
					array( 'name' => 'Nemlendir', 'text' => 'Komedojenik olmayan, hafif bir nemlendiriciyle bariyeri destekle.' ),
				),
				'_cr_faq'          => array(
					array( 'q' => 'Akne için en etkili içerik hangisi?', 'a' => 'Tek bir “en iyi” içerik yoktur. Salisilik asit, azelaik asit, benzoil peroksit ve retinoidler kanıtı güçlü seçeneklerdir; seçim cildin toleransına ve akne tipine bağlıdır.' ),
					array( 'q' => 'Akneli cilt nemlendirici kullanmalı mı?', 'a' => 'Evet. Hafif ve komedojenik olmayan bir nemlendirici, aktiflerin kurutucu etkisini dengeler ve bariyeri korur.' ),
					array( 'q' => 'Diyet akneyi etkiler mi?', 'a' => 'Yüksek glisemik indeksli besinler ve bazı kişilerde süt ürünleriyle akne arasında ilişki bildiren çalışmalar vardır; ancak etki kişiden kişiye değişir.' ),
				),
				'_cr_sources'      => $src_nhs_a . "\n" . $src_dn,
			),
		),
		array(
			'title'   => 'Sabah cilt bakım rutini nasıl olmalı?',
			'slug'    => 'sabah-cilt-bakim-rutini',
			'excerpt' => 'Güne başlarken ihtiyacın olan tek şey karmaşık bir rutin değil; temizlik, koruma ve nem dengesini doğru sırayla kurmak.',
			'image'   => cr_default_img( 'blog5' ),
			'views'   => 1020,
			'terms'   => array( 'category' => array( 'cilt-bakim-rutini' ) ),
			'content' => cr_p( 'Sabah rutininin amacı, cildi günün etkenlerine (UV ışınları, kirlilik, iklim) karşı hazırlamaktır. Bu yüzden sabah rutininin yıldızı <strong>güneş koruyucudur</strong>.' ) .
				cr_h( 'Temel sıralama' ) .
				cr_p( 'Genel kural: en ince dokudan en kalına. Suya yakın dokular önce, kremler sonra, güneş koruyucu en son.' ) .
				cr_h( 'Antioksidan eklemek ister misin?' ) .
				cr_p( $ing( 'c-vitamini', 'C vitamini' ) . ' gibi antioksidanlar güneş koruyucunun yerini tutmaz ama onunla birlikte serbest radikallere karşı ek destek sağlar. Hassas ciltte ' . $ing( 'niasinamid', 'niasinamid' ) . ' daha nazik bir alternatif olabilir.' ) .
				cr_boxb( 'Güneş koruyucu miktarı', 'Yüz ve boyun için yaklaşık iki parmak boyu (yaklaşık 1/4 çay kaşığı) ürün önerilir. Dışarıdaysan 2 saatte bir yenile.', 'cream' ) .
				cr_p( 'Güneş koruyucu seçimi için <a href="' . esc_url( home_url( '/gunes-koruyucu-secimi/' ) ) . '">güneş koruyucu rehberimize</a> göz at.' ),
			'meta'    => array(
				'_cr_focus_kw'     => 'sabah cilt bakım rutini',
				'_cr_seo_desc'     => 'Sabah cilt bakım rutini adım adım: temizlik, antioksidan serum, nemlendirici ve güneş koruyucu. Doğru sıralamayı ve miktarları öğren.',
				'_cr_short_answer' => 'Sabah cilt bakım rutini dört temel adımdan oluşur: nazik temizlik, isteğe bağlı antioksidan serum (ör. C vitamini), cilt tipine uygun nemlendirici ve en son geniş spektrumlu güneş koruyucu. Ürünler en ince dokudan en kalına doğru sürülür.',
				'_cr_takeaways'    => "Sabah rutininin en önemli adımı güneş koruyucudur.\nÜrünler inceden kalına doğru sürülür.\nAntioksidanlar güneş koruyucunun yerini tutmaz, onu destekler.",
				'_cr_steps_title'  => 'Sabah cilt bakım rutini nasıl uygulanır?',
				'_cr_steps_time'   => '5',
				'_cr_steps'        => array(
					array( 'name' => 'Temizle', 'text' => 'Cildin kuru veya hassassa yalnızca ılık su ya da çok nazik bir temizleyici yeterlidir.' ),
					array( 'name' => 'Antioksidan serum (isteğe bağlı)', 'text' => 'C vitamini ya da niasinamid içeren serumu kuru cilde birkaç damla uygula.' ),
					array( 'name' => 'Nemlendir', 'text' => 'Cilt tipine uygun dokuda bir nemlendirici sür; yağlı ciltte jel, kuru ciltte krem.' ),
					array( 'name' => 'Güneş koruyucu', 'text' => 'En az SPF 30, geniş spektrumlu bir ürünü iki parmak boyu miktarda uygula.' ),
				),
				'_cr_faq'          => array(
					array( 'q' => 'Sabah yüzümü temizleyiciyle yıkamalı mıyım?', 'a' => 'Yağlı ciltte nazik bir temizleyici faydalıdır. Kuru ve hassas ciltte sabahları yalnızca ılık su da yeterli olabilir.' ),
					array( 'q' => 'Makyaj bazı güneş koruyucunun yerini tutar mı?', 'a' => 'Hayır. SPF’li makyaj ürünleri genellikle yeterli miktarda sürülmediği için tek başına güvenilir koruma sağlamaz.' ),
				),
				'_cr_sources'      => $src_aad . "\n" . $src_nhs_s,
			),
		),
		array(
			'title'   => 'Retinol nedir, nasıl başlanır?',
			'slug'    => 'retinol-nasil-baslanir',
			'excerpt' => 'Retinol, cilt yenilenmesini destekleyen en çok araştırılmış içeriklerden biri. Doğru başlangıç, tahrişi en aza indirmenin anahtarı.',
			'image'   => cr_default_img( 'blog2' ),
			'views'   => 1320,
			'terms'   => array( 'category' => array( 'icerikler' ), 'cilt_sorunu' => array( 'ince-cizgiler', 'akne', 'leke' ) ),
			'content' => cr_p( $ing( 'retinol', 'Retinol' ) . ', A vitamini türevi olan retinoidler ailesinin kozmetiklerde en sık kullanılan üyesidir. Ciltte retinoik aside dönüşerek hücre yenilenmesini ve kolajen üretimini destekler.' ) .
				cr_h( 'Retinol ne işe yarar?' ) .
				cr_ul( array( 'İnce çizgi ve kırışıklık görünümünü azaltmaya yardımcı olur.', 'Cilt dokusunu pürüzsüzleştirir, ton eşitsizliğini dengeler.', 'Gözenek tıkanıklığını azaltarak akneye eğilimli ciltte destek olur.' ) ) .
				cr_h( 'Nasıl başlanır?' ) .
				cr_p( 'Düşük oranla (%0,1–0,3) başla, haftada 2 geceyle ilerle ve cildin alıştıkça sıklığı artır. Uygulamayı kuru cilde, bezelye büyüklüğünde bir miktarla yap.' ) .
				cr_boxb( 'Önemli', 'Gebelik ve emzirme döneminde retinoid kullanımı önerilmez. Retinol kullanırken gündüz güneş koruyucu vazgeçilmezdir.', 'warn' ) .
				cr_h( 'Retinol ile neler kullanılmamalı?' ) .
				cr_p( 'Aynı akşam yüksek oranlı AHA/BHA veya benzoil peroksit ile kullanmak tahriş riskini artırır. ' . $ing( 'niasinamid', 'Niasinamid' ) . ', ' . $ing( 'seramid', 'seramid' ) . ' ve ' . $ing( 'hyaluronik-asit', 'hyaluronik asit' ) . ' ise iyi eşlikçilerdir.' ),
			'meta'    => array(
				'_cr_focus_kw'     => 'retinol',
				'_cr_seo_desc'     => 'Retinol nedir, ne işe yarar ve nasıl başlanır? Düşük oranla başlangıç, sandviç yöntemi, uyumlu içerikler ve dikkat edilmesi gerekenler.',
				'_cr_short_answer' => 'Retinol, ciltte retinoik aside dönüşerek hücre yenilenmesini ve kolajen üretimini destekleyen bir A vitamini türevidir. Düşük oranla (%0,1–0,3), haftada iki geceyle başlanmalı; cilt alıştıkça sıklık artırılmalı ve gündüz mutlaka güneş koruyucu kullanılmalıdır.',
				'_cr_takeaways'    => "Düşük oran ve seyrek kullanımla başla.\nKuru cilde bezelye büyüklüğünde uygula.\nGündüz güneş koruyucu şarttır.\nGebelikte retinoid önerilmez.",
				'_cr_steps_title'  => 'Retinole nasıl başlanır?',
				'_cr_steps_time'   => '3',
				'_cr_steps'        => array(
					array( 'name' => 'Düşük oran seç', 'text' => '%0,1–0,3 aralığında bir retinol ürünüyle başla.' ),
					array( 'name' => 'Haftada 2 gece uygula', 'text' => 'İlk iki hafta yalnızca iki akşam, kuru cilde bezelye büyüklüğünde sür.' ),
					array( 'name' => 'Nemlendiriciyle destekle', 'text' => 'Tahriş olursa “sandviç” yöntemi dene: nemlendirici, retinol, yine nemlendirici.' ),
					array( 'name' => 'Sıklığı kademeli artır', 'text' => 'Cilt uyum sağladıkça her 2–3 haftada bir gece ekle.' ),
				),
				'_cr_faq'          => array(
					array( 'q' => 'Retinol her gün kullanılır mı?', 'a' => 'Cilt uyum sağladıktan sonra birçok kişi her gece kullanabilir; ancak başlangıçta haftada iki geceyle başlayıp kademeli artırmak tahriş riskini azaltır.' ),
					array( 'q' => 'Retinol sabah sürülür mü?', 'a' => 'Retinol ışıkla parçalanabildiği ve cildi güneşe duyarlı hâle getirebildiği için genellikle akşam kullanılması önerilir.' ),
					array( 'q' => 'Retinol arınma (purging) yapar mı?', 'a' => 'Bazı kişilerde ilk haftalarda geçici sivilcelenme görülebilir. 6–8 haftadan uzun sürerse ürün veya kullanım sıklığı gözden geçirilmelidir.' ),
				),
				'_cr_sources'      => 'DermNet — Topical retinoids | https://dermnetnz.org/topics/topical-retinoids' . "\n" . $src_aad,
			),
		),
		array(
			'title'   => 'Güneş koruyucu seçerken nelere dikkat edilmeli?',
			'slug'    => 'gunes-koruyucu-secimi',
			'excerpt' => 'SPF, PA, UVA, mineral, kimyasal… Güneş koruyucu etiketini okumayı öğren, cildine uygun ürünü rahatça seç.',
			'image'   => cr_default_img( 'blog4' ),
			'views'   => 880,
			'terms'   => array( 'category' => array( 'urunler', 'cilt-bakim-rutini' ), 'cilt_sorunu' => array( 'leke', 'ince-cizgiler' ) ),
			'content' => cr_p( 'Güneş koruyucu, erken yaşlanma ve lekeye karşı en etkili bakım adımıdır. Doğru ürünü seçmek için etiketteki birkaç ifadeyi bilmek yeterli.' ) .
				cr_h( 'SPF ve UVA koruması' ) .
				cr_p( '<strong>SPF</strong> yanık yapan UVB ışınlarına karşı korumayı gösterir. Günlük kullanım için en az SPF 30 önerilir. <strong>Geniş spektrum</strong>, PA+++ veya UVA logosu ise yaşlanma ve lekeyle ilişkili UVA korumasını belirtir.' ) .
				cr_h( 'Mineral mi, kimyasal mı?' ) .
				cr_p( 'Mineral (fiziksel) filtreler çinko oksit ve titanyum dioksit içerir; hassas ciltte genellikle iyi tolere edilir. Organik (kimyasal) filtreler ise daha hafif ve iz bırakmayan dokular sunar. İkisi de doğru miktarda kullanıldığında etkilidir.' ) .
				cr_h( 'Cilt tipine göre doku' ) .
				cr_ul( array( '<strong>Yağlı/akneye eğilimli:</strong> Jel, fluid, mat bitişli dokular.', '<strong>Kuru:</strong> Nemlendirici kremsi dokular.', '<strong>Hassas:</strong> Parfümsüz, mineral filtreli formüller.' ) ) .
				cr_boxb( 'En sık yapılan hata', 'Yetersiz miktar sürmek. Yüz ve boyun için iki parmak boyu ürün önerilir; dışarıdaysan 2 saatte bir, terleme ve yüzme sonrası yenile.', 'warn' ),
			'meta'    => array(
				'_cr_focus_kw'     => 'güneş koruyucu',
				'_cr_seo_desc'     => 'Güneş koruyucu nasıl seçilir? SPF, UVA/PA, mineral ve kimyasal filtre farkları ile cilt tipine göre doğru doku seçimini öğren.',
				'_cr_short_answer' => 'İyi bir güneş koruyucu en az SPF 30 ve geniş spektrumlu (UVA + UVB) olmalıdır. Hassas ciltte mineral filtreler, yağlı ciltte jel veya fluid dokular, kuru ciltte kremsi formüller öne çıkar. Yüz ve boyun için iki parmak boyu ürün sürülmeli ve dışarıda 2 saatte bir yenilenmelidir.',
				'_cr_takeaways'    => "Günlük kullanım için en az SPF 30, geniş spektrum.\nMineral ve kimyasal filtreler doğru kullanımda eşit derecede etkilidir.\nYeterli miktar sürmek, SPF değeri kadar önemlidir.",
				'_cr_faq'          => array(
					array( 'q' => 'SPF 50, SPF 30’dan çok mu daha iyi?', 'a' => 'SPF 30 UVB ışınlarının yaklaşık %97’sini, SPF 50 yaklaşık %98’ini engeller. Aradaki fark küçüktür; asıl önemli olan yeterli miktarda sürmek ve yenilemektir.' ),
					array( 'q' => 'Kapalı havada güneş koruyucu gerekir mi?', 'a' => 'Evet. UVA ışınları bulutların ve camın arasından geçebildiği için kapalı havada da koruma önerilir.' ),
				),
				'_cr_sources'      => $src_sun . "\n" . $src_nhs_s,
			),
		),
		array(
			'title'   => 'Dermokozmetik nedir, gerçekten kimin için?',
			'slug'    => 'dermokozmetik-nedir',
			'excerpt' => 'Eczane raflarındaki “dermokozmetik” etiketi ne anlama geliyor? Pazarlama ile bilimi birbirinden ayırmanın yollarını anlatıyoruz.',
			'image'   => cr_default_img( 'derm' ),
			'views'   => 560,
			'terms'   => array( 'category' => array( 'urunler' ) ),
			'content' => cr_p( '“Dermokozmetik” ifadesi; dermatolojik testlerden geçmiş, belirli cilt ihtiyaçlarına yönelik ve genellikle eczanelerde satılan ürünler için kullanılır. Ancak yasal olarak ayrı bir ürün sınıfı değildir.' ) .
				cr_h( 'Dermokozmetik ile kozmetik arasındaki fark' ) .
				cr_p( 'Her iki grup da kozmetik mevzuatına tabidir ve ilaç değildir. Fark çoğunlukla formülasyon yaklaşımında ve pazarlama konumlandırmasındadır: parfümsüz, hassas ciltte test edilmiş, belirli bir soruna odaklanmış ürünler.' ) .
				cr_h( 'Etiketi okurken nelere bakmalı?' ) .
				cr_ul( array( 'İçerik listesinin (INCI) ilk sıralarındaki aktifler', 'Parfüm ve alkol içeriği (hassas ciltte)', 'Ürünün amacı ve kullanım sıklığı', '“Hipoalerjenik”, “komedojenik değildir” gibi ifadelerin test koşulları' ), true ) .
				cr_boxb( 'Kısacası', 'Ürünün adı değil, içeriği ve senin ihtiyacın önemlidir. Bir içeriğin ne yaptığını öğrenmek için içerik sözlüğümüzü kullanabilirsin.' ),
			'meta'    => array(
				'_cr_focus_kw'     => 'dermokozmetik',
				'_cr_seo_desc'     => 'Dermokozmetik nedir, kozmetikten farkı ne? Eczane ürünlerindeki etiket ifadelerini ve içerik listesini bilinçli okumayı öğren.',
				'_cr_short_answer' => 'Dermokozmetik, dermatolojik testlerden geçmiş ve belirli cilt ihtiyaçlarına yönelik formüle edilmiş ürünler için kullanılan bir pazarlama kategorisidir. Yasal olarak ayrı bir sınıf değildir; bu ürünler de kozmetik mevzuatına tabidir ve ilaç değildir.',
				'_cr_takeaways'    => "Dermokozmetik yasal değil, pazarlama kategorisidir.\nÜrünün adı değil içerik listesi önemlidir.\nHassas ciltte parfüm ve alkol içeriğine dikkat et.",
				'_cr_faq'          => array(
					array( 'q' => 'Dermokozmetik ürünler daha mı güvenli?', 'a' => 'Genellikle hassas ciltte test edilmiş ve daha sade formüllerle üretilirler; ancak her ürün her cilde uygun değildir. Yeni ürünleri yama testiyle denemek her zaman iyi bir fikirdir.' ),
					array( 'q' => 'Dermokozmetik ürünler reçetesiz mi satılır?', 'a' => 'Evet. Dermokozmetik ürünler kozmetik statüsündedir ve reçete gerektirmez.' ),
				),
				'_cr_sources'      => 'European Commission — Cosmetics | https://single-market-economy.ec.europa.eu/sectors/cosmetics_en',
			),
		),
	);
}

/**
 * Örnek sözlük içerikleri.
 *
 * @return array
 */
function cr_demo_ingredients() {
	$imgs = array( 'niasinamid' => 'blog3', 'hyaluronik-asit' => 'cream', 'seramid' => 'blog6', 'retinol' => 'blog2', 'salisilik-asit' => 'cleanser', 'c-vitamini' => 'serum', 'pantenol' => 'sensitive', 'azelaik-asit' => 'acne' );
	$mk   = function ( $title, $slug, $group, $m, $content, $faq, $probs = array() ) use ( $imgs ) {
		return array(
			'type'    => 'icerik',
			'title'   => $title,
			'slug'    => $slug,
			'image'   => cr_default_img( isset( $imgs[ $slug ] ) ? $imgs[ $slug ] : 'actives' ),
			'excerpt' => $m['_cr_function'],
			'terms'   => array( 'icerik_grubu' => array( $group ), 'cilt_sorunu' => $probs ),
			'content' => $content,
			'meta'    => array_merge( $m, array( '_cr_faq' => $faq, '_cr_seo_desc' => $title . ' nedir, ne işe yarar? ' . $m['_cr_function'] . ' Uygun cilt tipleri, etkili oran ve doğru kombinler.' ) ),
		);
	};
	return array(
		$mk(
			'Niasinamid',
			'niasinamid',
			'vitaminler',
			array(
				'_cr_inci'       => 'Niacinamide',
				'_cr_aka'        => 'B3 vitamini, nikotinamid',
				'_cr_function'   => 'Bariyeri güçlendirir, sebumu dengeler ve ton eşitsizliği görünümünü azaltır.',
				'_cr_benefits'   => "Seramid üretimini destekleyerek bariyeri güçlendirir\nSebum dengesine ve gözenek görünümüne yardımcı olur\nLeke ve ton eşitsizliği görünümünü azaltır\nKızarıklığı yatıştırmaya yardımcı olur",
				'_cr_skin_types' => 'Tüm cilt tipleri; özellikle yağlı, karma ve hassas',
				'_cr_conc'       => '%2–5 (hassas ciltte %2–4)',
				'_cr_when'       => 'ikisi',
				'_cr_pregnancy'  => 'uygun',
				'_cr_evidence'   => '3',
				'_cr_irritation' => '1',
				'_cr_pairs'      => "Hyaluronik Asit\nSeramid\nRetinol\nSalisilik Asit",
				'_cr_avoid'      => "Yüksek oranlı saf C vitamini (hassas ciltte aynı anda)",
			),
			cr_p( 'Niasinamid, B3 vitamininin suda çözünen bir formudur ve kozmetikte en çok yönlü içeriklerden biri kabul edilir. İyi tolere edilmesi, hemen her rutine uyum sağlamasını kolaylaştırır.' ) . cr_h( 'Nasıl kullanılır?' ) . cr_p( 'Temizlik sonrası, nemlendiriciden önce serum olarak uygulanır. Sabah ve akşam kullanılabilir. %10 ve üzeri oranlar bazı ciltlerde kızarıklık yapabileceğinden %2–5 genellikle yeterlidir.' ),
			array(
				array( 'q' => 'Niasinamid ve C vitamini birlikte kullanılır mı?', 'a' => 'Güncel formüllerde birlikte kullanılabilirler. Hassas ciltte kızarıklık olursa birini sabah, diğerini akşam kullanmak iyi bir çözümdür.' ),
				array( 'q' => 'Niasinamid gözenekleri küçültür mü?', 'a' => 'Gözeneklerin fiziksel boyutunu değiştirmez ama sebum dengesine katkıyla görünümlerini azaltmaya yardımcı olabilir.' ),
			),
			array( 'akne', 'leke', 'hassasiyet' )
		),
		$mk(
			'Hyaluronik Asit',
			'hyaluronik-asit',
			'nem-tutucular',
			array(
				'_cr_inci'       => 'Sodium Hyaluronate',
				'_cr_aka'        => 'Hyalüronik asit, sodyum hyaluronat',
				'_cr_function'   => 'Ağırlığının kat kat fazlası suyu bağlayan, cildi dolgunlaştıran bir nem tutucudur.',
				'_cr_benefits'   => "Cildin üst katmanlarına nem çeker\nİnce kuruluk çizgilerinin görünümünü azaltır\nHer cilt tipiyle uyumludur",
				'_cr_skin_types' => 'Tüm cilt tipleri; özellikle susuz (dehidrate) ciltler',
				'_cr_conc'       => '%0,1–2',
				'_cr_when'       => 'ikisi',
				'_cr_pregnancy'  => 'uygun',
				'_cr_evidence'   => '2',
				'_cr_irritation' => '1',
				'_cr_pairs'      => "Seramid\nNiasinamid\nRetinol\nC Vitamini",
				'_cr_avoid'      => '',
			),
			cr_p( 'Hyaluronik asit ciltte doğal olarak bulunan bir moleküldür. Kozmetikte genellikle tuz formu olan sodyum hyaluronat kullanılır.' ) . cr_h( 'En iyi sonuç için' ) . cr_p( 'Nemli cilde uygula ve üzerine mutlaka bir nemlendirici sür. Çok kuru ortamlarda tek başına kullanıldığında cildi rahatlatmakta yetersiz kalabilir.' ),
			array(
				array( 'q' => 'Hyaluronik asit cildi kurutur mu?', 'a' => 'Kuru havada ve üzerine nemlendirici sürülmediğinde gerginlik hissi oluşabilir. Nemli cilde uygulayıp nemlendiriciyle kapatmak bu durumu önler.' ),
				array( 'q' => 'Yağlı cilt hyaluronik asit kullanabilir mi?', 'a' => 'Evet. Yağ içermeyen, hafif bir nem desteği sağladığı için yağlı ciltte de rahatlıkla kullanılır.' ),
			),
			array( 'kuruluk', 'ince-cizgiler' )
		),
		$mk(
			'Seramid',
			'seramid',
			'bariyer-lipidleri',
			array(
				'_cr_inci'       => 'Ceramide NP, Ceramide AP, Ceramide EOP',
				'_cr_aka'        => 'Ceramide',
				'_cr_function'   => 'Cilt bariyerinin “harcını” oluşturan lipidlerdir; su kaybını azaltır ve bariyeri onarır.',
				'_cr_benefits'   => "Bariyeri güçlendirir\nTransepidermal su kaybını azaltır\nKuru ve hassas cildi rahatlatır",
				'_cr_skin_types' => 'Tüm cilt tipleri; özellikle kuru, hassas ve bariyeri zayıflamış ciltler',
				'_cr_conc'       => 'Kolesterol ve yağ asitleriyle birlikte kullanıldığında daha etkili',
				'_cr_when'       => 'ikisi',
				'_cr_pregnancy'  => 'uygun',
				'_cr_evidence'   => '3',
				'_cr_irritation' => '1',
				'_cr_pairs'      => "Niasinamid\nHyaluronik Asit\nRetinol\nPantenol",
				'_cr_avoid'      => '',
			),
			cr_p( 'Seramidler, stratum corneum’daki lipidlerin yaklaşık yarısını oluşturur. Bariyer onarımında en iyi sonuç; seramid, kolesterol ve serbest yağ asitlerinin dengeli oranlarda bir arada bulunduğu formüllerle alınır.' ),
			array(
				array( 'q' => 'Seramid akne yapar mı?', 'a' => 'Seramidler komedojenik kabul edilmez; ancak kremin genel formülü yağlı ve ağırsa akneye eğilimli ciltte gözenek tıkanıklığına katkı verebilir.' ),
				array( 'q' => 'Seramid retinolle kullanılır mı?', 'a' => 'Evet. Seramidli bir nemlendirici, retinolün yol açabileceği kuruluk ve tahrişi azaltmaya yardımcı olur.' ),
			),
			array( 'kuruluk', 'hassasiyet' )
		),
		$mk(
			'Retinol',
			'retinol',
			'retinoidler',
			array(
				'_cr_inci'       => 'Retinol',
				'_cr_aka'        => 'A vitamini, retinoid',
				'_cr_function'   => 'Hücre yenilenmesini ve kolajen üretimini destekleyerek çizgi, doku ve ton sorunlarını hedefler.',
				'_cr_benefits'   => "İnce çizgi görünümünü azaltır\nCilt dokusunu pürüzsüzleştirir\nGözenek tıkanıklığını azaltır\nTon eşitsizliğine yardımcı olur",
				'_cr_skin_types' => 'Çoğu cilt tipi; hassas ciltte düşük oranla ve seyrek başlanmalı',
				'_cr_conc'       => '%0,1–1 (başlangıç için %0,1–0,3)',
				'_cr_when'       => 'aksam',
				'_cr_pregnancy'  => 'kacin',
				'_cr_evidence'   => '3',
				'_cr_irritation' => '3',
				'_cr_pairs'      => "Niasinamid\nSeramid\nHyaluronik Asit\nPantenol",
				'_cr_avoid'      => "Salisilik Asit (aynı akşam, yüksek oranda)\nBenzoil peroksit\nYüksek oranlı AHA",
			),
			cr_p( 'Retinol, retinoidler ailesinin reçetesiz kozmetiklerde en sık kullanılan üyesidir. Etkili ama tahriş potansiyeli olan bir içerik olduğundan kademeli başlangıç önemlidir. Ayrıntılı başlangıç planı için <a href="' . esc_url( home_url( '/retinol-nasil-baslanir/' ) ) . '">retinol rehberine</a> göz at.' ),
			array(
				array( 'q' => 'Retinol hamilelikte kullanılır mı?', 'a' => 'Gebelik ve emzirme döneminde retinoid kullanımı önerilmez. Bu dönemde azelaik asit gibi alternatifler için hekime danışılmalıdır.' ),
				array( 'q' => 'Retinolün etkisi ne zaman görülür?', 'a' => 'Doku ve akne üzerindeki etkiler genellikle 8–12 haftada, ince çizgilerdeki değişim ise 3–6 ayda belirginleşir.' ),
			),
			array( 'ince-cizgiler', 'akne', 'leke' )
		),
		$mk(
			'Salisilik Asit',
			'salisilik-asit',
			'eksfolyanlar',
			array(
				'_cr_inci'       => 'Salicylic Acid',
				'_cr_aka'        => 'BHA, beta hidroksi asit',
				'_cr_function'   => 'Yağda çözünen bir asittir; gözenek içine ulaşarak tıkanıklığı ve siyah noktaları azaltır.',
				'_cr_benefits'   => "Gözenek içini temizlemeye yardımcı olur\nSiyah nokta ve komedonları azaltır\nHafif iltihap karşıtı etkisi vardır",
				'_cr_skin_types' => 'Yağlı, karma ve akneye eğilimli ciltler',
				'_cr_conc'       => '%0,5–2',
				'_cr_when'       => 'aksam',
				'_cr_pregnancy'  => 'danis',
				'_cr_evidence'   => '3',
				'_cr_irritation' => '2',
				'_cr_pairs'      => "Niasinamid\nHyaluronik Asit\nSeramid",
				'_cr_avoid'      => "Retinol (aynı akşam)\nYüksek oranlı AHA",
			),
			cr_p( 'Salisilik asit, yağda çözünebilmesi sayesinde gözenek içindeki sebuma ulaşabilen tek yaygın asittir. Temizleyici, tonik veya serum olarak kullanılabilir.' ) . cr_h( 'Kullanım önerisi' ) . cr_p( 'Haftada 2–3 akşamla başla. Kuruluk olursa sıklığı azalt ve nemlendiriciyle destekle.' ),
			array(
				array( 'q' => 'Salisilik asit her gün kullanılır mı?', 'a' => 'Düşük oranlı durulanan temizleyiciler her gün kullanılabilir. Bırakılan ürünlerde haftada birkaç kez ile başlamak daha güvenlidir.' ),
				array( 'q' => 'Salisilik asit kuru cilde uygun mu?', 'a' => 'Kuru ciltte daha seyrek ve yalnızca gereken bölgelere uygulanmalıdır.' ),
			),
			array( 'akne', 'siyah-nokta' )
		),
		$mk(
			'C Vitamini',
			'c-vitamini',
			'antioksidanlar',
			array(
				'_cr_inci'       => 'Ascorbic Acid',
				'_cr_aka'        => 'L-askorbik asit, askorbil glukozit, sodyum askorbil fosfat',
				'_cr_function'   => 'Güçlü bir antioksidandır; serbest radikallere karşı korur ve ışıltıyı, ton eşitliğini destekler.',
				'_cr_benefits'   => "Serbest radikal hasarına karşı antioksidan destek\nLeke ve ton eşitsizliği görünümünü azaltır\nKolajen üretimini destekler",
				'_cr_skin_types' => 'Çoğu cilt tipi; hassas ciltte türev formlar daha nazik',
				'_cr_conc'       => '%10–20 (L-askorbik asit)',
				'_cr_when'       => 'sabah',
				'_cr_pregnancy'  => 'uygun',
				'_cr_evidence'   => '3',
				'_cr_irritation' => '2',
				'_cr_pairs'      => "Güneş koruyucu\nE vitamini\nFerulik asit\nHyaluronik Asit",
				'_cr_avoid'      => "Retinol (aynı anda)\nYüksek oranlı AHA/BHA",
			),
			cr_p( 'C vitamini, güneş koruyucuyla birlikte kullanıldığında gündüz korumasını destekleyen en iyi bilinen antioksidandır. Işık ve havayla bozunabildiği için koyu ve hava almayan ambalajlar tercih edilmelidir.' ),
			array(
				array( 'q' => 'C vitamini serumu neden kararır?', 'a' => 'Oksitlenme nedeniyle. Belirgin şekilde kararmış (turuncu-kahverengi) bir serumun etkinliği azalmıştır.' ),
				array( 'q' => 'C vitamini akşam kullanılır mı?', 'a' => 'Kullanılabilir; ancak antioksidan koruma amacıyla genellikle sabah, güneş koruyucudan önce önerilir.' ),
			),
			array( 'leke', 'ince-cizgiler' )
		),
		$mk(
			'Pantenol',
			'pantenol',
			'yatistiricilar',
			array(
				'_cr_inci'       => 'Panthenol',
				'_cr_aka'        => 'Provitamin B5, D-pantenol',
				'_cr_function'   => 'Nem tutucu ve yatıştırıcıdır; tahriş olmuş cildin toparlanmasına yardımcı olur.',
				'_cr_benefits'   => "Cildi yatıştırır\nNem tutar\nBariyer onarımını destekler",
				'_cr_skin_types' => 'Tüm cilt tipleri; özellikle hassas ve tahriş olmuş ciltler',
				'_cr_conc'       => '%1–5',
				'_cr_when'       => 'ikisi',
				'_cr_pregnancy'  => 'uygun',
				'_cr_evidence'   => '2',
				'_cr_irritation' => '1',
				'_cr_pairs'      => "Seramid\nNiasinamid\nRetinol",
				'_cr_avoid'      => '',
			),
			cr_p( 'Pantenol, ciltte pantotenik aside (B5 vitamini) dönüşen, çok iyi tolere edilen bir içeriktir. Retinoid veya asit kullanımına bağlı tahrişte destekleyici olarak sık tercih edilir.' ),
			array(
				array( 'q' => 'Pantenol hassas ciltte kullanılır mı?', 'a' => 'Evet, tahriş potansiyeli çok düşüktür ve hassas ciltler için sık önerilen içeriklerdendir.' ),
				array( 'q' => 'Pantenol yaraları iyileştirir mi?', 'a' => 'Kozmetik ürünlerde yüzeysel tahrişlerin toparlanmasını destekler; açık yaralar için hekime danışılmalıdır.' ),
			),
			array( 'hassasiyet', 'kizariklik' )
		),
		$mk(
			'Azelaik Asit',
			'azelaik-asit',
			'eksfolyanlar',
			array(
				'_cr_inci'       => 'Azelaic Acid',
				'_cr_aka'        => 'Azelaic acid',
				'_cr_function'   => 'Akne, kızarıklık ve leke görünümünü hedefleyen, nazik ve çok yönlü bir asittir.',
				'_cr_benefits'   => "Akneye eğilimli ciltte iltihap görünümünü azaltır\nLeke ve iz görünümünü dengeler\nKızarıklığa eğilimli ciltte iyi tolere edilir",
				'_cr_skin_types' => 'Akneye eğilimli, hassas ve kızarıklığa eğilimli ciltler',
				'_cr_conc'       => '%10 (kozmetik), daha yüksek oranlar reçeteli',
				'_cr_when'       => 'ikisi',
				'_cr_pregnancy'  => 'uygun',
				'_cr_evidence'   => '3',
				'_cr_irritation' => '2',
				'_cr_pairs'      => "Niasinamid\nHyaluronik Asit\nSeramid",
				'_cr_avoid'      => "Yüksek oranlı AHA/BHA (aynı anda)",
			),
			cr_p( 'Azelaik asit; akne, iltihap sonrası lekeler ve kızarıklık için kullanılan, görece nazik bir içeriktir. İlk kullanımlarda hafif karıncalanma olabilir, genellikle birkaç gün içinde azalır.' ),
			array(
				array( 'q' => 'Azelaik asit hamilelikte kullanılır mı?', 'a' => 'Gebelikte kullanımı genellikle güvenli kabul edilen seçeneklerdendir; yine de hekime danışılması önerilir.' ),
				array( 'q' => 'Azelaik asit ile niasinamid birlikte kullanılır mı?', 'a' => 'Evet, iyi bir ikilidir: biri akne ve lekeyi hedeflerken diğeri bariyeri destekler.' ),
			),
			array( 'akne', 'leke', 'kizariklik' )
		),
	);
}

/**
 * Örnek ürün incelemeleri (marka adı içermeyen örnekler).
 *
 * @return array
 */
function cr_demo_products() {
	$mk = function ( $title, $slug, $type, $img, $m, $content ) {
		return array(
			'type'    => 'urun_rehberi',
			'title'   => $title,
			'slug'    => $slug,
			'excerpt' => $m['_cr_verdict'],
			'image'   => cr_default_img( $img ),
			'terms'   => array( 'urun_turu' => array( $type ) ),
			'content' => $content,
			'meta'    => array_merge( array( '_cr_brand' => 'Örnek Marka', '_cr_independent' => '1' ), $m ),
		);
	};
	return array(
		$mk(
			'Seramidli bariyer kremi (örnek inceleme)',
			'seramidli-bariyer-kremi',
			'nemlendiriciler',
			'cream',
			array(
				'_cr_size'     => '50 ml',
				'_cr_texture'  => 'Orta yoğunlukta krem',
				'_cr_for'      => 'Kuru, hassas ve bariyeri zayıflamış ciltler',
				'_cr_key_ingr' => "Seramid\nPantenol\nHyaluronik Asit\nNiasinamid",
				'_cr_usage'    => 'Sabah ve akşam, temizlik ve serum sonrası yüze ve boyna ince bir katman hâlinde uygulayın.',
				'_cr_pros'     => "Parfümsüz formül\nBariyer lipidlerini bir arada içerir\nMakyaj altında iyi çalışır",
				'_cr_cons'     => "Çok yağlı ciltlere ağır gelebilir",
				'_cr_price'    => '2',
				'_cr_rating'   => '4.5',
				'_cr_verdict'  => 'Bariyeri zorlanmış ciltler için sade ve etkili bir nemlendirici örneği: doğru içerikler, iddiasız bir formül.',
			),
			cr_p( 'Bu inceleme, bir bariyer kreminde neye bakmanız gerektiğini göstermek için hazırlanmış örnek bir içeriktir. Kendi incelemelerinizi yazarken ürünün içerik listesini, dokusunu ve kimler için uygun olduğunu dürüstçe paylaşın.' )
		),
		$mk(
			'%5 niasinamid serumu (örnek inceleme)',
			'niasinamid-serumu',
			'serumlar',
			'serum',
			array(
				'_cr_size'     => '30 ml',
				'_cr_texture'  => 'Hafif, su bazlı serum',
				'_cr_for'      => 'Yağlı, karma ve leke görünümü olan ciltler',
				'_cr_key_ingr' => "Niasinamid\nHyaluronik Asit\nPantenol",
				'_cr_usage'    => 'Temizlik sonrası 3–4 damla, nemlendiriciden önce. Sabah ve akşam kullanılabilir.',
				'_cr_pros'     => "Etkili ve iyi tolere edilen oran\nHızlı emilir\nDiğer aktiflerle uyumlu",
				'_cr_cons'     => "Tek başına belirgin leke tedavisi sağlamaz",
				'_cr_price'    => '1',
				'_cr_rating'   => '4',
				'_cr_verdict'  => 'Çoğu rutine kolayca eklenebilen, oranı makul, çok yönlü bir niasinamid serumu örneği.',
			),
			cr_p( 'Niasinamid serumlarında %10 ve üzeri oranlar daha etkili anlamına gelmez; %2–5 aralığı çoğu cilt için yeterlidir ve daha iyi tolere edilir.' )
		),
		$mk(
			'Salisilik asitli jel temizleyici (örnek inceleme)',
			'salisilik-asitli-temizleyici',
			'temizleyiciler',
			'cleanser',
			array(
				'_cr_size'     => '200 ml',
				'_cr_texture'  => 'Hafif köpüren jel',
				'_cr_for'      => 'Yağlı ve akneye eğilimli ciltler',
				'_cr_key_ingr' => "Salisilik Asit\nNiasinamid\nPantenol",
				'_cr_usage'    => 'Nemli cilde masaj yaparak uygulayın, 30–60 saniye bekletip ılık suyla durulayın.',
				'_cr_pros'     => "Gözenek temizliğini destekler\nKurutucu olmayan formül",
				'_cr_cons'     => "Kuru ciltte her gün kullanım fazla gelebilir",
				'_cr_price'    => '2',
				'_cr_rating'   => '4',
				'_cr_verdict'  => 'Akneye eğilimli ciltler için dengeli bir BHA temizleyici örneği; nemlendiriciyle birlikte kullanılmalı.',
			),
			cr_p( 'Durulanan ürünlerde aktiflerin ciltte kalma süresi kısa olduğundan, salisilik asitli temizleyiciler genellikle bırakılan ürünlere göre daha nazik bir başlangıç sunar.' )
		),
		$mk(
			'Mineral filtreli SPF 50 (örnek inceleme)',
			'mineral-filtreli-spf-50',
			'gunes-koruyucular',
			'spf',
			array(
				'_cr_size'     => '50 ml',
				'_cr_texture'  => 'Hafif renkli fluid',
				'_cr_for'      => 'Hassas ve kızarıklığa eğilimli ciltler',
				'_cr_key_ingr' => "Çinko oksit\nTitanyum dioksit\nNiasinamid",
				'_cr_usage'    => 'Sabah rutininin son adımı olarak iki parmak boyu uygulayın; dışarıda 2 saatte bir yenileyin.',
				'_cr_pros'     => "Hassas ciltte iyi tolere edilen filtreler\nRenkli doku beyaz iz bırakmaz\nParfümsüz",
				'_cr_cons'     => "Açık ton seçenekleri sınırlı olabilir",
				'_cr_price'    => '3',
				'_cr_rating'   => '4.5',
				'_cr_verdict'  => 'Hassas ciltler için beyaz iz sorununu renkli dokuyla çözen, geniş spektrumlu bir mineral güneş koruyucu örneği.',
			),
			cr_p( 'Mineral filtreler hassas ciltte sık önerilir. Renkli dokular, çinko oksidin bıraktığı beyazlığı dengelerken demir oksitler sayesinde görünür ışığa karşı da ek koruma sağlayabilir.' )
		),
	);
}
