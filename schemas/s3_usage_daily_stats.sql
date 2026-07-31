-- PostgreSQL
-- VIEW (read-only; re-run this file with CREATE OR REPLACE VIEW whenever the SELECT needs to change)

CREATE OR REPLACE VIEW s3_usage_daily_stats AS
WITH date_spine AS (
         SELECT generate_series(CURRENT_DATE - '29 days'::interval, CURRENT_DATE::timestamp without time zone, '1 day'::interval)::date AS stat_date
        ), daily_agg AS (
         SELECT s3_usage_snapshots.s3_account_id,
            s3_usage_snapshots.snapshot_at::date AS stat_date,
            avg(s3_usage_snapshots.storage_bytes)::bigint AS storage_bytes,
            avg(s3_usage_snapshots.object_count)::bigint AS object_count
           FROM s3_usage_snapshots
          WHERE s3_usage_snapshots.snapshot_at >= (CURRENT_DATE - '29 days'::interval)
          GROUP BY s3_usage_snapshots.s3_account_id, (s3_usage_snapshots.snapshot_at::date)
        )
 SELECT ds.stat_date,
    sa.id AS s3_account_id,
    sa.uuid AS s3_account_uuid,
    sa.iam_account_id,
    sa.slug,
    COALESCE(da.storage_bytes, 0::bigint) AS storage_bytes,
    COALESCE(da.object_count, 0::bigint) AS object_count,
    round(COALESCE(da.storage_bytes, 0::bigint)::numeric / 1073741824.0, 3) AS storage_gb
   FROM date_spine ds
     CROSS JOIN s3_accounts sa
     LEFT JOIN daily_agg da ON da.s3_account_id = sa.id AND da.stat_date = ds.stat_date
  WHERE sa.deleted_at IS NULL
  ORDER BY ds.stat_date, sa.id;
