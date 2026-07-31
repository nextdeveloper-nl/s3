-- PostgreSQL

CREATE TABLE s3_webhooks (
    id              bigint NOT NULL DEFAULT nextval('s3_webhooks_id_seq'::regclass),
    uuid            uuid DEFAULT gen_random_uuid(), -- [ro]
    s3_account_id   bigint NOT NULL,
    s3_bucket_id    bigint,
    iam_account_id  bigint NOT NULL,
    iam_user_id     bigint NOT NULL,
    endpoint_url    text NOT NULL,
    events          text[] NOT NULL,
    secret          text, -- [ro]
    status          text NOT NULL DEFAULT 'active'::text,
    failure_count   integer NOT NULL DEFAULT 0, -- [ro]
    paused_at       timestamp with time zone, -- [ro]
    tags            text[] NOT NULL DEFAULT '{}'::text[],
    created_at      timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at      timestamp with time zone,
    CONSTRAINT s3_webhooks_s3_account_id_foreign FOREIGN KEY (s3_account_id) REFERENCES s3_accounts(id) ON DELETE CASCADE,
    CONSTRAINT s3_webhooks_s3_bucket_id_foreign FOREIGN KEY (s3_bucket_id) REFERENCES s3_buckets(id) ON DELETE CASCADE,
    CONSTRAINT s3_webhooks_pkey PRIMARY KEY (id)
);

CREATE INDEX s3_webhooks_s3_account_id_idx ON public.s3_webhooks USING btree (s3_account_id);
CREATE INDEX s3_webhooks_s3_bucket_id_idx ON public.s3_webhooks USING btree (s3_bucket_id);
CREATE INDEX s3_webhooks_status_idx ON public.s3_webhooks USING btree (status) WHERE (status = 'active'::text);
CREATE UNIQUE INDEX s3_webhooks_uuid_uindex ON public.s3_webhooks USING btree (uuid);
