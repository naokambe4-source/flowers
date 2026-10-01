# Güncelleme Rehberi

Güncellemeler **veriyi silmez**; yalnız henüz uygulanmamış migration dosyaları çalıştırılır (`migrations` tablosu ile izlenir).

1. cPanel → *Backup* bölümünden **veritabanı ve dosya yedeği** alın.
2. Yeni ZIP içeriğini mevcut klasörün üzerine yükleyin. **Şunların üzerine yazmayın / silmeyin:**
   - `config/config.php` (uygulama anahtarı burada; kaybedilirse şifreli API anahtarları ve SMTP parolası çözülemez)
   - `storage/` (yüklenen görseller, oturumlar, loglar, kurulum kilidi)
3. Migration’ları uygulayın:
   - Yönetim → **Sistem Güncellemesi** → *Güncellemeyi uygula*, veya
   - SSH / cPanel Terminal: `php bin/migrate.php` (durum için `php bin/migrate.php --status`)
4. Tarayıcı önbelleği: CSS/JS adreslerine dosya değişim zamanına göre sürüm eklenir; ek işlem gerekmez.

Yeni migration eklerken (geliştiriciler için): `database/migrations/NNN_aciklama.sql` veya `.php` (`return static function (Database $db): void {...}`) oluşturun; mevcut dosyaları değiştirmeyin.

## 1.0.0 → 1.1.0
- Yeni migration: `003_demo_and_registration.sql` (demo işaretleri ve üyelik kayıt modu ayarları). Mevcut veriye dokunmaz; *Sistem Güncellemesi* sayfasından uygulayın.
- `database/demo/` klasörü (demo veri ve temsili görseller) yeni gelir; yalnız yönetici isteğiyle yüklenir.
- Yenilikler: Demo / Canlı mod, üyelik kayıt modu (açık kayıt), tarih seçicinin her zaman ekranın üstünde ve görünür alanda açılması (mobilde alttan açılan sayfa), çıkış tarihi seçilince otomatik uygulanma, sodium olmayan sunucularda OpenSSL şifreleme.

## 1.1.0 → 1.2.0
- Yeni migration: `004_live_hotel_sources.sql` (otel kaynak alanları, web/telefon alanları, OpenStreetMap ve LiteAPI sağlayıcı kayıtları). Mevcut veriye dokunmaz; *Sistem Güncellemesi* sayfasından uygulayın.
- Yenilikler: Yönetim → Canlı Otel Verisi (OpenStreetMap ile anahtarsız gerçek otel kataloğu, LiteAPI ile fotoğraf + canlı fiyat + rezervasyon), otel sayfasında “Canlı oda fiyatları” bölümü, `bin/import-hotels.php`.
