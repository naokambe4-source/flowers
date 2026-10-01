<?php
declare(strict_types=1);

/**
 * Referans veriler: roller, izinler, bölgeler, konseptler, özellikler, kurum tipleri,
 * varsayılan ayarlar, bildirim şablonları, yasal sayfa kayıtları (boş), sağlayıcı kayıtları (kapalı).
 * Örnek otel, sahte fiyat, test kullanıcısı veya uydurma iletişim bilgisi İÇERMEZ.
 */

use App\Core\Database;

return static function (Database $db): void {
    $roles = [
        ['super_admin', 'Super Admin', 'Tüm yetkiler; yetki matrisi dahil', 1, 1, 1],
        ['system_admin', 'Sistem Yöneticisi', 'Sistem, içerik, otel ve üyelik yönetimi', 1, 1, 2],
        ['reservation_officer', 'Rezervasyon Yetkilisi', 'Rezervasyon, talep, teklif ve kontenjan', 1, 1, 3],
        ['finance_officer', 'Finans Yetkilisi', 'Fiyatlar, raporlar ve dışa aktarım', 1, 1, 4],
        ['institution_manager', 'Kurum Yöneticisi', 'Yalnız kendi kurumunun üye ve rezervasyonları', 1, 0, 5],
        ['member', 'Standart Üye', 'Otel arama, rezervasyon ve teklif', 1, 0, 6],
    ];
    foreach ($roles as [$slug, $name, $desc, $sys, $staff, $sort]) {
        $db->insert('roles', ['slug' => $slug, 'name' => $name, 'description' => $desc, 'is_system' => $sys, 'is_staff' => $staff, 'sort' => $sort]);
    }

    $permissions = [
        'Genel' => [
            'member.book' => 'Otel arama, rezervasyon ve teklif talebi',
            'admin.access' => 'Yönetim paneline giriş',
            'dashboard.view' => 'Yönetim özetini görüntüleme',
        ],
        'Rezervasyon' => [
            'bookings.view' => 'Rezervasyonları görüntüleme',
            'bookings.manage' => 'Rezervasyon durumunu yönetme / otel teyidi girme',
            'requests.manage' => 'Konaklama talepleri ve teklif gönderme',
            'inventory.manage' => 'Kontenjan ve stop sale yönetimi',
        ],
        'Katalog' => [
            'hotels.view' => 'Otelleri görüntüleme',
            'hotels.manage' => 'Otel ve oda ekleme/düzenleme',
            'hotels.publish' => 'Oteli yayına alma / kaldırma',
            'catalog.manage' => 'Bölge, konsept ve özellik yönetimi',
        ],
        'Fiyat' => [
            'pricing.view' => 'Fiyatları görüntüleme',
            'pricing.manage' => 'Fiyat, kural, kampanya, promosyon ve sezon yönetimi',
        ],
        'Kurum ve Üyeler' => [
            'institutions.view' => 'Kurumları görüntüleme',
            'institutions.manage' => 'Kurum ekleme/düzenleme',
            'users.view' => 'Üyeleri görüntüleme',
            'users.manage' => 'Üye ekleme, düzenleme, askıya alma, parola sıfırlama',
            'users.import' => 'CSV/XLSX üye aktarımı',
            'applications.manage' => 'Erişim başvurularını onaylama/reddetme',
            'institution.own' => 'Kendi kurumunun üye ve rezervasyonlarını görme',
            'institution.own.applications' => 'Kendi kurumunun başvurularını onaylama',
        ],
        'Sistem' => [
            'providers.manage' => 'API sağlayıcıları ve anahtar yönetimi',
            'notifications.manage' => 'Bildirim şablonları ve iş kuyruğu',
            'reports.view' => 'Raporları görüntüleme',
            'reports.export' => 'Rapor dışa aktarma (CSV/XLSX)',
            'content.manage' => 'İçerik, sayfa ve ana sayfa bölümleri',
            'settings.manage' => 'Sistem ayarları',
            'roles.manage' => 'Rol ve yetki matrisi',
            'audit.view' => 'Audit log görüntüleme',
            'support.manage' => 'Destek taleplerini yanıtlama',
        ],
    ];
    $permIds = [];
    $sort = 0;
    foreach ($permissions as $group => $items) {
        foreach ($items as $slug => $name) {
            $permIds[$slug] = $db->insert('permissions', ['slug' => $slug, 'name' => $name, 'group_name' => $group, 'sort' => ++$sort]);
        }
    }
    $matrix = [
        'super_admin' => array_keys($permIds),
        'system_admin' => array_values(array_diff(array_keys($permIds), ['roles.manage'])),
        'reservation_officer' => ['member.book', 'admin.access', 'dashboard.view', 'bookings.view', 'bookings.manage', 'requests.manage', 'inventory.manage', 'hotels.view', 'pricing.view', 'institutions.view', 'users.view', 'support.manage'],
        'finance_officer' => ['member.book', 'admin.access', 'dashboard.view', 'bookings.view', 'pricing.view', 'pricing.manage', 'hotels.view', 'institutions.view', 'reports.view', 'reports.export', 'audit.view'],
        'institution_manager' => ['member.book', 'institution.own', 'institution.own.applications'],
        'member' => ['member.book'],
    ];
    foreach ($matrix as $roleSlug => $perms) {
        $roleId = (int) $db->value('SELECT id FROM roles WHERE slug = ?', [$roleSlug]);
        foreach ($perms as $p) {
            $db->insert('role_permission', ['role_id' => $roleId, 'permission_id' => $permIds[$p]]);
        }
    }

    // Antalya bölgeleri (koordinatlar bölge merkezini temsil eder)
    $regions = [
        ['Antalya Merkez', 'antalya-merkez', null, 36.8969, 30.7133, 'oldtown'],
        ['Lara', 'lara', 'antalya-merkez', 36.8573, 30.8155, 'beach'],
        ['Kundu', 'kundu', 'antalya-merkez', 36.8515, 30.8800, 'beach'],
        ['Belek', 'belek', null, 36.8625, 31.0556, 'coast'],
        ['Kadriye', 'kadriye', 'belek', 36.8778, 31.0072, 'coast'],
        ['Manavgat', 'manavgat', null, 36.7867, 31.4433, 'mountain'],
        ['Side', 'side', 'manavgat', 36.7673, 31.3890, 'oldtown'],
        ['Alanya', 'alanya', null, 36.5444, 31.9954, 'bay'],
        ['Kemer', 'kemer', null, 36.6000, 30.5595, 'mountain'],
        ['Beldibi', 'beldibi', 'kemer', 36.7071, 30.5640, 'cove'],
        ['Göynük', 'goynuk', 'kemer', 36.6680, 30.5470, 'cove'],
        ['Tekirova', 'tekirova', 'kemer', 36.5070, 30.5240, 'cove'],
        ['Kaş', 'kas', null, 36.2018, 29.6377, 'bay'],
        ['Kalkan', 'kalkan', 'kas', 36.2650, 29.4140, 'bay'],
        ['Finike', 'finike', null, 36.2990, 30.1460, 'coast'],
        ['Demre', 'demre', null, 36.2442, 29.9850, 'oldtown'],
    ];
    $regionIds = [];
    foreach ($regions as $i => [$name, $slug, $parent, $lat, $lng, $ill]) {
        $regionIds[$slug] = $db->insert('regions', [
            'name' => $name, 'slug' => $slug, 'parent_id' => $parent ? $regionIds[$parent] : null,
            'latitude' => $lat, 'longitude' => $lng, 'illustration' => $ill, 'sort' => $i + 1,
            'is_active' => 1, 'show_on_home' => 1,
        ]);
    }

    $concepts = [
        ['Ultra Her Şey Dahil', 'UAI'], ['Her Şey Dahil', 'AI'], ['Tam Pansiyon', 'FB'],
        ['Yarım Pansiyon', 'HB'], ['Oda Kahvaltı', 'BB'], ['Sadece Oda', 'RO'],
    ];
    foreach ($concepts as $i => [$name, $code]) {
        $db->insert('concepts', ['name' => $name, 'code' => $code, 'sort' => $i + 1, 'is_active' => 1]);
    }

    $amenities = [
        ['Özel plaj', 'hotel', 'beach', 'umbrella'], ['Denize sıfır', 'hotel', 'sea_front', 'waves'],
        ['Açık havuz', 'hotel', 'pool', 'pool'], ['Aquapark', 'hotel', 'aquapark', 'slide'],
        ['Spa & Wellness', 'hotel', 'spa', 'spa'], ['Çocuk kulübü', 'hotel', 'kids', 'child'],
        ['Engelli dostu', 'hotel', 'accessible', 'accessible'], ['Kapalı havuz', 'hotel', null, 'pool'],
        ['Hamam', 'hotel', null, 'spa'], ['Fitness merkezi', 'hotel', null, 'fitness'],
        ['Ücretsiz Wi-Fi', 'both', null, 'wifi'], ['Otopark', 'hotel', null, 'parking'],
        ['Restoran', 'hotel', null, 'restaurant'], ['Toplantı salonu', 'hotel', null, 'meeting'],
        ['Animasyon', 'hotel', null, 'star'], ['Havalimanı transferi', 'hotel', null, 'car'],
        ['Klima', 'room', null, 'snow'], ['Minibar', 'room', null, 'fridge'], ['Televizyon', 'room', null, 'tv'],
        ['Balkon', 'room', null, 'balcony'], ['Kasa', 'room', null, 'lock'], ['Saç kurutma makinesi', 'room', null, 'dryer'],
        ['Çay / kahve seti', 'room', null, 'cup'], ['Küvet', 'room', null, 'bath'],
    ];
    foreach ($amenities as $i => [$name, $scope, $key, $icon]) {
        $db->insert('amenities', ['name' => $name, 'scope' => $scope, 'filter_key' => $key, 'icon' => $icon, 'sort' => $i + 1, 'is_active' => 1]);
    }

    foreach (['Bakanlık', 'Valilik', 'Kaymakamlık', 'Belediye', 'Kamu Kurumu', 'Üniversite', 'Dernek', 'Vakıf', 'Özel Kurum'] as $i => $t) {
        $db->insert('institution_types', ['name' => $t, 'is_active' => 1, 'sort' => $i + 1]);
    }
    $db->insert('price_groups', ['name' => 'Standart', 'description' => 'Genel üye indirimi uygulanır', 'discount_bp' => null, 'is_active' => 1, 'sort' => 1]);

    $settings = [
        'site.name' => 'Kurumsal Konaklama',
        'site.tagline' => 'Antalya’da anlaşmalı otellerde kurumunuza özel konaklama',
        'site.logo' => '',
        'login.title' => 'Kurumunuza özel Antalya konaklaması',
        'login.text' => 'Anlaşmalı otellerdeki üyelere özel fiyatları görmek için kurumsal hesabınızla giriş yapın.',
        'login.image' => '',
        'home.hero_title' => 'Antalya’da size özel konaklama',
        'home.hero_text' => 'Anlaşmalı otellerde kurumunuza tanımlı fiyatlarla arayın, karşılaştırın ve rezervasyon yapın.',
        'home.hero_image' => '',
        'support.image' => '',
        'contact.phone' => '', 'contact.whatsapp' => '', 'contact.email' => '', 'contact.address' => '', 'contact.hours' => '',
        'social.instagram' => '', 'social.facebook' => '', 'social.x' => '', 'social.linkedin' => '', 'social.youtube' => '',
        'pricing.default_discount_bp' => '1000',
        'pricing.max_discount_bp' => '3000',
        'pricing.vat_bp' => '1000',
        'pricing.accommodation_tax_bp' => '200',
        'pricing.reference_max_age_hours' => '24',
        'pricing.currency' => 'TRY',
        'booking.max_nights' => '30',
        'booking.max_rooms' => '5',
        'booking.draft_ttl_minutes' => '60',
        'booking.terms_text' => '',
        'booking.cancel_text' => '',
        'offer.default_validity_hours' => '48',
        'cache.content_ttl' => '86400',
        'cache.rates_ttl' => '600',
        'cache.destination_ttl' => '2592000',
        'providers.live_search' => '0',
        'security.session_idle_minutes' => '60',
        'security.login_max_attempts' => '5',
        'security.login_decay_minutes' => '15',
        'mail.enabled' => '0', 'mail.host' => '', 'mail.port' => '587', 'mail.encryption' => 'tls',
        'mail.username' => '', 'mail.password' => '', 'mail.from_email' => '', 'mail.from_name' => '',
        'mail.admin_notify_email' => '',
        'map.tile_url' => 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
        'map.attribution' => '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> katkıcıları',
        'cookie.text' => 'Bu platform yalnızca oturum ve güvenlik için zorunlu çerezler kullanır.',
        'cron.last_run' => '',
    ];
    foreach ($settings as $k => $v) {
        $db->insert('settings', ['key' => $k, 'value' => $v, 'is_secret' => $k === 'mail.password' ? 1 : 0]);
    }

    $templates = [
        ['membership_approved', 'Üyelik onaylandı', 'Erişim başvurunuz onaylandı', "Merhaba {ad},\n\n{site} erişim başvurunuz onaylandı. Aşağıdaki bağlantıdan parolanızı oluşturabilirsiniz (bağlantı {sure} saat geçerlidir):\n\n{baglanti}"],
        ['membership_rejected', 'Üyelik reddedildi', 'Erişim başvurunuz hakkında', "Merhaba {ad},\n\n{site} erişim başvurunuz bu aşamada onaylanamadı.\n\n{not}"],
        ['user_invite', 'Davet / parola oluşturma', 'Hesabınız oluşturuldu', "Merhaba {ad},\n\n{site} için hesabınız oluşturuldu. Parolanızı oluşturmak için ({sure} saat geçerli):\n\n{baglanti}"],
        ['password_reset', 'Parola sıfırlama', 'Parola sıfırlama bağlantısı', "Merhaba {ad},\n\nParolanızı sıfırlamak için aşağıdaki bağlantıyı kullanın ({sure} saat geçerli). Bu isteği siz yapmadıysanız bu e-postayı dikkate almayın.\n\n{baglanti}"],
        ['request_received', 'Talep alındı', 'Konaklama talebiniz alındı ({kod})', "Merhaba {ad},\n\n{kod} numaralı konaklama talebiniz alındı. Ekibimiz teklif hazırlayarak size bildirecektir.\n\n{baglanti}"],
        ['offer_sent', 'Teklif gönderildi', 'Talebiniz için teklif hazır ({kod})', "Merhaba {ad},\n\n{kod} numaralı talebiniz için teklif hazırlandı. Toplam: {tutar}. Teklif {gecerlilik} tarihine kadar geçerlidir.\n\n{baglanti}"],
        ['booking_requested', 'Rezervasyon talebi alındı', 'Rezervasyon talebiniz alındı ({kod})', "Merhaba {ad},\n\n{otel} için {giris} – {cikis} tarihli rezervasyon talebiniz alındı. Otel teyidi sonrası bilgilendirileceksiniz.\n\n{baglanti}"],
        ['booking_confirmed', 'Rezervasyon onaylandı', 'Rezervasyonunuz onaylandı ({kod})', "Merhaba {ad},\n\n{otel} için {giris} – {cikis} tarihli rezervasyonunuz onaylandı. Voucher’ınızı aşağıdaki bağlantıdan indirebilirsiniz.\n\n{baglanti}"],
        ['booking_changed', 'Rezervasyon değişti', 'Rezervasyonunuzda değişiklik ({kod})', "Merhaba {ad},\n\n{kod} numaralı rezervasyonunuzda değişiklik yapıldı: {not}\n\n{baglanti}"],
        ['booking_cancelled', 'Rezervasyon iptal edildi', 'Rezervasyonunuz iptal edildi ({kod})', "Merhaba {ad},\n\n{kod} numaralı rezervasyonunuz iptal edildi. {not}\n\n{baglanti}"],
        ['support_reply', 'Destek yanıtı', 'Destek talebiniz yanıtlandı', "Merhaba {ad},\n\nDestek talebinize yanıt verildi:\n\n{not}\n\n{baglanti}"],
        ['staff_new_item', 'Yönetici bildirimi', 'Yeni kayıt: {baslik}', "{baslik}\n\n{not}\n\n{baglanti}"],
    ];
    foreach ($templates as [$key, $name, $subject, $body]) {
        $db->insert('notification_templates', ['key' => $key, 'name' => $name, 'subject' => $subject, 'body' => $body, 'send_email' => 1]);
    }

    foreach ([
        ['kvkk', 'KVKK Aydınlatma Metni'], ['gizlilik', 'Gizlilik Politikası'],
        ['kullanim-kosullari', 'Kullanım Koşulları'], ['cerez-politikasi', 'Çerez Politikası'],
        ['iletisim', 'İletişim'],
    ] as [$slug, $title]) {
        $db->insert('pages', ['slug' => $slug, 'title' => $title, 'body' => '', 'is_public' => 1]);
    }

    foreach ([
        ['hero', 'Karşılama ve otel arama', 1], ['summary', 'Bekleyen teklif ve rezervasyonlar', 2],
        ['featured', 'Anlaşmalı oteller', 3], ['regions', 'Antalya bölgeleri', 4],
        ['campaigns', 'Kampanyalar', 5], ['shortcuts', 'Kısa yollar', 6], ['support', 'Destek', 7],
    ] as [$key, $title, $sort]) {
        $db->insert('home_sections', ['key' => $key, 'title' => $title, 'is_enabled' => 1, 'sort' => $sort]);
    }

    $providers = [
        ['manual', 'Anlaşmalı Fiyatlar (Yerel)', 'App\\Providers\\Adapters\\ManualHotelProvider', 1, 1, 1, '{}', 'Yerel veritabanındaki anlaşmalı oda, fiyat ve kontenjanlar.'],
        ['stayapi', 'StayAPI', 'App\\Providers\\Adapters\\StayApiProvider', 0, 0, 0,
            json_encode(['base_url' => 'https://api.stayapi.com', 'timeout' => 8, 'path_destinations' => '/v1/booking/destinations', 'path_search' => '/v1/booking/search', 'path_hotel' => '/v1/booking/hotel/details', 'path_rooms' => '/v1/booking/hotel/rooms', 'path_account' => '/v1/account']),
            'Veri sağlayıcı. Rezervasyon oluşturmaz. Ticari gösterim izni sözleşmeyle doğrulanmadan fiyatlar yalnız “hedef teklif” referansı olarak kullanılır.'],
        ['hotelbeds', 'Hotelbeds (APItude)', 'App\\Providers\\Adapters\\HotelbedsProvider', 0, 0, 0,
            json_encode(['base_url' => 'https://api.test.hotelbeds.com', 'timeout' => 15]),
            'Yalnız Hotelbeds ile yetkili sözleşme ve sertifikasyon sonrası etkinleştirin.'],
        ['expedia_rapid', 'Expedia Rapid', 'App\\Providers\\Adapters\\ExpediaRapidProvider', 0, 0, 0,
            json_encode(['base_url' => 'https://test.ean.com', 'timeout' => 15, 'customer_ip' => '']),
            'Yalnız Expedia Partner Solutions yetkili erişimi sonrası etkinleştirin.'],
    ];
    foreach ($providers as [$code, $name, $adapter, $enabled, $book, $display, $settingsJson, $notes]) {
        $db->insert('providers', [
            'code' => $code, 'name' => $name, 'adapter' => $adapter, 'is_enabled' => $enabled,
            'booking_authorized' => $book, 'display_authorized' => $display, 'settings_json' => $settingsJson,
            'status' => $enabled ? 'ok' : 'disabled', 'notes' => $notes,
        ]);
    }
};
