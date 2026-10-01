<?php
declare(strict_types=1);

/**
 * Otomatik test paketi.
 * Kullanım: php tests/run.php
 * Gereksinim: boş bir test veritabanı (varsayılan konak_test / konak / konakpw; KK_TEST_DB_* ile değiştirilebilir).
 * UYARI: Test veritabanındaki tüm tablolar silinir. Üretim veritabanını KULLANMAYIN.
 */

define('APP_ROOT', dirname(__DIR__));
require APP_ROOT . '/vendor/autoload.php';
require __DIR__ . '/TestCase.php';
require __DIR__ . '/fixtures/fixtures.php';

use App\Core\App;
use App\Core\Auth;
use App\Core\Money;
use App\Core\Url;
use App\DTO\StayCriteria;
use App\Exceptions\DomainException;
use App\Exceptions\PriceChangedException;
use App\Exceptions\UnsupportedCapabilityException;
use App\Exceptions\ValidationException;
use App\Providers\Http\HttpClient;
use App\Providers\ProviderRegistry;
use App\Services\BookingService;
use App\Services\ExportService;
use App\Services\ImportService;
use App\Services\OfferService;
use App\Services\Pricing\PricingContext;
use App\Services\Pricing\PricingProfile;
use App\Services\Pricing\PricingService;
use App\Services\ProviderRateService;
use App\Services\SearchService;
use App\Services\SettingsService;
use App\Validators\StayCriteriaValidator;

TestEnv::boot();
$db = TestEnv::freshDatabase();
$fx = seed_fixtures($db);
$d = static fn (int $days) => date('Y-m-d', time() + $days * 86400);
$crit = static fn (int $in, int $nights, array $rooms = [[2, []]]) => StayCriteria::fromArray(['check_in' => date('Y-m-d', strtotime("+$in day")), 'check_out' => date('Y-m-d', strtotime('+' . ($in + $nights) . ' day')), 'rooms' => array_map(static fn ($r) => ['adults' => $r[0], 'ages' => $r[1]], $rooms)]);
$user = static fn (string $email) => $db->fetch('SELECT * FROM users WHERE email = ?', [$email]);

$tests = [];

// ---------------------------------------------------------------- URL ve kurulum yolu
$tests['URL üretimi: kök, tek ve iç içe alt klasör'] = function () {
    foreach (['' => '/oteller', '/oteller' => '/oteller/oteller', '/kurumsal/konaklama' => '/kurumsal/konaklama/oteller'] as $base => $expected) {
        Url::setBasePath($base);
        T::eq($expected, url('/oteller'), "url() base=$base");
        T::eq(($base === '' ? '' : $base) . '/', url('/'), "kök yol base=$base");
        T::ok(str_starts_with(asset_url('css/app.css'), $base . '/assets/css/app.css?v='), "asset_url base=$base");
        T::eq($base . '/panel', Url::safeRedirectTarget($base . '/panel', '/x'), 'güvenli yönlendirme');
        T::eq($base . '/panel', Url::safeRedirectTarget('https://evil.example/', '/panel'), 'dış yönlendirme engellenir');
        T::eq($base . '/panel', Url::safeRedirectTarget('//evil.example', '/panel'), 'protokol-göreli yönlendirme engellenir');
        $_SERVER['REQUEST_URI'] = $base . '/oteller/abc?x=1';
        T::eq('/oteller/abc', \App\Core\Request::resolvePath($_SERVER), "istek yolu base=$base");
        $_SERVER['REQUEST_URI'] = $base . '/index.php/kurulum';
        T::eq('/kurulum', \App\Core\Request::resolvePath($_SERVER), "index.php/ yolu base=$base");
    }
    T::eq('https://test.local/kurumsal/konaklama/giris', base_url('/giris'), 'mutlak adres yapılandırılmış app.url kullanır');
    T::eq('/kurumsal/konaklama/oteller/test-anlasmali-otel', route_url('hotel.show', ['slug' => 'test-anlasmali-otel']), 'isimli rota');
};

// ---------------------------------------------------------------- Para
$tests['Para: kuruş hesabı, ayrıştırma, biçim'] = function () {
    T::eq(1250050, Money::parse('12.500,50'), 'TR biçim');
    T::eq(1250050, Money::parse('12500.50'), 'nokta ondalık');
    T::eq(150000, Money::parse('1.500'), 'binlik ayırıcı');
    T::eq(1000, Money::parsePercent('10'), 'yüzde');
    T::eq(1250, Money::parsePercent('12,5'), 'ondalık yüzde');
    T::eq(900000, Money::applyDiscount(1000000, 1000), '10.000 TL %10 → 9.000 TL');
    T::eq('9.000 TL', Money::format(900000), 'biçim');
    T::eq('12.500,50 TL', Money::format(1250050), 'kuruşlu biçim');
    T::eq(1, Money::percentOf(5, 1000), 'yarım kuruş yukarı yuvarlama (0,5 → 1)');
    [$gross, $tax] = PricingService::taxes(1000000, true, 1000, 200);
    T::eq(1000000, $gross, 'vergi dahil toplam değişmez');
    T::ok($tax > 0 && $tax < 200000, 'vergi dahil içindeki pay hesaplanır');
    [$gross2] = PricingService::taxes(1000000, false, 1000, 200);
    T::eq(1122000, $gross2, 'vergi hariç: (1+%2)×(1+%10)');
};

