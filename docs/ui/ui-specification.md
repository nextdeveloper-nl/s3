# S3 Module — UI Specification

**Purpose:** This document gives a Claude instance (or any frontend developer) everything needed to build the S3 module UI. It covers every screen, the API endpoint behind it, the exact fields returned, the forms to build, and the business rules to enforce.

---

## 1. System Context

The S3 module is a PlusCloud-hosted object storage service backed by SeaweedFS. The API prefix for all endpoints is `/s3/`.

All IDs in API requests and responses are UUIDs, named `*_id` (not `*_uuid`).

There are two user personas:

- **Platform Admin** — can see all servers, all accounts, all buckets; can block/unblock accounts; can set server pricing.
- **Customer** — sees only their own S3 account, buckets, and access keys via `*-perspective` endpoints.

The UI should render both views. Use `*-perspective` endpoints for customer-facing pages.

---

## 2. Navigation Structure

> **Backup Agents** (registration, jobs, run history) has its own companion spec —
> see [`backup-agent-ui-specification.md`](backup-agent-ui-specification.md) —
> since it's a large enough feature area to document separately. It sits in this
> same nav tree, between Access Keys and Usage & Billing.

```
S3 Module
├── Dashboard (account stats + quick actions)
├── Buckets
│   ├── List
│   └── Bucket Detail
│       ├── WORM Commitment (if WORM bucket)
│       └── Audit Logs (if audit enabled)
├── Access Keys
│   ├── List
│   └── Create
├── Backup Agents (see backup-agent-ui-specification.md)
├── Usage & Billing
│   ├── Bandwidth (monthly)
│   ├── Deposit Ledger (WORM billing)
│   └── Usage Snapshots
├── Webhooks
│   ├── List
│   └── Webhook Deliveries
└── [Admin Only]
    ├── Servers
    │   ├── List
    │   └── Server Detail (capacity, telemetry)
    ├── Accounts (all customers)
    └── Audit Logs (all)
```

---

## 3. API Base URLs

| Resource | Customer Endpoint | Admin Endpoint |
|---|---|---|
| Account summary | `GET /s3/accounts-perspective` | `GET /s3/accounts` |
| Account stats | `GET /s3/account-stats` | — |
| Buckets | `GET /s3/buckets-perspective` | `GET /s3/buckets` |
| Access Keys | `GET /s3/access-keys-perspective` | `GET /s3/access-keys` |
| WORM Commitments | `GET /s3/worm-commitments` | same |
| WORM Expiring | `GET /s3/worm-expiring-perspective` | same |
| Audit Logs | `GET /s3/audit-logs` | same |
| Deposit Ledger | `GET /s3/deposit-ledger` | same |
| Bandwidth | `GET /s3/bandwidth-monthly` | same |
| Usage Snapshots | `GET /s3/usage-snapshots` | same |
| Servers | — | `GET /s3/servers` |
| Server Capacity | — | `GET /s3/server-capacity-stats` |
| Webhooks | `GET /s3/webhooks` | same |
| Webhook Deliveries | `GET /s3/webhook-deliveries` | same |

All list endpoints support query-string filtering by any field. All single-record endpoints: `GET /s3/{resource}/{uuid}`.

Actions (non-CRUD operations) are dispatched via: `POST /s3/{resource}/{uuid}/do/{action}`

---

## 4. Dashboard

**Endpoint:** `GET /s3/account-stats` (returns one record per S3 account)

**Fields to display:**

| Field | Display label | Notes |
|---|---|---|
| `storage_bytes_used` | Storage Used | Format as human-readable (GB/TB) |
| `quota_storage_bytes` | Storage Quota | |
| `storage_pct` | — | Use for progress bar (0–100) |
| `egress_bytes_mo_used` | Egress This Month | |
| `quota_egress_bytes_mo` | Egress Quota | |
| `egress_pct` | — | Progress bar |
| `object_count` | Objects | |
| `quota_max_objects` | Object Quota | |
| `object_pct` | — | Progress bar |
| `bucket_count` | Buckets | |
| `quota_max_buckets` | Bucket Quota | |
| `bucket_pct` | — | Progress bar |
| `active_key_count` | Active Access Keys | |
| `in_progress_upload_count` | In-Progress Uploads | Badge, warn if > 0 |
| `paused_webhook_count` | Paused Webhooks | Badge, warn if > 0 |
| `current_month_egress_bytes` | Current Month Egress | |
| `current_month_ingress_bytes` | Current Month Ingress | |
| `status` | Account Status | Pill: `active` = green, `blocked` = red |
| `blocked_reason` | Block reason | Show only when status = `blocked` |

