# Can Eloksal — Premium Endüstriyel Kurumsal WordPress Teması

Can Eloksal (alüminyum eloksal ve yüzey kaplama) için sıfırdan geliştirilmiş, tüm içerikleri **WordPress yönetim panelinden** veya **blok editöründen** düzenlenen kurumsal tema.

- Ön yüz: bağımlılıksız HTML5 / CSS3 / Vanilla JS (jQuery yok, framework yok)
- Arka uç: WordPress çekirdeği (PHP 8, MySQL/MariaDB, PDO/wpdb prepared statements)
- Fontlar tema içinde (Manrope + Inter, woff2, dış istek yok)

---

## 1. Gereksinimler

| Bileşen | Sürüm |
|---|---|
| PHP | 8.0+ (8.2+ önerilir) |
| MySQL / MariaDB | MySQL 8+ / MariaDB 10.4+ |
| WordPress | 6.2+ |
| PHP eklentileri | `mysqli`, `mbstring`, `fileinfo` (dosya MIME kontrolü — **zorunlu**), `openssl` (SMTP şifresi şifreleme), `gd` veya `imagick` (görsel boyutları + WebP), `zip`, `curl`, `intl` (önerilir) |
| Web sunucusu | Apache (`mod_rewrite`, `mod_headers` önerilir) veya Nginx |

## 2. Kurulum

1. WordPress'i kurun (hosting panelinin tek tık kurulumu veya wordpress.org paketi). Veritabanı, tablo yapısı ve yönetici hesabı WordPress kurulum sihirbazıyla güvenli şekilde oluşturulur; şifreler `wp_users` tablosunda hash'lenmiş tutulur.
2. Temayı yükleyin: **Görünüm → Temalar → Yeni ekle → Tema yükle →** depodaki `can-eloksal.zip`.
   > Deponun tamamını (GitHub "Download ZIP") yüklemeyin. Kendiniz zip'leyecekseniz `can-eloksal` klasörünü zip'leyin (zip içinde `can-eloksal/style.css` olmalı). FTP ile: `can-eloksal` klasörünü `wp-content/themes/` içine kopyalayın.
3. Temayı etkinleştirin.
4. **Can Eloksal → Kurulum & Araçlar → Kurulumu çalıştır.** Tek tıkla oluşturulur:
   - 9 hizmet (Naturel, Kırmızı, Mavi, Sarı, Siyah, Diğer Eloksal, Alodin, Kromat, Kuru Film Yağlama) + 3 hizmet kategorisi (Eloksal, Kimyasal Kaplama, Yüzey İşlem) — içerik, avantajlar, uygulama alanları, teknik bilgi tablosu dahil
   - 4 sektör, hero slaytı, 2 banka hesabı (Halk Bankası, Garanti Bankası)
   - Sayfalar: Ana Sayfa, Hakkımızda, Kalite Politikamız, Galeri, Blog, Banka Hesapları, İletişim, Teklif Al, KVKK, Gizlilik, Çerez
   - 8 teknik blog yazısı + blog kategorileri, galeri kategorileri
   - Ana menü + 4 footer menüsü, statik ana sayfa, `/blog/%postname%/` kalıcı bağlantısı
   - Var olan kayıtlar değiştirilmez; tekrar çalıştırmak yalnızca eksikleri ekler.
   - **Sahte referans, sertifika, kuruluş yılı, kapasite veya rakam üretilmez.**
5. Görselleri yükleyin (bkz. §5). Görsel eklenmeyen alanlarda hizmetin rengine uygun metal yüzey dokusu gösterilir; yönetici giriş yapmışsa üzerinde "Görsel ekleyin" ipucu görünür.
6. **Ayarlar → Genel**: Site dili Türkçe, saat dilimi İstanbul (kurulum aracı saat dilimini ayarlar).
7. Canlıya çıkmadan önce **KVKK / Gizlilik / Çerez** metinlerini hukuk danışmanınıza kontrol ettirin.

## 3. URL yapısı

