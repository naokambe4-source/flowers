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