**Quick actions on dashboard:**
- "Create Bucket" → opens bucket create form
- "Create Access Key" → opens access key create form

---

## 5. Buckets

### 5.1 Bucket List

**Customer endpoint:** `GET /s3/buckets-perspective`
**Admin endpoint:** `GET /s3/buckets`

**Columns:**

| Field | Label | Notes |
|---|---|---|
| `name` | Name | Link to bucket detail |
| `size_bytes` / `size_gb` | Size | Show in GB (perspective has `size_gb`) |
| `object_count` | Objects | |
| `status` | Status | Pill |
| `versioning` | Versioning | `Enabled` / `Suspended` |
| `object_lock_enabled` | WORM | Icon/badge if true |
| `worm_status` | WORM Status | From perspective: `active`, `expired`, `none` |
| `worm_locks_until` | WORM Locked Until | Show when WORM active |
| `replica_health` | Replica Health | `healthy` = green, `degraded` = yellow, `unknown` = grey |
| `active_webhook_count` | Webhooks | Count |
| `created_at` | Created | |

**Filters (available in UI):**
- Status (`active`, `deleted`)
- Object Lock Enabled (toggle)
- Account (admin only, filter by `s3_account_id`)

### 5.2 Create Bucket

**Endpoint:** `POST /s3/buckets`

**Form fields:**

| Field | Input type | Validation |
|---|---|---|
| `name` | text | required; 3–63 chars; lowercase alphanumeric and hyphens only; cannot start/end with hyphen |
| `s3_account_id` | select / hidden | required; UUID of the S3 account |
| `s3_server_id` | select (admin) | required; UUID of the server |
| `versioning` | select | options: `Enabled`, `Suspended` (default: `Suspended`) |
| `replication_factor` | number | optional; default 1 |
| `is_object_audit_enabled` | toggle | Enable object-level audit logging (optional add-on) |
| `object_lock_enabled` | toggle | **WORM bucket** — cannot be changed after creation |
| `object_lock_mode` | select | shown only when WORM enabled; options: `COMPLIANCE`, `GOVERNANCE`; default `COMPLIANCE` |
| `object_lock_days` | number | shown only when WORM enabled; minimum 1; default 1 |

**Business rules:**
- `object_lock_enabled` is **immutable** after creation. The form must make this clear (e.g. "This cannot be changed after the bucket is created.").
- COMPLIANCE mode means the retention period can only be extended, never shortened. Warn the user.
- GOVERNANCE mode allows cancellation with a pro-rata refund.
- Creating a WORM bucket automatically creates a WORM Commitment entry. Initial deposit is $0 (charges accumulate per upload).
- If the account has reached `quota_max_buckets`, show an error before submitting.

**Success:** Navigate to the new bucket's detail page.

### 5.3 Bucket Detail

**Endpoint:** `GET /s3/buckets/{id}` or `GET /s3/buckets-perspective/{id}`

**Display sections:**

**Overview panel:**
- Name, Status, Size (bytes formatted), Object Count
- Versioning, Replication Factor, Replica Health
- Audit Enabled badge
- Created At, Updated At

**WORM panel** (show only when `object_lock_enabled = true`):
- Mode (`COMPLIANCE` / `GOVERNANCE`) with explanation tooltip
- Retention Days
- WORM Locked Until (`worm_locks_until`)
- WORM Status (`worm_status`)
- Link to "WORM Commitment" (fetches from `/s3/worm-commitments?s3_bucket_id={id}`)