| Sayfa | URL |
|---|---|
| Ana sayfa | `/` |
| Hakkımızda | `/hakkimizda/` |
| Kalite Politikamız | `/kalite-politikamiz/` |
| Hizmetler | `/hizmetler/` |
| Hizmet detayı | `/hizmet/naturel-eloksal/` |
| Hizmet kategorisi | `/hizmetler/kategori/eloksal/` |
| Galeri | `/galeri/` |
| Blog | `/blog/` · yazı: `/blog/eloksal-nedir/` · kategori: `/blog/kategori/eloksal/` |
| Banka hesapları | `/banka-hesaplari/` |
| İletişim | `/iletisim/` |
| Teklif al | `/teklif-al/` (hizmet ön seçimi: `/teklif-al/?hizmet=siyah-eloksal`) |
| Sitemap / robots | `/sitemap.xml` · `/robots.txt` |

URL'lerde `.php`, `?page=`, `?id=` görünmez.

## 4. Yönetim paneli

| Menü | İçerik |
|---|---|
| **Can Eloksal → Kontrol Paneli** | Toplam hizmet / blog / galeri, yeni iletişim ve teklif sayıları, son talepler, son yazılar, hızlı işlemler, **global arama** (hizmet, blog, sayfa, mesaj, teklif), site durumu |
| **Tema Ayarları** | Marka & Genel (logo, koyu logo, mobil logo, favicon, WebP), İletişim & Sosyal (telefon, WhatsApp, e-posta, adres, harita, sosyal medya), **Ana Sayfa** (12 bölümün aç/kapat + sürükle-bırak sıralaması ve tüm metinleri), Footer, Formlar, SEO & Analitik, SMTP (+ test e-postası), Çerez & KVKK |
| Menü Yönetimi | Görünüm → Menüler (header dropdown + 4 footer menüsü). "Hizmetler" öğesinin alt menüsü aktif hizmetlerden otomatik dolar |
| Sayfalar | Blok editörü + sayfa şablonuna özel bölüm kutuları (Hakkımızda blokları, Kalite ilkeleri, İletişim formu, Teklif yan paneli…) + her sayfada Hero ve SEO kutusu |
| Hero / Slider | Başlık (`|` ile satır), üst etiket, açıklama, masaüstü + mobil görsel, opsiyonel MP4 video, 2 buton, karartma, hizalama, sıra, durum, **önizleme** |
| Hizmetler (+ Kategoriler) | Ad, slug (otomatik/elle), kategori, kısa açıklama, detaylı içerik (blok editörü), kapak, hero görseli, avantajlar, uygulama alanları, teknik bilgi tablosu, teknik içerik, galeri, sıra, durum, SEO |
| Sektörler | Numara, başlık, açıklama, görsel, bağlantı, sıra |
| Galeri (+ Kategoriler, **Toplu Ekle**) | Ortam Kütüphanesi'nden çoklu seçim, sınırsız kategori; galeride olan görsel tekrar eklenmez |
| Referanslar | Firma adı, logo, web sitesi, sıra. **Logosu olmayan referans gösterilmez; kayıt yoksa bölüm gizlenir** |
| Banka Hesapları | Banka, ünvan, şube, şube no, hesap no, IBAN, para birimi, kart rengi, logo |
| İletişim / Teklif Talepleri | Liste, arama (e-posta/telefon/firma dahil), durum filtresi, toplu durum değiştirme, detay, admin notu, ek indirme, CSV dışa aktarma, menü rozeti + üst çubuk bildirimi |
| Audit Log | Giriş/çıkış/başarısız giriş, oluşturma/güncelleme/silme, ayar, durum, dosya indirme, kullanıcı ve eklenti işlemleri; filtre + arama + sayfalama (365 gün saklanır) |
| Kurulum & Araçlar | Başlangıç kurulumu, kalıcı bağlantı yenileme, markalı 500/DB hata sayfalarını kurma, ayar JSON dışa/içe aktarma, sistem kontrolü |

