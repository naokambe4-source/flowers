-- Sağlayıcı teklif kimlikleri (ör. LiteAPI offerId) 190 karakterden uzun olabilir; kesilmeden saklanır.
ALTER TABLE reference_prices MODIFY external_rate_id VARCHAR(3000) NULL;