// ---------------------------------------------------------------- Kapalı üyelik
$tests['Girişsiz otel, fiyat, görsel ve JSON erişimi engellenir'] = function () use ($fx) {
    TestEnv::logout();
    foreach (['/oteller', '/oteller/test-anlasmali-otel', '/panel', '/favorilerim', '/rezervasyonlarim', '/tekliflerim', '/yonetim', '/medya/otel/1/medium', '/medya/bolge/1', '/teklif-iste'] as $p) {
        $r = TestEnv::request('GET', $p);
        T::eq(302, $r->status(), "$p yönlendirme");
        T::ok(str_ends_with($r->headers()['Location'] ?? '', '/giris'), "$p → /giris");
    }
    $r = TestEnv::request('GET', '/oteller/harita-verisi', [], ['Accept' => 'application/json']);
    T::eq(401, $r->status(), 'JSON uç noktası 401');
    T::ok(!str_contains($r->body(), 'Test Anlaşmalı'), 'JSON yanıtında otel adı yok');
    $login = TestEnv::request('GET', '/giris');
    T::eq(200, $login->status(), 'giriş sayfası açık');
    T::ok(!str_contains($login->body(), 'Test Anlaşmalı'), 'giriş sayfasında otel adı yok');
    foreach (['/kvkk', '/gizlilik', '/kullanim-kosullari', '/cerez-politikasi', '/iletisim', '/erisim-talebi', '/sifremi-unuttum'] as $p) {
        T::eq(200, TestEnv::request('GET', $p)->status(), "$p misafire açık");
    }
};

$tests['Pasif, onaysız, askıdaki ve pasif kurumlu kullanıcı giriş yapamaz'] = function () {
    TestEnv::logout();
    foreach (['bekleyen@test.local' => 'onaylanmadı', 'pasif@test.local' => 'pasif', 'pasifkurum@test.local' => 'Kurumunuzun'] as $email => $needle) {
        TestEnv::logout();
        TestEnv::request('POST', '/giris', ['email' => $email, 'password' => 'Test12345678']);
        T::ok(!Auth::check(), "$email oturum açmamalı");
        T::ok(str_contains(json_encode(\App\Core\Session::get('_flash'), JSON_UNESCAPED_UNICODE) ?: '', $needle) || str_contains(json_encode(\App\Core\Session::get('_errors'), JSON_UNESCAPED_UNICODE), $needle), "$email uygun mesaj");
    }
    TestEnv::logout();
    TestEnv::request('POST', '/giris', ['email' => 'uye@test.local', 'password' => 'yanlis-parola1']);
    T::ok(!Auth::check(), 'yanlış parola');
    TestEnv::logout();
    $r = TestEnv::request('POST', '/giris', ['email' => 'uye@test.local', 'password' => 'Test12345678']);
    T::ok(str_ends_with($r->headers()['Location'] ?? '', '/panel'), 'aktif üye giriş yapar');
    // Oturum açıkken hesap pasifleşirse oturum düşer
    App::db()->update('users', ['status' => 'suspended'], ['email' => 'uye@test.local']);
    Auth::reset();
    T::ok(Auth::user() === null, 'askıya alınan kullanıcının oturumu düşer');
    App::db()->update('users', ['status' => 'active'], ['email' => 'uye@test.local']);
};

$tests['Giriş hız sınırı'] = function () {
    TestEnv::logout();
    $ip = '192.0.2.10';
    for ($i = 0; $i < 5; $i++) {
        try {
            \App\Services\AuthService::attempt('kurum@test.local', 'yanlis' . $i . 'xxxxxx', $ip);
        } catch (DomainException) {
        }
    }
    T::throws(DomainException::class, fn () => \App\Services\AuthService::attempt('kurum@test.local', 'Test12345678', $ip), 'kilit sonrası doğru parola da reddedilir');
    \App\Services\RateLimiter::clear('login:kurum@test.local');
};

$tests['CSRF olmadan POST reddedilir'] = function () {
    TestEnv::actingAs('uye@test.local');
    $r = TestEnv::request('POST', '/profilim', ['first_name' => 'X', 'last_name' => 'Y'], [], false);
    T::eq(419, $r->status(), 'CSRF 419');
};

$tests['Yetkisiz kullanıcı yönetim alanına giremez'] = function () {
    TestEnv::actingAs('uye@test.local');
    foreach (['/yonetim', '/yonetim/oteller', '/yonetim/uyeler', '/yonetim/ayarlar', '/yonetim/api'] as $p) {
        T::eq(403, TestEnv::request('GET', $p)->status(), "üye $p → 403");
    }
    TestEnv::actingAs('rez@test.local');
    T::eq(200, TestEnv::request('GET', '/yonetim/rezervasyonlar')->status(), 'rezervasyon yetkilisi rezervasyonları görür');
    T::eq(403, TestEnv::request('GET', '/yonetim/ayarlar')->status(), 'rezervasyon yetkilisi ayarlara giremez');
    T::eq(403, TestEnv::request('GET', '/yonetim/yetkiler')->status(), 'rezervasyon yetkilisi yetki matrisine giremez');
    TestEnv::actingAs('admin@test.local');
    T::eq(200, TestEnv::request('GET', '/yonetim')->status(), 'super admin dashboard');
};

