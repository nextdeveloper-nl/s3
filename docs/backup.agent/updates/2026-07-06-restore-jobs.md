# Update: restore is now a real feature — plus a second backup engine

**Date:** 2026-07-06
**Backend:** `nextdeveloper/s3`
**Affects:** panel (frontend) and agent (Go binary) — both need work, see their
sections below

## What changed and why

Until now, backup.agent only had an upload direction. There was a
`verify_snapshot` command that restores a Kopia snapshot to a throwaway temp
location purely to checksum-verify it, but nothing persisted that outcome and
nothing let a customer actually get their data back — `overview.md`'s own
design research calls out "roughly half of restores fail in practice" as the
problem this whole feature exists to solve, and the only artifact for it was
that internal, unexposed primitive.

Two things changed together:

1. **A real, customer-facing restore path.** Pick a backup (a job, plus a
   specific run/snapshot for `kopia` jobs), ask the agent to restore it to an
   explicit destination, and track the outcome — including mandatory
   checksum verification — in a new `s3_restore_jobs` table. A restore is
   file-scoped by default (`restore_paths`): customers back up entire
   servers/databases but usually only want one specific file back (e.g. a DB
   dump), and are expected to already know the path — there's no
   snapshot-browsing UI or command.
2. **`s3_backup_jobs` now has an `engine` column** (`rsync` default, `kopia`
   opt-in). `rsync` mirrors a job's output directly into the bucket as a
   plain, browsable 1:1 copy — no point-in-time snapshot, a restore always
   means "current bucket state" for the requested path(s). `kopia` is the
   dedup engine this system originally (and still, for `kopia` jobs)
   described, where each run is a distinct restorable snapshot. This wasn't
   reflected anywhere in the schema/docs before this change.

These are linked: which restore path applies to a job depends entirely on its
`engine`. A `kopia` job's restore request must include
`s3_backup_job_run_id` (which snapshot to extract from); an `rsync` job's
must NOT include one — enforced in `RestoreJobsService::startRestore()`
before any command reaches the agent.

## What changed, concretely

- New `restore_snapshot` NATS command
  (`BackupAgentCommandService::restoreSnapshot()`) — params
  `{job_uuid, snapshot_id (nullable), destination_path, restore_paths (optional), restore_uuid}`,
  `timeout_s: 3600`. `snapshot_id` is only meaningful for `kopia` jobs; `null`
  for `rsync`.
- New `s3_restore_jobs` table + `RestoreJobs` model/service/filter/
  transformer, mirroring the `BackupJobs`/`BackupJobRuns` conventions
  exactly. Full column/lifecycle reference: `../database.md`.
- `BackupAgentEventService::handleResult()` now also handles
  `output.restore_uuid` — a restore only reaches `completed` when
  `status: "completed"` **and** `output.verified: true` both hold; anything
  else (including a checksum mismatch) is recorded `failed`.
- New endpoint: `POST /s3/backup-jobs/{id}/restore` — triggers a restore for
  that job. Body: `destination_path` (required), `restore_paths` (optional
  array), `s3_backup_job_run_id` (required for `engine=kopia`, must be absent
  for `engine=rsync` — a 422 either way if you get this backwards).
- New read-only endpoints: `GET /s3/restore-jobs`,
  `GET /s3/restore-jobs/{id}` — same "read-only by design" pattern as
  `backup-job-runs` (§6 of `../database.md`'s sibling doc,
  `backup-agent-ui-specification.md`).
- `engine` is now a field on `POST /s3/backup-jobs` (`nullable|in:rsync,kopia`,
  defaults to `rsync`) — immutable after creation, stripped server-side on
  `PATCH` like `job_type`/`s3_bucket_id`.
- The internal `verify_snapshot` command is untouched — it stays a
  kopia-only, temp-location, unexposed integrity check. It is not merged
  with `restore_snapshot`; they serve different purposes.

Full protocol details (JSON shapes, both commands' params, the `result`
event's extended `output`): `../protocol.md`.

## For the panel team

**`backup-agent-ui-specification.md` is already updated directly** with the
concrete field-by-field spec — this note is the "what changed and why."
Summary of what's new there:

- **Job List/Create/Edit (§5):** new `Engine` column and create-form field
  (radio: `rsync` default / `kopia`), immutable after creation like
  `job_type` — omit it from the edit form entirely, don't just disable it.
- **Run History (§6.1):** new row action, **Restore**, shown only for
  `kopia`-engine jobs' `completed` runs (an `rsync` job has no run to restore
  *from* — its restore trigger lives at the job level, not the run level).
- **New top-level Restore Jobs section (§6.3):** a "Restore" action at the
  job level too (for `rsync` jobs, and as a shortcut for `kopia` jobs when
  the customer wants the latest run rather than picking one from history),
  opening a form for `destination_path` (required) + optional
  `restore_paths` list, and a new read-only Restore History view
  (`GET /s3/restore-jobs?s3BackupJobId={id}`) — same "no create/edit/delete
  in the UI" pattern as Backup Job Runs, with a `verified` badge alongside
  the status pill so "completed but didn't verify" is never confusable with
  a clean success (there's no such state — unverified is always `failed`,
  but the badge still helps distinguish "verified success" from "agent
  reported done, platform is still confirming").
- **Status pills (§7):** restore jobs reuse the existing
  `completed`/`failed`/`running`/`pending` pill colors — no new color needed,
  just a new resource using the existing map.
- **Validation (§8):** surface the engine/run mismatch 422 as an inline
  field error on the run/snapshot picker, not a generic toast — same
  treatment as the existing `pre_script`-required-for-script-jobs case.

## For the agent (Go binary) team

- Implement the `restore_snapshot` operation: extract `restore_paths` (or
  everything, if empty) from either the live bucket objects (`rsync` jobs) or
  the given Kopia snapshot (`kopia` jobs) to `destination_path`, then run the
  same checksum-verification logic `verify_snapshot` already does, but
  against the real destination instead of a temp path.
- Report back via the standard `result` envelope with
  `output: {restore_uuid, verified, bytes_restored}` — `restore_uuid` is
  pre-created by the platform and must be echoed back exactly, same as
  `run_job_now`'s `run_uuid`.
- `verified` is not optional — a `restore_snapshot` result without it is
  treated as unverified (`false`) by the platform, which means `failed`, not
  `completed`. Don't omit it on a fast/happy path.
- Full sync payload entries now include `"engine": "rsync"|"kopia"` per job
  — pick the corresponding backup implementation, not just the restore one.