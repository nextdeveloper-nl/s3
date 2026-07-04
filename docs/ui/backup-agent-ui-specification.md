# backup.agent — UI Specification

**Purpose:** Companion to `ui-specification.md` (the base S3 module UI spec) — read
that one first for shared conventions (API base URL, error handling, status pill
colors, byte/date formatting). This document covers everything needed to build the
backup.agent screens specifically: registration, job management, and run history.

Backend reference: `../backup.agent/overview.md`, `protocol.md`, `registration.md`,
`database.md`.

---

## 1. System Context

backup.agent is a customer-installed binary (Windows/Linux/macOS, any machine —
not tied to our IAAS VM fleet) that backs up files or a script's output to a
dedicated bucket in this account's S3 service. The UI's job is: let a customer
register a new machine, define what it backs up and on what schedule, and show
them whether it's actually working — silent failure is the #1 problem this whole
feature exists to solve, so **"last successful run" must be impossible to miss**
on every screen that lists an agent or a job.

There is no separate "backup" persona — this is all under the existing S3
Customer view. Nothing here is Admin-only (unlike Servers).

---

## 2. Navigation Structure

Extends the tree in `ui-specification.md` §2:

```
S3 Module
├── Dashboard
├── Buckets
├── Access Keys
├── Backup Agents                     ← new
│   ├── List
│   ├── Register New Agent (token issuance + one-time install command)
│   └── Agent Detail
│       ├── Jobs (list, create, edit, run now)
│       │   └── Job Detail → Run History
│       └── Revoke
├── Usage & Billing
├── Webhooks
└── [Admin Only] ...
```

A job's run history is reachable two ways: from the job's own detail view, and
filtered by agent from the agent detail view. Same data (`s3_backup_job_runs`),
different default filter.

---

## 3. API Endpoints

| Resource | Endpoint | Notes |
|---|---|---|
| Backup Agents | `GET /s3/backup-agents` | List/show/update/destroy — standard CRUD |
| Issue registration token | `POST /s3/backup-agents` | Does **not** create an active agent — see §4.2 |
| Revoke agent | `POST /s3/backup-agents/{id}/revoke` | Not a `/do/{action}` dispatch — it's its own route |
| Backup Jobs | `GET /s3/backup-jobs` | Filter by `s3BackupAgentId` for an agent's jobs |
| Run job now | `POST /s3/backup-jobs/{id}/run-now` | Returns the created `s3_backup_job_runs` row |
| Backup Job Runs | `GET /s3/backup-job-runs` | **Read-only** — no create/edit/delete in the UI at all. Filter by `s3BackupJobId` |

All three support the standard list filters (`tags`, `created_at_start/end`, etc.)
plus their own fields — see §4–6 below for the filterable ones worth exposing in
the UI.

The registration endpoint the *agent binary* calls
(`POST /backup-agents/register`, note: no `/s3` prefix, unauthenticated) is not a
UI concern — it's what the customer pastes into a terminal, not a browser call.
The UI's job is only to display that command (§4.2).

---

## 4. Backup Agents

### 4.1 Agent List

**Endpoint:** `GET /s3/backup-agents`

**Columns:**

