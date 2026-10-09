# Cilt Rotası — WordPress Teması

Cilt Rotası için hazırlanmış premium skincare bilgi platformu teması. SEO, AEO (yanıt motorları) ve GEO (üretken yapay zekâ motorları) odaklıdır. Her şey özel yönetim panelinden ve sitedeki canlı düzenleyiciden yönetilir.

## Kurulum

1. Depodaki **`cilt-rotasi.zip`** dosyasını **Görünüm → Temalar → Yeni ekle → Tema yükle** ile yükle ve etkinleştir.
   (FTP ile kuruyorsan `cilt-rotasi` klasörünü `wp-content/themes/` içine kopyala.)
2. **Ayarlar → Genel**: Site dili **Türkçe** olsun (tarihler ve büyük harfler için).
3. **Cilt Rotası → Araçlar & Kurulum → Kurulumu çalıştır**. Tek tıkla şunları kurar:
   - kategoriler (Cilt Yapısı, Cilt Problemleri, Cilt Bakım Rutini, İçerikler, Ürünler),
   - cilt sorunları, cilt tipleri, içerik grupları ve ürün türleri,
   - sayfalar: Ana Sayfa, Rehberler, Cilt Testi, Kaydedilenler, Hakkımızda, Yayın İlkeleri, İletişim, Gizlilik, Çerez Politikası, Kullanım Koşulları,
   - ana menü (Cilt Problemleri mega menüsüyle) ve 3 footer menüsü,
   - `/%postname%/` kalıcı bağlantıları,
   - isteğe bağlı örnek içerikler: 9 rehber, 8 sözlük içeriği, 4 ürün incelemesi (kısa cevap, SSS, adımlar ve kaynaklarıyla).
4. Görseller varsayılan olarak mevcut tasarımındaki hizliresim adreslerinden gelir. Kalıcı olması için **Tema Ayarları** veya canlı düzenleyicideki **Görseli değiştir** ile medya kütüphanene yükle.
5. Yasal sayfalar şablon metinlerdir; yayınlamadan önce bir hukukçuyla gözden geçir. Örnek içeriklerdeki kaynak bağlantılarını da kontrol et.

## Yönetim paneli

Girişten sonra doğrudan **Cilt Rotası → Kontrol Paneli** açılır. Üstte kocaman **“Aslı, seni seviyorum ♥”** yazar; her girişte rastgele bir sevgi notu gösterilir, arkada kalpler uçuşur. Metinler **Tema Ayarları → Yönetim Paneli** sekmesinden değiştirilebilir.

| Sayfa | İçerik |
|---|---|
| Kontrol Paneli | Karşılama, istatistikler (rehber, sözlük, ürün, taslak, abone, okunma), SEO·AEO·GEO sağlık skoru, son düzenlenenler, en çok okunanlar, içerik fırsatları (sonuçsuz aramalar), GEO durumu, kurulum kontrol listesi |
| Tema Ayarları | 10 sekme: Marka & Görünüm, Header & Menü, Hero, Ana Sayfa, Makale & Sayfalar, Cilt Testi, SEO·AEO·GEO, Footer & Sosyal, Yönetim Paneli, Özel Kod. Ayar arama kutusu, sürükle-bırak bölüm sıralama, tekrarlayıcı alanlar |
| SEO Denetimi | Her içerik 0–100 puanlanır; eksikler SEO / AEO / GEO olarak etiketlenir |
| ✎ Canlı Düzenle | Sitede metne tıkla-yaz, görsel değiştir, bölümleri ▲▼ taşı / gizle, renk-yazı tipi-köşe paneli |
| Bülten Aboneleri | Liste, silme, CSV indirme |
| Arama Analitiği | En çok arananlar, sonuçsuz aramalar → tek tıkla yazı oluştur |
| Araçlar & Kurulum | Kurulum sihirbazı, JSON dışa/içe aktarma, sıfırlama, son 15 kaydın geçmişi (geri yükleme) |

Ayrıca: markalı giriş ekranı, tüm yönetime Cilt Rotası görünümü, üst çubukta karşılama, yazı listelerinde SEO skoru ve okunma sütunu.

## SEO · AEO · GEO

