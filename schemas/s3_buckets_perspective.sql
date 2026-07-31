-- PostgreSQL
-- VIEW (read-only; re-run this file with CREATE OR REPLACE VIEW whenever the SELECT needs to change)

CREATE OR REPLACE VIEW s3_buckets_perspective AS
SELECT id,
    uuid,
    s3_account_id,
    s3_server_id,
    iam_account_id,
    iam_user_id,
    name,
    replication_factor,
    lifecycle_rules,
    versioning,
    mfa_delete,
    object_lock_enabled,
    object_lock_mode,
    object_lock_days,
    object_count,
    size_bytes,
    round(size_bytes::numeric / 1073741824.0, 3) AS size_gb,
    replica_health,
    status,
    tags,
    created_at,
    updated_at,
    deleted_at,
    ( SELECT sa.slug
           FROM s3_accounts sa
          WHERE sa.id = sb.s3_account_id) AS s3_account_slug,
    ( SELECT ss.hostname
           FROM s3_servers ss
          WHERE ss.id = sb.s3_server_id) AS s3_server_hostname,
    ( SELECT ss.health
           FROM s3_servers ss
          WHERE ss.id = sb.s3_server_id) AS s3_server_health,
    ( SELECT wc.status
           FROM s3_worm_commitments wc
          WHERE wc.s3_bucket_id = sb.id) AS worm_status,
    ( SELECT wc.locks_until
           FROM s3_worm_commitments wc
          WHERE wc.s3_bucket_id = sb.id) AS worm_locks_until,
    (( SELECT count(*) AS count
           FROM s3_webhooks sw
          WHERE sw.s3_bucket_id = sb.id AND sw.status = 'active'::text AND sw.deleted_at IS NULL))::integer AS active_webhook_count,
    (( SELECT count(*) AS count
           FROM s3_multipart_uploads mu
          WHERE mu.s3_bucket_id = sb.id AND mu.status = 'in_progress'::text))::integer AS in_progress_uploads
   FROM s3_buckets sb
  WHERE deleted_at IS NULL;
