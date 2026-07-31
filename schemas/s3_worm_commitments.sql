-- PostgreSQL

CREATE TABLE s3_worm_commitments (
    id                  bigint NOT NULL DEFAULT nextval('s3_worm_commitments_id_seq'::regclass),
    uuid                uuid DEFAULT gen_random_uuid(), -- [ro]
    s3_bucket_id        bigint NOT NULL,
    s3_account_id       bigint NOT NULL,
    iam_account_id      bigint NOT NULL,
    mode                text NOT NULL,
    retention_days      integer NOT NULL,
    quota_bytes         bigint NOT NULL,
    price_per_gb_mo     numeric(10,6) NOT NULL, -- [ro][label:"Price locked at commitment creation — never changes"]
    deposit_amount      numeric(12,2) NOT NULL, -- [ro]
    committed_at        timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP, -- [ro]
    locks_until         timestamp with time zone NOT NULL, -- [ro]
    status              text NOT NULL DEFAULT 'active'::text,
    cancelled_at        timestamp with time zone,
    expired_at          timestamp with time zone,
    purged_at           timestamp with time zone,
    created_at          timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
    common_currency_id  bigint,
    CONSTRAINT s3_worm_commitments_s3_account_id_foreign FOREIGN KEY (s3_account_id) REFERENCES s3_accounts(id),
    CONSTRAINT s3_worm_commitments_s3_bucket_id_foreign FOREIGN KEY (s3_bucket_id) REFERENCES s3_buckets(id),
    CONSTRAINT s3_worm_commitments_pkey PRIMARY KEY (id),
    CONSTRAINT s3_worm_commitments_s3_bucket_id_unique UNIQUE (s3_bucket_id)
);

CREATE INDEX s3_worm_commitments_account_idx ON public.s3_worm_commitments USING btree (s3_account_id);
CREATE INDEX s3_worm_commitments_expiring_idx ON public.s3_worm_commitments USING btree (locks_until) WHERE (status = ANY (ARRAY['active'::text, 'cancelled'::text]));
CREATE UNIQUE INDEX s3_worm_commitments_uuid_uindex ON public.s3_worm_commitments USING btree (uuid);
