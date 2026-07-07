-- PostgreSQL
-- Read-only perspective: one row per s3_accounts row currently at or above the
-- quota warning threshold (see QuotaHelper::WARN_THRESHOLD, 80%) on storage or
-- monthly egress, or already blocked. Mirrors the same 80%/100% thresholds
-- QuotaHelper/CheckQuotasJob use to send QuotaWarningNotification /
-- QuotaExceededNotification, so this view always matches what triggered (or
-- will trigger) an alert email.
--
-- This is a VIEW, not a table — nothing to migrate, just re-run this file
-- whenever the SELECT needs to change.

CREATE OR REPLACE VIEW s3_quota_alerts_perspective AS
SELECT
    a.id                       AS id,
    a.uuid                     AS uuid,
    a.id                       AS s3_account_id,
    a.iam_account_id           AS iam_account_id,
    a.iam_user_id              AS iam_user_id,
    a.slug                     AS s3_account_slug,
    a.quota_storage_bytes      AS quota_storage_bytes,
    a.storage_bytes_used       AS storage_bytes_used,
    CASE WHEN a.quota_storage_bytes > 0
         THEN round((a.storage_bytes_used::numeric / a.quota_storage_bytes) * 100, 2)
         ELSE 0
    END                        AS storage_usage_pct,
    a.quota_egress_bytes_mo    AS quota_egress_bytes_mo,
    a.egress_bytes_mo_used     AS egress_bytes_mo_used,
    CASE WHEN a.quota_egress_bytes_mo > 0
         THEN round((a.egress_bytes_mo_used::numeric / a.quota_egress_bytes_mo) * 100, 2)
         ELSE 0
    END                        AS egress_usage_pct,
    CASE
        WHEN (a.quota_storage_bytes > 0 AND a.storage_bytes_used >= a.quota_storage_bytes)
          OR (a.quota_egress_bytes_mo > 0 AND a.egress_bytes_mo_used >= a.quota_egress_bytes_mo)
            THEN 'exceeded'
        ELSE 'warning'
    END                        AS severity,
    (a.blocked_at IS NOT NULL) AS is_blocked,
    a.blocked_at               AS blocked_at,
    a.blocked_reason           AS blocked_reason,
    a.usage_checked_at         AS usage_checked_at,
    a.created_at               AS created_at,
    a.updated_at               AS updated_at,
    a.deleted_at               AS deleted_at
FROM s3_accounts a
WHERE a.deleted_at IS NULL
  AND (
        (a.quota_storage_bytes > 0 AND a.storage_bytes_used::numeric / a.quota_storage_bytes >= 0.80)
     OR (a.quota_egress_bytes_mo > 0 AND a.egress_bytes_mo_used::numeric / a.quota_egress_bytes_mo >= 0.80)
     OR a.blocked_at IS NOT NULL
      );