// ---------------------------------------------------------------- Fiyat motoru
$tests['Fiyat: varsayılan %10 üye indirimi, kurum indirimi, çocuk ve ek yetişkin'] = function () use ($fx, $crit, $user, $db) {
    $c = $crit(10, 2);
    $ctx = PricingContext::load($db, [$fx['h1']], $c);
    $q = (new PricingService())->quote($ctx, PricingProfile::forUser($db, $user('uye@test.local')), $fx['h1'], $fx['r1'], $fx['p1']);
    T::eq('firm', $q->kind, 'anlaşmalı + satış yetkili + stoklu → kesin fiyat');
    T::eq(1000000, $q->sourceTotal, 'kaynak 2 gece × 5.000');
    T::eq(900000, $q->total, '%10 indirim → 9.000 TL');
    $q2 = (new PricingService())->quote($ctx, PricingProfile::forUser($db, $user('uye2@test.local')), $fx['h1'], $fx['r1'], $fx['p1']);
    T::eq(850000, $q2->total, 'kurum B %15 → 8.500 TL');
    SettingsService::set('pricing.default_discount_bp', '1200');
    $q3 = (new PricingService())->quote($ctx, PricingProfile::forUser($db, $user('uye@test.local')), $fx['h1'], $fx['r1'], $fx['p1']);
    T::eq(880000, $q3->total, 'varsayılan indirim yönetim ayarından okunur');
    SettingsService::set('pricing.default_discount_bp', '1000');
    $c2 = $crit(10, 1, [[2, [5, 9]]]);
    $ctx2 = PricingContext::load($db, [$fx['h1']], $c2);
    $q4 = (new PricingService())->quote($ctx2, PricingProfile::forUser($db, $user('uye@test.local')), $fx['h1'], $fx['r1'], $fx['p1']);
    T::eq(500000 + 100000, $q4->sourceTotal, '2 yetişkin + 5 yaş (ücretsiz) + 9 yaş (ücretli çocuk)');
    $q5 = (new PricingService())->quote(PricingContext::load($db, [$fx['h1']], $crit(10, 1, [[3, []]])), PricingProfile::forUser($db, $user('uye@test.local')), $fx['h1'], $fx['r1'], $fx['p1']);
    T::eq(650000, $q5->sourceTotal, '3. yetişkin ek ücret');
    $c3 = $crit(10, 1, [[4, []]]);
    $ctx3 = PricingContext::load($db, [$fx['h1']], $c3);
    T::eq('unavailable', (new PricingService())->quote($ctx3, PricingProfile::forUser($db, null), $fx['h1'], $fx['r1'], $fx['p1'])->kind, 'kapasite aşımı sunucuda reddedilir');
};

$tests['Fiyat: kural önceliği, birlikte uygulanma ve azami indirim'] = function () use ($fx, $crit, $user, $db) {
    $ids = [];
    $ids[] = $db->insert('rate_rules', ['name' => 'Tekil A %5', 'kind' => 'hotel', 'adjustment' => 'discount_percent', 'value' => 500, 'hotel_id' => $fx['h1'], 'priority' => 200, 'stackable' => 0]);
    $ids[] = $db->insert('rate_rules', ['name' => 'Tekil B %20 (düşük öncelik)', 'kind' => 'global', 'adjustment' => 'discount_percent', 'value' => 2000, 'priority' => 100, 'stackable' => 0]);
    $ids[] = $db->insert('rate_rules', ['name' => 'Erken rez. %10', 'kind' => 'early_booking', 'adjustment' => 'discount_percent', 'value' => 1000, 'min_lead_days' => 5, 'priority' => 150, 'stackable' => 1]);
    $c = $crit(10, 1);
    $ctx = PricingContext::load($db, [$fx['h1']], $c);
    $q = (new PricingService())->quote($ctx, PricingProfile::forUser($db, $user('uye@test.local')), $fx['h1'], $fx['r1'], $fx['p1']);
    // 500000 → tekil A %5 (öncelik 200) = 475000 → erken %10 = 427500 → üye %10 = 384750
    T::eq(384750, $q->total, 'yalnız en yüksek öncelikli tekil kural + birlikte uygulananlar + üye indirimi');
    $labels = array_column($q->breakdown['lines'], 'label');
    T::ok(in_array('Tekil A %5', $labels, true) && !in_array('Tekil B %20 (düşük öncelik)', $labels, true), 'hesap dökümü uygulanan kuralları içerir');
    SettingsService::set('pricing.max_discount_bp', '2000');
    $q2 = (new PricingService())->quote(PricingContext::load($db, [$fx['h1']], $c), PricingProfile::forUser($db, $user('uye@test.local')), $fx['h1'], $fx['r1'], $fx['p1']);
    T::eq(400000, $q2->total, 'azami %20 indirim sınırı');
    SettingsService::set('pricing.max_discount_bp', '3000');
    $cLate = $crit(2, 1);
    $q3 = (new PricingService())->quote(PricingContext::load($db, [$fx['h1']], $cLate), PricingProfile::forUser($db, $user('uye@test.local')), $fx['h1'], $fx['r1'], $fx['p1']);
    T::eq(427500, $q3->total, 'erken rezervasyon koşulu sağlanmazsa uygulanmaz');
    foreach ($ids as $id) {
        $db->delete('rate_rules', ['id' => $id]);
    }
};

