-- Demo içerik işaretleri ve üyelik kayıt modu ayarları (mevcut veriye dokunmaz)

ALTER TABLE hotels ADD COLUMN is_demo TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Demo içerik; canlı moda geçişte silinir' AFTER status;
ALTER TABLE hotels ADD KEY idx_hotels_demo (is_demo);
ALTER TABLE institutions ADD COLUMN is_demo TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active;
ALTER TABLE rate_rules ADD COLUMN is_demo TINYINT(1) NOT NULL DEFAULT 0 AFTER is_active;

INSERT IGNORE INTO settings (`key`, `value`, is_secret) VALUES
('demo.active', '0', 0),
('demo.loaded_at', '', 0),
('membership.registration_mode', 'application', 0),
('membership.allowed_domains', '', 0),
('membership.default_institution_id', '', 0),
('membership.require_institution', '0', 0);
