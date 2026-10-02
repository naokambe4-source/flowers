<?php
/**
 * Tema panelinin tüm alanları. Varsayılan değerler ve yönetim paneli bu şemadan üretilir.
 *
 * Alan tipleri: text, textarea, url, number, color, toggle, select, image, icon, lines,
 * repeater, products, product_cat, post_cat, page, sections, checkboxes, html.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Ana sayfa bölümleri (varsayılan sıra).
 *
 * @return array<string,string>
 */
function df_home_section_labels() {
	return array(
		'hero'        => 'Hero (tam genişlik)',
		'vitrin'      => 'Vitrin Koleksiyonu (kategori ürünleri + 3 banner)',
		'trust'       => 'Güven şeridi',
		'popular'     => 'Popüler kategoriler (yuvarlak)',
		'categories'  => 'Ana kategoriler',
		'occasions'   => 'Özel günler',
		'banners'     => 'Renkli kategori bannerları',
		'signature'   => 'Signature Collection',
		'editorial'   => 'Tam genişlik editorial banner (Söz & Nişan)',
		'bestsellers' => 'En Çok Sevilenler',
		'duo'         => 'İkili editorial koleksiyon',
		'delivery'    => 'İzmir teslimat',
		'story'       => 'Marka hikayesi',
		'blog'        => 'Çiçek Rehberi (blog)',
		'instagram'   => 'Instagram',
		'newsletter'  => 'Bülten',
		'social'      => 'Instagram · Blog · Sosyal şeridi',
		'districts'   => 'İlçe kısayolları (hangi ilçeye?)',
	);
}

/**
 * Hazır kampanya bannerları (renkli kart). Fotoğrafları eklenene kadar sitede görünmezler.
 *
 * @return array
 */
function df_campaign_banners() {
	$b = function ( $title, $text, $icon, $bg, $fg, $ac, $font, $size, $url, $badge = '' ) {
		return array( 'mode' => 'card', 'title' => $title, 'text' => $text, 'pill' => 'AYNI GÜN TESLİMAT', 'badge' => $badge, 'icon' => $icon, 'font' => $font, 'size' => $size, 'bg' => $bg, 'color' => $fg, 'accent' => $ac, 'url' => $url, 'image' => '', 'image_mobile' => '' );
	};
	return array(
		$b( "SEVGİLİYE\nÇİÇEK", 'AŞKINIZI EN GÜZEL ÇİÇEKLERLE ANLATIN…', 'heart', '#F8DCDD', '#A5202F', '#A5202F', 'sans', 'l', '/urun-kategori/ozel-gunler/sevgiliye/' ),
		$b( "SÖZ/NİŞAN/DÜĞÜN\nÇİÇEKLERİ", 'EN ÖZEL ANLARINIZI, EN GÜZEL ÇİÇEKLERLE TAÇLANDIRIN.', 'rings', '#F7EFEA', '#7C4A5A', '#8C2E40', 'serif', 's', '/urun-kategori/soz-nisan/' ),
		$b( "AÇILIŞ\nÇİÇEKLERİ", 'YENİ BAŞLANGIÇLAR İÇİN EN GÜZEL TEBRİKLER', 'bow', '#F8EFE5', '#9A2B2B', '#9A2B2B', 'serif', 'm', '/urun-kategori/ozel-gunler/acilis/' ),
		$b( "EV HEDİYESİ\nÇİÇEKLERİ", 'SEVDİKLERİNİZE EN GÜZEL HEDİYE', 'house', '#DDE4D3', '#3D4A32', '#93A47C', 'serif', 'm', '/urun-kategori/ozel-gunler/ev-hediyesi/' ),
		$b( "ÖZÜR\nÇİÇEKLERİ", 'KALPTEN BİR ÖZÜR, EN GÜZEL ÇİÇEKLERLE ANLATILIR.', 'heart-mend', '#E4D9EF', '#4A2D62', '#7B5B9B', 'serif', 'l', '/urun-kategori/ozel-gunler/ozur/' ),
		$b( "GEÇMİŞ OLSUN\nÇİÇEKLERİ", '', 'heart-plus', '#F8EDBA', '#6E6A2E', '#6E6A2E', 'sans', 'm', '/urun-kategori/ozel-gunler/gecmis-olsun/' ),
		$b( "ANNELER GÜNÜ\nÇİÇEKLERİ", '', 'mother', '#D7E6F3', '#2F5F8F', '#2F5F8F', 'sans', 'm', '/urun-kategori/ozel-gunler/anneler-gunu/' ),
		$b( "MEVSİM\nÇİÇEKLERİ", 'DOĞANIN EN GÜZEL RENKLERİ, HER MEVSİM TAZE ÇİÇEKLER', 'flower', '#FBDDC8', '#B35E35', '#B35E35', 'sans', 'l', '/urun-kategori/mevsim-cicekleri/' ),
		$b( "SAKSI\nÇİÇEKLERİ", '', 'pot', '#D6E2E6', '#2F4538', '#2F4538', 'sans', 'l', '/urun-kategori/saksi-cicekleri/' ),
		$b( "İNDİRİMLİ\nÇİÇEKLER", '', 'tag', '#F9D5DE', '#C0275E', '#C0275E', 'sans', 'l', '/magaza/?orderby=price', "SEÇİLİ ÜRÜNLERDE\n%20" ),
	);
}

/**
 * Varsayılan teslimat bölgeleri (Şehir | Bölge | Ücret).
 *
 * @return string
 */
function df_default_districts() {
	$free = array( 'ALSANCAK', 'BASMANE', 'ÇANKAYA', 'KAHRAMANLAR', 'KARATAŞ', 'KEMERALTI', 'KONAK', 'KORDON', 'PASAPORT' );
	$paid = array(
		'BAYRAKLI ŞEHİR HASTANESİ' => 300, 'URLA' => 1500, 'ALAÇATI' => 2000, 'ÇEŞME' => 2000, 'GÜZELBAHÇE' => 350,
		'GAZİEMİR EVKA 7' => 250, 'BALÇOVA' => 100, 'BUCA' => 100, 'ÇINARLI' => 100, 'ÇAMDİBİ' => 100, 'ESENDERE' => 100,
		'ESENYALI' => 100, 'EVKA 1' => 100, 'FUAR ALANI' => 150, 'GAZİEMİR' => 125, 'GÖZTEPE' => 100, 'GÜZELYALI' => 100,
		'HATAY' => 100, 'HAVALİMANI' => 200, 'İNCİRALTI' => 100, 'GÜLTEPE' => 100, 'YENİŞEHİR' => 100, 'YEŞİLYURT' => 100,
		'KARABAĞLAR' => 100, 'KAYNAKLAR' => 200, 'KÜÇÜKYALI' => 100, 'MANAVKUYU' => 100, 'NARLIDERE' => 100, 'POLİGON' => 100,
		'SAHİLEVLERİ' => 100, 'SARNIÇ' => 150, 'ŞİRİNYER' => 100, 'ÜÇKUYULAR' => 100, 'ÜÇYOL' => 100, 'YEŞİLOVA' => 100,
		'KARŞIYAKA' => 100, 'BAYRAKLI' => 100, 'TURAN' => 100, 'ŞEMİKLER' => 100, 'ÖRNEKKÖY' => 100, 'MAVİŞEHİR' => 150,
		'LİMONTEPE' => 200, 'PINARBAŞI' => 150, 'ALTINDAĞ' => 125, 'IŞIKKENT' => 150, 'GÜMÜŞPALA' => 100, 'EVKA 4' => 150,
		'EVKA 2' => 300, 'EVKA 3' => 125, 'ÇİĞLİ' => 200, 'BOSTANLI' => 100, 'BORNOVA' => 100, 'AOSB ÇİĞLİ' => 200,
		'ÇİĞLİ ATAKENT' => 200, 'SEFERİHİSAR' => 1500, 'SASALI' => 400,
	);
	$lines = array();
	foreach ( $free as $d ) {
		$lines[] = 'İzmir | ' . $d . ' | 0';
	}
	foreach ( $paid as $d => $fee ) {
		$lines[] = 'İzmir | ' . $d . ' | ' . $fee;
	}
	return implode( "\n", $lines );
}

/**
 * Varsayılan kart notu şablonları (Kategori | Mesaj).
 *
 * @return string
 */
function df_default_note_templates() {
	return implode(
		"\n",
		array(
			'Anneler Günü | Hayatımın en güzel çiçeği sensin anneciğim. Anneler Günün kutlu olsun.',
			'Anneler Günü | Bana verdiğin tüm sevgi için teşekkür ederim. İyi ki varsın anne.',
			'Sevgililer Günü | Seninle geçen her gün, en güzel çiçeklerden daha değerli. Sevgililer Günün kutlu olsun.',
			'Sevgililer Günü | Kalbimin en güzel yerinde sen varsın. Seni seviyorum.',
			'Doğum Günü | Yeni yaşın sana sağlık, mutluluk ve bol bol güzel an getirsin. Nice mutlu yıllara!',
			'Doğum Günü | İyi ki doğdun! Bu yıl hayallerindeki her şey çiçek açsın.',
			'Geçmiş Olsun | Çok geçmiş olsun, en kısa zamanda sağlığına kavuşmanı dilerim.',
			'Geçmiş Olsun | Bu çiçekler sana iyi gelsin, seni yeniden sağlıkla görmek dileğiyle.',
			'Öğretmenler Günü | Emekleriniz ve sabrınız için teşekkürler. Öğretmenler Gününüz kutlu olsun.',
			'Teşekkür | Her şey için içtenlikle teşekkür ederim.',
			'Teşekkür | Desteğin ve güzel kalbin için teşekkürler.',
			'Özür | Seni kırdıysam özür dilerim. Bu çiçekler kalbimin sesi olsun.',
			'Yıl Dönümü | Birlikte nice mutlu yıllara. Yıl dönümümüz kutlu olsun.',
			'Tebrik & Yeni İş | Yeni başlangıcın hayırlı olsun, başarıların daim olsun.',
			'Söz & Nişan | Mutluluğunuz daim olsun, bir ömür boyu mutluluklar dileriz.',
			'Yeni Doğan | Minik mucizeniz hayırlı olsun, sağlıkla büyüsün.',
		)
	);
}

/**
 * Panel şeması.
 *
 * @return array
 */
