-- PostgreSQL
-- Member table for agent_type = 'backup' in the shared NATS agent protocol
-- (see docs/backup.agent/protocol.md). One row per registered backup.agent
-- install on a customer machine (any OS), not tied to the IAAS VM fleet.

CREATE TABLE s3_backup_agents (
    id                  bigserial    PRIMARY KEY,
    uuid                uuid         NOT NULL DEFAULT gen_random_uuid(),

    iam_account_id      bigint       NOT NULL REFERENCES iam_accounts(id),
    iam_user_id         bigint       NOT NULL REFERENCES iam_users(id),

    -- Reported by the agent at registration time
    hostname            text,
    os                  text,        -- windows | linux | darwin
    arch                text,        -- amd64 | arm64 | ...
    machine_fingerprint text,

    -- NATS credential (password for nats.UserInfo(uuid, agent_api_key)); null once revoked
    agent_api_key       text,
    agent_version       text,

    -- pending  = registration token issued, agent has not called /register yet
    -- active   = registered, agent_api_key live, may connect to NATS
    -- revoked  = agent_api_key cleared, rejected on next NATS reconnect
    status              text         NOT NULL DEFAULT 'pending',

    -- One-time bootstrap token, consumed by BackupAgentsService::register()
    registration_token             text,
    registration_token_expires_at  timestamptz,

    -- Dedicated bucket provisioned for this agent at registration time
    s3_bucket_id        bigint       REFERENCES s3_buckets(id),

    last_seen_at        timestamptz, -- touched on every heartbeat
    health              text,

    created_at          timestamptz  NOT NULL DEFAULT now(),
    updated_at          timestamptz  NOT NULL DEFAULT now(),
    deleted_at          timestamptz
);

CREATE UNIQUE INDEX ON s3_backup_agents (uuid);
CREATE UNIQUE INDEX ON s3_backup_agents (agent_api_key) WHERE agent_api_key IS NOT NULL;
CREATE UNIQUE INDEX ON s3_backup_agents (registration_token) WHERE registration_token IS NOT NULL;
CREATE        INDEX ON s3_backup_agents (iam_account_id);
CREATE        INDEX ON s3_backup_agents (status);
