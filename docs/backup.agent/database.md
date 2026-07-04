# backup.agent — Database

Three new tables, all in the S3 module's schema (`s3_` prefix). DDL lives in
`schemas/s3_backup_agents.sql`, `schemas/s3_backup_jobs.sql`,
`schemas/s3_backup_job_runs.sql` — the same DDL is also checked into the
authoritative DataGrip project at
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
| `s3_bucket_id` | FK to `s3_buckets` — the dedicated bucket provisioned at register() time. |
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
| `job_type` | `files` (snapshot `source_paths` directly) or `script` (run `pre_script` first, snapshot its output). |
| `source_paths` | `text[]` — paths to snapshot (files job) or paths the script is expected to write to (script job). |
| `pre_script` | Required (enforced in `BackupJobsService::assertScriptHasPreScript()`) when `job_type = script`. |
| `schedule` | Cron expression, evaluated **agent-side** — see `docs/backup.agent/overview.md` for why. |
| `keep_last_n` / `keep_for_days` | Retention — same shape as `iaas_backup_retention_policies` in the (unrelated) hypervisor-level VM backup system. |
| `object_lock_enabled` | Routes the target bucket through the existing `s3_worm_commitments` mechanism. Immutable after creation — same rule as `s3_buckets.object_lock_enabled`, stripped in `BackupJobsService::update()`. |
| `s3_backup_agent_id`, `job_type` | Both immutable after creation — a job doesn't change which machine it lives on, and switching `files`↔`script` changes what `source_paths` even means. |

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

## Ownership

`iam_account_id`/`iam_user_id` live on `s3_backup_agents` and `s3_backup_jobs`
directly (every account-owned table in this project must have them). They're
intentionally **absent** from `s3_backup_job_runs` — it's a high-frequency child
record whose ownership flows through `s3_backup_job_id → s3_backup_jobs`, the same
pattern `s3_server_telemetry` already uses for its parent `s3_servers` relationship.
