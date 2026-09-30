# Derin Flowers — Premium WordPress + WooCommerce Teması

İzmir'deki premium çiçek butiği için geliştirilmiş, tüm içerik ve tasarımı **WordPress yönetim panelinden** yönetilen tema.

Tema klasörü: [`derin-flowers/`](derin-flowers/)

## Kurulum

1. Depodaki hazır **`derin-flowers.zip`** dosyasını **Görünüm → Temalar → Yeni ekle → Tema yükle** ile yükleyin.
   > Deponun tamamını (GitHub "Download ZIP") yüklemeyin: tema bir klasör içeride kaldığı için WordPress "style.css stil dosyası eksik" hatası verir. Kendiniz zip'leyecekseniz `derin-flowers` klasörünü zip'leyin (zip içinde `derin-flowers/style.css` olmalı). FTP ile kuruyorsanız `derin-flowers` klasörünü `wp-content/themes/` içine kopyalayın.
2. WooCommerce eklentisi etkin olmalıdır.
3. **Derin Flowers → Araçlar & Kurulum → Kurulumu çalıştır** — tek tıkla:
   - ikonlu ürün kategorilerini (Buketler, Güller, Orkideler, Kutuda Çiçek, Vazoda Çiçek, Özel Günler ve alt kategorileri, Söz & Nişan, Koleksiyonlar),
   - Sipariş Takip, Favorilerim, Teslimat Bölgeleri, Hakkımızda, İletişim ve Blog sayfalarını,
   - ana menü ve footer menülerini oluşturur,
   - sepet/ödeme sayfalarını çiçekçi akışına (klasik kısa kod) çevirir ve üyeliği açar.
   Ürün oluşturmaz; ürünler WooCommerce'ten eklenir.
4. **Derin Flowers → Tema Ayarları**'ndan görselleri yükleyin (hero, kategori, banner, hikaye, Instagram…). Görsel eklenmemiş alanlar yöneticiye "görsel ekleyin" ipucu gösteren sade bir zemin gösterir; aynı görsel hiçbir yerde otomatik tekrar edilmez.
5. **WooCommerce → Ayarlar → Genel**: para birimi TRY, binlik ayracı `.` ondalık `,` önerilir.

## Yönetim paneli (Derin Flowers menüsü)

| Sekme | İçerik |
|---|---|
| Görünüm & Marka | Logo (görsel veya metin), 12 renk, başlık/metin fontu, içerik genişliği, köşe yuvarlaklığı, bölüm boşlukları |
| Header | Üst bant metinleri/rengi, logo konumu, yapışkan header, ikonlu **Kategoriler** çekmecesi, arama/hesap/favori/sepet ikonları, menü ikonları |
| Hero | Çoklu slayt: masaüstü + **ayrı mobil görsel**, "görsel + metin" veya "sadece görsel (tıklanabilir)", opsiyonel arka plan videosu, metin konumu, gradyan, metin rengi, odak noktası, otomatik geçiş |
| Ana Sayfa | 13 bölümün **aç/kapat + sürükle-bırak sıralama**sı ve her bölümün tüm içerikleri (kategori seçimi, özel gün bannerları, Signature/En Çok Sevilenler ürün kaynağı — elle seçim, öne çıkan, kategori, en çok satan…) |
| Ürün & Mağaza | Hızlı sepete ekle (varsayılan kapalı → kart ürün detayına götürür), kart ayarları, ürün sayfası notları, güven maddeleri, WhatsApp, bakım/teslimat metinleri, **genel SSS** |
| Teslimat & Ödeme | Bölge listesi (Şehir \| Bölge \| Ücret — sizin İzmir listeniz hazır), mağazalar, saat aralıkları (+ek ücret), kapalı günler/tarihler, son sipariş saati, hazırlık süresi, ücretsiz teslimat limiti, hazır not mesajları |
| Footer & İletişim | Footer metinleri/renk/logo, telefonlar, e-posta, adres, saatler, WhatsApp, harita, sosyal medya |
| Üyelik & Takip | Kayıt ayarları (ad/soyad/telefon/KVKK), giriş sayfası görseli, sipariş takip metinleri |

Araçlar sayfasında ayarları **JSON dışa/içe aktarma** ve sıfırlama da vardır.

## Öne çıkan özellikler

