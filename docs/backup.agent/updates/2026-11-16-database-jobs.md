# Update: database backup jobs (`job_type: "database"`) and the database picker

**Date:** 2026-11-16
**Backend:** `nextdeveloper/s3`
**Affects:** backend (this repo), panel (job form), backup.agent (Go binary)

## What changed and why

A backup job could only back up paths (`files`) or the output of a
hand-written script (`script`). Customers running SQL Server on Windows —
and anyone who doesn't want to write a dump script — had no way to say
"back up these databases".

A job can now be of type `database`: the customer picks an engine and ticks
databases, and the agent dumps them itself and uploads the dumps. The list
of databases to tick comes live from the agent.

Engines: `postgres`, `mysql` (also MariaDB), `mssql`.

The agent side (dump drivers, runner, `list_databases` command, machine
requirements) is documented in the backup.agent repo:
`docs/backup.agent/updates/2026-11-16-database-jobs.md`.

## Schema — must be applied before deploying this code

```sql
ALTER TABLE s3_backup_jobs ADD COLUMN database_engine     text;
ALTER TABLE s3_backup_jobs ADD COLUMN database_connection text;
ALTER TABLE s3_backup_jobs ADD COLUMN database_names      text[];
ALTER TABLE s3_backup_jobs ADD COLUMN database_all        boolean NOT NULL DEFAULT false;

-- required too: the deployed table has a CHECK that only allows files|script
ALTER TABLE s3_backup_jobs DROP CONSTRAINT s3_backup_jobs_job_type_check;
ALTER TABLE s3_backup_jobs ADD  CONSTRAINT s3_backup_jobs_job_type_check
    CHECK (job_type IN ('files', 'script', 'database'));
```

All four are additive and nullable/defaulted; existing rows are unaffected.
`schemas/s3_backup_jobs.sql` carries the same statements.

| Column | Notes |
|---|---|
| `database_engine` | `postgres` \| `mysql` \| `mssql`. Required for `job_type = database`. Immutable after creation (stripped in `BackupJobsService::update()`). Not to be confused with `engine` (rsync/kopia). |
| `database_connection` | Name of a connection in the agent's local config. `NULL` = `default`. |
| `database_names` | Selected databases. Ignored when `database_all`. |
| `database_all` | Every user database present at run time. |

**No credentials are stored.** Host, user and password live only in the
agent's own config file on the customer's machine.

## API

### `POST /s3/backup-jobs` and `PATCH /s3/backup-jobs/{id}`

`job_type` accepts `database`. New fields: `database_engine` (create only),
`database_connection`, `database_names[]`, `database_all`.

Rules, enforced in `BackupJobsService::normalizeDatabaseSelection()`:

- `database_engine` must be one of the three engines;
- either `database_all = true` or at least one entry in `database_names`;
- names are trimmed and de-duplicated;
- `script_timeout_s` defaults to 3600 on create (the column default of 900
  is too short for a real dump); it bounds the whole dump phase;
- for `files`/`script` jobs the `database_*` fields are dropped.

`source_paths` and `pre_script` are ignored by the agent for this type.

### `POST /s3/backup-agents/{id}/list-databases`

Body: `{"engine": "mssql", "connection": "default"}` (`connection` optional).

Asks the agent, synchronously over NATS (30s limit), which databases it can
back up. **Always answers 200** with:

```json
{ "ok": true, "engine": "mssql", "databases": ["crm", "erp"], "code": null, "message": null }
```

On failure `ok` is `false`, `databases` is empty, and:

| `code` | Meaning |
|---|---|
| `AGENT_NOT_ACTIVE` | Agent is pending/revoked — it was not contacted. |
| `AGENT_TIMEOUT` | No reply — agent offline or disconnected. |
| `AGENT_UNSUPPORTED` | Agent predates database jobs and must be updated. |
| `LIST_FAILED` | The agent tried and failed; `message` is the database client's own error (bad password, server down, client tools missing). |

These are 200s on purpose: every one of them is something the customer has
to fix on their own machine, and `message` is what they need to read.

### `full_sync`

Each job entry gains a `database` key — `null` for `files`/`script`, and for
`database` jobs:

```json
"database": { "engine": "mssql", "connection": "default", "databases": ["crm", "erp"], "all": false }
```

See `BackupJobsService::databasePayload()` and `../protocol.md`.

## Agent version

An agent older than this change ignores `database` and fails every run of
such a job with "no source_paths configured". There is no hard version gate
on job creation; the panel's picker cannot load on an old agent
(`AGENT_UNSUPPORTED`), which is what stops a customer from creating one
through the UI. A direct API caller is not stopped.

## For the panel team

Implemented in `S3BackupJobForm.vue`: a third job type "Database", an engine
choice, an "All databases" switch, and a checklist fed by `list-databases`
with a Refresh action. When the list cannot be loaded the agent's message is
shown as is. On edit, databases that were selected but no longer exist on
the server are flagged and can be removed.
