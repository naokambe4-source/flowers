<?php
/**
 * Rank Math entegrasyonu.
 *
 * Rank Math çalışırken tek bir SEO motoru olsun diye tema kendi başlık/açıklama/OG/canonical/JSON-LD
 * çıktısını kapatır ve AEO/GEO eklerini Rank Math'in tek şema grafiğine bağlar:
 *  - FAQPage, HowTo, DefinedTerm (sözlük), Product (ürün rehberi), BreadcrumbList (görünen kırıntıyla aynı)
 *  - WebPage: speakable (kısa cevap), MedicalWebPage + reviewedBy + lastReviewed (uzman kontrolü)
 *  - Article: citation (kaynaklar), about (cilt sorunları), timeRequired
 * Ayrıca: boş meta açıklamalarda kısa cevap/özet, ana sayfa başlığı, robots.txt ve site haritası uyumu,
 * Rank Math içerik analizine kısa cevap + SSS metni, tek tıkla önerilen Rank Math ayarları.
 *
 * Not: Rank Math, kurulum sihirbazında hesap bağlanmadan / “atla” denmeden ön yüzde hiçbir şey basmaz.
 * Bu durumda cr_rm_active() false döner ve tema SEO'su devrede kalır (site SEO'suz kalmaz).
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Rank Math kurulu ama ön yüzü çalışmıyor mu (sihirbaz tamamlanmamış)?
 *
 * @return bool
 */
function cr_rm_installed_inactive() {
	return defined( 'RANK_MATH_VERSION' ) && ! cr_rm_active();
}

/* -------------------------------------------------------------------------
 * Şema: tema eklerini Rank Math grafiğine ekle.
 * ---------------------------------------------------------------------- */

/**
 * Düğüm tiplerini dizi olarak verir.
 *
 * @param mixed $node Düğüm.
 * @return array
 */
function cr_rm_types( $node ) {
	return is_array( $node ) && isset( $node['@type'] ) ? (array) $node['@type'] : array();
}

/**
 * Rank Math JSON-LD verisine tema eklerini ekler.
 *
 * @param array $data   Rank Math düğümleri.
 * @param mixed $jsonld Rank Math JsonLD nesnesi.
 * @return array
 */
