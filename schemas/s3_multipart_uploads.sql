-- PostgreSQL

CREATE TABLE s3_multipart_uploads (
    id                 bigint NOT NULL DEFAULT nextval('s3_multipart_uploads_id_seq'::regclass),
    uuid               uuid DEFAULT gen_random_uuid(), -- [ro]
    s3_account_id      bigint NOT NULL,
    s3_bucket_id       bigint NOT NULL,
    iam_account_id     bigint NOT NULL,
    upload_id          text NOT NULL,
    object_key         text NOT NULL,
    initiated_at       timestamp with time zone NOT NULL,
    status             text NOT NULL DEFAULT 'in_progress'::text,
    size_bytes_so_far  bigint NOT NULL DEFAULT 0,
    part_count         integer NOT NULL DEFAULT 0,
    last_activity_at   timestamp with time zone,
    aborted_at         timestamp with time zone,
    aborted_reason     text,
    created_at         timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT s3_multipart_uploads_s3_account_id_foreign FOREIGN KEY (s3_account_id) REFERENCES s3_accounts(id) ON DELETE CASCADE,
    CONSTRAINT s3_multipart_uploads_s3_bucket_id_foreign FOREIGN KEY (s3_bucket_id) REFERENCES s3_buckets(id) ON DELETE CASCADE,
    CONSTRAINT s3_multipart_uploads_pkey PRIMARY KEY (id),
    CONSTRAINT s3_multipart_uploads_upload_id_unique UNIQUE (upload_id)
);

CREATE INDEX s3_multipart_uploads_account_status_idx ON public.s3_multipart_uploads USING btree (s3_account_id, status);
CREATE INDEX s3_multipart_uploads_stale_idx ON public.s3_multipart_uploads USING btree (initiated_at) WHERE (status = 'in_progress'::text);
CREATE UNIQUE INDEX s3_multipart_uploads_uuid_uindex ON public.s3_multipart_uploads USING btree (uuid);
