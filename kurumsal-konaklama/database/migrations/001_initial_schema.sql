-- Kurumsal Konaklama Platformu — ilk şema
-- MySQL 8+ / MariaDB 10.6+ uyumlu. Para alanları kuruş (BIGINT), yüzdeler baz puan (bp; %10 = 1000).

CREATE TABLE settings (
    `key` VARCHAR(100) NOT NULL PRIMARY KEY,
    `value` MEDIUMTEXT NULL,
    is_secret TINYINT(1) NOT NULL DEFAULT 0,
    updated_by INT UNSIGNED NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) NULL,
    is_system TINYINT(1) NOT NULL DEFAULT 0,
    is_staff TINYINT(1) NOT NULL DEFAULT 0,
    sort INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE permissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(80) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    group_name VARCHAR(80) NOT NULL,
    sort INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE role_permission (
    role_id INT UNSIGNED NOT NULL,
    permission_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    CONSTRAINT fk_rp_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    CONSTRAINT fk_rp_perm FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE institution_types (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE price_groups (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    discount_bp INT NULL COMMENT 'Boşsa genel üye indirimi uygulanır',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE institutions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    institution_type_id INT UNSIGNED NULL,
    logo_path VARCHAR(255) NULL,
    contact_name VARCHAR(150) NULL,
    phone VARCHAR(30) NULL,
    email VARCHAR(190) NULL,
    price_group_id INT UNSIGNED NULL,
    discount_bp INT NULL COMMENT 'Kuruma özel indirim; boşsa fiyat grubu / genel indirim',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    admin_notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_institution_name (name),
    KEY idx_inst_type (institution_type_id),
    KEY idx_inst_active (is_active),
    CONSTRAINT fk_inst_type FOREIGN KEY (institution_type_id) REFERENCES institution_types(id) ON DELETE SET NULL,
    CONSTRAINT fk_inst_pg FOREIGN KEY (price_group_id) REFERENCES price_groups(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(80) NOT NULL,
    last_name VARCHAR(80) NOT NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(30) NULL,
    institution_id INT UNSIGNED NULL,
    department VARCHAR(150) NULL,
    membership_type VARCHAR(30) NOT NULL DEFAULT 'personel' COMMENT 'personel, uye, yonetici, misafir_kurum',
    role_id INT UNSIGNED NOT NULL,
    status ENUM('pending','active','passive','suspended','rejected') NOT NULL DEFAULT 'pending',
    password_hash VARCHAR(255) NULL,
    password_changed_at DATETIME NULL,
    last_login_at DATETIME NULL,
    last_login_ip VARCHAR(45) NULL,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_inst (institution_id),
    KEY idx_users_role (role_id),
    KEY idx_users_status (status),
    KEY idx_users_phone (phone),
    CONSTRAINT fk_users_inst FOREIGN KEY (institution_id) REFERENCES institutions(id) ON DELETE SET NULL,
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE membership_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(80) NOT NULL,
    last_name VARCHAR(80) NOT NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(30) NULL,
    institution_id INT UNSIGNED NULL,
    institution_text VARCHAR(200) NULL COMMENT 'Listede olmayan kurum adı',
    department VARCHAR(150) NULL,
    note VARCHAR(1000) NULL,
    kvkk_accepted_at DATETIME NOT NULL,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    reviewed_by INT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    review_note VARCHAR(500) NULL,
    user_id INT UNSIGNED NULL,
    ip VARCHAR(45) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_mr_status (status, created_at),
    KEY idx_mr_email (email),
    KEY idx_mr_inst (institution_id),
    CONSTRAINT fk_mr_inst FOREIGN KEY (institution_id) REFERENCES institutions(id) ON DELETE SET NULL,
    CONSTRAINT fk_mr_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_mr_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE password_tokens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    purpose ENUM('reset','invite') NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_pt_user (user_id),
    CONSTRAINT fk_pt_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE rate_limits (
    bucket CHAR(64) NOT NULL PRIMARY KEY,
    hits INT UNSIGNED NOT NULL DEFAULT 0,
    reset_at DATETIME NOT NULL,
    KEY idx_rl_reset (reset_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE regions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(140) NOT NULL UNIQUE,
    parent_id INT UNSIGNED NULL,
    description VARCHAR(500) NULL,
    image_path VARCHAR(255) NULL,
    illustration VARCHAR(30) NULL COMMENT 'Görsel yoksa kullanılacak temsili illüstrasyon',
    latitude DECIMAL(10,7) NULL,
    longitude DECIMAL(10,7) NULL,
    sort INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    show_on_home TINYINT(1) NOT NULL DEFAULT 1,
    KEY idx_regions_parent (parent_id),
    CONSTRAINT fk_regions_parent FOREIGN KEY (parent_id) REFERENCES regions(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE concepts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    code VARCHAR(10) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    sort INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE amenities (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    icon VARCHAR(40) NULL,
    scope ENUM('hotel','room','both') NOT NULL DEFAULT 'hotel',
    filter_key VARCHAR(40) NULL COMMENT 'Arama filtresinde kullanılan anahtar (beach, sea_front, pool...)',
    sort INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uq_amenity_name_scope (name, scope),
    KEY idx_amenity_filter (filter_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hotels (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(220) NOT NULL UNIQUE,
    stars TINYINT UNSIGNED NULL,
    region_id INT UNSIGNED NULL,
    district VARCHAR(100) NULL,
    neighborhood VARCHAR(120) NULL,
    address VARCHAR(500) NULL,
    latitude DECIMAL(10,7) NULL,
    longitude DECIMAL(10,7) NULL,
    short_description VARCHAR(500) NULL,
    description TEXT NULL,
    cover_image_id INT UNSIGNED NULL,
    video_url VARCHAR(255) NULL,
    concept_id INT UNSIGNED NULL,
    beach_type VARCHAR(40) NULL COMMENT 'kum, cakil, iskele, platform, yok',
    beach_info VARCHAR(500) NULL,
    sea_distance_m INT UNSIGNED NULL,
    airport_distance_km DECIMAL(6,1) NULL,
    center_distance_km DECIMAL(6,1) NULL,
    check_in_time VARCHAR(5) NULL,
    check_out_time VARCHAR(5) NULL,
    child_policy TEXT NULL,
    pet_policy VARCHAR(500) NULL,
    cancellation_policy TEXT NULL,
    payment_policy TEXT NULL,
    important_info TEXT NULL,
    booking_mode ENUM('instant','request','offer') NOT NULL DEFAULT 'request',
    is_contracted TINYINT(1) NOT NULL DEFAULT 0,
    contract_valid_until DATE NULL,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    featured_sort INT NOT NULL DEFAULT 0,
    vat_bp INT NULL COMMENT 'Boşsa genel ayar',
    accommodation_tax_bp INT NULL COMMENT 'Boşsa genel ayar',
    status ENUM('draft','published','unpublished') NOT NULL DEFAULT 'draft',
    wizard_step TINYINT UNSIGNED NOT NULL DEFAULT 1,
    admin_notes TEXT NULL,
    created_by INT UNSIGNED NULL,
    published_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_hotels_status (status),
    KEY idx_hotels_region (region_id, status),
    KEY idx_hotels_featured (is_featured, featured_sort),
    KEY idx_hotels_name (name),
    CONSTRAINT fk_hotels_region FOREIGN KEY (region_id) REFERENCES regions(id) ON DELETE SET NULL,
    CONSTRAINT fk_hotels_concept FOREIGN KEY (concept_id) REFERENCES concepts(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE hotel_images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hotel_id INT UNSIGNED NOT NULL,
    storage_key VARCHAR(100) NOT NULL,
    original_name VARCHAR(255) NULL,
    mime VARCHAR(40) NOT NULL,
    width INT UNSIGNED NULL,
    height INT UNSIGNED NULL,
    bytes INT UNSIGNED NULL,
    caption VARCHAR(200) NULL,
    sort INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_hi_hotel (hotel_id, sort),
    CONSTRAINT fk_hi_hotel FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE hotels ADD CONSTRAINT fk_hotels_cover FOREIGN KEY (cover_image_id) REFERENCES hotel_images(id) ON DELETE SET NULL;

CREATE TABLE hotel_amenity (
    hotel_id INT UNSIGNED NOT NULL,
    amenity_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (hotel_id, amenity_id),
    KEY idx_ha_amenity (amenity_id),
    CONSTRAINT fk_ha_hotel FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE,
    CONSTRAINT fk_ha_amenity FOREIGN KEY (amenity_id) REFERENCES amenities(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE rooms (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hotel_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT NULL,
    size_m2 SMALLINT UNSIGNED NULL,
    bed_type VARCHAR(100) NULL,
    view_type VARCHAR(100) NULL,
    max_adults TINYINT UNSIGNED NOT NULL DEFAULT 2,
    max_children TINYINT UNSIGNED NOT NULL DEFAULT 0,
    max_occupancy TINYINT UNSIGNED NOT NULL DEFAULT 2,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_rooms_hotel (hotel_id, is_active, sort),
    CONSTRAINT fk_rooms_hotel FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE room_images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    room_id INT UNSIGNED NOT NULL,
    storage_key VARCHAR(100) NOT NULL,
    original_name VARCHAR(255) NULL,
    mime VARCHAR(40) NOT NULL,
    width INT UNSIGNED NULL,
    height INT UNSIGNED NULL,
    bytes INT UNSIGNED NULL,
    caption VARCHAR(200) NULL,
    sort INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_ri_room (room_id, sort),
    CONSTRAINT fk_ri_room FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE room_amenity (
    room_id INT UNSIGNED NOT NULL,
    amenity_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (room_id, amenity_id),
    KEY idx_ra_amenity (amenity_id),
    CONSTRAINT fk_ra_room FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    CONSTRAINT fk_ra_amenity FOREIGN KEY (amenity_id) REFERENCES amenities(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE providers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(40) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    adapter VARCHAR(120) NOT NULL,
    is_enabled TINYINT(1) NOT NULL DEFAULT 0,
    booking_authorized TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Ticari satış/rezervasyon yetkisi yazılı olarak doğrulandı',
    display_authorized TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Fiyat verisinin üyelere gösterim izni doğrulandı',
    settings_json TEXT NULL,
    status ENUM('unknown','ok','degraded','error','disabled') NOT NULL DEFAULT 'unknown',
    last_check_at DATETIME NULL,
    last_error VARCHAR(500) NULL,
    quota_remaining INT NULL,
    quota_reset_at DATETIME NULL,
    rate_limit_per_minute INT UNSIGNED NOT NULL DEFAULT 30,
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE provider_credentials (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    provider_id INT UNSIGNED NOT NULL,
    key_name VARCHAR(60) NOT NULL,
    value_encrypted TEXT NOT NULL,
    last4 VARCHAR(8) NULL,
    updated_by INT UNSIGNED NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_pc (provider_id, key_name),
    CONSTRAINT fk_pc_provider FOREIGN KEY (provider_id) REFERENCES providers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE provider_destinations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    provider_id INT UNSIGNED NOT NULL,
    region_id INT UNSIGNED NOT NULL,
    external_id VARCHAR(100) NOT NULL,
    external_name VARCHAR(200) NOT NULL,
    external_type VARCHAR(40) NULL,
    country_code CHAR(2) NULL,
    verified_by INT UNSIGNED NULL,
    verified_at DATETIME NULL,
    UNIQUE KEY uq_pd (provider_id, region_id),
    CONSTRAINT fk_pd_provider FOREIGN KEY (provider_id) REFERENCES providers(id) ON DELETE CASCADE,
    CONSTRAINT fk_pd_region FOREIGN KEY (region_id) REFERENCES regions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE provider_hotel_map (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    provider_id INT UNSIGNED NOT NULL,
    hotel_id INT UNSIGNED NOT NULL,
    external_hotel_id VARCHAR(100) NOT NULL,
    external_name VARCHAR(200) NULL,
    verified_by INT UNSIGNED NULL,
    verified_at DATETIME NULL,
    UNIQUE KEY uq_phm (provider_id, hotel_id),
    KEY idx_phm_ext (provider_id, external_hotel_id),
    CONSTRAINT fk_phm_provider FOREIGN KEY (provider_id) REFERENCES providers(id) ON DELETE CASCADE,
    CONSTRAINT fk_phm_hotel FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE rate_plans (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hotel_id INT UNSIGNED NOT NULL,
    room_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    concept_id INT UNSIGNED NULL,
    source ENUM('contract','campaign') NOT NULL DEFAULT 'contract',
    currency CHAR(3) NOT NULL DEFAULT 'TRY',
    tax_included TINYINT(1) NOT NULL DEFAULT 1,
    base_adults TINYINT UNSIGNED NOT NULL DEFAULT 2 COMMENT 'Gecelik fiyatın kapsadığı yetişkin sayısı',
    free_child_max_age TINYINT UNSIGNED NOT NULL DEFAULT 6 COMMENT 'Bu yaş ve altı çocuk ücretsiz',
    child_max_age TINYINT UNSIGNED NOT NULL DEFAULT 12 COMMENT 'Bu yaştan büyükler yetişkin fiyatı',
    refundable TINYINT(1) NOT NULL DEFAULT 1,
    free_cancel_days SMALLINT UNSIGNED NULL COMMENT 'Girişten kaç gün öncesine kadar ücretsiz iptal',
    cancellation_policy TEXT NULL,
    payment_terms TEXT NULL,
    is_bookable TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Geçerli otel anlaşması ile kesin satış yetkisi var',
    member_discount_applies TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1: girilen fiyat indirim öncesi fiyattır; 0: net kurumsal fiyattır',
    contract_reference VARCHAR(120) NULL,
    valid_from DATE NULL,
    valid_to DATE NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    verified_by INT UNSIGNED NULL,
    verified_at DATETIME NULL,
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_rp_hotel (hotel_id, is_active),
    KEY idx_rp_room (room_id),
    CONSTRAINT fk_rp_hotel FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE,
    CONSTRAINT fk_rp_room FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    CONSTRAINT fk_rp_concept FOREIGN KEY (concept_id) REFERENCES concepts(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE rates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rate_plan_id INT UNSIGNED NOT NULL,
    stay_date DATE NOT NULL,
    price_minor BIGINT NOT NULL COMMENT 'Oda başı gecelik, base_adults kişiye kadar',
    single_minor BIGINT NULL COMMENT 'Tek kişi kullanımı (boşsa price_minor)',
    extra_adult_minor BIGINT NOT NULL DEFAULT 0,
    child_minor BIGINT NOT NULL DEFAULT 0,
    updated_by INT UNSIGNED NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_rates (rate_plan_id, stay_date),
    KEY idx_rates_date (stay_date),
    CONSTRAINT fk_rates_plan FOREIGN KEY (rate_plan_id) REFERENCES rate_plans(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reference_prices (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hotel_id INT UNSIGNED NOT NULL,
    room_id INT UNSIGNED NULL,
    room_name VARCHAR(150) NULL,
    source ENUM('manual_reference','provider') NOT NULL,
    provider_id INT UNSIGNED NULL,
    source_label VARCHAR(150) NULL COMMENT 'Fiyatın görüldüğü kaynak (site adı vb.)',
    currency CHAR(3) NOT NULL DEFAULT 'TRY',
    total_minor BIGINT NOT NULL,
    tax_included TINYINT(1) NOT NULL DEFAULT 1,
    check_in DATE NOT NULL,
    check_out DATE NOT NULL,
    adults TINYINT UNSIGNED NOT NULL,
    children_ages VARCHAR(60) NOT NULL DEFAULT '' COMMENT 'Virgülle ayrılmış, sıralı',
    rooms_count TINYINT UNSIGNED NOT NULL DEFAULT 1,
    concept_id INT UNSIGNED NULL,
    refundable TINYINT(1) NULL,
    cancellation_summary VARCHAR(500) NULL,
    captured_at DATETIME NOT NULL COMMENT 'Sorgu zamanı',
    valid_until DATETIME NOT NULL,
    verified_by INT UNSIGNED NULL,
    verified_at DATETIME NULL,
    external_rate_id VARCHAR(190) NULL,
    provider_bookable TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Sağlayıcı bu rate için rezervasyonu destekliyor',
    notes VARCHAR(500) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_ref_lookup (hotel_id, check_in, check_out, adults, valid_until),
    KEY idx_ref_valid (valid_until),
    CONSTRAINT fk_ref_hotel FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE,
    CONSTRAINT fk_ref_room FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL,
    CONSTRAINT fk_ref_provider FOREIGN KEY (provider_id) REFERENCES providers(id) ON DELETE SET NULL,
    CONSTRAINT fk_ref_concept FOREIGN KEY (concept_id) REFERENCES concepts(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE seasons (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    kind ENUM('season','holiday','special') NOT NULL DEFAULT 'season',
    date_from DATE NOT NULL,
    date_to DATE NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    KEY idx_seasons_dates (date_from, date_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE rate_rules (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    kind ENUM('global','institution','price_group','hotel','room','date_range','weekday','weekend','season','holiday','early_booking','last_minute','long_stay','campaign') NOT NULL,
    adjustment ENUM('discount_percent','discount_per_night','surcharge_percent','surcharge_per_night') NOT NULL DEFAULT 'discount_percent',
    value INT NOT NULL COMMENT 'Yüzde için bp, tutar için kuruş',
    institution_id INT UNSIGNED NULL,
    price_group_id INT UNSIGNED NULL,
    hotel_id INT UNSIGNED NULL,
    room_id INT UNSIGNED NULL,
    season_id INT UNSIGNED NULL,
    stay_from DATE NULL,
    stay_to DATE NULL,
    weekdays VARCHAR(20) NULL COMMENT 'ISO gün numaraları: 1=Pzt ... 7=Paz',
    min_nights SMALLINT UNSIGNED NULL,
    min_lead_days SMALLINT UNSIGNED NULL COMMENT 'Erken rezervasyon: girişe en az kaç gün',
    max_lead_days SMALLINT UNSIGNED NULL COMMENT 'Son dakika: girişe en fazla kaç gün',
    priority INT NOT NULL DEFAULT 100,
    stackable TINYINT(1) NOT NULL DEFAULT 1,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    valid_from DATETIME NULL COMMENT 'Kuralın satışta geçerli olduğu dönem',
    valid_to DATETIME NULL,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_rr_active (is_active, priority),
    KEY idx_rr_hotel (hotel_id),
    CONSTRAINT fk_rr_inst FOREIGN KEY (institution_id) REFERENCES institutions(id) ON DELETE CASCADE,
    CONSTRAINT fk_rr_pg FOREIGN KEY (price_group_id) REFERENCES price_groups(id) ON DELETE CASCADE,
    CONSTRAINT fk_rr_hotel FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE,
    CONSTRAINT fk_rr_room FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    CONSTRAINT fk_rr_season FOREIGN KEY (season_id) REFERENCES seasons(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE campaigns (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    summary VARCHAR(500) NULL,
    description TEXT NULL,
    image_path VARCHAR(255) NULL,
    hotel_id INT UNSIGNED NULL,
    region_id INT UNSIGNED NULL,
    rate_rule_id INT UNSIGNED NULL COMMENT 'Fiyata yansıyan kural; kampanya yalnız bu kural aktifken anlamlıdır',
    valid_from DATETIME NOT NULL,
    valid_to DATETIME NOT NULL,
    stay_from DATE NULL,
    stay_to DATE NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    show_on_home TINYINT(1) NOT NULL DEFAULT 1,
    sort INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_campaign_valid (is_active, valid_from, valid_to),
    CONSTRAINT fk_camp_hotel FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE,
    CONSTRAINT fk_camp_region FOREIGN KEY (region_id) REFERENCES regions(id) ON DELETE SET NULL,
    CONSTRAINT fk_camp_rule FOREIGN KEY (rate_rule_id) REFERENCES rate_rules(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE promo_codes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(40) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    adjustment ENUM('discount_percent','discount_amount') NOT NULL DEFAULT 'discount_percent',
    value INT NOT NULL,
    max_uses INT UNSIGNED NULL,
    used_count INT UNSIGNED NOT NULL DEFAULT 0,
    per_user_limit INT UNSIGNED NULL,
    institution_id INT UNSIGNED NULL,
    hotel_id INT UNSIGNED NULL,
    min_nights SMALLINT UNSIGNED NULL,
    valid_from DATETIME NULL,
    valid_to DATETIME NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_promo_inst FOREIGN KEY (institution_id) REFERENCES institutions(id) ON DELETE CASCADE,
    CONSTRAINT fk_promo_hotel FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE inventory (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    room_id INT UNSIGNED NOT NULL,
    stay_date DATE NOT NULL,
    total_units SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    booked_units SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    is_open TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Satış açık/kapalı',
    min_stay SMALLINT UNSIGNED NULL,
    max_stay SMALLINT UNSIGNED NULL,
    note VARCHAR(255) NULL,
    updated_by INT UNSIGNED NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_inventory (room_id, stay_date),
    KEY idx_inv_date (stay_date),
    CONSTRAINT fk_inv_room FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE stop_sales (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hotel_id INT UNSIGNED NOT NULL,
    room_id INT UNSIGNED NULL COMMENT 'Boşsa tüm odalar',
    date_from DATE NOT NULL,
    date_to DATE NOT NULL,
    reason VARCHAR(255) NULL,
    created_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_ss_hotel (hotel_id, date_from, date_to),
    CONSTRAINT fk_ss_hotel FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE,
    CONSTRAINT fk_ss_room FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE accommodation_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    user_id INT UNSIGNED NOT NULL,
    institution_id INT UNSIGNED NULL,
    hotel_id INT UNSIGNED NULL,
    region_id INT UNSIGNED NULL,
    room_id INT UNSIGNED NULL,
    concept_id INT UNSIGNED NULL,
    check_in DATE NOT NULL,
    check_out DATE NOT NULL,
    rooms_json TEXT NOT NULL COMMENT 'Oda bazlı konuk dağılımı',
    adults TINYINT UNSIGNED NOT NULL,
    children TINYINT UNSIGNED NOT NULL DEFAULT 0,
    target_minor BIGINT NULL COMMENT 'Onaya bağlı hedef teklif tutarı',
    target_reference_id BIGINT UNSIGNED NULL,
    notes VARCHAR(2000) NULL,
    contact_phone VARCHAR(30) NULL,
    status ENUM('new','offered','accepted','declined','cancelled','expired','converted') NOT NULL DEFAULT 'new',
    idempotency_key VARCHAR(64) NULL,
    assigned_to INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ar_idem (user_id, idempotency_key),
    KEY idx_ar_status (status, created_at),
    KEY idx_ar_inst (institution_id),
    CONSTRAINT fk_ar_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_ar_inst FOREIGN KEY (institution_id) REFERENCES institutions(id) ON DELETE SET NULL,
    CONSTRAINT fk_ar_hotel FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE SET NULL,
    CONSTRAINT fk_ar_region FOREIGN KEY (region_id) REFERENCES regions(id) ON DELETE SET NULL,
    CONSTRAINT fk_ar_room FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL,
    CONSTRAINT fk_ar_concept FOREIGN KEY (concept_id) REFERENCES concepts(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE bookings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(20) NOT NULL UNIQUE,
    user_id INT UNSIGNED NOT NULL,
    institution_id INT UNSIGNED NULL,
    hotel_id INT UNSIGNED NOT NULL,
    check_in DATE NOT NULL,
    check_out DATE NOT NULL,
    nights SMALLINT UNSIGNED NOT NULL,
    rooms_count TINYINT UNSIGNED NOT NULL DEFAULT 1,
    adults TINYINT UNSIGNED NOT NULL,
    children TINYINT UNSIGNED NOT NULL DEFAULT 0,
    status ENUM('draft','requested','pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'draft',
    mode ENUM('instant','request','offer') NOT NULL,
    source ENUM('contract','provider','offer') NOT NULL,
    provider_id INT UNSIGNED NULL,
    provider_reference VARCHAR(120) NULL,
    hotel_confirmation_no VARCHAR(120) NULL,
    offer_id INT UNSIGNED NULL,
    currency CHAR(3) NOT NULL DEFAULT 'TRY',
    source_total_minor BIGINT NOT NULL DEFAULT 0 COMMENT 'İndirim öncesi kaynak toplam',
    discount_minor BIGINT NOT NULL DEFAULT 0,
    tax_minor BIGINT NOT NULL DEFAULT 0,
    total_minor BIGINT NOT NULL DEFAULT 0,
    tax_included TINYINT(1) NOT NULL DEFAULT 1,
    verified_savings_minor BIGINT NULL COMMENT 'Doğrulanmış karşılaştırmalı tasarruf',
    reference_price_id BIGINT UNSIGNED NULL,
    price_breakdown MEDIUMTEXT NULL,
    quote_hash CHAR(64) NULL,
    terms_snapshot MEDIUMTEXT NULL,
    stock_managed TINYINT(1) NOT NULL DEFAULT 0,
    promo_code_id INT UNSIGNED NULL,
    contact_name VARCHAR(160) NULL,
    contact_phone VARCHAR(30) NULL,
    contact_email VARCHAR(190) NULL,
    notes VARCHAR(2000) NULL,
    admin_notes TEXT NULL,
    payment_status ENUM('unpaid','pay_at_hotel','invoiced','paid','refunded') NOT NULL DEFAULT 'pay_at_hotel',
    verify_token CHAR(43) NULL UNIQUE,
    idempotency_key VARCHAR(64) NULL,
    cancel_reason VARCHAR(500) NULL,
    cancelled_by INT UNSIGNED NULL,
    submitted_at DATETIME NULL,
    confirmed_at DATETIME NULL,
    cancelled_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_bookings_idem (user_id, idempotency_key),
    KEY idx_b_status (status, check_in),
    KEY idx_b_user (user_id, created_at),
    KEY idx_b_inst (institution_id, created_at),
    KEY idx_b_hotel (hotel_id, check_in),
    KEY idx_b_dates (check_in, check_out),
    CONSTRAINT fk_b_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_b_inst FOREIGN KEY (institution_id) REFERENCES institutions(id) ON DELETE SET NULL,
    CONSTRAINT fk_b_hotel FOREIGN KEY (hotel_id) REFERENCES hotels(id),
    CONSTRAINT fk_b_provider FOREIGN KEY (provider_id) REFERENCES providers(id) ON DELETE SET NULL,
    CONSTRAINT fk_b_promo FOREIGN KEY (promo_code_id) REFERENCES promo_codes(id) ON DELETE SET NULL,
    CONSTRAINT fk_b_ref FOREIGN KEY (reference_price_id) REFERENCES reference_prices(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE offers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    request_id INT UNSIGNED NOT NULL,
    version SMALLINT UNSIGNED NOT NULL,
    hotel_id INT UNSIGNED NOT NULL,
    room_id INT UNSIGNED NULL,
    room_name VARCHAR(150) NOT NULL,
    concept_id INT UNSIGNED NULL,
    check_in DATE NOT NULL,
    check_out DATE NOT NULL,
    total_minor BIGINT NOT NULL COMMENT 'Vergiler dahil toplam',
    currency CHAR(3) NOT NULL DEFAULT 'TRY',
    payment_terms TEXT NOT NULL,
    cancellation_terms TEXT NOT NULL,
    valid_until DATETIME NOT NULL,
    status ENUM('sent','superseded','accepted','declined','expired','withdrawn') NOT NULL DEFAULT 'sent',
    note VARCHAR(1000) NULL,
    created_by INT UNSIGNED NULL,
    accepted_at DATETIME NULL,
    booking_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_offer_version (request_id, version),
    KEY idx_offer_status (status, valid_until),
    CONSTRAINT fk_offer_req FOREIGN KEY (request_id) REFERENCES accommodation_requests(id) ON DELETE CASCADE,
    CONSTRAINT fk_offer_hotel FOREIGN KEY (hotel_id) REFERENCES hotels(id),
    CONSTRAINT fk_offer_room FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL,
    CONSTRAINT fk_offer_concept FOREIGN KEY (concept_id) REFERENCES concepts(id) ON DELETE SET NULL,
    CONSTRAINT fk_offer_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE bookings ADD CONSTRAINT fk_b_offer FOREIGN KEY (offer_id) REFERENCES offers(id) ON DELETE SET NULL;

CREATE TABLE booking_rooms (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id INT UNSIGNED NOT NULL,
    room_id INT UNSIGNED NULL,
    rate_plan_id INT UNSIGNED NULL,
    room_name VARCHAR(150) NOT NULL,
    concept_name VARCHAR(100) NULL,
    adults TINYINT UNSIGNED NOT NULL,
    children_ages VARCHAR(60) NOT NULL DEFAULT '',
    total_minor BIGINT NOT NULL DEFAULT 0,
    KEY idx_br_booking (booking_id),
    KEY idx_br_room (room_id),
    CONSTRAINT fk_br_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    CONSTRAINT fk_br_room FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL,
    CONSTRAINT fk_br_plan FOREIGN KEY (rate_plan_id) REFERENCES rate_plans(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE booking_guests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id INT UNSIGNED NOT NULL,
    booking_room_id INT UNSIGNED NULL,
    first_name VARCHAR(80) NOT NULL,
    last_name VARCHAR(80) NOT NULL,
    is_child TINYINT(1) NOT NULL DEFAULT 0,
    age TINYINT UNSIGNED NULL,
    is_lead TINYINT(1) NOT NULL DEFAULT 0,
    KEY idx_bg_booking (booking_id),
    CONSTRAINT fk_bg_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    CONSTRAINT fk_bg_room FOREIGN KEY (booking_room_id) REFERENCES booking_rooms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE booking_status_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id INT UNSIGNED NOT NULL,
    from_status VARCHAR(20) NULL,
    to_status VARCHAR(20) NOT NULL,
    note VARCHAR(1000) NULL,
    changed_by INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_bsh_booking (booking_id, created_at),
    CONSTRAINT fk_bsh_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE booking_inventory (
    booking_id INT UNSIGNED NOT NULL,
    room_id INT UNSIGNED NOT NULL,
    stay_date DATE NOT NULL,
    units SMALLINT UNSIGNED NOT NULL,
    PRIMARY KEY (booking_id, room_id, stay_date),
    CONSTRAINT fk_bi_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE favorites (
    user_id INT UNSIGNED NOT NULL,
    hotel_id INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, hotel_id),
    KEY idx_fav_hotel (hotel_id),
    CONSTRAINT fk_fav_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_fav_hotel FOREIGN KEY (hotel_id) REFERENCES hotels(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(200) NOT NULL,
    body VARCHAR(1000) NULL,
    link VARCHAR(255) NULL,
    read_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_notif_user (user_id, read_at, created_at),
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notification_templates (
    `key` VARCHAR(60) NOT NULL PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    body TEXT NOT NULL,
    send_email TINYINT(1) NOT NULL DEFAULT 1,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE api_cache (
    cache_key CHAR(64) NOT NULL PRIMARY KEY,
    provider_id INT UNSIGNED NULL,
    kind ENUM('content','rates','destination') NOT NULL,
    payload MEDIUMTEXT NOT NULL,
    created_at DATETIME NOT NULL,
    expires_at DATETIME NOT NULL,
    KEY idx_cache_exp (expires_at),
    KEY idx_cache_provider (provider_id, kind)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE api_request_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    provider_id INT UNSIGNED NULL,
    operation VARCHAR(40) NOT NULL,
    method VARCHAR(8) NOT NULL,
    endpoint VARCHAR(255) NOT NULL,
    request_summary VARCHAR(1000) NULL,
    status_code SMALLINT NULL,
    duration_ms INT UNSIGNED NULL,
    success TINYINT(1) NOT NULL DEFAULT 0,
    from_cache TINYINT(1) NOT NULL DEFAULT 0,
    error VARCHAR(500) NULL,
    user_id INT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_arl_provider (provider_id, created_at),
    KEY idx_arl_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    action VARCHAR(80) NOT NULL,
    entity_type VARCHAR(60) NULL,
    entity_id VARCHAR(60) NULL,
    old_values MEDIUMTEXT NULL,
    new_values MEDIUMTEXT NULL,
    ip VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_audit_entity (entity_type, entity_id),
    KEY idx_audit_user (user_id, created_at),
    KEY idx_audit_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(80) NOT NULL UNIQUE,
    title VARCHAR(200) NOT NULL,
    body MEDIUMTEXT NULL,
    is_public TINYINT(1) NOT NULL DEFAULT 1,
    updated_by INT UNSIGNED NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE home_sections (
    `key` VARCHAR(40) NOT NULL PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    is_enabled TINYINT(1) NOT NULL DEFAULT 1,
    sort INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE support_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    name VARCHAR(160) NULL,
    email VARCHAR(190) NULL,
    phone VARCHAR(30) NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    booking_code VARCHAR(20) NULL,
    status ENUM('open','answered','closed') NOT NULL DEFAULT 'open',
    reply TEXT NULL,
    replied_by INT UNSIGNED NULL,
    replied_at DATETIME NULL,
    ip VARCHAR(45) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_sr_status (status, created_at),
    KEY idx_sr_user (user_id),
    CONSTRAINT fk_sr_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(60) NOT NULL,
    payload MEDIUMTEXT NOT NULL,
    status ENUM('queued','running','done','failed') NOT NULL DEFAULT 'queued',
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    max_attempts TINYINT UNSIGNED NOT NULL DEFAULT 5,
    available_at DATETIME NOT NULL,
    reserved_at DATETIME NULL,
    completed_at DATETIME NULL,
    last_error VARCHAR(1000) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_jobs_queue (status, available_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
