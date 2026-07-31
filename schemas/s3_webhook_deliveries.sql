-- PostgreSQL

CREATE TABLE s3_webhook_deliveries (
    id             bigint NOT NULL DEFAULT nextval('s3_webhook_deliveries_id_seq'::regclass),
    s3_webhook_id  bigint NOT NULL,
    s3_account_id  bigint NOT NULL,
    event_type     text NOT NULL,
    object_key     text NOT NULL,
    payload        json NOT NULL,
    status_code    integer,
    attempt        integer NOT NULL DEFAULT 1,
    next_retry_at  timestamp with time zone,
    delivered_at   timestamp with time zone,
    created_at     timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT s3_webhook_deliveries_s3_webhook_id_foreign FOREIGN KEY (s3_webhook_id) REFERENCES s3_webhooks(id) ON DELETE CASCADE,
    CONSTRAINT s3_webhook_deliveries_pkey PRIMARY KEY (id)
);

CREATE INDEX s3_webhook_deliveries_account_idx ON public.s3_webhook_deliveries USING btree (s3_account_id);
CREATE INDEX s3_webhook_deliveries_retry_idx ON public.s3_webhook_deliveries USING btree (next_retry_at) WHERE ((next_retry_at IS NOT NULL) AND (delivered_at IS NULL));
CREATE INDEX s3_webhook_deliveries_webhook_time_idx ON public.s3_webhook_deliveries USING btree (s3_webhook_id, created_at DESC);