$tests['Referans fiyat: süresi dolan gösterilmez, koşullar farklıysa tasarruf iddiası yok, hedef teklif'] = function () use ($fx, $crit, $user, $db) {
    $c = $crit(20, 2);
    $ai = (int) $db->value("SELECT id FROM concepts WHERE code = 'AI'");
    $base = ['hotel_id' => $fx['h1'], 'room_id' => $fx['r1'], 'source' => 'manual_reference', 'source_label' => 'Test', 'currency' => 'TRY', 'tax_included' => 1,
        'check_in' => $c->checkIn, 'check_out' => $c->checkOut, 'adults' => 2, 'children_ages' => '', 'rooms_count' => 1, 'concept_id' => $ai, 'refundable' => 1,
        'captured_at' => date('Y-m-d H:i:s'), 'verified_at' => date('Y-m-d H:i:s'), 'verified_by' => 1];
    $expired = $db->insert('reference_prices', ['total_minor' => 1200000, 'valid_until' => date('Y-m-d H:i:s', time() - 60)] + $base);
    $q = (new PricingService())->quote(PricingContext::load($db, [$fx['h1']], $c), PricingProfile::forUser($db, $user('uye@test.local')), $fx['h1'], $fx['r1'], $fx['p1']);
    T::eq(null, $q->verifiedSavings, 'süresi dolmuş referans karşılaştırmada kullanılmaz');
    $other = $db->insert('reference_prices', ['total_minor' => 1200000, 'valid_until' => date('Y-m-d H:i:s', time() + 3600), 'refundable' => 0] + $base);
    $q = (new PricingService())->quote(PricingContext::load($db, [$fx['h1']], $c), PricingProfile::forUser($db, $user('uye@test.local')), $fx['h1'], $fx['r1'], $fx['p1']);
    T::eq(null, $q->verifiedSavings, 'iptal koşulu farklı → tasarruf iddiası yok');
    $match = $db->insert('reference_prices', ['total_minor' => 1200000, 'valid_until' => date('Y-m-d H:i:s', time() + 3600)] + $base);
    $q = (new PricingService())->quote(PricingContext::load($db, [$fx['h1']], $c), PricingProfile::forUser($db, $user('uye@test.local')), $fx['h1'], $fx['r1'], $fx['p1']);
    T::eq(300000, $q->verifiedSavings, 'aynı koşullarda doğrulanmış tasarruf = 12.000 − 9.000');
    // Anlaşması olmayan otel: referans → onaya bağlı hedef teklif (kesin değil)
    $db->insert('reference_prices', ['hotel_id' => $fx['h3'], 'room_id' => null, 'concept_id' => null, 'total_minor' => 1000000, 'valid_until' => date('Y-m-d H:i:s', time() + 3600)] + array_diff_key($base, ['hotel_id' => 1, 'room_id' => 1, 'concept_id' => 1]));
    $best = (new PricingService())->bestForHotel(PricingContext::load($db, [$fx['h3']], $c), PricingProfile::forUser($db, $user('uye@test.local')), $fx['h3']);
    T::eq('target', $best->kind, 'dış fiyat satış yetkisi vermez → hedef teklif');
    T::eq(900000, $best->total, 'hedef teklif = referans − %10');
    T::throws(DomainException::class, fn () => (new BookingService($db))->startDraft($user('uye@test.local'), $fx['h3'], $fx['r3'], null, $c, null, 'x1'), 'hedef teklif ile rezervasyon başlatılamaz');
    $db->query('DELETE FROM reference_prices WHERE id IN (?, ?, ?)', [$expired, $other, $match]);
    // Anlaşma süresi biterse kesin fiyat gösterilmez
    $db->update('hotels', ['contract_valid_until' => date('Y-m-d', strtotime('-1 day'))], ['id' => $fx['h1']]);
    $q = (new PricingService())->quote(PricingContext::load($db, [$fx['h1']], $c), PricingProfile::forUser($db, $user('uye@test.local')), $fx['h1'], $fx['r1'], $fx['p1']);
    T::eq('target', $q->kind, 'anlaşması biten otelde fiyat onaya bağlı olur');
    $db->update('hotels', ['contract_valid_until' => null], ['id' => $fx['h1']]);
};

// ---------------------------------------------------------------- Sağlayıcı / fallback
$tests['API: desteklenmeyen işlem başarılı gibi davranmaz; hata durumunda fallback; önbellek'] = function () use ($fx, $crit, $user, $db) {
    $stay = (int) $db->value("SELECT id FROM providers WHERE code = 'stayapi'");
    $provider = ProviderRegistry::find($stay);
    T::throws(UnsupportedCapabilityException::class, fn () => $provider->createBooking('x', $crit(5, 1), [], [], 'KK-1'), 'StayAPI createBooking desteklenmez');
    T::throws(UnsupportedCapabilityException::class, fn () => $provider->cancelBooking('x'), 'StayAPI cancel desteklenmez');
    $db->update('providers', ['is_enabled' => 1, 'display_authorized' => 1], ['id' => $stay]);
    $db->insert('provider_credentials', ['provider_id' => $stay, 'key_name' => 'api_key', 'value_encrypted' => \App\Core\Crypto::encrypt('test-anahtar-1234'), 'last4' => '1234']);
    $db->insert('provider_destinations', ['provider_id' => $stay, 'region_id' => $db->value("SELECT id FROM regions WHERE slug = 'lara'"), 'external_id' => '-755070', 'external_name' => 'Lara, Antalya', 'country_code' => 'TR', 'verified_at' => date('Y-m-d H:i:s')]);
    $db->insert('provider_hotel_map', ['provider_id' => $stay, 'hotel_id' => $fx['h3'], 'external_hotel_id' => '998877']);
    SettingsService::set('providers.live_search', '1');
    ProviderRegistry::reset();
    // 1) Sağlayıcı hatası → sistem çökmez, yerel sonuçlar döner
    $calls = 0;
    HttpClient::$fake = function () use (&$calls) { $calls++; return ['status' => 503, 'headers' => [], 'body' => 'down']; };
    $svc = new SearchService($db);
    $res = $svc->search($user('uye@test.local'), $crit(30, 2), [], 'onerilen', 1);
    T::ok($res['page']['total'] >= 3, 'sağlayıcı hatasında yerel oteller listelenir');
    T::ok((bool) $svc->notices, 'kullanıcıya bilgilendirme gösterilir');
    T::eq(3, $calls, 'kontrollü tekrar: 1 + 2 retry');
    T::eq('degraded', $db->value('SELECT status FROM providers WHERE id = ?', [$stay]), 'sağlayıcı durumu güncellenir');
    T::ok(!str_contains((string) $db->value('SELECT request_summary FROM api_request_logs WHERE provider_id = ? ORDER BY id DESC LIMIT 1', [$stay]), 'test-anahtar'), 'anahtar loglanmaz');
    // 2) Başarılı yanıt → referans fiyat (hedef teklif), ikinci arama önbellekten
    \App\Services\RateLimiter::clear('provider:' . $stay);
    $calls = 0;
    HttpClient::$fake = function ($m, $url, $h) use (&$calls) {
        $calls++;
        T::eq('test-anahtar-1234', $h['x-api-key'] ?? null, 'anahtar başlıkta sunucu tarafından gönderilir');
        return ['status' => 200, 'headers' => [], 'body' => json_encode(['data' => [['hotel_id' => 998877, 'name' => 'X', 'price' => ['total' => '20000.00', 'currency' => 'TRY']]]])];
    };
    $c = $crit(31, 2);
    $svc2 = new SearchService($db);
    $res = $svc2->search($user('uye@test.local'), $c, ['ids' => [$fx['h3']]], 'onerilen', 1);
    $q = $res['items'][0]['quote'];
    T::eq('target', $q->kind, 'yetkisiz sağlayıcı fiyatı hedef teklif olur');
    T::eq(1800000, $q->total, '20.000 − %10');
    (new SearchService($db))->search($user('uye@test.local'), $c, ['ids' => [$fx['h3']]], 'onerilen', 1);
    T::eq(1, $calls, 'aynı sorgu önbellekten (tekrar API çağrısı yok)');
    // 3) Gösterim izni kaldırılırsa fiyat gösterilmez
    $db->update('providers', ['display_authorized' => 0], ['id' => $stay]);
    $best = (new PricingService())->bestForHotel(PricingContext::load($db, [$fx['h3']], $c), PricingProfile::forUser($db, $user('uye@test.local')), $fx['h3']);
    T::ok($best->kind !== 'target', 'gösterim izni yoksa sağlayıcı fiyatı kullanılmaz');
    HttpClient::$fake = null;
    SettingsService::set('providers.live_search', '0');
    $db->update('providers', ['is_enabled' => 0], ['id' => $stay]);
};

