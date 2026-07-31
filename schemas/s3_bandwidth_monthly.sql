-- PostgreSQL

CREATE TABLE s3_bandwidth_monthly (
    id              bigint NOT NULL DEFAULT nextval('s3_bandwidth_monthly_id_seq'::regclass),
    s3_account_id   bigint NOT NULL,
    iam_account_id  bigint NOT NULL,
    month           date NOT NULL,
    egress_bytes    bigint NOT NULL DEFAULT 0,
    ingress_bytes   bigint NOT NULL DEFAULT 0,
    updated_at      timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT s3_bandwidth_monthly_s3_account_id_foreign FOREIGN KEY (s3_account_id) REFERENCES s3_accounts(id) ON DELETE CASCADE,
    CONSTRAINT s3_bandwidth_monthly_pkey PRIMARY KEY (id),
    CONSTRAINT s3_bandwidth_monthly_account_month_unique UNIQUE (s3_account_id, month)
);

CREATE INDEX s3_bandwidth_monthly_account_idx ON public.s3_bandwidth_monthly USING btree (s3_account_id);
