# backup.agent — Protocol

Shares the same envelope and subject convention as every other agent type in this
system (`plusclouds.api.v4/docs/agents/protocol.md`) — `agent_type: "backup"` is
the only thing that differs.

## Envelope (both directions)

```json
{
  "v":          1,
  "id":         "550e8400-e29b-41d4-a716-446655440000",
  "type":       "command|heartbeat|telemetry|job_run|alert|result",
  "agent_type": "backup",
  "agent_uuid": "550e8400-e29b-41d4-a716-446655440001",
  "timestamp":  1748000000,
  "payload":    {}
}
```

## Subjects

| Direction | Subject |
|---|---|
| Platform → agent (commands) | `agent.backup.{uuid}.cmd` |
| Agent → platform (events)   | `agent.backup.{uuid}.evt` |

---

## Platform → Agent (commands)

Published by `NextDeveloper\S3\Services\BackupAgentCommandService`. Envelope
`type` is always `"command"`; `id` becomes `command_id` in the agent's `result`.

| `operation` | Params | Notes |
|---|---|---|
| `full_sync` | `{jobs: [...]}` | Complete desired job list — apply idempotently. Sent on registration and after every job CRUD change. |
| `run_job_now` | `{job_uuid, run_uuid}` | `run_uuid` is pre-created by the platform (see `database.md`) — the agent must echo it back in its `result.output.run_uuid`. |
| `pause_job` | `{job_uuid}` | |
| `resume_job` | `{job_uuid}` | |
| `cancel_job` | `{job_uuid}` | Cancel a currently-running job. |
| `verify_snapshot` | `{job_uuid, snapshot_id}` | Restore the given Kopia snapshot to a temp location and checksum-verify it. Long `timeout_s` (1800s) — a restore+checksum pass takes a while. |
| `restore_snapshot` | `{job_uuid, snapshot_id, destination_path, restore_paths, restore_uuid}` | Customer-facing restore: restore backup data to a real `destination_path`, then mandatorily checksum-verify it before reporting success. Works for both engines — see below. Long `timeout_s` (3600s) — writes the full dataset back to disk in addition to checksumming it. |
| `revoke` | `{reason}` | Shut down cleanly. Sent before `agent_api_key` is cleared — the agent has a brief window to react before it's rejected on reconnect. |

#### `restore_snapshot` params

- `snapshot_id` — the Kopia snapshot to extract from. **Only meaningful for
  `engine=kopia` jobs.** `null` for `engine=rsync` jobs, which have no
  point-in-time snapshot — a restore for those always means "copy what's
  currently in the bucket" for `restore_paths`.
- `destination_path` — required, explicit. Never defaults to the job's
  original `source_paths` — restoring over the original location is allowed,
  but only if the customer explicitly requested that same path.
- `restore_paths` — optional array of paths relative to the backup root.
  Empty/omitted restores everything; the common case is expected to be a
  single specific file (e.g. a DB dump), not the whole snapshot/bucket
  contents.
- `restore_uuid` — pre-created by the platform (an `s3_restore_jobs` row),
  echoed back in the `result.output.restore_uuid`, same pattern as
  `run_job_now`'s `run_uuid`.
- The agent picks its restore strategy based on which engine the job uses
  (direct per-object copy for `rsync`, path-scoped extraction for `kopia`) —
  that logic is entirely agent-side.

### `full_sync` job entry shape

Each item in `payload.jobs` (see `BackupJobsService::buildFullSyncPayload()`):

```json
{
  "uuid": "...",
  "name": "nightly-mysql-dump",
  "job_type": "files|script",
  "engine": "rsync|kopia",
  "source_paths": ["/var/backups/mysql"],
  "pre_script": "#!/bin/bash\nmysqldump ... > /var/backups/mysql/dump.sql\n",
  "script_timeout_s": 900,
  "schedule": "0 2 * * *",
  "keep_last_n": 14,
  "keep_for_days": null,
  "bandwidth_limit_mbps": 50,
  "bucket_name": "backup-agent-a1b2c3d4",
  "object_lock_enabled": false
}
```

- `bucket_name` — **where this job's Kopia snapshots go.** Most jobs share the
  agent's own bucket (chosen by the customer when the registration token was
  issued — never auto-created); a job with `object_lock_enabled: true` must
  point at a *different*, pre-existing bucket that already has Object Lock
  enabled, since that's a bucket-level setting that can't retroactively apply
  to whatever the agent's default bucket already holds, and we never create
  buckets on the customer's behalf. See
  `BackupJobsService::resolveBucketForJob()`. The agent points its Kopia
  repository client at this bucket per job — never assume "the" bucket from
  registration is the only one a given agent will ever see.
