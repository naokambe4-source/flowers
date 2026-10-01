# Kurulum Rehberi (cPanel)

## Gereksinimler
- PHP **8.3** veya üzeri (cPanel → *Select PHP Version* / *MultiPHP Manager*)
- Zorunlu PHP uzantıları: `pdo_mysql`, `mbstring`, `fileinfo`, `dom`, `xml`, `openssl` (`sodium` önerilir ama zorunlu değildir; yoksa gizli değerler OpenSSL AES-256-GCM ile şifrelenir)
- Önerilen: `curl` (API sağlayıcıları), `gd` (görsel boyutlandırma ve QR PNG), `zip` (XLSX), `exif`
- MySQL 8+ veya MariaDB 10.6+
- Apache veya LiteSpeed, `.htaccess` (AllowOverride) açık
- SSL sertifikası (AutoSSL / Let’s Encrypt) — canlı kullanımda zorunlu

## Adımlar
1. **Veritabanı:** cPanel → *MySQL Databases* → yeni veritabanı ve kullanıcı oluşturun, kullanıcıya veritabanında *ALL PRIVILEGES* verin. Veritabanı **boş** olmalıdır.
2. **Dosyalar:** `kurumsal-konaklama-x.y.z.zip` dosyasını *File Manager* ile kurulum klasörüne yükleyip açın:
   - Ana domain: `public_html/`
   - Alt klasör: `public_html/oteller/`
   - İç içe klasör: `public_html/kurumsal/konaklama/`
   Arşivdeki **gizli `.htaccess` dosyalarının** da açıldığından emin olun (File Manager → *Settings → Show Hidden Files*).
3. **İzinler:** klasörler 755, dosyalar 644. `config/` ve `storage/` (alt klasörleriyle) PHP tarafından yazılabilir olmalı.
4. **Sihirbaz:** tarayıcıda kurulum adresini açın (ör. `https://alanadi.com/oteller/`). Sistem otomatik olarak `…/index.php/kurulum` sihirbazına yönlenir.
   - Ortam kontrolleri: PHP, uzantılar, yazma izinleri, HTTPS, **temiz adres (rewrite) testi**, algılanan kurulum yolu.
   - Rewrite çalışmıyorsa kurulum **tamamlanmaz** ve tanı mesajı gösterilir (sayfaların sonradan 404 vermesini önlemek için).
   - Veritabanı bilgileri, site adresi, site adı ve ilk **Super Admin** hesabını girin.
5. Kurulum tamamlanınca `config/config.php` oluşturulur (uygulama şifreleme anahtarı ve cron anahtarı dahil), migration’lar çalışır ve `storage/installed.lock` ile sihirbaz kilitlenir.
6. **Cron:** `docs/CRON-VE-EPOSTA.md` adımlarını uygulayın.
7. Yönetim paneli → **Dashboard**’daki “Yayın öncesi tamamlanması gerekenler” listesini bitirin: destek telefonu/WhatsApp, KVKK / gizlilik / kullanım koşulları metinleri, SMTP.

## İlk içerik kurulumu (önerilen sıra)
1. İçerik Yönetimi → logo, site adı, giriş ve ana sayfa görselleri, iletişim bilgileri, yasal metinler
2. Kurum Tipleri / Fiyat Grupları → Kurumlar (kuruma özel indirim gerekiyorsa)
3. Sistem Ayarları → varsayılan üye indirimi (%10), azami indirim, KDV/konaklama vergisi, SMTP
4. Oteller → *Yeni otel ekle* sihirbazı (7 adım) → odalar → fotoğraflar
5. Fiyatlar → fiyat planı + toplu gecelik fiyat; Kontenjan → oda adetleri
6. Üyeler → tek tek ekleyin veya CSV/XLSX aktarın; ya da Başvurular’dan onaylayın

> Kurulum **örnek otel, sahte fiyat, test kullanıcısı veya DEMO içeriği oluşturmaz.** Yalnız roller/izinler, Antalya bölgeleri, konseptler, özellikler, kurum tipleri, bildirim şablonları, boş yasal sayfa kayıtları ve varsayılan ayarlar yüklenir. Harici API sağlayıcıları kapalı gelir.

## Elle kurulum (sihirbaz kullanılamıyorsa)
1. `config/config.example.php` → `config/config.php` olarak kopyalayıp değerleri doldurun (`key` için: `php -r "echo 'base64:'.base64_encode(random_bytes(32));"`).
2. `php bin/migrate.php`
3. `php bin/create-admin.php eposta@kurum.gov.tr Ad Soyad`
4. `storage/installed.lock` dosyasını oluşturun.

## Sorun giderme
| Belirti | Çözüm |
|---|---|
| Sihirbazda “Temiz adresler çalışmıyor” | `.htaccess` yüklenmemiş veya AllowOverride kapalı. Hosting desteğinden `AllowOverride All` isteyin; LiteSpeed’de .htaccess desteği açık olmalı. |
| Beyaz sayfa / 500 | `storage/logs/app-YYYY-MM-DD.log` dosyasına bakın; PHP sürümünün 8.3 olduğunu doğrulayın. |
| Görseller yüklenmiyor | `storage/private` yazılabilir mi; `upload_max_filesize` ≥ 8M olmalı. |
| PDF voucher Türkçe karakter | dompdf DejaVu fontları paketle gelir; `storage/tmp` yazılabilir olmalı. |
