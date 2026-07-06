# backup.agent — Overview

## What this is

`backup.agent` is a Go binary a customer installs on any machine — Windows, Linux,
or macOS, on our IAAS fleet or entirely elsewhere — to back up files or the output
of a script (e.g. a `mysqldump` wrapper) to this account's dedicated bucket in our
own multi-tenant S3 service.

It is **not** a replacement for the existing hypervisor-level VM backup system
(`NextDeveloper\IAAS` — `BackupJobs`/`VirtualMachineBackups`, XenServer snapshot +
export, no in-guest agent). That system answers "recover the whole VM." This one
answers "recover this database" or "recover these files," including on machines we
don't host at all.

## Why this design

Backup-tool research converged on one theme: **silent failure is the dominant
failure mode** — industry surveys cite roughly half of restores failing in
practice, with jobs reporting "success" against unusable or corrupted data.
Homegrown backup formats (chunking, dedup, encryption, incremental snapshots) are
a major contributor — mature tools get this right after years of edge cases;
reinventing it is where the risk concentrates. Backup repositories are also now a
primary ransomware target, making immutability non-negotiable.

Given that, four decisions shape everything below:

- **Two engines: `rsync` (default) and `kopia`.** `rsync` mirrors a job's
  output directly into the bucket as a plain, browsable 1:1 copy — no
  chunking/dedup, no point-in-time snapshot, cheapest to restore from since a
  file in the backup *is* an object in the bucket. `kopia`, embedded as a Go
  library, is the dedup/content-addressable option for jobs that need
  incremental snapshots, encryption, and space efficiency at the cost of data
  only being extractable through the Kopia engine itself, never a direct
  object read. Per-job, immutable after creation — see `database.md`.
- **Target: our own S3 service only.** Every agent is registered against a
  bucket the customer already owns (created via the normal Buckets API — we
  never provision one on their behalf), so we can apply the WORM/Object Lock
  immutability and quota infrastructure that already exists in this package,
  rather than supporting arbitrary external S3 endpoints.
- **Missed backups are surfaced, not inferred.** A job's outcome is recorded
  explicitly in `s3_backup_job_runs` every time it runs; a job that stops
  reporting completions gets escalated by `s3:backup-agents-check-missed`
  even while the agent's heartbeat looks perfectly healthy.
- **Restores are verified, not just reported.** The other half of "silent
  failure" is a restore that *looks* successful but hands back unusable data.
  Every customer-triggered restore (`restore_snapshot` — see `protocol.md`)
  mandatorily checksum-verifies the restored data before the platform will
  ever mark it `completed`; anything else, including a checksum mismatch,
  is recorded as `failed` in `s3_restore_jobs` (`database.md`) — never a
  success with a caveat. Most restore requests are expected to be file-scoped
  (`restore_paths`), not whole-snapshot — customers usually want one specific
  file (e.g. a DB dump) back, not the entire backup.

## House pattern this plugs into

The platform already runs a generic multi-tenant agent architecture over NATS
JetStream (`plusclouds.api.v4/docs/agents/{overview,protocol,database}.md`),
proven in production for the S3/storaged agent
(`S3AgentService`/`S3AgentCommandService`, the shipped Go agent at
`s3.agent`). `docs/agents/database.md` explicitly designed the generic
`agent_commands` table to be domain-agnostic *anticipating* a future "backup"
agent type — this implementation walks through that door rather than inventing
a new protocol:

- **Envelope** (unchanged): `{v, id, type, agent_type, agent_uuid, timestamp, payload}`,
  `agent_type: "backup"`.
- **Subjects** (unchanged pattern): `agent.backup.{uuid}.cmd` (platform → agent),
  `agent.backup.{uuid}.evt` (agent → platform).
- **Auth**: `NatsAuthCalloutService::AGENT_TABLES` (in `NextDeveloper\Events`) maps
  `s3_backup_agents → backup` — one line added to an existing generic map, no new
  code path.
- **Bulk data path stays off NATS.** NATS is control-plane only everywhere in this
  system. The agent's embedded Kopia client uploads backup data straight to its
  dedicated bucket over HTTPS using a scoped IAM key — the same separation of
  concerns storaged already uses (NATS for control, the SeaweedFS S3 gateway for
  data).

### The one genuinely new piece: registration

Existing agent types (`vm`, `storage`, `compute`, `network`, `s3`) are
pre-provisioned — their member row and `agent_api_key` exist before the agent ever
starts, baked into a config-drive ISO or written at VM-creation time. That doesn't
work here: backup.agent runs on a customer's own arbitrary machine with no
pre-existing relationship to us. See `registration.md` for the token → register →
NATS-credentials handshake, modeled on the existing in-VM "Managed Services" agent
registration flow (`App\Http\Controllers\ManagedServices\AgentRegistrationController`).

### Scheduling runs agent-side, not platform-side

Jobs are scheduled and evaluated **on the agent**, not triggered by the platform on
a tick. A customer's machine may be offline, asleep, or unreachable at any given
moment — a platform-side scheduler can't know when it'll next be reachable, so the
agent owns its own cron loop and reports finished runs after the fact (see the
`job_run` message in `protocol.md`). The platform can still force an out-of-band run
via the `run_job_now` command (dashboard "run now" button), which *does* pre-create
the run record before the agent replies, since that path has a known start.

## Document map

| File | Purpose |
|---|---|
| `overview.md` | This file — architecture, rationale, how it fits the existing agent system |
| `registration.md` | Token issuance → agent register() → bootstrap payload |
| `protocol.md` | Envelope, every command and event type, JSON examples |
| `database.md` | `s3_backup_agents` / `s3_backup_jobs` / `s3_backup_job_runs` schema and lifecycle |
| `updates/` | Dated notes for changes to this system after initial build, aimed at the panel and agent teams specifically — start here if you're checking whether a recent backend change affects work in progress |

## Key files

| File | Purpose |
|---|---|
| `src/Services/BackupAgentsService.php` | Registration token issuance (requires an existing bucket), register(), revoke() |
| `src/Services/BackupJobsService.php` | Job CRUD, full_sync payload assembly |
| `src/Services/BackupJobRunsService.php` | Run lifecycle (start/complete/fail/record), missed-job detection |
| `src/Services/RestoreJobsService.php` | Restore lifecycle (start/complete/fail), engine/run validation |
| `src/Services/BackupAgentCommandService.php` | Outbound NATS command dispatch, including `restoreSnapshot()` |
| `src/Services/BackupAgentEventService.php` | Inbound NATS message routing (heartbeat/telemetry/job_run/alert/result) |
| `src/Jobs/CheckMissedBackupsJob.php` + `Console/Commands/CheckMissedBackupsCommand.php` | `s3:backup-agents-check-missed` cron |
| `NextDeveloper\Events\Services\NatsAuthCalloutService` | NATS auth — `s3_backup_agents` entry in `AGENT_TABLES` |
| `App\Jobs\Nats\HandleBackupAgentEventJob` (host app) | Routes `agent.backup.*.evt` messages to `BackupAgentEventService` |
