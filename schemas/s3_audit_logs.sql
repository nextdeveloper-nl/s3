-- PostgreSQL

CREATE TABLE s3_audit_logs (
    id                     bigint NOT NULL DEFAULT nextval('s3_audit_logs_id_seq'::regclass),
    iam_account_id         bigint,
    iam_user_id            bigint,
    s3_account_id          bigint,
    s3_server_id           bigint,
    s3_access_key_id       bigint,
    s3_bucket_id           bigint,
    s3_worm_commitment_id  bigint,
    action                 text NOT NULL,
    performed_by           text NOT NULL,
    reason                 text,
    data                   json,
    performed_at           timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT s3_audit_logs_pkey PRIMARY KEY (id)
);

CREATE INDEX s3_audit_logs_iam_account_id_idx ON public.s3_audit_logs USING btree (iam_account_id);
CREATE INDEX s3_audit_logs_performed_at_idx ON public.s3_audit_logs USING btree (performed_at DESC);
CREATE INDEX s3_audit_logs_s3_account_id_idx ON public.s3_audit_logs USING btree (s3_account_id);
