# Test Sonuçları

Test tarihi: 1 Ekim 2026 (sürüm 1.2.0) · Ortam: PHP 8.3.6, MariaDB 10.11, Apache 2.4.58 + mod_php, Chromium 141 (Playwright ile).

## 1. Otomatik test paketi (`php tests/run.php`)

Gerçek veritabanı üzerinde çalışır (test veritabanı her çalıştırmada sıfırlanır; test verisi yalnız `tests/fixtures` içindedir). Son çalıştırma çıktısı:

```
✔ URL üretimi: kök, tek ve iç içe alt klasör
  ✔ Para: kuruş hesabı, ayrıştırma, biçim
  ✔ Girişsiz otel, fiyat, görsel ve JSON erişimi engellenir
  ✔ Pasif, onaysız, askıdaki ve pasif kurumlu kullanıcı giriş yapamaz
  ✔ Giriş hız sınırı
  ✔ CSRF olmadan POST reddedilir
  ✔ Yetkisiz kullanıcı yönetim alanına giremez
  ✔ Fiyat: varsayılan %10 üye indirimi, kurum indirimi, çocuk ve ek yetişkin
  ✔ Fiyat: kural önceliği, birlikte uygulanma ve azami indirim
  ✔ Referans fiyat: süresi dolan gösterilmez, koşullar farklıysa tasarruf iddiası yok, hedef teklif
  ✔ API: desteklenmeyen işlem başarılı gibi davranmaz; hata durumunda fallback; önbellek
  ✔ Rezervasyon stok düşürür, iptal stoğu geri verir; tekrarlı POST mükerrer kayıt üretmez
  ✔ Fiyat değişince yeniden kabul gerekir
  ✔ Eşzamanlı taleplerde double booking oluşmaz
  ✔ Kurumlar arası veri erişimi engellenir
  ✔ Teklif: eski sürüm ve süresi dolmuş teklif kabul edilemez; kabul otel teyidi sayılmaz
  ✔ Promosyon kodu limiti ve azami indirim
  ✔ Arama kriteri doğrulaması (sunucu tarafı)
  ✔ Dışa aktarım formül enjeksiyonuna karşı korunur
  ✔ CSV aktarımı: zorunlu alan, mükerrer e-posta ve hata raporu
  ✔ Gizli anahtarlar şifreli saklanır ve maskelenir
  ✔ sodium olmayan sunucuda OpenSSL (AES-256-GCM) ile şifreleme
  ✔ Demo modu: yükleme, arama, kaldırma (gerçek kayıtlar korunur)
  ✔ Üyelik kayıt modu: başvuru / açık kayıt / kapalı
  ✔ Sayfa render (üye ve yönetim) — taşma riski olmayan HTML üretimi
  ✔ Cron kuyruğu: süresi dolan teklifler, tamamlanan rezervasyonlar, başarısız iş tekrar denemesi
  ✔ Tüm PHP dosyaları sözdizimi kontrolü (php -l)

254 doğrulama başarılı, 0 başarısız (15.3 sn)
```

Kapsam: kapalı üyelik (girişsiz HTML/JSON/görsel erişimi), pasif/onaysız/askıdaki/pasif kurumlu kullanıcı, giriş hız sınırı, CSRF, rol bazlı yönetim erişimi, kurumlar arası veri izolasyonu (üye ve kurum yöneticisi), fiyat motoru (varsayılan %10, kurum %15, çocuk/ek yetişkin, kural önceliği ve birlikte uygulanma, azami indirim, vergi), süresi dolmuş/koşulları farklı referans fiyat, hedef teklif, anlaşma süresi, sağlayıcı hatasında fallback + kontrollü retry + önbellek + anahtar maskeleme, desteklenmeyen işlem, rezervasyonun stok düşürmesi/iptalin geri vermesi, tekrarlı POST, fiyat değişince yeniden kabul, **6 eşzamanlı süreçle double booking**, teklif sürümü ve süresi, promosyon limiti, sunucu tarafı tarih/kapasite doğrulaması, formül enjeksiyonu, CSV/XLSX aktarım hata raporu, şifreli ayarlar, cron kuyruğu, **demo modu** (yükleme, kesin fiyat / hedef teklif, temsili etiket, deneme rezervasyonuyla birlikte kaldırma, gerçek kayıtların korunması, görsel dosyalarının silinmesi), **üyelik kayıt modları** (başvuru / açık kayıt / kapalı, alan adı kısıtı, zorunlu kurum, mükerrer e-posta, kayıt sonrası oturum), **sodium olmayan sunucuda OpenSSL şifreleme**, PDF voucher ve QR doğrulama sayfası, URL üretimi (kök / tek / iç içe alt klasör), tüm PHP dosyalarının sözdizimi.

## 2. Kurulum yolları (gerçek Apache + .htaccess)

