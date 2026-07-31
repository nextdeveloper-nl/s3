-- PostgreSQL

CREATE TABLE s3_usage_snapshots (
    id              bigint NOT NULL DEFAULT nextval('s3_usage_snapshots_id_seq'::regclass),
    s3_account_id   bigint NOT NULL,
    iam_account_id  bigint NOT NULL,
    snapshot_at     timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
    storage_bytes   bigint NOT NULL,
    object_count    bigint NOT NULL,
    CONSTRAINT s3_usage_snapshots_s3_account_id_foreign FOREIGN KEY (s3_account_id) REFERENCES s3_accounts(id) ON DELETE CASCADE,
    CONSTRAINT s3_usage_snapshots_pkey PRIMARY KEY (id)
);

CREATE INDEX s3_usage_snapshots_account_time_idx ON public.s3_usage_snapshots USING btree (s3_account_id, snapshot_at DESC);
CREATE INDEX s3_usage_snapshots_iam_account_idx ON public.s3_usage_snapshots USING btree (iam_account_id);
