# backup.agent — Database

Four tables, all in the S3 module's schema (`s3_` prefix). DDL lives in
`schemas/s3_backup_agents.sql`, `schemas/s3_backup_jobs.sql`,
`schemas/s3_backup_job_runs.sql`, `schemas/s3_restore_jobs.sql` — the same DDL
is also checked into the authoritative DataGrip project at
`~/DataGripProjects/LEOv4/PostgreSQL/Tables/S3/CreateTable.sql`.

## `s3_backup_agents`

The "member table" for `agent_type = backup` in the generic NATS agent protocol —
`NatsAuthCalloutService::AGENT_TABLES` maps this table to `backup` exactly like
`s3_servers` maps to `s3`.

| Column | Notes |
|---|---|
| `uuid` | Exposed externally; this is the agent's identity in every NATS subject and envelope (`agent_uuid`). |
| `agent_api_key` | The NATS credential (password in `nats.UserInfo(uuid, key)`). `null` while `pending`; cleared on revoke. |
| `status` | `pending` → `active` → `revoked`. See lifecycle below. |
| `registration_token` / `registration_token_expires_at` | One-time bootstrap token; cleared the moment `register()` succeeds. |
| `s3_bucket_id` | FK to `s3_buckets` — a bucket the customer already owns, required when the registration token is issued (`BackupAgentsService::create()`). Never provisioned by us — see `registration.md`. |
| `hostname`, `os`, `arch`, `machine_fingerprint`, `agent_version` | Reported by the agent itself at register()/heartbeat time — not customer-editable (see the `[ro]` column comments in the DataGrip schema). |
| `last_seen_at`, `health` | Updated on every heartbeat/telemetry/job_run/result message. |

### Status lifecycle

```
pending  (registration token issued, agent has not called register() yet)
   │
   ▼ BackupAgentsService::register() — token consumed
active   (agent_api_key live, may connect to NATS)
   │
   ▼ BackupAgentsService::revoke()
revoked  (agent_api_key cleared — rejected on next NATS reconnect)
```

## `s3_backup_jobs`

A named backup job belonging to one agent.

| Column | Notes |
|---|---|
| `s3_bucket_id` | **Destination bucket for this job's snapshots.** Immutable after creation. Resolved in `BackupJobsService::resolveBucketForJob()`: an explicit value is validated to belong to the same account (and, when `object_lock_enabled = true`, must already have Object Lock enabled on the bucket itself); otherwise defaults to the agent's own `s3_backup_agents.s3_bucket_id`. Never auto-provisioned — see below. |
| `job_type` | `files` (snapshot `source_paths` directly) or `script` (run `pre_script` first, snapshot its output). |
| `engine` | `rsync` (default) or `kopia`. `rsync` mirrors the output path directly into the bucket as a plain, browsable 1:1 copy — no point-in-time snapshot, a restore always means "current bucket state." `kopia` is the dedup/content-addressable engine the rest of this doc otherwise describes, where each run produces a distinct restorable snapshot (`s3_backup_job_runs.kopia_snapshot_id`). Immutable after creation, stripped in `BackupJobsService::update()` like `job_type`. Drives which restore path a job supports — see `s3_restore_jobs` below. |
| `source_paths` | `text[]` — paths to snapshot (files job) or paths the script is expected to write to (script job). |
| `pre_script` | Required (enforced in `BackupJobsService::assertScriptHasPreScript()`) when `job_type = script`. |
| `schedule` | Cron expression, evaluated **agent-side** — see `docs/backup.agent/overview.md` for why. |
| `keep_last_n` / `keep_for_days` | Retention — same shape as `iaas_backup_retention_policies` in the (unrelated) hypervisor-level VM backup system. |
| `object_lock_enabled` | States the job's intent that its bucket must have Object Lock enabled — validated, not enforced, at create time: `resolveBucketForJob()` rejects the job if the resolved bucket (explicit or the agent's default) doesn't already have `s3_buckets.object_lock_enabled = true`. We never create a bucket to satisfy this; the customer must point the job at an existing WORM bucket. Immutable after creation, stripped in `BackupJobsService::update()`. |
| `s3_backup_agent_id`, `s3_bucket_id`, `job_type`, `engine` | All four immutable after creation — a job doesn't change which machine it lives on or where its existing snapshots already are, switching `files`↔`script` changes what `source_paths` even means, and switching engines would orphan every existing run's `kopia_snapshot_id` (or lack thereof). |

Any create/update/delete triggers a `full_sync` to the owning agent
(`BackupJobsService::syncAgent()`) — jobs are plain config, so a full resync on
every change is simpler than one NATS operation per field.