| Kurulum | Sihirbaz | Rewrite testi | Giriş → panel → yönetim | CSS/JS |
|---|---|---|---|---|
| `http://host/` | ✔ | ✔ | ✔ | ✔ |
| `http://host/oteller/` | ✔ | ✔ | ✔ | ✔ |
| `http://host/kurumsal/konaklama/` | ✔ | ✔ | ✔ | ✔ |
| AllowOverride None (rewrite kapalı) | Sihirbaz `index.php/kurulum` ile açılır | ✘ tespit edildi | **Kurulum engellendi**, tanı mesajı gösterildi; kilit dosyası oluşmadı | — |
| Teslim ZIP’inden temiz kurulum (`/kurumsal-konaklama/`) | ✔ | ✔ | ✔ (0 otel, yalnız kurulum yöneticisi) | ✔ |

Doğrudan erişim denetimi (alt klasör kurulumu): `app/`, `config/config.php`, `config/config.example.php`, `storage/*`, `vendor/`, `composer.json/lock`, `bin/cron.php`, `database/migrations/*.sql`, `.htaccess`, `tests/`, `README.md` → **403**. Girişsiz `/oteller`, `/oteller/harita-verisi`, `/medya/otel/…`, `/panel`, `/yonetim` → **/giris’e yönlendirme** (JSON isteğinde 401). Eski `?page=dashboard` / `index.php?page=hotels` → **301** ile temiz adrese. Özel sayfalarda `X-Robots-Tag: noindex, nofollow`; çerez `HttpOnly; SameSite=Lax`, kurulum yoluna göre `path`.

## 3. Uçtan uca tarayıcı senaryoları (teslim ZIP’i üzerinde)

1. Yönetici: içerik, ayarlar, kurum, üye (davet bağlantısı), **7 adımlı otel sihirbazı** (konum, özellikler, gerçek görsel yükleme, oda, politikalar), fiyat planı + 61 günlük toplu fiyat, kontenjan, yayına alma, fiyat kuralı, promosyon, kampanya, yetki matrisi, API anahtarı (kaydedildikten sonra sayfada görünmediği doğrulandı), API bağlantı testi (ağ engeli kontrollü hata olarak raporlandı), CSV üye aktarımı (1 geçerli / 1 hatalı), XLSX rapor indirme.
2. Üye (390 px): davet bağlantısıyla parola oluşturma, giriş, arama (kart fiyatı 17.955 TL), otel detayı, mobil alt çubuktan oda seçimi, misafir bilgileri + promosyon, döküm: *21.000 → erken rez. %5 → üye %10 → promosyon %3 = 17.416,35 TL*, onay, PDF voucher indirme.
3. Misafir erişim başvurusu → yönetici onayı (bağlantı bir kez gösterildi) → teklif talebi → teklif v1 ve v2 → üyede yalnız v2 kabul edilebilir → kabul → “Onay Bekliyor” → otel teyit numarası olmadan onay **reddedildi** → teyit numarasıyla onaylandı → üye bildirimi → destek talebi ve yanıtı → bölge düzenleme → global arama → audit log (27 kayıt).
4. CLI cron (`www-data` kullanıcısıyla): 4 periyodik iş kuyruğa alındı ve tamamlandı.

## 4. Responsive ve erişilebilirlik (360 / 390 / 768 / 1024 / 1440 px)

18 sayfa (giriş, başvuru, panel, arama listesi, harita, otel detayı, rezervasyonlar, teklifler, teklif formu, profil, destek, yönetim dashboard, rezervasyonlar, otel sihirbazı, fiyatlar, kontenjan, yetki matrisi, raporlar) × 5 genişlik:

| Kontrol | Sonuç |
|---|---|
| Yatay taşma | 0 (tüm kombinasyonlar) |
| Mobil menü başlangıçta kapalı, düğme ile açılıyor | ✔ (360–1024) |
| Tarih aralığı takvimi ekrana sığıyor, aralık + gece sayısı doğru | ✔ (mobilde tam genişlik alt panel, masaüstünde 2 ay) |
| Konuk paneli sığıyor; yetişkin/çocuk/yaş/oda ekleme ve gizli form alanları | ✔ (`2 yetişkin + 1 çocuk(7) / 1 yetişkin` → `oda[0][y]=2&oda[0][c]=7&oda[1][y]=1`) |
| 34 px’ten küçük etkileşimli kontrol (takvim günleri hariç) | 0 |
| JavaScript hatası | 0 |

Testler sırasında bulunup düzeltilen hatalar: hero yığın bağlamı nedeniyle mobil takvim arka planının paneli örtmesi, grid sütunlarının içerikle genişleyip taşması, tablo içi `sr-only` etiketlerin taşma yaratması, voucher doğrulama ve cron rotalarında `{n,m}` regex’inin rota ayrıştırıcısını bozması, `bin/cron.php` yorumundaki `*/5` ifadesinin sözdizimi hatası, audit kaydında dizi karşılaştırma hatası, eşzamanlı ikinci onay isteğinin mevcut sonuç yerine hata göstermesi.

