-- PostgreSQL
-- One row per customer-initiated restore of backup data back to a real
-- destination on the agent's machine. Distinct from the internal
-- verify_snapshot command (BackupAgentCommandService::verifySnapshot), which
-- only restores to a throwaway temp path to checksum-verify a Kopia snapshot —
-- this table tracks restores the customer actually asked for, where the data
-- landed, mandatorily checksum-verified same as verify_snapshot. Works for
-- both backup engines (s3_backup_jobs.engine).

CREATE TABLE s3_restore_jobs (
    id                     bigserial    PRIMARY KEY,
    uuid                   uuid         NOT NULL DEFAULT gen_random_uuid(),

    s3_backup_job_id       bigint       NOT NULL REFERENCES s3_backup_jobs(id),

    -- Required for engine='kopia' (picks the snapshot to extract from).
    -- Must be NULL for engine='rsync' — rsync has no point-in-time snapshot,
    -- a restore always pulls current bucket state for restore_paths.
    s3_backup_job_run_id   bigint       REFERENCES s3_backup_job_runs(id),

    iam_account_id         bigint       NOT NULL,
    iam_user_id            bigint       NOT NULL,

    destination_path       text         NOT NULL,

    -- Relative paths within the backup to restore. NULL/empty = restore
    -- everything. Most requests are expected to set this — customers usually
    -- want one specific file (e.g. a DB dump) back, not the whole snapshot.
    restore_paths          text[],

    -- pending   = row created, command not yet dispatched (should be instantaneous)
    -- running   = agent accepted restore_snapshot, no result yet
    -- completed = agent's `result` reported status=completed AND checksum verified
    -- failed    = agent's `result` reported failed/rejected, or checksum verification failed
    status                 text         NOT NULL DEFAULT 'pending',

    verified               boolean,
    bytes_restored         bigint,
    error                  text,

    triggered_by           text         NOT NULL DEFAULT 'manual',

    started_at             timestamptz,
    finished_at            timestamptz,

    created_at             timestamptz  NOT NULL DEFAULT now(),
    updated_at             timestamptz  NOT NULL DEFAULT now()
);

CREATE UNIQUE INDEX ON s3_restore_jobs (uuid);
CREATE        INDEX ON s3_restore_jobs (s3_backup_job_id);
CREATE        INDEX ON s3_restore_jobs (s3_backup_job_run_id);
CREATE        INDEX ON s3_restore_jobs (iam_account_id);
CREATE        INDEX ON s3_restore_jobs (status);
