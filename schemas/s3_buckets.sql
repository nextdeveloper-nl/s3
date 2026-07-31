-- PostgreSQL

CREATE TABLE s3_buckets (
    id                       bigint NOT NULL DEFAULT nextval('s3_buckets_id_seq'::regclass),
    uuid                     uuid DEFAULT gen_random_uuid(), -- [ro]
    s3_account_id            bigint NOT NULL,
    s3_server_id             bigint NOT NULL,
    iam_account_id           bigint NOT NULL,
    iam_user_id              bigint NOT NULL,
    name                     text NOT NULL,
    replication_factor       integer NOT NULL DEFAULT 1,
    lifecycle_rules          json NOT NULL DEFAULT '[]'::json,
    versioning               text NOT NULL DEFAULT 'Suspended'::text,
    mfa_delete               boolean NOT NULL DEFAULT false,
    object_lock_enabled      boolean NOT NULL DEFAULT false, -- [ro][label:"Cannot be changed after bucket creation"]
    object_lock_mode         text, -- [ro][label:"Cannot be changed after bucket creation"]
    object_lock_days         integer, -- [ro][label:"Cannot be changed after bucket creation"]
    object_count             bigint, -- [ro]
    size_bytes               bigint, -- [ro]
    replica_health           text, -- [ro]
    status                   text NOT NULL DEFAULT 'active'::text,
    tags                     text[] NOT NULL DEFAULT '{}'::text[],
    created_at               timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at               timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at               timestamp with time zone,
    is_object_audit_enabled  boolean,
    bucket_name              text,
    CONSTRAINT s3_buckets_object_lock_check CHECK ((((object_lock_enabled = false) AND (object_lock_mode IS NULL) AND (object_lock_days IS NULL)) OR ((object_lock_enabled = true) AND (object_lock_mode = ANY (ARRAY['GOVERNANCE'::text, 'COMPLIANCE'::text])) AND (object_lock_days > 0)))),
    CONSTRAINT s3_buckets_s3_account_id_foreign FOREIGN KEY (s3_account_id) REFERENCES s3_accounts(id) ON DELETE CASCADE,
    CONSTRAINT s3_buckets_s3_server_id_foreign FOREIGN KEY (s3_server_id) REFERENCES s3_servers(id),
    CONSTRAINT s3_buckets_pkey PRIMARY KEY (id)
);

CREATE INDEX s3_buckets_iam_account_id_idx ON public.s3_buckets USING btree (iam_account_id);
CREATE INDEX s3_buckets_object_lock_idx ON public.s3_buckets USING btree (object_lock_enabled) WHERE (object_lock_enabled = true);
CREATE INDEX s3_buckets_s3_account_id_idx ON public.s3_buckets USING btree (s3_account_id);
CREATE INDEX s3_buckets_s3_server_id_idx ON public.s3_buckets USING btree (s3_server_id);
CREATE UNIQUE INDEX s3_buckets_server_name_uindex ON public.s3_buckets USING btree (s3_server_id, name);
CREATE UNIQUE INDEX s3_buckets_uuid_uindex ON public.s3_buckets USING btree (uuid);
