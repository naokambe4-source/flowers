<?php
declare(strict_types=1);

use App\Controllers\Admin;
use App\Controllers\Auth;
use App\Controllers\Install\InstallController;
use App\Controllers\Member;
use App\Controllers\Site;
use App\Core\Router;

return static function (Router $r): void {
    // ---------- Kurulum ----------
    $r->get('/kurulum', [InstallController::class, 'index'], [], 'install');
    $r->post('/kurulum', [InstallController::class, 'install']);
    $r->get('/kurulum/rewrite-test', [InstallController::class, 'rewriteTest']);

    // ---------- Misafir (kapalı platform: yalnız giriş, başvuru ve yasal sayfalar) ----------
    $r->get('/', [Auth\LoginController::class, 'home'], [], 'home');
    $r->get('/giris', [Auth\LoginController::class, 'show'], ['guest'], 'login');
    $r->post('/giris', [Auth\LoginController::class, 'login'], ['guest']);
    $r->post('/cikis', [Auth\LoginController::class, 'logout'], [], 'logout');
    $r->get('/sifremi-unuttum', [Auth\PasswordController::class, 'forgot'], ['guest'], 'password.forgot');
    $r->post('/sifremi-unuttum', [Auth\PasswordController::class, 'sendLink'], ['guest']);
    $r->get('/sifre-olustur/{token}', [Auth\PasswordController::class, 'showReset'], [], 'password.reset');
    $r->post('/sifre-olustur/{token}', [Auth\PasswordController::class, 'reset']);
    $r->get('/erisim-talebi', [Auth\AccessRequestController::class, 'show'], ['guest'], 'access.request');
    $r->post('/erisim-talebi', [Auth\AccessRequestController::class, 'store'], ['guest']);
    $r->get('/kayit-ol', [Auth\RegisterController::class, 'show'], ['guest'], 'register');
    $r->post('/kayit-ol', [Auth\RegisterController::class, 'store'], ['guest']);
    $r->get('/iletisim', [Site\PageController::class, 'contact'], [], 'contact');
    $r->post('/iletisim', [Site\PageController::class, 'sendContact']);
    $r->get('/kvkk', [Site\PageController::class, 'kvkk']);
    $r->get('/gizlilik', [Site\PageController::class, 'privacy']);
    $r->get('/kullanim-kosullari', [Site\PageController::class, 'terms']);
    $r->get('/cerez-politikasi', [Site\PageController::class, 'cookies']);
    $r->get('/voucher/dogrula/{token:[A-Za-z0-9_-]+}', [Site\VoucherVerifyController::class, 'show'], [], 'voucher.verify');
    $r->get('/medya/site/{key:[a-z_]+}', [Site\MediaController::class, 'site']);
    $r->match(['GET', 'POST'], '/cron/{token:[A-Za-z0-9_-]+}', [Site\CronController::class, 'run'], ['nocsrf']);

    // ---------- Üye alanı ----------
    $r->group('', ['auth'], static function (Router $r): void {
        $r->get('/panel', [Member\DashboardController::class, 'index'], [], 'dashboard');
        $r->get('/oteller', [Member\HotelController::class, 'index'], ['can:member.book'], 'hotels');
        $r->get('/oteller/harita-verisi', [Member\HotelController::class, 'mapData'], ['can:member.book']);
        $r->get('/oteller/{slug:[a-z0-9-]+}', [Member\HotelController::class, 'show'], ['can:member.book'], 'hotel.show');
        $r->get('/oteller/{slug:[a-z0-9-]+}/fiyatlar', [Member\HotelController::class, 'rates'], ['can:member.book']);
        $r->post('/favoriler/{hotelId:\d+}', [Member\FavoriteController::class, 'toggle'], ['can:member.book']);
        $r->get('/favorilerim', [Member\FavoriteController::class, 'index'], ['can:member.book'], 'favorites');

        $r->post('/rezervasyon/baslat', [Member\BookingFlowController::class, 'start'], ['can:member.book']);
        $r->get('/rezervasyon/{code:[A-Z0-9-]+}/misafirler', [Member\BookingFlowController::class, 'guests'], ['can:member.book']);
        $r->post('/rezervasyon/{code:[A-Z0-9-]+}/misafirler', [Member\BookingFlowController::class, 'saveGuests'], ['can:member.book']);
        $r->get('/rezervasyon/{code:[A-Z0-9-]+}/kontrol', [Member\BookingFlowController::class, 'review'], ['can:member.book']);
        $r->post('/rezervasyon/{code:[A-Z0-9-]+}/onayla', [Member\BookingFlowController::class, 'confirm'], ['can:member.book']);

        $r->get('/rezervasyonlarim', [Member\BookingController::class, 'index'], [], 'bookings');
        $r->get('/rezervasyonlarim/{code:[A-Z0-9-]+}', [Member\BookingController::class, 'show'], [], 'booking.show');
        $r->post('/rezervasyonlarim/{code:[A-Z0-9-]+}/iptal', [Member\BookingController::class, 'cancel']);
        $r->get('/rezervasyonlarim/{code:[A-Z0-9-]+}/voucher', [Member\BookingController::class, 'voucher'], [], 'booking.voucher');

        $r->get('/teklif-iste', [Member\RequestController::class, 'create'], ['can:member.book'], 'request.create');
        $r->post('/teklif-iste', [Member\RequestController::class, 'store'], ['can:member.book']);
        $r->get('/tekliflerim', [Member\RequestController::class, 'index'], [], 'requests');
        $r->get('/tekliflerim/{code:[A-Z0-9-]+}', [Member\RequestController::class, 'show'], [], 'request.show');
        $r->post('/tekliflerim/{code:[A-Z0-9-]+}/kabul', [Member\RequestController::class, 'accept']);
        $r->post('/tekliflerim/{code:[A-Z0-9-]+}/reddet', [Member\RequestController::class, 'decline']);
        $r->post('/tekliflerim/{code:[A-Z0-9-]+}/iptal', [Member\RequestController::class, 'cancel']);

        $r->get('/bildirimler', [Member\NotificationController::class, 'index'], [], 'notifications');
        $r->post('/bildirimler/okundu', [Member\NotificationController::class, 'markRead']);
        $r->get('/profilim', [Member\ProfileController::class, 'show'], [], 'profile');
        $r->post('/profilim', [Member\ProfileController::class, 'update']);
        $r->get('/sifre-degistir', [Member\ProfileController::class, 'password'], [], 'password.change');
        $r->post('/sifre-degistir', [Member\ProfileController::class, 'updatePassword']);
        $r->get('/destek', [Member\SupportController::class, 'index'], [], 'support');
        $r->post('/destek', [Member\SupportController::class, 'store']);

        $r->get('/kurumum', [Member\InstitutionController::class, 'index'], ['can:institution.own'], 'institution.own');
        $r->get('/kurumum/rezervasyonlar', [Member\InstitutionController::class, 'bookings'], ['can:institution.own']);
        $r->post('/kurumum/basvurular/{id:\d+}', [Member\InstitutionController::class, 'decideApplication'], ['can:institution.own.applications']);

        $r->get('/medya/otel/{id:\d+}/{size:thumb|medium|large}', [Site\MediaController::class, 'hotelImage']);
        $r->get('/medya/oda/{id:\d+}/{size:thumb|medium|large}', [Site\MediaController::class, 'roomImage']);
        $r->get('/medya/bolge/{id:\d+}', [Site\MediaController::class, 'region']);
        $r->get('/medya/kampanya/{id:\d+}', [Site\MediaController::class, 'campaign']);
        $r->get('/medya/kurum/{id:\d+}', [Site\MediaController::class, 'institutionLogo']);
    });

    // ---------- Yönetim ----------
    $r->group('/yonetim', ['staff'], static function (Router $r): void {
        $r->get('', [Admin\DashboardController::class, 'index'], ['can:dashboard.view'], 'admin');
        $r->get('/ara', [Admin\DashboardController::class, 'search']);

        $r->get('/rezervasyonlar', [Admin\BookingController::class, 'index'], ['can:bookings.view'], 'admin.bookings');
        $r->get('/rezervasyonlar/{id:\d+}', [Admin\BookingController::class, 'show'], ['can:bookings.view']);
        $r->post('/rezervasyonlar/{id:\d+}/durum', [Admin\BookingController::class, 'updateStatus'], ['can:bookings.manage']);
        $r->post('/rezervasyonlar/{id:\d+}/bilgi', [Admin\BookingController::class, 'updateInfo'], ['can:bookings.manage']);
        $r->get('/rezervasyonlar/{id:\d+}/voucher', [Admin\BookingController::class, 'voucher'], ['can:bookings.view']);

        $r->get('/talepler', [Admin\RequestController::class, 'index'], ['can:requests.manage'], 'admin.requests');
        $r->get('/talepler/{id:\d+}', [Admin\RequestController::class, 'show'], ['can:requests.manage']);
        $r->post('/talepler/{id:\d+}/teklif', [Admin\RequestController::class, 'sendOffer'], ['can:requests.manage']);
        $r->post('/talepler/{id:\d+}/durum', [Admin\RequestController::class, 'updateStatus'], ['can:requests.manage']);

        $r->get('/oteller', [Admin\HotelController::class, 'index'], ['can:hotels.view'], 'admin.hotels');
        $r->get('/oteller/yeni', [Admin\HotelController::class, 'create'], ['can:hotels.manage']);
        $r->post('/oteller/yeni', [Admin\HotelController::class, 'store'], ['can:hotels.manage']);
        $r->get('/oteller/{id:\d+}/adim/{step:[1-7]}', [Admin\HotelController::class, 'step'], ['can:hotels.manage']);
        $r->post('/oteller/{id:\d+}/adim/{step:[1-7]}', [Admin\HotelController::class, 'saveStep'], ['can:hotels.manage']);
        $r->post('/oteller/{id:\d+}/gorseller', [Admin\HotelController::class, 'uploadImages'], ['can:hotels.manage']);
        $r->post('/oteller/{id:\d+}/gorseller/{imageId:\d+}', [Admin\HotelController::class, 'updateImage'], ['can:hotels.manage']);
        $r->post('/oteller/{id:\d+}/yayin', [Admin\HotelController::class, 'publish'], ['can:hotels.publish']);
        $r->get('/oteller/{id:\d+}/onizleme', [Admin\HotelController::class, 'preview'], ['can:hotels.view']);

        $r->get('/odalar', [Admin\RoomController::class, 'index'], ['can:hotels.view'], 'admin.rooms');
        $r->post('/oteller/{id:\d+}/odalar', [Admin\RoomController::class, 'store'], ['can:hotels.manage']);
        $r->post('/odalar/{id:\d+}', [Admin\RoomController::class, 'update'], ['can:hotels.manage']);
        $r->post('/odalar/{id:\d+}/gorseller', [Admin\RoomController::class, 'uploadImages'], ['can:hotels.manage']);
        $r->post('/odalar/{id:\d+}/gorseller/{imageId:\d+}/sil', [Admin\RoomController::class, 'deleteImage'], ['can:hotels.manage']);

        foreach (['ozellikler', 'bolgeler', 'konseptler', 'kurum-tipleri', 'fiyat-gruplari', 'sezonlar'] as $lookup) {
            $perm = in_array($lookup, ['kurum-tipleri', 'fiyat-gruplari'], true) ? 'institutions.manage' : ($lookup === 'sezonlar' ? 'pricing.manage' : 'catalog.manage');
            $r->get('/' . $lookup, [Admin\LookupController::class, 'index'], ['can:' . $perm]);
            $r->post('/' . $lookup, [Admin\LookupController::class, 'save'], ['can:' . $perm]);
            $r->post('/' . $lookup . '/{id:\d+}/sil', [Admin\LookupController::class, 'delete'], ['can:' . $perm]);
        }

        $r->get('/fiyatlar', [Admin\PricingController::class, 'index'], ['can:pricing.view'], 'admin.pricing');
        $r->post('/fiyatlar/plan', [Admin\PricingController::class, 'savePlan'], ['can:pricing.manage']);
        $r->post('/fiyatlar/takvim', [Admin\PricingController::class, 'saveRates'], ['can:pricing.manage']);
        $r->post('/fiyatlar/referans', [Admin\PricingController::class, 'saveReference'], ['can:pricing.manage']);
        $r->post('/fiyatlar/referans/{id:\d+}/sil', [Admin\PricingController::class, 'deleteReference'], ['can:pricing.manage']);
        $r->get('/fiyatlar/hesapla', [Admin\PricingController::class, 'simulate'], ['can:pricing.view']);

        $r->get('/fiyat-kurallari', [Admin\RuleController::class, 'index'], ['can:pricing.view']);
        $r->post('/fiyat-kurallari', [Admin\RuleController::class, 'save'], ['can:pricing.manage']);
        $r->post('/fiyat-kurallari/{id:\d+}/sil', [Admin\RuleController::class, 'delete'], ['can:pricing.manage']);
        $r->get('/kampanyalar', [Admin\CampaignController::class, 'index'], ['can:pricing.view']);
        $r->post('/kampanyalar', [Admin\CampaignController::class, 'save'], ['can:pricing.manage']);
        $r->post('/kampanyalar/{id:\d+}/sil', [Admin\CampaignController::class, 'delete'], ['can:pricing.manage']);
        $r->get('/promosyonlar', [Admin\PromoController::class, 'index'], ['can:pricing.view']);
        $r->post('/promosyonlar', [Admin\PromoController::class, 'save'], ['can:pricing.manage']);
        $r->post('/promosyonlar/{id:\d+}/sil', [Admin\PromoController::class, 'delete'], ['can:pricing.manage']);

        $r->get('/kontenjan', [Admin\InventoryController::class, 'index'], ['can:inventory.manage'], 'admin.inventory');
        $r->post('/kontenjan', [Admin\InventoryController::class, 'save'], ['can:inventory.manage']);
        $r->post('/kontenjan/stop-sale', [Admin\InventoryController::class, 'stopSale'], ['can:inventory.manage']);
        $r->post('/kontenjan/stop-sale/{id:\d+}/sil', [Admin\InventoryController::class, 'deleteStopSale'], ['can:inventory.manage']);

        $r->get('/kurumlar', [Admin\InstitutionController::class, 'index'], ['can:institutions.view'], 'admin.institutions');
        $r->post('/kurumlar', [Admin\InstitutionController::class, 'save'], ['can:institutions.manage']);
        $r->get('/kurumlar/{id:\d+}', [Admin\InstitutionController::class, 'edit'], ['can:institutions.view']);

        $r->get('/uyeler', [Admin\UserController::class, 'index'], ['can:users.view'], 'admin.users');
        $r->get('/uyeler/yeni', [Admin\UserController::class, 'create'], ['can:users.manage']);
        $r->post('/uyeler', [Admin\UserController::class, 'save'], ['can:users.manage']);
        $r->get('/uyeler/{id:\d+}', [Admin\UserController::class, 'edit'], ['can:users.view']);
        $r->post('/uyeler/{id:\d+}/durum', [Admin\UserController::class, 'status'], ['can:users.manage']);
        $r->post('/uyeler/{id:\d+}/parola', [Admin\UserController::class, 'sendReset'], ['can:users.manage']);
        $r->get('/uyeler/aktar', [Admin\ImportController::class, 'show'], ['can:users.import']);
        $r->post('/uyeler/aktar', [Admin\ImportController::class, 'upload'], ['can:users.import']);
        $r->post('/uyeler/aktar/onizleme', [Admin\ImportController::class, 'preview'], ['can:users.import']);
        $r->post('/uyeler/aktar/tamamla', [Admin\ImportController::class, 'commit'], ['can:users.import']);

        $r->get('/basvurular', [Admin\ApplicationController::class, 'index'], ['can:applications.manage'], 'admin.applications');
        $r->post('/basvurular/{id:\d+}', [Admin\ApplicationController::class, 'decide'], ['can:applications.manage']);

        $r->get('/api', [Admin\ProviderController::class, 'index'], ['can:providers.manage'], 'admin.providers');
        $r->get('/api/{id:\d+}', [Admin\ProviderController::class, 'show'], ['can:providers.manage']);
        $r->post('/api/{id:\d+}', [Admin\ProviderController::class, 'update'], ['can:providers.manage']);
        $r->post('/api/{id:\d+}/test', [Admin\ProviderController::class, 'test'], ['can:providers.manage']);
        $r->post('/api/{id:\d+}/destinasyon', [Admin\ProviderController::class, 'mapDestination'], ['can:providers.manage']);
        $r->post('/api/{id:\d+}/destinasyon-ara', [Admin\ProviderController::class, 'searchDestination'], ['can:providers.manage']);
        $r->post('/api/{id:\d+}/otel-esle', [Admin\ProviderController::class, 'mapHotel'], ['can:providers.manage']);
        $r->post('/api/onbellek-temizle', [Admin\ProviderController::class, 'clearCache'], ['can:providers.manage']);

        $r->get('/bildirimler', [Admin\NotificationController::class, 'index'], ['can:notifications.manage']);
        $r->post('/bildirimler/sablon', [Admin\NotificationController::class, 'saveTemplate'], ['can:notifications.manage']);
        $r->post('/bildirimler/is/{id:\d+}/tekrar', [Admin\NotificationController::class, 'retryJob'], ['can:notifications.manage']);
        $r->post('/bildirimler/test-eposta', [Admin\NotificationController::class, 'testMail'], ['can:notifications.manage']);

        $r->get('/raporlar', [Admin\ReportController::class, 'index'], ['can:reports.view'], 'admin.reports');
        $r->get('/raporlar/disa-aktar', [Admin\ReportController::class, 'export'], ['can:reports.export']);

        $r->get('/icerik', [Admin\ContentController::class, 'index'], ['can:content.manage'], 'admin.content');
        $r->post('/icerik/ayarlar', [Admin\ContentController::class, 'saveSettings'], ['can:content.manage']);
        $r->post('/icerik/sayfa/{id:\d+}', [Admin\ContentController::class, 'savePage'], ['can:content.manage']);
        $r->post('/icerik/bolumler', [Admin\ContentController::class, 'saveSections'], ['can:content.manage']);

        $r->get('/ayarlar', [Admin\SettingsController::class, 'index'], ['can:settings.manage'], 'admin.settings');
        $r->post('/ayarlar', [Admin\SettingsController::class, 'save'], ['can:settings.manage']);
        $r->get('/canli-veri', [Admin\LiveDataController::class, 'index'], ['can:providers.manage'], 'admin.live');
        $r->post('/canli-veri/liteapi', [Admin\LiveDataController::class, 'saveLiteApi'], ['can:providers.manage']);
        $r->post('/canli-veri/test/{code:osm|liteapi}', [Admin\LiveDataController::class, 'test'], ['can:providers.manage']);
        $r->post('/canli-veri/ice-aktar', [Admin\LiveDataController::class, 'import'], ['can:providers.manage']);
        $r->post('/canli-veri/kaldir', [Admin\LiveDataController::class, 'remove'], ['can:providers.manage']);
        $r->post('/canli-veri/canli-arama', [Admin\LiveDataController::class, 'liveSearch'], ['can:providers.manage']);
        $r->get('/demo-mod', [Admin\DemoController::class, 'index'], ['can:settings.manage'], 'admin.demo');
        $r->post('/demo-mod/yukle', [Admin\DemoController::class, 'install'], ['can:settings.manage']);
        $r->post('/demo-mod/kaldir', [Admin\DemoController::class, 'remove'], ['can:settings.manage']);
        $r->get('/guncelleme', [Admin\SettingsController::class, 'updates'], ['can:settings.manage']);
        $r->post('/guncelleme', [Admin\SettingsController::class, 'runUpdates'], ['can:settings.manage']);
        $r->get('/yetkiler', [Admin\RoleController::class, 'index'], ['can:roles.manage|settings.manage'], 'admin.roles');
        $r->post('/yetkiler', [Admin\RoleController::class, 'save'], ['can:roles.manage']);
        $r->get('/audit-log', [Admin\AuditController::class, 'index'], ['can:audit.view'], 'admin.audit');
        $r->get('/destek', [Admin\SupportController::class, 'index'], ['can:support.manage'], 'admin.support');
        $r->post('/destek/{id:\d+}', [Admin\SupportController::class, 'reply'], ['can:support.manage']);
    });
};