function cr_rm_json_ld( $data, $jsonld = null ) {
	if ( is_admin() || ! cr_rm_active() || ! cr_opt( 'seo_enable' ) ) {
		return $data;
	}
	$data = is_array( $data ) ? $data : array();

	$has = function ( $type ) use ( &$data ) {
		foreach ( $data as $n ) {
			if ( in_array( $type, cr_rm_types( $n ), true ) ) {
				return true;
			}
		}
		return false;
	};
	$find = function ( $types ) use ( &$data ) {
		foreach ( $data as $k => $n ) {
			if ( array_intersect( $types, cr_rm_types( $n ) ) ) {
				return $k;
			}
		}
		return null;
	};

	$page_key = $find( array( 'WebPage', 'CollectionPage', 'ProfilePage', 'SearchResultsPage', 'AboutPage', 'ContactPage', 'MedicalWebPage' ) );
	$page_id  = ( null !== $page_key && ! empty( $data[ $page_key ]['@id'] ) ) ? $data[ $page_key ]['@id'] : '';

	// Tema ekleri (FAQ, HowTo, DefinedTerm, Product…). cr_seo_active() false olduğundan çekirdek düğümler gelmez.
	foreach ( cr_schema_graph() as $node ) {
		$types = cr_rm_types( $node );
		if ( ! $types ) {
			continue;
		}
		$t = $types[0];
		if ( $has( $t ) ) {
			continue; // Rank Math (ör. FAQ bloğu) zaten ekledi: çift FAQPage olmasın.
		}
		if ( $page_id ) {
			if ( 'DefinedTerm' === $t || 'Product' === $t ) {
				$node['mainEntityOfPage'] = array( '@id' => $page_id );
			} elseif ( 'FAQPage' === $t || 'HowTo' === $t ) {
				$node['isPartOf'] = array( '@id' => $page_id );
			}
		}
		$data[ 'cr_' . strtolower( $t ) ] = $node;
	}

	// Görünen kırıntıyla aynı BreadcrumbList (Rank Math'in kırıntıları kapalıyken).
	if ( ! $has( 'BreadcrumbList' ) && cr_opt( 'seo_breadcrumbs' ) ) {
		$url   = $page_id ? preg_replace( '/#.*$/', '', $page_id ) : ( is_singular() ? get_permalink() : home_url( add_query_arg( array() ) ) );
		$crumb = is_front_page() ? null : cr_schema_breadcrumb( $url );
		if ( $crumb ) {
			$data['cr_breadcrumb'] = $crumb;
			if ( null !== $page_key ) {
				$data[ $page_key ]['breadcrumb'] = array( '@id' => $crumb['@id'] );
			}
		}
	}

	// Tekil içerik: WebPage ve Article zenginleştirme.
	if ( is_singular( array( 'post', 'icerik', 'urun_rehberi' ) ) && null !== $page_key ) {
		$id = get_queried_object_id();
		$wp = $data[ $page_key ];

		$wp['speakable'] = array(
			'@type'       => 'SpeakableSpecification',
			'cssSelector' => array( '.cr-article__title', '.cr-short-answer__text', '.cr-takeaways' ),
		);
		$wp['inLanguage'] = 'tr-TR';
		if ( cr_opt( 'seo_medical' ) ) {
			$wp['@type'] = array_values( array_unique( array_merge( cr_rm_types( $wp ), array( 'MedicalWebPage' ) ) ) );
			$reviewer    = cr_meta( '_cr_reviewer', $id );
			if ( ! $reviewer ) {
				$reviewer = cr_opt( 'art_reviewer_default' );
			}
			if ( $reviewer ) {
				$rv = array(
					'@type' => 'Person',
					'name'  => $reviewer,
				);
				if ( cr_meta( '_cr_reviewer_url', $id ) ) {
					$rv['url'] = cr_meta( '_cr_reviewer_url', $id );
				}
				$wp['reviewedBy']   = $rv;
				$rd                 = cr_meta( '_cr_reviewed_date', $id );
				$wp['lastReviewed'] = $rd ? $rd : mysql2date( 'Y-m-d', cr_updated( $id ) );
			}
		}
		$data[ $page_key ] = $wp;

		$art_key = $find( array( 'Article', 'BlogPosting', 'NewsArticle', 'MedicalScholarlyArticle' ) );
		if ( null !== $art_key ) {
			$art     = $data[ $art_key ];
			$sources = cr_sources( $id );
			if ( $sources && empty( $art['citation'] ) ) {
				$art['citation'] = array();
				foreach ( $sources as $s ) {
					$c = array(
						'@type' => 'CreativeWork',
						'name'  => $s['title'],
					);
					if ( $s['url'] ) {
						$c['url'] = $s['url'];
					}
					$art['citation'][] = $c;
				}
			}
			$about = get_the_terms( $id, 'cilt_sorunu' );
			if ( $about && ! is_wp_error( $about ) && empty( $art['about'] ) ) {
				$art['about'] = array();
				foreach ( $about as $a ) {
					$art['about'][] = array(
						'@type' => 'Thing',
						'name'  => $a->name,
						'url'   => get_term_link( $a ),
					);
				}
			}
			$art['timeRequired'] = 'PT' . cr_reading_time( $id ) . 'M';
			$art['inLanguage']   = 'tr-TR';
			$data[ $art_key ]    = $art;
		}
	}

	// Sözlük arşivi: DefinedTermSet.
	if ( is_post_type_archive( 'icerik' ) && ! $has( 'DefinedTermSet' ) ) {
		$data['cr_termset'] = array(
			'@type' => 'DefinedTermSet',
			'@id'   => get_post_type_archive_link( 'icerik' ) . '#set',
			'name'  => cr_opt( 'glossary_archive_title' ),
			'url'   => get_post_type_archive_link( 'icerik' ),
		);
	}
	return $data;
}
add_filter( 'rank_math/json_ld', 'cr_rm_json_ld', 98, 2 );

/* -------------------------------------------------------------------------
 * Başlık ve açıklama: boşta kalanları temanın akıllı kaynaklarıyla doldur.
 * ---------------------------------------------------------------------- */

/**
 * Meta açıklama: yazıda Rank Math açıklaması girilmemişse kısa cevap (AEO) → özet → içerik.
 *
 * @param string $desc Rank Math açıklaması.
 * @return string
 */