- **SEO:** başlık, meta açıklama, canonical, robots (noindex kuralları), Open Graph, Twitter kartı, önceki/sonraki bağlantılar, doğrulama kodları (Google, Bing, Yandex, Pinterest), noindex içerikleri dışlayan ve `lastmod` ekleyen site haritası, breadcrumb, otomatik alt metin, ek sayfası yönlendirmesi, gecikmeli GA4.
- **Yapılandırılmış veri (@graph):** Organization, WebSite + SearchAction, WebPage / MedicalWebPage (reviewedBy, lastReviewed, speakable), BlogPosting / Article (citation, about, keywords), Person (E-E-A-T), BreadcrumbList, FAQPage, HowTo, DefinedTerm + DefinedTermSet, Product + Review, CollectionPage + ItemList.
- **AEO:** her yazıda *Kısa cevap* kutusu, *Akılda kalsın* maddeleri, SSS, adım adım bölümü; ana sayfada SSS.
- **GEO:** `/llms.txt`, `/llms-full.txt`, her yazının Markdown sürümü (`?format=md`), yapay zekâ botları için robots.txt kuralları (izin ver / yalnızca arama botları / engelle), kaynak listesi, yazar ve uzman kontrolü bilgileri.
- Yazı düzenleme ekranında canlı Google önizlemesi, karakter sayaçları ve anlık kontrol listesi bulunan **SEO · AEO · GEO** kutusu vardır.
- Yoast, Rank Math, AIOSEO veya SEOPress etkinse tema meta etiketlerini kendiliğinden kapatır; sözlük, SSS, HowTo, ürün şemaları ve GEO dosyaları çalışmaya devam eder.

## v1.3 — yerleşim, hız ve SEO

- Header düz (opak) zeminli; aşağı kaydırınca gizlenir, yukarı kaydırınca geri gelir — içerik header'ın altında kalmaz.
- Mobil alt menü varsayılan kapalı (Panel › Header › Mobil alt menü). Mobil header 64px.
- Sözlük filtre çubuğu yalnızca masaüstünde yapışkan; mobilde ekranı kaplamaz. Breadcrumb mobilde kaymaz, alt satıra iner.
- **Araçlar › Görselleri siteye aktar**: harici (hizliresim vb.) görselleri medya kütüphanesine indirir; site otomatik olarak küçük, WebP ve srcset'li sürümleri kullanır (2–3 MB PNG → 50–200 KB). Ayarlar değişmez.
- Yüklenen görsellerin boyutları WebP üretilir (Panel › Genel › WebP).
- Hero görseli tam boyut yerine 1600px sürümle ve srcset'li ön yüklemeyle gelir.
- Tarihli kalıcı bağlantılarda tema içi “/yazi-adi/” bağlantıları yönlendirmesiz gerçek adrese gider.
- Kontrol paneli › **SEO ve hız sağlığı**: harici görsel, kalıcı bağlantı yapısı, alan adı/alt klasör ve yazar adı kontrolleri.

## v1.2 revizyonları (1.10.26 listesi)

