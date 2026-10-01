# API Sağlayıcıları

Yönetim → **API Yönetimi**. Tüm sağlayıcılar `HotelProviderInterface` sözleşmesini uygular ve desteklediği yetenekleri (`capabilities()`) açıkça bildirir. Desteklenmeyen işlem başarılı gibi davranmaz; `UnsupportedCapabilityException` fırlatır.

## Güvenlik
- Anahtarlar yönetimden girilir, **libsodium ile şifreli** saklanır, ekranda tekrar gösterilmez (yalnız son 4 hane), ön yüze gönderilmez, loglarda maskelenir. Teslim paketinde gerçek anahtar yoktur.
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
| ExpediaRapidProvider | Destinasyon, içerik, müsaitlik, fiyat, price check; ödeme tipi tanımlanırsa rezervasyon/sorgu/iptal | Kod tamam; **canlı test yapılmadı**, EPS sözleşmesi gerektirir |

### StayAPI notları
- Kimlik doğrulama: `x-api-key` başlığı; taban adres `https://api.stayapi.com`. Varsayılan uç noktalar `/v1/booking/destinations` (destinasyon ID çözümleme) ve `/v1/booking/search` (tarihli arama). Yollar yönetimden düzenlenebilir.
- Geliştirme ortamından stayapi.com dokümantasyonuna ağ politikası nedeniyle doğrudan erişilemedi; uç noktalar arama sonuçlarındaki resmi doküman özetlerinden alındı. Yanıt alanları savunmacı biçimde eşlenir. **Canlıya almadan önce geçerli anahtarla “Bağlantı testi” ve bir destinasyon araması yapıp sonuçları kontrol edin.**
- StayAPI **resmi Booking.com partner API’si değildir** ve rezervasyon oluşturmaz. Meta eşleştirme sonuçları fiyat/müsaitlik olarak kullanılmaz; fiyat yalnız tarihli aramadan alınır. Oda tipi, konsept ve iptal koşulu yanıtta yoksa **tasarruf iddiası oluşturulmaz**.
- Ticari kullanım/gösterim izni StayAPI ve ilgili kaynak platformlarla sözleşmeyle doğrulanmalıdır.

### Antalya destinasyon eşleştirme
API Yönetimi → sağlayıcı → *Antalya bölge eşleştirme*: arama yapıp her bölge için doğru sonucu seçin. Türkiye dışı (ör. aynı isimli başka ülke/şehir) sonuçlar uyarıyla gösterilir ve **ek onay olmadan kabul edilmez**; koordinat varsa bölge merkezine uzaklık gösterilir. Otel eşleştirme aynı sayfadan yapılır (dış otel kimliği).

## Yeni sağlayıcı eklemek
1. `app/Providers/Adapters/YeniProvider.php` → `AbstractProvider`’dan türetin, `credentialFields()` ve `capabilities()` tanımlayın, desteklenen metotları uygulayın.
2. Bir migration ile `providers` tablosuna satır ekleyin (`adapter` = sınıf adı, `is_enabled` = 0).
3. Yönetimden anahtarları girip bağlantı testi yapın.

Üçüncü taraf siteler scraping ile kopyalanmaz.
