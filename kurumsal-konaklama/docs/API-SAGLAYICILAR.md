# API Sağlayıcıları

Yönetim → **API Yönetimi**. Tüm sağlayıcılar `HotelProviderInterface` sözleşmesini uygular ve desteklediği yetenekleri (`capabilities()`) açıkça bildirir. Desteklenmeyen işlem başarılı gibi davranmaz; `UnsupportedCapabilityException` fırlatır.

## Güvenlik
- Anahtarlar yönetimden girilir, **şifreli** (libsodium; sunucuda yoksa OpenSSL AES-256-GCM) saklanır, ekranda tekrar gösterilmez (yalnız son 4 hane), ön yüze gönderilmez, loglarda maskelenir. Teslim paketinde gerçek anahtar yoktur.
- HTTP istemcisi: bağlantı + toplam timeout, yalnız ağ hatası / 429 / 5xx için en fazla 2 kontrollü tekrar (rezervasyon oluşturma asla tekrarlanmaz), sağlayıcı bazlı dakika sınırı, `api_request_logs` kaydı.

## Önbellek ve geri dönüş
- İçerik ve fiyat TTL’leri ayrı ve yönetilebilir (Sistem Ayarları). Aynı sorgu önbellekten karşılanır.
- **Sağlayıcı → geçerli önbellek → yerel anlaşmalı veri → manuel teklif talebi.** Sağlayıcı hatasında arama çökmez; kullanıcıya bilgi verilir.
- Sağlayıcı fiyatları `reference_prices` tablosuna **geçerlilik süresiyle** yazılır; süresi geçen fiyat asla güncel/kesin fiyat olarak kullanılmaz.
- Rezervasyon veya teklif kabulünden önce fiyat ve müsaitlik yeniden doğrulanır; değiştiyse yeni toplam gösterilip yeniden onay istenir.

## Ticari izinler
Her sağlayıcı için iki ayrı onay vardır:
- **Gösterim izni doğrulandı**: işaretlenmeden sağlayıcı fiyatı üyelere gösterilmez.
- **Rezervasyon yetkisi doğrulandı**: yalnız `createBooking` destekleyen adaptörlerde verilebilir. Yoksa fiyat yalnız **onaya bağlı hedef teklif** olarak sunulur.

## Adaptörler ve durumları

| Adaptör | Yetenekler | Durum |
|---|---|---|
| ManualHotelProvider | Yerel arama, içerik, müsaitlik, fiyat, yeniden doğrulama, yerel rezervasyon | Tam çalışır, testli |
| StayApiProvider | Destinasyon, tarihli arama, fiyat (arama sonucundan), içerik, sağlık kontrolü. **Rezervasyon YOK** | Kod tamam; **canlı API ile test edilmedi** (aşağıya bakın) |
| HotelbedsProvider | Tüm yetenekler (APItude Booking + Content API, X-Signature) | Kod tamam; **canlı test yapılmadı**, sözleşme + sertifikasyon gerektirir |
| **OsmOverpassProvider** | Gerçek otel kataloğu (ad, yıldız, konum, adres, telefon, web), sağlık kontrolü. **Fiyat/müsaitlik YOK** | Kod tamam, sahte yanıtlarla testli; **canlı Overpass sunucusuyla bu ortamdan denenemedi** (ağ erişimi yok) |
| **LiteApiProvider** | Katalog + içerik + fotoğraf, canlı fiyat (`/hotels/rates`), prebook ile yeniden doğrulama, rezervasyon, sorgu, iptal, sağlık | Kod tamam, sahte yanıtlarla uçtan uca testli; **canlı LiteAPI ile bu ortamdan denenemedi** |
| ExpediaRapidProvider | Destinasyon, içerik, müsaitlik, fiyat, price check; ödeme tipi tanımlanırsa rezervasyon/sorgu/iptal | Kod tamam; **canlı test yapılmadı**, EPS sözleşmesi gerektirir |

### StayAPI notları
- Kimlik doğrulama: `x-api-key` başlığı; taban adres `https://api.stayapi.com`. Varsayılan uç noktalar `/v1/booking/destinations` (destinasyon ID çözümleme) ve `/v1/booking/search` (tarihli arama). Yollar yönetimden düzenlenebilir.
- Geliştirme ortamından stayapi.com dokümantasyonuna ağ politikası nedeniyle doğrudan erişilemedi; uç noktalar arama sonuçlarındaki resmi doküman özetlerinden alındı. Yanıt alanları savunmacı biçimde eşlenir. **Canlıya almadan önce geçerli anahtarla “Bağlantı testi” ve bir destinasyon araması yapıp sonuçları kontrol edin.**
- StayAPI **resmi Booking.com partner API’si değildir** ve rezervasyon oluşturmaz. Meta eşleştirme sonuçları fiyat/müsaitlik olarak kullanılmaz; fiyat yalnız tarihli aramadan alınır. Oda tipi, konsept ve iptal koşulu yanıtta yoksa **tasarruf iddiası oluşturulmaz**.
- Ticari kullanım/gösterim izni StayAPI ve ilgili kaynak platformlarla sözleşmeyle doğrulanmalıdır.

