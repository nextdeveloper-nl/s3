# S3 Object Storage — Feature Overview

**Version:** 1.0
**Status:** Draft
**Audience:** Website / marketing copy — customer-facing feature summary
**Companion documents:** `seaweedfs-s3-service-standard.md` (architecture), `s3-market-and-support-analysis.md` (positioning), `client-setup-guide.md` (setup), `ui/*.md` (panel screens), `backup.agent/overview.md` (backup agent)

---

## Table of Contents

1. [Overview](#1-overview)
2. [Core Object Storage](#2-core-object-storage)
3. [Access & Security](#3-access--security)
4. [Immutability & Compliance (WORM)](#4-immutability--compliance-worm)
5. [Usage, Quotas & Transparent Billing](#5-usage-quotas--transparent-billing)
6. [Automated Backups](#6-automated-backups)
7. [Integrations & Automation](#7-integrations--automation)
8. [Why Customers Choose Us](#8-why-customers-choose-us)

---

## 1. Overview

Our S3 service is an S3-compatible object storage platform, built on SeaweedFS,
that any standard S3 tool or SDK can talk to without code changes. It combines
familiar, standards-based storage with the things customers consistently say
generic cloud storage gets wrong: predictable billing, real usage visibility,
and support from a team that can see the whole stack.

---

## 2. Core Object Storage

- **Standard S3 API** — works with the S3 tools customers already use:
  rclone, boto3/Python, Cyberduck, S3 Browser, Mountain Duck, WinSCP,
  s3fs-fuse, Veeam, restic, aws-cli, and more.
- **Bucket management** — create, browse, edit, and delete buckets from the
  dashboard, each scoped to the owning account.
- **Multipart uploads** for large files, handled automatically by supported
  clients.
- **No vendor lock-in** — because the API is standard S3, customers can move
  data in or out with any S3-compatible tool at any time. No proprietary
  format, no migration tooling required.
- **Data sovereignty** — data is stored on infrastructure we operate, in the
  datacenter the customer chooses, rather than an anonymous multi-region pool.

---

## 3. Access & Security

- **Scoped IAM access keys** — every access key is generated per account and
  restricted to that account's own buckets. Secret keys are shown once at
  creation and encrypted at rest (AES-256-GCM); they are never stored or
  displayed in plaintext again.
- **Bucket isolation** — bucket names are namespaced to the owning account, so
  one customer's credentials can never reach another customer's data.
- **Full audit trail** — every object upload and delete, plus every WORM and
  administrative action, is recorded with who performed it, when, and from
  which access key or IP — searchable from the dashboard.

---

## 4. Immutability & Compliance (WORM)

For customers who need tamper-proof retention — compliance archives, backup
targets, ransomware-resistant copies:

- **Object Lock / WORM commitments** on a bucket, with a defined retention
  period.
- **Two modes:** `GOVERNANCE` (cancellable by an authorized admin, with a
  pro-rated refund) and `COMPLIANCE` (locked for the full retention period,
  no early cancellation — for regulatory requirements).
- **Transparent deposit-based pricing** — WORM retention is billed as a
  deposit up front, with a full ledger of deposits and refunds available to
  the customer at any time.
- **Expiry visibility** — a dedicated view shows commitments expiring in the
  next 7 days, so nothing lapses unexpectedly.

---

## 5. Usage, Quotas & Transparent Billing

Billing surprises are the single biggest complaint customers have about
object storage elsewhere. Our model is built to avoid that:

- **Self-serve usage dashboard** — current storage, object count, and
  egress/bandwidth usage, plus historical usage snapshots and monthly
  bandwidth charts, all visible any time — no waiting for a monthly
  statement.
- **Soft quota enforcement with advance warning** — customers are warned by
  email at 80% of their quota, well before anything is restricted. Nothing is
  blocked without warning.
- **Flexible storage packages, per server** — every server offers a
  zero-config Pay-As-You-Go tier automatically, plus optional flat-fee
  packages with an included storage allowance for predictable monthly costs.
  Usage beyond the included allowance is simply billed at the package's
  overage rate for that month — **it never blocks the account.**
- **One predictable egress rate** — bandwidth (egress) is billed account-wide
  at a single, blended rate rather than per-server variable pricing, so
  customers can forecast transfer costs up front.
- **Full deposit and billing ledger** — every WORM deposit, refund, and
  monthly bandwidth figure is available for the customer to review and
  reconcile themselves.

---

## 6. Automated Backups

A cross-platform backup agent (`backup.agent`) customers can install on any
machine — on our infrastructure or entirely elsewhere — to back up files or
database dumps straight into their own bucket:

- **Runs anywhere** — Windows, Linux, or macOS, whether or not the machine is
  hosted with us.
- **Two backup engines:** a simple `rsync`-style mirror (each backed-up file
  is a plain, directly browsable object — no proprietary format to restore
  from) or `kopia` for deduplicated, incremental, encrypted snapshots when
  space efficiency matters more.
- **Verified restores, not just reported ones** — every restore is
  checksum-verified before it is ever marked complete. A restore that hands
  back unusable data is recorded as failed, never a silent success.
- **Missed-backup detection** — if a scheduled job stops reporting completed
  runs, it's flagged automatically, even if the agent's connection still
  looks healthy.
- **Immutable by design** — backups land in the customer's own bucket, so the
  same WORM/Object Lock protection described above can be applied to guard
  backups against ransomware and accidental deletion.

---

## 7. Integrations & Automation

- **Webhooks** — subscribe to bucket and account events and receive real-time
  delivery to your own endpoint, with automatic retry on failure so events
  aren't silently dropped.
- **Broad client compatibility** — documented, tested setup paths for the
  most common desktop and CLI tools, so onboarding a new machine takes
  minutes, not a support ticket.

---

## 8. Why Customers Choose Us

| What customers care about | Our approach |
|---|---|
| Egress pricing | One clear, predictable, account-wide rate — no surprise per-request or per-region charges |
| Billing visibility | Self-serve dashboard with live usage, snapshots, and a full deposit/billing ledger |
| Vendor lock-in | Standard S3 API — migrate in or out with any S3 tool, any time |
| Account suspension | Always warned by email at 80% usage before anything is restricted; package overage never blocks an account |
| Data sovereignty | Data stays on infrastructure we operate, in the datacenter you choose |
| Support quality | We operate the full stack — support can see the actual logs and data, not just a ticket queue |
| Compliance & ransomware protection | WORM/Object Lock with COMPLIANCE and GOVERNANCE retention modes |

---

*End of Document*

---

> **Document Owner:** Product / Marketing
> **Companion Documents:** `seaweedfs-s3-service-standard.md`, `s3-market-and-support-analysis.md`, `client-setup-guide.md`, `ui/ui-specification.md`, `ui/packaging-billing-ui-specification.md`, `backup.agent/overview.md`
