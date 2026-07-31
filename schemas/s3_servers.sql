-- PostgreSQL

CREATE TABLE s3_servers (
    id                      bigint NOT NULL DEFAULT nextval('s3_servers_id_seq'::regclass),
    uuid                    uuid DEFAULT gen_random_uuid(), -- [ro]
    iam_account_id          bigint,
    iam_user_id             bigint,
    hostname                text NOT NULL,
    name                    text,
    agent_api_key           text NOT NULL, -- [ro]
    agent_version           text, -- [ro]
    seaweedfs_version       text, -- [ro]
    agent_status            text NOT NULL DEFAULT 'unknown'::text, -- [ro]
    agent_last_seen_at      timestamp with time zone, -- [ro]
    agent_connected_at      timestamp with time zone, -- [ro]
    health                  text NOT NULL DEFAULT 'unknown'::text, -- [ro]
    health_summary          text, -- [ro]
    components              json NOT NULL DEFAULT '{}'::json, -- [ro]
    tags                    text[] NOT NULL DEFAULT '{}'::text[],
    created_at              timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              timestamp with time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at              timestamp with time zone,
    price_per_gb            numeric(10,4),
    common_currency_id      bigint,
    is_public               boolean NOT NULL DEFAULT true,
    marketplace_product_id  bigint,
    CONSTRAINT s3_servers_marketplace_product_id_fkey FOREIGN KEY (marketplace_product_id) REFERENCES marketplace_products(id),
    CONSTRAINT s3_servers_pkey PRIMARY KEY (id)
);

CREATE INDEX s3_servers_agent_status_idx ON public.s3_servers USING btree (agent_status);
CREATE INDEX s3_servers_health_idx ON public.s3_servers USING btree (health);
CREATE UNIQUE INDEX s3_servers_hostname_uindex ON public.s3_servers USING btree (hostname);
CREATE INDEX s3_servers_is_public_idx ON public.s3_servers USING btree (is_public) WHERE (is_public = true);
CREATE INDEX s3_servers_marketplace_product_id_idx ON public.s3_servers USING btree (marketplace_product_id);
CREATE UNIQUE INDEX s3_servers_uuid_uindex ON public.s3_servers USING btree (uuid);
