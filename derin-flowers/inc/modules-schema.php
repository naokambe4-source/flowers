<?php
/**
 * Operasyon modüllerinin ayarları: ürün kodu, varyasyon şablonları, başlık havuzu,
 * görsel SEO, kapasite & çalışma modları, bakım modu, arşiv, kurye ekranı,
 * müşteri deneyimi, bildirim / e-fatura bağlantıları, lokasyon SEO.
 *
 * @package DerinFlowers
 */

defined( 'ABSPATH' ) || exit;

/**
 * Şemaya modül ayarlarını ekle.
 *
 * @param array $schema Şema.
 * @return array
 */
function df_modules_schema( $schema ) {
	$schema['product']['groups'][] = array(
		'title'  => 'Ürün kodu, varyasyon şablonları & başlık havuzu',
		'desc'   => 'Yeni ürünler kaydedilirken ürün kodu (SKU) boşsa otomatik verilir: AYZ0001, AYZ0002… Varyasyonlar ana kodun devamını alır (AYZ0001-21). Varyasyon şablonu ürün düzenleme ekranındaki "Hazır Varyasyon" kutusundan uygulanır.',
		'fields' => array(
			array( 'id' => 'sku_auto', 'type' => 'toggle', 'label' => 'Otomatik ürün kodu', 'default' => 1, 'third' => true ),
			array( 'id' => 'sku_prefix', 'type' => 'text', 'label' => 'Kod öneki', 'default' => 'AYZ', 'third' => true ),
			array( 'id' => 'sku_digits', 'type' => 'number', 'label' => 'Rakam sayısı', 'default' => 4, 'min' => 3, 'max' => 8, 'third' => true ),
			array( 'id' => 'var_templates', 'type' => 'lines', 'label' => 'Varyasyon şablonları (Şablon adı | Özellik adı | Seçenekler virgülle)', 'default' => "Gül adedi | Adet | 11 Gül, 21 Gül, 41 Gül, 51 Gül, 101 Gül\nBoy | Boy | Standart, Büyük, Premium\nRenk | Renk | Kırmızı, Beyaz, Pembe, Karışık", 'rows' => 4, 'code' => true ),
			array( 'id' => 'title_pool', 'type' => 'lines', 'label' => 'Ürün başlık havuzu (her satır bir isim)', 'default' => '', 'rows' => 5, 'half' => true, 'desc' => 'Yeni ürün eklerken bu isimlerden seçilebilir; daha önce kullanılan isimler işaretlenir ve aynı başlık tekrar kullanılırsa uyarı verilir.' ),
			array( 'id' => 'title_dupe_warn', 'type' => 'toggle', 'label' => 'Aynı ürün başlığı kullanılırsa uyar', 'default' => 1, 'half' => true ),
			array( 'id' => 'img_seo_name', 'type' => 'toggle', 'label' => 'Görsel dosya adlarını SEO uyumlu yap (Türkçe karakter → kırmızı-gul-buketi.jpg)', 'default' => 1, 'half' => true ),
			array( 'id' => 'img_seo_alt', 'type' => 'toggle', 'label' => 'ALT metni boş ürün görsellerine ürün adını yaz', 'default' => 1, 'half' => true ),
			array( 'id' => 'img_max', 'type' => 'number', 'label' => 'Yüklenen görselin en büyük kenarı (px)', 'default' => 2000, 'min' => 1200, 'max' => 4000, 'half' => true, 'desc' => 'Daha büyük görseller bu boyuta küçültülür (WordPress büyük görsel sınırı).' ),
			array( 'id' => 'img_webp', 'type' => 'toggle', 'label' => 'Yeni yüklenen JPEG/PNG görsellerin boyutlarını WebP üret', 'default' => 0, 'half' => true, 'desc' => 'Sunucunuz WebP destekliyorsa açın. LiteSpeed Cache\'in WebP özelliği de kullanılabilir.' ),
		),
	);

	$schema['delivery']['groups'][] = array(
		'title'  => 'Çalışma modu & kapasite',
		'desc'   => 'Yoğun günlerde (14 Şubat, Anneler Günü) siparişleri kontrol altında tutun. Kapasite dolan gün ve saatler takvimde otomatik kapanır.',
		'fields' => array(
			array(
				'id'      => 'df_mode',
				'type'    => 'select',
				'label'   => 'Çalışma modu',
				'default' => 'normal',
				'half'    => true,
				'options' => array(
					'normal' => 'Normal gün',
					'busy'   => 'Yoğun gün (uyarı + ek hazırlık süresi)',
					'future' => 'Sadece ileri tarih (aynı gün kapalı)',
					'closed' => 'Sipariş alımı kapalı (site açık)',
				),
			),
			array( 'id' => 'df_mode_note', 'type' => 'text', 'label' => 'Ziyaretçiye gösterilecek duyuru (boşsa moda göre hazır metin)', 'default' => '', 'half' => true ),
			array( 'id' => 'df_busy_extra', 'type' => 'number', 'label' => 'Yoğun günde ek hazırlık süresi (saat)', 'default' => 2, 'min' => 0, 'max' => 12, 'third' => true ),
			array( 'id' => 'df_cap_day', 'type' => 'number', 'label' => 'Günlük sipariş kapasitesi (0 = sınırsız)', 'default' => 0, 'min' => 0, 'max' => 2000, 'third' => true ),
			array( 'id' => 'df_cap_slot', 'type' => 'number', 'label' => 'Saat aralığı başına kapasite (0 = sınırsız)', 'default' => 0, 'min' => 0, 'max' => 500, 'third' => true ),
			array( 'id' => 'df_cap_dates', 'type' => 'lines', 'label' => 'Özel gün kapasitesi (YYYY-AA-GG | günlük kapasite)', 'default' => '', 'rows' => 3, 'half' => true, 'desc' => 'Örn. 2027-02-14 | 120' ),
			array( 'id' => 'df_sameday_off', 'type' => 'toggle', 'label' => 'Bugün için aynı gün teslimatı kapat (diğer günler açık)', 'default' => 0, 'half' => true ),
		),
	);
	$schema['delivery']['groups'][] = array(
		'title'  => 'Kurye ekranı & teslim fotoğrafı',
		'desc'   => 'Her kuryenin kendine özel, şifresiz bir telefon ekranı vardır (Yönetim Paneli → Kurye Yönetimi → "Kurye ekranı" bağlantısı). Kurye siparişlerini görür, "Yola çıktım" ve "Teslim ettim" der, teslim fotoğrafı çeker.',
		'fields' => array(
			array( 'id' => 'courier_screen', 'type' => 'toggle', 'label' => 'Kurye ekranı açık', 'default' => 1, 'half' => true ),
			array( 'id' => 'courier_photo_req', 'type' => 'toggle', 'label' => 'Teslimde fotoğraf zorunlu', 'default' => 0, 'half' => true ),
			array( 'id' => 'courier_photo_customer', 'type' => 'toggle', 'label' => 'Teslim fotoğrafını müşteri sipariş takipte görsün', 'default' => 0, 'half' => true ),
			array( 'id' => 'courier_days', 'type' => 'number', 'label' => 'Kurye ekranında kaç gün ileri görünsün', 'default' => 1, 'min' => 0, 'max' => 7, 'half' => true ),
		),
	);
	$schema['delivery']['groups'][] = array(
		'title'  => 'Sipariş arşivi',
		'desc'   => 'Teslim edilen siparişler belirtilen gün sonra "Arşiv" durumuna alınır; aktif sipariş listesinden çıkar, raporlarda sayılmaya devam eder. Siparişler → Arşiv sekmesinden ya da sipariş numarasıyla aranarak her zaman bulunur.',
		'fields' => array(
			array( 'id' => 'archive_on', 'type' => 'toggle', 'label' => 'Teslim edilenleri otomatik arşivle', 'default' => 1, 'half' => true ),
			array( 'id' => 'archive_days', 'type' => 'number', 'label' => 'Teslimden kaç gün sonra (0 = hemen)', 'default' => 2, 'min' => 0, 'max' => 60, 'half' => true ),
		),
	);
	$schema['delivery']['groups'][] = array(
		'title'  => 'Müşteri deneyimi & bildirimler',
		'desc'   => 'Bildirim bağlantısı: sipariş olaylarında (yeni sipariş, hazırlanıyor, yolda, teslim edildi) seçtiğiniz adrese JSON gönderilir; SMS firması, Zapier/Make veya kendi sisteminiz bağlanabilir. E-fatura bağlantısı: teslim edilen sipariş e-fatura sağlayıcınızın adresine imzalı (HMAC-SHA256) olarak aktarılır.',
		'fields' => array(
			array( 'id' => 'review_url', 'type' => 'url', 'label' => 'Google yorum bağlantısı', 'half' => true, 'desc' => 'Google İşletme Profili → "Yorum iste" bağlantısı.' ),
			array( 'id' => 'review_on', 'type' => 'toggle', 'label' => 'Teslimden sonra müşteriye yorum daveti e-postası', 'default' => 1, 'half' => true ),
			array( 'id' => 'review_text', 'type' => 'textarea', 'label' => 'Yorum daveti metni', 'default' => "Çiçekleriniz teslim edildi, sevdiklerinizi mutlu ettiysek ne mutlu bize!\nDeneyiminizi birkaç kelimeyle paylaşırsanız çok seviniriz:", 'rows' => 2 ),
			array( 'id' => 'hook_url', 'type' => 'url', 'label' => 'Bildirim bağlantısı (webhook — SMS / otomasyon)', 'half' => true ),
			array( 'id' => 'hook_secret', 'type' => 'text', 'label' => 'Bildirim gizli anahtarı (opsiyonel)', 'half' => true ),
			array( 'id' => 'efatura_url', 'type' => 'url', 'label' => 'E-fatura sağlayıcı bağlantısı', 'half' => true ),
			array( 'id' => 'efatura_secret', 'type' => 'text', 'label' => 'E-fatura gizli anahtarı', 'half' => true ),
		),
	);

	$schema['admin']['groups'][] = array(
		'title'  => 'Bakım modu',
		'desc'   => 'Açıkken ziyaretçiler bakım sayfası görür (arama motorlarına "geçici" 503 bildirilir). Yöneticiler siteyi normal görür. Sadece sipariş alımını durdurmak için Teslimat → Çalışma modu → "Sipariş alımı kapalı" kullanın.',
		'fields' => array(
			array( 'id' => 'maint_on', 'type' => 'toggle', 'label' => 'Bakım modu', 'default' => 0, 'half' => true ),
			array( 'id' => 'maint_title', 'type' => 'text', 'label' => 'Başlık', 'default' => 'Kısa bir aradayız', 'half' => true ),
			array( 'id' => 'maint_text', 'type' => 'textarea', 'label' => 'Metin', 'default' => 'Sitemizi sizin için yeniliyoruz. Siparişleriniz için bize telefon ve WhatsApp üzerinden ulaşabilirsiniz.', 'rows' => 2 ),
		),
	);

	$schema['seo'] = array(
		'title'  => 'Lokasyon SEO',
		'icon'   => 'dashicons-location-alt',
		'groups' => array(
			array(
				'title'  => 'İlçe sayfaları',
				'desc'   => 'Her ilçe için otomatik "{ilce} Çiçek Siparişi" sayfası oluşturulur (ör. /cicek-siparisi/bornova/). Sayfalar site haritasına eklenir, Google için yerel işletme bilgisi taşır. İlçeye özel metin yazdıkça Google sıralaması güçlenir.',
				'fields' => array(
					array( 'id' => 'loc_on', 'type' => 'toggle', 'label' => 'İlçe sayfaları açık', 'default' => 1, 'half' => true ),
					array( 'id' => 'loc_base', 'type' => 'text', 'label' => 'Adres öneki', 'default' => 'cicek-siparisi', 'half' => true ),
					array( 'id' => 'loc_items', 'type' => 'lines', 'label' => 'İlçeler (İlçe | ilçeye özel metin — opsiyonel)', 'default' => "Konak\nKarşıyaka\nBornova\nBayraklı\nBuca\nKarabağlar\nBalçova\nNarlıdere\nGaziemir\nÇiğli\nGüzelbahçe\nUrla\nÇeşme\nSeferihisar", 'rows' => 10 ),
					array( 'id' => 'loc_title', 'type' => 'text', 'label' => 'Sayfa başlığı', 'default' => '{ilce} Çiçek Siparişi', 'half' => true ),
					array( 'id' => 'loc_meta', 'type' => 'text', 'label' => 'Google açıklaması (meta description)', 'default' => '{ilce} çiçek siparişi: aynı gün teslimat, taze buketler, güller ve aranjmanlar. {ilce} adreslerine özenle hazırlanan çiçekler.', 'half' => true ),
					array( 'id' => 'loc_intro', 'type' => 'textarea', 'label' => 'Genel metin (ilçeye özel metin yoksa)', 'default' => "{ilce}'deki sevdiklerinize çiçek göndermek hiç bu kadar kolay olmamıştı. Atölyemizde günlük taze çiçeklerle hazırlanan buketler, güller ve aranjmanlar, seçtiğiniz gün ve saat aralığında {ilce} adresine özenle teslim edilir.\nSaat {cutoff}'a kadar verilen siparişler aynı gün yola çıkar. Siparişinize ücretsiz, kişiye özel not kartı ekleyebilirsiniz.", 'rows' => 4 ),
					array( 'id' => 'loc_cat', 'type' => 'product_cat', 'label' => 'Sayfada gösterilecek ürün kategorisi (boşsa çok satanlar)', 'half' => true ),
					array( 'id' => 'loc_count', 'type' => 'number', 'label' => 'Ürün sayısı', 'default' => 8, 'min' => 4, 'max' => 24, 'half' => true ),
					array( 'id' => 'loc_home_links', 'type' => 'toggle', 'label' => 'Ana sayfadaki ilçe kısayolları ilçe sayfalarına gitsin', 'default' => 1, 'half' => true ),
					array( 'id' => 'loc_llms', 'type' => 'toggle', 'label' => 'Yapay zekâ arama motorları için /llms.txt', 'default' => 1, 'half' => true ),
				),
			),
		),
	);
	return $schema;
}
add_filter( 'df_options_schema', 'df_modules_schema' );
