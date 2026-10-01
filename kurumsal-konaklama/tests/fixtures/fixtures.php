<?php
declare(strict_types=1);

/** YALNIZ TEST: örnek kurum, kullanıcı, otel, oda, fiyat ve kontenjan verisi. Üretim paketine dahil edilmez. */
function seed_fixtures(App\Core\Database $db): array
{
    $role = static fn (string $s) => (int) $db->value('SELECT id FROM roles WHERE slug = ?', [$s]);
    $pw = password_hash('Test12345678', PASSWORD_DEFAULT);
    $now = date('Y-m-d H:i:s');
    $instA = $db->insert('institutions', ['name' => 'Test Kurumu A', 'institution_type_id' => 1, 'is_active' => 1, 'discount_bp' => null]);
    $instB = $db->insert('institutions', ['name' => 'Test Kurumu B', 'institution_type_id' => 2, 'is_active' => 1, 'discount_bp' => 1500]);
    $instPassive = $db->insert('institutions', ['name' => 'Pasif Kurum', 'is_active' => 0]);
    $users = [
        ['admin@test.local', 'Ayşe', 'Yönetici', 'super_admin', 'active', null],
        ['rez@test.local', 'Rıza', 'Rezervasyon', 'reservation_officer', 'active', null],
        ['uye@test.local', 'Mehmet', 'Üye', 'member', 'active', $instA],
        ['uye2@test.local', 'Zeynep', 'Diğer', 'member', 'active', $instB],
        ['kurum@test.local', 'Kemal', 'Kurum', 'institution_manager', 'active', $instA],
        ['bekleyen@test.local', 'Bekir', 'Bekler', 'member', 'pending', $instA],
        ['pasif@test.local', 'Pelin', 'Pasif', 'member', 'passive', $instA],
        ['pasifkurum@test.local', 'Kerem', 'Kurumsuz', 'member', 'active', $instPassive],
    ];
    foreach ($users as [$email, $f, $l, $r, $st, $inst]) {
        $db->insert('users', ['email' => $email, 'first_name' => $f, 'last_name' => $l, 'role_id' => $role($r), 'status' => $st, 'institution_id' => $inst, 'password_hash' => $pw, 'password_changed_at' => $now, 'phone' => '05551112233']);
    }
    $ai = (int) $db->value("SELECT id FROM concepts WHERE code = 'AI'");
    $bb = (int) $db->value("SELECT id FROM concepts WHERE code = 'BB'");
    $lara = (int) $db->value("SELECT id FROM regions WHERE slug = 'lara'");
    $kemer = (int) $db->value("SELECT id FROM regions WHERE slug = 'kemer'");
    $mk = static function (array $d) use ($db): int { return $db->insert('hotels', $d + ['status' => 'published', 'published_at' => date('Y-m-d H:i:s'), 'check_in_time' => '14:00', 'check_out_time' => '12:00', 'cancellation_policy' => 'Girişten 7 gün öncesine kadar ücretsiz iptal.', 'address' => 'Test adresi']); };
    $h1 = $mk(['name' => 'Test Anlaşmalı Otel', 'slug' => 'test-anlasmali-otel', 'stars' => 5, 'region_id' => $lara, 'district' => 'Muratpaşa', 'latitude' => 36.86, 'longitude' => 30.83, 'concept_id' => $ai, 'booking_mode' => 'instant', 'is_contracted' => 1, 'is_featured' => 1, 'short_description' => 'Test amaçlı anlaşmalı otel kaydı.', 'sea_distance_m' => 0]);
    $h2 = $mk(['name' => 'Test Talep Oteli', 'slug' => 'test-talep-oteli', 'stars' => 4, 'region_id' => $kemer, 'concept_id' => $bb, 'booking_mode' => 'request', 'is_contracted' => 1, 'latitude' => 36.60, 'longitude' => 30.56]);
    $h3 = $mk(['name' => 'Test Referans Oteli', 'slug' => 'test-referans-oteli', 'stars' => 4, 'region_id' => $lara, 'booking_mode' => 'request', 'is_contracted' => 0]);
    $pool = (int) $db->value("SELECT id FROM amenities WHERE filter_key = 'pool'");
    $db->insert('hotel_amenity', ['hotel_id' => $h1, 'amenity_id' => $pool]);
    $r1 = $db->insert('rooms', ['hotel_id' => $h1, 'name' => 'Standart Deniz Manzaralı', 'max_adults' => 3, 'max_children' => 2, 'max_occupancy' => 4, 'size_m2' => 28]);
    $r2 = $db->insert('rooms', ['hotel_id' => $h2, 'name' => 'Aile Odası', 'max_adults' => 4, 'max_children' => 2, 'max_occupancy' => 5]);
    $r3 = $db->insert('rooms', ['hotel_id' => $h3, 'name' => 'Standart Oda', 'max_adults' => 2, 'max_children' => 1, 'max_occupancy' => 3]);
    $p1 = $db->insert('rate_plans', ['hotel_id' => $h1, 'room_id' => $r1, 'name' => 'Anlaşmalı HD', 'concept_id' => $ai, 'is_bookable' => 1, 'refundable' => 1, 'free_cancel_days' => 7, 'base_adults' => 2]);
    $p2 = $db->insert('rate_plans', ['hotel_id' => $h2, 'room_id' => $r2, 'name' => 'Anlaşmalı OK', 'concept_id' => $bb, 'is_bookable' => 1, 'refundable' => 1, 'base_adults' => 2]);
    $start = new DateTimeImmutable('today');
    for ($i = 0; $i < 120; $i++) {
        $d = $start->modify("+$i day")->format('Y-m-d');
        $db->insert('rates', ['rate_plan_id' => $p1, 'stay_date' => $d, 'price_minor' => 500000, 'extra_adult_minor' => 150000, 'child_minor' => 100000]);
        $db->insert('rates', ['rate_plan_id' => $p2, 'stay_date' => $d, 'price_minor' => 300000, 'extra_adult_minor' => 80000, 'child_minor' => 50000]);
        $db->insert('inventory', ['room_id' => $r1, 'stay_date' => $d, 'total_units' => 1, 'booked_units' => 0, 'is_open' => 1]);
        $db->insert('inventory', ['room_id' => $r2, 'stay_date' => $d, 'total_units' => 5, 'booked_units' => 0, 'is_open' => 1]);
    }
    return ['instA' => $instA, 'instB' => $instB, 'h1' => $h1, 'h2' => $h2, 'h3' => $h3, 'r1' => $r1, 'r2' => $r2, 'r3' => $r3, 'p1' => $p1, 'p2' => $p2];
}
