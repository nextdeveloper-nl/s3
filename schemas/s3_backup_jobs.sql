-- PostgreSQL
-- A named backup job belonging to one s3_backup_agent. Either backs up raw
-- file/folder paths directly (job_type='files'), or runs a script first and
-- backs up that script's output (job_type='script', e.g. a mysqldump wrapper).
-- Both feed the same output path into the agent's embedded Kopia engine.

CREATE TABLE s3_backup_jobs (
    id                   bigserial    PRIMARY KEY,
    uuid                 uuid         NOT NULL DEFAULT gen_random_uuid(),

    s3_backup_agent_id   bigint       NOT NULL REFERENCES s3_backup_agents(id),
    iam_account_id       bigint       NOT NULL REFERENCES iam_accounts(id),
    iam_user_id          bigint       NOT NULL REFERENCES iam_users(id),

    name                 text         NOT NULL,
    job_type             text         NOT NULL, -- files | script

    source_paths         text[],      -- files job: paths to snapshot; script job: paths to
                                       -- clean up / where the script is expected to write output
    pre_script           text,        -- script job: the script body run before snapshotting
    script_timeout_s     integer      NOT NULL DEFAULT 900,

    schedule             text         NOT NULL, -- cron expression, evaluated agent-side
                                                 -- (not platform-triggered — the machine may
                                                 -- be offline/asleep on a platform-driven tick)

    keep_last_n          integer,     -- retention: keep at most N snapshots
    keep_for_days        integer,     -- retention: keep snapshots newer than N days

    is_enabled           boolean      NOT NULL DEFAULT true,
    bandwidth_limit_mbps integer,

    -- Routes the target bucket through the existing s3_worm_commitments /
    -- Object Lock mechanism for ransomware-resistant immutability.
    object_lock_enabled  boolean      NOT NULL DEFAULT false,

    status               text         NOT NULL DEFAULT 'active',

    created_at           timestamptz  NOT NULL DEFAULT now(),
    updated_at           timestamptz  NOT NULL DEFAULT now(),
    deleted_at           timestamptz
);

CREATE UNIQUE INDEX ON s3_backup_jobs (uuid);
CREATE        INDEX ON s3_backup_jobs (s3_backup_agent_id);
CREATE        INDEX ON s3_backup_jobs (iam_account_id);
CREATE        INDEX ON s3_backup_jobs (is_enabled) WHERE is_enabled = true;
