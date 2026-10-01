-- Gerçek otel kaynakları: OpenStreetMap (anahtarsız katalog) ve LiteAPI (canlı fiyat + rezervasyon)
-- Mevcut veriye dokunmaz.

ALTER TABLE hotels ADD COLUMN data_source VARCHAR(20) NOT NULL DEFAULT 'manual' COMMENT 'manual, osm, liteapi' AFTER is_demo;
ALTER TABLE hotels ADD COLUMN source_attribution VARCHAR(200) NULL AFTER data_source;
ALTER TABLE hotels ADD COLUMN website VARCHAR(255) NULL AFTER address;
ALTER TABLE hotels ADD COLUMN phone VARCHAR(40) NULL AFTER website;
ALTER TABLE hotels ADD COLUMN last_synced_at DATETIME NULL AFTER source_attribution;
ALTER TABLE hotels ADD KEY idx_hotels_source (data_source);

INSERT IGNORE INTO providers (code, name, adapter, is_enabled, booking_authorized, display_authorized, settings_json, status, rate_limit_per_minute, notes) VALUES
('osm', 'OpenStreetMap (gerçek otel kataloğu)', 'App\\Providers\\Adapters\\OsmOverpassProvider', 1, 0, 0,
 '{"base_url":"https://overpass-api.de/api/interpreter","timeout":70,"tourism_types":"hotel"}', 'unknown', 6,
 'Ücretsiz ve anahtarsız. Antalya bölgelerindeki gerçek otelleri (ad, yıldız, konum, adres, telefon, web) içe aktarır. Fiyat ve müsaitlik vermez; bu oteller teklif iste akışıyla çalışır. Veri lisansı ODbL — kaynak gösterimi otomatik eklenir.'),
('liteapi', 'LiteAPI (canlı fiyat ve rezervasyon)', 'App\\Providers\\Adapters\\LiteApiProvider', 0, 0, 0,
 '{"base_url":"https://api.liteapi.travel/v3.0","book_url":"https://book.liteapi.travel/v3.0","timeout":25,"currency":"TRY","nationality":"TR","payment_method":"ACC_CREDIT_CARD","supplier_timeout":8,"apply_member_discount":false}', 'disabled', 60,
 'Ücretsiz hesap ile API anahtarı alınır (dashboard.liteapi.travel). sand_ ile başlayan sandbox anahtarı gerçek otel içeriği ve TEST fiyatları verir, rezervasyonlar gerçek değildir. Canlı anahtarla fiyatlar gerçek, rezervasyonlar bağlayıcıdır.');