function df_options_schema() {
	static $schema = null;
	if ( null !== $schema ) {
		return $schema;
	}

	$link_fields = function ( $prefix, $btn_default = '', $url_default = '' ) {
		return array(
			array( 'id' => $prefix . '_btn', 'type' => 'text', 'label' => 'Buton metni', 'default' => $btn_default, 'half' => true ),
			array( 'id' => $prefix . '_url', 'type' => 'url', 'label' => 'Buton bağlantısı', 'default' => $url_default, 'half' => true ),
		);
	};

	$schema = array(

		/* ------------------------------------------------------------------ */
		'appearance' => array(
			'title'  => 'Görünüm & Marka',
			'icon'   => 'dashicons-admin-appearance',
			'groups' => array(
				array(
					'title'  => 'Logo',
					'fields' => array(
						array( 'id' => 'logo_image', 'type' => 'image', 'label' => 'Logo görseli', 'desc' => 'Boş bırakılırsa aşağıdaki metin logo kullanılır. SVG / şeffaf PNG önerilir.' ),
						array( 'id' => 'logo_width', 'type' => 'number', 'label' => 'Logo genişliği (px, masaüstü)', 'default' => 220, 'min' => 80, 'max' => 420, 'half' => true ),
						array( 'id' => 'logo_width_mobile', 'type' => 'number', 'label' => 'Logo genişliği (px, mobil)', 'default' => 150, 'min' => 60, 'max' => 300, 'half' => true ),
						array( 'id' => 'logo_text', 'type' => 'text', 'label' => 'Metin logo', 'default' => 'DERİN FLOWERS', 'half' => true ),
						array( 'id' => 'logo_tagline', 'type' => 'text', 'label' => 'Logo alt satırı', 'default' => 'EXCLUSIVE FLORAL DESIGN', 'half' => true ),
					),
				),
				array(
					'title'  => 'Renkler',
					'desc'   => 'Tüm sitedeki renkler bu paletten türetilir. Premium, sıcak ve sakin tonlar önerilir.',
					'fields' => array(
						array( 'id' => 'color_bg', 'type' => 'color', 'label' => 'Sayfa arka planı', 'default' => '#FFFFFF', 'third' => true ),
						array( 'id' => 'color_ivory', 'type' => 'color', 'label' => 'Ivory / yumuşak zemin', 'default' => '#FAF7F2', 'third' => true ),
						array( 'id' => 'color_sand', 'type' => 'color', 'label' => 'Vurgulu bölüm zemini', 'default' => '#F7F1EB', 'third' => true ),
						array( 'id' => 'color_text', 'type' => 'color', 'label' => 'Metin', 'default' => '#2B2522', 'third' => true ),
						array( 'id' => 'color_muted', 'type' => 'color', 'label' => 'İkincil metin', 'default' => '#7A6E68', 'third' => true ),
						array( 'id' => 'color_line', 'type' => 'color', 'label' => 'Çizgi / border', 'default' => '#E9E0D8', 'third' => true ),
						array( 'id' => 'color_accent', 'type' => 'color', 'label' => 'Vurgu rengi (buton, link)', 'default' => '#8E5E52', 'third' => true ),
						array( 'id' => 'color_accent_dark', 'type' => 'color', 'label' => 'Vurgu koyu (hover)', 'default' => '#6F463C', 'third' => true ),
						array( 'id' => 'color_rose', 'type' => 'color', 'label' => 'Soft rose (bülten)', 'default' => '#F3E7E1', 'third' => true ),
						array( 'id' => 'color_dark', 'type' => 'color', 'label' => 'Koyu buton zemini', 'default' => '#2B2522', 'third' => true ),
						array( 'id' => 'color_sale', 'type' => 'color', 'label' => 'İndirim etiketi', 'default' => '#A2574A', 'third' => true ),
						array( 'id' => 'color_success', 'type' => 'color', 'label' => 'Başarı / onay', 'default' => '#5E7B61', 'third' => true ),
					),
				),
				array(
					'title'  => 'Tipografi',
					'fields' => array(
						array(
							'id'      => 'font_heading',
							'type'    => 'select',
							'label'   => 'Başlık fontu (serif)',
							'default' => 'Cormorant Garamond',
							'half'    => true,
							'options' => array(
								'Cormorant Garamond' => 'Cormorant Garamond',
								'Playfair Display'   => 'Playfair Display',
								'Bodoni Moda'        => 'Bodoni Moda',
								'Marcellus'          => 'Marcellus',
								'Gilda Display'      => 'Gilda Display',
								'Lora'               => 'Lora',
							),
						),
						array(
							'id'      => 'font_body',
							'type'    => 'select',
							'label'   => 'Metin fontu',
							'default' => 'Jost',
							'half'    => true,
							'options' => array(
								'Jost'       => 'Jost',
								'Manrope'    => 'Manrope',
								'DM Sans'    => 'DM Sans',
								'Inter'      => 'Inter',
								'Montserrat' => 'Montserrat',
								'Nunito Sans'=> 'Nunito Sans',
							),
						),
						array( 'id' => 'font_script', 'type' => 'select', 'label' => 'El yazısı fontu (hero notu vb.)', 'default' => 'Great Vibes', 'half' => true, 'options' => array( 'Great Vibes' => 'Great Vibes', 'Allura' => 'Allura', 'Parisienne' => 'Parisienne', 'Pinyon Script' => 'Pinyon Script', '' => 'Kullanma' ) ),
						array( 'id' => 'font_scale', 'type' => 'select', 'label' => 'Başlık ölçeği', 'default' => '1', 'half' => true, 'options' => array( '0.9' => 'Küçük', '1' => 'Standart', '1.1' => 'Büyük' ) ),
					),
				),
				array(
					'title'  => 'Düzen',
					'fields' => array(
						array( 'id' => 'container_width', 'type' => 'number', 'label' => 'İçerik genişliği (px)', 'default' => 1440, 'min' => 1080, 'max' => 1800, 'half' => true ),
						array( 'id' => 'btn_radius', 'type' => 'number', 'label' => 'Buton köşe yuvarlaklığı (px)', 'default' => 0, 'min' => 0, 'max' => 40, 'half' => true ),
						array( 'id' => 'card_radius', 'type' => 'number', 'label' => 'Kart köşe yuvarlaklığı (px)', 'default' => 2, 'min' => 0, 'max' => 24, 'half' => true ),
						array( 'id' => 'section_space', 'type' => 'number', 'label' => 'Bölüm dikey boşluğu (px, masaüstü — sıkı görünüm için 56)', 'default' => 120, 'min' => 32, 'max' => 180, 'half' => true ),
					),
				),
			),
		),

		/* ------------------------------------------------------------------ */
		'header' => array(
			'title'  => 'Header',
			'icon'   => 'dashicons-align-wide',
			'groups' => array(
				array(
					'title'  => 'Üst bilgi bandı',
					'fields' => array(
						array( 'id' => 'topbar_on', 'type' => 'toggle', 'label' => 'Üst bandı göster', 'default' => 1 ),
						array( 'id' => 'topbar_left', 'type' => 'text', 'label' => 'Sol metin', 'default' => "İzmir'in özel anlarına özenle hazırlanan çiçekler", 'half' => true ),
						array( 'id' => 'topbar_right', 'type' => 'text', 'label' => 'Sağ metin', 'default' => 'Pazartesi - Cumartesi 09.00 - 19.00', 'half' => true ),
						array( 'id' => 'topbar_link', 'type' => 'url', 'label' => 'Sol metin bağlantısı (opsiyonel)', 'half' => true ),
						array( 'id' => 'topbar_bg', 'type' => 'color', 'label' => 'Band zemini', 'default' => '#F5EEE8', 'half' => true ),
						array( 'id' => 'topbar_fg', 'type' => 'color', 'label' => 'Band yazı rengi (boşsa metin rengi)', 'default' => '', 'half' => true ),
					),
				),
				array(
					'title'  => 'Ana header',
					'fields' => array(
						array( 'id' => 'header_layout', 'type' => 'select', 'label' => 'Logo konumu', 'default' => 'center', 'half' => true, 'options' => array( 'center' => 'Ortada (önerilen)', 'left' => 'Solda' ) ),
						array( 'id' => 'header_bg', 'type' => 'color', 'label' => 'Header zemini', 'default' => '#FFFFFF', 'half' => true ),
						array( 'id' => 'header_sticky', 'type' => 'toggle', 'label' => 'Kaydırınca header sabit kalsın', 'default' => 1, 'half' => true ),
						array( 'id' => 'header_cat_btn', 'type' => 'toggle', 'label' => 'Solda "Kategoriler" menüsü (ikonlu)', 'default' => 1, 'half' => true ),
						array( 'id' => 'header_search_bar', 'type' => 'toggle', 'label' => 'Solda arama kutusu (ürün adı veya kodu)', 'default' => 0, 'half' => true ),
						array( 'id' => 'header_inline_labels', 'type' => 'toggle', 'label' => 'Sağdaki ikon yazıları yanında ("Hesabım", "Sepetim")', 'default' => 0, 'half' => true ),
						array( 'id' => 'logo_icon', 'type' => 'toggle', 'label' => 'Metin logonun üstünde küçük çiçek ikonu', 'default' => 0, 'half' => true ),
						array( 'id' => 'nav_serif', 'type' => 'toggle', 'label' => 'Menü yazıları başlık fontuyla (zarif)', 'default' => 0, 'half' => true ),
						array( 'id' => 'lang_on', 'type' => 'toggle', 'label' => 'Üst bantta dil seçici (TR · EN · …)', 'default' => 0, 'half' => true ),
						array( 'id' => 'lang_codes', 'type' => 'text', 'label' => 'Diller (virgülle; ilk dil sitenin dili)', 'default' => 'TR, EN, RU, AR, DE, FR', 'half' => true, 'desc' => 'Polylang veya WPML kuruluysa onların dilleri kullanılır. Kurulu değilse diğer diller sayfayı Google Çeviri ile açar.' ),
						array( 'id' => 'header_search_ph', 'type' => 'text', 'label' => 'Arama kutusu yazısı', 'default' => 'Çiçek, buket, özel gün ara…', 'half' => true ),
						array( 'id' => 'header_cat_label', 'type' => 'text', 'label' => 'Kategori butonu metni', 'default' => 'Kategoriler', 'half' => true ),
						array( 'id' => 'header_phone_on', 'type' => 'toggle', 'label' => 'Kategori butonu yanında telefon', 'default' => 0, 'half' => true ),
						array( 'id' => 'header_show_search', 'type' => 'toggle', 'label' => 'Arama', 'default' => 1, 'quarter' => true ),
						array( 'id' => 'header_show_account', 'type' => 'toggle', 'label' => 'Hesabım', 'default' => 1, 'quarter' => true ),
						array( 'id' => 'header_show_wishlist', 'type' => 'toggle', 'label' => 'Favoriler', 'default' => 1, 'quarter' => true ),
						array( 'id' => 'header_show_cart', 'type' => 'toggle', 'label' => 'Sepet', 'default' => 1, 'quarter' => true ),
					),
				),
				array(
					'title'  => 'Navigasyon',
					'desc'   => 'Menü içeriğini Görünüm → Menüler ekranından "Ana Menü" konumuna atayın. Her menü öğesine ikon seçebilirsiniz; ürün kategorisi öğeleri kategori ikonunu otomatik kullanır.',
					'fields' => array(
						array( 'id' => 'nav_icons', 'type' => 'toggle', 'label' => 'Menüde ikonları göster', 'default' => 1, 'half' => true ),
						array( 'id' => 'nav_align', 'type' => 'select', 'label' => 'Menü hizası', 'default' => 'center', 'half' => true, 'options' => array( 'center' => 'Ortalı', 'spread' => 'Yayılmış' ) ),
						array( 'id' => 'drawer_title', 'type' => 'text', 'label' => 'Kategori paneli başlığı', 'default' => 'Anınıza uygun çiçekler', 'half' => true ),
						array( 'id' => 'drawer_image', 'type' => 'image', 'label' => 'Kategori paneli alt görseli (opsiyonel)', 'half' => true ),
					),
				),
			),
		),

		/* ------------------------------------------------------------------ */
		'hero' => array(
			'title'  => 'Hero',
			'icon'   => 'dashicons-format-image',
			'groups' => array(
				array(
					'title'  => 'Hero ayarları',
					'desc'   => 'Birden fazla slayt ekleyerek slider oluşturabilirsiniz. Her slayt "Görsel + metin" ya da "Sadece görsel" olabilir. Masaüstü için en az 2400×1200px, mobil için 1080×1500px dikey görsel önerilir.',
					'fields' => array(
						array( 'id' => 'hero_height', 'type' => 'select', 'label' => 'Yükseklik', 'default' => 'tall', 'third' => true, 'options' => array( 'standard' => 'Standart (72vh)', 'tall' => 'Yüksek (78vh)', 'full' => 'Tam ekran' ) ),
						array( 'id' => 'hero_autoplay', 'type' => 'toggle', 'label' => 'Otomatik geçiş', 'default' => 1, 'third' => true ),
						array( 'id' => 'hero_interval', 'type' => 'number', 'label' => 'Geçiş süresi (sn)', 'default' => 7, 'min' => 3, 'max' => 20, 'third' => true ),
						array( 'id' => 'hero_side_on', 'type' => 'toggle', 'label' => 'Hero\'yu küçült, sağına kategori bannerları koy', 'default' => 0, 'half' => true ),
						array(
							'id'          => 'hero_side',
							'type'        => 'repeater',
							'label'       => 'Sağ bannerlar (en fazla 4, önerilen 3)',
							'add_label'   => 'Banner ekle',
							'title_field' => 'title',
							'default'     => array(
								array( 'title' => 'Buketler', 'url' => '/urun-kategori/buketler/' ),
								array( 'title' => 'Güller', 'url' => '/urun-kategori/guller/' ),
								array( 'title' => 'Orkideler', 'url' => '/urun-kategori/orkideler/' ),
							),
							'fields'      => array(
								array( 'id' => 'title', 'type' => 'text', 'label' => 'Başlık', 'third' => true ),
								array( 'id' => 'url', 'type' => 'url', 'label' => 'Bağlantı', 'third' => true ),
								array( 'id' => 'image', 'type' => 'image', 'label' => 'Görsel (sağa yaslı)', 'third' => true ),
								array( 'id' => 'theme', 'type' => 'select', 'label' => 'Ton', 'default' => 'light', 'third' => true, 'options' => array( 'light' => 'Açık zemin, koyu yazı', 'dark' => 'Koyu zemin, beyaz yazı' ) ),
							),
						),
					),
				),
				array(
					'title'  => 'Slaytlar',
					'fields' => array(
						array(
							'id'         => 'hero_slides',
							'type'       => 'repeater',
							'label'      => 'Hero slaytları',
							'add_label'  => 'Slayt ekle',
							'title_field'=> 'title',
							'default'    => array(
								array(
									'mode'      => 'text',
									'eyebrow'   => 'DERİN FLOWERS · İZMİR',
									'title'     => "Hayatın En Güzel\nAnları Çiçeklerle\nHatırlanır.",
									'text'      => "Sevdiklerinize söylemek istediklerinizi,\nözenle hazırlanan çiçeklerle anlatın.",
									'btn1_text' => 'KOLEKSİYONU KEŞFET',
									'btn1_url'  => '/magaza/',
									'btn2_text' => 'AYNI GÜN TESLİMAT',
									'btn2_url'  => '/teslimat-bolgeleri/',
									'align'     => 'left',
									'overlay'   => 'soft',
									'theme'     => 'dark',
									'position'  => 'center center',
								),
							),
							'fields'     => array(
								array( 'id' => 'mode', 'type' => 'select', 'label' => 'Slayt tipi', 'default' => 'text', 'half' => true, 'options' => array( 'text' => 'Görsel + metin', 'image' => 'Sadece görsel (tıklanabilir)' ) ),
								array( 'id' => 'link', 'type' => 'url', 'label' => 'Görsel bağlantısı (sadece görsel modunda)', 'half' => true ),
								array( 'id' => 'image', 'type' => 'image', 'label' => 'Masaüstü görseli', 'half' => true ),
								array( 'id' => 'image_mobile', 'type' => 'image', 'label' => 'Mobil görseli (dikey)', 'half' => true ),
								array( 'id' => 'video', 'type' => 'url', 'label' => 'Arka plan videosu (MP4 URL, opsiyonel)', 'desc' => 'Sessiz ve döngüde oynar. Görsel poster olarak kullanılır.' ),
								array( 'id' => 'eyebrow', 'type' => 'text', 'label' => 'Üst küçük metin', 'half' => true ),
								array( 'id' => 'alt', 'type' => 'text', 'label' => 'Görsel alternatif metni (SEO)', 'half' => true ),
								array( 'id' => 'title', 'type' => 'textarea', 'label' => 'Başlık (H1) — satır atlamak için Enter', 'rows' => 3 ),
								array( 'id' => 'text', 'type' => 'textarea', 'label' => 'Açıklama', 'rows' => 2 ),
								array( 'id' => 'btn1_text', 'type' => 'text', 'label' => '1. buton metni', 'half' => true ),
								array( 'id' => 'btn1_url', 'type' => 'url', 'label' => '1. buton bağlantısı', 'half' => true ),
								array( 'id' => 'btn2_text', 'type' => 'text', 'label' => '2. buton metni', 'half' => true ),
								array( 'id' => 'btn2_url', 'type' => 'url', 'label' => '2. buton bağlantısı', 'half' => true ),
								array( 'id' => 'script', 'type' => 'textarea', 'label' => 'El yazısı not (sağ tarafta, ör. "Çiçeklerle" Enter "daha güzel bir İzmir")', 'rows' => 2 ),
								array( 'id' => 'align', 'type' => 'select', 'label' => 'Metin konumu', 'default' => 'left', 'quarter' => true, 'options' => array( 'left' => 'Sol', 'center' => 'Orta', 'right' => 'Sağ' ) ),
								array( 'id' => 'overlay', 'type' => 'select', 'label' => 'Okunabilirlik gradyanı', 'default' => 'soft', 'quarter' => true, 'options' => array( 'none' => 'Yok', 'soft' => 'Hafif', 'strong' => 'Belirgin' ) ),
								array( 'id' => 'theme', 'type' => 'select', 'label' => 'Metin rengi', 'default' => 'dark', 'quarter' => true, 'options' => array( 'dark' => 'Koyu (açık görseller)', 'light' => 'Açık (koyu görseller)' ) ),
								array( 'id' => 'position', 'type' => 'select', 'label' => 'Görsel odak noktası', 'default' => 'center center', 'quarter' => true, 'options' => array( 'center center' => 'Orta', 'right center' => 'Sağ', 'left center' => 'Sol', 'center top' => 'Üst', 'center bottom' => 'Alt' ) ),
							),
						),
					),
				),
			),
		),

		/* ------------------------------------------------------------------ */
		'home' => array(
			'title'  => 'Ana Sayfa',
			'icon'   => 'dashicons-admin-home',
			'groups' => array(
				array(
					'title'  => 'Bölümler: aç / kapat & sırala',
					'desc'   => 'Bölümleri sürükleyerek sıralayabilir, anahtar ile gizleyebilirsiniz. Önerilen sıra varsayılan olarak gelir.',
					'fields' => array(
						array( 'id' => 'home_sections', 'type' => 'sections' ),
					),
				),
				array(
					'title'   => 'Güven şeridi',
					'section' => 'trust',
					'fields'  => array(
						array(
							'id'          => 'trust_items',
							'type'        => 'repeater',
							'label'       => 'Maddeler (en fazla 4 önerilir)',
							'add_label'   => 'Madde ekle',
							'title_field' => 'title',
							'default'     => array(
								array( 'icon' => 'truck', 'title' => 'Aynı Gün Teslimat', 'text' => 'Seçili İzmir Bölgelerinde' ),
								array( 'icon' => 'hand', 'title' => 'Özenle Hazırlanır', 'text' => 'Her sipariş özel hazırlanır' ),
								array( 'icon' => 'shield', 'title' => 'Güvenli Ödeme', 'text' => '3D Secure ödeme' ),
								array( 'icon' => 'leaf', 'title' => 'Tazelik Garantisi', 'text' => 'Günlük seçilen çiçekler' ),
							),
							'fields'      => array(
								array( 'id' => 'icon', 'type' => 'icon', 'label' => 'İkon', 'third' => true ),
								array( 'id' => 'title', 'type' => 'text', 'label' => 'Başlık', 'third' => true ),
								array( 'id' => 'text', 'type' => 'text', 'label' => 'Alt metin', 'third' => true ),
							),
						),
					),
				),
				array(
					'title'   => 'Popüler kategoriler (yuvarlak)',
					'section' => 'popular',
					'desc'    => 'Kategori seçin; görsel boşsa WooCommerce kategori görseli kullanılır. Hiç seçilmezse görseli olan ilk 8 kategori gösterilir.',
					'fields'  => array(
						array( 'id' => 'pop_title', 'type' => 'text', 'label' => 'Başlık', 'default' => 'Popüler Kategoriler', 'half' => true ),
						array( 'id' => 'pop_align', 'type' => 'select', 'label' => 'Başlık hizası', 'default' => 'left', 'half' => true, 'options' => array( 'left' => 'Sol', 'center' => 'Orta' ) ),
						array(
							'id'          => 'pop_items',
							'type'        => 'repeater',
							'label'       => 'Kategoriler (8 önerilir)',
							'add_label'   => 'Kategori ekle',
							'title_field' => 'title',
							'default'     => array(),
							'fields'      => array(
								array( 'id' => 'cat', 'type' => 'product_cat', 'label' => 'Ürün kategorisi', 'third' => true ),
								array( 'id' => 'image', 'type' => 'image', 'label' => 'Yuvarlak görsel (kare)', 'third' => true ),
								array( 'id' => 'title', 'type' => 'text', 'label' => 'Başlık (boşsa kategori adı)', 'third' => true ),
							),
						),
					),
				),
				array(
					'title'   => 'Ana kategoriler',
					'section' => 'categories',
					'desc'    => 'Kategori seçin. Görsel boş bırakılırsa WooCommerce kategori görseli kullanılır. Hiç kategori seçilmezse en çok ürünü olan 4 kategori gösterilir.',
					'fields'  => array(
						array( 'id' => 'cats_eyebrow', 'type' => 'text', 'label' => 'Üst küçük metin', 'default' => 'ALIŞVERİŞE BAŞLAYIN', 'third' => true ),
						array( 'id' => 'cats_title', 'type' => 'text', 'label' => 'Başlık', 'default' => 'Anınıza Uygun Çiçeği Bulun', 'third' => true ),
						array( 'id' => 'cats_sub', 'type' => 'text', 'label' => 'Alt metin', 'default' => 'Her an için özenle seçilmiş koleksiyonlar.', 'third' => true ),
						array( 'id' => 'cats_style', 'type' => 'select', 'label' => 'Kart stili', 'default' => 'below', 'half' => true, 'options' => array( 'below' => 'Metin görselin altında', 'overlay' => 'Metin görselin üzerinde', 'banner' => 'Yatay banner (metin solda, görsel sağda)' ) ),
						array( 'id' => 'cats_bg', 'type' => 'color', 'label' => 'Yatay banner zemin rengi', 'default' => '#F6E7E4', 'half' => true ),
						array( 'id' => 'cats_cta', 'type' => 'text', 'label' => 'Kart link metni', 'default' => 'KEŞFET', 'half' => true ),
						array(
							'id'          => 'cats_items',
							'type'        => 'repeater',
							'label'       => 'Kategoriler',
							'add_label'   => 'Kategori ekle',
							'title_field' => 'title',
							'default'     => array(
								array( 'cat' => '', 'title' => 'Buketler', 'text' => 'En sevilen tasarımlar' ),
								array( 'cat' => '', 'title' => 'Güller', 'text' => 'Zamansız bir zarafet' ),
								array( 'cat' => '', 'title' => 'Orkideler', 'text' => 'Uzun ömürlü şıklık' ),
								array( 'cat' => '', 'title' => 'Kutuda Çiçekler', 'text' => 'Hediye etmenin en zarif hali' ),
							),
							'fields'      => array(
								array( 'id' => 'cat', 'type' => 'product_cat', 'label' => 'Ürün kategorisi', 'half' => true ),
								array( 'id' => 'image', 'type' => 'image', 'label' => 'Özel görsel (3:4 dikey)', 'half' => true ),
								array( 'id' => 'title', 'type' => 'text', 'label' => 'Başlık (boşsa kategori adı)', 'half' => true ),
								array( 'id' => 'text', 'type' => 'text', 'label' => 'Alt metin', 'half' => true ),
							),
						),
					),
				),
				array(
					'title'   => 'Özel günler',
					'section' => 'occasions',
					'fields'  => array(
						array( 'id' => 'occ_eyebrow', 'type' => 'text', 'label' => 'Üst küçük metin', 'default' => 'ÖZEL GÜNLER', 'half' => true ),
						array( 'id' => 'occ_title', 'type' => 'text', 'label' => 'Başlık', 'default' => 'Her Özel Anın Bir Çiçeği Var', 'half' => true ),
						array( 'id' => 'occ_style', 'type' => 'select', 'label' => 'Görünüm', 'default' => 'overlay', 'half' => true, 'options' => array( 'overlay' => 'Görsel üzerinde metin (geniş banner)', 'card' => 'Kart: metin solda, görsel sağda' ) ),
						array( 'id' => 'occ_btn', 'type' => 'text', 'label' => 'Kart buton metni (kart görünümü)', 'default' => 'KEŞFET', 'half' => true ),
						array( 'id' => 'occ_bg', 'type' => 'color', 'label' => 'Kart zemin rengi (kart görünümü)', 'default' => '#F6E7E4', 'half' => true ),
						array(
							'id'          => 'occ_items',
							'type'        => 'repeater',
							'label'       => 'Bannerlar (3 önerilir, 16:10 görsel)',
							'add_label'   => 'Banner ekle',
							'title_field' => 'title',
							'default'     => array(
								array( 'title' => 'DOĞUM GÜNÜ', 'text' => 'Mutluluğu çiçeklerle çoğaltın.', 'url' => '/urun-kategori/dogum-gunu/' ),
								array( 'title' => 'SEVGİLİYE', 'text' => 'Söylemek istediklerinizi çiçekler anlatsın.', 'url' => '/urun-kategori/sevgiliye/' ),
								array( 'title' => 'TEBRİK & YENİ İŞ', 'text' => 'Yeni başlangıçlara özel.', 'url' => '/urun-kategori/tebrik-yeni-is/' ),
							),
							'fields'      => array(
								array( 'id' => 'image', 'type' => 'image', 'label' => 'Görsel', 'third' => true ),
								array( 'id' => 'title', 'type' => 'text', 'label' => 'Başlık', 'third' => true ),
								array( 'id' => 'text', 'type' => 'text', 'label' => 'Alt metin', 'third' => true ),
								array( 'id' => 'url', 'type' => 'url', 'label' => 'Bağlantı' ),
							),
						),
					),
				),
				array(
					'title'   => 'Renkli kategori bannerları',
					'section' => 'banners',
					'desc'    => 'İki kullanım: 1) Hazır banner görseli yükleyin (metin görselin içindeyse "Sadece görsel" seçin). 2) Renkli kart: fotoğraf solda; ikon, iki satırlı başlık (son satır ayrı renk), alt metin ve "Aynı Gün Teslimat" düğmesi sağda. Görseli olmayan bannerlar sitede gösterilmez. 10 hazır kampanya bannerı için: Araçlar & Kurulum → Hazır kampanya bannerları.',
					'fields'  => array(
						array( 'id' => 'ban_title', 'type' => 'text', 'label' => 'Bölüm başlığı (opsiyonel)', 'default' => '', 'third' => true ),
						array( 'id' => 'ban_cols', 'type' => 'select', 'label' => 'Masaüstü sütun', 'default' => '2', 'third' => true, 'options' => array( '1' => '1', '2' => '2', '3' => '3' ) ),
						array( 'id' => 'ban_radius', 'type' => 'number', 'label' => 'Köşe yuvarlaklığı (px)', 'default' => 18, 'min' => 0, 'max' => 40, 'third' => true ),
						array(
							'id'          => 'ban_items',
							'type'        => 'repeater',
							'label'       => 'Bannerlar',
							'add_label'   => 'Banner ekle',
							'title_field' => 'title',
							'default'     => df_campaign_banners(),
							'fields'      => array(
								array( 'id' => 'mode', 'type' => 'select', 'label' => 'Tip', 'default' => 'card', 'half' => true, 'options' => array( 'card' => 'Renkli kart (fotoğraf + metin)', 'image' => 'Sadece görsel (hazır banner)' ) ),
								array( 'id' => 'url', 'type' => 'url', 'label' => 'Bağlantı', 'half' => true ),
								array( 'id' => 'image', 'type' => 'image', 'label' => 'Görsel (kart: fotoğraf / hazır banner)', 'half' => true ),
								array( 'id' => 'image_mobile', 'type' => 'image', 'label' => 'Mobil görsel (opsiyonel, hazır banner)', 'half' => true ),
								array( 'id' => 'title', 'type' => 'textarea', 'label' => 'Başlık (satır atlamak için Enter)', 'rows' => 2, 'half' => true ),
								array( 'id' => 'text', 'type' => 'text', 'label' => 'Küçük açıklama (opsiyonel)', 'half' => true ),
								array( 'id' => 'pill', 'type' => 'text', 'label' => 'Etiket butonu', 'default' => 'AYNI GÜN TESLİMAT', 'third' => true ),
								array( 'id' => 'badge', 'type' => 'textarea', 'label' => 'Köşe rozeti (ör. "SEÇİLİ ÜRÜNLERDE" Enter "%20")', 'rows' => 2, 'third' => true ),
								array( 'id' => 'icon', 'type' => 'icon', 'label' => 'Başlık üstü ikon', 'third' => true ),
								array( 'id' => 'font', 'type' => 'select', 'label' => 'Başlık yazı tipi', 'default' => 'sans', 'half' => true, 'options' => array( 'sans' => 'Kalın düz (GEÇMİŞ OLSUN tarzı)', 'serif' => 'Tırnaklı zarif (SÖZ / NİŞAN tarzı)' ) ),
								array( 'id' => 'split', 'type' => 'select', 'label' => 'Fotoğraf alanı genişliği', 'default' => '58', 'half' => true, 'options' => array( '50' => 'Yarı yarıya', '58' => 'Fotoğraf biraz geniş', '65' => 'Fotoğraf geniş', '72' => 'Fotoğraf çok geniş' ) ),
								array( 'id' => 'focus', 'type' => 'select', 'label' => 'Fotoğraf hizası (kesilen tarafı göstermek için)', 'default' => 'left', 'half' => true, 'options' => array( 'left' => 'Sola yasla (sol taraf görünür)', 'center' => 'Ortala', 'right' => 'Sağa yasla', 'fit' => 'Tamamını göster (kırpma yok)' ) ),
								array( 'id' => 'size', 'type' => 'select', 'label' => 'Başlık boyutu', 'default' => 'm', 'half' => true, 'options' => array( 's' => 'Küçük (uzun başlıklar)', 'm' => 'Orta', 'l' => 'Büyük (kısa başlıklar)' ) ),
								array( 'id' => 'bg', 'type' => 'color', 'label' => 'Zemin rengi', 'default' => '#F3E7E1', 'third' => true ),
								array( 'id' => 'color', 'type' => 'color', 'label' => 'Yazı / buton rengi', 'default' => '#6F463C', 'third' => true ),
								array( 'id' => 'accent', 'type' => 'color', 'label' => 'Başlığın son satırı rengi (boşsa aynı)', 'default' => '', 'third' => true ),
							),
						),
					),
				),
				array(
					'title'   => 'Signature Collection',
					'section' => 'signature',
					'fields'  => array_merge(
						array(
							array( 'id' => 'sig_title', 'type' => 'text', 'label' => 'Başlık', 'default' => 'Signature Collection', 'half' => true ),
							array( 'id' => 'sig_sub', 'type' => 'text', 'label' => 'Alt metin', 'default' => 'Özel tasarımlar. Unutulmaz anlar.', 'half' => true ),
							array( 'id' => 'sig_source', 'type' => 'select', 'label' => 'Ürün kaynağı', 'default' => 'featured', 'third' => true, 'options' => array( 'manual' => 'Elle seçilen ürünler', 'featured' => 'Öne çıkan ürünler', 'category' => 'Kategoriden', 'newest' => 'En yeniler' ) ),
							array( 'id' => 'sig_cat', 'type' => 'product_cat', 'label' => 'Kategori (kaynak "Kategoriden" ise)', 'third' => true ),
							array( 'id' => 'sig_count', 'type' => 'select', 'label' => 'Ürün sayısı', 'default' => '4', 'third' => true, 'options' => array( '4' => '4 (tek satır)', '5' => '5 (tek satır, dar kart)', '8' => '8 (iki satır)', '10' => '10 (iki satır, 5\'li)' ) ),
							array( 'id' => 'sig_products', 'type' => 'products', 'label' => 'Elle seçilen ürünler (sıralı)' ),
						),
						$link_fields( 'sig', 'TÜM KOLEKSİYON', '/magaza/' )
					),
				),
				array(
					'title'   => 'Tam genişlik editorial banner',
					'section' => 'editorial',
					'fields'  => array_merge(
						array(
							array( 'id' => 'ed_image', 'type' => 'image', 'label' => 'Masaüstü görseli (lifestyle, geniş)', 'half' => true ),
							array( 'id' => 'ed_image_mobile', 'type' => 'image', 'label' => 'Mobil görseli', 'half' => true ),
							array( 'id' => 'ed_eyebrow', 'type' => 'text', 'label' => 'Üst küçük metin', 'default' => 'Söz & Nişan', 'third' => true ),
							array( 'id' => 'ed_align', 'type' => 'select', 'label' => 'Metin tarafı', 'default' => 'left', 'third' => true, 'options' => array( 'left' => 'Sol', 'center' => 'Orta', 'right' => 'Sağ' ) ),
							array( 'id' => 'ed_theme', 'type' => 'select', 'label' => 'Metin rengi', 'default' => 'dark', 'third' => true, 'options' => array( 'dark' => 'Koyu', 'light' => 'Açık' ) ),
							array( 'id' => 'ed_title', 'type' => 'textarea', 'label' => 'Başlık', 'default' => "En özel başlangıçları\nçiçeklerle anlatın.", 'rows' => 2 ),
							array( 'id' => 'ed_text', 'type' => 'textarea', 'label' => 'Açıklama', 'default' => 'Söz ve nişan törenleriniz için size özel tasarlanan çiçek aranjmanları, çikolata sunumları ve masa süslemeleri.', 'rows' => 2 ),
						),
						$link_fields( 'ed', 'KOLEKSİYONU KEŞFET', '/urun-kategori/soz-nisan/' )
					),
				),
				array(
					'title'   => 'En Çok Sevilenler',
					'section' => 'bestsellers',
					'fields'  => array_merge(
						array(
							array( 'id' => 'best_title', 'type' => 'text', 'label' => 'Başlık', 'default' => 'En Çok Sevilenler', 'half' => true ),
							array( 'id' => 'best_sub', 'type' => 'text', 'label' => 'Alt metin', 'default' => "Derin Flowers'ın en çok tercih edilen tasarımları.", 'half' => true ),
							array( 'id' => 'best_source', 'type' => 'select', 'label' => 'Ürün kaynağı', 'default' => 'bestsellers', 'third' => true, 'options' => array( 'bestsellers' => 'En çok satanlar', 'manual' => 'Elle seçilen ürünler', 'category' => 'Kategoriden', 'onsale' => 'İndirimdekiler', 'rating' => 'En yüksek puanlılar' ) ),
							array( 'id' => 'best_cat', 'type' => 'product_cat', 'label' => 'Kategori', 'third' => true ),
							array( 'id' => 'best_count', 'type' => 'select', 'label' => 'Ürün sayısı', 'default' => '4', 'third' => true, 'options' => array( '4' => '4', '8' => '8' ) ),
							array( 'id' => 'best_products', 'type' => 'products', 'label' => 'Elle seçilen ürünler' ),
							array( 'id' => 'best_exclude_sig', 'type' => 'toggle', 'label' => "Signature Collection'daki ürünleri tekrar gösterme", 'default' => 1 ),
						),
						$link_fields( 'best', 'TÜMÜNÜ GÖR', '/magaza/?orderby=popularity' )
					),
				),
				array(
					'title'   => 'İkili editorial koleksiyon',
					'section' => 'duo',
					'desc'    => 'Sol kart geniş (%58), sağ kart dar (%42) — asimetrik yerleşim.',
					'fields'  => array(
						array( 'id' => 'duo1_image', 'type' => 'image', 'label' => 'Sol kart görseli', 'half' => true ),
						array( 'id' => 'duo2_image', 'type' => 'image', 'label' => 'Sağ kart görseli', 'half' => true ),
						array( 'id' => 'duo1_eyebrow', 'type' => 'text', 'label' => 'Sol üst metin', 'default' => 'KOLEKSİYON', 'half' => true ),
						array( 'id' => 'duo2_eyebrow', 'type' => 'text', 'label' => 'Sağ üst metin', 'default' => 'KOLEKSİYON', 'half' => true ),
						array( 'id' => 'duo1_title', 'type' => 'text', 'label' => 'Sol başlık', 'default' => 'Söz Çiçekleri', 'half' => true ),
						array( 'id' => 'duo2_title', 'type' => 'text', 'label' => 'Sağ başlık', 'default' => 'Orkide Koleksiyonu', 'half' => true ),
						array( 'id' => 'duo1_text', 'type' => 'text', 'label' => 'Sol açıklama', 'default' => 'Bir ömrün ilk adımına yakışan zarafet.', 'half' => true ),
						array( 'id' => 'duo2_text', 'type' => 'text', 'label' => 'Sağ açıklama', 'default' => 'Uzun ömürlü, sade ve asil.', 'half' => true ),
						array( 'id' => 'duo1_btn', 'type' => 'text', 'label' => 'Sol buton', 'default' => 'KEŞFET', 'half' => true ),
						array( 'id' => 'duo2_btn', 'type' => 'text', 'label' => 'Sağ buton', 'default' => 'KEŞFET', 'half' => true ),
						array( 'id' => 'duo1_url', 'type' => 'url', 'label' => 'Sol bağlantı', 'default' => '/urun-kategori/soz-nisan/', 'half' => true ),
						array( 'id' => 'duo2_url', 'type' => 'url', 'label' => 'Sağ bağlantı', 'default' => '/urun-kategori/orkideler/', 'half' => true ),
					),
				),
				array(
					'title'   => 'İzmir teslimat',
					'section' => 'delivery',
					'fields'  => array_merge(
						array(
							array( 'id' => 'del_image', 'type' => 'image', 'label' => 'Görsel (çiçek hazırlama / teslimat)', 'half' => true ),
							array( 'id' => 'del_eyebrow', 'type' => 'text', 'label' => 'Üst küçük metin', 'default' => 'TESLİMAT', 'half' => true ),
							array( 'id' => 'del_title', 'type' => 'text', 'label' => 'Başlık', 'default' => "İzmir'in Her Anına Çiçek" ),
							array( 'id' => 'del_text', 'type' => 'textarea', 'label' => 'Metin', 'default' => "Alsancak'tan İzmir'in seçili bölgelerine,\nözenle hazırlanan çiçekleri güvenle ulaştırıyoruz.", 'rows' => 3 ),
							array( 'id' => 'del_points', 'type' => 'lines', 'label' => 'Kısa bilgiler (her satır bir madde, en fazla 3)', 'default' => "Aynı gün teslimat için son sipariş 16.00\nKonak ve Alsancak çevresine ücretsiz teslimat\nTeslimat anında fotoğraflı bilgilendirme", 'rows' => 3 ),
						),
						$link_fields( 'del', 'TESLİMAT BÖLGELERİNİ İNCELE', '/teslimat-bolgeleri/' )
					),
				),
				array(
					'title'   => 'Marka hikayesi',
					'section' => 'story',
					'fields'  => array_merge(
						array(
							array( 'id' => 'story_image', 'type' => 'image', 'label' => 'Ana görsel (çiçek hazırlanırken close-up)', 'half' => true ),
							array( 'id' => 'story_image2', 'type' => 'image', 'label' => 'İkinci küçük görsel (opsiyonel)', 'half' => true ),
							array( 'id' => 'story_eyebrow', 'type' => 'text', 'label' => 'Üst küçük metin', 'default' => 'DERİN FLOWERS', 'half' => true ),
							array( 'id' => 'story_title', 'type' => 'text', 'label' => 'Başlık', 'default' => 'Çiçekten Daha Fazlası', 'half' => true ),
							array( 'id' => 'story_text', 'type' => 'textarea', 'label' => 'Metin', 'default' => "Her sipariş, özel bir anın parçası olarak\nözenle hazırlanır.", 'rows' => 3 ),
							array( 'id' => 'story_signature', 'type' => 'text', 'label' => 'İmza satırı (opsiyonel)', 'default' => "Alsancak'taki atölyemizden sevgiyle" ),
						),
						$link_fields( 'story', 'DEVAMINI KEŞFET', '/hakkimizda/' )
					),
				),
				array(
					'title'   => 'Çiçek Rehberi (blog)',
					'section' => 'blog',
					'fields'  => array(
						array( 'id' => 'blog_title', 'type' => 'text', 'label' => 'Başlık', 'default' => 'Çiçek Rehberi', 'third' => true ),
						array( 'id' => 'blog_sub', 'type' => 'text', 'label' => 'Alt metin', 'default' => 'Çiçek seçimi, bakım ve özel gün ipuçları.', 'third' => true ),
						array( 'id' => 'blog_cat', 'type' => 'post_cat', 'label' => 'Kategori (boşsa en yeniler)', 'third' => true ),
					),
				),
				array(
					'title'   => 'Instagram',
					'section' => 'instagram',
					'fields'  => array(
						array( 'id' => 'ig_handle', 'type' => 'text', 'label' => 'Kullanıcı adı', 'default' => '@derinflowers', 'third' => true ),
						array( 'id' => 'ig_sub', 'type' => 'text', 'label' => 'Alt metin', 'default' => 'Çiçeklerden ilham alın.', 'third' => true ),
						array( 'id' => 'ig_url', 'type' => 'url', 'label' => 'Profil bağlantısı', 'default' => 'https://www.instagram.com/derinflowers/', 'third' => true ),
						array(
							'id'        => 'ig_images',
							'type'      => 'repeater',
							'label'     => 'Görseller (6 kare görsel önerilir)',
							'add_label' => 'Görsel ekle',
							'default'   => array(),
							'fields'    => array(
								array( 'id' => 'image', 'type' => 'image', 'label' => 'Görsel', 'half' => true ),
								array( 'id' => 'link', 'type' => 'url', 'label' => 'Gönderi bağlantısı', 'half' => true ),
							),
						),
					),
				),
				array(
					'title'   => 'Bülten',
					'section' => 'newsletter',
					'fields'  => array(
						array( 'id' => 'nl_title', 'type' => 'text', 'label' => 'Başlık', 'default' => 'Güzel Anlardan Haberdar Olun', 'half' => true ),
						array( 'id' => 'nl_text', 'type' => 'text', 'label' => 'Metin', 'default' => 'Yeni koleksiyonlar ve özel dönemlerden haberdar olun.', 'half' => true ),
						array( 'id' => 'nl_placeholder', 'type' => 'text', 'label' => 'E-posta alan metni', 'default' => 'E-posta adresiniz', 'third' => true ),
						array( 'id' => 'nl_btn', 'type' => 'text', 'label' => 'Buton', 'default' => 'Kaydol', 'third' => true ),
						array( 'id' => 'nl_action', 'type' => 'url', 'label' => 'Harici form adresi (Mailchimp vb., opsiyonel)', 'third' => true, 'desc' => 'Boş bırakılırsa aboneler WordPress içinde (Derin Flowers → Aboneler) saklanır.' ),
						array( 'id' => 'nl_consent', 'type' => 'text', 'label' => 'Onay metni', 'default' => 'Kaydolarak KVKK Aydınlatma Metni\'ni okuduğumu kabul ederim.' ),
					),
				),
				array(
					'title'   => 'Vitrin Koleksiyonu',
					'section' => 'vitrin',
					'desc'    => 'Seçtiğiniz kategorinin ürünleri 4\'lü satırlarla gösterilir; ilk satırdan sonra üç tanıtım bannerı gelir. Kartlarda "İncele" düğmesi bulunur (ürün sayfasına gider).',
					'fields'  => array(
						array( 'id' => 'vit_title', 'type' => 'text', 'label' => 'Başlık', 'default' => 'Vitrin Koleksiyonu', 'third' => true ),
						array( 'id' => 'vit_cat', 'type' => 'product_cat', 'label' => 'Vitrin kategorisi', 'third' => true ),
						array( 'id' => 'vit_count', 'type' => 'select', 'label' => 'Ürün sayısı', 'default' => '12', 'third' => true, 'options' => array( '4' => '4', '8' => '8', '12' => '12', '16' => '16', '20' => '20' ) ),
						array( 'id' => 'vit_link', 'type' => 'text', 'label' => 'Sağ bağlantı yazısı', 'default' => 'Tüm ürünleri gör', 'third' => true ),
						array( 'id' => 'vit_promos_on', 'type' => 'toggle', 'label' => 'Ürünlerin arasına banner koy', 'default' => 1, 'third' => true ),
						array( 'id' => 'vit_cols', 'type' => 'select', 'label' => 'Masaüstü sütun (az sütun = büyük görsel)', 'default' => '4', 'third' => true, 'options' => array( '3' => '3', '4' => '4', '5' => '5' ) ),
						array( 'id' => 'vit_ratio', 'type' => 'select', 'label' => 'Ürün görsel oranı', 'default' => '1-1', 'third' => true, 'options' => array( '1-1' => 'Kare (1:1)', '4-5' => 'Dikey (4:5)', '3-4' => 'Uzun dikey (3:4)', 'fit' => 'Tamamını göster (kırpma yok)' ) ),
						array( 'id' => 'card_view_style', 'type' => 'select', 'label' => '"İncele" düğmesi', 'default' => 'solid', 'third' => true, 'options' => array( 'solid' => 'Dolu', 'outline' => 'Çerçeveli (ince)' ) ),
						array(
							'id'          => 'vit_promos',
							'type'        => 'repeater',
							'label'       => 'Ürünlerin arasındaki bannerlar (önerilen 3)',
							'add_label'   => 'Banner ekle',
							'title_field' => 'title',
							'default'     => array(
								array( 'title' => "Özel\nKoleksiyon", 'btn' => 'Keşfet', 'url' => '/magaza/' ),
								array( 'title' => "Signature\nKoleksiyon", 'btn' => 'Keşfet', 'url' => '/urun-kategori/koleksiyonlar/' ),
								array( 'title' => 'Söz Çiçekleri', 'btn' => 'Keşfet', 'url' => '/urun-kategori/soz-nisan/' ),
							),
							'fields'      => array(
								array( 'id' => 'title', 'type' => 'textarea', 'label' => 'Başlık', 'rows' => 2, 'half' => true ),
								array( 'id' => 'image', 'type' => 'image', 'label' => 'Görsel (çiçek sağda olacak şekilde)', 'half' => true ),
								array( 'id' => 'btn', 'type' => 'text', 'label' => 'Buton', 'default' => 'Keşfet', 'half' => true ),
								array( 'id' => 'url', 'type' => 'url', 'label' => 'Bağlantı', 'half' => true ),
							),
						),
					),
				),
				array(
					'title'   => 'İlçe kısayolları',
					'section' => 'districts',
					'fields'  => array(
						array( 'id' => 'dist_title', 'type' => 'text', 'label' => 'Başlık', 'default' => 'İzmir\'de hangi ilçeye çiçek göndermek istersiniz?', 'half' => true ),
						array( 'id' => 'dist_url', 'type' => 'url', 'label' => 'İlçelere tıklanınca gidilecek sayfa (boşsa mağaza)', 'half' => true ),
						array( 'id' => 'dist_items', 'type' => 'textarea', 'label' => 'İlçeler (virgülle ya da satır satır)', 'default' => 'Konak, Karşıyaka, Bornova, Bayraklı, Buca, Balçova, Narlıdere, Gaziemir, Çiğli, Güzelbahçe', 'rows' => 3 ),
						array( 'id' => 'dist_note', 'type' => 'text', 'label' => 'Alt not (opsiyonel)', 'default' => '' ),
					),
				),
				array(
					'title'   => 'Instagram · Blog · Sosyal şeridi',
					'section' => 'social',
					'desc'    => 'Footer\'ın hemen üstünde üç kart: Instagram, Blog ve sosyal medya ikonları. Bağlantılar Footer & İletişim → Sosyal medya alanlarından gelir.',
					'fields'  => array(
						array( 'id' => 'soc_style', 'type' => 'select', 'label' => 'Görünüm', 'default' => 'cards', 'half' => true, 'options' => array( 'cards' => 'Üç küçük kart', 'image' => 'İki büyük görselli banner (Instagram + Blog)' ) ),
						array( 'id' => 'soc_blog_btn', 'type' => 'text', 'label' => 'Blog buton yazısı (görselli)', 'default' => 'Yazıları keşfet', 'half' => true ),
						array( 'id' => 'soc_ig_dark', 'type' => 'toggle', 'label' => 'Instagram bannerı koyu (beyaz yazı)', 'default' => 0, 'half' => true ),
						array( 'id' => 'price_tl', 'type' => 'toggle', 'label' => 'Fiyatlarda ₺ yerine "TL" yaz', 'default' => 0, 'half' => true ),
						array( 'id' => 'soc_ig_image', 'type' => 'image', 'label' => 'Instagram banner görseli (görselli)', 'half' => true ),
						array( 'id' => 'soc_blog_image', 'type' => 'image', 'label' => 'Blog banner görseli (görselli)', 'half' => true ),
						array( 'id' => 'soc_ig_title', 'type' => 'text', 'label' => 'Instagram başlığı', 'default' => 'Instagram', 'third' => true ),
						array( 'id' => 'soc_ig_text', 'type' => 'text', 'label' => 'Instagram metni', 'default' => 'En özel anlarımız', 'third' => true ),
						array( 'id' => 'soc_bg', 'type' => 'color', 'label' => 'Kart zemin rengi', 'default' => '#F6E7E4', 'third' => true ),
						array( 'id' => 'soc_blog_title', 'type' => 'text', 'label' => 'Blog başlığı', 'default' => 'Blog', 'third' => true ),
						array( 'id' => 'soc_blog_text', 'type' => 'text', 'label' => 'Blog metni', 'default' => 'Çiçek ve yaşam rehberi', 'third' => true ),
						array( 'id' => 'soc_follow', 'type' => 'text', 'label' => 'Sosyal kart başlığı', 'default' => 'Bizi takip edin', 'third' => true ),
					),
				),
			),
		),

		/* ------------------------------------------------------------------ */
		'product' => array(
			'title'  => 'Ürün & Mağaza',
			'icon'   => 'dashicons-products',
			'groups' => array(
				array(
					'title'  => 'Ürün kartları',
					'fields' => array(
						array( 'id' => 'quick_add', 'type' => 'toggle', 'label' => 'Listelerde hızlı "Sepete Ekle"', 'default' => 0, 'desc' => 'Kapalıyken ürün kartları ürün detayına yönlendirir (teslimat & not seçimi için önerilir).', 'half' => true ),
						array( 'id' => 'card_btn_text', 'type' => 'text', 'label' => 'Kart buton metni', 'default' => 'İncele', 'half' => true ),
						array( 'id' => 'card_cart_icon', 'type' => 'toggle', 'label' => 'Kartta fiyat yanında sepet ikonu', 'default' => 0, 'half' => true, 'desc' => 'Hızlı sepete ekle kapalıysa ikon ürün detayına götürür.' ),
						array( 'id' => 'card_show_sku', 'type' => 'toggle', 'label' => 'Kartta ürün kodu (SKU)', 'default' => 0, 'half' => true ),
						array( 'id' => 'card_hover_image', 'type' => 'toggle', 'label' => 'Üzerine gelince 2. görseli göster', 'default' => 1, 'third' => true ),
						array( 'id' => 'card_show_cat', 'type' => 'toggle', 'label' => 'Kategori etiketini göster', 'default' => 1, 'third' => true ),
						array( 'id' => 'badge_new_days', 'type' => 'number', 'label' => '"Yeni" etiketi (gün)', 'default' => 14, 'min' => 0, 'max' => 90, 'third' => true ),
						array( 'id' => 'shop_columns', 'type' => 'select', 'label' => 'Mağaza sütun sayısı', 'default' => '4', 'half' => true, 'options' => array( '3' => '3', '4' => '4' ) ),
						array( 'id' => 'shop_per_page', 'type' => 'number', 'label' => 'Sayfa başına ürün', 'default' => 16, 'min' => 4, 'max' => 60, 'half' => true ),
					),
				),
				array(
					'title'  => 'Ürün detay sayfası',
					'fields' => array(
						array( 'id' => 'single_delivery_note', 'type' => 'text', 'label' => 'Fiyat altı teslimat notu', 'default' => 'Saat {cutoff}\'a kadar verilen siparişler aynı gün teslim edilir.' ),
						array( 'id' => 'single_trust', 'type' => 'lines', 'label' => 'Sepete ekle altı güven maddeleri', 'default' => "shield | 3D Secure ile güvenli ödeme\nnote | Ücretsiz kişiye özel not kartı\nleaf | Günlük taze çiçeklerle hazırlanır", 'rows' => 3, 'desc' => 'Format: ikon | metin' ),
						array( 'id' => 'single_whatsapp', 'type' => 'toggle', 'label' => 'WhatsApp ile sipariş butonu', 'default' => 1, 'half' => true ),
						array( 'id' => 'single_share', 'type' => 'toggle', 'label' => 'Paylaş bağlantıları', 'default' => 1, 'half' => true ),
						array( 'id' => 'desc_title', 'type' => 'text', 'label' => 'Açıklama bölümü başlığı', 'default' => 'Ürün Açıklaması', 'half' => true ),
						array( 'id' => 'care_title', 'type' => 'text', 'label' => 'Bakım başlığı', 'default' => 'Çiçek Bakımı', 'half' => true ),
						array( 'id' => 'care_default', 'type' => 'textarea', 'label' => 'Varsayılan bakım bilgisi', 'default' => "Çiçeklerinizi doğrudan güneş ışığından ve ısı kaynaklarından uzak tutun.\nVazo suyunu iki günde bir değiştirin, sapları çapraz şekilde 1-2 cm kesin.\nSolan yaprakları temizleyerek çiçeklerinizin ömrünü uzatabilirsiniz.", 'rows' => 3 ),
						array( 'id' => 'delivery_info', 'type' => 'textarea', 'label' => 'Teslimat bilgisi (ürün sayfası)', 'default' => "Siparişleriniz İzmir'in seçili bölgelerine, seçtiğiniz tarih ve saat aralığında teslim edilir.\nAynı gün teslimat için son sipariş saati {cutoff}'dır.\nÇiçeklerde mevsimsel farklılıklar olabilir; tasarım aynı özen ve renk uyumuyla hazırlanır.", 'rows' => 3 ),
						array( 'id' => 'related_title', 'type' => 'text', 'label' => 'Benzer ürünler başlığı', 'default' => 'Bunları da Beğenebilirsiniz', 'half' => true ),
						array( 'id' => 'related_count', 'type' => 'select', 'label' => 'Benzer ürün sayısı', 'default' => '8', 'half' => true, 'options' => array( '4' => '4', '8' => '8', '12' => '12' ) ),
						array( 'id' => 'quick_order', 'type' => 'toggle', 'label' => 'Hızlı sipariş: teslimat bilgileri ürün sayfasında alınır, tek adımda ödeme', 'default' => 1, 'half' => true ),
						array( 'id' => 'quick_single', 'type' => 'toggle', 'label' => '"Hemen Satın Al" sepeti sadece bu ürünle başlatsın (tek teslimat = tek sipariş)', 'default' => 1, 'half' => true ),
						array( 'id' => 'quick_intro', 'type' => 'text', 'label' => 'Hızlı sipariş üst yazısı', 'default' => 'Teslimat bilgilerini burada doldurun, tek adımda ödemeye geçin.', 'half' => true ),
						array( 'id' => 'single_related_first', 'type' => 'toggle', 'label' => 'Benzer ürünler hemen ürünün altında (açıklama ve SSS sonra)', 'default' => 1, 'half' => true ),
					),
				),
				array(
					'title'  => 'Sıkça Sorulan Sorular',
					'desc'   => 'Genel SSS tüm ürün sayfalarında gösterilir. Ürüne özel sorular ürün düzenleme ekranından eklenir ve üstte listelenir.',
					'fields' => array(
						array( 'id' => 'faq_title', 'type' => 'text', 'label' => 'Başlık', 'default' => 'Sıkça Sorulan Sorular', 'half' => true ),
						array( 'id' => 'faq_on_product', 'type' => 'toggle', 'label' => 'Ürün sayfalarında göster', 'default' => 1, 'half' => true ),
						array(
							'id'          => 'faq_items',
							'type'        => 'repeater',
							'label'       => 'Genel sorular',
							'add_label'   => 'Soru ekle',
							'title_field' => 'q',
							'default'     => array(
								array( 'q' => 'Aynı gün teslimat yapıyor musunuz?', 'a' => "Evet. Saat 16.00'ya kadar verilen siparişler İzmir'in seçili bölgelerine aynı gün teslim edilir." ),
								array( 'q' => 'Teslimat ücreti ne kadar?', 'a' => 'Teslimat ücreti ödeme adımında seçtiğiniz bölgeye göre otomatik hesaplanır. Alsancak ve Konak çevresine teslimat ücretsizdir.' ),
								array( 'q' => 'Çiçek notu ekleyebilir miyim?', 'a' => 'Her siparişe ücretsiz, kişiye özel not kartı ekliyoruz. Ödeme adımında hazır mesaj şablonlarından da seçebilirsiniz.' ),
								array( 'q' => 'Ürün görseldekiyle aynı mı olacak?', 'a' => 'Mevsime bağlı olarak bazı çiçeklerde küçük farklılıklar olabilir; tasarım aynı renk uyumu ve özenle hazırlanır.' ),
								array( 'q' => 'Siparişimi nasıl takip ederim?', 'a' => 'Sipariş Takip sayfasından sipariş numaranız ve telefon/e-posta bilginizle siparişinizin durumunu anlık görebilirsiniz.' ),
							),
							'fields'      => array(
								array( 'id' => 'q', 'type' => 'text', 'label' => 'Soru' ),
								array( 'id' => 'a', 'type' => 'textarea', 'label' => 'Cevap', 'rows' => 3 ),
							),
						),
					),
				),
			),
		),

		/* ------------------------------------------------------------------ */
		'delivery' => array(
			'title'  => 'Teslimat & Ödeme',
			'icon'   => 'dashicons-car',
			'groups' => array(
				array(
					'title'  => 'Teslimat türleri',
					'fields' => array(
						array( 'id' => 'df_checkout_on', 'type' => 'toggle', 'label' => 'Çiçekçi ödeme akışını kullan (takvim, alıcı, not)', 'default' => 1, 'half' => true ),
						array( 'id' => 'df_fee_mode', 'type' => 'toggle', 'label' => 'Teslimat ücretini bölgeye göre hesapla', 'default' => 1, 'half' => true, 'desc' => 'Açıkken WooCommerce kargo yöntemleri devre dışı kalır, ücret bölge listesinden eklenir.' ),
						array( 'id' => 'df_pickup_on', 'type' => 'toggle', 'label' => '"Mağazadan Teslim" seçeneği', 'default' => 1, 'half' => true ),
						array( 'id' => 'df_free_over', 'type' => 'number', 'label' => 'Bu tutarın üzerinde teslimat ücretsiz (₺, 0 = kapalı)', 'default' => 0, 'min' => 0, 'half' => true ),
						array( 'id' => 'df_fee_label', 'type' => 'text', 'label' => 'Ücret satırı etiketi', 'default' => 'Teslimat Ücreti', 'half' => true ),
						array( 'id' => 'df_default_city', 'type' => 'text', 'label' => 'Varsayılan şehir', 'default' => 'İzmir', 'half' => true ),
						array( 'id' => 'df_stores', 'type' => 'lines', 'label' => 'Mağazalar (Mağaza adı | Adres | Çalışma saatleri)', 'default' => 'Alsancak Atölye | 1338 Sk 5/G Çankaya, 35700 Konak / İzmir | Pazartesi - Cumartesi 09.00 - 19.00', 'rows' => 3 ),
					),
				),
				array(
					'title'  => 'Teslimat bölgeleri',
					'desc'   => 'Her satır: Şehir | Bölge | Ücret. Ücretsiz bölgeler için 0 yazın. Ödeme sayfasında şehir gruplarıyla aranabilir liste olarak gösterilir.',
					'fields' => array(
						array( 'id' => 'df_districts', 'type' => 'lines', 'label' => 'Bölgeler', 'default' => df_default_districts(), 'rows' => 14, 'code' => true ),
					),
				),
				array(
					'title'  => 'Takvim & saat',
					'fields' => array(
						array( 'id' => 'df_cutoff', 'type' => 'text', 'label' => 'Aynı gün son sipariş saati', 'default' => '16:00', 'third' => true ),
						array( 'id' => 'df_prep_hours', 'type' => 'number', 'label' => 'Hazırlık süresi (saat)', 'default' => 2, 'min' => 0, 'max' => 24, 'third' => true ),
						array( 'id' => 'df_max_days', 'type' => 'number', 'label' => 'En fazla kaç gün ileri', 'default' => 60, 'min' => 1, 'max' => 365, 'third' => true ),
						array(
							'id'      => 'df_closed_days',
							'type'    => 'checkboxes',
							'label'   => 'Teslimat yapılmayan günler',
							'default' => array( '0' ),
							'options' => array( '1' => 'Pazartesi', '2' => 'Salı', '3' => 'Çarşamba', '4' => 'Perşembe', '5' => 'Cuma', '6' => 'Cumartesi', '0' => 'Pazar' ),
						),
						array( 'id' => 'df_blocked_dates', 'type' => 'lines', 'label' => 'Kapalı tarihler (YYYY-AA-GG, her satır bir tarih)', 'default' => '', 'rows' => 3, 'half' => true ),
						array( 'id' => 'df_slots', 'type' => 'lines', 'label' => 'Saat aralıkları (Başlangıç - Bitiş | Ek ücret)', 'default' => "09:00 - 12:00 | 0\n12:00 - 15:00 | 0\n15:00 - 18:00 | 0\n18:00 - 21:00 | 0", 'rows' => 5, 'half' => true ),
					),
				),
				array(
					'title'  => 'Kuryeler',
					'desc'   => 'Her satır: Ad Soyad | Telefon (905xxxxxxxxx). Yönetim Paneli → Kurye Yönetimi ekranında siparişlere atanır; kuryeye WhatsApp ile adres gönderilebilir.',
					'fields' => array(
						array( 'id' => 'df_couriers', 'type' => 'lines', 'label' => 'Kurye listesi', 'default' => '', 'rows' => 4 ),
						array( 'id' => 'courier_status', 'type' => 'toggle', 'label' => 'Kurye atanınca sipariş durumu "Yolda" olsun', 'default' => 0 ),
					),
				),
				array(
					'title'  => 'Çiçek notu',
					'fields' => array(
						array( 'id' => 'note_max', 'type' => 'number', 'label' => 'Not karakter sınırı', 'default' => 300, 'min' => 50, 'max' => 1000, 'half' => true ),
						array( 'id' => 'note_intro', 'type' => 'text', 'label' => 'Not alanı açıklaması', 'default' => 'Notunuz özel kartımıza el yazısı tadında basılarak çiçeğinize eklenir.', 'half' => true ),
						array( 'id' => 'note_templates', 'type' => 'lines', 'label' => 'Hazır mesajlar (Kategori | Mesaj)', 'default' => df_default_note_templates(), 'rows' => 10 ),
					),
				),
			),
		),

		/* ------------------------------------------------------------------ */
		'footer' => array(
			'title'  => 'Footer & İletişim',
			'icon'   => 'dashicons-editor-insertmore',
			'groups' => array(
				array(
					'title'  => 'Footer',
					'desc'   => 'Footer menülerini Görünüm → Menüler ekranından "Footer: Hakkımızda" ve "Footer: Müşteri Hizmetleri" konumlarına atayın.',
					'fields' => array(
						array( 'id' => 'footer_bg', 'type' => 'color', 'label' => 'Footer zemini', 'default' => '#F1E9E1', 'half' => true ),
						array( 'id' => 'footer_logo', 'type' => 'image', 'label' => 'Footer logosu (opsiyonel)', 'half' => true ),
						array( 'id' => 'footer_about', 'type' => 'textarea', 'label' => 'Marka metni', 'default' => "İzmir Alsancak'taki atölyemizde her gün taze seçilen çiçeklerle, sevdiklerinize söylemek istediklerinizi zarif tasarımlara dönüştürüyoruz.", 'rows' => 3 ),
						array( 'id' => 'footer_col2_title', 'type' => 'text', 'label' => '2. sütun başlığı', 'default' => 'Hakkımızda', 'third' => true ),
						array( 'id' => 'footer_col3_title', 'type' => 'text', 'label' => '3. sütun başlığı', 'default' => 'Müşteri Hizmetleri', 'third' => true ),
						array( 'id' => 'footer_col4_title', 'type' => 'text', 'label' => '4. sütun başlığı', 'default' => 'İletişim', 'third' => true ),
						array( 'id' => 'footer_payment', 'type' => 'image', 'label' => 'Ödeme logoları görseli', 'half' => true ),
						array( 'id' => 'footer_copyright', 'type' => 'text', 'label' => 'Telif metni', 'default' => '© {year} Derin Flowers. Tüm hakları saklıdır.', 'half' => true ),
					),
				),
				array(
					'title'  => 'İletişim bilgileri',
					'fields' => array(
						array( 'id' => 'contact_phone1', 'type' => 'text', 'label' => 'Telefon 1', 'default' => '0850 532 50 95', 'third' => true ),
						array( 'id' => 'contact_phone2', 'type' => 'text', 'label' => 'Telefon 2 (GSM)', 'default' => '0540 284 0 444', 'third' => true ),
						array( 'id' => 'contact_email', 'type' => 'text', 'label' => 'E-posta', 'default' => 'info@derinflowers.com', 'third' => true ),
						array( 'id' => 'contact_address', 'type' => 'textarea', 'label' => 'Adres', 'default' => "Alsancak, Konak\n1338 Sk 5/G Çankaya\n35700 Konak / İzmir", 'rows' => 3, 'half' => true ),
						array( 'id' => 'contact_hours', 'type' => 'textarea', 'label' => 'Çalışma saatleri', 'default' => "Pazartesi - Cumartesi\n09.00 - 19.00", 'rows' => 3, 'half' => true ),
						array( 'id' => 'contact_whatsapp', 'type' => 'text', 'label' => 'WhatsApp numarası (905xxxxxxxxx)', 'default' => '905402840444', 'half' => true ),
						array( 'id' => 'whatsapp_float', 'type' => 'toggle', 'label' => 'Sabit WhatsApp butonu', 'default' => 1, 'half' => true ),
						array( 'id' => 'contact_map', 'type' => 'url', 'label' => 'Google Maps bağlantısı', 'default' => 'https://maps.google.com/?q=1338+Sk+5/G+%C3%87ankaya+Konak+%C4%B0zmir' ),
					),
				),
				array(
					'title'  => 'Sosyal medya',
					'fields' => array(
						array( 'id' => 'social_instagram', 'type' => 'url', 'label' => 'Instagram', 'default' => 'https://www.instagram.com/derinflowers/', 'third' => true ),
						array( 'id' => 'social_facebook', 'type' => 'url', 'label' => 'Facebook', 'third' => true ),
						array( 'id' => 'social_pinterest', 'type' => 'url', 'label' => 'Pinterest', 'third' => true ),
						array( 'id' => 'social_youtube', 'type' => 'url', 'label' => 'YouTube', 'third' => true ),
						array( 'id' => 'social_tiktok', 'type' => 'url', 'label' => 'TikTok', 'third' => true ),
					),
				),
			),
		),

		/* ------------------------------------------------------------------ */
		'pages' => array(
			'title'  => 'Hakkımızda & İletişim',
			'icon'   => 'dashicons-media-document',
			'groups' => array(
				array(
					'title'  => 'Hakkımızda sayfası',
					'desc'   => 'Kısa adı "hakkimizda" olan sayfada kullanılır. Sayfa içeriği (editör) kullanılmaz; böylece başka eklentilerin kaydettiği HTML header/footer\'ı bozamaz. Ön yüzde "Canlı Düzenle" ile de değiştirebilirsiniz.',
					'fields' => array(
						array( 'id' => 'about_eyebrow', 'type' => 'text', 'label' => 'Üst küçük metin', 'default' => 'Hikayemiz', 'half' => true ),
						array( 'id' => 'about_title', 'type' => 'text', 'label' => 'Başlık', 'default' => 'Hakkımızda', 'half' => true ),
						array( 'id' => 'about_image', 'type' => 'image', 'label' => 'Görsel (opsiyonel, yatay)' ),
						array( 'id' => 'about_text', 'type' => 'textarea', 'label' => 'Metin (paragraflar arasında boş satır bırakın)', 'rows' => 7, 'default' => "Derin Flowers, İzmir Alsancak'taki atölyesinde her gün taze seçilen çiçeklerle özel anlarınız için tasarımlar hazırlar.\n\nHer siparişi floristlerimiz elde hazırlar; renk uyumuna, tazeliğe ve sunuma aynı özeni gösteririz. Çiçekleriniz, seçtiğiniz gün ve saatte sevdiklerinize ulaştırılır.\n\nAmacımız basit: Söylemek istediklerinizi en güzel haliyle çiçeklere emanet etmek." ),
						array(
							'id'          => 'about_values',
							'type'        => 'repeater',
							'label'       => 'Maddeler',
							'add_label'   => 'Madde ekle',
							'title_field' => 'title',
							'default'     => array(
								array( 'icon' => 'leaf', 'title' => 'Taze Çiçek', 'text' => 'Her gün yeniden seçilir' ),
								array( 'icon' => 'hand', 'title' => 'El Yapımı', 'text' => 'Her tasarım özel hazırlanır' ),
								array( 'icon' => 'truck', 'title' => 'Zamanında Teslimat', 'text' => "İzmir'in seçili bölgelerine" ),
							),
							'fields'      => array(
								array( 'id' => 'icon', 'type' => 'icon', 'label' => 'İkon', 'third' => true ),
								array( 'id' => 'title', 'type' => 'text', 'label' => 'Başlık', 'third' => true ),
								array( 'id' => 'text', 'type' => 'text', 'label' => 'Alt metin', 'third' => true ),
							),
						),
					),
				),
				array(
					'title'  => 'İletişim sayfası',
					'desc'   => 'Kısa adı "iletisim" olan sayfada kullanılır. Telefon, adres ve saatler "Footer & İletişim" sekmesinden gelir.',
					'fields' => array(
						array( 'id' => 'contact_eyebrow', 'type' => 'text', 'label' => 'Üst küçük metin', 'default' => 'Bize Ulaşın', 'half' => true ),
						array( 'id' => 'contact_title', 'type' => 'text', 'label' => 'Başlık', 'default' => 'İletişim', 'half' => true ),
						array( 'id' => 'contact_lead', 'type' => 'textarea', 'label' => 'Kısa açıklama (opsiyonel)', 'rows' => 2, 'default' => '' ),
						array( 'id' => 'contact_form_on', 'type' => 'toggle', 'label' => 'İletişim formu göster', 'default' => 1, 'half' => true ),
						array( 'id' => 'contact_form_title', 'type' => 'text', 'label' => 'Form başlığı', 'default' => 'Mesaj Gönderin', 'half' => true ),
					),
				),
			),
		),

		'admin' => array(
			'title'  => 'Yönetim & Giriş',
			'icon'   => 'dashicons-lock',
			'groups' => array(
				array(
					'title'  => 'Yönetim paneli',
					'fields' => array(
						array( 'id' => 'admin_redirect', 'type' => 'toggle', 'label' => 'Girişten sonra Yönetim Paneli açılsın', 'default' => 1, 'half' => true ),
						array( 'id' => 'admin_replace_dashboard', 'type' => 'toggle', 'label' => 'WordPress Başlangıç yerine Yönetim Paneli', 'default' => 1, 'half' => true ),
					),
				),
				array(
					'title'  => 'Giriş ekranı (wp-login)',
					'fields' => array(
						array( 'id' => 'admin_login_style', 'type' => 'toggle', 'label' => 'Markalı giriş ekranı', 'default' => 1, 'third' => true ),
						array( 'id' => 'admin_login_logo', 'type' => 'image', 'label' => 'Giriş logosu (boşsa metin logo)', 'third' => true ),
						array( 'id' => 'admin_login_image', 'type' => 'image', 'label' => 'Giriş yan görseli', 'third' => true ),
						array( 'id' => 'admin_login_text', 'type' => 'text', 'label' => 'Giriş ekranı metni', 'default' => 'Yönetim paneline hoş geldiniz.' ),
					),
				),
			),
		),

		'account' => array(
			'title'  => 'Üyelik & Takip',
			'icon'   => 'dashicons-id',
			'groups' => array(
				array(
					'title'  => 'Üyelik',
					'fields' => array(
						array( 'id' => 'reg_on', 'type' => 'toggle', 'label' => 'Hesabım sayfasında üye kaydı açık', 'default' => 1, 'half' => true, 'desc' => 'WooCommerce kayıt ayarıyla eşitlenir.' ),
						array( 'id' => 'reg_phone', 'type' => 'toggle', 'label' => 'Kayıtta ad, soyad ve telefon iste', 'default' => 1, 'half' => true ),
						array( 'id' => 'reg_kvkk', 'type' => 'text', 'label' => 'Kayıt onay metni', 'default' => 'Üyelik sözleşmesini ve KVKK Aydınlatma Metni\'ni okudum, kabul ediyorum.' ),
						array( 'id' => 'login_title', 'type' => 'text', 'label' => 'Giriş sayfası başlığı', 'default' => 'Hoş Geldiniz', 'half' => true ),
						array( 'id' => 'login_text', 'type' => 'text', 'label' => 'Giriş sayfası metni', 'default' => 'Siparişlerinizi takip edin, favorilerinizi kaydedin, özel günlerde size özel fırsatlardan haberdar olun.', 'half' => true ),
						array( 'id' => 'login_image', 'type' => 'image', 'label' => 'Giriş sayfası görseli' ),
					),
				),
				array(
					'title'  => 'Sipariş takip',
					'desc'   => 'Sipariş takip formu [derin_siparis_takip] kısa koduyla herhangi bir sayfada kullanılabilir. Kurulum yardımcısı sayfayı otomatik oluşturur.',
					'fields' => array(
						array( 'id' => 'track_title', 'type' => 'text', 'label' => 'Başlık', 'default' => 'Siparişim Nerede?', 'half' => true ),
						array( 'id' => 'track_text', 'type' => 'text', 'label' => 'Açıklama', 'default' => 'Sipariş numaranız ve siparişte kullandığınız telefon ya da e-posta ile siparişinizin durumunu görün.', 'half' => true ),
						array( 'id' => 'track_page', 'type' => 'page', 'label' => 'Sipariş takip sayfası (header/footer linkleri için)', 'half' => true ),
						array( 'id' => 'wishlist_page', 'type' => 'page', 'label' => 'Favoriler sayfası', 'half' => true ),
					),
				),
			),
		),
	);

	// Her ana sayfa bölümüne "Bölüm tasarımı" (zemin, yazı rengi, boşluklar, hizalama, cihazda gizleme).
	foreach ( $schema['home']['groups'] as $gi => $group ) {
		if ( ! empty( $group['section'] ) ) {
			$schema['home']['groups'][ $gi ]['fields'][] = array( 'id' => 'sd_' . $group['section'], 'type' => 'design', 'label' => 'Bölüm tasarımı' );
		}
	}
	$schema['hero']['groups'][0]['fields'][] = array( 'id' => 'sd_hero', 'type' => 'design', 'label' => 'Bölüm tasarımı' );

	$schema['appearance']['groups'][] = array(
		'title'  => 'Gelişmiş: özel CSS',
		'desc'   => 'İleri düzey kullanıcılar için. Buraya yazılan CSS tüm sitede, tema stillerinden sonra yüklenir.',
		'fields' => array(
			array( 'id' => 'custom_css', 'type' => 'lines', 'label' => 'Özel CSS', 'rows' => 8 ),
		),
	);
	$schema['admin']['groups'][0]['fields'][] = array( 'id' => 'admin_skin', 'type' => 'toggle', 'label' => 'Yönetim ekranında Derin Flowers görünümü (koyu menü)', 'default' => 1, 'half' => true );
	$schema['admin']['groups'][0]['fields'][] = array( 'id' => 'admin_simple_menu', 'type' => 'toggle', 'label' => 'Sade menü (müşteriye gerekmeyen WordPress öğelerini gizle; menü altındaki bağlantıyla açılır)', 'default' => 1, 'half' => true );
	$schema['admin']['groups'][0]['fields'][] = array( 'id' => 'admin_fab', 'type' => 'toggle', 'label' => 'Sitede yöneticiye "Tasarım Stüdyosu" düğmesi göster', 'default' => 1, 'half' => true );

	return apply_filters( 'df_options_schema', $schema );
}
