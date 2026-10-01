# Mimari ve Tasarım Dokümanı

Bu doküman sistem mimarisini, veritabanı ER yapısını, rota listesini, yetki matrisini, modülleri, klasör yapısını ve rezervasyon akışını açıklar. Rota ve yetki tabloları kaynak koddan / kurulum verisinden otomatik üretilmiştir.

## 1. Sistem mimarisi

- **Çalışma ortamı:** Standart cPanel hosting; PHP 8.3+, MySQL 8+ veya MariaDB 10.6+, Apache/LiteSpeed. Node.js, Docker veya sürekli çalışan servis gerekmez. Arka plan işleri cPanel cron + veritabanı kuyruğu ile yürür.
- **Tek giriş noktası:** `index.php` (front controller). `.htaccess` tüm istekleri yönlendirir; iç klasörleri (`app`, `config`, `storage`, `vendor`, `database`, `bin`, `tests`, `docs`) ve hassas uzantıları engeller.
- **Kurulum yolu bağımsızlığı:** `Url::basePath()` kurulum yolunu `SCRIPT_NAME`’den algılar. Tüm bağlantılar `url()`, `base_url()`, `asset_url()`, `route_url()` yardımcılarıyla üretilir; çerez yolu kurulum klasörüne göre ayarlanır. Kök, `/oteller/` ve `/kurumsal/konaklama/` Apache üzerinde test edilmiştir.
- **Katmanlar** (Laravel’e taşınabilir ayrım):

| Katman | Klasör | Sorumluluk |
|---|---|---|
| Core | `app/Core` | App (çekirdek), Router, Request/Response, View, Database (PDO), Session, Csrf, Auth, Validator, Money, Crypto, Logger, Migrator, Middleware, hata yönetimi |
| Controller | `app/Controllers/{Auth,Site,Member,Admin,Install}` | HTTP girdisi → servis çağrısı → yanıt. İş kuralı içermez |
| Service | `app/Services` | İş kuralları: fiyat motoru, rezervasyon, envanter, teklif, üyelik, bildirim, kuyruk, voucher, içe/dışa aktarım, rapor |
| Repository | `app/Repositories` | Toplu ve N+1’siz otel sorguları |
| DTO | `app/DTO` | StayCriteria, RoomOccupancy, PriceQuote, ProviderRate, ProviderBookingResult, ProviderHealth |
| Validator | `app/Validators`, `Core/Validator` | Sunucu tarafı doğrulama (tarih, kapasite, konuk dağılımı) |
| Policy | `app/Policies` | Rezervasyon ve kurum veri erişim kuralları |
| Provider Interface / Adapter | `app/Providers` | `HotelProviderInterface`, yetenek bildirimi, HTTP istemcisi, önbellek, adaptörler |
| Exception | `app/Exceptions` | Http, Validation, Domain, Provider, UnsupportedCapability, PriceChanged |
| Migration | `database/migrations` | `.sql` ve `.php` migration’lar; `migrations` tablosu ile izlenir |
| Job | `app/Jobs` | E-posta, teklif süresi, tamamlanan rezervasyon, temizlik, sağlayıcı sağlık kontrolü |

## 2. Veritabanı ER yapısı

Tüm tablolar InnoDB, `utf8mb4_unicode_ci`. Para alanları **kuruş (BIGINT)**, yüzdeler **baz puan** (%10 = 1000). Şema: `database/migrations/001_initial_schema.sql`.

