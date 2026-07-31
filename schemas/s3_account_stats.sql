-- PostgreSQL
-- VIEW (read-only; re-run this file with CREATE OR REPLACE VIEW whenever the SELECT needs to change)

CREATE OR REPLACE VIEW s3_account_stats AS
SELECT id AS s3_account_id,
    uuid AS s3_account_uuid,
    iam_account_id,
    slug,
    status,
    blocked_at,
    blocked_reason,
    usage_checked_at,
    quota_storage_bytes,
    storage_bytes_used,
    round(storage_bytes_used::numeric * 100.0 / NULLIF(quota_storage_bytes, 0)::numeric, 2) AS storage_pct,
    quota_egress_bytes_mo,
    egress_bytes_mo_used,
    round(egress_bytes_mo_used::numeric * 100.0 / NULLIF(quota_egress_bytes_mo, 0)::numeric, 2) AS egress_pct,
    quota_max_objects,
    object_count,
    round(object_count::numeric * 100.0 / NULLIF(quota_max_objects, 0)::numeric, 2) AS object_pct,
    quota_max_buckets,
    (( SELECT count(*) AS count
           FROM s3_buckets sb
          WHERE sb.s3_account_id = sa.id AND sb.status = 'active'::text AND sb.deleted_at IS NULL))::integer AS bucket_count,
    round((( SELECT count(*) AS count
           FROM s3_buckets sb
          WHERE sb.s3_account_id = sa.id AND sb.status = 'active'::text AND sb.deleted_at IS NULL))::numeric * 100.0 / NULLIF(quota_max_buckets, 0)::numeric, 2) AS bucket_pct,
    (( SELECT count(*) AS count
           FROM s3_access_keys sk
          WHERE sk.s3_account_id = sa.id AND sk.status = 'active'::text AND sk.deleted_at IS NULL))::integer AS active_key_count,
    (( SELECT count(*) AS count
           FROM s3_multipart_uploads mu
          WHERE mu.s3_account_id = sa.id AND mu.status = 'in_progress'::text))::integer AS in_progress_upload_count,
    (( SELECT count(*) AS count
           FROM s3_webhooks sw
          WHERE sw.s3_account_id = sa.id AND sw.status = 'paused'::text AND sw.deleted_at IS NULL))::integer AS paused_webhook_count,
    COALESCE(( SELECT bm.egress_bytes
           FROM s3_bandwidth_monthly bm
          WHERE bm.s3_account_id = sa.id AND bm.month = date_trunc('month'::text, CURRENT_DATE::timestamp with time zone)::date), 0::bigint) AS current_month_egress_bytes,
    COALESCE(( SELECT bm.ingress_bytes
           FROM s3_bandwidth_monthly bm
          WHERE bm.s3_account_id = sa.id AND bm.month = date_trunc('month'::text, CURRENT_DATE::timestamp with time zone)::date), 0::bigint) AS current_month_ingress_bytes
   FROM s3_accounts sa
  WHERE deleted_at IS NULL;
