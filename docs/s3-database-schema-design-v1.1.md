# S3 Service — Database Schema Design

**Version:** 1.1  
**Status:** Draft  
**Last Updated:** 2026-05-26  
**Context:** Companion to `seaweedfs-s3-service-standard.md` and `storaged-design.md`

---

## Changelog

| Version | Date | Changes |
|---|---|---|
| 1.1 | 2026-05-26 | Added `s3_buckets` versioning columns; added `s3_webhooks`, `s3_webhook_deliveries`, `s3_multipart_uploads`, `s3_worm_commitments`, `s3_deposit_ledger` tables; updated entity overview, design decisions, and data ownership summary |
| 1.0 | 2026-05-26 | Initial draft |

---

## Table of Contents

1. [Scope & Assumptions](#1-scope--assumptions)
2. [Entity Overview](#2-entity-overview)
3. [Schema](#3-schema)
   - [s3_servers](#s3_servers)
   - [s3_accounts](#s3_accounts)
   - [s3_buckets](#s3_buckets)
   - [iam_keys](#iam_keys)
   - [s3_server_telemetry](#s3_server_telemetry)
   - [s3_usage_snapshots](#s3_usage_snapshots)
   - [s3_bandwidth_monthly](#s3_bandwidth_monthly)
   - [s3_notifications_sent](#s3_notifications_sent)
   - [s3_audit_log](#s3_audit_log)
   - [s3_webhooks](#s3_webhooks) *(new v1.1)*
   - [s3_webhook_deliveries](#s3_webhook_deliveries) *(new v1.1)*
   - [s3_multipart_uploads](#s3_multipart_uploads) *(new v1.1)*
   - [s3_worm_commitments](#s3_worm_commitments) *(new v1.1)*
   - [s3_deposit_ledger](#s3_deposit_ledger) *(new v1.1)*
4. [Design Decisions](#4-design-decisions)
5. [IAM Key Generation](#5-iam-key-generation)
6. [Data Ownership Summary](#6-data-ownership-summary)

---

## 1. Scope & Assumptions

This document covers only the tables introduced for the S3 service. It assumes the following already exist in the database:

- An `accounts` (or `users`) table with a UUID primary key — `s3_accounts.account_id` references it
- An application-level encryption mechanism for secrets at rest
- A payment processor integration reachable from the provisioning layer
- PostgreSQL 16+

Tables defined here are prefixed with `s3_`, `iam_`, or `worm_` to avoid collisions with existing schema.

---

## 2. Entity Overview

```
accounts (existing)
    │
    └──► s3_accounts          ← one S3 service subscription per account
              │
              ├──► s3_buckets              ← buckets owned by this account
              │         │
              │         └──► s3_worm_commitments   ← WORM lock commitment per bucket (new)
              │                   │
              │                   └──► s3_deposit_ledger  ← deposit/refund transactions (new)
              │
              ├──► iam_keys                ← access key pairs for S3 API auth
              ├──► s3_usage_snapshots      ← 15-min quota check snapshots
              ├──► s3_bandwidth_monthly    ← hourly egress/ingress aggregates
              ├──► s3_notifications_sent   ← email deduplication
              ├──► s3_webhooks             ← customer webhook subscriptions (new)
              │         │
              │         └──► s3_webhook_deliveries ← per-event delivery log (new)
              └──► s3_multipart_uploads    ← in-progress multipart upload tracking (new)

s3_servers                    ← physical storage servers running storaged
    │
    ├──► s3_buckets            ← buckets live on a specific server
    └──► s3_server_telemetry   ← 30-second health snapshots from storaged

s3_audit_log                  ← immutable record of all admin and system actions
```

---

## 3. Schema

### `s3_servers`

Represents a physical storage server running the `storaged` agent alongside a SeaweedFS cluster. Health and component state are written here by `storaged` via PATCH. Orchestration derives the top-level `health` field from the `components` JSONB object.

```sql
CREATE TABLE s3_servers (
    id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    hostname            TEXT UNIQUE NOT NULL,           -- e.g. 'storage01.internal'
    display_name        TEXT,                           -- optional human label
    agent_api_key       TEXT NOT NULL,                  -- hashed; used in X-Agent-Key auth
    agent_version       TEXT,                           -- last reported by storaged
    seaweedfs_version   TEXT,                           -- last reported by storaged

    -- Agent liveness (written by storaged telemetry)
    agent_status        TEXT NOT NULL DEFAULT 'unknown',
                                                        -- online | unknown | offline
    agent_last_seen_at  TIMESTAMPTZ,
    agent_connected_at  TIMESTAMPTZ,

    -- Derived health (computed by orchestration from components)
    health              TEXT NOT NULL DEFAULT 'unknown',
                                                        -- ok | degraded | critical | unknown
    health_summary      TEXT,

    -- Component state — JSON Merge Patch target written by storaged
    -- Shape mirrors the s3_server object in storaged-design.md
    components          JSONB NOT NULL DEFAULT '{}',
    /*
      {
        "master":  { "reachable": bool, "is_leader": bool, "peers": int, "checked_at": timestamptz },
        "volume":  { "reachable": bool, "total_volumes": int, "volumes_writable": int,
                     "volumes_degraded": int, "volumes_readonly": int,
                     "capacity_bytes_total": int, "capacity_bytes_used": int,
                     "capacity_pct": float, "checked_at": timestamptz },
        "filer":   { "reachable": bool, "checked_at": timestamptz },
        "s3":      { "reachable": bool, "bucket_count": int, "checked_at": timestamptz }
      }
    */

    created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
```

---

### `s3_accounts`

One row per tenant S3 service subscription. Links to the existing `accounts` table via `account_id`. Carries quota limits and the current soft-enforcement status. The `slug` is used as the mandatory bucket name prefix and as the IAM namespace (e.g. `acme-corp-*`).

```sql
CREATE TABLE s3_accounts (
    id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    account_id          UUID NOT NULL UNIQUE,           -- FK → your accounts table
    slug                TEXT UNIQUE NOT NULL,           -- url-safe, bucket prefix: 'acme-corp'

    status              TEXT NOT NULL DEFAULT 'active',
                                                        -- active | blocked | suspended | deleted

    -- Quota limits
    quota_storage_bytes     BIGINT NOT NULL DEFAULT 107374182400,   -- 100 GB
    quota_egress_bytes_mo   BIGINT NOT NULL DEFAULT 536870912000,   -- 500 GB/month
    quota_max_buckets       INT    NOT NULL DEFAULT 10,
    quota_max_objects       BIGINT NOT NULL DEFAULT 10000000,

    -- Live usage cache (updated by quota-check cron every 15 min)
    storage_bytes_used      BIGINT NOT NULL DEFAULT 0,
    egress_bytes_mo_used    BIGINT NOT NULL DEFAULT 0,
    object_count            BIGINT NOT NULL DEFAULT 0,
    usage_checked_at        TIMESTAMPTZ,

    -- Blocking metadata
    blocked_at              TIMESTAMPTZ,                -- null when not blocked
    blocked_reason          TEXT,

    created_at              TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at              TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_s3_accounts_status  ON s3_accounts (status);
CREATE INDEX idx_s3_accounts_account ON s3_accounts (account_id);
```

---

### `s3_buckets`

One row per S3 bucket. Owned by an `s3_account`, physically residing on an `s3_server`. Orchestration owns the intent fields (`replication_factor`, `lifecycle_rules`, versioning, Object Lock). `storaged` writes the observed state fields (`object_count`, `size_bytes`, `replica_health`) via telemetry.

All bucket names must start with the owning account's `slug` — enforced at the application layer.

> **v1.1:** Added `versioning`, `mfa_delete`, `object_lock_enabled`, `object_lock_mode`, and `object_lock_days` columns. Object Lock columns are immutable after bucket creation — enforced at the application layer.

```sql
CREATE TABLE s3_buckets (
    id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    s3_account_id       UUID NOT NULL REFERENCES s3_accounts(id),
    s3_server_id        UUID NOT NULL REFERENCES s3_servers(id),

    -- Intent (owned by orchestration)
    name                TEXT NOT NULL,                  -- full name, e.g. 'acme-corp-backups'
    replication_factor  INT  NOT NULL DEFAULT 1,        -- 1 = no replication, 2 = two copies
    lifecycle_rules     JSONB NOT NULL DEFAULT '[]',
    -- [{ "prefix": "logs/", "expire_days": 30 }, ...]

    -- Versioning (new v1.1)
    versioning          TEXT NOT NULL DEFAULT 'Suspended',
                                                        -- Suspended | Enabled
    mfa_delete          BOOLEAN NOT NULL DEFAULT false, -- future: require MFA for version deletes

    -- Object Lock / WORM (new v1.1)
    -- IMMUTABLE after bucket creation — cannot be changed retroactively (S3 spec constraint)
    object_lock_enabled BOOLEAN NOT NULL DEFAULT false,
    object_lock_mode    TEXT,                           -- GOVERNANCE | COMPLIANCE | null
    object_lock_days    INT,                            -- default retention period in days | null

    -- Observed state (written by storaged telemetry)
    object_count        BIGINT,
    size_bytes          BIGINT,
    replica_health      TEXT,                           -- ok | degraded | unknown

    status              TEXT NOT NULL DEFAULT 'active',
                                                        -- active | deleting | deleted

    created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ NOT NULL DEFAULT NOW(),

    UNIQUE (s3_server_id, name),                        -- SeaweedFS constraint: unique per server

    -- Object Lock consistency constraints
    CONSTRAINT chk_object_lock_mode CHECK (
        (object_lock_enabled = false AND object_lock_mode IS NULL AND object_lock_days IS NULL)
        OR
        (object_lock_enabled = true AND object_lock_mode IN ('GOVERNANCE', 'COMPLIANCE') AND object_lock_days > 0)
    )
);

CREATE INDEX idx_s3_buckets_account      ON s3_buckets (s3_account_id);
CREATE INDEX idx_s3_buckets_server       ON s3_buckets (s3_server_id);
CREATE INDEX idx_s3_buckets_object_lock  ON s3_buckets (object_lock_enabled) WHERE object_lock_enabled = true;
```

---

### `iam_keys`

Access key pairs for S3 API authentication. Each key belongs to an `s3_account` and optionally carries per-bucket ACL overrides on top of a coarse `role`. Orchestration is the source of truth; `storaged` creates and deletes these on the SeaweedFS side based on `full_sync` payloads.

Secret keys are **never stored in plaintext** — see [Section 5](#5-iam-key-generation) for generation and storage guidance.

```sql
CREATE TABLE iam_keys (
    id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    s3_account_id       UUID NOT NULL REFERENCES s3_accounts(id),

    -- Credentials
    access_key          TEXT UNIQUE NOT NULL,           -- AKIA_ prefix + slug + random 16 chars
    secret_key_enc      TEXT NOT NULL,                  -- encrypted at rest (AES-256-GCM)

    -- Permissions
    role                TEXT NOT NULL DEFAULT 'readwrite',
                                                        -- readwrite | readonly | writeonly | nodelete
    -- Fine-grained per-bucket overrides (empty = role applies uniformly)
    bucket_acls         JSONB NOT NULL DEFAULT '[]',
    -- [{ "bucket_id": "uuid", "permission": "rw|ro|wo|none" }, ...]

    -- Lifecycle
    status              TEXT NOT NULL DEFAULT 'active', -- active | revoked
    expires_at          TIMESTAMPTZ,                    -- null = never expires
    last_used_at        TIMESTAMPTZ,                    -- updated on auth events
    revoked_at          TIMESTAMPTZ,
    revoked_reason      TEXT,

    created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_iam_keys_account    ON iam_keys (s3_account_id);
CREATE INDEX idx_iam_keys_access_key ON iam_keys (access_key);
CREATE INDEX idx_iam_keys_status     ON iam_keys (status);
```

> **Note on `nodelete` role:** Added in v1.1 to support the recommended two-key pattern for WORM buckets. A `nodelete` key can Read, Write, and List but cannot call DeleteObject or AbortMultipartUpload. This role is applied automatically when a key is scoped to a bucket with `object_lock_enabled = true`.

---

### `s3_server_telemetry`

Append-only time-series written from `storaged` telemetry POSTs every 30 seconds. Used for capacity trending, alerting, and historical health queries. Pruned by a cron job (recommended retention: 30 days).

```sql
CREATE TABLE s3_server_telemetry (
    id                      BIGSERIAL PRIMARY KEY,
    s3_server_id            UUID NOT NULL REFERENCES s3_servers(id),
    reported_at             TIMESTAMPTZ NOT NULL DEFAULT NOW(),

    master_reachable        BOOLEAN,
    volume_count            INT,
    volumes_degraded        INT,
    capacity_bytes_total    BIGINT,
    capacity_bytes_used     BIGINT,
    capacity_pct            NUMERIC(5,2)
);

CREATE INDEX idx_s3_telemetry_server_time
    ON s3_server_telemetry (s3_server_id, reported_at DESC);
```

---

### `s3_usage_snapshots`

Per-account storage and object count snapshots written every 15 minutes by the quota-check cron. Separate from server telemetry — this is per-tenant, not per-server. Used for quota enforcement history and usage graphs.

```sql
CREATE TABLE s3_usage_snapshots (
    id              BIGSERIAL PRIMARY KEY,
    s3_account_id   UUID NOT NULL REFERENCES s3_accounts(id),
    snapshot_at     TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    storage_bytes   BIGINT NOT NULL,
    object_count    BIGINT NOT NULL
);

CREATE INDEX idx_s3_usage_account_time
    ON s3_usage_snapshots (s3_account_id, snapshot_at DESC);
```

---

### `s3_bandwidth_monthly`

Egress and ingress aggregated per account per calendar month. Updated hourly by the nginx log parser. Reset at the start of each month by `bandwidth-reset.py`.

```sql
CREATE TABLE s3_bandwidth_monthly (
    s3_account_id   UUID NOT NULL REFERENCES s3_accounts(id),
    month           DATE NOT NULL,                      -- always first of month
    egress_bytes    BIGINT NOT NULL DEFAULT 0,
    ingress_bytes   BIGINT NOT NULL DEFAULT 0,
    PRIMARY KEY (s3_account_id, month)
);
```

---

### `s3_notifications_sent`

Deduplication table to prevent repeated warning emails within the same month and threshold. The unique constraint on `(s3_account_id, notification, month)` means the application can upsert without risk of double-sends.

```sql
CREATE TABLE s3_notifications_sent (
    id              BIGSERIAL PRIMARY KEY,
    s3_account_id   UUID NOT NULL REFERENCES s3_accounts(id),
    notification    TEXT NOT NULL,
    -- 'warning_80_storage'  | 'warning_80_egress'
    -- 'exceeded_storage'    | 'exceeded_egress'
    -- 'worm_expiry_30d'     | 'worm_expiry_7d'    (new v1.1)
    -- 'worm_locked_cancel'  | 'deposit_refunded'  (new v1.1)
    month           DATE NOT NULL,
    sent_at         TIMESTAMPTZ NOT NULL DEFAULT NOW(),

    UNIQUE (s3_account_id, notification, month)
);
```

---

### `s3_audit_log`

Immutable append-only record of every admin and automated action. Never updated or deleted. All foreign keys are nullable so a single table covers actions across all entity types.

```sql
CREATE TABLE s3_audit_log (
    id              BIGSERIAL PRIMARY KEY,
    action          TEXT NOT NULL,
    -- 'provision'        | 'block'           | 'unblock'       | 'delete'         | 'purge'
    -- 'key_create'       | 'key_revoke'      | 'key_rotate'
    -- 'bucket_create'    | 'bucket_delete'
    -- 'quota_update'     | 'agent_command'
    -- 'worm_commit'      | 'worm_cancel'     | 'worm_expired'  | 'worm_purged'    (new v1.1)
    -- 'deposit_charged'  | 'deposit_refunded'| 'deposit_forfeited'                (new v1.1)
    -- 'webhook_create'   | 'webhook_delete'  | 'multipart_aborted'                (new v1.1)
    s3_account_id   UUID REFERENCES s3_accounts(id),
    s3_server_id    UUID REFERENCES s3_servers(id),
    iam_key_id      UUID REFERENCES iam_keys(id),
    s3_bucket_id    UUID REFERENCES s3_buckets(id),
    performed_by    TEXT NOT NULL,                      -- admin username or 'system'
    reason          TEXT,
    metadata        JSONB,                              -- arbitrary extra context
    performed_at    TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_s3_audit_account ON s3_audit_log (s3_account_id);
CREATE INDEX idx_s3_audit_time    ON s3_audit_log (performed_at DESC);
```

---

### `s3_webhooks` *(new v1.1)*

Customer-configured webhook subscriptions. One row per subscription. A customer may subscribe to multiple event types on multiple buckets. The `secret` column holds a signing key used to generate `X-Signature: sha256=HMAC(secret, body)` headers on every delivery, allowing the customer's endpoint to verify authenticity.

```sql
CREATE TABLE s3_webhooks (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    s3_account_id   UUID NOT NULL REFERENCES s3_accounts(id),
    s3_bucket_id    UUID REFERENCES s3_buckets(id),    -- null = all buckets for this account

    endpoint_url    TEXT NOT NULL,                     -- customer HTTPS endpoint
    events          TEXT[] NOT NULL,
    -- 'ObjectCreated:*'   | 'ObjectCreated:Put'   | 'ObjectCreated:CompleteMultipartUpload'
    -- 'ObjectDeleted:*'   | 'ObjectDeleted:Delete'
    -- 'ObjectRestore:*'   (future)

    secret          TEXT,                              -- HMAC-SHA256 signing key; null = unsigned
    status          TEXT NOT NULL DEFAULT 'active',    -- active | paused | deleted
    failure_count   INT  NOT NULL DEFAULT 0,           -- consecutive delivery failures
    paused_at       TIMESTAMPTZ,                       -- set when failure_count exceeds threshold

    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_s3_webhooks_account ON s3_webhooks (s3_account_id);
CREATE INDEX idx_s3_webhooks_bucket  ON s3_webhooks (s3_bucket_id);
CREATE INDEX idx_s3_webhooks_status  ON s3_webhooks (status) WHERE status = 'active';
```

---

### `s3_webhook_deliveries` *(new v1.1)*

Per-event delivery log for webhook attempts. Append-only during active delivery; `status_code` and `delivered_at` are written on completion. Supports retry tracking via `attempt` and `next_retry_at`. Recommended retention: 7 days (pruned by cron).

```sql
CREATE TABLE s3_webhook_deliveries (
    id              BIGSERIAL PRIMARY KEY,
    webhook_id      UUID NOT NULL REFERENCES s3_webhooks(id),

    event_type      TEXT NOT NULL,                     -- e.g. 'ObjectCreated:Put'
    object_key      TEXT NOT NULL,                     -- S3 object key that triggered the event
    payload         JSONB NOT NULL,                    -- full S3-compatible event payload

    status_code     INT,
    -- null = pending/in-flight
    -- 0    = timeout or connection error
    -- 2xx  = success
    -- 4xx/5xx = delivery failed

    attempt         INT  NOT NULL DEFAULT 1,
    next_retry_at   TIMESTAMPTZ,                       -- null when delivered or exhausted
    delivered_at    TIMESTAMPTZ,

    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_s3_webhook_deliveries_webhook ON s3_webhook_deliveries (webhook_id, created_at DESC);
CREATE INDEX idx_s3_webhook_deliveries_retry   ON s3_webhook_deliveries (next_retry_at)
    WHERE next_retry_at IS NOT NULL AND delivered_at IS NULL;
```

> **Retry policy:** Exponential backoff — 1s, 4s, 16s, 64s, 256s (max 5 attempts). After 5 consecutive failures on a webhook, `s3_webhooks.status` is set to `paused` and an alert email is sent to the account. The customer must manually re-enable the webhook after fixing their endpoint.

---

### `s3_multipart_uploads` *(new v1.1)*

Tracks in-progress multipart uploads across all buckets. Populated and updated by the `multipart-cleanup.py` cron (daily). Stale uploads (older than 48 hours with no activity) are aborted and their storage reclaimed. Surfaced to customers via the usage API.

```sql
CREATE TABLE s3_multipart_uploads (
    id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    s3_account_id       UUID NOT NULL REFERENCES s3_accounts(id),
    s3_bucket_id        UUID NOT NULL REFERENCES s3_buckets(id),

    upload_id           TEXT NOT NULL UNIQUE,          -- SeaweedFS-assigned multipart upload ID
    object_key          TEXT NOT NULL,
    initiated_at        TIMESTAMPTZ NOT NULL,          -- from SeaweedFS ListMultipartUploads

    status              TEXT NOT NULL DEFAULT 'in_progress',
    -- in_progress | completed | aborted | stale
    -- 'stale' = in_progress AND initiated_at < NOW() - INTERVAL '48 hours'

    size_bytes_so_far   BIGINT NOT NULL DEFAULT 0,     -- sum of all uploaded parts so far
    part_count          INT    NOT NULL DEFAULT 0,
    last_activity_at    TIMESTAMPTZ,
    aborted_at          TIMESTAMPTZ,
    aborted_reason      TEXT                           -- 'cleanup_cron' | 'customer' | 'admin'
);

CREATE INDEX idx_s3_multipart_account ON s3_multipart_uploads (s3_account_id, status);
CREATE INDEX idx_s3_multipart_stale   ON s3_multipart_uploads (initiated_at)
    WHERE status = 'in_progress';
```

---

### `s3_worm_commitments` *(new v1.1)*

One row per WORM-enabled bucket, created at the moment the bucket is provisioned. Records the full financial and temporal commitment: the storage quota, the retention period, the price locked in at creation time, and the deposit charged. This row is the source of truth for refund calculations and purge scheduling.

> **Critical:** `price_per_gb_mo` is a snapshot of the price at commitment creation time and must never be updated after the row is inserted. Future price changes must not affect existing commitments.

```sql
CREATE TABLE s3_worm_commitments (
    id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    s3_bucket_id        UUID NOT NULL UNIQUE REFERENCES s3_buckets(id),
    s3_account_id       UUID NOT NULL REFERENCES s3_accounts(id),

    -- Lock parameters (copied from s3_buckets at creation; immutable)
    mode                TEXT NOT NULL,                 -- GOVERNANCE | COMPLIANCE
    retention_days      INT  NOT NULL,
    quota_bytes         BIGINT NOT NULL,               -- max storage quota for this bucket

    -- Pricing snapshot (locked at commitment creation — never updated)
    price_per_gb_mo     NUMERIC(10,6) NOT NULL,        -- $/GB/month at time of commitment
    deposit_amount      NUMERIC(12,2) NOT NULL,        -- total deposit charged upfront

    -- Timeline
    committed_at        TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    locks_until         TIMESTAMPTZ NOT NULL,          -- committed_at + retention_days
    -- Data cannot be purged before this timestamp regardless of account status

    -- Status lifecycle
    status              TEXT NOT NULL DEFAULT 'active',
    -- active      → lock is live, data is protected
    -- cancelled   → account cancelled; deposit partially refunded; awaiting locks_until to purge
    -- expired     → locks_until has passed; data is eligible for purge
    -- purged      → data deleted, commitment closed

    cancelled_at        TIMESTAMPTZ,
    expired_at          TIMESTAMPTZ,
    purged_at           TIMESTAMPTZ,

    created_at          TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_s3_worm_account  ON s3_worm_commitments (s3_account_id);
CREATE INDEX idx_s3_worm_expiring ON s3_worm_commitments (locks_until)
    WHERE status IN ('active', 'cancelled');
```

---

### `s3_deposit_ledger` *(new v1.1)*

Append-only financial ledger for all deposit-related transactions. Every deposit, refund, forfeiture, and release is recorded here with its amount, the reason, and a reference to the payment processor transaction ID. Never updated or deleted.

```sql
CREATE TABLE s3_deposit_ledger (
    id                  BIGSERIAL PRIMARY KEY,
    s3_account_id       UUID NOT NULL REFERENCES s3_accounts(id),
    commitment_id       UUID NOT NULL REFERENCES s3_worm_commitments(id),

    type                TEXT NOT NULL,
    -- 'deposit'     → charged at bucket creation (amount is positive)
    -- 'refund'      → partial refund on voluntary cancellation (amount is negative)
    -- 'forfeiture'  → deposit retained on non-payment cancellation (amount is positive, notes reason)
    -- 'release'     → deposit balance released after lock period expires naturally (amount is negative)

    amount              NUMERIC(12,2) NOT NULL,
    -- positive = money collected from customer
    -- negative = money returned to customer

    -- Refund calculation snapshot (populated for 'refund' and 'release' types)
    days_remaining      INT,                           -- days left on lock at transaction time
    days_total          INT,                           -- total retention_days of commitment

    reference           TEXT,                          -- payment processor transaction ID
    performed_by        TEXT NOT NULL,                 -- 'system' | admin username
    notes               TEXT,                          -- human-readable reason
    performed_at        TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_s3_deposit_commitment ON s3_deposit_ledger (commitment_id);
CREATE INDEX idx_s3_deposit_account    ON s3_deposit_ledger (s3_account_id);
```

> **Refund formula:** `refund_amount = deposit_amount × (days_remaining / days_total)` where `days_remaining = locks_until - cancellation_date` and `days_total = retention_days`. Rounded down to 2 decimal places. The `days_remaining` and `days_total` columns snapshot the values used in the calculation at transaction time for auditing purposes.

---

## 4. Design Decisions

### `components` as JSONB on `s3_servers`

`storaged` sends JSON Merge Patch (RFC 7396) directly to orchestration. Keeping component state as a single JSONB column means the PATCH handler is a trivial `jsonb_merge` with no column mapping required. Orchestration reads individual keys from it when computing the derived `health` field.

If you need to query component state in SQL (e.g. "all servers with `volumes_degraded > 0`"), add generated columns or a GIN index:

```sql
-- Example: generated column for quick degraded volume queries
ALTER TABLE s3_servers
    ADD COLUMN volumes_degraded INT
    GENERATED ALWAYS AS ((components->'volume'->>'volumes_degraded')::int) STORED;
```

### `s3_accounts.slug` as the bucket namespace

Every bucket name in `s3_buckets` must start with the owning account's `slug`. This maps directly to the IAM action pattern in `s3.json`:

```
Read:acme-corp-*
Write:acme-corp-*
```

Validation is enforced at the application layer on bucket creation, not by a database constraint, because the slug comparison needs to be case-insensitive and prefix-aware.

### `iam_keys.bucket_acls` as JSONB

Rather than a separate join table. This mirrors the `full_sync` payload shape from `storaged-design.md` exactly, so `storaged` can consume it without transformation. If your access patterns require querying "which keys have access to bucket X", a separate join table would give cleaner SQL — but for the agent sync path JSONB is simpler and sufficient.

### Two separate time-series tables

`s3_server_telemetry` and `s3_usage_snapshots` are intentionally separate:

| | `s3_server_telemetry` | `s3_usage_snapshots` |
|---|---|---|
| Source | `storaged` telemetry POST | quota-check cron |
| Frequency | Every 30 seconds | Every 15 minutes |
| Scope | Per server (infrastructure) | Per account (tenant) |
| Content | Capacity, volume health | Storage bytes, object count |
| Retention | 30 days recommended | 90 days recommended |

### Object Lock columns are immutable after bucket creation

The S3 specification does not allow enabling Object Lock on an existing bucket. `object_lock_enabled`, `object_lock_mode`, and `object_lock_days` in `s3_buckets` must be set at bucket creation time and must never be updated afterwards. This is enforced at the application layer; no UPDATE should ever touch these columns after the initial INSERT. The `chk_object_lock_mode` CHECK constraint ensures referential integrity between the three columns at the database level.

### `s3_worm_commitments.price_per_gb_mo` is a snapshot, not a foreign key

The price at which a customer committed to a WORM bucket is locked in at that moment. If you raise your pricing later, customers with active WORM commitments must continue to pay the original price. Storing it as a snapshot (not a reference to a pricing table) makes this guarantee structurally impossible to break accidentally.

### `s3_deposit_ledger` is append-only

No row in `s3_deposit_ledger` is ever updated or deleted. It is a legal and financial record. Even if a deposit is disputed and the outcome is reversed, a new row is inserted rather than updating an existing one. This gives you a full audit trail that survives any application bug or accidental update.

### `s3_webhook_deliveries` delivery index

The partial index on `next_retry_at WHERE next_retry_at IS NOT NULL AND delivered_at IS NULL` keeps the retry worker fast — it only scans pending retries, not the full history of millions of delivery rows.

---

## 5. IAM Key Generation

### IAM keys are not SSL keys

These serve completely different purposes and must never be cross-used:

| | IAM Access Keys | SSL/TLS Keys |
|---|---|---|
| **Purpose** | Authenticate S3 API requests | Encrypt transport |
| **Algorithm** | Random bytes — no crypto algorithm | RSA, ECDSA, Ed25519 |
| **Format** | Arbitrary string pair | PEM-encoded key pair + certificate |
| **Used by** | S3 clients (boto3, rclone, AWS SDK) | Nginx, Let's Encrypt / Certbot |

### How the secret key is actually used

IAM secret keys are never sent over the wire. The S3 client uses them as an HMAC-SHA256 signing key (AWS Signature V4). SeaweedFS re-derives the same signature server-side and compares. The secret key just needs to be a high-entropy random string — no specific cryptographic format is required.

### Generation

Use Python's `secrets` module in your provisioning script — it uses `os.urandom()` under the hood, the same entropy source as OpenSSL, with no additional dependency.

```python
import secrets
import string

def generate_access_key(slug: str) -> str:
    """
    Produces: AKIA_{SLUG8}_{RANDOM16}
    Matches the format expected by SeaweedFS s3.json IAM config.
    """
    alphabet = string.ascii_uppercase + string.digits
    random_part = ''.join(secrets.choice(alphabet) for _ in range(16))
    return f"AKIA_{slug[:8].upper()}_{random_part}"

def generate_secret_key() -> str:
    """
    64-character URL-safe base64 string.
    secrets.token_urlsafe(48) → 48 bytes → 64 base64 chars.
    """
    return secrets.token_urlsafe(48)
```

Or from the shell if needed:

```bash
# Access key
echo "AKIA_$(openssl rand -hex 8 | tr '[:lower:]' '[:upper:]')"

# Secret key (64 base64 chars)
openssl rand -base64 48
```

### Storage

Secret keys are stored encrypted in `iam_keys.secret_key_enc`. Use AES-256-GCM with a key sourced from your secrets manager or environment. The plaintext secret is only ever decrypted when:

1. Sending a `full_sync` payload to `storaged` (on agent reconnect)
2. Delivering credentials to the customer on initial provisioning

After provisioning, consider whether you need the plaintext at all. If `storaged` is the SeaweedFS ground truth and you send it on every reconnect, you must retain it. If you can re-provision keys on reconnect, you could store only a hash — but re-provisioning interrupts the customer.

### Where OpenSSL does belong in this stack

OpenSSL is the right tool for two things, neither of which is IAM key generation:

- **Nginx TLS certificates** — handled entirely by Certbot, not manually
- **Application-level encryption** of `secret_key_enc` — done via your language's crypto library (e.g. Python `cryptography`, Go `crypto/aes`), not the OpenSSL CLI

---

## 6. Data Ownership Summary

### What orchestration owns (intent / desired state)

| Table | Intent fields |
|---|---|
| `s3_servers` | `hostname`, `display_name`, `agent_api_key` |
| `s3_accounts` | `slug`, `quota_*`, `status` |
| `s3_buckets` | `name`, `replication_factor`, `lifecycle_rules`, `versioning`, `mfa_delete`, `object_lock_*` |
| `iam_keys` | `access_key`, `secret_key_enc`, `role`, `bucket_acls`, `expires_at` |
| `s3_webhooks` | All columns (customer-configured intent) |
| `s3_worm_commitments` | `mode`, `retention_days`, `quota_bytes`, `price_per_gb_mo`, `deposit_amount`, `locks_until` |

### What `storaged` reports (actual / observed state)

| Table | Observed fields |
|---|---|
| `s3_servers` | `agent_status`, `agent_last_seen_at`, `agent_connected_at`, `agent_version`, `seaweedfs_version`, `components`, `health`, `health_summary` |
| `s3_buckets` | `object_count`, `size_bytes`, `replica_health` |
| `s3_server_telemetry` | All columns (append-only from telemetry POSTs) |

### What the quota-check cron writes

| Table | Written fields |
|---|---|
| `s3_accounts` | `storage_bytes_used`, `egress_bytes_mo_used`, `object_count`, `usage_checked_at`, `blocked_at`, `blocked_reason`, `status` |
| `s3_usage_snapshots` | All columns (append-only) |
| `s3_bandwidth_monthly` | `egress_bytes`, `ingress_bytes` (upsert) |
| `s3_notifications_sent` | All columns (insert on first send) |

### What the event dispatcher writes

| Table | Written fields |
|---|---|
| `s3_webhook_deliveries` | All columns (append-only per delivery attempt) |
| `s3_webhooks` | `failure_count`, `paused_at`, `status` (on repeated failures) |

### What the multipart cleanup cron writes

| Table | Written fields |
|---|---|
| `s3_multipart_uploads` | All columns (upsert from ListMultipartUploads); `status`, `aborted_at`, `aborted_reason` on cleanup |

### What the provisioning layer writes

| Table | Written fields |
|---|---|
| `s3_worm_commitments` | All columns at bucket creation; `status`, `cancelled_at`, `expired_at`, `purged_at` on lifecycle transitions |
| `s3_deposit_ledger` | All columns (append-only per financial transaction) |

### What `storaged` never stores locally

Per the agent design principles:

- Bucket or IAM intent — always re-fetched from `full_sync` on reconnect
- Historical metrics — no local time-series, no local database
- SeaweedFS internal repair or rebalance state

---

*End of Document*

---

> **Document Owner:** Infrastructure Team  
> **Companion Documents:** `seaweedfs-s3-service-standard.md`, `storaged-design.md`  
> **Repository:** `git@yourdomain.com:infra/s3-service-standard.git`
