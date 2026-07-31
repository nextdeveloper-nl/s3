-- PostgreSQL

CREATE TABLE s3_accounts (
    id                        bigint NOT NULL DEFAULT nextval('s3_accounts_id_seq'::regclass),
    uuid                      uuid DEFAULT gen_random_uuid(), -- [ro]
    iam_account_id            bigint NOT NULL,
    iam_user_id               bigint NOT NULL,
    slug                      text NOT NULL,
    status                    text NOT NULL DEFAULT 'active'::text,
    quota_storage_bytes       bigint NOT NULL DEFAULT '107374182400'::bigint,
    quota_egress_bytes_mo     bigint NOT NULL DEFAULT '536870912000'::bigint,
    quota_max_buckets         integer NOT NULL DEFAULT 10,
    quota_max_objects         bigint NOT NULL DEFAULT 10000000,
    storage_bytes_used        bigint NOT NULL DEFAULT 0, -- [ro]
    egress_bytes_mo_used      bigint NOT NULL DEFAULT 0, -- [ro]
    object_count              bigint NOT NULL DEFAULT 0, -- [ro]
    usage_checked_at          timestamp with time zone, -- [ro]
    blocked_at                timestamp with time zone, -- [ro]
    blocked_reason            text, -- [ro]
    tags                      text[] NOT NULL DEFAULT '{}'::text[],
    created_at                timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at                timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at                timestamp with time zone,
    included_egress_bytes_mo  bigint NOT NULL DEFAULT 0,
    CONSTRAINT s3_accounts_pkey PRIMARY KEY (id)
);

CREATE INDEX s3_accounts_iam_account_id_idx ON public.s3_accounts USING btree (iam_account_id);
CREATE UNIQUE INDEX s3_accounts_iam_account_uindex ON public.s3_accounts USING btree (iam_account_id);
CREATE UNIQUE INDEX s3_accounts_slug_uindex ON public.s3_accounts USING btree (slug);
CREATE INDEX s3_accounts_status_idx ON public.s3_accounts USING btree (status);
CREATE UNIQUE INDEX s3_accounts_uuid_uindex ON public.s3_accounts USING btree (uuid);
