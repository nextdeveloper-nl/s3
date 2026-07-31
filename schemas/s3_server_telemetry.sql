-- PostgreSQL

CREATE TABLE s3_server_telemetry (
    id                    bigint NOT NULL DEFAULT nextval('s3_server_telemetry_id_seq'::regclass),
    s3_server_id          bigint NOT NULL,
    reported_at           timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
    master_reachable      boolean,
    volume_count          integer,
    volumes_degraded      integer,
    capacity_bytes_total  bigint,
    capacity_bytes_used   bigint,
    capacity_pct          numeric(5,2),
    cpu                   json,
    ram                   json,
    disk                  json,
    network               json,
    traffic               json,
    CONSTRAINT s3_server_telemetry_s3_server_id_foreign FOREIGN KEY (s3_server_id) REFERENCES s3_servers(id) ON DELETE CASCADE,
    CONSTRAINT s3_server_telemetry_pkey PRIMARY KEY (id)
);

CREATE INDEX s3_server_telemetry_server_time_idx ON public.s3_server_telemetry USING btree (s3_server_id, reported_at DESC);