- Header: “İçerikler” → **Sözlük**, “Ürün Rehberi” → **Blog** (/rehberler/); “İncele” butonu kaldırıldı.
- Hero: “Bağımsız Kozmetik Platformu” etiketi kaldırıldı; butonlar “Uzman Bilgilerini Oku” → Blog ve “Cildini Tanı” → Cilt Yapısı (v1.2.2).
- Öne çıkan blok: “Blog Yazısını Oku” → ilgili blog yazısına gider.
- Kategoriler: Cilt Yapısı / Cilt Problemleri / Bakım Rutini header sayfalarına gider; Aktif İçerikler yeni **/aktif-icerikler/** landing sayfasına gider (Sayfalar → Aktif İçerikler; şablon “Aktif İçerikler (landing)”). Sayfaya yazılan metin üstte görünür; altta içerik grupları ve sözlükten öne çıkan aktifler otomatik listelenir.
- Ürün kataloğu ana sayfadan kalktı. **Ürün rehberi** panelde “Ana Sayfa → Ürün rehberi ve kataloğu → Ürün rehberini sitede yayınla” anahtarıyla kapalı: ürün sayfaları ziyaretçileri bloga yönlendirir; arama, site haritası, llms.txt ve sözlükteki ürün bağlantıları gizlenir.
- İhtiyacınıza göre rotalar: yalnızca görsel + açıklama (bağlantı alanı boş bırakıldıkça tıklanmaz).
- Journal → **Blog** (“Cilt Rotası Blog”, “Tüm Blog Yazıları”); “Ürünler” filtresi kaldırıldı.
- Bülten metninden “ürün incelemeleri” kalktı.
- Footer: Kütüphane = header başlıkları; Kurumsal = Hakkımızda + İletişim; Yasal aynı.
- Header ana sayfada da baştan dolu (krem) zeminli; şeffaf başlangıç istenirse Header ayarlarından açılabilir.
- Canlı düzenleyici yalnızca **Düzenle** düğmesine (veya üst çubuktaki Canlı Düzenle’ye) basınca açılır; Vazgeç/Kapat ile tamamen kapanır.
- Mevcut kurulumlar güncellenince bu değişiklikler (menüler dahil) otomatik uygulanır; yüklediğin görseller ve diğer metinler korunur.

## Ön yüz

- Editoryal tasarım dili (v1.1): Cormorant Garamond başlıklar, keskin köşeler, çizgili eyebrow'lar, kare/alt çizgili butonlar, #1A2F25 / #B8905B / #FAF9F5 paleti. Tüm renkler ve yazı tipleri panelden değişir.
- Ana sayfa (14 bölüm, sıralanabilir, açılıp kapatılabilir): tam ekran hero (sol gradyan, Ken Burns), 4'lü kategori kartları (3:4), 50/50 editoryal split, filtreli ürün kataloğu (4:5), manifesto bandı, ihtiyaçlar ızgarası (16:10), filtreli Journal kartları, cilt testi çağrısı, INCI sözlüğü bandı, SSS, koyu yeşil bülten; isteğe bağlı popüler yazılar, problem şeridi ve yayın ilkeleri.
- Ürün rehberi: tür sekmeleri (adetli), editör puanı / A–Z sıralama, 4 sütun katalog, değerlendirme yöntemi bölümü. Ürün sayfası: sabit görsel, yıldız puanı, editör kararı, özellik satırları, öne çıkan içerikler (sözlüğe bağlı), artı/eksi, kullanım ve benzer ürünler.
- İçerik sözlüğü: görselli başlık, öne çıkan 4 içerik, sabit A–Z + grup filtre çubuğu, harf blokları halinde dizin (INCI, işlev, grup, kanıt noktaları), gösterge açıklaması. İçerik sayfası: büyük başlık, kısa cevap, 6 hücreli özellik tablosu, faydalar, uyum rehberi (iyi anlaşır / dikkatli kombinle), bu içeriği barındıran ürünler, geçtiği rehberler.
- Global arama paneli (`/` veya Ctrl+K): Makaleler, İçerikler, Problemler, Ürün rehberleri; klavyeyle gezinme, son aramalar.
- Kaydedilenler (üyelik gerekmez), paylaş, okuma ilerleme çubuğu, sağda sabit içindekiler, sonraki rehber, ilgili içerikler.
- İçerik sözlüğü: A–Z dizini, anlık filtre, grup filtreleri; her içerikte INCI adı, etkili oran, kanıt düzeyi, tahriş potansiyeli, hamilelik bilgisi ve uyumlu içerikler.
- Cilt testi: sorular ve sonuç metinleri panelden düzenlenir.
- Mobilde alt gezinme çubuğu (iPhone güvenli alan uyumlu), yatay kaydırmalı şeritler, 48px dokunma alanları.
- Performans: Tailwind CDN veya ikon fontu yok (satır içi SVG), `defer` betikler, LCP görseli ön yükleme, tembel yükleme, `aspect-ratio`, emoji betikleri kapalı. `prefers-reduced-motion` desteklenir.

## Kısa kodlar

`[cr_kisa_cevap]…[/cr_kisa_cevap]`, `[cr_bilgi]`, `[cr_not]`, `[cr_ipucu]`, `[cr_uyari]`, `[cr_icerik ad="niasinamid"]`, `[cilt_testi]`, `[cr_bulten]`. Blok ekleyicide **Desenler → Cilt Rotası** altında bilgi kutusu, karşılaştırma tablosu, uzman görüşü ve kontrol listesi desenleri bulunur.
