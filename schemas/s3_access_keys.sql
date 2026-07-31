-- PostgreSQL

CREATE TABLE s3_access_keys (
    id              bigint NOT NULL DEFAULT nextval('s3_access_keys_id_seq'::regclass),
    uuid            uuid DEFAULT gen_random_uuid(), -- [ro]
    s3_account_id   bigint NOT NULL,
    iam_account_id  bigint NOT NULL,
    iam_user_id     bigint NOT NULL,
    access_key      text NOT NULL, -- [ro]
    secret_key_enc  text NOT NULL, -- [ro]
    role            text NOT NULL DEFAULT 'readwrite'::text,
    bucket_acls     json NOT NULL DEFAULT '[]'::json,
    status          text NOT NULL DEFAULT 'active'::text,
    expires_at      timestamp with time zone,
    last_used_at    timestamp with time zone, -- [ro]
    revoked_at      timestamp with time zone, -- [ro]
    revoked_reason  text,
    tags            text[] NOT NULL DEFAULT '{}'::text[],
    created_at      timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at      timestamp with time zone,
    name            text,
    CONSTRAINT s3_access_keys_s3_account_id_foreign FOREIGN KEY (s3_account_id) REFERENCES s3_accounts(id) ON DELETE CASCADE,
    CONSTRAINT s3_access_keys_pkey PRIMARY KEY (id)
);

CREATE UNIQUE INDEX s3_access_keys_access_key_uindex ON public.s3_access_keys USING btree (access_key);
CREATE INDEX s3_access_keys_iam_account_id_idx ON public.s3_access_keys USING btree (iam_account_id);
CREATE INDEX s3_access_keys_s3_account_id_idx ON public.s3_access_keys USING btree (s3_account_id);
CREATE INDEX s3_access_keys_status_idx ON public.s3_access_keys USING btree (status);
CREATE UNIQUE INDEX s3_access_keys_uuid_uindex ON public.s3_access_keys USING btree (uuid);
