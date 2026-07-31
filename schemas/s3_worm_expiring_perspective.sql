-- PostgreSQL
-- VIEW (read-only; re-run this file with CREATE OR REPLACE VIEW whenever the SELECT needs to change)

CREATE OR REPLACE VIEW s3_worm_expiring_perspective AS
SELECT id AS s3_worm_commitment_id,
    uuid AS s3_worm_commitment_uuid,
    s3_bucket_id,
    s3_account_id,
    iam_account_id,
    mode,
    retention_days,
    quota_bytes,
    deposit_amount,
    price_per_gb_mo,
    committed_at,
    locks_until,
    status,
    cancelled_at,
    ( SELECT sa.slug
           FROM s3_accounts sa
          WHERE sa.id = wc.s3_account_id) AS s3_account_slug,
    ( SELECT sb.name
           FROM s3_buckets sb
          WHERE sb.id = wc.s3_bucket_id) AS bucket_name,
    EXTRACT(day FROM locks_until - CURRENT_TIMESTAMP)::integer AS days_until_expiry,
    locks_until < CURRENT_TIMESTAMP AS is_expired,
        CASE
            WHEN status = ANY (ARRAY['active'::text, 'cancelled'::text]) THEN round(deposit_amount * GREATEST(0::numeric, EXTRACT(day FROM locks_until - CURRENT_TIMESTAMP)) / NULLIF(retention_days, 0)::numeric, 2)
            ELSE NULL::numeric
        END AS deposit_refund_estimate
   FROM s3_worm_commitments wc
  WHERE (status = ANY (ARRAY['active'::text, 'cancelled'::text, 'expired'::text])) AND locks_until <= (CURRENT_TIMESTAMP + '30 days'::interval)
  ORDER BY locks_until;