```
roles 1─* role_permission *─1 permissions
roles 1─* users *─1 institutions *─1 institution_types
                     institutions *─1 price_groups
users 1─* membership_requests ; users 1─* password_tokens
regions (self parent) 1─* hotels 1─* hotel_images
hotels *─* amenities (hotel_amenity) ; hotels *─1 concepts
hotels 1─* rooms 1─* room_images ; rooms *─* amenities (room_amenity)
rooms 1─* rate_plans 1─* rates (gecelik)            ← anlaşmalı / kampanya fiyatı
hotels 1─* reference_prices (manual_reference | provider)  ← doğrulanmış referans
rate_rules ─ (institution, price_group, hotel, room, season) ; campaigns ─ rate_rules
promo_codes ─ (institution, hotel)
rooms 1─* inventory (gün bazlı stok) ; hotels 1─* stop_sales
users 1─* bookings *─1 hotels ; bookings 1─* booking_rooms 1─* booking_guests
bookings 1─* booking_status_history ; bookings 1─* booking_inventory (stok izi)
users 1─* accommodation_requests 1─* offers (sürümlü) ─1 bookings
users *─* hotels (favorites) ; users 1─* notifications ; notification_templates
providers 1─* provider_credentials (şifreli) ; providers 1─* provider_destinations *─1 regions
providers 1─* provider_hotel_map *─1 hotels ; api_cache ; api_request_logs
audit_logs ; settings ; pages ; home_sections ; support_requests ; jobs ; rate_limits ; migrations
```

Önemli kısıtlar: `bookings(user_id, idempotency_key)` ve `accommodation_requests(user_id, idempotency_key)` benzersiz; `inventory(room_id, stay_date)` benzersiz; `offers(request_id, version)` benzersiz; `rates(rate_plan_id, stay_date)` benzersiz.

## 3. Fiyat motoru

Kaynaklar ayrı tutulur: anlaşmalı fiyat (`rate_plans.source=contract` + `rates`), kampanya fiyatı (`source=campaign` veya kampanyaya bağlı kural), manuel doğrulanmış referans fiyat ve harici sağlayıcı fiyatı (`reference_prices`).

Gece bazında uygulama sırası:
1. Kaynak gecelik fiyat (oda başı; `base_adults` kişiye kadar, ek yetişkin, ücretli çocuk, ücretsiz çocuk yaşı, yetişkin sayılan yaş)
2. Uygun kurallar yüksek öncelikten düşüğe. “Birlikte uygulanamaz” kurallardan yalnız en yüksek öncelikli (eşitlikte en avantajlı) olan; “birlikte uygulanabilir” kuralların tamamı, her biri bir öncekinin sonucuna
3. Üye indirimi: kuruma özel → fiyat grubu → genel ayar (`pricing.default_discount_bp`, varsayılan %10, yönetimden değiştirilir). Plan “net kurumsal fiyat” ise uygulanmaz
4. Azami indirim sınırı (`pricing.max_discount_bp`)
5. Promosyon kodu (toplam üzerinden, sınır içinde)
6. Vergiler (dahil ise içindeki pay, hariç ise KDV + konaklama vergisi eklenir)

Sonuç türü: **kesin fiyat** yalnız anlaşmalı otel + geçerli anlaşma + satış yetkili plan + yönetilen ve müsait kontenjan varsa; referans/sağlayıcı fiyatı yetki yoksa **onaya bağlı hedef teklif**; fiyat yoksa **teklif iste**. Doğrulanmış tasarruf yalnız aynı tarih, oda, konuk dağılımı, konsept, vergi (dahil), iptal koşulu ve para biriminde, süresi geçmemiş referansla gösterilir. Hesap dökümü (gece bazlı) rezervasyonda saklanır.

## 4. Rezervasyon akışı (en fazla 4 adım)

```
Otel detayı → ODAYI SEÇ (taslak, idempotency anahtarı)
  → Misafir bilgileri (oda başı sorumlu misafir, iletişim, isteğe bağlı promosyon)
  → Fiyat ve koşul kontrolü (fiyat + müsaitlik yeniden hesaplanır; değiştiyse yeni toplam gösterilir ve yeniden onay istenir)
  → Onay: transaction + satır kilidi (FOR UPDATE) + koşullu atomik stok güncellemesi
       instant mod → Onaylandı (voucher)
       request mod → Talep Alındı → (yönetici) Onay Bekliyor → otel teyit no ile Onaylandı
Manuel akış: Talep → Yönetici teklifi (sürümlü, geçerlilik süreli) → Üye kabulü (yalnız güncel sürüm) → Onay Bekliyor → otel teyit no → Onaylandı
```

