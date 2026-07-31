-- PostgreSQL

CREATE TABLE s3_notifications_sent (
    id              bigint NOT NULL DEFAULT nextval('s3_notifications_sent_id_seq'::regclass),
    s3_account_id   bigint NOT NULL,
    iam_account_id  bigint NOT NULL,
    notification    text NOT NULL,
    month           date NOT NULL,
    sent_at         timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT s3_notifications_sent_s3_account_id_foreign FOREIGN KEY (s3_account_id) REFERENCES s3_accounts(id) ON DELETE CASCADE,
    CONSTRAINT s3_notifications_sent_pkey PRIMARY KEY (id),
    CONSTRAINT s3_notifications_sent_unique UNIQUE (s3_account_id, notification, month)
);

CREATE INDEX s3_notifications_sent_account_idx ON public.s3_notifications_sent USING btree (s3_account_id);
