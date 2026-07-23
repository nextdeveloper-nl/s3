-- PostgreSQL
-- Egress overage baseline. Unlike storage, egress isn't tracked per-server
-- anywhere today (s3_bandwidth_monthly is account-wide only) and has no
-- per-server price field — so it stays a single blended allowance per
-- account. The overage *price* itself is a single platform-wide config
-- value (s3.packaging.egress_overage_price_per_gb), not stored here, since
-- it isn't a per-customer choice like the storage price is.
--
-- quota_egress_bytes_mo (pre-existing) keeps its current meaning: the hard
-- abuse ceiling used by QuotaHelper/CheckQuotasJob. Raised above
-- included_egress_bytes_mo the first time an account subscribes to any
-- server package (see AccountsService::subscribeToServerPackage()), so
-- normal egress overage never trips the existing block path.

ALTER TABLE s3_accounts ADD COLUMN included_egress_bytes_mo bigint NOT NULL DEFAULT 0;