Tüm listelerde WordPress'in arama, filtre, sayfalama, toplu işlem ve çöp kutusu özellikleri çalışır; kalıcı silme ve çöpe taşıma işlemleri onay penceresi ister. Kayıt/güncelleme sonrası bildirimler (toast) gösterilir.

### Roller (RBAC)

| Rol | Yetki |
|---|---|
| **Yönetici** (Süper Admin) | Her şey + kullanıcılar, eklentiler, Audit Log, Kurulum & Araçlar |
| **Site Yöneticisi** (`ce_admin`) | Tüm içerikler, talepler, Tema Ayarları, menüler. Kullanıcı/eklenti yönetemez |
| **İçerik Editörü** (`ce_editor`) | Hizmet, blog, galeri, sayfa içerikleri. Ayarlara ve taleplere erişemez |
| Düzenleyici (WordPress) | İçerik + talepler |

Özel yetkiler: `ce_manage_inquiries`, `ce_manage_settings`, `ce_view_audit`. Kullanıcılar **Kullanıcılar → Yeni ekle** ile oluşturulur.

## 4.1 Sayfa düzenleme: Builder eklentisi, Elementor ve Özelleştirici

**Can Eloksal Builder eklentisi** (`can-eloksal-builder.zip`, Eklentiler → Yeni ekle → Eklenti yükle):

- Temanın tüm bölümleri blok editöründe **20 sürükle-bırak blok** olarak gelir ("+" → **Can Eloksal**): Hero Slider, Sayfa Başlığı, Güven Şeridi, Hizmetler, Görsel + Metin, Özellik Kartları, Sektörler, Proses, Galeri, CTA, CTA Bandı, Blog, Referanslar, İletişim Kartları, Harita, İletişim Formu, Teklif Formu, Banka Hesapları, Bölüm Başlığı ve **Bölüm (Kapsayıcı)**. Kapsayıcının içine paragraf, görsel, sütun, buton, video gibi her WordPress bloğu konabilir.
- Her bloğun ayarları sağ paneldedir; önizleme editörde anında güncellenir. Boş bırakılan alanlarda Tema Ayarları kullanılır.
- **Can Eloksal → Sayfa Oluşturucu**:
  - *Ana sayfayı düzenlenebilir bloklara aktar*: ana sayfanın mevcut görünümü (sıra + tüm metinler) bloklara çevrilir, önceki içerik yedeklenir ve tek tıkla tema bölümlerine geri dönülebilir.
  - *Hazır düzenden yeni sayfa*: Kurumsal açılış, Hizmet tanıtım, İletişim, Duyuru/Kampanya, Boş.
- Blok desenleri: editörde "+" → Desenler → Can Eloksal.

**Elementor ile kullanım:** Tema Elementor ile uyumludur (ücretsiz Elementor'u Eklentiler → Yeni ekle'den kurun). Elementor ile düzenlenen sayfalar otomatik olarak tam genişlikte, tema header/footer'ı ile çizilir; Elementor Pro Theme Builder ile header/footer/arşiv şablonları da değiştirilebilir. İstenirse sayfa şablonu olarak **Boş Tuval (Builder / Elementor)** seçilebilir.

**Görünüm → Özelleştir → Can Eloksal Tasarım** (canlı önizleme): 7 marka rengi, başlık/metin fontu, yazı ölçeği, içerik genişliği, köşe yuvarlaklığı, buton şekli, bölüm boşlukları, header'ı kaydırırken gizleme ve kaydırma animasyonları. Ek CSS için Özelleştir → Ek CSS.

Giriş yapmış yöneticiler ana sayfadaki her bölümün üzerinde bir **"Bölümü düzenle"** kısayolu görür.

## 5. Görseller

