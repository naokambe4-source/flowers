<?php
/**
 * Tema panelinin tüm alanları. Varsayılanlar, yönetim paneli ve canlı düzenleyici bu şemadan üretilir.
 *
 * Alan tipleri: text, textarea, url, number, color, toggle, select, image, lines, repeater, sections, code, html.
 *
 * @package CiltRotasi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Varsayılan görseller (kullanıcının mevcut tasarımından). Panelden medya kütüphanesiyle değiştirilir.
 *
 * @param string $key Anahtar.
 * @return string
 */
function cr_default_img( $key ) {
	$map = array(
		'hero'        => 'https://i.hizliresim.com/lrbqwngr.png',
		'structure'   => 'https://i.hizliresim.com/dxkqz7yj.png',
		'problems'    => 'https://i.hizliresim.com/imfo2yfu.png',
		'actives'     => 'https://i.hizliresim.com/cuvywjch.png',
		'routine'     => 'https://i.hizliresim.com/mc7sh0no.png',
		'derm'        => 'https://i.hizliresim.com/go6g4ykb.png',
		'serum'       => 'https://i.hizliresim.com/4ddxpgim.png',
		'cleanser'    => 'https://i.hizliresim.com/enmyddab.png',
		'cream'       => 'https://i.hizliresim.com/tjfha4vr.png',
		'spf'         => 'https://i.hizliresim.com/xwqnanby.png',
		'calm'        => 'https://i.hizliresim.com/s5xbe1kk.png',
		'sensitive'   => 'https://i.hizliresim.com/8fvhztkh.png',
		'dry'         => 'https://i.hizliresim.com/oeeqd9de.png',
		'acne'        => 'https://i.hizliresim.com/s6xhjfsv.png',
		'spot'        => 'https://i.hizliresim.com/mvlk077n.png',
		'barrier'     => 'https://i.hizliresim.com/ztwlkfya.png',
		'aging'       => 'https://i.hizliresim.com/h1vjhxlr.png',
		'blog1'       => 'https://i.hizliresim.com/azgxukfi.png',
		'blog2'       => 'https://i.hizliresim.com/f771gidp.png',
		'blog3'       => 'https://i.hizliresim.com/n4fvdkwv.png',
		'blog4'       => 'https://i.hizliresim.com/c76bceup.png',
		'blog5'       => 'https://i.hizliresim.com/jgjrys7g.png',
		'blog6'       => 'https://i.hizliresim.com/jeobrxzv.png',
	);
	return isset( $map[ $key ] ) ? $map[ $key ] : '';
}

/**
 * Ana sayfa bölümleri (varsayılan sıra).
 *
 * @return array<string,string>
 */
function cr_home_section_labels() {
	return array(
		'hero'      => 'Hero',
		'quick'     => 'Hızlı keşif (3 büyük kategori)',
		'featured'  => 'En çok okunanlar',
		'problems'  => 'Cilt problemleri şeridi',
		'quiz'      => 'Cilt tipini keşfet (test)',
		'guides'    => 'Cilt Rotası Rehberleri (filtreli)',
		'products'  => 'Ürün rehberi',
		'glossary'  => 'İçerik sözlüğü',
		'manifesto' => 'Manifesto (görsel mola)',
		'trust'     => 'Yayın ilkeleri / güven',
		'faq'       => 'Sık sorulan sorular (AEO)',
		'newsletter'=> 'Bülten',
	);
}

/**
 * Seçilebilir yazı tipleri (hepsi Türkçe karakter destekli, latin-ext).
 *
 * @return array
 */
function cr_font_choices() {
	return array(
		'heading' => array(
			'DM Serif Display'   => 'DM Serif Display (yüksek kontrast)',
			'Instrument Serif'   => 'Instrument Serif (modern editoryal)',
			'Cormorant Garamond' => 'Cormorant Garamond (zarif)',
			'Playfair Display'   => 'Playfair Display (klasik kontrast)',
			'Fraunces'           => 'Fraunces (yumuşak serif)',
			'Libre Caslon Display' => 'Libre Caslon Display',
		),
		'body'    => array(
			'Manrope'           => 'Manrope',
			'Inter'             => 'Inter',
			'Plus Jakarta Sans' => 'Plus Jakarta Sans',
			'DM Sans'           => 'DM Sans',
			'Outfit'            => 'Outfit',
		),
	);
}

/**
 * Tüm panel şeması.
 *
 * @return array
 */
