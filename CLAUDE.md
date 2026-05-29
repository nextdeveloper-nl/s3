# S3 Service — Project Foundation

## What This Project Is

A self-hosted, multi-tenant S3-compatible object storage service built on **SeaweedFS**, managed through a custom orchestration architecture. The service provides tenant-isolated S3 storage with quota enforcement, IAM key management, and infrastructure health observability.

Two companion agents run on each storage server:
- **`storaged`** — S3 bucket management + SeaweedFS health observation (this repo's focus)
- **`nfsd`** — NFS export and VM disk management (separate, no IPC with storaged)

---

## Architecture Overview

```
Orchestration Service (central)
    │  SSE push (commands)       ▲ telemetry / ack / PATCH
    ▼                            │
Storage Server
    ├── storaged (Go agent)      ← this codebase
    │       ├── S3 Manager       → SeaweedFS S3 gateway :8333
    │       └── SeaweedFS Observer → master :9333, filer :8888
    └── SeaweedFS cluster
            master  :9333
            volume  :8080
            filer   :8888
            s3      :8333  (bound to 127.0.0.1 only)
            └── Nginx TLS :443 (public-facing proxy)
```

Orchestration is the **single source of intent**. `storaged` is a **stateless executor**: no local DB, no persistent disk state. All desired state arrives via SSE `full_sync` on every connect; all observed state is pushed back immediately.

---

## Key Design Invariants

- `storaged` is **read-only toward SeaweedFS internals** — never calls repair or rebalance endpoints
- **PATCH on change, not on tick** — telemetry heartbeat every 30s; PATCH only when a value crosses a threshold or a `reachable` boolean flips
- **SeaweedFS S3 gateway binds to 127.0.0.1 only** — Nginx is the only allowed public entrypoint
- **Never restart `weed-s3` for IAM changes** — always `systemctl reload weed-s3`
- **Object Lock columns are immutable after bucket creation** — enforced at the application layer
- `s3_deposit_ledger` and `s3_audit_log` are **append-only** — never update or delete rows

---

## Communication Model

### Orchestration → storaged (commands)
SSE stream: `GET /v1/agents/{id}/stream`  
Auth: `X-Agent-Key: {api_key}` on every request, TLS required

| SSE type | Description |
|---|---|
| `full_sync` | Complete desired state on every connect — apply idempotently |
| `bucket_create` / `bucket_delete` / `bucket_update` | Bucket lifecycle |
| `iam_create` / `iam_delete` | IAM user lifecycle |
| `reconcile` | Force reconcile of `buckets`, `iam`, or `all` |

### storaged → Orchestration
| Channel | Endpoint | Frequency |
|---|---|---|
| Telemetry | `POST /v1/agents/{id}/telemetry` | Every 30s |
| Ack | `POST /v1/agents/{id}/ack` | Per command |
| State change | `PATCH /v1/s3-servers/{id}` | On threshold cross |

**Reconnect backoff:** 1s → 2s → 4s → 8s → max 60s (jittered). Resets after successful telemetry ack.  
**Dead agent detection:** orchestration marks `unknown` if no telemetry for >90s (3 missed ticks).

### PATCH triggers (immediate, not on tick)
- Any `reachable` boolean flips
- `volumes_degraded` crosses 0
- `capacity_pct` crosses 80% (warn) or 90% (critical)
- `volumes_readonly` increases unexpectedly

---

## Database Schema (PostgreSQL 16)

All tables prefixed `s3_`, `iam_`, or `worm_`. Schema version: **v1.1**.

| Table | Owner | Purpose |
|---|---|---|
| `s3_servers` | Orchestration + storaged | Physical storage servers; `components` JSONB written by storaged via JSON Merge Patch |
| `s3_accounts` | Orchestration + quota cron | Tenant subscriptions; quota limits and live usage cache |
| `s3_buckets` | Orchestration + storaged telemetry | Buckets; intent owned by orchestration, observed state by storaged |
| `iam_keys` | Orchestration | Access key pairs; `secret_key_enc` AES-256-GCM encrypted at rest |
| `s3_server_telemetry` | storaged | 30s health snapshots; append-only; 30-day retention |
| `s3_usage_snapshots` | Quota cron | 15-min per-tenant usage; 90-day retention |
| `s3_bandwidth_monthly` | Nginx log parser | Egress/ingress per tenant per month |
| `s3_notifications_sent` | Quota cron | Email dedup; unique on `(account, notification, month)` |
| `s3_audit_log` | All writers | Immutable admin + system action log |
| `s3_webhooks` | Customer / orchestration | Customer webhook subscriptions |
| `s3_webhook_deliveries` | Event dispatcher | Per-event delivery log; exponential backoff retry |
| `s3_multipart_uploads` | Multipart cleanup cron | In-progress multipart upload tracking; stale after 48h |
| `s3_worm_commitments` | Provisioning layer | WORM lock: mode, retention, pricing snapshot (immutable after creation) |
| `s3_deposit_ledger` | Provisioning layer | Financial ledger; append-only; deposit / refund / forfeiture / release |

### Key schema rules
- `s3_accounts.slug` is the mandatory bucket name prefix (`slug-*`) — enforced at app layer
- `s3_worm_commitments.price_per_gb_mo` is a price snapshot — never update it
- `object_lock_enabled/mode/days` in `s3_buckets` cannot change after creation
- `iam_keys.bucket_acls` is JSONB, mirroring `full_sync` payload shape exactly

---

## Quota Enforcement

Soft enforcement model: customers can temporarily exceed quota between check cycles.

| Cron | Schedule | Script |
|---|---|---|
| Quota check + block enforcement | Every 15min | `quota-check.py` |
| Nginx log bandwidth parser | Every hour | `bandwidth-parse.py` |
| Monthly bandwidth reset | 1st of month 00:05 | `bandwidth-reset.py` |
| Daily usage report | 07:00 | `daily-report.py` |

**Blocking:** adds customer key to `/etc/nginx/conf.d/s3_blocked_keys.conf`, reloads Nginx, updates DB status to `blocked`.  
**Unblocking:** always manual — customers are never auto-unblocked.  
**Warning emails:** sent at 80% (once per threshold per month) via `s3_notifications_sent` dedup table.

---

## IAM Key Generation

```python
import secrets, string

def generate_access_key(slug: str) -> str:
    alphabet = string.ascii_uppercase + string.digits
    random_part = ''.join(secrets.choice(alphabet) for _ in range(16))
    return f"AKIA_{slug[:8].upper()}_{random_part}"

def generate_secret_key() -> str:
    return secrets.token_urlsafe(48)  # 64 base64 chars
```

Secret keys use HMAC-SHA256 (AWS Signature V4) — they are high-entropy random strings, not SSL keys.  
Stored encrypted in `iam_keys.secret_key_enc` (AES-256-GCM). Plaintext only decrypted for `full_sync` delivery to storaged and initial provisioning.

---

## Storage Layout

```
/data/seaweedfs/master/      # master metadata
/data/seaweedfs/filer/       # filer metadata (leveldb)
/data/seaweedfs/volumes/     # object data (bulk)
/export/vms/                 # NFS exports (nfsd, not storaged)
/etc/seaweedfs/s3.json       # IAM config (managed by scripts, 640 perms)
/etc/nginx/conf.d/s3_blocked_keys.conf  # auto-managed by quota cron
/etc/s3quota/config.toml     # quota script config (600 perms)
```

SeaweedFS and NFS data must **never share a filesystem mount point**.

---

## Security Standards

- All SeaweedFS ports (8080, 8333, 8888, 9333) firewalled from public access
- S3 gateway binds to `127.0.0.1` only — Nginx is the sole public entry
- TLS 1.2+ minimum, HSTS enabled, `server_tokens off`
- All components run as `seaweedfs` user (non-root, no login shell)
- PostgreSQL listens on localhost only; `quotauser` has no DDL privileges
- Secret keys in `s3.json` rotated every 90 days

---

## Competitive Context

| Differentiator | Status |
|---|---|
| Standard S3 API (no lock-in) | Live |
| Data sovereignty (self-hosted) | Live |
| 80% warning before blocking | Live |
| Full-stack support visibility | Live |
| Self-serve usage dashboard | **Not built — #1 missing feature** |
| Server 2 / replication active | **Not live — single-server data loss risk** |
| Client setup documentation | **Needed** |

## Known Support Risk Areas

1. **Quota block UX** — blocked customers see `403` without context. Block email quality is the primary lever on ticket volume.
2. **No self-serve usage dashboard** — tickets grow linearly with customer count until this exists.
3. **Single-server data loss** — must be explicit in customer SLAs.
4. **Bucket naming constraint** (`slug-*` prefix) — non-obvious at onboarding; must lead every setup guide.
5. **Secret key non-recovery** — loss forces rotation and breaks all integrations; must be explicit at provisioning.

---

## Document Map

| File | Purpose |
|---|---|
| `docs/storaged-design.md` | storaged agent architecture, SSE protocol, PATCH rules, data ownership |
| `docs/seaweedfs-s3-service-standard.md` | Full infrastructure standard: stack, nginx config, IAM, quota, security, scaling |
| `docs/s3-database-schema-design-v1.1.md` | Complete PostgreSQL schema (current version) |
| `docs/s3-database-schema-design.md` | v1.0 schema (historical reference) |
| `docs/s3-market-and-support-analysis.md` | Market pain points, expected support categories, competitive positioning |
