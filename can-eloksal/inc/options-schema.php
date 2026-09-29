<?php
/**
 * Tema Ayarları şeması. Varsayılan değerler ve yönetim ekranı bu şemadan üretilir.
 *
 * Alan tipleri: text, textarea, email, url, number, toggle, select, image, gallery, page,
 * repeater, sections, code, password, lines, heading, icon.
 *
 * @package CanEloksal
 */

defined( 'ABSPATH' ) || exit;

/**
 * Ana sayfa bölümleri (varsayılan sıra).
 *
 * @return array<string,string>
 */
function ce_home_section_labels() {
	return array(
		'hero'       => 'Hero / Slider',
		'trust'      => 'Güven şeridi',
		'services'   => 'Hizmetler',
		'about'      => 'Hakkımızda',
		'why'        => 'Neden Can Eloksal',
		'sectors'    => 'Sektörler',
		'process'    => 'Proses',
		'gallery'    => 'Galeri önizleme',
		'cta'        => 'Ana CTA (tam genişlik)',
		'blog'       => 'Teknik Bilgi Merkezi (blog)',
		'references' => 'Referanslar',
		'contact'    => 'İletişim CTA',
	);
}

/**
 * Hizmet renk tonları (görsel yokken kullanılan metal yüzey dokusu).
 *
 * @return array<string,string>
 */
function ce_tone_choices() {
	return array(
		'natural'   => 'Naturel (gümüş)',
		'red'       => 'Kırmızı',
		'blue'      => 'Mavi',
		'gold'      => 'Sarı / Altın',
		'black'     => 'Siyah (mat)',
		'multi'     => 'Çok renkli',
		'champagne' => 'Şampanya (alodin)',
		'chromate'  => 'Sarı yanardöner (kromat)',
		'graphite'  => 'Grafit (kuru film)',
		'steel'     => 'Çelik / nötr',
	);
}

/**
 * Tüm ayar sekmeleri.
 *
 * @return array
 */