- WordPress her yüklemede orijinal + large/medium/thumbnail ve tema boyutlarını (`ce-hero` 2400, `ce-wide` 1600, `ce-card` 960×720, `ce-tall` 800×1040, `ce-thumb`, `ce-logo`) üretir.
- **Tema Ayarları → Marka & Genel → WebP** açıkken (varsayılan) JPG/PNG alt boyutları sunucu destekliyorsa WebP üretilir.
- Alt metin, başlık ve açıklama Ortam Kütüphanesi'nden girilir; alt metin boşsa kayıt başlığı kullanılır.
- Önerilen görsel eşleşmeleri: Naturel → gümüş eloksal; Kırmızı/Mavi/Sarı/Siyah → ilgili renkte parçalar; Diğer → çok renkli; Alodin → şampanya ton; Kromat → sarı yanardöner; Kuru Film → grafit kaplı hassas parçalar; Hakkımızda → personel + eloksal hattı; Savunma → hassas alüminyum komponentler; Makine → CNC; Endüstriyel → eloksal hattı; Metal İşleme → hassas işlenmiş parçalar.
- Her alanda ayrı görsel kullanın; tema aynı görseli otomatik olarak başka yerde tekrar etmez.
- SVG yükleme güvenlik nedeniyle kapalıdır.

## 6. SEO

- Sayfa/hizmet/yazı başına: SEO başlığı, meta açıklama, canonical, noindex/nofollow, OG başlık/açıklama/görsel, ek JSON-LD.
- Twitter Card, Open Graph, `max-image-preview:large`.
- Sayfa tipine göre JSON-LD: Ana sayfa → Organization + LocalBusiness + WebSite + WebPage; Hakkımızda/İletişim → AboutPage/ContactPage + Organization + LocalBusiness; hizmet → Service + WebPage + BreadcrumbList; yazı → BlogPosting + BreadcrumbList; hizmet arşivi → CollectionPage + ItemList. Tek global şema basılmaz.
- `/sitemap.xml`: ana sayfa, sayfalar, hizmetler, yazılar, kategoriler. Taslak, şifreli ve noindex içerikler dahil edilmez. (Çekirdek `wp-sitemap.xml` çakışma olmaması için kapatılır.)
- `/robots.txt`: `wp-admin`, `wp-login.php`, gizli yükleme klasörü ve arama sonuçları engellenir. **Sunucu kökünde fiziksel `robots.txt` varsa WordPress'inki devre dışı kalır — silin.**
- Yoast SEO / Rank Math / AIOSEO etkinse tema SEO çıktısı otomatik kapanır.
- Google Analytics 4 / GTM kodları yalnızca ziyaretçi çerez bandında "Kabul et" derse yüklenir. Search Console doğrulama kodu Tema Ayarları'ndan.

## 7. Formlar ve güvenlik