**Actions:**
- **Edit** (PATCH `/s3/buckets/{id}`) — opens edit form (see 5.4)
- **Delete** (DELETE `/s3/buckets/{id}`) — confirmation required; blocked if COMPLIANCE commitment is active (API returns 403 with message)
- **View Audit Logs** — navigates to audit logs filtered by this bucket

### 5.4 Edit Bucket

**Endpoint:** `PATCH /s3/buckets/{id}`

**Editable fields:**
- `versioning` (if not WORM)
- `replication_factor`
- `is_object_audit_enabled`
- `object_lock_mode` (WORM buckets only — can be changed)
- `object_lock_days` (WORM buckets only — COMPLIANCE can only increase)
- `lifecycle_rules` (JSON editor, advanced)

**Not editable:** `name`, `object_lock_enabled`, `s3_server_id`, `s3_account_id`

**Business rules on WORM edit:**
- If mode is `COMPLIANCE`: warn that reducing `object_lock_days` will be rejected.
- If the edit changes the WORM policy, the platform supersedes the old commitment and creates a new one automatically (no UI action needed).

### 5.5 Delete Bucket

Show a confirmation dialog. Include this warning when `object_lock_enabled = true`:
> "If this bucket has an active COMPLIANCE commitment, deletion will be blocked until the retention period expires. GOVERNANCE commitments will be cancelled with a pro-rata refund."

On API 403, display the error message from the response.

---

## 6. Access Keys

See `updates/2026-07-05-access-key-name-and-bucket-acls.md` for the background on
why `name` and bucket-scoped ACLs are new here — short version: every key's IAM
identity name came back blank until now, and every key created through this form
has always silently gotten **full, unrestricted access to every bucket on the
account**, because the create form never sent `bucket_acls` at all. Both are fixed
below.

### 6.1 Access Key List

**Customer endpoint:** `GET /s3/access-keys-perspective`
**Admin endpoint:** `GET /s3/access-keys`

**Columns:**

| Field | Label | Notes |
|---|---|---|
| `name` | Name | Falls back to showing `access_key` if blank (older keys created before this field existed) |
| `access_key` | Access Key ID | Monospace font |
| `role` | Role | `full_access`, `read_only`, etc. |
| — | **Scope** | Not a raw column — derive from `bucket_acls`: empty/null → pill "All buckets" (amber — call out that this is broad access, not the safe default); non-empty → "N bucket(s)" with the bucket names in a tooltip |
| `status` | Status | Pill: `active` green, `revoked` red |
| `expires_at` | Expires | Show "Never" if null |
| `last_used_at` | Last Used | Relative time |
| `created_at` | Created | |

**Actions per row:**
- **Revoke** → `POST /s3/access-keys/{id}/do/revoke` (confirm dialog)
- **Copy Access Key** → copies `access_key` to clipboard

### 6.2 Create Access Key

**Endpoint:** `POST /s3/access-keys`

**Form:**

| Field | Input | Notes |
|---|---|---|
| `s3_account_id` | hidden / select | Required |
| `name` | text | Optional but strongly encouraged — label the key by what it's for (e.g. "Jenkins CI uploads"). Falls back to the access key string itself if left blank, better than the previous always-blank behavior but still worth prompting for |
| `role` | select | Options: `full_access`, `read_only` (and any others from existing data) |
| **Bucket access** | radio: **All buckets** / **Specific buckets** | See below — this is the ACL picker, net-new |
| `expires_at` | date picker | Optional; leave blank = never expires |

**Bucket access picker (the new part):**

- Default to **"All buckets"** selected, matching today's actual (if previously
  invisible) behavior — sends `bucket_acls: []` or omits it. Label this option
  clearly: *"This key can read/write every bucket in this account."* Don't hide
  that this is broad access — make it a visible, deliberate choice rather than
  an invisible default the way it's been until now.
- **"Specific buckets"** reveals a repeatable row: bucket select
  (`GET /s3/buckets-perspective` for options) + permission select
  (`Read only` / `Read & write` / `Admin`), Add-another-bucket button.
