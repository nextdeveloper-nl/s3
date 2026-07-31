-- PostgreSQL
-- VIEW (read-only; re-run this file with CREATE OR REPLACE VIEW whenever the SELECT needs to change)

CREATE OR REPLACE VIEW s3_servers_perspective AS
SELECT id,
    uuid,
    iam_account_id,
    iam_user_id,
    hostname,
    name,
    agent_version,
    seaweedfs_version,
    agent_status,
    agent_last_seen_at,
    agent_connected_at,
    health,
    health_summary,
    components,
    tags,
    is_public,
    created_at,
    updated_at,
    deleted_at,
    ((components -> 'master'::text) ->> 'reachable'::text)::boolean AS master_reachable,
    ((components -> 'master'::text) ->> 'is_leader'::text)::boolean AS master_is_leader,
    ((components -> 'master'::text) ->> 'peers'::text)::integer AS master_peers,
    ((components -> 'volume'::text) ->> 'reachable'::text)::boolean AS volume_reachable,
    ((components -> 'volume'::text) ->> 'total_volumes'::text)::integer AS volume_total,
    ((components -> 'volume'::text) ->> 'volumes_writable'::text)::integer AS volumes_writable,
    ((components -> 'volume'::text) ->> 'volumes_degraded'::text)::integer AS volumes_degraded,
    ((components -> 'volume'::text) ->> 'volumes_readonly'::text)::integer AS volumes_readonly,
    ((components -> 'volume'::text) ->> 'capacity_bytes_total'::text)::bigint AS capacity_bytes_total,
    ((components -> 'volume'::text) ->> 'capacity_bytes_used'::text)::bigint AS capacity_bytes_used,
    ((components -> 'volume'::text) ->> 'capacity_pct'::text)::numeric AS capacity_pct,
    round((((components -> 'volume'::text) ->> 'capacity_bytes_total'::text)::bigint)::numeric / 1073741824.0, 2) AS capacity_gb_total,
    round((((components -> 'volume'::text) ->> 'capacity_bytes_used'::text)::bigint)::numeric / 1073741824.0, 2) AS capacity_gb_used,
    ((components -> 'filer'::text) ->> 'reachable'::text)::boolean AS filer_reachable,
    ((components -> 's3'::text) ->> 'reachable'::text)::boolean AS s3_reachable,
    ((components -> 's3'::text) ->> 'bucket_count'::text)::integer AS s3_bucket_count,
    (( SELECT count(*) AS count
           FROM s3_buckets sb
          WHERE sb.s3_server_id = ss.id AND sb.deleted_at IS NULL))::integer AS hosted_bucket_count,
    (( SELECT count(DISTINCT sb.s3_account_id) AS count
           FROM s3_buckets sb
          WHERE sb.s3_server_id = ss.id AND sb.deleted_at IS NULL))::integer AS hosted_account_count
   FROM s3_servers ss
  WHERE deleted_at IS NULL;