Durumlar: Taslak, Talep Alındı, Onay Bekliyor, Onaylandı, İptal Edildi, Tamamlandı. İptalde stok (`booking_inventory` izi üzerinden) ve promosyon kullanımı geri alınır; sağlayıcı rezervasyonu iptal desteği yoksa çevrimiçi iptal engellenir.

## 5. Sağlayıcı mimarisi

`HotelProviderInterface`: `searchDestinations`, `searchHotels`, `getHotel`, `getAvailability`, `getRates`, `checkRate`, `createBooking`, `getBooking`, `cancelBooking`, `healthCheck` + `capabilities()`. Desteklenmeyen işlem `UnsupportedCapabilityException` fırlatır. Adaptörler: `ManualHotelProvider`, `StayApiProvider`, `HotelbedsProvider`, `ExpediaRapidProvider`. Yeni sağlayıcı = yeni adaptör sınıfı + `providers` satırı. Ayrıntı: `docs/API-SAGLAYICILAR.md`.

## 6. Modüller

Kurulum sihirbazı · Kimlik doğrulama (giriş, parola sıfırlama/davet, erişim başvurusu) · Üye alanı (ana sayfa, arama, liste/harita, otel detayı, rezervasyon, teklif, favoriler, bildirimler, profil, destek, kurumum) · Yönetim (dashboard, global arama, rezervasyonlar, talepler/teklifler, otel sihirbazı, odalar, özellikler, bölgeler, konseptler, fiyatlar, fiyat kuralları, kampanyalar, promosyonlar, sezonlar, kontenjan/stop sale, kurumlar, kurum tipleri, fiyat grupları, üyeler, CSV/XLSX aktarım, başvurular, API yönetimi, bildirim şablonları ve iş kuyruğu, raporlar + dışa aktarım, içerik, sistem ayarları, sistem güncellemesi, yetki matrisi, audit log, destek) · Voucher (PDF + QR doğrulama) · Cron.

## 7. Klasör yapısı

```
index.php  .htaccess  composer.json
app/      Core/ Controllers/ Services/ Repositories/ DTO/ Validators/ Policies/ Providers/ Jobs/ Exceptions/ Support/ Views/ routes.php
assets/   css/app.css  js/app.js  img/ (favicon, temsili illüstrasyonlar)  vendor/leaflet
bin/      migrate.php  cron.php  create-admin.php  build-zip.sh
config/   config.example.php  (config.php kurulumda oluşur)
database/ migrations/
storage/  private/ (görseller, herkese kapalı)  logs/  sessions/  tmp/  cache/
tests/    run.php  TestCase.php  concurrency_worker.php  fixtures/ (yalnız test)
docs/     dokümanlar
vendor/   Composer bağımlılıkları (teslim paketinde hazır)
```

## 8. Rota listesi