- On submit, build `bucket_acls` as an array of
  `{ "bucket_id": "<bucket_name>", "permission": "r" | "rw" | "admin" }`
  objects — **`bucket_id` takes the bucket's *name*, not its UUID**, despite
  the field name (confirmed against the storage agent's actual IAM config).
  Getting this wrong silently produces a key that exists but can't access
  anything — validate that every row has both a bucket and a permission
  selected before allowing submit, and don't let the same bucket appear twice
  in the list.

**Post-creation:** Show the `secret_key_enc` (decrypted secret key) **once** in
a "copy now" dialog — it will not be shown again. Also show the `access_key`
value.

### 6.3 Edit Access Key

**Endpoint:** `PATCH /s3/access-keys/{id}`

**Editable in the UI:** `name` only.

**Do not expose `role` or `bucket_acls` as editable, even though the API
technically accepts them on PATCH.** There is no mechanism that pushes a
changed role/ACL out to the already-created identity on the storage agent —
only `iam_create`/`iam_delete` exist, there's no `iam_update`. A PATCH that
changes `bucket_acls` would silently update our database record while the
real, live SeaweedFS identity keeps its original permissions forever,
producing exactly the kind of "looks right in the dashboard, wrong in
reality" mismatch this whole project exists to avoid. If a customer needs
different bucket access, the correct flow is **revoke this key and create a
new one** with the right scope — make that the only path the UI offers,
rather than a PATCH that appears to work but doesn't.

---

## 7. WORM Commitments

### 7.1 WORM Commitment List

**Endpoint:** `GET /s3/worm-commitments`

Can be reached from a bucket's detail page (pre-filtered) or as a standalone admin list.

**Columns:**

| Field | Label | Notes |
|---|---|---|
| `s3_bucket_id` | Bucket | Resolve to bucket name via relationship |
| `mode` | Mode | `COMPLIANCE` / `GOVERNANCE` pill |
| `retention_days` | Retention | "X days" |
| `quota_bytes` | Data Stored | Format as human-readable |
| `price_per_gb_mo` | Price/GB/mo | Show currency if `common_currency_id` set |
| `deposit_amount` | Total Deposited | 6 decimal places |
| `committed_at` | Committed | |
| `locks_until` | Locked Until | Highlight in red if past |
| `status` | Status | Pill: `active` green, `expired` grey, `cancelled` orange, `superseded` blue, `purged` dark |

**Detail view fields** (same fields, plus):
- `cancelled_at`, `expired_at`, `purged_at`
- List of related deposit ledger entries (`GET /s3/deposit-ledger?s3_worm_commitment_id={id}`)

**Actions:**
- **Cancel** — only shown when `status = active` AND `mode = GOVERNANCE`.
  - Endpoint: `POST /s3/worm-commitments/{id}/do/cancel`
  - Show estimated refund in the confirmation dialog (call `GET /s3/worm-commitments/{id}` to read `deposit_amount` and calculate client-side: `deposit × (days_remaining / retention_days)`)
  - COMPLIANCE commitments must NOT show the cancel button.

### 7.2 WORM Expiring Soon

**Endpoint:** `GET /s3/worm-expiring-perspective`

Show as a dashboard widget or a separate tab. Lists commitments expiring in the next 7 days.

---

## 8. Deposit Ledger

**Endpoint:** `GET /s3/deposit-ledger`

Read-only billing trail. No create/edit/delete actions in the UI.

**Columns:**

| Field | Label | Notes |
|---|---|---|
| `type` | Type | `deposit` = green, `refund` = orange |
| `amount` | Amount | 6 decimal places; show currency |
| `days_remaining` | Days Remaining | At time of event |
| `days_total` | Retention Days | |
| `reference` | Reference | e.g., WORM commitment UUID |
| `notes` | Notes | |
| `performed_at` | Date | |

Provide filters: `type`, `s3_account_id`, date range.

---

## 9. Audit Logs

**Endpoint:** `GET /s3/audit-logs`

Read-only. No actions.

**Columns:**

| Field | Label | Notes |
|---|---|---|
| `action` | Action | e.g., `worm.create`, `PUT`, `DELETE`, `worm.cancel`, `worm.purge` |
| `performed_by` | Performed By | UUID of user or `system` |
| `s3_bucket_id` | Bucket | |
| `s3_access_key_id` | Access Key | |
| `reason` | Reason | |
| `data` | Details | Collapsible JSON view |
| `performed_at` | Time | |

**Important action types:**

| Action | Meaning |
|---|---|
| `PUT` | Object uploaded (from agent S3 audit) |
| `DELETE` | Object deleted (from agent S3 audit) |
| `worm.create` | WORM commitment created |
| `worm.cancel` | WORM commitment cancelled |
| `worm.purge` | WORM commitment purged (system lifecycle) |

The `data` JSON field for `PUT`/`DELETE` entries contains: `object_key`, `size_bytes`, `retain_until`, `client_ip`.

Provide filters: `action`, `s3_bucket_id`, `iam_account_id`, date range.

---

## 10. Bandwidth (Monthly)

**Endpoint:** `GET /s3/bandwidth-monthly`

Read-only.

**Columns:**

| Field | Label | Notes |
|---|---|---|
| `month` | Month | Format as `YYYY-MM` |
| `egress_bytes` | Egress | Human-readable |
| `ingress_bytes` | Ingress | Human-readable |
| `updated_at` | Last Updated | |

Optionally show as a bar chart (egress vs ingress per month).

---

## 11. Usage Snapshots

**Endpoint:** `GET /s3/usage-snapshots`

Daily snapshots of account-level storage usage.

**Endpoint (daily granularity):** `GET /s3/usage-daily-stats`

Use these to render a storage usage time-series chart on the dashboard or a dedicated page.

---

## 12. Webhooks

### 12.1 Webhook List

**Endpoint:** `GET /s3/webhooks`

**Columns:** (read from `WebhooksTransformer`) — URL, events subscribed, status (active/paused), created_at.

**Actions:**
- Create webhook: `POST /s3/webhooks`
- Edit: `PATCH /s3/webhooks/{id}`
- Delete: `DELETE /s3/webhooks/{id}`

### 12.2 Webhook Deliveries

**Endpoint:** `GET /s3/webhook-deliveries`

Filter by `s3_webhook_id`. Show: event type, delivery status (success/failed), HTTP response code, attempted_at, next_retry_at.

---

## 13. Servers (Admin Only)

### 13.1 Server List

**Endpoint:** `GET /s3/servers`

**Columns:**

| Field | Label | Notes |
|---|---|---|
| `name` | Name | Link to detail |
| `hostname` | Hostname | |
| `agent_status` | Agent | `connected` green, `disconnected` red |
| `health` | Health | `healthy`, `degraded`, `unknown` |
| `seaweedfs_version` | Version | |
| `agent_last_seen_at` | Last Seen | Relative time |
| `price_per_gb` | Price/GB/mo | |
| `created_at` | Created | |

**Actions:**
- Create server: `POST /s3/servers`
- Edit (PATCH) — update `price_per_gb`, `common_currency_id`, `name`
- View capacity stats → links to `/s3/server-capacity-stats?s3_server_id={id}`

### 13.2 Server Capacity Stats

**Endpoint:** `GET /s3/server-capacity-stats`

**Fields:**

| Field | Label | Notes |
|---|---|---|
| `capacity_bytes_total` / `capacity_gb_total` | Total Capacity | |
| `capacity_bytes_used` / `capacity_gb_used` | Used | |
| `capacity_pct` | Used % | Progress bar |
| `volume_count` | Volumes | |
| `volumes_degraded` | Degraded Volumes | Warn if > 0 |
| `hosted_bucket_count` | Buckets Hosted | |
| `hosted_account_count` | Accounts Hosted | |
| `master_reachable` | Master Reachable | Boolean badge |
| `minutes_since_last_report` | Last Report | Warn if > 5 |

### 13.3 Customer Account List (Admin)

**Endpoint:** `GET /s3/accounts`

**Columns:** slug, status, storage_bytes_used, quota_storage_bytes, bucket_count (derived), blocked_at.

**Actions:**
- Block account: `POST /s3/accounts/{id}/do/block` (prompt for reason)
- Unblock account: `POST /s3/accounts/{id}/do/unblock`
- View account stats: link to `GET /s3/account-stats?s3_account_id={id}`

---

## 14. Forms — Common Patterns

### Byte size display

Always format bytes as human-readable: B → KB → MB → GB → TB.

Use `1 GB = 1,073,741,824 bytes` (binary, matching server-side calculations).

### Currency amounts

Display deposit/refund amounts to 6 decimal places minimum (e.g., `$0.000767`).

### Status pills

| Value | Color |
|---|---|
| `active` | Green |
| `blocked` | Red |
| `revoked` | Red |
| `cancelled` | Orange |
| `expired` | Grey |
| `superseded` | Blue |
| `purged` | Dark grey |
| `degraded` | Yellow |
| `unknown` | Grey |
| `connected` | Green |
| `disconnected` | Red |

### Date/time display

- Timestamps: use local timezone.
- Future dates (e.g., `locks_until`): show as "in X days" with absolute date on hover.
- Past dates: show as "X days ago" with absolute date on hover.

---

## 15. Key Business Rules for the UI

1. **WORM bucket creation is irreversible.** The form must warn: "Object Lock cannot be disabled after the bucket is created."

2. **COMPLIANCE commitments cannot be cancelled or shortened.** Hide the "Cancel" button. The delete button on the bucket should be disabled with tooltip: "Active COMPLIANCE commitment expires on {locks_until}."

3. **GOVERNANCE commitments can be cancelled.** Show a cancel button on the commitment detail page with a refund estimate.

4. **Access key secret is shown exactly once.** After creating an access key, display a modal with the secret key and a "Copy" button. Warn: "This secret will not be shown again."

5. **Account blocking:** When an admin blocks an account, a `customer_block` command is sent to all connected agents, which suspends all IAM keys on the SeaweedFS side. The UI should reflect `status = blocked` with `blocked_at` and `blocked_reason`.

6. **Audit log toggle:** `is_object_audit_enabled` on a bucket controls whether the agent emits per-object PUT/DELETE events. This is an optional paid add-on. When disabled, the Audit Logs tab for that bucket should show: "Object-level audit logging is not enabled for this bucket."

7. **Deposit ledger is append-only.** No edit or delete in the UI. Each WORM PUT event creates a deposit row; each GOVERNANCE cancellation creates a refund row.

8. **Bucket name rules:** 3–63 characters, lowercase letters, numbers, and hyphens only. Cannot start or end with a hyphen. Validate on the client before submit.

---

## 16. Error Handling

The API returns structured errors. Common HTTP codes:

| Code | Meaning | UI action |
|---|---|---|
| 403 | Not allowed (COMPLIANCE lock, quota exceeded, etc.) | Show error message from response body |
| 404 | Not found | Show "not found" state |
| 422 | Validation error | Show field-level errors |
| 500 | Server error | Show generic error toast |

Error response shape: `{ "message": "...", "errors": { "field": ["message"] } }`

---

## 17. Multipart Uploads

**Endpoint:** `GET /s3/multipart-uploads`

Show in-progress multipart uploads per bucket. Provide a way to cancel stale ones (`DELETE /s3/multipart-uploads/{id}` or via action). Surface the count on the bucket detail and dashboard.

---

## 18. Notifications Sent

**Endpoint:** `GET /s3/notifications-sent`

Audit trail of system notifications (e.g., WORM expiry warnings, quota alerts). Read-only list.

---

## 19. Implementation Priority Order

Build in this order:

1. **Dashboard** — account stats with quota bars
2. **Bucket List + Create** — core workflow
3. **Access Key List + Create** (with one-time secret display)
4. **Bucket Detail** + Edit + Delete
5. **WORM Commitment detail** + Cancel (GOVERNANCE)
6. **Audit Logs viewer**
7. **Deposit Ledger**
8. **Bandwidth / Usage charts**
9. **Webhooks**
10. **Admin: Servers + Capacity**
11. **Admin: Customer Accounts + Block/Unblock**
