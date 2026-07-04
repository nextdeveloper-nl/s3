-- PostgreSQL
-- One row per execution of an s3_backup_job. This is the source of truth for
-- "did the backup actually work" — never inferred from a heartbeat alone.
-- Consumed by BackupJobRunsService::getMissedJobs() (the s3:backup-agents-check-missed
-- cron) to detect silent failures: a job with no recent 'completed' row here
-- gets escalated even if the agent is still heartbeating fine.

CREATE TABLE s3_backup_job_runs (
    id                 bigserial    PRIMARY KEY,
    uuid               uuid         NOT NULL DEFAULT gen_random_uuid(),

    s3_backup_job_id   bigint       NOT NULL REFERENCES s3_backup_jobs(id),

    -- running   = agent accepted run_job_now / schedule fired, no result yet
    -- completed = agent's `result` message reported status=completed
    -- failed    = agent's `result` message reported status=failed|rejected,
    --             or the pre_script exited non-zero / produced empty output
    -- missed    = set by the check-missed cron when no run was ever recorded
    --             for an expected schedule slot
    status             text         NOT NULL DEFAULT 'running',

    started_at         timestamptz,
    finished_at        timestamptz,

    bytes_uploaded     bigint,
    bytes_deduped      bigint,
    kopia_snapshot_id  text,
    error              text,

    triggered_by       text         NOT NULL DEFAULT 'schedule', -- schedule | manual | command

    created_at         timestamptz  NOT NULL DEFAULT now(),
    updated_at         timestamptz  NOT NULL DEFAULT now()
);

CREATE UNIQUE INDEX ON s3_backup_job_runs (uuid);
CREATE        INDEX ON s3_backup_job_runs (s3_backup_job_id, status);
CREATE        INDEX ON s3_backup_job_runs (s3_backup_job_id, created_at DESC);
