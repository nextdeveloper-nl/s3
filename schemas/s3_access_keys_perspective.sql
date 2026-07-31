-- PostgreSQL
-- VIEW (read-only; re-run this file with CREATE OR REPLACE VIEW whenever the SELECT needs to change)

CREATE OR REPLACE VIEW s3_access_keys_perspective AS
SELECT id,
    uuid,
    s3_account_id,
    iam_account_id,
    iam_user_id,
    name,
    access_key,
    role,
    bucket_acls,
    status,
    expires_at,
    last_used_at,
    revoked_at,
    revoked_reason,
    tags,
    created_at,
    updated_at,
    deleted_at,
    ( SELECT sa.slug
           FROM s3_accounts sa
          WHERE sa.id = sk.s3_account_id) AS s3_account_slug,
    expires_at IS NOT NULL AND expires_at < CURRENT_TIMESTAMP AS is_expired,
        CASE
            WHEN expires_at IS NULL THEN NULL::integer
            ELSE EXTRACT(day FROM expires_at - CURRENT_TIMESTAMP)::integer
        END AS expires_in_days,
    expires_at IS NOT NULL AND expires_at > CURRENT_TIMESTAMP AND expires_at < (CURRENT_TIMESTAMP + '7 days'::interval) AS is_expiring_soon
   FROM s3_access_keys sk
  WHERE deleted_at IS NULL;
