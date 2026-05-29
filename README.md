# S3

This module provides S3-compatible object storage management for the PlusClouds platform, backed by a [SeaweedFS](https://github.com/seaweedfs/seaweedfs) cluster. It exposes a full REST API to manage storage accounts, buckets, access keys, quotas, bandwidth tracking, audit logs, WORM commitments, and webhook notifications — all integrated with the platform's IAM and billing layers.

The storage agent (`storaged`) that runs alongside the SeaweedFS cluster on each storage server is a separate Go binary. This module is its orchestration counterpart: it holds desired state, receives telemetry, and dispatches commands to the agent over NATS.

## Resources

| Resource | Description |
|---|---|
| `Accounts` | S3 storage accounts (one per IAM account) |
| `Buckets` | S3 buckets belonging to an account |
| `AccessKeys` | HMAC access key pairs for S3 API authentication |
| `Servers` | Storage servers running the SeaweedFS cluster |
| `ServerTelemetries` | Real-time capacity and health reports from each server |
| `AuditLogs` | Per-request access log for compliance |
| `BandwidthMonthlies` | Monthly egress/ingress usage per account |
| `DepositLedgers` | Billing credits deposited against an account |
| `MultipartUploads` | In-progress multipart upload sessions |
| `Webhooks` | Outbound event notification endpoints |
| `WebhookDeliveries` | Delivery history and retry log for each webhook |
| `UsageSnapshots` | Point-in-time storage usage snapshots |
| `WormCommitments` | WORM (Write Once Read Many) bucket lock commitments |
| `NotificationsSents` | Record of quota and lifecycle notifications sent |

## Actions

| Action | Description |
|---|---|
| `AccessKeys/Revoke` | Immediately revoke an access key pair |
| `Accounts/Block` | Suspend all API access for an account |
| `Accounts/Unblock` | Restore API access for a blocked account |
| `Buckets/EnableVersioning` | Enable object versioning on a bucket |
| `Buckets/SuspendVersioning` | Suspend object versioning on a bucket |
| `MultipartUploads/Abort` | Abort an in-progress multipart upload and free its parts |
| `Servers/RegenerateApiKey` | Rotate the API key used between this service and the storage agent |
| `Webhooks/Pause` | Pause delivery of events to a webhook endpoint |
| `Webhooks/Resume` | Resume delivery of events to a paused webhook endpoint |
| `WormCommitments/Cancel` | Cancel a pending WORM commitment before it becomes active |

## Artisan Commands

```bash
# Take usage snapshots and enforce quota thresholds for all active accounts
php artisan s3:check-quotas

# Abort and remove multipart uploads inactive for more than 48 hours
php artisan s3:cleanup-multipart

# Sync current egress/ingress usage into the monthly bandwidth tracking table
php artisan s3:parse-bandwidth

# Process WORM commitment lifecycle: expire active commitments and purge old ones
php artisan s3:worm-lifecycle
```

These commands are intended to run on a schedule. Add them to your `app/Console/Kernel.php` or a cron table:

```
* * * * * php artisan s3:check-quotas
0 * * * * php artisan s3:parse-bandwidth
0 3 * * * php artisan s3:cleanup-multipart
0 4 * * * php artisan s3:worm-lifecycle
```

## Required .env Keys

```env
# AES-256-GCM key for encrypting access key secrets at rest (32-byte raw or 64-char hex)
S3_ENCRYPTION_KEY=

# Default quota limits applied when provisioning a new account
S3_DEFAULT_QUOTA_STORAGE_BYTES=10737418240       # 10 GB
S3_DEFAULT_QUOTA_EGRESS_BYTES_MO=107374182400    # 100 GB
S3_DEFAULT_QUOTA_MAX_BUCKETS=10
S3_DEFAULT_QUOTA_MAX_OBJECTS=1000000

# WORM pricing and lifecycle
S3_WORM_PRICE_PER_GB_MO=0.023
S3_WORM_PURGE_GRACE_DAYS=30
```

## Architecture

```
┌─────────────────────────────────────────────────────┐
│               PlusClouds Orchestration               │
│            S3 Module (this package)                  │
│  Accounts · Buckets · Keys · Quotas · Billing        │
└──────────────┬──────────────────────────────────────┘
               │  NATS commands / telemetry
               ▼
┌──────────────────────────────────────────────────────┐
│                    Storage Server                    │
│  ┌──────────────────────────────────────────────┐   │
│  │              storaged (Go agent)             │   │
│  │  bucket management · SeaweedFS health watch  │   │
│  └───────────────────┬──────────────────────────┘   │
│                      │                              │
│  ┌───────────────────▼──────────────────────────┐   │
│  │              SeaweedFS cluster               │   │
│  │        master · volume · filer · s3          │   │
│  └──────────────────────────────────────────────┘   │
└──────────────────────────────────────────────────────┘
```

The `storaged` Go agent design is documented in [docs/storaged-design.md](docs/storaged-design.md). The NATS command/event contract between this module and the agent is defined in [docs/agent/seaweed-nats-contract.md](docs/agent/seaweed-nats-contract.md).

## Planned Feature List

- [x] Account and bucket lifecycle management
- [x] Access key provisioning and revocation
- [x] Quota enforcement with notifications
- [x] Monthly bandwidth tracking
- [x] Multipart upload cleanup
- [x] WORM commitment lifecycle
- [x] Webhook delivery with retry log
- [x] Audit logging
- [x] Server telemetry ingestion
- [ ] storaged Go agent (first release)
- [ ] Automated billing ledger reconciliation
- [ ] Cross-region replication support

---

## Our Libraries

This library is part of the **NextDeveloper / PlusClouds open-source ecosystem**. Browse all available libraries and find the right building blocks for your next project:

[https://plusclouds.com/us/solutions/libraries](https://plusclouds.com/us/solutions/libraries)

---

## Join the Community

We believe great software is built together. The PlusClouds developer community is a place where engineers share ideas, ask questions, showcase what they have built, and help shape the direction of these libraries. Whether you are integrating a single package or building an entire platform on top of our stack, you are very welcome here.

Come and join us — we would love to see what you build:

[https://plusclouds.com/us/community](https://plusclouds.com/us/community)