function cr_rm_description( $desc ) {
	if ( ! cr_opt( 'seo_enable' ) ) {
		return $desc;
	}
	if ( is_front_page() ) {
		$pid = (int) get_option( 'page_on_front' );
		if ( ( ! $pid || ! get_post_meta( $pid, 'rank_math_description', true ) ) && cr_opt( 'seo_home_desc' ) ) {
			return cr_opt( 'seo_home_desc' );
		}
		return $desc;
	}
	if ( is_singular() ) {
		if ( get_post_meta( get_queried_object_id(), 'rank_math_description', true ) ) {
			return $desc;
		}
		return cr_seo_description();
	}
	// Arşivler: Rank Math'te terime/sayfaya özel açıklama girilmemişse temanın Türkçe açıklaması
	// (Rank Math'in varsayılan şablonu “… Archive” gibi İngilizce/boş metin üretebiliyor).
	if ( is_category() || is_tag() || is_tax() ) {
		$term = get_queried_object();
		if ( $term && ! get_term_meta( $term->term_id, 'rank_math_description', true ) ) {
			return cr_seo_description();
		}
		return $desc;
	}
	if ( is_home() ) {
		$pid = (int) get_option( 'page_for_posts' );
		if ( ! $pid || ! get_post_meta( $pid, 'rank_math_description', true ) ) {
			return cr_seo_description();
		}
		return $desc;
	}
	if ( is_post_type_archive() ) {
		return cr_seo_description();
	}
	return '' === trim( (string) $desc ) ? cr_seo_description() : $desc;
}
add_filter( 'rank_math/frontend/description', 'cr_rm_description', 20 );

/**
 * Ana sayfa başlığı: Rank Math'te ana sayfaya özel başlık yoksa tema ayarı (“Ana Sayfa - Site” olmasın).
 *
 * @param string $title Başlık.
 * @return string
 */
function cr_rm_title( $title ) {
	if ( is_front_page() && cr_opt( 'seo_home_title' ) ) {
		$pid = (int) get_option( 'page_on_front' );
		if ( ! $pid || ! get_post_meta( $pid, 'rank_math_title', true ) ) {
			return cr_opt( 'seo_home_title' );
		}
	}
	return $title;
}
add_filter( 'rank_math/frontend/title', 'cr_rm_title', 20 );

/* -------------------------------------------------------------------------
 * Site haritası: ürün rehberi kapalıyken dışarıda tut.
 * ---------------------------------------------------------------------- */

add_filter(
	'rank_math/sitemap/exclude_post_type',
	function ( $exclude, $type ) {
		return ( 'urun_rehberi' === $type && function_exists( 'cr_products_on' ) && ! cr_products_on() ) ? true : $exclude;
	},
	10,
	2
);
add_filter(
	'rank_math/sitemap/exclude_taxonomy',
	function ( $exclude, $type ) {
		return ( 'urun_turu' === $type && function_exists( 'cr_products_on' ) && ! cr_products_on() ) ? true : $exclude;
	},
	10,
	2
);

/**
 * Site haritası adresi (Rank Math etkinse onun dizini).
 *
 * @return string
 */
function cr_sitemap_url() {
	if ( cr_rm_active() && class_exists( '\RankMath\Helper' ) && \RankMath\Helper::is_module_active( 'sitemap' ) && class_exists( '\RankMath\Sitemap\Router' ) ) {
		return \RankMath\Sitemap\Router::get_base_url( 'sitemap_index.xml' );
	}
	if ( function_exists( 'wp_sitemaps_get_server' ) && wp_sitemaps_get_server()->sitemaps_enabled() ) {
		return home_url( '/wp-sitemap.xml' );
	}
	return '';
}

/**
 * Rank Math robots.txt düzenleyicisine içerik girilmiş mi?
 *
 * @return bool
 */
function cr_rm_owns_robots() {
	if ( ! cr_rm_active() ) {
		return false;
	}
	$g = get_option( 'rank-math-options-general', array() );
	return is_array( $g ) && ! empty( $g['robots_txt_content'] ) && '' !== trim( (string) $g['robots_txt_content'] );
}

/**
 * Rank Math'in llms.txt modülü açık mı? (Açıksa tema kendi llms.txt'sini sunmaz.)
 *
 * @return bool
 */
function cr_rm_owns_llms() {
	return cr_rm_active() && class_exists( '\RankMath\Helper' ) && \RankMath\Helper::is_module_active( 'llms-txt' );
}

/* -------------------------------------------------------------------------
 * Önerilen Rank Math ayarları (tek tık + ilk algılamada bir kez).
 * ---------------------------------------------------------------------- */