- İletişim ve teklif formları kayıtları veritabanına (`ce_message`, `ce_quote`) yazar; SMTP ayarlıysa bildirim e-postası gönderir (Reply-To: müşteri).
- Nonce (CSRF), bal küpü alanı, imzalı zaman damgası (bot hız kontrolü), IP başına hız sınırı (varsayılan 10 dakikada 5), sunucu tarafı doğrulama, Türkçe hata mesajları, AJAX + JavaScript kapalıyken çalışan klasik gönderim, çift gönderim engeli.
- Teklif dosyaları: yalnızca PDF/JPG/JPEG/PNG/WEBP; **uzantı + finfo MIME + içerik imzası** (PDF başlığı / gerçek görsel) kontrolü; boyut/adet sınırı; `random_bytes` ile rastgele ad; `wp-content/uploads/ce-private/` klasörüne (`.htaccess` ile web erişimi kapalı, PHP çalıştırma kapalı). Dosyalar yalnızca `ce_manage_inquiries` yetkisiyle, nonce'lu bağlantıdan indirilir; teklif kalıcı silinince dosyaları da silinir.
- Giriş koruması: aynı IP'den 5 başarısız denemede 15 dakika kilit, kullanıcı adını ele vermeyen hata mesajı, `?author=` taraması ve REST kullanıcı listesi kapalı, XML-RPC kapalı, sürüm bilgisi gizli, panelden PHP dosyası düzenleme kapalı (`DISALLOW_FILE_EDIT`).
- Güvenlik başlıkları: `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`.
- Tüm çıktılar `esc_html/esc_attr/esc_url/wp_kses` ile kaçırılır; tüm veritabanı sorguları WordPress API'si veya `$wpdb->prepare` ile yapılır. Oturum çerezleri WordPress tarafından `HttpOnly`, HTTPS'te `Secure` olarak verilir; girişte oturum belirteci yenilenir.
- Tema günlükleri: `wp-content/uploads/ce-private/logs/` (web'den erişilemez).

## 8. SMTP

**Tema Ayarları → SMTP**: host, port, şifreleme (TLS/SSL/yok), kullanıcı adı, şifre, gönderen e-posta ve adı. Şifre veritabanına AES-256 ile şifrelenmiş kaydedilir (anahtar `wp-config.php` tuzlarından türetilir; tuzlar değişirse şifreyi yeniden girin). Daha güvenli seçenek — `wp-config.php`:

```php
define( 'CE_SMTP_PASSWORD', 'uygulama-sifresi' );
```

Gmail için "Uygulama şifresi" kullanın (host `smtp.gmail.com`, port 587, TLS). Ayarları kaydedip **Test e-postası gönder** ile doğrulayın.

## 9. Canlı ortam (production)

`wp-config.php`:

```php
define( 'WP_DEBUG', false );
define( 'WP_DEBUG_DISPLAY', false );
define( 'WP_DEBUG_LOG', true );          // Hatalar wp-content/debug.log'a yazılır
@ini_set( 'display_errors', '0' );
define( 'DISALLOW_FILE_EDIT', true );
define( 'FORCE_SSL_ADMIN', true );
```

- `debug.log` dosyasına web erişimini engelleyin (aşağıdaki .htaccess).
- Kurulum & Araçlar → **Hata sayfalarını kur**: `wp-content/php-error.php` (500) ve `wp-content/db-error.php` markalı sayfaları.
- Sayfa önbellek eklentisi kullanıyorsanız form nonce'ları 12–24 saat geçerlidir; önbellek süresini 10 saatin altında tutun ya da İletişim ve Teklif Al sayfalarını önbellekten hariç tutun.
- Arama motorları: **Ayarlar → Okuma → "Arama motorlarının siteyi dizine eklemesini engelle"** kutusunun kapalı olduğundan emin olun.

### Apache `.htaccess` (WordPress kök dizini)

```apache
# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
# END WordPress

# Hassas dosyalar
<FilesMatch "^(wp-config\.php|debug\.log|readme\.html|license\.txt|\.env)$">
  Require all denied
</FilesMatch>
Options -Indexes

# Çerezlere SameSite (WordPress oturum çerezleri dahil)
<IfModule mod_headers.c>
  Header always edit Set-Cookie ^(.*)$ "$1; SameSite=Lax" "expr=%{resp:Set-Cookie} !~ /SameSite/i"
  Header always set Strict-Transport-Security "max-age=31536000" env=HTTPS
</IfModule>

# Sıkıştırma ve tarayıcı önbelleği
<IfModule mod_deflate.c>
  AddOutputFilterByType DEFLATE text/html text/css application/javascript application/json image/svg+xml application/xml
</IfModule>
<IfModule mod_expires.c>
  ExpiresActive On
  ExpiresByType text/css "access plus 1 year"
  ExpiresByType application/javascript "access plus 1 year"
  ExpiresByType font/woff2 "access plus 1 year"
  ExpiresByType image/webp "access plus 1 year"
  ExpiresByType image/jpeg "access plus 1 year"
  ExpiresByType image/png "access plus 1 year"
</IfModule>

# Yüklemeler klasöründe PHP çalıştırma
<IfModule mod_rewrite.c>
  RewriteRule ^wp-content/uploads/.*\.(php|phtml|phar)$ - [F,L]
</IfModule>
```

(Tema CSS/JS dosyaları dosya değişim zamanına göre sürümlendiği için uzun önbellek süresi güvenlidir.)

### Nginx örneği

```nginx
server {
    listen 443 ssl http2;
    server_name caneloksal.com www.caneloksal.com;
    root /var/www/caneloksal;
    index index.php;

    client_max_body_size 64M;   # teklif dosyaları için
    gzip on; gzip_types text/css application/javascript application/json image/svg+xml application/xml;

    location / {
        try_files $uri $uri/ /index.php?$args;
    }

    # Gizli teklif dosyaları ve günlükler: web'den erişim yok (Nginx .htaccess okumaz!)
    location ^~ /wp-content/uploads/ce-private/ { deny all; return 404; }
    location ~* /wp-content/uploads/.*\.(php|phtml|phar)$ { deny all; }
    location ~* /(wp-config\.php|debug\.log|readme\.html|\.env|\.ht) { deny all; }

    location ~* \.(css|js|woff2|webp|jpe?g|png|svg|ico)$ {
        expires 1y; add_header Cache-Control "public, immutable"; access_log off;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    }
}
```

## 10. Dosya izinleri

| Yol | İzin |
|---|---|
| Klasörler | 755 |
| Dosyalar | 644 |
| `wp-config.php` | 600 veya 640 |
| `wp-content/uploads/` | 755, web sunucusu kullanıcısı yazabilmeli |

## 11. Yedekleme

- Veritabanı (tüm `wp_` tabloları; talepler ve audit log `wp_ce_audit_log` dahil) ve `wp-content/uploads/` (**`ce-private` klasörü teklif dosyalarını içerir**) günlük yedeklenmeli.
- Tema Ayarları: Kurulum & Araçlar → JSON indir.
- Yedekleri sunucu dışında (bulut depolama) en az 30 gün saklayın; geri yüklemeyi düzenli test edin.

## 12. Sorun giderme

| Sorun | Çözüm |
|---|---|
| **HTTP 500** | `wp-config.php`'de geçici olarak `WP_DEBUG_LOG` açıp `wp-content/debug.log`'u inceleyin; PHP sürümünün 8.0+ olduğunu ve eklentilerin (özellikle `fileinfo`, `mbstring`) yüklü olduğunu kontrol edin. Kurulum & Araçlar → Sistem kontrolü eksikleri listeler. `.htaccess` bozuksa silip Ayarlar → Kalıcı Bağlantılar → Kaydet. |
| **403 Forbidden** | Klasör 755 / dosya 644 izinleri; `.htaccess`'te `Options -Indexes` sunucu tarafından yasaklanmışsa satırı kaldırın; ModSecurity form gönderimini engelliyorsa hosting'den `admin-ajax.php` / `admin-post.php` için istisna isteyin. |
| **Database Access Denied / "Veritabanı bağlantısı kurulamadı"** | `wp-config.php`'deki `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `DB_HOST` değerlerini hosting paneliyle karşılaştırın; kullanıcının veritabanına **tüm yetkilerle** atandığını kontrol edin; bazı hostlarda `DB_HOST` `localhost` yerine özel bir adrestir. |
| **Hizmet/blog sayfaları 404** | Kurulum & Araçlar → **Kalıcı bağlantıları yenile**. Apache'de `mod_rewrite` açık ve `AllowOverride All` olmalı; Nginx'te `try_files` satırı gerekli. |
| **mod_rewrite yok** | Hosting'den etkinleştirmesini isteyin; geçici olarak Ayarlar → Kalıcı Bağlantılar → "Düz" seçilebilir (SEO URL'leri çalışmaz). |
| **Görsel yüklenemiyor / uploads izin hatası** | `wp-content/uploads` web sunucusu kullanıcısına yazılabilir olmalı (755, sahibi `www-data`/hosting kullanıcısı). Büyük görsellerde `upload_max_filesize` ve `post_max_size` değerlerini artırın. |
| **Teklif formunda "dosya içeriği uzantısıyla uyuşmuyor"** | Dosya gerçekten o türde değildir (ör. uzantısı değiştirilmiş). `fileinfo` eklentisi yoksa tüm dosyalar reddedilir — eklentiyi açtırın. |
| **E-posta gelmiyor** | SMTP'yi yapılandırıp test edin; kayıtlar yine de panele düşer. Talep detayında "Bildirim e-postası: gönderilemedi" yazıyorsa SMTP hatalıdır; ayrıntı `ce-private/logs/` altında. |
| **WebP üretilmiyor** | Sunucudaki GD/Imagick WebP desteklemiyor; Sistem kontrolü bunu gösterir. |
| **"Oturum süreniz dolmuş olabilir"** | Sayfa önbelleği formu çok uzun süre saklıyor; İletişim / Teklif Al sayfalarını önbellekten hariç tutun. |
| **Giriş yapamıyorum, "Çok fazla başarısız deneme"** | 15 dakika bekleyin veya veritabanında `wp_options` tablosundan `_transient_ce_lock_%` kayıtlarını silin. |
| **Hosting PHP eklentileri** | cPanel → "Select PHP Version" / Plesk → PHP ayarları'ndan `fileinfo`, `mbstring`, `openssl`, `gd`, `intl`, `zip` işaretlenmeli. |

