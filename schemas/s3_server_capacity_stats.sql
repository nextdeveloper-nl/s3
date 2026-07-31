-- PostgreSQL
-- VIEW (read-only; re-run this file with CREATE OR REPLACE VIEW whenever the SELECT needs to change)

CREATE OR REPLACE VIEW s3_server_capacity_stats AS
WITH latest_telemetry AS (
         SELECT DISTINCT ON (s3_server_telemetry.s3_server_id) s3_server_telemetry.s3_server_id,
            s3_server_telemetry.reported_at AS latest_reported_at,
            s3_server_telemetry.master_reachable,
            s3_server_telemetry.volume_count,
            s3_server_telemetry.volumes_degraded,
            s3_server_telemetry.capacity_bytes_total,
            s3_server_telemetry.capacity_bytes_used,
            s3_server_telemetry.capacity_pct
           FROM s3_server_telemetry
          ORDER BY s3_server_telemetry.s3_server_id, s3_server_telemetry.reported_at DESC
        )
 SELECT ss.id AS s3_server_id,
    ss.uuid AS s3_server_uuid,
    ss.hostname,
    ss.name,
    ss.health,
    ss.agent_status,
    ss.agent_last_seen_at,
    lt.latest_reported_at,
    lt.master_reachable,
    lt.volume_count,
    lt.volumes_degraded,
    lt.capacity_bytes_total,
    lt.capacity_bytes_used,
    lt.capacity_pct,
    round(lt.capacity_bytes_total::numeric / 1073741824.0, 2) AS capacity_gb_total,
    round(lt.capacity_bytes_used::numeric / 1073741824.0, 2) AS capacity_gb_used,
    (( SELECT count(*) AS count
           FROM s3_buckets sb
          WHERE sb.s3_server_id = ss.id AND sb.deleted_at IS NULL))::integer AS hosted_bucket_count,
    (( SELECT count(DISTINCT sb.s3_account_id) AS count
           FROM s3_buckets sb
          WHERE sb.s3_server_id = ss.id AND sb.deleted_at IS NULL))::integer AS hosted_account_count,
    round(EXTRACT(epoch FROM CURRENT_TIMESTAMP - lt.latest_reported_at) / 60::numeric, 1) AS minutes_since_last_report
   FROM s3_servers ss
     LEFT JOIN latest_telemetry lt ON lt.s3_server_id = ss.id
  WHERE ss.deleted_at IS NULL;
