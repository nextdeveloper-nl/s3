-- PostgreSQL
-- One Marketplace Product per storage server — lets each server's packages
-- (ProductCatalogs) carry their own pricing, matching price_per_gb already
-- varying per server. Auto-populated by ServersService::create(), not
-- something an admin sets manually.

ALTER TABLE s3_servers ADD COLUMN marketplace_product_id bigint REFERENCES marketplace_products(id);
CREATE INDEX ON s3_servers (marketplace_product_id);
