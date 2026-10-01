# Kurumsal Konaklama ve Otel Rezervasyon Platformu

Antalya ve ilçelerindeki anlaşmalı otelleri **yalnızca yetkilendirilmiş kurum üyelerine** özel fiyatlarla sunan kapalı B2B/B2E konaklama platformu. Standart cPanel hosting üzerinde (PHP 8.3 + MySQL/MariaDB) çalışır; Node.js, Docker veya sürekli çalışan servis gerektirmez.

**Teslim paketi:** `../kurumsal-konaklama-1.2.1.zip` (vendor dahil, cPanel’e doğrudan yüklenir).

## Dokümanlar
| Doküman | İçerik |
|---|---|
| [docs/MIMARI.md](docs/MIMARI.md) | Sistem mimarisi, ER yapısı, fiyat motoru, rezervasyon akışı, rota listesi, yetki matrisi, modüller, klasör yapısı |
| [docs/KURULUM.md](docs/KURULUM.md) | cPanel kurulum rehberi ve sorun giderme |
| [docs/GUNCELLEME.md](docs/GUNCELLEME.md) | Veriyi silmeden güncelleme |
| [docs/CRON-VE-EPOSTA.md](docs/CRON-VE-EPOSTA.md) | Cron ve SMTP |
| [docs/API-SAGLAYICILAR.md](docs/API-SAGLAYICILAR.md) | Sağlayıcı mimarisi, StayAPI / Hotelbeds / Expedia durumu ve izinler |
| [docs/TEST-SONUCLARI.md](docs/TEST-SONUCLARI.md) | Otomatik, uçtan uca, kurulum yolu ve responsive test sonuçları; yapılamayan testler |

## Öne çıkanlar
- **Gerçek otellerle canlı sistem:** OpenStreetMap’ten anahtarsız gerçek Antalya otel kataloğu; ücretsiz LiteAPI hesabıyla fotoğraf, canlı oda fiyatı ve anında rezervasyon (Yönetim → Canlı Otel Verisi).
- **Demo / Canlı mod:** tek tıkla 14 kurgusal Antalya oteli (fiyat, kontenjan, temsili görsellerle) yükleyip sistemi deneyin; canlıya geçişte yalnız demo kayıtları silinir.
- **Üyelik kayıt modu:** erişim talebi (onaylı) / açık kayıt (alan adı kısıtlamalı olabilir) / kapalı — yönetimden seçilir.
- **Kapalı üyelik:** girişsiz kullanıcı otel, fiyat, görsel veya JSON verisine sunucu tarafında erişemez; otel fotoğrafları herkese açık klasörde değil, yetkili uç noktadan sunulur.
- **Kurulum yolu bağımsız:** `/`, `/oteller/`, `/kurumsal/konaklama/` — tüm bağlantılar `base_url()`, `url()`, `asset_url()`, `route_url()` ile üretilir; rewrite kapalıysa kurulum engellenir.
- **Fiyat motoru:** kuruş bazlı hesap; anlaşmalı / kampanya / doğrulanmış referans / sağlayıcı fiyatları ayrı; kural önceliği ve birlikte uygulanma, kurum → fiyat grubu → genel (%10, yönetimden) üye indirimi, azami indirim sınırı, promosyon, vergi; **kesin fiyat ↔ onaya bağlı hedef teklif** ayrımı; yalnız aynı koşullarda doğrulanmış tasarruf.
- **Rezervasyon:** 4 adım; fiyat/müsaitlik yeniden doğrulama ve değişince yeniden kabul; transaction + satır kilidi + atomik stok + idempotency; anında veya otel teyitli mod; sürümlü manuel teklif akışı; PDF voucher + tahmin edilemez QR doğrulama.
- **Sağlayıcı mimarisi:** `HotelProviderInterface` + yetenek bildirimi; Manual, StayAPI, Hotelbeds, Expedia Rapid adaptörleri; şifreli anahtarlar, maskeli loglar, ayrı TTL’li önbellek, hız sınırı, kontrollü retry, geri dönüş zinciri.
- **Yönetim:** dashboard, global arama, 7 adımlı otel sihirbazı, fiyat/kural/kampanya/promosyon/sezon, kontenjan ve stop sale, kurumlar ve üyeler (CSV/XLSX aktarım), başvurular, API yönetimi, bildirim şablonları ve iş kuyruğu, raporlar (CSV/XLSX, formül enjeksiyonu korumalı), içerik, ayarlar, sistem güncellemesi, yetki matrisi, audit log.
- **Arayüz:** özgün tasarım sistemi (açık yüzeyler, lacivert metin, turkuaz vurgu, ölçülü altın), 360 px dahil mobil uyum, erişilebilir tarih aralığı takvimi ve konuk seçici, OpenStreetMap + Leaflet harita, JavaScript kapalıyken çalışan formlar.

## Geliştirici komutları
```bash
composer install                 # bağımlılıklar
php bin/migrate.php [--status]   # migration
php bin/cron.php                 # kuyruk + periyodik işler
php bin/demo.php durum|yukle|kaldir   # demo içerik
php bin/import-hotels.php osm|liteapi all  # gerçek otelleri içe aktar
node tools/demo-images/generate.mjs  # (geliştirici) demo görsellerini yeniden üret — Playwright gerekir
php tests/run.php                # test paketi (KK_TEST_DB_* ile test veritabanı)
bash bin/build-zip.sh            # üretim ZIP'i → build/
```

## Açık sınırlamalar
- Harici API adaptörleri (OpenStreetMap, LiteAPI, StayAPI, Hotelbeds, Expedia) bu geliştirme ortamında ağ erişimi olmadığı için canlı servislerle test edilemedi; sahte yanıtlarla uçtan uca test edildi; canlıya almadan önce yönetimdeki bağlantı testi ve sözleşme/izin doğrulaması gereklidir.
- Online ödeme alınmaz; ödeme durumu (otelde ödeme, kurum faturası vb.) yönetici tarafından işaretlenir.
- Giriş, ana sayfa, destek, bölge ve demo otel görselleri bu proje için çizilmiş **temsili illüstrasyonlardır** ve “Temsili” etiketiyle gösterilir; gerçek fotoğraflar yönetimden yüklenir. Gerçek (demo olmayan) otellerde fotoğraf yüklenmemişse başka görsel kullanılmaz.
- Demo oteller kurgusaldır; isimleri gerçek işletmeleri temsil etmez, fiyatları örnektir.
- KVKK, gizlilik ve kullanım koşulları metinleri boş gelir; kurumunuzun hukuk birimi tarafından hazırlanıp yönetimden girilmelidir (uydurma şirket bilgisi eklenmemiştir).

## Lisanslar
Uygulama kodu kuruma özeldir. Üçüncü taraf bileşenler: dompdf (LGPL-2.1), php-font-lib (LGPL-2.1+), php-svg-lib (LGPL-3.0+), PHPMailer (LGPL-2.1), chillerlan/php-qrcode (MIT/Apache-2.0), php-settings-container (MIT), masterminds/html5 (MIT), sabberworm/php-css-parser (MIT), Leaflet 1.9.4 (BSD-2-Clause). Harita verisi © OpenStreetMap katkıcıları (ODbL); yoğun kullanımda OSM karo kullanım politikası gereği ticari bir karo sağlayıcısı yapılandırılmalıdır.