| Yöntem | Yol | Ara katman | İşleyici |
|---|---|---|---|
| GET | `/kurulum` | misafir | InstallController::index |
| POST | `/kurulum` | misafir | InstallController::install |
| GET | `/kurulum/rewrite-test` | misafir | InstallController::rewriteTest |
| GET | `/` | misafir | LoginController::home |
| GET | `/giris` | guest | LoginController::show |
| POST | `/giris` | guest | LoginController::login |
| POST | `/cikis` | misafir | LoginController::logout |
| GET | `/sifremi-unuttum` | guest | PasswordController::forgot |
| POST | `/sifremi-unuttum` | guest | PasswordController::sendLink |
| GET | `/sifre-olustur/{token}` | misafir | PasswordController::showReset |
| POST | `/sifre-olustur/{token}` | misafir | PasswordController::reset |
| GET | `/erisim-talebi` | guest | AccessRequestController::show |
| POST | `/erisim-talebi` | guest | AccessRequestController::store |
| GET | `/iletisim` | misafir | PageController::contact |
| POST | `/iletisim` | misafir | PageController::sendContact |
| GET | `/kvkk` | misafir | PageController::kvkk |
| GET | `/gizlilik` | misafir | PageController::privacy |
| GET | `/kullanim-kosullari` | misafir | PageController::terms |
| GET | `/cerez-politikasi` | misafir | PageController::cookies |
| GET | `/voucher/dogrula/{token:[A-Za-z0-9_-]+}` | misafir | VoucherVerifyController::show |
| GET | `/medya/site/{key:[a-z_]+}` | misafir | MediaController::site |
| GET | `/cron/{token:[A-Za-z0-9_-]+}` | misafir | CronController::run |
| POST | `/cron/{token:[A-Za-z0-9_-]+}` | misafir | CronController::run |
| GET | `/panel` | auth | DashboardController::index |
| GET | `/oteller` | auth, can:member.book | HotelController::index |
| GET | `/oteller/harita-verisi` | auth, can:member.book | HotelController::mapData |
| GET | `/oteller/{slug:[a-z0-9-]+}` | auth, can:member.book | HotelController::show |
| GET | `/oteller/{slug:[a-z0-9-]+}/fiyatlar` | auth, can:member.book | HotelController::rates |
| POST | `/favoriler/{hotelId:\d+}` | auth, can:member.book | FavoriteController::toggle |
| GET | `/favorilerim` | auth, can:member.book | FavoriteController::index |
| POST | `/rezervasyon/baslat` | auth, can:member.book | BookingFlowController::start |
| GET | `/rezervasyon/{code:[A-Z0-9-]+}/misafirler` | auth, can:member.book | BookingFlowController::guests |
| POST | `/rezervasyon/{code:[A-Z0-9-]+}/misafirler` | auth, can:member.book | BookingFlowController::saveGuests |
| GET | `/rezervasyon/{code:[A-Z0-9-]+}/kontrol` | auth, can:member.book | BookingFlowController::review |
| POST | `/rezervasyon/{code:[A-Z0-9-]+}/onayla` | auth, can:member.book | BookingFlowController::confirm |
| GET | `/rezervasyonlarim` | auth | BookingController::index |
| GET | `/rezervasyonlarim/{code:[A-Z0-9-]+}` | auth | BookingController::show |
| POST | `/rezervasyonlarim/{code:[A-Z0-9-]+}/iptal` | auth | BookingController::cancel |
| GET | `/rezervasyonlarim/{code:[A-Z0-9-]+}/voucher` | auth | BookingController::voucher |
| GET | `/teklif-iste` | auth, can:member.book | RequestController::create |
| POST | `/teklif-iste` | auth, can:member.book | RequestController::store |
| GET | `/tekliflerim` | auth | RequestController::index |
| GET | `/tekliflerim/{code:[A-Z0-9-]+}` | auth | RequestController::show |
| POST | `/tekliflerim/{code:[A-Z0-9-]+}/kabul` | auth | RequestController::accept |
| POST | `/tekliflerim/{code:[A-Z0-9-]+}/reddet` | auth | RequestController::decline |
| POST | `/tekliflerim/{code:[A-Z0-9-]+}/iptal` | auth | RequestController::cancel |
| GET | `/bildirimler` | auth | NotificationController::index |
| POST | `/bildirimler/okundu` | auth | NotificationController::markRead |
| GET | `/profilim` | auth | ProfileController::show |
| POST | `/profilim` | auth | ProfileController::update |
| GET | `/sifre-degistir` | auth | ProfileController::password |
| POST | `/sifre-degistir` | auth | ProfileController::updatePassword |
| GET | `/destek` | auth | SupportController::index |
| POST | `/destek` | auth | SupportController::store |
| GET | `/kurumum` | auth, can:institution.own | InstitutionController::index |
| GET | `/kurumum/rezervasyonlar` | auth, can:institution.own | InstitutionController::bookings |
| POST | `/kurumum/basvurular/{id:\d+}` | auth, can:institution.own.applications | InstitutionController::decideApplication |
| GET | `/medya/otel/{id:\d+}/{size:thumb|medium|large}` | auth | MediaController::hotelImage |
| GET | `/medya/oda/{id:\d+}/{size:thumb|medium|large}` | auth | MediaController::roomImage |
| GET | `/medya/bolge/{id:\d+}` | auth | MediaController::region |
| GET | `/medya/kampanya/{id:\d+}` | auth | MediaController::campaign |
| GET | `/medya/kurum/{id:\d+}` | auth | MediaController::institutionLogo |
| GET | `/yonetim` | staff, can:dashboard.view | DashboardController::index |
| GET | `/yonetim/ara` | staff | DashboardController::search |
| GET | `/yonetim/rezervasyonlar` | staff, can:bookings.view | BookingController::index |
| GET | `/yonetim/rezervasyonlar/{id:\d+}` | staff, can:bookings.view | BookingController::show |
| POST | `/yonetim/rezervasyonlar/{id:\d+}/durum` | staff, can:bookings.manage | BookingController::updateStatus |
| POST | `/yonetim/rezervasyonlar/{id:\d+}/bilgi` | staff, can:bookings.manage | BookingController::updateInfo |
| GET | `/yonetim/rezervasyonlar/{id:\d+}/voucher` | staff, can:bookings.view | BookingController::voucher |
| GET | `/yonetim/talepler` | staff, can:requests.manage | RequestController::index |
| GET | `/yonetim/talepler/{id:\d+}` | staff, can:requests.manage | RequestController::show |
| POST | `/yonetim/talepler/{id:\d+}/teklif` | staff, can:requests.manage | RequestController::sendOffer |
| POST | `/yonetim/talepler/{id:\d+}/durum` | staff, can:requests.manage | RequestController::updateStatus |
| GET | `/yonetim/oteller` | staff, can:hotels.view | HotelController::index |
| GET | `/yonetim/oteller/yeni` | staff, can:hotels.manage | HotelController::create |
| POST | `/yonetim/oteller/yeni` | staff, can:hotels.manage | HotelController::store |
| GET | `/yonetim/oteller/{id:\d+}/adim/{step:[1-7]}` | staff, can:hotels.manage | HotelController::step |
| POST | `/yonetim/oteller/{id:\d+}/adim/{step:[1-7]}` | staff, can:hotels.manage | HotelController::saveStep |
| POST | `/yonetim/oteller/{id:\d+}/gorseller` | staff, can:hotels.manage | HotelController::uploadImages |
| POST | `/yonetim/oteller/{id:\d+}/gorseller/{imageId:\d+}` | staff, can:hotels.manage | HotelController::updateImage |
| POST | `/yonetim/oteller/{id:\d+}/yayin` | staff, can:hotels.publish | HotelController::publish |
| GET | `/yonetim/oteller/{id:\d+}/onizleme` | staff, can:hotels.view | HotelController::preview |
| GET | `/yonetim/odalar` | staff, can:hotels.view | RoomController::index |
| POST | `/yonetim/oteller/{id:\d+}/odalar` | staff, can:hotels.manage | RoomController::store |
| POST | `/yonetim/odalar/{id:\d+}` | staff, can:hotels.manage | RoomController::update |
| POST | `/yonetim/odalar/{id:\d+}/gorseller` | staff, can:hotels.manage | RoomController::uploadImages |
| POST | `/yonetim/odalar/{id:\d+}/gorseller/{imageId:\d+}/sil` | staff, can:hotels.manage | RoomController::deleteImage |
| GET | `/yonetim/ozellikler` | staff, can:catalog.manage | LookupController::index |
| POST | `/yonetim/ozellikler` | staff, can:catalog.manage | LookupController::save |
| POST | `/yonetim/ozellikler/{id:\d+}/sil` | staff, can:catalog.manage | LookupController::delete |
| GET | `/yonetim/bolgeler` | staff, can:catalog.manage | LookupController::index |
| POST | `/yonetim/bolgeler` | staff, can:catalog.manage | LookupController::save |
| POST | `/yonetim/bolgeler/{id:\d+}/sil` | staff, can:catalog.manage | LookupController::delete |
| GET | `/yonetim/konseptler` | staff, can:catalog.manage | LookupController::index |
| POST | `/yonetim/konseptler` | staff, can:catalog.manage | LookupController::save |
| POST | `/yonetim/konseptler/{id:\d+}/sil` | staff, can:catalog.manage | LookupController::delete |
| GET | `/yonetim/kurum-tipleri` | staff, can:institutions.manage | LookupController::index |
| POST | `/yonetim/kurum-tipleri` | staff, can:institutions.manage | LookupController::save |
| POST | `/yonetim/kurum-tipleri/{id:\d+}/sil` | staff, can:institutions.manage | LookupController::delete |
| GET | `/yonetim/fiyat-gruplari` | staff, can:institutions.manage | LookupController::index |
| POST | `/yonetim/fiyat-gruplari` | staff, can:institutions.manage | LookupController::save |
| POST | `/yonetim/fiyat-gruplari/{id:\d+}/sil` | staff, can:institutions.manage | LookupController::delete |
| GET | `/yonetim/sezonlar` | staff, can:pricing.manage | LookupController::index |
| POST | `/yonetim/sezonlar` | staff, can:pricing.manage | LookupController::save |
| POST | `/yonetim/sezonlar/{id:\d+}/sil` | staff, can:pricing.manage | LookupController::delete |
| GET | `/yonetim/fiyatlar` | staff, can:pricing.view | PricingController::index |
| POST | `/yonetim/fiyatlar/plan` | staff, can:pricing.manage | PricingController::savePlan |
| POST | `/yonetim/fiyatlar/takvim` | staff, can:pricing.manage | PricingController::saveRates |
| POST | `/yonetim/fiyatlar/referans` | staff, can:pricing.manage | PricingController::saveReference |
| POST | `/yonetim/fiyatlar/referans/{id:\d+}/sil` | staff, can:pricing.manage | PricingController::deleteReference |
| GET | `/yonetim/fiyatlar/hesapla` | staff, can:pricing.view | PricingController::simulate |
| GET | `/yonetim/fiyat-kurallari` | staff, can:pricing.view | RuleController::index |
| POST | `/yonetim/fiyat-kurallari` | staff, can:pricing.manage | RuleController::save |
| POST | `/yonetim/fiyat-kurallari/{id:\d+}/sil` | staff, can:pricing.manage | RuleController::delete |
| GET | `/yonetim/kampanyalar` | staff, can:pricing.view | CampaignController::index |
| POST | `/yonetim/kampanyalar` | staff, can:pricing.manage | CampaignController::save |
| POST | `/yonetim/kampanyalar/{id:\d+}/sil` | staff, can:pricing.manage | CampaignController::delete |
| GET | `/yonetim/promosyonlar` | staff, can:pricing.view | PromoController::index |
| POST | `/yonetim/promosyonlar` | staff, can:pricing.manage | PromoController::save |
| POST | `/yonetim/promosyonlar/{id:\d+}/sil` | staff, can:pricing.manage | PromoController::delete |
| GET | `/yonetim/kontenjan` | staff, can:inventory.manage | InventoryController::index |
| POST | `/yonetim/kontenjan` | staff, can:inventory.manage | InventoryController::save |
| POST | `/yonetim/kontenjan/stop-sale` | staff, can:inventory.manage | InventoryController::stopSale |
| POST | `/yonetim/kontenjan/stop-sale/{id:\d+}/sil` | staff, can:inventory.manage | InventoryController::deleteStopSale |
| GET | `/yonetim/kurumlar` | staff, can:institutions.view | InstitutionController::index |
| POST | `/yonetim/kurumlar` | staff, can:institutions.manage | InstitutionController::save |
| GET | `/yonetim/kurumlar/{id:\d+}` | staff, can:institutions.view | InstitutionController::edit |
| GET | `/yonetim/uyeler` | staff, can:users.view | UserController::index |
| GET | `/yonetim/uyeler/yeni` | staff, can:users.manage | UserController::create |
| POST | `/yonetim/uyeler` | staff, can:users.manage | UserController::save |
| GET | `/yonetim/uyeler/{id:\d+}` | staff, can:users.view | UserController::edit |
| POST | `/yonetim/uyeler/{id:\d+}/durum` | staff, can:users.manage | UserController::status |
| POST | `/yonetim/uyeler/{id:\d+}/parola` | staff, can:users.manage | UserController::sendReset |
| GET | `/yonetim/uyeler/aktar` | staff, can:users.import | ImportController::show |
| POST | `/yonetim/uyeler/aktar` | staff, can:users.import | ImportController::upload |
| POST | `/yonetim/uyeler/aktar/onizleme` | staff, can:users.import | ImportController::preview |
| POST | `/yonetim/uyeler/aktar/tamamla` | staff, can:users.import | ImportController::commit |
| GET | `/yonetim/basvurular` | staff, can:applications.manage | ApplicationController::index |
| POST | `/yonetim/basvurular/{id:\d+}` | staff, can:applications.manage | ApplicationController::decide |
| GET | `/yonetim/api` | staff, can:providers.manage | ProviderController::index |
| GET | `/yonetim/api/{id:\d+}` | staff, can:providers.manage | ProviderController::show |
| POST | `/yonetim/api/{id:\d+}` | staff, can:providers.manage | ProviderController::update |
| POST | `/yonetim/api/{id:\d+}/test` | staff, can:providers.manage | ProviderController::test |
| POST | `/yonetim/api/{id:\d+}/destinasyon` | staff, can:providers.manage | ProviderController::mapDestination |
| POST | `/yonetim/api/{id:\d+}/destinasyon-ara` | staff, can:providers.manage | ProviderController::searchDestination |
| POST | `/yonetim/api/{id:\d+}/otel-esle` | staff, can:providers.manage | ProviderController::mapHotel |
| POST | `/yonetim/api/onbellek-temizle` | staff, can:providers.manage | ProviderController::clearCache |
| GET | `/yonetim/bildirimler` | staff, can:notifications.manage | NotificationController::index |
| POST | `/yonetim/bildirimler/sablon` | staff, can:notifications.manage | NotificationController::saveTemplate |
| POST | `/yonetim/bildirimler/is/{id:\d+}/tekrar` | staff, can:notifications.manage | NotificationController::retryJob |
| POST | `/yonetim/bildirimler/test-eposta` | staff, can:notifications.manage | NotificationController::testMail |
| GET | `/yonetim/raporlar` | staff, can:reports.view | ReportController::index |
| GET | `/yonetim/raporlar/disa-aktar` | staff, can:reports.export | ReportController::export |
| GET | `/yonetim/icerik` | staff, can:content.manage | ContentController::index |
| POST | `/yonetim/icerik/ayarlar` | staff, can:content.manage | ContentController::saveSettings |
| POST | `/yonetim/icerik/sayfa/{id:\d+}` | staff, can:content.manage | ContentController::savePage |
| POST | `/yonetim/icerik/bolumler` | staff, can:content.manage | ContentController::saveSections |
| GET | `/yonetim/ayarlar` | staff, can:settings.manage | SettingsController::index |
| POST | `/yonetim/ayarlar` | staff, can:settings.manage | SettingsController::save |
| GET | `/yonetim/guncelleme` | staff, can:settings.manage | SettingsController::updates |
| POST | `/yonetim/guncelleme` | staff, can:settings.manage | SettingsController::runUpdates |
| GET | `/yonetim/yetkiler` | staff, can:roles.manage|settings.manage | RoleController::index |
| POST | `/yonetim/yetkiler` | staff, can:roles.manage | RoleController::save |
| GET | `/yonetim/audit-log` | staff, can:audit.view | AuditController::index |
| GET | `/yonetim/destek` | staff, can:support.manage | SupportController::index |
| POST | `/yonetim/destek/{id:\d+}` | staff, can:support.manage | SupportController::reply |