/**
 * Rank Math'i Cilt Rotası için yapılandırır ve tema SEO alanlarını Rank Math'e taşır.
 *
 * @return array Yapılanlar.
 */
function cr_rm_configure() {
	$done = array();
	$name = get_bloginfo( 'name' );

	$t = get_option( 'rank-math-options-titles', array() );
	$t = is_array( $t ) ? $t : array();
	$t['knowledgegraph_type'] = 'company';
	$t['knowledgegraph_name'] = $name;
	$t['website_name']        = $name;
	$logo                     = cr_opt( 'org_logo' ) ? cr_opt( 'org_logo' ) : cr_opt( 'logo_image' );
	$logo                     = cr_local_img( $logo );
	if ( is_numeric( $logo ) && (int) $logo ) {
		$t['knowledgegraph_logo']    = wp_get_attachment_url( (int) $logo );
		$t['knowledgegraph_logo_id'] = (int) $logo;
	} elseif ( is_string( $logo ) && $logo ) {
		$t['knowledgegraph_logo'] = $logo;
	} elseif ( get_option( 'site_icon' ) ) {
		$t['knowledgegraph_logo']    = wp_get_attachment_url( (int) get_option( 'site_icon' ) );
		$t['knowledgegraph_logo_id'] = (int) get_option( 'site_icon' );
	}
	$t['title_separator']          = '—';
	$t['homepage_title']           = cr_opt( 'seo_home_title' ) ? cr_opt( 'seo_home_title' ) : '%sitename% %sep% %sitedesc%';
	$t['homepage_description']     = (string) cr_opt( 'seo_home_desc' );
	$t['noindex_empty_taxonomies'] = 'on';
	$t['noindex_search']           = 'on';
	$t['disable_date_archives']    = 'on';
	// İçerik türlerine doğru şema tipi.
	$t['pt_post_default_rich_snippet']   = 'article';
	$t['pt_post_default_article_type']   = 'BlogPosting';
	$t['pt_icerik_default_rich_snippet'] = 'article';
	$t['pt_icerik_default_article_type'] = 'Article';
	$t['pt_page_default_rich_snippet']   = 'off';
	$t['pt_urun_rehberi_default_rich_snippet'] = 'off'; // Product + Review tema tarafından eklenir.
	foreach ( array( 'post', 'icerik', 'page' ) as $pt ) {
		$t[ 'pt_' . $pt . '_title' ] = '%title% %sep% %sitename%';
		if ( empty( $t[ 'pt_' . $pt . '_add_meta_box' ] ) ) {
			$t[ 'pt_' . $pt . '_add_meta_box' ] = 'on';
		}
	}
	$t['pt_icerik_archive_title'] = cr_opt( 'glossary_archive_title' ) . ': Kozmetik İçerikler A–Z %sep% %sitename%';
	if ( function_exists( 'cr_products_on' ) && ! cr_products_on() ) {
		$t['pt_urun_rehberi_custom_robots'] = 'on';
		$t['pt_urun_rehberi_robots']        = array( 'noindex' );
	}
	update_option( 'rank-math-options-titles', $t );
	$done[] = 'Kurum bilgisi (Organization), logo, başlık ayracı, ana sayfa başlığı/açıklaması';
	$done[] = 'Şema tipleri: blog → BlogPosting, sözlük → Article (+ DefinedTerm), sayfalar → WebPage';
	$done[] = 'Arama sonuçları, boş kategoriler ve tarih arşivleri noindex';

	$s = get_option( 'rank-math-options-sitemap', array() );
	$s = is_array( $s ) ? $s : array();
	foreach ( array( 'post', 'page', 'icerik' ) as $pt ) {
		$s[ 'pt_' . $pt . '_sitemap' ] = 'on';
	}
	foreach ( array( 'category', 'icerik_grubu', 'cilt_sorunu', 'cilt_tipi' ) as $tx ) {
		$s[ 'tax_' . $tx . '_sitemap' ] = 'on';
	}
	$prod                            = function_exists( 'cr_products_on' ) && cr_products_on();
	$s['pt_urun_rehberi_sitemap']    = $prod ? 'on' : 'off';
	$s['tax_urun_turu_sitemap']      = $prod ? 'on' : 'off';
	$s['tax_post_tag_sitemap']       = 'off';
	$s['include_images']             = 'on';
	$s['authors_sitemap']            = 'off';
	update_option( 'rank-math-options-sitemap', $s );
	$done[] = 'Site haritası: yazılar, sayfalar, sözlük, kategoriler, içerik grupları, cilt sorunları/tipleri (görsellerle)';

	// Ana sayfaya özel başlık/açıklama (statik ön sayfa).
	$front = (int) get_option( 'page_on_front' );
	if ( $front ) {
		if ( ! get_post_meta( $front, 'rank_math_title', true ) && cr_opt( 'seo_home_title' ) ) {
			update_post_meta( $front, 'rank_math_title', cr_opt( 'seo_home_title' ) );
		}
		if ( ! get_post_meta( $front, 'rank_math_description', true ) && cr_opt( 'seo_home_desc' ) ) {
			update_post_meta( $front, 'rank_math_description', cr_opt( 'seo_home_desc' ) );
		}
	}

	// Tema SEO alanlarını Rank Math'e taşı (Rank Math alanı boşsa).
	$map = array(
		'_cr_seo_title' => 'rank_math_title',
		'_cr_seo_desc'  => 'rank_math_description',
		'_cr_focus_kw'  => 'rank_math_focus_keyword',
		'_cr_canonical' => 'rank_math_canonical_url',
	);
	$moved = 0;
	$ids   = get_posts(
		array(
			'post_type'      => array( 'post', 'page', 'icerik', 'urun_rehberi' ),
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);
	foreach ( $ids as $pid ) {
		foreach ( $map as $from => $to ) {
			$v = get_post_meta( $pid, $from, true );
			if ( '' !== (string) $v && '' === (string) get_post_meta( $pid, $to, true ) ) {
				update_post_meta( $pid, $to, $v );
				$moved++;
			}
		}
		if ( get_post_meta( $pid, '_cr_noindex', true ) && ! get_post_meta( $pid, 'rank_math_robots', true ) ) {
			update_post_meta( $pid, 'rank_math_robots', array( 'noindex' ) );
			$moved++;
		}
		$og = get_post_meta( $pid, '_cr_og_image', true );
		if ( $og && ! get_post_meta( $pid, 'rank_math_facebook_image', true ) ) {
			$og = cr_local_img( $og );
			update_post_meta( $pid, 'rank_math_facebook_image', is_numeric( $og ) ? wp_get_attachment_url( (int) $og ) : $og );
			if ( is_numeric( $og ) ) {
				update_post_meta( $pid, 'rank_math_facebook_image_id', (int) $og );
			}
			$moved++;
		}
	}
	$done[] = $moved . ' tema SEO alanı (başlık, açıklama, odak kelime, canonical, noindex, paylaşım görseli) Rank Math’e taşındı';

	update_option( 'cr_rm_configured', time(), false );
	flush_rewrite_rules( false );
	if ( function_exists( 'cr_purge_caches' ) ) {
		cr_purge_caches();
	}
	return $done;
}

/**
 * Rank Math ilk kez çalışır hâlde algılandığında önerilen ayarları bir kez uygula.
 */
function cr_rm_maybe_configure() {
	if ( ! cr_rm_active() || get_option( 'cr_rm_configured' ) || ! current_user_can( 'manage_options' ) ) {
		return;
	}
	cr_rm_configure();
	set_transient( 'cr_rm_configured_notice', 1, 10 * MINUTE_IN_SECONDS );
}
add_action( 'admin_init', 'cr_rm_maybe_configure', 20 );

/**
 * Yönetim bildirimleri: sihirbaz tamamlanmamışsa uyar; ayarlar uygulandıysa bildir.
 */
function cr_rm_notices() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if ( cr_rm_installed_inactive() ) {
		echo '<div class="notice notice-warning"><p><strong>Cilt Rotası:</strong> Rank Math kurulu ama ön yüzde henüz çalışmıyor (kurulum sihirbazında hesap bağlanmamış ya da “Atla” denmemiş). Bu sürede sitenin SEO’sunu tema yönetiyor. <a href="' . esc_url( admin_url( 'admin.php?page=rank-math-wizard' ) ) . '">Rank Math sihirbazını tamamla</a> — tamamlandığında tema SEO’yu Rank Math’e devreder ve önerilen ayarları otomatik uygular.</p></div>';
	}
	if ( get_transient( 'cr_rm_configured_notice' ) ) {
		delete_transient( 'cr_rm_configured_notice' );
		echo '<div class="notice notice-success is-dismissible"><p><strong>Cilt Rotası:</strong> Rank Math algılandı; tema SEO’yu devretti ve önerilen ayarları uyguladı (kurum bilgisi, şema tipleri, site haritası, noindex kuralları, tema SEO alanlarının taşınması). Araçlar & Kurulum’dan tekrar çalıştırabilirsin.</p></div>';
	}
}
add_action( 'admin_notices', 'cr_rm_notices' );