// ---------------------------------------------------------------- Rezervasyon
$tests['Rezervasyon stok düşürür, iptal stoğu geri verir; tekrarlı POST mükerrer kayıt üretmez'] = function () use ($fx, $crit, $user, $db) {
    $u = $user('uye@test.local');
    $c = $crit(40, 2);
    $svc = new BookingService($db);
    $draft = $svc->startDraft($u, $fx['h1'], $fx['r1'], $fx['p1'], $c, null, 'idem-1');
    $again = $svc->startDraft($u, $fx['h1'], $fx['r1'], $fx['p1'], $c, null, 'idem-1');
    T::eq((int) $draft['id'], (int) $again['id'], 'aynı idempotency anahtarı aynı taslağı döndürür');
    $svc->saveGuests($draft, ['misafir' => [['ad' => 'Mehmet', 'soyad' => 'Üye']], 'iletisim_telefon' => '05551112233', 'iletisim_eposta' => 'uye@test.local']);
    $draft = $db->fetch('SELECT * FROM bookings WHERE id = ?', [$draft['id']]);
    $b = $svc->confirm($draft, $u, $draft['quote_hash']);
    T::eq('confirmed', $b['status'], 'anında rezervasyon onaylandı');
    T::eq(1, (int) $db->value('SELECT booked_units FROM inventory WHERE room_id = ? AND stay_date = ?', [$fx['r1'], $c->checkIn]), 'stok düştü');
    $b2 = $svc->confirm($draft, $u, $draft['quote_hash']);
    T::eq((int) $b['id'], (int) $b2['id'], 'tekrar onay aynı kaydı döndürür');
    T::eq(1, (int) $db->value('SELECT booked_units FROM inventory WHERE room_id = ? AND stay_date = ?', [$fx['r1'], $c->checkIn]), 'tekrar onay stoğu ikinci kez düşürmez');
    T::eq(1, (int) $db->value("SELECT COUNT(*) FROM bookings WHERE user_id = ? AND check_in = ? AND status <> 'draft'", [$u['id'], $c->checkIn]), 'tek rezervasyon kaydı');
    T::ok((bool) $b['verify_token'] && strlen((string) $b['verify_token']) >= 40, 'voucher doğrulama belirteci üretildi');
    // HTTP düzeyinde tekrar POST
    TestEnv::actingAs('uye@test.local');
    $r = TestEnv::request('POST', '/rezervasyon/' . $b['code'] . '/onayla', ['kosullar' => '1', 'quote_hash' => $draft['quote_hash']]);
    T::ok(str_ends_with($r->headers()['Location'] ?? '', '/rezervasyonlarim/' . $b['code']), 'tekrar POST mevcut rezervasyona yönlendirir');
    // Voucher
    $pdf = \App\Services\VoucherService::render((int) $b['id']);
    T::ok(str_starts_with($pdf, '%PDF'), 'PDF voucher üretildi');
    $verify = TestEnv::request('GET', '/voucher/dogrula/' . $b['verify_token']);
    T::ok(str_contains($verify->body(), 'Geçerli rezervasyon') && !str_contains($verify->body(), 'Test Anlaşmalı') && !str_contains($verify->body(), 'Mehmet Üye'), 'doğrulama sayfası otel adı ve tam isim göstermez');
    // İptal
    $svc->cancelByUser($b, $u, 'test');
    T::eq(0, (int) $db->value('SELECT booked_units FROM inventory WHERE room_id = ? AND stay_date = ?', [$fx['r1'], $c->checkIn]), 'iptal stoğu geri verdi');
    T::eq('cancelled', $db->value('SELECT status FROM bookings WHERE id = ?', [$b['id']]), 'durum iptal');
    T::throws(DomainException::class, fn () => \App\Services\VoucherService::render((int) $b['id']), 'iptal edilen için voucher üretilmez');
};

$tests['Fiyat değişince yeniden kabul gerekir'] = function () use ($fx, $crit, $user, $db) {
    $u = $user('uye@test.local');
    $c = $crit(45, 1);
    $svc = new BookingService($db);
    $draft = $svc->startDraft($u, $fx['h1'], $fx['r1'], $fx['p1'], $c, null, 'idem-price');
    $svc->saveGuests($draft, ['misafir' => [['ad' => 'Mehmet', 'soyad' => 'Üye']], 'iletisim_telefon' => '05551112233', 'iletisim_eposta' => 'uye@test.local']);
    $oldHash = $draft['quote_hash'];
    $db->update('rates', ['price_minor' => 550000], ['rate_plan_id' => $fx['p1'], 'stay_date' => $c->checkIn]);
    $e = T::throws(PriceChangedException::class, fn () => $svc->confirm($db->fetch('SELECT * FROM bookings WHERE id = ?', [$draft['id']]), $u, $oldHash), 'eski fiyatla onay reddedilir');
    T::eq(495000, $e instanceof PriceChangedException ? $e->newTotal : null, 'yeni toplam gösterilir');
    $fresh = $db->fetch('SELECT * FROM bookings WHERE id = ?', [$draft['id']]);
    T::eq('draft', $fresh['status'], 'rezervasyon taslakta kalır');
    T::eq(0, (int) $db->value('SELECT booked_units FROM inventory WHERE room_id = ? AND stay_date = ?', [$fx['r1'], $c->checkIn]), 'stok düşmez');
    $b = $svc->confirm($fresh, $u, $fresh['quote_hash']);
    T::eq('confirmed', $b['status'], 'yeni toplam kabul edilince onaylanır');
    T::eq(495000, (int) $b['total_minor'], 'kayıttaki toplam yeni fiyat');
    $db->update('rates', ['price_minor' => 500000], ['rate_plan_id' => $fx['p1'], 'stay_date' => $c->checkIn]);
};