| Field | Label | Notes |
|---|---|---|
| `hostname` | Machine | `—` while `status = pending` (agent hasn't reported in yet) |
| `os` / `arch` | Platform | e.g. "linux / amd64"; `—` while pending |
| `status` | Status | Pill: `active` green, `pending` — see §7 (needs a new pill color), `revoked` red |
| `health` | Health | Pill: `healthy` green, anything else falls back to grey/`unknown` |
| `last_seen_at` | Last Seen | Relative time (`—` if never) |
| `agent_version` | Version | Monospace |
| `created_at` | Registered | |

**Row actions:**
- **View** → Agent Detail (§4.3)
- **Revoke** (only if `status = active`) → confirm dialog → `POST /s3/backup-agents/{id}/revoke`

**Empty state:** "No backup agents yet." + primary button → Register New Agent.

**Filters:** `status`, `os`, `hostname` (contains).

### 4.2 Register New Agent

**Endpoint:** `POST /s3/backup-agents` (body: `{}` — no required fields; see
`BackupAgentsCreateRequest`, `tags` is the only optional field)

This does **not** create a working agent. It creates a `pending` row with a
one-time `registration_token`. Model this exactly like the existing Access Key
creation flow (`S3AccessKeyCreateForm.vue` + `S3SecretKeyModal.vue`) — a form
with effectively nothing to fill in, followed by a **one-time reveal modal**,
because `registration_token` behaves exactly like a secret key: shown once, gone
from the API after `register()` consumes it.

**Modal content after creation:**

1. Warning banner (reuse the exact copy pattern from `S3SecretKeyModal.vue`):
   "Save this token now — it expires in 1 hour and cannot be viewed again."
2. The token itself, monospace, with a Copy button.
3. **The install command, pre-filled and copyable as one block**, e.g.:
   ```
   backup-agent register --token=<registration_token> --endpoint=https://api.<domain>
   ```
   Give the customer a platform picker (Linux / Windows / macOS) if install
   instructions differ per OS — defer exact per-OS command text to the agent's own
   install docs once written; the token/endpoint substitution is the only
   UI-owned part.
4. Close button: "I've copied the install command."

**After closing the modal:** navigate to the new agent's detail page — it will
show `status = pending` until the agent's first heartbeat arrives.

### 4.3 Agent Detail

**Endpoint:** `GET /s3/backup-agents/{id}`

**Overview panel:**
- Hostname, OS/Arch, Status pill, Health pill
- Agent Version, Last Seen (relative + absolute on hover)
- Registered (`created_at`)
- If `status = pending`: banner — "Waiting for this agent to connect. Run the
  install command on the target machine to complete setup." + button to
  re-display the install command (re-fetch is not possible once the token is
  consumed server-side — if the token expired before use, the only path is
  **Revoke** this pending row and **Register New Agent** again; don't build a
  "resend token" action, there isn't one).

**Jobs panel:** table of this agent's jobs (`GET /s3/backup-jobs?s3BackupAgentId={id}`)
— see §5.1 for columns. Primary button: "New Job" (§5.2).

**Actions:**
- **Revoke** (§4.1) — confirm dialog copy: "This agent will be told to shut down,
  then its credentials are revoked immediately. It cannot be un-revoked —
  registering this machine again requires a new token."

---

## 5. Backup Jobs

### 5.1 Job List

**Endpoint:** `GET /s3/backup-jobs?s3BackupAgentId={agentId}` (always scoped to an
agent — jobs are shown inside Agent Detail, not as a standalone top-level list)

**Columns:**

| Field | Label | Notes |
|---|---|---|
| `name` | Name | Link to Job Detail |
| `job_type` | Type | `files` / `script` |
| `schedule` | Schedule | Raw cron string — see §7 for the "next run" caveat |
| `is_enabled` | Enabled | Toggle switch, `PATCH /s3/backup-jobs/{id}` `{is_enabled: bool}` |
| — | **Last Run** | Not a column on this model — fetch the most recent `s3_backup_job_runs` row per job (`GET /s3/backup-job-runs?s3BackupJobId={id}&orderBy=-created_at&per_page=1`) and render as a pill: `completed` green + relative time, `failed` red + relative time, `running` blue/pulsing, or "Never run" grey if no rows exist yet. **This column is the whole point of the feature — do not ship this screen without it.** |

**Row actions:**
- **Run Now** → `POST /s3/backup-jobs/{id}/run-now` → toast "Run started" → the
  Last Run cell should optimistically flip to `running` without a full reload.
- **Edit** → §5.3
- **Delete** → confirm dialog (`DELETE /s3/backup-jobs/{id}`)

### 5.2 Create Job

**Endpoint:** `POST /s3/backup-jobs`

**Form fields:**

| Field | Input | Validation / notes |
|---|---|---|
| `s3_backup_agent_id` | hidden | Set from the Agent Detail context, never user-chosen |
| `name` | text | required |
| `job_type` | radio/select: Files / Script | required; **changes which fields below are shown** |
| `source_paths` | repeatable text list | Files job: "Which files/folders to back up." Script job: "Where the script writes its output" (still required either way, informs what gets snapshotted) |
| `pre_script` | code editor / textarea | **Required when `job_type = script`** — validate client-side before submit, since the API rejects an empty script with a 403 (`BackupJobsService::assertScriptHasPreScript()`). Show a placeholder example (e.g. a `mysqldump` one-liner writing to a path also listed in `source_paths`). |
| `script_timeout_s` | number (seconds) | Only shown for `job_type = script`; default 900 |
| `schedule` | text, cron syntax | required; show a human-readable preview if a cron-parsing library is available client-side (e.g. "Every day at 02:00") — nice-to-have, not required for v1 |
| `keep_last_n` | number | optional retention — "keep the last N backups" |
| `keep_for_days` | number | optional retention — "keep backups for N days" (either retention field is fine alone; don't force both) |
| `bandwidth_limit_mbps` | number | optional |
| `object_lock_enabled` | toggle | **Warn: cannot be changed after creation**, same pattern as the Bucket create form's WORM toggle in `ui-specification.md` §5.2 |
| `is_enabled` | toggle | default on |

**Business rules:**
- A `script` job with an empty `pre_script` must be blocked client-side with an
  inline field error, matching the server-side rejection.
- `object_lock_enabled` follows the exact same "irreversible" messaging as bucket
  WORM creation — reuse that copy.

### 5.3 Edit Job

**Endpoint:** `PATCH /s3/backup-jobs/{id}`

**Editable:** `name`, `source_paths`, `pre_script`, `script_timeout_s`, `schedule`,
`keep_last_n`, `keep_for_days`, `bandwidth_limit_mbps`, `is_enabled`.

**Not editable (omit from the form entirely, don't just disable):**
`s3_backup_agent_id`, `s3_bucket_id`, `job_type`, `object_lock_enabled` — all
four are stripped server-side if sent (`BackupJobsService::update()`), so
showing them as editable would be misleading. See
`../backup.agent/updates/2026-07-04-per-job-destination-bucket.md` for why
`s3_bucket_id` exists at all.

### 5.4 Delete Job

Confirm dialog. No special-case warnings beyond the standard "this cannot be
undone" — unlike bucket deletion, there's no WORM-lock-blocks-delete equivalent
here (the underlying Kopia snapshots in the bucket aren't touched by deleting the
job record).

---

## 6. Backup Job Runs

**Read-only everywhere — there is intentionally no create/edit/delete UI for this
resource.** Runs are written by the agent and the platform's command dispatch,
never by a person filling out a form.

### 6.1 Run History (scoped to a job)

**Endpoint:** `GET /s3/backup-job-runs?s3BackupJobId={jobId}&orderBy=-created_at`

**Columns:**

| Field | Label | Notes |
|---|---|---|
| `status` | Status | Pill: `completed` green, `failed` red, `running` blue |
| `triggered_by` | Trigger | `schedule` / `manual` / `command` |
| `started_at` | Started | Relative + absolute on hover |
| `finished_at` | Finished | `—` while running |
| `bytes_uploaded` | Uploaded | Human-readable bytes; `—` if null |
| `bytes_deduped` | Deduplicated | Human-readable bytes; `—` if null. Consider showing as "X saved by dedup" if you want to make Kopia's value visible to the customer |
| `error` | Error | Only rendered when `status = failed`; full text in a tooltip/expandable row, not truncated silently |

**Empty state:** "No runs yet." — distinct from a `failed` state; don't conflate
"never ran" with "ran and failed," they mean very different things for a feature
whose entire point is surfacing exactly this distinction.

### 6.2 Run Detail (optional for v1)

If you want a dedicated detail view rather than just expanding the table row:
show every field above plus `kopia_snapshot_id` (monospace, useful for support to
cross-reference against the Kopia repository directly).

---

## 7. Status Pills — Additions Needed

`ui-specification.md` §14 documents the shared `statusPillClass`/`statusDotClass`
maps (`src/modules/s3/utils/statusPill.ts`). Backup agents introduce one status
value that map doesn't have yet:

| Value | Used by | Suggested color |
|---|---|---|
| `pending` | `s3_backup_agents.status` | Amber/yellow — "waiting," not error, not yet healthy. Add alongside the existing `degraded`/`suspended`/`paused` entries which already use the amber-ish `bg-yellow-100 ...` class. |

Everything else backup.agent uses (`active`, `revoked`, `healthy`, `completed`→treat
as `active`'s green, `failed`→treat as `revoked`'s red, `running`→needs a blue
entry, currently only `superseded` uses blue) — add `running` too:

| Value | Suggested color |
|---|---|
| `running` | Blue, same as existing `superseded` entry, ideally with the `animate-pulse` treatment `degraded`/`paused` already get, since "in progress" benefits from the same visual cue as "needs attention" |

### Cron schedule display (nice-to-have)

`schedule` is a raw cron string end to end — there's no server-side "next run
timestamp" field to display (the schedule is evaluated agent-side; see
`../backup.agent/overview.md`). If a cron-parsing package is already a
dependency (check before adding one), use it to render a human-readable preview
next to the raw string ("Every day at 02:00 UTC"). If not available, ship v1 with
just the raw cron string — don't block the feature on this.

---

## 8. Error Handling

Same shape as the rest of the S3 module (`ui-specification.md` §16). One
backup-specific case worth calling out explicitly:

| Scenario | HTTP | UI action |
|---|---|---|
| `pre_script` empty on a `script` job | 403 | Should be caught client-side first (§5.2); if it reaches the API anyway, show the response message as a field-level error on `pre_script` |
| Registration token expired or already used | 403 (from the agent's own `register()` call, not a browser call) | Not directly UI-facing — but the Agent Detail "pending" banner (§4.3) should make clear the only recovery path is Revoke + Register New Agent, since there is no resend/regenerate-token action |

---

## 9. Implementation Priority Order

1. **Agent List + Register New Agent** (with one-time token/install-command modal) — nothing else is reachable without this
2. **Agent Detail** with the pending-state banner
3. **Job List with Last Run column** — this is the feature's whole value proposition; do not ship without it
4. **Create/Edit Job**
5. **Run Now action** + optimistic status update
6. **Run History** (job-scoped)
7. **Revoke agent**
8. `pending`/`running` status pill additions
9. Cron human-readable preview (optional)