/**
 * Araçlar sayfası işlemi.
 */
function cr_rm_tools_action() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Yetkiniz yok.' );
	}
	check_admin_referer( 'cr_rm_configure' );
	$r   = cr_rm_active() ? cr_rm_configure() : array( 'Rank Math etkin değil ya da sihirbazı tamamlanmamış.' );
	$msg = 'Rank Math: ' . implode( ' · ', $r );
	wp_safe_redirect( add_query_arg( array( 'page' => 'cilt-rotasi-araclar', 'msg' => rawurlencode( $msg ) ), admin_url( 'admin.php' ) ) );
	exit;
}
add_action( 'admin_post_cr_rm_configure', 'cr_rm_tools_action' );

/**
 * Araçlar sayfasındaki Rank Math kartı.
 */
function cr_rm_card() {
	if ( ! defined( 'RANK_MATH_VERSION' ) ) {
		return;
	}
	$on = cr_rm_active();
	?>
	<section class="cr-card-a">
		<header class="cr-card-a__head">
			<h2>Rank Math entegrasyonu</h2>
			<p><?php echo $on ? 'Rank Math başlık, açıklama, OG, canonical, robots meta ve site haritasını yönetiyor; tema AEO/GEO şemalarını (SSS, HowTo, sözlük, uzman kontrolü, kaynaklar, speakable, kırıntı) Rank Math grafiğine ekliyor.' : 'Rank Math kurulu ama sihirbazı tamamlanmamış; şu an SEO’yu tema yönetiyor.'; ?></p>
		</header>
		<div class="cr-card-a__body cr-stack">
			<?php if ( $on ) : ?>
				<p class="description">Önerilen ayarlar: <?php echo get_option( 'cr_rm_configured' ) ? 'uygulandı (' . esc_html( wp_date( 'j F Y H:i', (int) get_option( 'cr_rm_configured' ) ) ) . ')' : 'henüz uygulanmadı'; ?>.</p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="cr_rm_configure">
					<?php wp_nonce_field( 'cr_rm_configure' ); ?>
					<button class="cr-abtn" type="submit">Önerilen Rank Math ayarlarını uygula</button>
				</form>
			<?php else : ?>
				<a class="cr-abtn" href="<?php echo esc_url( admin_url( 'admin.php?page=rank-math-wizard' ) ); ?>">Rank Math sihirbazını tamamla</a>
			<?php endif; ?>
		</div>
	</section>
	<?php
}