### Antalya destinasyon eşleştirme
API Yönetimi → sağlayıcı → *Antalya bölge eşleştirme*: arama yapıp her bölge için doğru sonucu seçin. Türkiye dışı (ör. aynı isimli başka ülke/şehir) sonuçlar uyarıyla gösterilir ve **ek onay olmadan kabul edilmez**; koordinat varsa bölge merkezine uzaklık gösterilir. Otel eşleştirme aynı sayfadan yapılır (dış otel kimliği).

## Ücretsiz canlı otel verisi (Yönetim → Canlı Otel Verisi)

### OpenStreetMap (anahtarsız)
- Overpass API (`https://overpass-api.de/api/interpreter`, yönetimden değiştirilebilir) ile her bölgenin merkez koordinatı çevresindeki `tourism=hotel` kayıtları alınır (üst bölgeler 9 km, alt bölgeler 5 km).
- Aktarılan alanlar: ad, yıldız (`stars`), konum, adres, telefon, web sitesi, Wi-Fi/havuz/erişilebilirlik etiketleri. **Fiyat, müsaitlik ve fotoğraf yoktur**; bu oteller `booking_mode = request` ile “Teklif iste” akışında çalışır, uydurma fiyat veya görsel eklenmez. Kartta “Temsili bölge görseli” etiketiyle bölge illüstrasyonu gösterilir.
- Lisans: ODbL. Otel sayfasında “© OpenStreetMap katkıcıları” kaynak gösterimi otomatik yer alır. Ortak Overpass sunucusu sınırlı kapasitelidir: içe aktarma bölgeler arasında bekleyerek yapılır, sık tekrarlanmamalıdır.

### LiteAPI (ücretsiz hesap)
- Hesap: https://dashboard.liteapi.travel → API anahtarı. `sand_` ile başlayan **sandbox** anahtarı gerçek otel içeriği ve **test** fiyatları döndürür; sandbox rezervasyonları gerçek değildir (otel sayfasında uyarı gösterilir). Canlı anahtar ile fiyatlar gerçek, rezervasyonlar bağlayıcıdır; ödeme yöntemi (`payment_method`, varsayılan `ACC_CREDIT_CARD`) LiteAPI hesabınızdaki tanıma göre API Yönetimi’nden ayarlanmalıdır.
- Uç noktalar (resmî `liteapi-node-sdk` 4.3.2 kaynağıyla doğrulandı): `GET /data/hotels`, `GET /data/hotel`, `POST /hotels/rates`, `POST book…/rates/prebook`, `POST book…/rates/book`, `GET|PUT book…/bookings/{id}`; kimlik doğrulama `X-API-Key`.
- Akış: arama/otel sayfasında `hotels/rates` (TL, `guestNationality=TR`) → her oda teklifi `reference_prices`’a geçerlilik süresiyle yazılır → üye bir teklifi seçer (seçilen `offerId` rezervasyon boyunca korunur) → onayda `prebook` ile fiyat yeniden doğrulanır, **fiyat değiştiyse rezervasyon durdurulur** → `book` → sağlayıcı rezervasyon numarası saklanır → iptal `PUT /bookings/{id}` ile iletilir. Rezervasyon ve iptal istekleri tekrar denenmez (mükerrer işlem riskine karşı).
- Üye indirimi sağlayıcı fiyatına varsayılan olarak uygulanmaz (`apply_member_discount=false`); sağlayıcı fiyatı kurum indirimiyle düşürülürse fark platformun zararı olur.
- LiteAPI ile içe aktarılan otel, daha önce OpenStreetMap’ten gelen aynı otelle (benzer ad + 300 m) otomatik birleştirilir; fotoğraflar indirilip doğrulanır ve yeniden kodlanarak özel depoda saklanır.

## Yeni sağlayıcı eklemek
1. `app/Providers/Adapters/YeniProvider.php` → `AbstractProvider`’dan türetin, `credentialFields()` ve `capabilities()` tanımlayın, desteklenen metotları uygulayın.
2. Bir migration ile `providers` tablosuna satır ekleyin (`adapter` = sınıf adı, `is_enabled` = 0).
3. Yönetimden anahtarları girip bağlantı testi yapın.

Üçüncü taraf siteler scraping ile kopyalanmaz.