$tests['Eşzamanlı taleplerde double booking oluşmaz'] = function () use ($fx, $crit, $db) {
    $c = $crit(60, 2);
    $drafts = [];
    $svc = new BookingService($db);
    foreach (['uye@test.local', 'uye2@test.local', 'kurum@test.local', 'admin@test.local', 'rez@test.local', 'uye@test.local'] as $i => $email) {
        $u = $db->fetch('SELECT * FROM users WHERE email = ?', [$email]);
        $dr = $svc->startDraft($u, $fx['h1'], $fx['r1'], $fx['p1'], $c, null, 'conc-' . $i);
        $svc->saveGuests($dr, ['misafir' => [['ad' => 'Ad', 'soyad' => 'Soyad']], 'iletisim_telefon' => '05551112233', 'iletisim_eposta' => 'a@b.co']);
        $drafts[] = (int) $dr['id'];
    }
    $procs = [];
    foreach ($drafts as $id) {
        $procs[] = proc_open([PHP_BINARY, __DIR__ . '/concurrency_worker.php', (string) $id], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        $outs[] = $pipes;
    }
    $results = [];
    foreach ($procs as $i => $p) {
        $results[] = trim(stream_get_contents($outs[$i][1]) . stream_get_contents($outs[$i][2]));
        proc_close($p);
    }
    $ok = count(array_filter($results, static fn ($r) => $r === 'OK'));
    T::eq(1, $ok, '6 eşzamanlı istekten yalnız biri başarılı (stok: 1) — sonuçlar: ' . implode(',', $results));
    T::eq(1, (int) $db->value('SELECT booked_units FROM inventory WHERE room_id = ? AND stay_date = ?', [$fx['r1'], $c->checkIn]), 'stok yalnız bir kez düştü');
    T::eq(0, (int) $db->value('SELECT COUNT(*) FROM inventory WHERE room_id = ? AND booked_units > total_units', [$fx['r1']]), 'aşırı satış yok');
};

$tests['Kurumlar arası veri erişimi engellenir'] = function () use ($fx, $crit, $user, $db) {
    $u = $user('uye@test.local');
    $c = $crit(70, 1);
    $svc = new BookingService($db);
    $dr = $svc->startDraft($u, $fx['h2'], $fx['r2'], $fx['p2'], $c, null, 'iso-1');
    $svc->saveGuests($dr, ['misafir' => [['ad' => 'Mehmet', 'soyad' => 'Üye']], 'iletisim_telefon' => '05551112233', 'iletisim_eposta' => 'uye@test.local']);
    $b = $svc->confirm($db->fetch('SELECT * FROM bookings WHERE id = ?', [$dr['id']]), $u, $dr['quote_hash']);
    T::eq('requested', $b['status'], 'otel teyidine bağlı mod → Talep Alındı');
    TestEnv::actingAs('uye2@test.local');
    T::eq(404, TestEnv::request('GET', '/rezervasyonlarim/' . $b['code'])->status(), 'başka kurumdaki üye göremez');
    T::eq(404, TestEnv::request('GET', '/rezervasyonlarim/' . $b['code'] . '/voucher')->status(), 'başka kurumdaki üye voucher indiremez');
    T::eq(404, TestEnv::request('GET', '/rezervasyon/' . $dr['code'] . '/kontrol')->status(), 'başkasının taslağı açılamaz');
    TestEnv::actingAs('kurum@test.local');
    T::eq(200, TestEnv::request('GET', '/rezervasyonlarim/' . $b['code'])->status(), 'aynı kurumun yöneticisi görür');
    $r = TestEnv::request('GET', '/kurumum/rezervasyonlar');
    T::ok(str_contains($r->body(), $b['code']), 'kurum yöneticisi listesinde kendi kurumu');
    $u2 = $user('uye2@test.local');
    $dr2 = $svc->startDraft($u2, $fx['h2'], $fx['r2'], $fx['p2'], $crit(71, 1), null, 'iso-2');
    $svc->saveGuests($dr2, ['misafir' => [['ad' => 'Zeynep', 'soyad' => 'Diğer']], 'iletisim_telefon' => '05551112233', 'iletisim_eposta' => 'uye2@test.local']);
    $b2 = $svc->confirm($db->fetch('SELECT * FROM bookings WHERE id = ?', [$dr2['id']]), $u2, $dr2['quote_hash']);
    T::eq(404, TestEnv::request('GET', '/rezervasyonlarim/' . $b2['code'])->status(), 'kurum yöneticisi başka kurumun rezervasyonunu göremez');
    T::ok(!str_contains(TestEnv::request('GET', '/kurumum/rezervasyonlar')->body(), $b2['code']), 'başka kurumun kaydı listede yok');
    T::eq(403, TestEnv::request('POST', '/rezervasyonlarim/' . $b['code'] . '/iptal', ['neden' => 'x'])->status(), 'kurum yöneticisi üyenin rezervasyonunu iptal edemez');
    // Otel teyidi olmadan onay verilemez
    TestEnv::actingAs('rez@test.local');
    T::throws(DomainException::class, fn () => $svc->changeStatus($b, 'confirmed', (int) $user('rez@test.local')['id'], '', null), 'otel teyit numarası olmadan onaylanamaz');
    $svc->changeStatus($b, 'confirmed', (int) $user('rez@test.local')['id'], '', 'OTEL-778');
    T::eq('confirmed', $db->value('SELECT status FROM bookings WHERE id = ?', [$b['id']]), 'teyit numarasıyla onaylandı');
};

$tests['Teklif: eski sürüm ve süresi dolmuş teklif kabul edilemez; kabul otel teyidi sayılmaz'] = function () use ($fx, $crit, $user, $db) {
    $u = $user('uye@test.local');
    $svc = new OfferService($db);
    $c = $crit(80, 3);
    $req = $svc->createRequest($u, $c, ['hotel_id' => $fx['h3'], 'notes' => 'test'], 'off-1');
    $again = $svc->createRequest($u, $c, ['hotel_id' => $fx['h3']], 'off-1');
    T::eq((int) $req['id'], (int) $again['id'], 'tekrarlı talep tek kayıt');
    $admin = (int) $user('admin@test.local')['id'];
    $base = ['hotel_id' => $fx['h3'], 'room_id' => $fx['r3'], 'room_name' => '', 'concept_id' => null, 'check_in' => $c->checkIn, 'check_out' => $c->checkOut, 'payment_terms' => 'Otelde ödeme', 'cancellation_terms' => '7 gün öncesine kadar ücretsiz', 'valid_until' => date('Y-m-d H:i:s', time() + 3600)];
    $o1 = $svc->sendOffer($req, $admin, $base + ['total_minor' => 1500000]);
    $req = $db->fetch('SELECT * FROM accommodation_requests WHERE id = ?', [$req['id']]);
    $o2 = $svc->sendOffer($req, $admin, $base + ['total_minor' => 1400000]);
    T::eq(2, (int) $o2['version'], 'yeni sürüm');
    T::throws(DomainException::class, fn () => $svc->accept($req, $u, (int) $o1['id'], 1), 'eski sürüm kabul edilemez');
    $db->update('offers', ['valid_until' => date('Y-m-d H:i:s', time() - 10)], ['id' => $o2['id']]);
    T::throws(DomainException::class, fn () => $svc->accept($req, $u, (int) $o2['id'], 2), 'süresi dolmuş teklif kabul edilemez');
    $req = $db->fetch('SELECT * FROM accommodation_requests WHERE id = ?', [$req['id']]);
    $o3 = $svc->sendOffer($req, $admin, $base + ['total_minor' => 1350000]);
    T::throws(DomainException::class, fn () => $svc->accept($req, $user('uye2@test.local'), (int) $o3['id'], 3), 'başkası kabul edemez');
    $b = $svc->accept($req, $u, (int) $o3['id'], 3);
    T::eq('pending', $b['status'], 'kabul → Onay Bekliyor (otel teyidi değil)');
    T::eq(1350000, (int) $b['total_minor'], 'teklif tutarı');
    $b2 = $svc->accept($req, $u, (int) $o3['id'], 3);
    T::eq((int) $b['id'], (int) $b2['id'], 'tekrarlı kabul aynı rezervasyon');
};

$tests['Promosyon kodu limiti ve azami indirim'] = function () use ($fx, $crit, $user, $db) {
    $db->insert('promo_codes', ['code' => 'TEST5', 'adjustment' => 'discount_percent', 'value' => 500, 'max_uses' => 1, 'is_active' => 1]);
    $u = $user('uye@test.local');
    $p = (new \App\Services\PromoService($db))->resolve('test5', $u, $fx['h1'], 1);
    $q = (new PricingService())->quote(PricingContext::load($db, [$fx['h1']], $crit(90, 1)), PricingProfile::forUser($db, $u), $fx['h1'], $fx['r1'], $fx['p1'], $p);
    T::eq(427500, $q->total, '5000 → %10 üye → %5 promosyon');
    (new \App\Services\PromoService($db))->consume((int) $p['id']);
    T::throws(DomainException::class, fn () => (new \App\Services\PromoService($db))->resolve('TEST5', $u, $fx['h1'], 1), 'kullanım limiti dolunca reddedilir');
};

// ---------------------------------------------------------------- Diğer
$tests['Arama kriteri doğrulaması (sunucu tarafı)'] = function () use ($d) {
    T::throws(ValidationException::class, fn () => StayCriteriaValidator::fromInput(['giris' => $d(-1), 'cikis' => $d(2)]), 'geçmiş tarih');
    T::throws(ValidationException::class, fn () => StayCriteriaValidator::fromInput(['giris' => $d(5), 'cikis' => $d(5)]), 'çıkış girişten sonra olmalı');
    T::throws(ValidationException::class, fn () => StayCriteriaValidator::fromInput(['giris' => $d(5), 'cikis' => $d(50)]), 'en fazla 30 gece');
    T::throws(ValidationException::class, fn () => StayCriteriaValidator::fromInput(['giris' => $d(5), 'cikis' => $d(6), 'oda' => [['y' => 0, 'c' => '']]]), 'odada en az bir yetişkin');
    T::throws(ValidationException::class, fn () => StayCriteriaValidator::fromInput(['giris' => $d(5), 'cikis' => $d(6), 'oda' => [['y' => 2, 'c' => '19']]]), 'çocuk yaşı 0–17');
    $c = StayCriteriaValidator::fromInput(['giris' => $d(5), 'cikis' => $d(7), 'yetiskin' => '3', 'cocuk' => '2', 'yaslar' => '4, 9', 'oda_sayisi' => '2']);
    T::eq('2 yetişkin · 1 çocuk', $c->rooms[0]->adults . ' yetişkin · ' . $c->rooms[0]->children() . ' çocuk', 'basit form oda dağılımı');
    T::eq('5 yetişkin', $c->adults() + 2 . ' yetişkin', 'toplam');
};

$tests['Dışa aktarım formül enjeksiyonuna karşı korunur'] = function () {
    $csv = ExportService::csv(['A'], [['=HYPERLINK("x")'], ['+1'], ['-2'], ['@SUM(A1)'], ['Normal']]);
    T::ok(str_contains($csv, "'=HYPERLINK") && str_contains($csv, "'+1") && str_contains($csv, "'@SUM"), 'CSV hücreleri kaçırıldı');
    $xlsx = ExportService::xlsx(['A'], [['=1+1']]);
    T::ok(str_starts_with($xlsx, 'PK'), 'XLSX zip üretildi');
    $tmp = tempnam(sys_get_temp_dir(), 'x') . '.xlsx';
    file_put_contents($tmp, ExportService::xlsx(['Ad', 'Soyad', 'E-posta'], [['Ali', 'Veli', 'ali@ornek.com']]));
    $parsed = ImportService::parse($tmp, 'a.xlsx');
    T::eq(['Ad', 'Soyad', 'E-posta'], $parsed['headers'], 'XLSX okuma (kendi ürettiğimiz dosya)');
    @unlink($tmp);
};

$tests['CSV aktarımı: zorunlu alan, mükerrer e-posta ve hata raporu'] = function () use ($db, $fx) {
    $tmp = tempnam(sys_get_temp_dir(), 'c');
    file_put_contents($tmp, "Ad;Soyad;E-posta;Kurum\nAli;Veli;ali@ornek.com;Test Kurumu A\nAyşe;Kaya;ali@ornek.com;Test Kurumu A\nX;;bozuk;Test Kurumu A\nMehmet;Var;uye@test.local;Test Kurumu A\nCan;Yok;can@ornek.com;Bilinmeyen Kurum\n");
    $p = ImportService::parse($tmp, 'u.csv');
    $map = ImportService::guessMapping($p['headers']);
    $res = (new ImportService($db))->validate($p['rows'], $map, null);
    T::eq(1, count($res['valid']), 'yalnız 1 geçerli satır');
    T::eq(4, count($res['errors']), '4 hatalı satır raporlanır');
    $all = json_encode($res['errors'], JSON_UNESCAPED_UNICODE);
    T::ok(str_contains($all, 'mükerrer') && str_contains($all, 'sistemde kayıtlı') && str_contains($all, 'Kurum bulunamadı'), 'hata nedenleri');
    @unlink($tmp);
};

$tests['Gizli anahtarlar şifreli saklanır ve maskelenir'] = function () use ($db) {
    SettingsService::set('mail.password', 'cokgizliparola', true);
    $raw = (string) $db->value("SELECT `value` FROM settings WHERE `key` = 'mail.password'");
    T::ok(str_starts_with($raw, 'v1:') && !str_contains($raw, 'cokgizli'), 'veritabanında şifreli');
    T::eq('cokgizliparola', SettingsService::get('mail.password'), 'sunucuda çözülür');
    T::eq('••••••••lama', \App\Core\Crypto::mask('çokgizliparolalama'), 'maskeleme');
    $ctx = \App\Core\Logger::sanitize(['api_key' => 'abcd1234efgh', 'x' => 'y']);
    T::ok(!str_contains($ctx['api_key'], 'abcd1234'), 'log maskesi');
};

$tests['Sayfa render (üye ve yönetim) — taşma riski olmayan HTML üretimi'] = function () use ($fx, $crit) {
    TestEnv::actingAs('uye@test.local');
    $c = $crit(15, 2);
    foreach (['/panel', '/oteller?' . http_build_query($c->toQuery()), '/oteller/test-anlasmali-otel?' . http_build_query($c->toQuery()), '/rezervasyonlarim', '/tekliflerim', '/favorilerim', '/bildirimler', '/profilim', '/destek', '/teklif-iste'] as $p) {
        $r = TestEnv::request('GET', $p);
        T::eq(200, $r->status(), "üye sayfası $p");
    }
    TestEnv::actingAs('admin@test.local');
    foreach (['/yonetim', '/yonetim/oteller', '/yonetim/oteller/' . $fx['h1'] . '/adim/2', '/yonetim/fiyatlar?otel=' . $fx['h1'], '/yonetim/kontenjan?otel=' . $fx['h1'], '/yonetim/raporlar', '/yonetim/api', '/yonetim/guncelleme'] as $p) {
        T::eq(200, TestEnv::request('GET', $p)->status(), "yönetim sayfası $p");
    }
    $exp = TestEnv::request('GET', '/yonetim/raporlar/disa-aktar', ['format' => 'csv', 'bas' => date('Y-01-01'), 'bit' => date('Y-12-31'), 'tarih' => 'created']);
    T::ok(str_contains($exp->headers()['Content-Type'] ?? '', 'text/csv'), 'rapor CSV dışa aktarım');
    TestEnv::actingAs('uye@test.local');
    T::eq(403, TestEnv::request('GET', '/yonetim/raporlar/disa-aktar')->status(), 'üye dışa aktaramaz');
};

// ---------------------------------------------------------------- Çalıştır
$start = microtime(true);
foreach ($tests as $name => $fn) {
    T::$current = $name;
    $before = count(T::$fail);
    try {
        $fn();
    } catch (\Throwable $e) {
        T::$fail[] = $name . ': BEKLENMEYEN HATA ' . $e::class . ': ' . $e->getMessage() . ' @ ' . basename($e->getFile()) . ':' . $e->getLine();
    }
    echo (count(T::$fail) === $before ? "  ✔ " : "  ✘ ") . $name . PHP_EOL;
}
echo PHP_EOL . T::$pass . ' doğrulama başarılı, ' . count(T::$fail) . ' başarısız (' . round(microtime(true) - $start, 1) . ' sn)' . PHP_EOL;
foreach (T::$fail as $f) {
    echo '  - ' . $f . PHP_EOL;
}
exit(T::$fail ? 1 : 0);