function ce_options_schema() {
	static $schema = null;
	if ( null !== $schema ) {
		return $schema;
	}

	$schema = array(

		// ------------------------------------------------------------------
		'brand'    => array(
			'title'    => 'Marka & Genel',
			'icon'     => 'dashicons-art',
			'sections' => array(
				array(
					'title'  => 'Logo',
					'desc'   => 'Logo yüklenmezse metin logo (Site Adı) kullanılır. Koyu zemin üzerinde açık logo, açık zeminde (kaydırma sonrası header) koyu logo gösterilir.',
					'fields' => array(
						array( 'id' => 'site_name', 'type' => 'text', 'label' => 'Site / firma adı', 'default' => 'Can Eloksal', 'half' => true ),
						array( 'id' => 'logo_tagline', 'type' => 'text', 'label' => 'Logo alt yazısı', 'default' => 'Alüminyum Yüzey İşlem', 'half' => true ),
						array( 'id' => 'logo_light', 'type' => 'image', 'label' => 'Logo (koyu zemin / açık renk logo)', 'third' => true ),
						array( 'id' => 'logo_dark', 'type' => 'image', 'label' => 'Koyu logo (açık zemin)', 'third' => true ),
						array( 'id' => 'logo_mobile', 'type' => 'image', 'label' => 'Mobil logo (opsiyonel)', 'third' => true ),
						array( 'id' => 'logo_height', 'type' => 'number', 'label' => 'Logo yüksekliği (px)', 'default' => 40, 'min' => 20, 'max' => 90, 'half' => true ),
						array( 'id' => 'favicon', 'type' => 'image', 'label' => 'Favicon (kare PNG, en az 512px)', 'desc' => 'Görünüm → Özelleştir → Site Kimliği\'nden site simgesi atanmışsa o önceliklidir.', 'half' => true ),
					),
				),
				array(
					'title'  => 'Görseller',
					'fields' => array(
						array( 'id' => 'webp_enabled', 'type' => 'toggle', 'label' => 'Yüklenen JPG/PNG görsellerin boyutlarını WebP olarak üret (sunucu destekliyorsa)', 'default' => 1 ),
						array( 'id' => 'header_cta_label', 'type' => 'text', 'label' => 'Header buton metni', 'default' => 'Teklif Al', 'half' => true ),
						array( 'id' => 'header_cta_url', 'type' => 'url', 'label' => 'Header buton bağlantısı', 'default' => '/teklif-al/', 'half' => true ),
					),
				),
			),
		),

		// ------------------------------------------------------------------
		'contact'  => array(
			'title'    => 'İletişim & Sosyal',
			'icon'     => 'dashicons-phone',
			'sections' => array(
				array(
					'title'  => 'Firma iletişim bilgileri',
					'fields' => array(
						array( 'id' => 'company_name', 'type' => 'text', 'label' => 'Firma ünvanı', 'default' => 'CAN ELOKSAL', 'half' => true ),
						array( 'id' => 'phone', 'type' => 'text', 'label' => 'Telefon', 'default' => '0541 781 20 60', 'half' => true ),
						array( 'id' => 'whatsapp', 'type' => 'text', 'label' => 'WhatsApp numarası (ülke kodlu, sadece rakam)', 'default' => '905417812060', 'half' => true ),
						array( 'id' => 'whatsapp_message', 'type' => 'text', 'label' => 'WhatsApp hazır mesajı', 'default' => 'Merhaba, eloksal / kaplama hizmetiniz hakkında bilgi almak istiyorum.', 'half' => true ),
						array( 'id' => 'email', 'type' => 'email', 'label' => 'E-posta', 'default' => 'caneloksal@gmail.com', 'half' => true ),
						array( 'id' => 'working_hours', 'type' => 'text', 'label' => 'Çalışma saatleri (boş bırakılırsa gösterilmez)', 'default' => '', 'half' => true ),
						array( 'id' => 'address', 'type' => 'textarea', 'label' => 'Adres', 'default' => "Yeni Mh. Küme Evler Topça I-Blok No:11/5\nErenler / SAKARYA", 'rows' => 2 ),
						array( 'id' => 'maps_embed', 'type' => 'code', 'label' => 'Google Maps embed kodu (iframe)', 'default' => '<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3022.6282760174795!2d30.41994650000001!3d40.7482043!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x14ccb3cff0c9676b%3A0x1287dceedb3e7ee!2sCAN%20ELOKSAL!5e0!3m2!1str!2str!4v1790341532320!5m2!1str!2str" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>', 'desc' => 'Yalnızca google.com/maps adresli iframe kabul edilir.' ),
						array( 'id' => 'maps_link', 'type' => 'url', 'label' => 'Yol tarifi bağlantısı', 'default' => 'https://www.google.com/maps/search/?api=1&query=CAN+ELOKSAL+Erenler+Sakarya', 'half' => true ),
						array( 'id' => 'geo_lat', 'type' => 'text', 'label' => 'Enlem (schema)', 'default' => '40.7482043', 'quarter' => true ),
						array( 'id' => 'geo_lng', 'type' => 'text', 'label' => 'Boylam (schema)', 'default' => '30.4199465', 'quarter' => true ),
					),
				),
				array(
					'title'  => 'Sosyal medya',
					'desc'   => 'Boş bırakılan hesaplar sitede gösterilmez.',
					'fields' => array(
						array( 'id' => 'instagram', 'type' => 'url', 'label' => 'Instagram', 'half' => true ),
						array( 'id' => 'linkedin', 'type' => 'url', 'label' => 'LinkedIn', 'half' => true ),
						array( 'id' => 'facebook', 'type' => 'url', 'label' => 'Facebook', 'half' => true ),
						array( 'id' => 'youtube', 'type' => 'url', 'label' => 'YouTube', 'half' => true ),
					),
				),
				array(
					'title'  => 'Yüzen iletişim butonu',
					'fields' => array(
						array( 'id' => 'float_whatsapp', 'type' => 'toggle', 'label' => 'WhatsApp butonunu göster', 'default' => 1 ),
					),
				),
			),
		),

		// ------------------------------------------------------------------
		'home'     => array(
			'title'    => 'Ana Sayfa',
			'icon'     => 'dashicons-admin-home',
			'sections' => array(
				array(
					'title'  => 'Bölüm sırası ve görünürlüğü',
					'desc'   => 'Sürükleyerek sıralayın, kutucukla açıp kapatın.',
					'fields' => array(
						array( 'id' => 'home_sections', 'type' => 'sections', 'label' => 'Bölümler', 'choices' => ce_home_section_labels() ),
					),
				),
				array(
					'title'  => 'Hero (slayt yoksa kullanılır)',
					'desc'   => 'Hero içerikleri "Hero / Slider" menüsünden yönetilir. Hiç slayt yayınlanmamışsa aşağıdaki içerik gösterilir.',
					'fields' => array(
						array( 'id' => 'hero_eyebrow', 'type' => 'text', 'label' => 'Üst etiket', 'default' => 'Alüminyum Yüzey Mühendisliği', 'half' => true ),
						array( 'id' => 'hero_title', 'type' => 'textarea', 'label' => 'Başlık (satır başı = yeni satır)', 'default' => "ALÜMİNYUM YÜZEYLERDE\nPROFESYONEL ELOKSAL ÇÖZÜMLERİ", 'rows' => 2 ),
						array( 'id' => 'hero_text', 'type' => 'textarea', 'label' => 'Açıklama', 'default' => 'İstenilen mikron, yüzey kalitesi ve renk seçenekleriyle endüstriyel alüminyum parçalarınız için profesyonel eloksal ve yüzey kaplama çözümleri.', 'rows' => 3 ),
						array( 'id' => 'hero_tags', 'type' => 'lines', 'label' => 'Sektör etiketleri (her satır bir etiket)', 'default' => "Savunma Sanayi\nMakine Sanayi\nEndüstriyel Üretim", 'rows' => 3 ),
						array( 'id' => 'hero_image', 'type' => 'image', 'label' => 'Arka plan görseli', 'third' => true ),
						array( 'id' => 'hero_autoplay', 'type' => 'number', 'label' => 'Slayt geçiş süresi (sn, 0 = kapalı)', 'default' => 7, 'min' => 0, 'max' => 30, 'third' => true ),
					),
				),
				array(
					'title'  => 'Güven şeridi',
					'fields' => array(
						array(
							'id'      => 'trust_items',
							'type'    => 'repeater',
							'label'   => 'Maddeler',
							'add'     => 'Madde ekle',
							'fields'  => array(
								array( 'id' => 'icon', 'type' => 'icon', 'label' => 'İkon' ),
								array( 'id' => 'title', 'type' => 'text', 'label' => 'Başlık' ),
								array( 'id' => 'text', 'type' => 'text', 'label' => 'Kısa açıklama' ),
							),
							'default' => array(
								array( 'icon' => 'layers', 'title' => 'Profesyonel Yüzey İşleme', 'text' => 'Kontrollü proses, tutarlı yüzey' ),
								array( 'icon' => 'ruler', 'title' => 'İstenilen Mikron Seçenekleri', 'text' => 'Kalınlık talebinize göre uygulama' ),
								array( 'icon' => 'palette', 'title' => 'Farklı Renk Uygulamaları', 'text' => 'Naturelden siyaha geniş renk yelpazesi' ),
								array( 'icon' => 'factory', 'title' => 'Endüstriyel Çözümler', 'text' => 'Savunma ve makine sanayine yönelik' ),
							),
						),
					),
				),
				array(
					'title'  => 'Hizmetler bölümü',
					'fields' => array(
						array( 'id' => 'services_eyebrow', 'type' => 'text', 'label' => 'Üst etiket', 'default' => 'Hizmetlerimiz', 'third' => true ),
						array( 'id' => 'services_title', 'type' => 'text', 'label' => 'Başlık', 'default' => 'YÜZEY İŞLEM ÇÖZÜMLERİMİZ', 'third' => true ),
						array( 'id' => 'services_count', 'type' => 'number', 'label' => 'Gösterilecek hizmet sayısı', 'default' => 9, 'min' => 1, 'max' => 24, 'third' => true ),
						array( 'id' => 'services_text', 'type' => 'textarea', 'label' => 'Açıklama', 'default' => 'Alüminyum parçalarınız için talep edilen renk, mikron ve yüzey kalitesine uygun eloksal ve kimyasal kaplama uygulamaları.', 'rows' => 2 ),
					),
				),
				array(
					'title'  => 'Hizmetler sayfası (/hizmetler)',
					'fields' => array(
						array( 'id' => 'services_page_title', 'type' => 'text', 'label' => 'Sayfa başlığı', 'default' => 'HİZMETLERİMİZ', 'half' => true ),
						array( 'id' => 'services_page_image', 'type' => 'image', 'label' => 'Hero görseli', 'half' => true ),
						array( 'id' => 'services_page_sub', 'type' => 'text', 'label' => 'Alt başlık', 'default' => 'Alüminyum yüzeylerde profesyonel eloksal ve kaplama çözümleri.' ),
						array( 'id' => 'service_cta_title', 'type' => 'text', 'label' => 'Hizmet detayı varsayılan CTA başlığı', 'default' => 'Bu hizmet için teklif almak ister misiniz?' ),
					),
				),
				array(
					'title'  => 'Hakkımızda bölümü',
					'fields' => array(
						array( 'id' => 'about_eyebrow', 'type' => 'text', 'label' => 'Üst etiket', 'default' => 'Can Eloksal', 'half' => true ),
						array( 'id' => 'about_title', 'type' => 'textarea', 'label' => 'Başlık', 'default' => "HER YÜZEYDE\nÖZENLİ İŞÇİLİK.", 'rows' => 2, 'half' => true ),
						array( 'id' => 'about_text', 'type' => 'textarea', 'label' => 'Metin (boş satır = yeni paragraf)', 'rows' => 8, 'default' => "Tesisimizde günümüz teknolojisinin getirdiği tüm imkanlardan yararlanılarak istenilen mikron, kalite ve renkte alüminyum malzemeler için eloksal işçiliği yapılır.\n\nCan Eloksal firması olarak savunma sanayi ve makine sanayi alanlarında ağırlıklı hizmet vererek bu sektörlerdeki her türlü alüminyum malzeme, parça veya ürünlere; mat veya parlak olmak üzere eloksal kaplama uygulamaları gerçekleştirmekteyiz.\n\nEloksal kaplanacak malzemelerin yüzey özelliklerini ve oksitlenmeye karşı direncini geliştirmeye yönelik, teknolojimizi sürekli geliştirerek müşterilerimize yüksek standartlarda hizmet sunmayı hedefliyoruz." ),
						array( 'id' => 'about_image', 'type' => 'image', 'label' => 'Ana görsel (personel + üretim hattı)', 'third' => true ),
						array( 'id' => 'about_image_2', 'type' => 'image', 'label' => 'İkincil küçük görsel', 'third' => true ),
						array( 'id' => 'about_badge', 'type' => 'text', 'label' => 'Görsel üzeri etiket', 'default' => 'Savunma & Makine Sanayi', 'third' => true ),
						array( 'id' => 'about_button', 'type' => 'text', 'label' => 'Buton metni', 'default' => 'Firmamızı Tanıyın', 'half' => true ),
						array( 'id' => 'about_link', 'type' => 'url', 'label' => 'Buton bağlantısı', 'default' => '/hakkimizda/', 'half' => true ),
					),
				),
				array(
					'title'  => 'Neden Can Eloksal',
					'fields' => array(
						array( 'id' => 'why_eyebrow', 'type' => 'text', 'label' => 'Üst etiket', 'default' => 'Neden Can Eloksal', 'half' => true ),
						array( 'id' => 'why_title', 'type' => 'text', 'label' => 'Başlık', 'default' => 'Hassasiyet, süreç disiplini ve teknik yaklaşım.', 'half' => true ),
						array(
							'id'      => 'why_items',
							'type'    => 'repeater',
							'label'   => 'Kartlar',
							'add'     => 'Kart ekle',
							'fields'  => array(
								array( 'id' => 'icon', 'type' => 'icon', 'label' => 'İkon' ),
								array( 'id' => 'title', 'type' => 'text', 'label' => 'Başlık' ),
								array( 'id' => 'text', 'type' => 'textarea', 'label' => 'Açıklama' ),
							),
							'default' => array(
								array( 'icon' => 'target', 'title' => 'Hassas Yüzey İşleme', 'text' => 'Parçanın geometrisine ve kullanım amacına göre planlanan, ölçü hassasiyetini gözeten yüzey işlemleri.' ),
								array( 'icon' => 'ruler', 'title' => 'İstenilen Mikron Seçenekleri', 'text' => 'Talep edilen oksit tabakası kalınlığına uygun proses parametreleri.' ),
								array( 'icon' => 'palette', 'title' => 'Geniş Renk Alternatifleri', 'text' => 'Naturel, kırmızı, mavi, sarı, siyah ve talebe göre farklı renk uygulamaları.' ),
								array( 'icon' => 'settings', 'title' => 'Profesyonel Proses Yönetimi', 'text' => 'Ön işlemden sızdırmazlığa kadar her adımın kontrollü yürütülmesi.' ),
								array( 'icon' => 'badge-check', 'title' => 'Kalite Odaklı Üretim', 'text' => 'Yüzey görünümü, renk tutarlılığı ve kaplama bütünlüğü üzerinde titiz kontrol.' ),
								array( 'icon' => 'cpu', 'title' => 'Teknik Çözüm Yaklaşımı', 'text' => 'Malzeme, alaşım ve beklentiye göre doğru kaplama yöntemini birlikte belirleme.' ),
							),
						),
					),
				),
				array(
					'title'  => 'Sektörler',
					'desc'   => 'Sektör kartları "Sektörler" menüsünden yönetilir.',
					'fields' => array(
						array( 'id' => 'sectors_eyebrow', 'type' => 'text', 'label' => 'Üst etiket', 'default' => 'Hizmet Verdiğimiz Sektörler', 'half' => true ),
						array( 'id' => 'sectors_title', 'type' => 'text', 'label' => 'Başlık', 'default' => 'Hassasiyet gerektiren sektörler için yüzey mühendisliği.', 'half' => true ),
					),
				),
				array(
					'title'  => 'Proses',
					'fields' => array(
						array( 'id' => 'process_eyebrow', 'type' => 'text', 'label' => 'Üst etiket', 'default' => 'Çalışma Sürecimiz', 'half' => true ),
						array( 'id' => 'process_title', 'type' => 'text', 'label' => 'Başlık', 'default' => 'YÜZEYDEN PERFORMANSA', 'half' => true ),
						array(
							'id'      => 'process_steps',
							'type'    => 'repeater',
							'label'   => 'Aşamalar',
							'add'     => 'Aşama ekle',
							'fields'  => array(
								array( 'id' => 'title', 'type' => 'text', 'label' => 'Başlık' ),
								array( 'id' => 'text', 'type' => 'textarea', 'label' => 'Açıklama' ),
							),
							'default' => array(
								array( 'title' => 'Parça Analizi', 'text' => 'Malzeme, alaşım, geometri ve talep edilen yüzey özelliklerinin değerlendirilmesi.' ),
								array( 'title' => 'Yüzey Hazırlığı', 'text' => 'Yağ alma, dağlama ve parlatma gibi ön işlemlerle yüzeyin kaplamaya hazırlanması.' ),
								array( 'title' => 'Eloksal / Kaplama', 'text' => 'Talep edilen mikron, renk ve yüzey tipine uygun kontrollü eloksal ya da kimyasal kaplama.' ),
								array( 'title' => 'Kontrol ve Teslim', 'text' => 'Görsel kontrol, renk ve yüzey uygunluğunun doğrulanması, özenli paketleme ve teslim.' ),
							),
						),
					),
				),
				array(
					'title'  => 'Galeri önizleme',
					'fields' => array(
						array( 'id' => 'gallery_eyebrow', 'type' => 'text', 'label' => 'Üst etiket', 'default' => 'Galeri', 'third' => true ),
						array( 'id' => 'gallery_title', 'type' => 'text', 'label' => 'Başlık', 'default' => 'Yüzeylerimizden kareler', 'third' => true ),
						array( 'id' => 'gallery_count', 'type' => 'number', 'label' => 'Görsel sayısı', 'default' => 8, 'min' => 4, 'max' => 16, 'third' => true ),
					),
				),
				array(
					'title'  => 'Ana CTA',
					'fields' => array(
						array( 'id' => 'cta_title', 'type' => 'textarea', 'label' => 'Başlık', 'default' => "PROJENİZ İÇİN DOĞRU YÜZEY\nKAPLAMA ÇÖZÜMÜNÜ BİRLİKTE BELİRLEYELİM.", 'rows' => 2 ),
						array( 'id' => 'cta_text', 'type' => 'textarea', 'label' => 'Açıklama', 'default' => 'Parça bilgilerinizi ve beklentilerinizi paylaşın; malzemenize uygun eloksal veya kaplama yöntemini birlikte netleştirelim.', 'rows' => 2 ),
						array( 'id' => 'cta_image', 'type' => 'image', 'label' => 'Arka plan görseli', 'third' => true ),
						array( 'id' => 'cta_button', 'type' => 'text', 'label' => 'Buton metni', 'default' => 'Teklif Al', 'third' => true ),
						array( 'id' => 'cta_link', 'type' => 'url', 'label' => 'Buton bağlantısı', 'default' => '/teklif-al/', 'third' => true ),
					),
				),
				array(
					'title'  => 'Blog, referanslar, iletişim',
					'fields' => array(
						array( 'id' => 'blog_eyebrow', 'type' => 'text', 'label' => 'Blog üst etiket', 'default' => 'Blog', 'half' => true ),
						array( 'id' => 'blog_title', 'type' => 'text', 'label' => 'Blog başlığı', 'default' => 'TEKNİK BİLGİ MERKEZİ', 'half' => true ),
						array( 'id' => 'refs_title', 'type' => 'text', 'label' => 'Referanslar başlığı', 'default' => 'Birlikte çalıştığımız firmalar', 'half' => true ),
						array( 'id' => 'contact_title', 'type' => 'text', 'label' => 'İletişim CTA başlığı', 'default' => 'Parçalarınız için bizimle iletişime geçin.', 'half' => true ),
						array( 'id' => 'contact_text', 'type' => 'textarea', 'label' => 'İletişim CTA metni', 'default' => 'Telefon, e-posta veya WhatsApp üzerinden ulaşabilir; teknik resim ya da fotoğraflarınızla teklif talebi oluşturabilirsiniz.', 'rows' => 2 ),
					),
				),
			),
		),

		// ------------------------------------------------------------------
		'footer'   => array(
			'title'    => 'Footer',
			'icon'     => 'dashicons-editor-insertmore',
			'sections' => array(
				array(
					'title'  => 'Footer içerikleri',
					'desc'   => 'Footer menüleri Görünüm → Menüler\'den yönetilir (Kurumsal, Hizmetler, Hızlı Linkler, Yasal).',
					'fields' => array(
						array( 'id' => 'footer_text', 'type' => 'textarea', 'label' => 'Firma açıklaması', 'default' => 'Savunma sanayi ve makine sanayi başta olmak üzere alüminyum parçalar için istenilen mikron, kalite ve renkte eloksal ve yüzey kaplama çözümleri.', 'rows' => 3 ),
						array( 'id' => 'copyright', 'type' => 'text', 'label' => 'Telif metni ({year} = yıl)', 'default' => '© {year} Can Eloksal. Tüm Hakları Saklıdır.' ),
						array( 'id' => 'footer_cta', 'type' => 'toggle', 'label' => 'Footer üstünde "Teklif Al" bandını göster', 'default' => 1 ),
						array( 'id' => 'prefooter_title', 'type' => 'text', 'label' => 'Teklif bandı başlığı', 'default' => 'Parçalarınız için doğru yüzey çözümünü birlikte belirleyelim.' ),
					),
				),
			),
		),

		// ------------------------------------------------------------------
		'forms'    => array(
			'title'    => 'Formlar',
			'icon'     => 'dashicons-feedback',
			'sections' => array(
				array(
					'title'  => 'Bildirim ve metinler',
					'fields' => array(
						array( 'id' => 'notify_email', 'type' => 'email', 'label' => 'Bildirim e-postası (boşsa iletişim e-postası)', 'half' => true ),
						array( 'id' => 'form_rate_limit', 'type' => 'number', 'label' => 'IP başına 10 dakikada en fazla gönderim', 'default' => 5, 'min' => 1, 'max' => 50, 'half' => true ),
						array( 'id' => 'contact_success', 'type' => 'text', 'label' => 'İletişim formu başarı mesajı', 'default' => 'Mesajınız alındı. En kısa sürede size dönüş yapacağız.' ),
						array( 'id' => 'quote_success', 'type' => 'text', 'label' => 'Teklif formu başarı mesajı', 'default' => 'Teklif talebiniz alındı. Teknik ekibimiz inceleyip size dönüş yapacaktır.' ),
						array( 'id' => 'kvkk_label', 'type' => 'text', 'label' => 'KVKK onay metni ({link} = KVKK sayfası bağlantısı)', 'default' => '{link} kapsamında kişisel verilerimin işlenmesini kabul ediyorum.' ),
					),
				),
				array(
					'title'  => 'Teklif formu seçenekleri',
					'fields' => array(
						array( 'id' => 'quote_materials', 'type' => 'lines', 'label' => 'Malzeme türleri', 'rows' => 5, 'default' => "Alüminyum (6xxx serisi)\nAlüminyum (7xxx serisi)\nAlüminyum (2xxx serisi)\nAlüminyum (5xxx serisi)\nDöküm alüminyum\nBilinmiyor / Danışmak istiyorum" ),
						array( 'id' => 'quote_surfaces', 'type' => 'lines', 'label' => 'Talep edilen yüzey', 'rows' => 4, 'default' => "Mat\nParlak\nSaten\nTeknik resme göre" ),
						array( 'id' => 'quote_max_mb', 'type' => 'number', 'label' => 'Dosya başına en fazla boyut (MB)', 'default' => 10, 'min' => 1, 'max' => 50, 'half' => true ),
						array( 'id' => 'quote_max_files', 'type' => 'number', 'label' => 'En fazla dosya sayısı', 'default' => 5, 'min' => 1, 'max' => 10, 'half' => true ),
					),
				),
			),
		),

		// ------------------------------------------------------------------
		'seo'      => array(
			'title'    => 'SEO & Analitik',
			'icon'     => 'dashicons-chart-line',
			'sections' => array(
				array(
					'title'  => 'Varsayılan SEO',
					'desc'   => 'Her sayfa, hizmet ve yazının kendi SEO kutusu vardır; boş bırakılan alanlarda bu değerler kullanılır. Yoast SEO veya Rank Math etkinse tema SEO çıktısını otomatik devre dışı bırakır.',
					'fields' => array(
						array( 'id' => 'seo_home_title', 'type' => 'text', 'label' => 'Ana sayfa başlığı', 'default' => 'Can Eloksal | Alüminyum Eloksal ve Yüzey Kaplama — Sakarya' ),
						array( 'id' => 'seo_description', 'type' => 'textarea', 'label' => 'Varsayılan meta açıklama', 'rows' => 2, 'default' => 'Can Eloksal; savunma ve makine sanayi için istenilen mikron, kalite ve renkte naturel, renkli ve siyah eloksal, alodin, kromat ve kuru film yağlama kaplama hizmetleri sunar.' ),
						array( 'id' => 'seo_separator', 'type' => 'select', 'label' => 'Başlık ayracı', 'default' => '|', 'options' => array( '|' => '|', '—' => '—', '-' => '-', '·' => '·' ), 'third' => true ),
						array( 'id' => 'og_image', 'type' => 'image', 'label' => 'Varsayılan paylaşım görseli (1200×630)', 'third' => true ),
						array( 'id' => 'twitter_site', 'type' => 'text', 'label' => 'X (Twitter) kullanıcı adı', 'third' => true ),
					),
				),
				array(
					'title'  => 'Analitik & doğrulama',
					'desc'   => 'Analitik kodları yalnızca ziyaretçi çerez bandında onay verirse yüklenir.',
					'fields' => array(
						array( 'id' => 'ga4_id', 'type' => 'text', 'label' => 'Google Analytics 4 ölçüm kimliği (G-XXXX)', 'third' => true ),
						array( 'id' => 'gtm_id', 'type' => 'text', 'label' => 'Google Tag Manager (GTM-XXXX)', 'third' => true ),
						array( 'id' => 'gsc_verification', 'type' => 'text', 'label' => 'Search Console doğrulama kodu (content değeri)', 'third' => true ),
					),
				),
			),
		),

		// ------------------------------------------------------------------
		'smtp'     => array(
			'title'    => 'SMTP',
			'icon'     => 'dashicons-email-alt',
			'sections' => array(
				array(
					'title'  => 'E-posta gönderimi',
					'desc'   => 'Şifre veritabanına şifrelenmiş olarak kaydedilir. Daha güvenli kurulum için wp-config.php içinde CE_SMTP_PASSWORD sabitini tanımlayabilirsiniz; tanımlıysa o kullanılır.',
					'fields' => array(
						array( 'id' => 'smtp_enabled', 'type' => 'toggle', 'label' => 'SMTP ile gönder', 'default' => 0 ),
						array( 'id' => 'smtp_host', 'type' => 'text', 'label' => 'Sunucu (host)', 'half' => true ),
						array( 'id' => 'smtp_port', 'type' => 'number', 'label' => 'Port', 'default' => 587, 'min' => 1, 'max' => 65535, 'quarter' => true ),
						array( 'id' => 'smtp_encryption', 'type' => 'select', 'label' => 'Şifreleme', 'default' => 'tls', 'options' => array( 'tls' => 'TLS (587)', 'ssl' => 'SSL (465)', 'none' => 'Yok' ), 'quarter' => true ),
						array( 'id' => 'smtp_username', 'type' => 'text', 'label' => 'Kullanıcı adı', 'half' => true ),
						array( 'id' => 'smtp_password', 'type' => 'password', 'label' => 'Şifre', 'half' => true ),
						array( 'id' => 'smtp_from_email', 'type' => 'email', 'label' => 'Gönderen e-posta', 'half' => true ),
						array( 'id' => 'smtp_from_name', 'type' => 'text', 'label' => 'Gönderen adı', 'default' => 'Can Eloksal', 'half' => true ),
						array( 'id' => 'smtp_test', 'type' => 'heading', 'label' => '', 'html' => '<button type="button" class="button ce-smtp-test">Test e-postası gönder</button> <span class="ce-smtp-test__result" role="status"></span><p class="description">Önce ayarları kaydedin; test e-postası profil e-postanıza gönderilir.</p>' ),
					),
				),
			),
		),

		// ------------------------------------------------------------------
		'privacy'  => array(
			'title'    => 'Çerez & KVKK',
			'icon'     => 'dashicons-shield',
			'sections' => array(
				array(
					'title'  => 'Çerez bandı',
					'fields' => array(
						array( 'id' => 'cookie_enabled', 'type' => 'toggle', 'label' => 'Çerez bandını göster', 'default' => 1 ),
						array( 'id' => 'cookie_text', 'type' => 'textarea', 'label' => 'Metin', 'rows' => 3, 'default' => 'Sitemizde yalnızca sitenin çalışması için gerekli çerezler kullanılır. Analitik çerezler yalnızca onay vermeniz halinde etkinleşir.' ),
						array( 'id' => 'kvkk_page', 'type' => 'page', 'label' => 'KVKK aydınlatma metni sayfası', 'third' => true ),
						array( 'id' => 'privacy_page', 'type' => 'page', 'label' => 'Gizlilik politikası sayfası', 'third' => true ),
						array( 'id' => 'cookie_page', 'type' => 'page', 'label' => 'Çerez politikası sayfası', 'third' => true ),
					),
				),
			),
		),
	);

	return $schema;
}

/**
 * Şemadan varsayılan değerleri çıkarır.
 *
 * @return array
 */
function ce_option_defaults() {
	static $defaults = null;
	if ( null !== $defaults ) {
		return $defaults;
	}
	$defaults = array();
	foreach ( ce_options_schema() as $tab ) {
		foreach ( $tab['sections'] as $section ) {
			foreach ( $section['fields'] as $field ) {
				if ( 'heading' === $field['type'] ) {
					continue;
				}
				if ( 'sections' === $field['type'] ) {
					$defaults[ $field['id'] ] = array_map(
						static function ( $key ) {
							return array( 'key' => $key, 'on' => 1 );
						},
						array_keys( $field['choices'] )
					);
					continue;
				}
				$defaults[ $field['id'] ] = $field['default'] ?? ( in_array( $field['type'], array( 'repeater', 'gallery' ), true ) ? array() : '' );
			}
		}
	}
	return $defaults;
}