- **Ana sayfa** (istenen sırayla): tam genişlik hero → ince güven şeridi → 4 büyük editorial kategori (3:4) → 3 geniş özel gün banner'ı (16:10) → Signature Collection (4 büyük ürün, 4:5) → tam genişlik Söz & Nişan banner'ı → En Çok Sevilenler → asimetrik %58/%42 ikili koleksiyon → İzmir teslimat (canlı "bugün teslimat için X saat kaldı") → marka hikayesi → Çiçek Rehberi → Instagram → bülten. Tüm ürünler `wc_get_products()` ile WooCommerce'ten gelir; fiyatlar `get_price_html()`.
- **Ürün detayı** tam genişlik: görsel + **ürün videosu** galerisi (MP4 yükleme veya YouTube/Vimeo), büyütme, aynı gün geri sayımı, Sepete Ekle + **Hemen Al**, WhatsApp, tam genişlik açıklama + ürün içeriği/ölçü/bakım, ürüne özel + genel **SSS** (FAQ schema), benzer ürünler, mobilde yapışkan sepet çubuğu.
- **Ödeme**: Adrese / Mağazadan teslim, **takvim** (bugün/yarın kısayolları + aylık takvim; kapalı gün ve geçmiş saatler otomatik kapanır), saat aralıkları, gönderici ve alıcı bilgileri (ülke kodlu telefon), aranabilir bölge listesi — ücret siparişe otomatik eklenir, kategoriye göre hazır mesajlı **çiçek notu** + canlı kart önizlemesi. Tüm bilgiler siparişe, yönetici ekranına, e-postalara ve sipariş listesine ("Teslimat" sütunu) yazılır; **not kartı yazdırma** butonu.
- **Sipariş takip**: `[derin_siparis_takip]` — sipariş no + telefon/e-posta ile durum zaman çizelgesi. Yeni sipariş durumları: **Hazırlanıyor**, **Yolda** (Tamamlandı = Teslim Edildi); müşteriye otomatik bilgilendirme notu.
- **Üyelik**: sekmeli Giriş / Üye Ol, ad-soyad-telefon-KVKK alanları, Hesabım kısayol kartları.
- **Favoriler** (üye + ziyaretçi), canlı ürün araması, mini sepet çekmecesi, ürün kategorisine ikon + kapak görseli, menü öğelerine ikon seçimi, bülten aboneleri + CSV.

## v1.4 — Kampanya bannerları ve yeni ana sayfa görünümü

- **Kampanya bannerları:** solda fotoğraf, sağda renkli zemin; başlık üstü ikon, yaprak süsleri, iki satırlı başlık (son satır ayrı renk), alt metin ve kamyon ikonlu "Aynı Gün Teslimat" düğmesi, istenirse köşe rozeti (ör. "SEÇİLİ ÜRÜNLERDE %20"). Her şey banner genişliğine göre ölçeklenir; mobilde de oranlar aynı kalır. Başlık yazı tipi (kalın düz / tırnaklı zarif) ve boyutu banner başına seçilir.
- **Araçlar → Hazır kampanya bannerları:** 10 hazır banner (Sevgiliye, Söz/Nişan/Düğün, Açılış, Ev Hediyesi, Özür, Geçmiş Olsun, Anneler Günü, Mevsim, Saksı, İndirimli); sadece fotoğraf eklenir.
- **Hazır ana sayfa düzeni** gönderilen tasarıma göre: el yazısı notlu hero, pembe özel gün kartları, yuvarlak popüler kategoriler, yatay kategori bannerları, 5'li Signature, Söz & Nişan banner'ı, footer üstünde Instagram · Blog · Sosyal şeridi; header'da arama kutusu, sıkı bölüm aralıkları.
- Yeni ikonlar: alyans, fiyonk, ev, geçmiş olsun, özür, indirim, çiçek, saksı, anneler günü, yaprak süsü.

## v1.3 — Sabit menü, sipariş kartı, her sayfa düzenlenebilir

- **Sabit sol menü:** Yönetim Paneli sayfaları dahil her ekranda aynı, düzenli WordPress menüsü (Yönetim Paneli, Siparişler, Ürünler, Kullanıcılar, Tasarım Stüdyosu, Site Ayarları, Sayfalar, Blog, Görseller, WooCommerce). **Sade menü** müşteriye gerekmeyen öğeleri gizler; menünün altındaki *Tüm menüyü göster* ile (kullanıcı bazında) açılır. Yönetim & Giriş sekmesinden kapatılabilir.
- **Çiçek Siparişi kartı:** sipariş ekranının en üstünde teslimat günü/saati (Bugün/Yarın rozeti), gönderici ve alıcı (Ara / WhatsApp), adres (Haritada aç), kart notu, kurye atama + adresi WhatsApp ile gönderme, kart/fiş yazdırma.
- **Tasarım Stüdyosu:** önizleme arka planda yenilenir (kayma ve yanıp sönme yok); *Sayfa* sekmesi açık sayfaya göre değişir: ürün (ad, fiyat, indirim, stok, kısa açıklama, görsel), kategori (ad, açıklama, görsel), sayfa (başlık), ayrıca "tam düzenle" editörü stüdyo içinde açılır. Header, footer, giriş ve sipariş takip yazıları her sayfada tıklanarak düzenlenir.