function cr_options_schema() {
	static $schema = null;
	if ( null !== $schema ) {
		return $schema;
	}
	$fonts = cr_font_choices();

	$schema = array(

		/* ------------------------------------------------------------------ */
		'brand' => array(
			'title' => 'Marka & Görünüm',
			'icon'  => 'sparkle',
			'groups' => array(
				array(
					'title'  => 'Logo',
					'fields' => array(
						array( 'id' => 'logo_image', 'type' => 'image', 'label' => 'Logo görseli', 'desc' => 'Boş bırakılırsa metin logo kullanılır. Yatay, şeffaf PNG/SVG önerilir (yükseklik ~40px görünür).', 'default' => '' ),
						array( 'id' => 'logo_text', 'type' => 'text', 'label' => 'Metin logo', 'default' => 'Cilt Rotası' ),
						array( 'id' => 'logo_mark', 'type' => 'toggle', 'label' => 'Metin logonun yanında yaprak işareti', 'default' => 1 ),
						array( 'id' => 'logo_height', 'type' => 'number', 'label' => 'Logo görsel yüksekliği (px)', 'default' => 38, 'min' => 20, 'max' => 70 ),
					),
				),
				array(
					'title'  => 'Renk paleti',
					'desc'   => 'Arka plan çoğunlukla krem ve sıcak beyaz; yeşil ve şampanya yalnızca vurgu için.',
					'fields' => array(
						array( 'id' => 'c_green', 'type' => 'color', 'label' => 'Ana koyu yeşil', 'default' => '#24483F', 'var' => '--cr-green' ),
						array( 'id' => 'c_green2', 'type' => 'color', 'label' => 'İkinci yeşil', 'default' => '#3B5E53', 'var' => '--cr-green-2' ),
						array( 'id' => 'c_sage', 'type' => 'color', 'label' => 'Sage', 'default' => '#AAB7A2', 'var' => '--cr-sage' ),
						array( 'id' => 'c_sage_light', 'type' => 'color', 'label' => 'Açık sage', 'default' => '#E6ECE1', 'var' => '--cr-sage-light' ),
						array( 'id' => 'c_cream', 'type' => 'color', 'label' => 'Sıcak krem', 'default' => '#FBF8F2', 'var' => '--cr-cream' ),
						array( 'id' => 'c_white', 'type' => 'color', 'label' => 'Beyaz-krem', 'default' => '#FFFDFC', 'var' => '--cr-white' ),
						array( 'id' => 'c_beige', 'type' => 'color', 'label' => 'Bej', 'default' => '#E9DDCE', 'var' => '--cr-beige' ),
						array( 'id' => 'c_gold', 'type' => 'color', 'label' => 'Şampanya / altın vurgu', 'default' => '#C9A06B', 'var' => '--cr-gold' ),
						array( 'id' => 'c_text', 'type' => 'color', 'label' => 'Ana metin', 'default' => '#28322E', 'var' => '--cr-text' ),
						array( 'id' => 'c_muted', 'type' => 'color', 'label' => 'İkincil metin', 'default' => '#68716D', 'var' => '--cr-muted' ),
					),
				),
				array(
					'title'  => 'Tipografi',
					'fields' => array(
						array( 'id' => 'font_heading', 'type' => 'select', 'label' => 'Başlık yazı tipi', 'choices' => $fonts['heading'], 'default' => 'DM Serif Display' ),
						array( 'id' => 'font_body', 'type' => 'select', 'label' => 'Gövde yazı tipi', 'choices' => $fonts['body'], 'default' => 'Manrope' ),
						array( 'id' => 'font_scale', 'type' => 'number', 'label' => 'Yazı ölçeği (%)', 'desc' => 'Tüm siteyi orantılı büyütür/küçültür.', 'default' => 100, 'min' => 85, 'max' => 120 ),
						array( 'id' => 'google_fonts', 'type' => 'toggle', 'label' => 'Google Fonts yükle', 'desc' => 'Kapatırsanız sistem yazı tipleri kullanılır (en hızlı).', 'default' => 1 ),
					),
				),
				array(
					'title'  => 'Düzen',
					'fields' => array(
						array( 'id' => 'container', 'type' => 'number', 'label' => 'Maksimum içerik genişliği (px)', 'default' => 1360, 'min' => 1100, 'max' => 1440 ),
						array( 'id' => 'radius', 'type' => 'number', 'label' => 'Köşe yuvarlaklığı (px)', 'default' => 22, 'min' => 0, 'max' => 40 ),
						array( 'id' => 'section_space', 'type' => 'number', 'label' => 'Bölüm dikey boşluğu (px, masaüstü)', 'default' => 112, 'min' => 48, 'max' => 180 ),
						array( 'id' => 'animations', 'type' => 'toggle', 'label' => 'Yumuşak giriş animasyonları', 'desc' => '“Hareketi azalt” tercihi olan ziyaretçilerde her zaman kapalıdır.', 'default' => 1 ),
					),
				),
			),
		),

		/* ------------------------------------------------------------------ */
		'header' => array(
			'title' => 'Header & Menü',
			'icon'  => 'menu',
			'groups' => array(
				array(
					'title'  => 'Header',
					'fields' => array(
						array( 'id' => 'header_cta_text', 'type' => 'text', 'label' => 'Buton yazısı', 'default' => 'Cildini Keşfet' ),
						array( 'id' => 'header_cta_url', 'type' => 'url', 'label' => 'Buton bağlantısı', 'default' => '/cilt-testi/' ),
						array( 'id' => 'header_search', 'type' => 'toggle', 'label' => 'Arama ikonu', 'default' => 1 ),
						array( 'id' => 'header_saved', 'type' => 'toggle', 'label' => 'Kaydedilenler ikonu', 'default' => 1 ),
						array( 'id' => 'announce_on', 'type' => 'toggle', 'label' => 'Üst duyuru bandı', 'default' => 0 ),
						array( 'id' => 'announce_text', 'type' => 'text', 'label' => 'Duyuru metni', 'default' => 'Yeni: 2 dakikalık cilt testiyle bakım rotanı oluştur.' ),
						array( 'id' => 'announce_url', 'type' => 'url', 'label' => 'Duyuru bağlantısı', 'default' => '/cilt-testi/' ),
						array( 'id' => 'mega_problems', 'type' => 'toggle', 'label' => 'Cilt Problemleri için otomatik mega menü', 'desc' => 'Menüde “cilt-problemleri” kategorisine giden öğeye, cilt sorunlarını görselli bir panelde gösterir.', 'default' => 1 ),
					),
				),
				array(
					'title'  => 'Mobil alt menü',
					'desc'   => 'Ekranın altında sabit, iPhone güvenli alan uyumlu gezinme çubuğu.',
					'fields' => array(
						array( 'id' => 'bottom_nav', 'type' => 'toggle', 'label' => 'Mobil alt menüyü göster', 'default' => 1 ),
						array( 'id' => 'bn_home', 'type' => 'text', 'label' => '1. Ana Sayfa etiketi', 'default' => 'Ana Sayfa' ),
						array( 'id' => 'bn_explore', 'type' => 'text', 'label' => '2. Keşfet etiketi', 'default' => 'Keşfet' ),
						array( 'id' => 'bn_explore_url', 'type' => 'url', 'label' => 'Keşfet bağlantısı', 'default' => '/rehberler/' ),
						array( 'id' => 'bn_search', 'type' => 'text', 'label' => '3. Ara etiketi', 'default' => 'Ara' ),
						array( 'id' => 'bn_saved', 'type' => 'text', 'label' => '4. Kaydet etiketi', 'default' => 'Kaydet' ),
						array( 'id' => 'bn_menu', 'type' => 'text', 'label' => '5. Menü etiketi', 'default' => 'Menü' ),
					),
				),
				array(
					'title'  => 'Arama paneli',
					'fields' => array(
						array( 'id' => 'search_placeholder', 'type' => 'text', 'label' => 'Arama kutusu metni', 'default' => 'Ne arıyorsun?' ),
						array( 'id' => 'search_popular', 'type' => 'lines', 'label' => 'Popüler aramalar', 'desc' => 'Her satıra bir arama.', 'default' => "niasinamid\nretinol\ncilt bariyeri\nakne\nleke\ngüneş koruyucu" ),
						array( 'id' => 'search_log', 'type' => 'toggle', 'label' => 'Aramaları kaydet (anonim, içerik fırsatları için)', 'default' => 1 ),
					),
				),
			),
		),

		/* ------------------------------------------------------------------ */
		'hero' => array(
			'title' => 'Hero',
			'icon'  => 'star',
			'groups' => array(
				array(
					'title'  => 'Metinler',
					'fields' => array(
						array( 'id' => 'hero_eyebrow', 'type' => 'text', 'label' => 'Üst etiket', 'default' => 'Bağımsız cilt bilgisi platformu' ),
						array( 'id' => 'hero_title', 'type' => 'text', 'label' => 'Başlık (1. satır)', 'default' => 'Cildini tanı.' ),
						array( 'id' => 'hero_title_accent', 'type' => 'text', 'label' => 'Başlık (2. satır, vurgulu)', 'default' => 'Bakımını bilinçle şekillendir.' ),
						array( 'id' => 'hero_text', 'type' => 'textarea', 'label' => 'Açıklama', 'default' => 'Cilt yapısından bakım rutinlerine, içeriklerden dermokozmetik ürünlere kadar ihtiyacın olan bilgiyi sade ve anlaşılır şekilde keşfet.' ),
						array( 'id' => 'hero_cta1_text', 'type' => 'text', 'label' => 'Birincil buton', 'default' => 'Cildini Keşfet' ),
						array( 'id' => 'hero_cta1_url', 'type' => 'url', 'label' => 'Birincil buton bağlantısı', 'default' => '/cilt-testi/' ),
						array( 'id' => 'hero_cta2_text', 'type' => 'text', 'label' => 'İkincil buton', 'default' => 'Rehberlere Göz At' ),
						array( 'id' => 'hero_cta2_url', 'type' => 'url', 'label' => 'İkincil buton bağlantısı', 'default' => '/rehberler/' ),
					),
				),
				array(
					'title'  => 'Görsel',
					'fields' => array(
						array( 'id' => 'hero_image', 'type' => 'image', 'label' => 'Hero görseli', 'desc' => 'Dikey veya kare, en az 1200px. Krem/yeşil tonlu doğal bir kompozisyon önerilir.', 'default' => cr_default_img( 'hero' ) ),
						array( 'id' => 'hero_image_alt', 'type' => 'text', 'label' => 'Görsel alternatif metni (SEO)', 'default' => 'Krem ve yeşil tonlarda doğal cilt bakım kompozisyonu' ),
						array( 'id' => 'hero_focus', 'type' => 'select', 'label' => 'Görsel odak noktası', 'choices' => array( 'center' => 'Orta', 'left' => 'Sol', 'right' => 'Sağ', 'top' => 'Üst', 'bottom' => 'Alt' ), 'default' => 'center' ),
						array( 'id' => 'hero_chip_on', 'type' => 'toggle', 'label' => 'Görsel üstünde yüzen içerik kartı', 'default' => 1 ),
						array( 'id' => 'hero_chip_label', 'type' => 'text', 'label' => 'Kart etiketi', 'default' => 'Haftanın içeriği' ),
						array( 'id' => 'hero_chip_title', 'type' => 'text', 'label' => 'Kart başlığı', 'default' => 'Niasinamid' ),
						array( 'id' => 'hero_chip_text', 'type' => 'text', 'label' => 'Kart kısa metni', 'default' => 'Bariyer ve ton dengesi' ),
						array( 'id' => 'hero_chip_url', 'type' => 'url', 'label' => 'Kart bağlantısı', 'default' => '/icerik/niasinamid/' ),
						array( 'id' => 'hero_motion', 'type' => 'toggle', 'label' => 'Çok yavaş yüzen dekor ve hafif parallax', 'default' => 1 ),
					),
				),
			),
		),

		/* ------------------------------------------------------------------ */
		'home' => array(
			'title' => 'Ana Sayfa',
			'icon'  => 'home',
			'groups' => array(
				array(
					'title'  => 'Bölüm sırası',
					'desc'   => 'Sürükleyip bırakarak sıralayın, göz ikonuyla gizleyin. Canlı düzenleyicide de ▲▼ ile taşınabilir.',
					'fields' => array(
						array( 'id' => 'sections', 'type' => 'sections', 'label' => 'Bölümler' ),
					),
				),
				array(
					'title'  => 'Hızlı keşif',
					'fields' => array(
						array( 'id' => 'quick_title', 'type' => 'text', 'label' => 'Başlık', 'default' => 'Cildinle ilgili nereden başlamak istersin?' ),
						array(
							'id'      => 'quick_items',
							'type'    => 'repeater',
							'label'   => 'Kategoriler',
							'max'     => 3,
							'fields'  => array(
								'title' => array( 'type' => 'text', 'label' => 'Başlık' ),
								'text'  => array( 'type' => 'text', 'label' => 'Kısa açıklama' ),
								'image' => array( 'type' => 'image', 'label' => 'Görsel' ),
								'url'   => array( 'type' => 'url', 'label' => 'Bağlantı' ),
							),
							'default' => array(
								array( 'title' => 'Cilt Yapısı', 'text' => 'Cildin nasıl çalıştığını keşfet.', 'image' => cr_default_img( 'structure' ), 'url' => '/kategori/cilt-yapisi/' ),
								array( 'title' => 'Cilt Problemleri', 'text' => 'Akne, leke, hassasiyet ve daha fazlasını tanı.', 'image' => cr_default_img( 'problems' ), 'url' => '/kategori/cilt-problemleri/' ),
								array( 'title' => 'Cilt Bakım Rutini', 'text' => 'İhtiyacına uygun bakım adımlarını öğren.', 'image' => cr_default_img( 'routine' ), 'url' => '/kategori/cilt-bakim-rutini/' ),
							),
						),
					),
				),
				array(
					'title'  => 'En çok okunanlar',
					'fields' => array(
						array( 'id' => 'featured_title', 'type' => 'text', 'label' => 'Başlık', 'default' => 'Şu anda en çok okunanlar' ),
						array( 'id' => 'featured_source', 'type' => 'select', 'label' => 'Kaynak', 'choices' => array( 'popular' => 'En çok okunan (görüntülenme)', 'latest' => 'En yeni', 'sticky' => 'Sabitlenmiş yazılar' ), 'default' => 'popular' ),
					),
				),
				array(
					'title'  => 'Cilt problemleri şeridi',
					'fields' => array(
						array( 'id' => 'problems_title', 'type' => 'text', 'label' => 'Başlık', 'default' => 'Cildin ne anlatıyor?' ),
						array( 'id' => 'problems_text', 'type' => 'text', 'label' => 'Alt metin', 'default' => 'Bir sinyal seç, nedenlerini ve bakım yaklaşımlarını keşfet.' ),
						array(
							'id'      => 'problems_items',
							'type'    => 'repeater',
							'label'   => 'Problemler',
							'desc'    => 'Boş bırakılırsa “Cilt Sorunu” terimleri otomatik gösterilir.',
							'fields'  => array(
								'title' => array( 'type' => 'text', 'label' => 'Ad' ),
								'text'  => array( 'type' => 'text', 'label' => 'Tek satır açıklama' ),
								'image' => array( 'type' => 'image', 'label' => 'Görsel' ),
								'url'   => array( 'type' => 'url', 'label' => 'Bağlantı' ),
							),
							'default' => array(
								array( 'title' => 'Akne', 'text' => 'Sebum, gözenek ve iltihap döngüsü.', 'image' => cr_default_img( 'acne' ), 'url' => '/cilt-sorunu/akne/' ),
								array( 'title' => 'Siyah Nokta', 'text' => 'Açık komedonları nazikçe yönet.', 'image' => cr_default_img( 'blog2' ), 'url' => '/cilt-sorunu/siyah-nokta/' ),
								array( 'title' => 'Leke', 'text' => 'Ton eşitsizliği ve hiperpigmentasyon.', 'image' => cr_default_img( 'spot' ), 'url' => '/cilt-sorunu/leke/' ),
								array( 'title' => 'Kızarıklık', 'text' => 'Tetikleyicileri tanı, cildi sakinleştir.', 'image' => cr_default_img( 'sensitive' ), 'url' => '/cilt-sorunu/kizariklik/' ),
								array( 'title' => 'Hassasiyet', 'text' => 'Bariyeri güçlendiren sade bakım.', 'image' => cr_default_img( 'barrier' ), 'url' => '/cilt-sorunu/hassasiyet/' ),
								array( 'title' => 'Kuruluk', 'text' => 'Nem kaybını azaltan lipid desteği.', 'image' => cr_default_img( 'dry' ), 'url' => '/cilt-sorunu/kuruluk/' ),
								array( 'title' => 'İnce Çizgiler', 'text' => 'Kolajen, retinoid ve güneş koruması.', 'image' => cr_default_img( 'aging' ), 'url' => '/cilt-sorunu/ince-cizgiler/' ),
							),
						),
					),
				),
				array(
					'title'  => 'Cilt tipini keşfet',
					'fields' => array(
						array( 'id' => 'quiz_title', 'type' => 'text', 'label' => 'Başlık', 'default' => 'Cildin gerçekten neye ihtiyaç duyuyor?' ),
						array( 'id' => 'quiz_text', 'type' => 'textarea', 'label' => 'Metin', 'default' => 'Cilt tipi tek başına yeterli değildir. Cildinin mevcut durumunu da birlikte değerlendirmek gerekir.' ),
						array( 'id' => 'quiz_cta', 'type' => 'text', 'label' => 'Buton', 'default' => 'Kısa Teste Başla' ),
						array( 'id' => 'quiz_note', 'type' => 'text', 'label' => 'Küçük not', 'default' => '2–3 dakika' ),
						array( 'id' => 'quiz_url', 'type' => 'url', 'label' => 'Test sayfası', 'default' => '/cilt-testi/' ),
						array( 'id' => 'quiz_points', 'type' => 'lines', 'label' => 'Diyagram etiketleri', 'desc' => 'Yüz diyagramı üzerindeki bölge etiketleri (en fazla 4).', 'default' => "Alın · sebum\nYanaklar · nem\nT bölgesi · gözenek\nÇene · hassasiyet" ),
					),
				),
				array(
					'title'  => 'Rehberler',
					'fields' => array(
						array( 'id' => 'guides_title', 'type' => 'text', 'label' => 'Başlık', 'default' => 'Cilt Rotası Rehberleri' ),
						array( 'id' => 'guides_text', 'type' => 'text', 'label' => 'Alt metin', 'default' => 'Kanıta dayalı, sade ve güncel rehberler.' ),
						array( 'id' => 'guides_filters', 'type' => 'lines', 'label' => 'Filtreler', 'desc' => 'Her satır: Etiket | kategori-slug (Tümü otomatik eklenir).', 'default' => "Cilt Yapısı | cilt-yapisi\nCilt Problemleri | cilt-problemleri\nRutin | cilt-bakim-rutini\nİçerikler | icerikler\nÜrünler | urunler" ),
						array( 'id' => 'guides_count', 'type' => 'number', 'label' => 'Gösterilecek yazı', 'desc' => 'Asimetrik ritim 6 yazıda bir tekrar eder (6 veya 12 önerilir).', 'default' => 6, 'min' => 3, 'max' => 12 ),
					),
				),
				array(
					'title'  => 'Ürün rehberi',
					'fields' => array(
						array( 'id' => 'products_title', 'type' => 'text', 'label' => 'Başlık', 'default' => 'Ürünü değil, ihtiyacı seç.' ),
						array( 'id' => 'products_text', 'type' => 'textarea', 'label' => 'Açıklama', 'default' => 'Temizleyici, serum, nemlendirici ve güneş koruyucu kategorilerini içerik ve kullanım amacına göre incele.' ),
						array( 'id' => 'products_note', 'type' => 'text', 'label' => 'Bağımsızlık notu', 'default' => 'Sponsorlu sıralama yok. Ürünleri içerik ve amaç üzerinden değerlendiriyoruz.' ),
						array(
							'id'      => 'products_items',
							'type'    => 'repeater',
							'label'   => 'Kategoriler',
							'fields'  => array(
								'title' => array( 'type' => 'text', 'label' => 'Ad' ),
								'text'  => array( 'type' => 'text', 'label' => 'Kısa açıklama' ),
								'image' => array( 'type' => 'image', 'label' => 'Görsel' ),
								'url'   => array( 'type' => 'url', 'label' => 'Bağlantı' ),
							),
							'default' => array(
								array( 'title' => 'Temizleyiciler', 'text' => 'Bariyeri bozmadan arındırma.', 'image' => cr_default_img( 'cleanser' ), 'url' => '/urun-turu/temizleyiciler/' ),
								array( 'title' => 'Serumlar', 'text' => 'Hedefe yönelik aktif konsantrasyon.', 'image' => cr_default_img( 'serum' ), 'url' => '/urun-turu/serumlar/' ),
								array( 'title' => 'Nemlendiriciler', 'text' => 'Nem tutma ve lipid desteği.', 'image' => cr_default_img( 'cream' ), 'url' => '/urun-turu/nemlendiriciler/' ),
								array( 'title' => 'Güneş Koruyucular', 'text' => 'UVA/UVB filtreleri ve doku seçimi.', 'image' => cr_default_img( 'spf' ), 'url' => '/urun-turu/gunes-koruyucular/' ),
								array( 'title' => 'Tonikler', 'text' => 'Denge, hazırlık ve nazik asitler.', 'image' => cr_default_img( 'blog5' ), 'url' => '/urun-turu/tonikler/' ),
								array( 'title' => 'Aktif İçerikler', 'text' => 'Molekül molekül bilinçli seçim.', 'image' => cr_default_img( 'actives' ), 'url' => '/icerik/' ),
							),
						),
					),
				),
				array(
					'title'  => 'İçerik sözlüğü',
					'fields' => array(
						array( 'id' => 'glossary_title', 'type' => 'text', 'label' => 'Başlık', 'default' => 'İçerik sözlüğü' ),
						array( 'id' => 'glossary_text', 'type' => 'text', 'label' => 'Alt metin', 'default' => 'INCI listesindeki her molekülün ne yaptığını saniyeler içinde öğren.' ),
						array( 'id' => 'glossary_placeholder', 'type' => 'text', 'label' => 'Arama metni', 'default' => 'Bir içerik ara: niasinamid, retinol, seramid…' ),
						array( 'id' => 'glossary_popular', 'type' => 'lines', 'label' => 'Popüler içerikler', 'desc' => 'Her satır: Ad | bağlantı', 'default' => "Niasinamid | /icerik/niasinamid/\nHyaluronik Asit | /icerik/hyaluronik-asit/\nSeramid | /icerik/seramid/\nRetinol | /icerik/retinol/\nSalisilik Asit | /icerik/salisilik-asit/\nVitamin C | /icerik/c-vitamini/" ),
					),
				),
				array(
					'title'  => 'Manifesto',
					'fields' => array(
						array( 'id' => 'manifesto_quote', 'type' => 'textarea', 'label' => 'Söz', 'default' => 'Cilt bakımı, bedeninle kurduğun en dürüst iletişimdir.' ),
						array( 'id' => 'manifesto_by', 'type' => 'text', 'label' => 'İmza', 'default' => 'Cilt Rotası Manifestosu' ),
						array( 'id' => 'manifesto_image', 'type' => 'image', 'label' => 'Görsel', 'default' => cr_default_img( 'calm' ) ),
					),
				),
				array(
					'title'  => 'Yayın ilkeleri / güven',
					'fields' => array(
						array( 'id' => 'trust_title', 'type' => 'text', 'label' => 'Başlık', 'default' => 'Bilgiye nasıl yaklaşıyoruz?' ),
						array(
							'id'      => 'trust_items',
							'type'    => 'repeater',
							'label'   => 'Maddeler',
							'fields'  => array(
								'icon'  => array( 'type' => 'select', 'label' => 'İkon', 'choices' => 'icons' ),
								'title' => array( 'type' => 'text', 'label' => 'Başlık' ),
								'text'  => array( 'type' => 'textarea', 'label' => 'Metin' ),
							),
							'default' => array(
								array( 'icon' => 'flask', 'title' => 'Kanıta dayalı', 'text' => 'Her rehber hakemli çalışmalara ve dermatoloji kılavuzlarına dayanır; kaynaklar yazının sonunda listelenir.' ),
								array( 'icon' => 'shield', 'title' => 'Uzman kontrolü', 'text' => 'Sağlıkla ilgili içerikler yayın öncesi alanında uzman bir editör tarafından gözden geçirilir.' ),
								array( 'icon' => 'refresh', 'title' => 'Düzenli güncelleme', 'text' => 'Rehberler yeni bilgilerle güncellenir; güncelleme tarihi her yazıda görünür.' ),
								array( 'icon' => 'leaf', 'title' => 'Bağımsız', 'text' => 'Sponsorlu sıralama yapmıyoruz. Ürünü değil, ihtiyacı ve içeriği anlatıyoruz.' ),
							),
						),
						array( 'id' => 'trust_link_text', 'type' => 'text', 'label' => 'Bağlantı yazısı', 'default' => 'Yayın ilkelerimizi oku' ),
						array( 'id' => 'trust_link_url', 'type' => 'url', 'label' => 'Bağlantı', 'default' => '/yayin-ilkeleri/' ),
					),
				),
				array(
					'title'  => 'Sık sorulan sorular',
					'desc'   => 'Ana sayfada FAQPage yapılandırılmış verisiyle yayınlanır; yanıt motorları (AEO) ve yapay zekâ aramaları için kısa, net yanıtlar yazın.',
					'fields' => array(
						array( 'id' => 'faq_title', 'type' => 'text', 'label' => 'Başlık', 'default' => 'Kısa cevaplar' ),
						array(
							'id'      => 'faq_items',
							'type'    => 'repeater',
							'label'   => 'Sorular',
							'fields'  => array(
								'q' => array( 'type' => 'text', 'label' => 'Soru' ),
								'a' => array( 'type' => 'textarea', 'label' => 'Yanıt' ),
							),
							'default' => array(
								array( 'q' => 'Cilt tipim zamanla değişebilir mi?', 'a' => 'Evet. Cilt tipi genetik bir temele sahip olsa da hormonlar, yaş, mevsim, iklim ve kullandığın ürünler cildin yağ ve nem dengesini değiştirebilir. Bu yüzden bakımını cilt tipine ek olarak cildinin o anki durumuna göre ayarlamak gerekir.' ),
								array( 'q' => 'Cilt bariyeri nedir?', 'a' => 'Cilt bariyeri, derinin en üst katmanındaki (stratum corneum) hücreler ile aralarındaki seramid, kolesterol ve yağ asitlerinden oluşan koruyucu yapıdır. Su kaybını azaltır, tahriş edici maddelerin ve mikropların içeri girmesini zorlaştırır.' ),
								array( 'q' => 'Dermokozmetik ürün ile kozmetik ürün arasındaki fark nedir?', 'a' => 'Dermokozmetik, genellikle dermatolojik testlerden geçmiş ve belirli cilt ihtiyaçlarına yönelik formüle edilmiş ürünler için kullanılan bir pazarlama kategorisidir. Yasal olarak ayrı bir sınıf değildir; her iki grup da kozmetik mevzuatına tabidir.' ),
								array( 'q' => 'Güneş koruyucuyu her gün kullanmak gerekir mi?', 'a' => 'Evet. UVA ışınları bulutlu havada ve camın arkasında da cilde ulaşır. Leke, erken yaşlanma ve cilt kanseri riskini azaltmak için gündüz rutininin son adımında geniş spektrumlu bir güneş koruyucu kullanılması önerilir.' ),
							),
						),
					),
				),
				array(
					'title'  => 'Bülten',
					'fields' => array(
						array( 'id' => 'news_title', 'type' => 'text', 'label' => 'Başlık', 'default' => 'Cilt bakımında bilgi karmaşasını azalt.' ),
						array( 'id' => 'news_text', 'type' => 'text', 'label' => 'Alt metin', 'default' => 'Yeni rehberleri ve güncel içerikleri kaçırma.' ),
						array( 'id' => 'news_placeholder', 'type' => 'text', 'label' => 'Kutu metni', 'default' => 'E-posta adresin' ),
						array( 'id' => 'news_button', 'type' => 'text', 'label' => 'Buton', 'default' => 'Katıl' ),
						array( 'id' => 'news_note', 'type' => 'text', 'label' => 'Küçük not', 'default' => 'Ayda en fazla iki e-posta. İstediğin an tek tıkla ayrılabilirsin.' ),
						array( 'id' => 'news_success', 'type' => 'text', 'label' => 'Başarı mesajı', 'default' => 'Teşekkürler! Listeye eklendin.' ),
					),
				),
			),
		),

		/* ------------------------------------------------------------------ */
		'content' => array(
			'title' => 'Makale & Sayfalar',
			'icon'  => 'book',
			'groups' => array(
				array(
					'title'  => 'Makale sayfası',
					'fields' => array(
						array( 'id' => 'art_toc', 'type' => 'toggle', 'label' => 'Otomatik içindekiler (sağda sabit)', 'default' => 1 ),
						array( 'id' => 'art_progress', 'type' => 'toggle', 'label' => 'Okuma ilerleme çubuğu', 'default' => 1 ),
						array( 'id' => 'art_share', 'type' => 'toggle', 'label' => 'Paylaş / kaydet', 'default' => 1 ),
						array( 'id' => 'art_author', 'type' => 'toggle', 'label' => 'Yazar kutusu (E-E-A-T)', 'default' => 1 ),
						array( 'id' => 'art_related', 'type' => 'number', 'label' => 'İlgili içerik sayısı', 'default' => 3, 'min' => 0, 'max' => 6 ),
						array( 'id' => 'art_next', 'type' => 'toggle', 'label' => '“Bir sonraki rehber” kartı', 'default' => 1 ),
						array( 'id' => 'art_comments', 'type' => 'toggle', 'label' => 'Yorumlar', 'default' => 0 ),
						array( 'id' => 'art_short_label', 'type' => 'text', 'label' => 'Kısa cevap kutusu başlığı', 'default' => 'Kısa cevap' ),
						array( 'id' => 'art_takeaways_label', 'type' => 'text', 'label' => 'Öne çıkanlar kutusu başlığı', 'default' => 'Akılda kalsın' ),
						array( 'id' => 'art_disclaimer', 'type' => 'textarea', 'label' => 'Sağlık uyarısı (makale sonu)', 'default' => 'Bu içerik bilgilendirme amaçlıdır; tıbbi tanı veya tedavi yerine geçmez. Kalıcı ya da şiddetli cilt sorunlarında bir dermatoloğa başvur.' ),
						array( 'id' => 'art_reviewer_default', 'type' => 'text', 'label' => 'Varsayılan uzman kontrolü (boş = gösterme)', 'default' => '' ),
					),
				),
				array(
					'title'  => 'Arşiv & kategori',
					'fields' => array(
						array( 'id' => 'blog_title', 'type' => 'text', 'label' => 'Rehberler sayfası başlığı', 'default' => 'Cilt Rotası Rehberleri' ),
						array( 'id' => 'blog_text', 'type' => 'textarea', 'label' => 'Rehberler sayfası açıklaması', 'default' => 'Cilt yapısından içerik analizine, bakım rutinlerinden ürün rehberlerine kadar tüm yazılar.' ),
						array( 'id' => 'glossary_archive_title', 'type' => 'text', 'label' => 'İçerik sözlüğü sayfa başlığı', 'default' => 'İçerik sözlüğü' ),
						array( 'id' => 'glossary_archive_text', 'type' => 'textarea', 'label' => 'İçerik sözlüğü açıklaması', 'default' => 'Etiketteki içerikleri A’dan Z’ye keşfet: ne işe yarar, hangi cilde uygundur, neyle birlikte kullanılır.' ),
						array( 'id' => 'products_archive_title', 'type' => 'text', 'label' => 'Ürün rehberi sayfa başlığı', 'default' => 'Ürün rehberi' ),
						array( 'id' => 'products_archive_text', 'type' => 'textarea', 'label' => 'Ürün rehberi açıklaması', 'default' => 'Ürünleri markaya göre değil; içerik, doku ve kullanım amacına göre inceliyoruz.' ),
						array( 'id' => 'saved_title', 'type' => 'text', 'label' => 'Kaydedilenler başlığı', 'default' => 'Kaydettiklerin' ),
						array( 'id' => 'saved_empty', 'type' => 'text', 'label' => 'Kaydedilenler boş mesajı', 'default' => 'Henüz bir şey kaydetmedin. Beğendiğin rehberlerdeki yer imi ikonuna dokun.' ),
						array( 'id' => 'notfound_title', 'type' => 'text', 'label' => '404 başlığı', 'default' => 'Bu rota bir yere çıkmıyor.' ),
						array( 'id' => 'notfound_text', 'type' => 'text', 'label' => '404 metni', 'default' => 'Aradığın sayfa taşınmış ya da kaldırılmış olabilir. Aramayı dene ya da rehberlere göz at.' ),
					),
				),
			),
		),

		/* ------------------------------------------------------------------ */
		'quiz' => array(
			'title' => 'Cilt Testi',
			'icon'  => 'target',
			'groups' => array(
				array(
					'title'  => 'Test',
					'desc'   => 'Test, “Cilt Testi” sayfa şablonunda ve [cilt_testi] kısa koduyla gösterilir. Cevap satırı biçimi: Cevap metni | tip. Tipler: kuru, yagli, karma, normal, hassas.',
					'fields' => array(
						array( 'id' => 'qz_intro_title', 'type' => 'text', 'label' => 'Giriş başlığı', 'default' => 'Cildini birlikte tanıyalım.' ),
						array( 'id' => 'qz_intro_text', 'type' => 'textarea', 'label' => 'Giriş metni', 'default' => 'Birkaç kısa soruyla cildinin bugünkü eğilimini ve sana uygun bakım rotasını çıkaralım. Doğru ya da yanlış cevap yok; son iki haftayı düşün.' ),
						array(
							'id'      => 'qz_questions',
							'type'    => 'repeater',
							'label'   => 'Sorular',
							'fields'  => array(
								'q'       => array( 'type' => 'text', 'label' => 'Soru' ),
								'hint'    => array( 'type' => 'text', 'label' => 'İpucu' ),
								'answers' => array( 'type' => 'lines', 'label' => 'Cevaplar (Cevap | tip)' ),
							),
							'default' => array(
								array( 'q' => 'Yüzünü yıkadıktan bir saat sonra cildin nasıl hissediyor?', 'hint' => 'Hiçbir ürün sürmediğini düşün.', 'answers' => "Gergin ve pürüzlü | kuru\nT bölgem parlak, yanaklarım normal | karma\nHer yerim parlak | yagli\nRahat ve dengeli | normal\nYanıyor ya da kızarıyor | hassas" ),
								array( 'q' => 'Gün ortasında aynaya baktığında ne görüyorsun?', 'hint' => '', 'answers' => "Mat, yer yer pul pul | kuru\nAlın ve burunda parlama | karma\nTüm yüzde belirgin parlama | yagli\nPek bir değişiklik yok | normal\nYer yer kızarıklık | hassas" ),
								array( 'q' => 'Gözeneklerin nasıl görünüyor?', 'hint' => '', 'answers' => "Neredeyse görünmez | kuru\nBurun ve alında belirgin | karma\nYüzün geneline yayılmış, geniş | yagli\nKüçük ve düzenli | normal\nİnce ama cilt reaktif | hassas" ),
								array( 'q' => 'Yeni bir ürün denediğinde genelde ne olur?', 'hint' => '', 'answers' => "Kuruluk ya da gerginlik artar | kuru\nBazı bölgelerde sivilce çıkabilir | karma\nSıklıkla sivilce ya da siyah nokta | yagli\nGenelde sorun yaşamam | normal\nKaşıntı, yanma ya da kızarıklık | hassas" ),
								array( 'q' => 'Soğuk ve rüzgârlı havada cildin?', 'hint' => '', 'answers' => "Çatlar, soyulur | kuru\nYanaklarım kurur, T bölgem aynı | karma\nPek etkilenmez | yagli\nBiraz kurur ama toparlar | normal\nKızarır ve hassaslaşır | hassas" ),
								array( 'q' => 'Bakımda en çok hangisini istiyorsun?', 'hint' => 'Önceliğini seç.', 'answers' => "Daha fazla nem ve konfor | kuru\nDengeli ama hafif dokular | karma\nParlama ve sivilce kontrolü | yagli\nKoruma ve ışıltı | normal\nSakinleşme ve bariyer onarımı | hassas" ),
							),
						),
					),
				),
				array(
					'title'  => 'Sonuçlar',
					'fields' => array(
						array( 'id' => 'qz_kuru_title', 'type' => 'text', 'label' => 'Kuru — başlık', 'default' => 'Kuru cilt eğilimi' ),
						array( 'id' => 'qz_kuru_text', 'type' => 'textarea', 'label' => 'Kuru — metin', 'default' => 'Cildin yeterince lipid üretmiyor ve nemi tutmakta zorlanıyor olabilir. Nazik, köpürmeyen temizleyiciler; seramid, kolesterol ve yağ asitleri içeren bariyer kremleri ve gliserin, hyaluronik asit gibi nem tutucular rotanın temeli.' ),
						array( 'id' => 'qz_kuru_url', 'type' => 'url', 'label' => 'Kuru — önerilen rehber', 'default' => '/cilt-sorunu/kuruluk/' ),
						array( 'id' => 'qz_yagli_title', 'type' => 'text', 'label' => 'Yağlı — başlık', 'default' => 'Yağlı cilt eğilimi' ),
						array( 'id' => 'qz_yagli_text', 'type' => 'textarea', 'label' => 'Yağlı — metin', 'default' => 'Sebum üretimin yüksek görünüyor. Cildi kurutmadan dengelemek önemli: jel temizleyiciler, niasinamid, salisilik asit ve hafif, yağsız nemlendiriciler iyi bir başlangıç. Nemlendiriciyi atlamak sebumu azaltmaz.' ),
						array( 'id' => 'qz_yagli_url', 'type' => 'url', 'label' => 'Yağlı — önerilen rehber', 'default' => '/cilt-sorunu/akne/' ),
						array( 'id' => 'qz_karma_title', 'type' => 'text', 'label' => 'Karma — başlık', 'default' => 'Karma cilt eğilimi' ),
						array( 'id' => 'qz_karma_text', 'type' => 'textarea', 'label' => 'Karma — metin', 'default' => 'T bölgen yağlanırken yanakların daha kuru ya da normal. Bölgesel bakım işe yarar: hafif, dengeleyici bir nemlendirici tüm yüze; gerekirse BHA yalnızca T bölgesine.' ),
						array( 'id' => 'qz_karma_url', 'type' => 'url', 'label' => 'Karma — önerilen rehber', 'default' => '/kategori/cilt-yapisi/' ),
						array( 'id' => 'qz_normal_title', 'type' => 'text', 'label' => 'Normal — başlık', 'default' => 'Dengeli (normal) cilt' ),
						array( 'id' => 'qz_normal_text', 'type' => 'textarea', 'label' => 'Normal — metin', 'default' => 'Cildin şu an dengede görünüyor. Amacın korumak: nazik temizlik, antioksidan bir serum, hafif bir nemlendirici ve her gün geniş spektrumlu güneş koruyucu.' ),
						array( 'id' => 'qz_normal_url', 'type' => 'url', 'label' => 'Normal — önerilen rehber', 'default' => '/kategori/cilt-bakim-rutini/' ),
						array( 'id' => 'qz_hassas_title', 'type' => 'text', 'label' => 'Hassas — başlık', 'default' => 'Hassas / reaktif cilt' ),
						array( 'id' => 'qz_hassas_text', 'type' => 'textarea', 'label' => 'Hassas — metin', 'default' => 'Cildin tetikleyicilere hızlı tepki veriyor. Rutini sadeleştir: parfümsüz ürünler, az sayıda içerik, bariyer onarıcılar (seramid, pantenol, centella) ve yeni ürünleri tek tek, yama testiyle dene.' ),
						array( 'id' => 'qz_hassas_url', 'type' => 'url', 'label' => 'Hassas — önerilen rehber', 'default' => '/cilt-sorunu/hassasiyet/' ),
						array( 'id' => 'qz_disclaimer', 'type' => 'text', 'label' => 'Sonuç uyarısı', 'default' => 'Bu test tanı koymaz; yalnızca bakım için bir başlangıç noktası sunar.' ),
					),
				),
			),
		),

		/* ------------------------------------------------------------------ */
		'seo' => array(
			'title' => 'SEO · AEO · GEO',
			'icon'  => 'chart',
			'groups' => array(
				array(
					'title'  => 'Genel SEO',
					'desc'   => 'Yoast SEO, Rank Math, AIOSEO veya SEOPress etkinse tema meta etiketlerini otomatik olarak devre dışı bırakır (yinelenme olmaz); sözlük, SSS ve adım şemaları yine üretilir.',
					'fields' => array(
						array( 'id' => 'seo_enable', 'type' => 'toggle', 'label' => 'Tema SEO motorunu kullan', 'default' => 1 ),
						array( 'id' => 'seo_sep', 'type' => 'select', 'label' => 'Başlık ayracı', 'choices' => array( '—' => '—', '|' => '|', '·' => '·', '-' => '-', '•' => '•' ), 'default' => '—' ),
						array( 'id' => 'seo_home_title', 'type' => 'text', 'label' => 'Ana sayfa SEO başlığı', 'desc' => '50–60 karakter önerilir.', 'default' => 'Cilt Rotası — Cilt Bakımı, İçerik Sözlüğü ve Dermokozmetik Rehberi' ),
						array( 'id' => 'seo_home_desc', 'type' => 'textarea', 'label' => 'Ana sayfa meta açıklaması', 'desc' => '140–160 karakter önerilir.', 'default' => 'Cilt yapısı, cilt problemleri, bakım rutinleri ve aktif içerikler hakkında kanıta dayalı, sade ve bağımsız rehberler. Cildini tanı, bakımını bilinçle şekillendir.' ),
						array( 'id' => 'seo_og_image', 'type' => 'image', 'label' => 'Varsayılan paylaşım görseli (1200×630)', 'default' => cr_default_img( 'hero' ) ),
						array( 'id' => 'seo_twitter', 'type' => 'text', 'label' => 'X (Twitter) kullanıcı adı', 'desc' => '@ ile birlikte.', 'default' => '' ),
						array( 'id' => 'seo_noindex_search', 'type' => 'toggle', 'label' => 'Arama sonuçlarını dizine ekletme', 'default' => 1 ),
						array( 'id' => 'seo_noindex_tags', 'type' => 'toggle', 'label' => 'Etiket arşivlerini dizine ekletme', 'default' => 1 ),
						array( 'id' => 'seo_noindex_author', 'type' => 'toggle', 'label' => 'Yazar arşivlerini dizine ekletme', 'default' => 0 ),
						array( 'id' => 'seo_noindex_date', 'type' => 'toggle', 'label' => 'Tarih arşivlerini dizine ekletme', 'default' => 1 ),
						array( 'id' => 'seo_breadcrumbs', 'type' => 'toggle', 'label' => 'Ekmek kırıntısı (breadcrumb)', 'default' => 1 ),
						array( 'id' => 'seo_auto_alt', 'type' => 'toggle', 'label' => 'Alt metni boş görsellere otomatik alt metin', 'default' => 1 ),
						array( 'id' => 'seo_attachment_redirect', 'type' => 'toggle', 'label' => 'Ek (attachment) sayfalarını ana yazıya yönlendir', 'default' => 1 ),
					),
				),
				array(
					'title'  => 'Kurum (Organization) & E-E-A-T',
					'fields' => array(
						array( 'id' => 'org_name', 'type' => 'text', 'label' => 'Kurum adı', 'default' => 'Cilt Rotası' ),
						array( 'id' => 'org_alt', 'type' => 'text', 'label' => 'Alternatif ad', 'default' => 'CiltRotası' ),
						array( 'id' => 'org_logo', 'type' => 'image', 'label' => 'Şema logosu (kare, en az 112px)', 'default' => '' ),
						array( 'id' => 'org_desc', 'type' => 'textarea', 'label' => 'Kurum açıklaması', 'default' => 'Cilt Rotası; cilt yapısı, cilt problemleri, bakım rutinleri ve kozmetik içerikler hakkında kanıta dayalı bilgi sunan bağımsız bir Türkçe cilt bilgisi platformudur.' ),
						array( 'id' => 'org_email', 'type' => 'text', 'label' => 'İletişim e-postası', 'default' => '' ),
						array( 'id' => 'org_founded', 'type' => 'text', 'label' => 'Kuruluş yılı', 'default' => '2026' ),
						array( 'id' => 'org_same_as', 'type' => 'lines', 'label' => 'Resmî profiller (sameAs)', 'desc' => 'Her satıra bir URL. Footer sosyal bağlantıları otomatik eklenir.', 'default' => '' ),
						array( 'id' => 'org_knows', 'type' => 'lines', 'label' => 'Uzmanlık alanları (knowsAbout)', 'default' => "Cilt bakımı\nDermokozmetik\nKozmetik içerikler\nCilt bariyeri\nAkne\nHiperpigmentasyon\nGüneş koruma" ),
						array( 'id' => 'seo_medical', 'type' => 'toggle', 'label' => 'Makaleleri MedicalWebPage olarak işaretle', 'desc' => 'Uzman kontrolü (reviewedBy) ve son inceleme tarihi (lastReviewed) şemaya eklenir.', 'default' => 1 ),
					),
				),
				array(
					'title'  => 'GEO — Üretken yapay zekâ motorları',
					'desc'   => 'ChatGPT, Claude, Perplexity, Gemini gibi motorların siteyi doğru anlaması ve kaynak göstermesi için.',
					'fields' => array(
						array( 'id' => 'geo_llms', 'type' => 'toggle', 'label' => '/llms.txt ve /llms-full.txt yayınla', 'default' => 1 ),
						array( 'id' => 'geo_llms_intro', 'type' => 'textarea', 'label' => 'llms.txt tanıtım metni', 'default' => 'Cilt Rotası, cilt bakımı hakkında kanıta dayalı Türkçe rehberler yayınlayan bağımsız bir bilgi platformudur. İçerikler bilgilendirme amaçlıdır; tıbbi tanı veya tedavi yerine geçmez. Alıntı yaparken lütfen ilgili sayfanın URL’sini kaynak olarak belirtin.' ),
						array( 'id' => 'geo_full_limit', 'type' => 'number', 'label' => 'llms-full.txt içindeki yazı sayısı', 'default' => 40, 'min' => 5, 'max' => 200 ),
						array( 'id' => 'geo_ai_bots', 'type' => 'select', 'label' => 'Yapay zekâ tarayıcıları (robots.txt)', 'choices' => array( 'allow' => 'İzin ver (önerilen — kaynak olarak gösterilme şansı)', 'search' => 'Yalnızca arama/yanıt botlarına izin ver, eğitim botlarını engelle', 'block' => 'Hepsini engelle' ), 'default' => 'allow' ),
						array( 'id' => 'geo_markdown', 'type' => 'toggle', 'label' => 'Makalelerin Markdown sürümü (?format=md)', 'desc' => 'Yapay zekâ ajanları sayfanın sade metin sürümünü okuyabilir; head içinde alternate bağlantısı eklenir.', 'default' => 1 ),
						array( 'id' => 'geo_robots_extra', 'type' => 'code', 'label' => 'robots.txt ek satırlar', 'default' => '' ),
					),
				),
				array(
					'title'  => 'Doğrulama & analitik',
					'fields' => array(
						array( 'id' => 'verify_google', 'type' => 'text', 'label' => 'Google Search Console doğrulama kodu', 'default' => '' ),
						array( 'id' => 'verify_bing', 'type' => 'text', 'label' => 'Bing Webmaster doğrulama kodu', 'default' => '' ),
						array( 'id' => 'verify_yandex', 'type' => 'text', 'label' => 'Yandex doğrulama kodu', 'default' => '' ),
						array( 'id' => 'verify_pinterest', 'type' => 'text', 'label' => 'Pinterest doğrulama kodu', 'default' => '' ),
						array( 'id' => 'ga4_id', 'type' => 'text', 'label' => 'Google Analytics 4 ölçüm kimliği', 'desc' => 'G-XXXXXXX. Sayfa yüklendikten sonra gecikmeli yüklenir (Core Web Vitals dostu).', 'default' => '' ),
						array( 'id' => 'track_views', 'type' => 'toggle', 'label' => 'Okunma sayısını tut (en çok okunanlar için)', 'default' => 1 ),
					),
				),
			),
		),

		/* ------------------------------------------------------------------ */
		'footer' => array(
			'title' => 'Footer & Sosyal',
			'icon'  => 'layers',
			'groups' => array(
				array(
					'title'  => 'Footer',
					'fields' => array(
						array( 'id' => 'footer_text', 'type' => 'textarea', 'label' => 'Kısa açıklama', 'default' => 'Cilt bakımını daha anlaşılır hale getiren bağımsız bilgi platformu.' ),
						array( 'id' => 'footer_col1', 'type' => 'text', 'label' => '1. kolon başlığı', 'default' => 'Keşfet' ),
						array( 'id' => 'footer_col2', 'type' => 'text', 'label' => '2. kolon başlığı', 'default' => 'Kurumsal' ),
						array( 'id' => 'footer_col3', 'type' => 'text', 'label' => '3. kolon başlığı', 'default' => 'Yasal' ),
						array( 'id' => 'footer_disclaimer', 'type' => 'textarea', 'label' => 'Alt uyarı', 'default' => 'İçerikler bilgilendirme amaçlıdır ve tıbbi tanı veya tedavi yerine geçmez.' ),
						array( 'id' => 'footer_copy', 'type' => 'text', 'label' => 'Telif satırı', 'desc' => '{yil} otomatik yıl olur.', 'default' => '© {yil} Cilt Rotası. Tüm hakları saklıdır.' ),
					),
				),
				array(
					'title'  => 'Sosyal medya',
					'fields' => array(
						array( 'id' => 'social_instagram', 'type' => 'url', 'label' => 'Instagram', 'default' => '' ),
						array( 'id' => 'social_pinterest', 'type' => 'url', 'label' => 'Pinterest', 'default' => '' ),
						array( 'id' => 'social_tiktok', 'type' => 'url', 'label' => 'TikTok', 'default' => '' ),
						array( 'id' => 'social_youtube', 'type' => 'url', 'label' => 'YouTube', 'default' => '' ),
						array( 'id' => 'social_x', 'type' => 'url', 'label' => 'X (Twitter)', 'default' => '' ),
					),
				),
			),
		),

		/* ------------------------------------------------------------------ */
		'admin' => array(
			'title' => 'Yönetim Paneli',
			'icon'  => 'heart',
			'groups' => array(
				array(
					'title'  => 'Karşılama',
					'fields' => array(
						array( 'id' => 'adm_greeting', 'type' => 'text', 'label' => 'Karşılama başlığı', 'default' => 'Aslı, seni seviyorum' ),
						array( 'id' => 'adm_notes', 'type' => 'lines', 'label' => 'Sevgi notları', 'desc' => 'Her girişte biri rastgele gösterilir.', 'default' => "Bugün de dünyanın en güzel rotası sensin.\nHer yazın, birinin cildine iyi gelecek. Seninle gurur duyuyorum.\nKahveni al, bugün harika içerikler çıkacak.\nCilt Rotası seninle güzel. Sen olmasan bu rota olmazdı.\nYorulursan dur, nefes al; ben hep buradayım." ),
						array( 'id' => 'adm_hearts', 'type' => 'toggle', 'label' => 'Uçuşan kalpler animasyonu', 'default' => 1 ),
						array( 'id' => 'adm_redirect', 'type' => 'toggle', 'label' => 'Girişten sonra Cilt Rotası paneli açılsın', 'default' => 1 ),
						array( 'id' => 'adm_howdy', 'type' => 'toggle', 'label' => 'Üst çubukta “Merhaba” yerine karşılama', 'default' => 1 ),
					),
				),
				array(
					'title'  => 'Görünüm',
					'fields' => array(
						array( 'id' => 'adm_skin', 'type' => 'toggle', 'label' => 'Tüm yönetim paneline Cilt Rotası görünümü', 'default' => 1 ),
						array( 'id' => 'adm_login', 'type' => 'toggle', 'label' => 'Markalı giriş ekranı', 'default' => 1 ),
						array( 'id' => 'adm_login_image', 'type' => 'image', 'label' => 'Giriş ekranı görseli', 'default' => cr_default_img( 'calm' ) ),
						array( 'id' => 'adm_login_text', 'type' => 'text', 'label' => 'Giriş ekranı sözü', 'default' => 'Hoş geldin Aslı. Rota seni bekliyordu.' ),
						array( 'id' => 'adm_clean_menu', 'type' => 'toggle', 'label' => 'Sade yönetim menüsü', 'desc' => 'Günlük kullanımda gerekmeyen menüleri gizler (yalnızca yöneticiler görmeye devam eder: Görünüm, Eklentiler, Ayarlar).', 'default' => 0 ),
						array( 'id' => 'live_edit_button', 'type' => 'toggle', 'label' => 'Sitede “✎ Canlı Düzenle” düğmesi', 'default' => 1 ),
					),
				),
			),
		),

		/* ------------------------------------------------------------------ */
		'code' => array(
			'title' => 'Özel Kod',
			'icon'  => 'command',
			'groups' => array(
				array(
					'title'  => 'Kod alanları',
					'fields' => array(
						array( 'id' => 'custom_css', 'type' => 'code', 'label' => 'Özel CSS', 'default' => '' ),
						array( 'id' => 'code_head', 'type' => 'code', 'label' => '&lt;head&gt; içine kod', 'desc' => 'Yalnızca güvendiğiniz kodları ekleyin.', 'default' => '' ),
						array( 'id' => 'code_footer', 'type' => 'code', 'label' => '&lt;/body&gt; öncesine kod', 'default' => '' ),
					),
				),
			),
		),
	);

	return $schema;
}

/**
 * Şemadaki tüm alanlar id => alan olarak.
 *
 * @return array
 */
function cr_schema_fields() {
	static $fields = null;
	if ( null !== $fields ) {
		return $fields;
	}
	$fields = array();
	foreach ( cr_options_schema() as $tab ) {
		foreach ( $tab['groups'] as $group ) {
			foreach ( $group['fields'] as $field ) {
				if ( ! empty( $field['id'] ) ) {
					$fields[ $field['id'] ] = $field;
				}
			}
		}
	}
	return $fields;
}