/* -------------------------------------------------------------------------
 * Editör: Rank Math içerik analizi kısa cevap ve SSS'yi de görsün.
 * ---------------------------------------------------------------------- */

/**
 * Yazı düzenleme ekranına küçük entegrasyon betiği.
 *
 * @param string $hook Sayfa.
 */
function cr_rm_editor_js( $hook ) {
	if ( ! cr_rm_active() || ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}
	$js = "(function(){if(!window.wp||!wp.hooks){return;}function extra(){var t='';document.querySelectorAll('[name=\"cr_meta[_cr_short_answer]\"],[name=\"cr_meta[_cr_takeaways]\"],[name^=\"cr_meta[_cr_faq]\"],[name^=\"cr_meta[_cr_steps]\"]').forEach(function(e){if(e.value){t+=' '+e.value;}});return t?'<p>'+t.replace(/</g,'&lt;')+'</p>':'';}wp.hooks.addFilter('rank_math_content','cilt-rotasi',function(c){return c+extra();});var tm;document.addEventListener('input',function(e){if(e.target&&e.target.name&&e.target.name.indexOf('cr_meta[')===0&&window.rankMathEditor){clearTimeout(tm);tm=setTimeout(function(){try{rankMathEditor.refresh('content');}catch(x){}},800);}});})();";
	wp_enqueue_script( 'wp-hooks' );
	wp_add_inline_script( 'wp-hooks', $js );
}
add_action( 'admin_enqueue_scripts', 'cr_rm_editor_js', 30 );