- `job_type: "files"` — snapshot `source_paths` directly.
- `job_type: "script"` — run `pre_script` first (respecting `script_timeout_s`); a
  non-zero exit or missing/empty output must fail the run *before* the agent ever
  touches Kopia. A failed pre-script is never allowed to look like a successful
  backup (see `overview.md` — this is the direct answer to the "silent failure"
  problem the design research turned up).
- `engine: "rsync"` (default) — mirrors the output path directly into the bucket
  as a plain, browsable 1:1 copy. No point-in-time snapshot concept; a restore
  always means "current bucket state" (see `restore_snapshot` above).
  `engine: "kopia"` — the dedup/content-addressable engine described throughout
  the rest of this doc, where each run produces a distinct restorable snapshot.
- `schedule` is a standard cron expression, evaluated **by the agent**, not the
  platform (see `overview.md`).

---

## Agent → Platform (events)

Published to `agent.backup.{uuid}.evt`, routed by
`NextDeveloper\S3\Services\BackupAgentEventService::handle()`.

### `heartbeat`

Every ~30s. `{version, uptime_s, tasks_queued}` — same shape as every other agent
type. The first heartbeat after registration triggers a `full_sync` from the
platform (mirrors the "pending server connected" behavior already in
`S3AgentService`).

### `telemetry`

OS-level metrics (disk free, etc). There's no dedicated telemetry table for backup
agents — the thing that actually matters here (did the backup work) travels in
`job_run`/`result` instead, not a periodic snapshot.

### `job_run`

**The core message of this protocol.** Sent when the agent's own cron fires a job
and it finishes — there was no platform command for this, so there's no
`command_id` to reply to.

```json
{
  "type": "job_run",
  "payload": {
    "job_uuid":          "...",
    "run_uuid":          "...",
    "status":            "completed",
    "started_at":        "2026-07-04T02:00:00Z",
    "finished_at":       "2026-07-04T02:03:41Z",
    "bytes_uploaded":    104857600,
    "bytes_deduped":     943718400,
    "kopia_snapshot_id": "k1a2b3c4",
    "error":             null
  }
}
```

The agent generates its own `run_uuid` and the platform creates the
`s3_backup_job_runs` row with that exact uuid (`BackupJobRunsService::recordRun()`)
— a redelivered `job_run` is naturally idempotent rather than creating a duplicate
row with a fresh id.

### `alert`

Same shape as every other agent type — `{severity, code, message, details}` — fired
via `Events::fire("alert:backup.{code}", ...)`.

### `result`

Reply to a platform-initiated command (`run_job_now`, `pause_job`, etc.) — the only
path with a `command_id`, since scheduled runs never go through this method (see
`job_run` above).

```json
{
  "type": "result",
  "payload": {
    "command_id": "...",
    "status":     "completed",
    "message":    "...",
    "output": {
      "run_uuid":          "...",
      "bytes_uploaded":    104857600,
      "bytes_deduped":     943718400,
      "kopia_snapshot_id": "k1a2b3c4"
    }
  }
}
```

For a `restore_snapshot` command, `output` instead carries:

```json
{
  "output": {
    "restore_uuid":    "...",
    "verified":        true,
    "bytes_restored":  104857600
  }
}
```

`verified` is mandatory — the platform only marks a restore `completed` when
`status: "completed"` **and** `verified: true` both hold (see
`RestoreJobsService::completeRestore()`/`failRestore()`); anything else,
including a checksum mismatch, is recorded as `failed`.

`status` values: `completed`, `failed`, `rejected` — same convention as every
other agent type. `rejected` is what an agent should return when it can't act
on the command as given — most commonly an unrecognized `job_uuid` (its local
job list is stale relative to the platform, e.g. it reconnected before the
last `full_sync` landed, or a job changed after its last sync). On a
`rejected` result, `BackupAgentEventService::handleResult()` immediately
re-sends `full_sync` to that agent so its job list catches up before the next
attempt.

For `run_job_now` specifically, a `rejected` result also triggers an automatic
retry of the run (`BackupAgentCommandService::retryRunJobNowAfterRejection()`)
— full_sync, then re-send `run_job_now`. This is capped at
`MAX_REJECTED_RETRIES` (3) consecutive rejections for the same `job_uuid`
(cache-based counter, 15-minute TTL, not a DB column). Past that, the platform
stops retrying — three rejections in a row each preceded by a fresh
`full_sync` means the agent isn't merely out of sync — and leaves a system
comment on the job (`NextDeveloper\Commons\Services\CommentsService::createSystemComment()`)
for a human to investigate instead of looping forever.

## Versioning

Same rule as the rest of the system: `v: 1` today; agents should reject unknown
versions and log a warning. New optional fields can be added without a version
bump; removing or renaming a required field requires a new version.