## v1.2 — Tasarım Stüdyosu ve yeni yönetim görünümü

- **Tasarım Stüdyosu** (menüde *Tasarım Stüdyosu*, sitede sol alttaki **✎ Düzenle** düğmesi ya da üst çubuk): solda koyu ayar paneli, sağda sitenin canlı önizlemesi.
  - Önizlemede yazılara tıklayıp yazın, **Görseli değiştir** ile medya kütüphanesinden seçin.
  - **Bölümler** sekmesi: sürükle-bırak sıralama, aç/kapat, her bölüm için **Düzenle** (içerik + *Bölüm tasarımı*: zemin/yazı rengi, üst/alt boşluk, başlık hizası ve boyutu, mobilde/masaüstünde gizle).
  - **Site** sekmesi: header, footer, renkler, yazı tipleri, ürün sayfası, teslimat, Hakkımızda/İletişim dahil tüm tema ayarları; özel CSS.
  - Masaüstü / tablet / mobil önizleme, geri al / ileri al (Ctrl+Z / Ctrl+Y), sayfa seçici (ana sayfa, Hakkımızda, İletişim, mağaza, kategori, ürün, sepet, hesap…).
  - Değişiklikler önce **taslak** olarak kaydedilir; ziyaretçiler görmez. **Yayınla** ile canlıya alınır (LiteSpeed / WP Rocket önbelleği temizlenir), **Vazgeç** ile silinir.
- **Yönetim görünümü:** tüm WordPress yönetimi koyu menü + marka başlığıyla Derin Flowers görünümünde (Yönetim & Giriş sekmesinden kapatılabilir).
- Sayfalar listesinde **Tasarım Stüdyosu'nda aç** bağlantısı.

## v1.1 — Yönetim Paneli, Canlı Düzenleyici, yeni ana sayfa bölümleri

- **Yönetim Paneli** (WordPress menüsünde en üstte; girişten sonra otomatik açılır): yeni / hazırlanıyor / kuryede / bugün teslim edilen sipariş kartları, son siparişler (teslimat bölgesi, tarih-saat, yazdırma durumu), hızlı işlemler, son 7 gün ciro grafiği (+ tablo görünümü), son 30 günün en çok satanları, sistem durumu, SEO & indeksleme kontrolleri, bildirimler.
  - **İzmir Teslimat:** seçilen günün teslimatları saat aralığına göre, bölge özetiyle.
  - **Yazdırma Merkezi:** günün not kartlarını (A6) ve teslimat fişlerini (A5) toplu yazdırır, siparişi "Yazdırıldı" olarak işaretler.
  - **Kurye Yönetimi:** kurye atama (Teslimat & Ödeme → Kuryeler), adresi kuryeye tek dokunuşla WhatsApp ile gönderme; istenirse durum otomatik "Yolda".
- **Canlı Düzenleyici:** ön yüzde admin çubuğundaki **✎ Canlı Düzenle** ile yazılara tıklayıp düzenleyin, görselleri değiştirin, bölümleri ▲▼ taşıyın / gizleyin, **Kaydet**. Değişiklikler ham HTML olarak değil tema ayarlarına yazılır; ürünler, fiyatlar, header ve footer hep canlı kalır.
- **Yeni ana sayfa bölümleri:** yuvarlak **Popüler Kategoriler**, renkli **kategori bannerları** (fotoğraf + büyük başlık + "Aynı Gün Teslimat" etiketi ya da hazır banner görseli), kart tipi özel gün bannerları, 5'li Signature Collection (sepet ikonu + ürün kodu), ortalı Söz & Nişan banner'ı. **Araçlar → Hazır ana sayfa düzeni** tek tıkla bu kurguyu uygular.
- **Hakkımızda & İletişim** artık panelden (Hakkımızda & İletişim sekmesi) ve canlı düzenleyiciden yönetilir; sayfa editörü içeriğini kullanmaz, bu yüzden başka eklentilerin sayfaya kaydettiği HTML header/footer'ı bozamaz.
- **Markalı giriş ekranı** (Yönetim & Giriş sekmesi: logo, yan görsel, metin).

## Kısa kodlar

`[derin_siparis_takip]` · `[derin_favoriler]` · `[derin_teslimat_bolgeleri]` · `[derin_iletisim]` · `[derin_sss]`

## Notlar

- Çiçekçi ödeme alanları WooCommerce'in **klasik** ödeme sayfasında çalışır. Ödeme sayfası blok kullanıyorsa panelde "Tek tıkla dönüştür" uyarısı çıkar.
- Bölge ücreti modunda WooCommerce kargo yöntemleri devre dışı kalır; teslimat ücreti bölge listesinden hesaplanır (panelden kapatılabilir).
- Gerekli sürümler: WordPress 6.2+, WooCommerce 8+, PHP 7.4+.