## `s3_backup_job_runs`

One row per execution. This is the source of truth for "did the backup actually
work" — never inferred from a heartbeat. Two ways a row gets created:

1. **Manual / `run_job_now`** — `BackupAgentCommandService::runJobNow()` creates
   the row as `running` *before* the command is even sent, then
   `BackupAgentEventService::handleResult()` closes it out
   (`completed`/`failed`) when the agent's `result` arrives.
2. **Agent-scheduled** — the agent's own cron fires the job; there's no
   platform-initiated "start," so the agent reports the finished outcome in one
   `job_run` event (see `protocol.md`), and
   `BackupJobRunsService::recordRun()` creates the row directly using the
   agent's own generated uuid — a redelivered event is naturally idempotent
   rather than producing a duplicate row with a fresh id.

### Status lifecycle

```
running    (run_job_now sent; no result yet)
  │
  ├──► completed  (result/job_run status=completed)
  └──► failed     (result status=failed|rejected, or job_run status=failed)

missed     (no row exists at all for an expected schedule slot — see below)
```

`missed` is never written by the agent — it's a query result, not a stored status.
`BackupJobRunsService::getMissedJobs()` uses
`dragonmantank/cron-expression` (already a Laravel dependency via
`Illuminate\Console\Scheduling`) to compute each enabled job's last expected run
time, and flags any job with no `completed` run since then, past a configurable
grace window (`s3.backup.missed_grace_minutes`, default 30). Consumed by the
`s3:backup-agents-check-missed` cron (every 15 minutes,
`CheckMissedBackupsJob` → `BackupJobMissedNotification`, deduplicated per
job per day via the existing `NotificationsSentsService`/`s3_notifications_sent`
mechanism).

## `s3_restore_jobs`

One row per customer-initiated restore of backup data back to a real destination
on the agent's machine — the direct, customer-facing answer to "restores
silently fail." Distinct from `verify_snapshot` (see `protocol.md`), which only
ever restores to a throwaway temp path to spot-check a Kopia snapshot; this
table tracks restores the customer actually asked for, where the data landed,
and whether it verified.

| Column | Notes |
|---|---|
| `s3_backup_job_id` | The job being restored from. Required. |
| `s3_backup_job_run_id` | **Required for `engine=kopia` jobs** (picks which snapshot to extract from). **Must be `NULL` for `engine=rsync` jobs** — rsync has no point-in-time snapshot, so a restore always pulls current bucket state. Enforced in `RestoreJobsService::startRestore()` before the command is ever dispatched. |
| `destination_path` | Required, explicit. Never defaults to the job's original `source_paths` — restoring over the original location is allowed, but only if the customer explicitly requests that same path. |
| `restore_paths` | `text[]`, optional. Relative paths within the backup to restore. `NULL`/empty restores everything; expected to be the *less* common case — customers usually want one specific file (e.g. a DB dump) back, not the whole snapshot/bucket contents. |
| `verified` | Whether the agent's mandatory post-restore checksum check passed. A restore only reaches `completed` when the agent both finished **and** `verified = true` — anything else (including a checksum mismatch) lands as `failed`, never "completed with a caveat." |
| `bytes_restored` | Reported by the agent in its `result.output`. |
| `iam_account_id`, `iam_user_id` | Present directly on this table (unlike `s3_backup_job_runs`) — see Ownership below. |

### Status lifecycle

```
pending    (row created, restore_snapshot command not yet dispatched)
  │
  ▼
running    (agent accepted restore_snapshot, no result yet)
  │
  ├──► completed  (result status=completed AND verified=true)
  └──► failed     (result status=failed|rejected, OR verified=false)
```

Created by `BackupAgentCommandService::restoreSnapshot()`
(`RestoreJobsService::startRestore()`), closed out by
`BackupAgentEventService::handleResult()` reading `output.restore_uuid` —
mirrors the `run_job_now`/`run_uuid` pattern on `s3_backup_job_runs` exactly.

## Ownership

`iam_account_id`/`iam_user_id` live on `s3_backup_agents`, `s3_backup_jobs`, and
`s3_restore_jobs` directly (every account-owned table in this project must have
them). They're intentionally **absent** from `s3_backup_job_runs` — it's a
high-frequency child record whose ownership flows through
`s3_backup_job_id → s3_backup_jobs`, the same pattern `s3_server_telemetry`
already uses for its parent `s3_servers` relationship. `s3_restore_jobs` is
different: it's a customer-initiated, security-sensitive action in its own
right (not just a passive execution log), so "who asked for this data back"
needs to be a first-class, directly-queryable fact rather than inferred through
a join.