## 4b. Sürüm 1.1.0 ek doğrulamaları
| Kontrol | Sonuç |
|---|---|
| ZIP (1.1.0) ile Apache’ye temiz kurulum, sihirbazda “Demo otelleri yükle” + “Açık kayıt” | Kurulum 7,5 sn’de tamamlandı; 14 demo otel, 61 otel görseli yüklendi; giriş ekranında “Hesap oluştur” göründü |
| Demo görsellerine / demo veri klasörüne misafir erişimi | `/medya/...` → girişe yönlendirme; `database/demo/hotels.json` → 403 |
| Yönetim → Demo / Canlı Mod → *Canlı moda geç* (tarayıcıdan) | 14 demo otel ve tüm görsel dosyaları silindi; gerçek kayıtlar korundu |
| Sodium kapalı Apache’de kurulum | Kurulum tamamlandı; gizli ayarlar OpenSSL (AES-256-GCM, `v2:`) ile şifrelendi |
| Mobil (390 px) uçtan uca: ana sayfa takvimi → arama → demo otel → misafir → onay → PDF voucher | Başarılı; takvim alttan açılan sayfa olarak göründü, çıkış tarihi seçilince otomatik uygulandı |
| Masaüstü (1366 px) takvim | Panel `position: fixed` ile body’ye taşındı; üst öğelerin `overflow` kesmesi yok; ekrana sığmıyorsa üstte açılıyor veya sayfa kaydırılıyor |
| Yatay taşma: 10 üye/yönetim sayfası + giriş/başvuru × 360/390/768/1024/1440 px | Taşma yok |

## 4c. Sürüm 1.2.0 — canlı otel verisi
Bu geliştirme ortamının dış ağ erişimi kapalı olduğundan (`overpass-api.de`, `api.liteapi.travel` erişilemez) OpenStreetMap ve LiteAPI **canlı servislerle denenemedi**. LiteAPI uç noktaları ve alan adları resmî `liteapi-node-sdk` 4.3.2 paketinin kaynak kodu ve README’si ile doğrulandı; OpenStreetMap için standart Overpass QL kullanıldı. Doğrulama, gerçek yanıt biçimini taklit eden sahte HTTP yanıtlarıyla yapıldı:

| Senaryo | Sonuç |
|---|---|
| OSM içe aktarma: adsız kayıt atlanır, yıldız/telefon/web/adres aktarılır, uydurma fiyat ve görsel eklenmez, tekrar içe aktarma kopya üretmez | Geçti |
| OSM sağlayıcısından fiyat istenmesi | `UnsupportedCapabilityException` (başarılı gibi davranmaz) |
| LiteAPI: anahtar başlıkta, sandbox tanıma, aynı otelin OSM kaydıyla birleşmesi, HTML temizleme, olanak eşleme, fotoğraf indirme + yeniden kodlama | Geçti |
| Otel sayfasında canlı teklifler, sandbox uyarısı, TL fiyatlar; aramada kesin fiyat | Geçti |
| Pahalı teklif seçildiğinde rezervasyonun o teklifle kalması; prebook → book; sağlayıcı rezervasyon no | Geçti |
| Prebook fiyatı değişince rezervasyonun durdurulması | Geçti (taslak onaylanmadı) |
| İptalin LiteAPI’ye iletilmesi; yönetim ekranında anahtar maskeleme; kaynak kaldırma | Geçti |
| Tarayıcı (1366 px ve 390 px): sonuç listesi, canlı teklifler, temsili bölge görseli, yönetim ekranı; 360–1440 px yatay taşma | Taşma yok |

## 5. Yapılamayan / doğrulanamayan testler (açıkça)
- OpenStreetMap Overpass ve LiteAPI ile **gerçek ağ üzerinden** içe aktarma, canlı fiyat ve sandbox rezervasyonu (ağ erişimi yok). Kurulumdan sonra Yönetim → Canlı Otel Verisi → *Bağlantıyı test et* ile ilk kontrol yapılmalıdır.

- **Gerçek API testi yapılmadı.** StayAPI, Hotelbeds ve Expedia Rapid için geçerli anahtar bulunmuyor ve geliştirme ortamının ağ politikası bu servislere erişimi engelliyor. Adaptörler sahte HTTP yanıtlarıyla (başarı, 503, önbellek) test edildi; canlı yanıt şemaları doğrulanmadı. StayAPI dokümantasyonu doğrudan açılamadı (bkz. `API-SAGLAYICILAR.md`).
- **Gerçek SMTP ile e-posta gönderimi test edilmedi** (sunucu yok). Kuyruk, şablon ve hata/tekrar mekanizması test edildi.
- **OpenStreetMap karoları** ortamın ağ politikası nedeniyle yüklenmedi; Leaflet haritası, işaretçiler ve kaynak gösterimi çalıştı.
- **LiteSpeed** üzerinde test yapılmadı (Apache ile test edildi; .htaccess LiteSpeed uyumlu yazıldı).
- Gerçek ekran okuyucu ile manuel erişilebilirlik testi yapılmadı; etiketler, odak görünürlüğü, aria öznitelikleri, hareket azaltma tercihi ve dokunma alanları kod ve otomatik kontrol ile doğrulandı.