Ara katmanlar: `auth` (giriş zorunlu), `guest` (yalnız misafir), `staff` (yönetim erişimi), `can:x` (izin). Tüm POST istekleri CSRF korumalıdır (yalnız gizli anahtarlı cron hariç).

## 9. Yetki matrisi (varsayılan)

Yetkiler yönetimde **Yetkiler** ekranından değiştirilebilir; her istekte sunucu tarafında kontrol edilir. Kurum yöneticisi yalnız kendi kurumunun verisini görür (`InstitutionPolicy`, `BookingPolicy`).

| İzin | Super Admin | Sistem Yöneticisi | Rezervasyon Yetkilisi | Finans Yetkilisi | Kurum Yöneticisi | Standart Üye |
|---|---|---|---|---|---|---|
| Otel arama, rezervasyon ve teklif talebi (`member.book`) | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Yönetim paneline giriş (`admin.access`) | ✓ | ✓ | ✓ | ✓ | — | — |
| Yönetim özetini görüntüleme (`dashboard.view`) | ✓ | ✓ | ✓ | ✓ | — | — |
| Rezervasyonları görüntüleme (`bookings.view`) | ✓ | ✓ | ✓ | ✓ | — | — |
| Rezervasyon durumunu yönetme / otel teyidi girme (`bookings.manage`) | ✓ | ✓ | ✓ | — | — | — |
| Konaklama talepleri ve teklif gönderme (`requests.manage`) | ✓ | ✓ | ✓ | — | — | — |
| Kontenjan ve stop sale yönetimi (`inventory.manage`) | ✓ | ✓ | ✓ | — | — | — |
| Otelleri görüntüleme (`hotels.view`) | ✓ | ✓ | ✓ | ✓ | — | — |
| Otel ve oda ekleme/düzenleme (`hotels.manage`) | ✓ | ✓ | — | — | — | — |
| Oteli yayına alma / kaldırma (`hotels.publish`) | ✓ | ✓ | — | — | — | — |
| Bölge, konsept ve özellik yönetimi (`catalog.manage`) | ✓ | ✓ | — | — | — | — |
| Fiyatları görüntüleme (`pricing.view`) | ✓ | ✓ | ✓ | ✓ | — | — |
| Fiyat, kural, kampanya, promosyon ve sezon yönetimi (`pricing.manage`) | ✓ | ✓ | — | ✓ | — | — |
| Kurumları görüntüleme (`institutions.view`) | ✓ | ✓ | ✓ | ✓ | — | — |
| Kurum ekleme/düzenleme (`institutions.manage`) | ✓ | ✓ | — | — | — | — |
| Üyeleri görüntüleme (`users.view`) | ✓ | ✓ | ✓ | — | — | — |
| Üye ekleme, düzenleme, askıya alma, parola sıfırlama (`users.manage`) | ✓ | ✓ | — | — | — | — |
| CSV/XLSX üye aktarımı (`users.import`) | ✓ | ✓ | — | — | — | — |
| Erişim başvurularını onaylama/reddetme (`applications.manage`) | ✓ | ✓ | — | — | — | — |
| Kendi kurumunun üye ve rezervasyonlarını görme (`institution.own`) | ✓ | ✓ | — | — | ✓ | — |
| Kendi kurumunun başvurularını onaylama (`institution.own.applications`) | ✓ | ✓ | — | — | ✓ | — |
| API sağlayıcıları ve anahtar yönetimi (`providers.manage`) | ✓ | ✓ | — | — | — | — |
| Bildirim şablonları ve iş kuyruğu (`notifications.manage`) | ✓ | ✓ | — | — | — | — |
| Raporları görüntüleme (`reports.view`) | ✓ | ✓ | — | ✓ | — | — |
| Rapor dışa aktarma (CSV/XLSX) (`reports.export`) | ✓ | ✓ | — | ✓ | — | — |
| İçerik, sayfa ve ana sayfa bölümleri (`content.manage`) | ✓ | ✓ | — | — | — | — |
| Sistem ayarları (`settings.manage`) | ✓ | ✓ | — | — | — | — |
| Rol ve yetki matrisi (`roles.manage`) | ✓ | — | — | — | — | — |
| Audit log görüntüleme (`audit.view`) | ✓ | ✓ | — | ✓ | — | — |
| Destek taleplerini yanıtlama (`support.manage`) | ✓ | ✓ | ✓ | — | — | — |