## 13. Tema dosya yapısı

```
can-eloksal/
├── style.css, functions.php, theme.json, screenshot.png
├── header.php, footer.php, front-page.php, page.php, single.php, home.php,
│   archive.php, archive-ce_service.php, taxonomy-ce_service_cat.php,
│   single-ce_service.php, search.php, searchform.php, 404.php, index.php
├── page-templates/   about, quality, gallery, banks, contact, quote
├── template-parts/
│   ├── home/         hero, trust, services, about, why, sectors, process, gallery, cta, blog, references, contact
│   └── components/   page-hero, service-card, post-card, sectors, gallery-grid, contact-cards, cta-band, blog-list
├── inc/
│   ├── helpers, icons, options-schema, fields, setup, post-types, meta-boxes, nav
│   ├── security, roles, audit-log, forms, form-fields, smtp, seo, sitemap, breadcrumbs
│   └── admin/        panel, dashboard, inquiries, gallery-bulk, audit-page, seed-content, tools
├── assets/
│   ├── css/          main.css, editor.css
│   ├── js/           main.js
│   ├── admin/        admin.css, admin.js
│   ├── fonts/        Manrope, Inter (woff2, OFL lisansı)
│   └── images/       favicon.svg, logo-mark.png
└── extras/           php-error.php (500), db-error.php
```

### Veri modeli (WordPress tabloları üzerinde)

| Spesifikasyondaki tablo | Karşılığı |
|---|---|
| users, roles, permissions, role_permissions, user_roles | `wp_users`, `wp_usermeta` + WordPress roller/yetkiler (`ce_admin`, `ce_editor`, özel yetkiler) |
| settings | `wp_options` → `ce_options` |
| pages, seo_meta | `page` + `_ce_seo_*` meta |
| sliders | `ce_slide` |
| services, service_categories, service_gallery | `ce_service`, `ce_service_cat`, `_ce_gallery` meta |
| sectors | `ce_sector` |
| gallery, gallery_categories | `ce_gallery`, `ce_gallery_cat` |
| blog_posts, blog_categories | `post`, `category` |
| references | `ce_reference` |
| bank_accounts | `ce_bank` |
| contact_messages | `ce_message` |
| quote_requests, quote_files | `ce_quote`, `_ce_files` meta |
| menus, menu_items | WordPress menüleri (`nav_menu`, `nav_menu_item`) |
| login_attempts | IP bazlı transient'ler (`ce_fail_*`, `ce_lock_*`) + audit log |
| audit_logs | `wp_ce_audit_log` (tema etkinleştirilince `dbDelta` ile oluşturulur) |

## 14. Test kontrol listesi

Ana sayfa, hizmet listesi/detayı, blog, galeri (filtre + lightbox: ok tuşları, ESC, kaydırma), iletişim ve teklif formu kaydı, dosya yükleme güvenliği (sahte PDF reddi), admin giriş/çıkış, CRUD, görsel yükleme, IBAN kopyalama, dinamik SEO meta, sitemap, 404, mobil menü, 360–1920 px arası yatay taşma yok, güvenlik başlıkları.
