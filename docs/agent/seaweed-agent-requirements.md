# seaweed — Go Agent Requirements

> **Audience:** Go developer building the `seaweed` binary.
> This document is self-contained. Read it top-to-bottom once before writing any code.

---

## 1. Overview & Role

`seaweed` is a stateless Go binary that runs as a systemd service on every physical S3 storage server. It has two responsibilities:

1. **Execute** SeaweedFS configuration changes (buckets, IAM) as directed by the PlusClouds platform orchestration layer.
2. **Observe and report** the health, capacity, and per-bucket usage of the local SeaweedFS cluster back to the platform.

### What seaweed is NOT

- Not a quota enforcer (that runs centrally in Laravel)
- Not a database — it holds no persistent state; all desired state arrives via NATS on every connect
- Not a user-facing API — it has no public ports

### Relationship to SeaweedFS

The agent is an **executor + observer**. It calls SeaweedFS S3 API and management endpoints to apply changes, and polls health endpoints to gather metrics. It never calls SeaweedFS repair, rebalance, or volume admin endpoints.

### Deployment Topology

```
PlusClouds Platform (Laravel)
     ↕  NATS JetStream
     agent.s3.{uuid}.cmd   ← commands to seaweed
     agent.s3.{uuid}.evt   → telemetry / alerts / results from seaweed

seaweed (Go binary) on each storage server
     ↕  local HTTP (127.0.0.1)
SeaweedFS cluster
     master   :9333
     filer    :8888
     s3       :8333
     volume   :8080
     ↕  Nginx TLS :443
End users / S3 clients
```

See [`docs/agents/overview.md`](../../plusclouds.api.v4/docs/agents/overview.md) for the platform-side agent architecture that seaweed plugs into.

---

## 2. Configuration

All configuration is via environment variables (12-factor). The binary must fail fast with a clear error if any required variable is missing or malformed.

### Required

| Variable | Description |
|---|---|
| `SEAWEED_SERVER_UUID` | UUID of the `s3_servers` row for this server |
| `SEAWEED_AGENT_API_KEY` | Value of `s3_servers.agent_api_key`; used as NATS password |
| `SEAWEED_NATS_URL` | NATS server URL, e.g. `nats://nats.plusclouds.com:4222` |
| `SEAWEED_NATS_TLS_CA` | Absolute path to the CA certificate file for NATS TLS verification |

### Optional (with defaults)

| Variable | Default | Description |
|---|---|---|
| `SEAWEED_MASTER` | `127.0.0.1:9333` | SeaweedFS master HTTP address |
| `SEAWEED_FILER` | `127.0.0.1:8888` | SeaweedFS filer HTTP address |
| `SEAWEED_S3` | `127.0.0.1:8333` | SeaweedFS S3 gateway HTTP address |
| `SEAWEED_IAM_FILE` | `/etc/seaweedfs/s3.json` | Absolute path to SeaweedFS IAM config file |
| `SEAWEED_HEARTBEAT_INTERVAL` | `30` | Seconds between heartbeat publishes |
| `SEAWEED_TELEMETRY_INTERVAL` | `60` | Seconds between telemetry publishes |
| `SEAWEED_LOG_LEVEL` | `info` | Log verbosity: `debug`, `info`, `warn`, `error` |
| `SEAWEED_GRAYLOG_HOST` | _(empty)_ | Graylog UDP host; if empty, GELF shipping is disabled |
| `SEAWEED_GRAYLOG_PORT` | `12201` | Graylog GELF UDP port |
| `SEAWEED_GRAYLOG_ENABLED` | `true` | Set to `false` to disable GELF shipping in dev |

### Dev / test only

| Variable | Description |
|---|---|
| `SEAWEED_NATS_TLS_SKIP_VERIFY` | Set to `true` to skip NATS TLS certificate verification. **Never set in production.** |

### Authentication note

The agent authenticates to NATS by sending `SEAWEED_AGENT_API_KEY` as the password. The platform's NATS auth callout service (`NatsAuthCalloutService`) validates this value against the `s3_servers` table and issues a signed JWT granting the agent permission to:

- Subscribe to `agent.s3.{SEAWEED_SERVER_UUID}.cmd`
- Publish to `agent.s3.{SEAWEED_SERVER_UUID}.evt`

The agent is automatically rejected on its next reconnect if the key is rotated or the server record is deleted.

---

## 3. Bootstrap Sequence

Perform these steps in order on every startup (and after a NATS reconnect):

1. **Load config** — parse all env vars; if any required variable is missing, print a human-readable error and exit with code 1.

2. **Connect to NATS** — use TLS with the CA from `SEAWEED_NATS_TLS_CA`; authenticate with `SEAWEED_AGENT_API_KEY` as password. On failure, log the error and exit with code 1 (systemd will restart the service).

3. **Create or resume the durable JetStream consumer**:
   - Stream: `AGENT_COMMANDS`
   - Filter subject: `agent.s3.{SEAWEED_SERVER_UUID}.cmd`
   - Consumer name: `seaweed-{SEAWEED_SERVER_UUID}`
   - Deliver policy: `all` (replay from last ack on resume; deliver everything on first connect)
   - Ack policy: `explicit` — the agent NAK-acks each message only after it has been fully processed

4. **Drain pending commands** — immediately fetch and process all queued messages. The platform always sends a `full_sync` as the first command on a fresh consumer.

5. **Handle missing `full_sync`** — if no `full_sync` is received within 30 seconds of connect, publish a `heartbeat` message. The platform will detect the new connection (via `agent_status = 'pending'`) and dispatch a `full_sync`.

6. **Send initial telemetry** — publish one `heartbeat` and one `telemetry` message to `agent.s3.{uuid}.evt` immediately after reconcile completes.

7. **Start tickers**:
   - Heartbeat ticker: every `SEAWEED_HEARTBEAT_INTERVAL` seconds
   - Telemetry ticker: every `SEAWEED_TELEMETRY_INTERVAL` seconds
   - SeaweedFS health poll: every 30 seconds, offset ±5 s from the heartbeat ticker to avoid bursts

8. **Enter the main loop** — consume and process commands from the durable consumer indefinitely.

---

## 4. NATS Message Protocol

All messages in both directions use the standard platform envelope. Full specification: [`docs/agents/protocol.md`](../../plusclouds.api.v4/docs/agents/protocol.md).

```json
{
  "v":          1,
  "id":         "550e8400-e29b-41d4-a716-446655440000",
  "type":       "heartbeat",
  "agent_type": "s3",
  "agent_uuid": "7c9e6679-7425-40de-944b-e07fc1f90ae7",
  "timestamp":  1748000000,
  "payload":    {}
}
```

| Field | Type | Notes |
|---|---|---|
| `v` | int | Protocol version. Always `1`. Reject messages with unknown versions. |
| `id` | UUID string | Unique message ID. Echo back as `command_id` in result messages. |
| `type` | string | Determines `payload` shape (see sections 5 and 6). |
| `agent_type` | string | Always `"s3"` for seaweed. |
| `agent_uuid` | UUID string | The `SEAWEED_SERVER_UUID` value. |
| `timestamp` | int | Unix epoch seconds. |
| `payload` | object | Type-specific data. |

### Message types used by seaweed

**Agent → Platform** (`agent.s3.{uuid}.evt`):

| Type | When | Purpose |
|---|---|---|
| `heartbeat` | Every 30 s | Keeps `agent_status` current; updates `agent_last_seen_at` |
| `telemetry` | Every 60 s | Full server health + per-bucket usage snapshot |
| `alert` | On state transition | Capacity thresholds, degraded volumes, unreachable components |
| `result` | After every command | Closes the `agent_commands` DB record on the platform |

**Platform → Agent** (`agent.s3.{uuid}.cmd`):

All platform messages have `type: "command"`. The operation is identified by `payload.operation`.

---

## 5. Agent → Platform: Payload Schemas

### 5.1 `heartbeat`

```json
{
  "version":      "1.0.0",
  "uptime_s":     3600,
  "tasks_queued": 0
}
```

| Field | Type | Description |
|---|---|---|
| `version` | string | seaweed binary version |
| `uptime_s` | int | Seconds since the agent process started |
| `tasks_queued` | int | Number of commands currently in the processing queue (normally 0) |

**Platform action:** update `s3_servers.agent_status = 'connected'`, `s3_servers.agent_last_seen_at = now()`.

---

### 5.2 `telemetry`

```json
{
  "cpu": {
    "usage_pct": 4.2,
    "cores":     4
  },
  "ram": {
    "used_bytes":  2147483648,
    "total_bytes": 8589934592
  },
  "network": {
    "interfaces": [
      {
        "name":      "eth0",
        "rx_bps":    1048576,
        "tx_bps":    524288,
        "rx_errors": 0,
        "tx_errors": 0
      }
    ]
  },
  "seaweedfs": {
    "master_reachable":     true,
    "filer_reachable":      true,
    "s3_reachable":         true,
    "volume_count":         12,
    "volumes_degraded":     0,
    "capacity_bytes_total": 10737418240,
    "capacity_bytes_used":  5368709120,
    "seaweedfs_version":    "3.67"
  },
  "buckets": [
    {
      "name":           "customer-a-main",
      "object_count":   14200,
      "size_bytes":     2147483648,
      "replica_health": "healthy"
    }
  ],
  "uptime_s": 3600
}
```

`replica_health` values: `"healthy"`, `"degraded"`, `"unknown"`.

**Platform action:** insert row into `s3_server_telemetry`; update `s3_servers.health`, `s3_servers.components`, `s3_servers.agent_version`, `s3_servers.seaweedfs_version`.

---

### 5.3 `alert`

Alerts are fired on **state transitions only** — not on every tick. The agent must track the previous value for each metric to detect changes.

```json
{
  "severity":    "warning",
  "code":        "CAPACITY_WARN",
  "message":     "Capacity at 82%: 8.6 GB used of 10 GB",
  "object_type": "server",
  "object_id":   "7c9e6679-7425-40de-944b-e07fc1f90ae7",
  "details": {
    "capacity_pct":         82.3,
    "capacity_bytes_used":  8825955328,
    "capacity_bytes_total": 10737418240
  }
}
```

**Alert codes the agent must implement:**

| Code | Severity | Fire condition | Clear condition |
|---|---|---|---|
| `CAPACITY_WARN` | `warning` | `capacity_bytes_used / total` ≥ 80% | Drops below 75% |
| `CAPACITY_CRITICAL` | `critical` | `capacity_bytes_used / total` ≥ 90% | Drops below 85% |
| `MASTER_UNREACHABLE` | `critical` | SeaweedFS master does not respond | Master becomes reachable again |
| `VOLUMES_DEGRADED` | `warning` | `volumes_degraded` goes from 0 to > 0 | `volumes_degraded` returns to 0 |
| `VOLUMES_RECOVERED` | `info` | `volumes_degraded` returns to 0 | — |

`severity` values: `info`, `warning`, `critical`, `emergency`.

**Platform action:** call `Events::fire("alert:s3.{code}", $server, $payload)`.

---

### 5.4 `result`

Every command the agent receives must be followed by a result. Send the result to `agent.s3.{uuid}.evt` within `payload.timeout_s` seconds of receiving the command.

```json
{
  "command_id": "550e8400-e29b-41d4-a716-446655440000",
  "status":     "completed",
  "message":    "Bucket customer-a-main created successfully",
  "output":     {}
}
```

| Field | Type | Description |
|---|---|---|
| `command_id` | UUID string | The `id` field from the original command envelope |
| `status` | string | `completed`, `failed`, or `rejected` |
| `message` | string | Human-readable summary (shown in platform UI and logs) |
| `output` | object | Operation-specific structured output (may be empty `{}`) |

`status` meanings:
- `completed` — operation succeeded
- `failed` — operation was attempted but encountered an error; include the error in `message`
- `rejected` — agent refused to attempt the operation (precondition not met, unsupported operation); include reason in `message`

**Platform action:** look up `agent_commands` row by `command_id`, set `status`, `result = output`, `completed_at = now()`.

---

## 6. Commands: Platform → Agent

All commands arrive with `type: "command"` in the envelope. The operation is in `payload.operation`. The agent MUST send a `result` for every command, even if it is a no-op.

### 6.1 `full_sync`

Delivers the complete desired state for this server. The agent applies it **idempotently**: create what is missing, update what differs, delete what is extra.

**Sent by platform:** on every fresh agent connection, after any bucket or IAM change while the agent was offline, or when an admin triggers a reconcile.

```json
{
  "operation": "full_sync",
  "params": {
    "buckets": [
      {
        "name":                "customer-a-main",
        "versioning":          "Enabled",
        "object_lock_enabled": false,
        "object_lock_mode":    null,
        "object_lock_days":    null,
        "lifecycle_rules":     null
      },
      {
        "name":                "customer-b-archive",
        "versioning":          "Suspended",
        "object_lock_enabled": true,
        "object_lock_mode":    "GOVERNANCE",
        "object_lock_days":    365,
        "lifecycle_rules":     null
      }
    ],
    "iam_keys": [
      {
        "access_key":  "AKIAcustomeraMAIN0001",
        "secret_key":  "<plaintext — transmitted over TLS-encrypted NATS>",
        "role":        "readwrite",
        "bucket_acls": null
      }
    ]
  },
  "timeout_s": 120
}
```

**Reconcile logic:**

Buckets:
1. For each bucket in payload: create in SeaweedFS if it does not exist; update versioning and lifecycle config if it does exist.
2. For each local SeaweedFS bucket NOT in payload: delete it.

IAM:
1. For each key in payload: add identity to `/etc/seaweedfs/s3.json` if missing; update role/actions if present.
2. For each identity in the IAM file NOT in payload: remove it.
3. Perform a single atomic write + `systemctl reload weed-s3` covering all IAM changes.

**Result output:**
```json
{
  "buckets": { "created": 2, "updated": 1, "deleted": 0 },
  "iam":     { "created": 3, "updated": 0, "deleted": 1 }
}
```

---

### 6.2 `bucket_create`

```json
{
  "operation": "bucket_create",
  "params": {
    "name":                "customer-b-backups",
    "versioning":          "Suspended",
    "object_lock_enabled": true,
    "object_lock_mode":    "GOVERNANCE",
    "object_lock_days":    365,
    "lifecycle_rules":     null
  },
  "timeout_s": 30
}
```

If the bucket already exists, treat as success (idempotent).

---

### 6.3 `bucket_delete`

```json
{
  "operation": "bucket_delete",
  "params": {
    "name": "customer-b-backups"
  },
  "timeout_s": 30
}
```

If the bucket does not exist, treat as success (idempotent).

---

### 6.4 `bucket_update`

Updates versioning and lifecycle config only. The bucket name and object lock settings are **immutable** — ignore them if present in the params.

```json
{
  "operation": "bucket_update",
  "params": {
    "name":            "customer-a-main",
    "versioning":      "Enabled",
    "lifecycle_rules": null
  },
  "timeout_s": 30
}
```

---

### 6.5 `iam_create`

```json
{
  "operation": "iam_create",
  "params": {
    "access_key":  "AKIAcustomerbBKUP0001",
    "secret_key":  "<plaintext>",
    "role":        "readonly",
    "bucket_acls": null
  },
  "timeout_s": 30
}
```

After writing the IAM file: `systemctl reload weed-s3`.

---

### 6.6 `iam_delete`

```json
{
  "operation": "iam_delete",
  "params": {
    "access_key": "AKIAcustomerbBKUP0001"
  },
  "timeout_s": 30
}
```

If the key is not in the IAM file, treat as success (idempotent). After removing: `systemctl reload weed-s3`.

---

### 6.7 `reconcile`

Forces a re-application of the last received `full_sync` state. Used when an operator suspects drift between the platform's desired state and the server's actual state.

```json
{
  "operation": "reconcile",
  "params": {
    "scope": "all"
  },
  "timeout_s": 120
}
```

`scope` values: `"buckets"`, `"iam"`, `"all"`. The agent must have the last `full_sync` payload in memory; if it does not (e.g. first connect and `full_sync` has not arrived), respond with `status=rejected` and message `"No full_sync state available; waiting for initial sync"`.

---

## 7. SeaweedFS Integration

### 7.1 Bucket Operations

All bucket operations use the SeaweedFS S3-compatible API at `http://{SEAWEED_S3}`.

**Create bucket:**
```
PUT http://127.0.0.1:8333/{bucket-name}
```
For Object Lock buckets, include header:
```
x-amz-bucket-object-lock-enabled: true
```
Object Lock mode and retention days are set via bucket default retention — use the `PUT /?object-lock` endpoint after creation.

**Delete bucket:**
```
DELETE http://127.0.0.1:8333/{bucket-name}
```

**Enable/suspend versioning:**
```
PUT http://127.0.0.1:8333/{bucket-name}?versioning
Content-Type: application/xml

<VersioningConfiguration>
  <Status>Enabled</Status>   <!-- or Suspended -->
</VersioningConfiguration>
```

**Set lifecycle rules:**
```
PUT http://127.0.0.1:8333/{bucket-name}?lifecycle
Content-Type: application/xml
(standard S3 lifecycle XML body)
```

---

### 7.2 IAM Management

The IAM configuration lives at `SEAWEED_IAM_FILE` (default `/etc/seaweedfs/s3.json`).

**File format:**
```json
{
  "identities": [
    {
      "name": "AKIAcustomeraMAIN0001",
      "credentials": [
        {
          "accessKey": "AKIAcustomeraMAIN0001",
          "secretKey": "plaintextsecretgoeshere"
        }
      ],
      "actions": ["Read", "Write", "List", "Tagging"]
    }
  ]
}
```

**Role → SeaweedFS actions mapping:**

| Role | SeaweedFS actions |
|---|---|
| `readwrite` | `Read`, `Write`, `List`, `Tagging` |
| `readonly` | `Read`, `List` |
| `writeonly` | `Write`, `Tagging` |
| `nodelete` | `Read`, `Write`, `List`, `Tagging` (no `DeleteObject`) |

**Writing the IAM file — atomic write required:**
1. Write new content to a temp file in the same directory as the IAM file (e.g. `s3.json.tmp`).
2. `rename(tmpFile, iamFile)` — atomic on Linux ext4/xfs.
3. Run `systemctl reload weed-s3` — do NOT use `restart` (causes downtime).

File permissions must remain `640`, owner `seaweed:seaweed` after every write.

---

### 7.3 Health Observation (Read-Only)

Poll the following endpoints every 30 seconds. Never call repair, rebalance, or volume admin endpoints.

| Endpoint | What to check |
|---|---|
| `GET http://127.0.0.1:9333/cluster/status` | JSON response: `Leader` field non-empty = master reachable |
| `GET http://127.0.0.1:9333/vol/status` | `Volumes` array: count entries; check `Collection` for degraded flags |
| `GET http://127.0.0.1:8888/` | HTTP 200 = filer alive |
| `GET http://127.0.0.1:8333/` | HTTP 200 = S3 gateway alive |

Timeouts for health checks: 5 seconds per request. A timeout counts as unreachable.

---

## 8. Reconnection & Resilience

### NATS Reconnection

Use the official `nats.go` client. Configure:

```go
nats.MaxReconnects(-1)           // reconnect forever
nats.ReconnectWait(2 * time.Second)
nats.ReconnectJitter(500*time.Millisecond, 2*time.Second)
nats.DisconnectErrHandler(...)   // log + set internal state
nats.ReconnectHandler(...)       // log + trigger bootstrap sequence
```

The durable JetStream consumer (`seaweed-{uuid}`) retains its position in the `AGENT_COMMANDS` stream. On reconnect it automatically replays any commands queued while the agent was offline (up to the 24-hour stream TTL).

If queued commands include a `full_sync`, process it first — it supersedes all earlier bucket/IAM commands.

### Heartbeat and Telemetry Failures

If a NATS publish fails, skip that tick and log a warning. Do **not** buffer unpublished telemetry. The platform detects staleness via `agent_last_seen_at`.

### IAM File Reload Failure

1. Retry `systemctl reload weed-s3` once after 2 seconds.
2. If the retry also fails: send a `result` with `status=failed` and include the systemd error in `message`. Log at `error` level.
3. Never leave the IAM file in a partially-written state. The atomic rename ensures this.

### SeaweedFS Unreachable

- Continue running and consuming NATS commands.
- Respond to any `bucket_*` or `iam_*` command with `status=failed` and message `"SeaweedFS master unreachable"`.
- Continue publishing heartbeats (with `tasks_queued` reflecting any backlogged commands if you choose to queue them).
- Include `seaweedfs.master_reachable: false` in every telemetry message.
- Fire a `MASTER_UNREACHABLE` alert on the first detection. Do not re-fire on every tick.

---

## 9. Graceful Shutdown

Handle `SIGTERM` and `SIGINT`:

1. Stop accepting new JetStream messages (pause consumer delivery).
2. Finish any bucket or IAM operation currently in progress. Do not interrupt a mid-write IAM file operation.
3. Publish a final `heartbeat` with `tasks_queued: 0` (best-effort, 3-second timeout).
4. Call `nc.Drain()` to flush any pending publishes before closing the NATS connection.
5. Exit code `0` on clean shutdown. Exit non-zero on any unrecoverable error.

---

## 10. Local Health Endpoint

The agent exposes a minimal HTTP server on `127.0.0.1:9090` (loopback only — never expose externally).

**`GET /health`**

Response (HTTP 200):
```json
{
  "status":              "ok",
  "uptime_s":            3600,
  "nats_connected":      true,
  "seaweedfs_reachable": true,
  "version":             "1.0.0"
}
```

If NATS is disconnected or SeaweedFS is unreachable, the corresponding field is `false` but the HTTP response is still 200 (the agent is running; individual subsystems may be degraded).

Return HTTP 503 only if the agent is in the process of shutting down.

Used by systemd `ExecStartPost` to verify the agent came up cleanly:
```ini
ExecStartPost=/usr/bin/curl -sf http://127.0.0.1:9090/health
```

---

## 11. Systemd Service

Install at `/etc/systemd/system/seaweed.service`:

```ini
[Unit]
Description=PlusClouds S3 seaweed agent
Documentation=https://docs.plusclouds.com/s3/agent
After=network.target weed-s3.service
Requires=weed-s3.service

[Service]
Type=simple
User=seaweed
Group=seaweed
EnvironmentFile=/etc/seaweed/env
ExecStart=/usr/local/bin/seaweed
ExecStartPost=/usr/bin/curl -sf --retry 5 --retry-delay 1 http://127.0.0.1:9090/health
Restart=on-failure
RestartSec=5s
StandardOutput=journal
StandardError=journal
SyslogIdentifier=seaweed

[Install]
WantedBy=multi-user.target
```

Environment file `/etc/seaweed/env` (permissions `600`, owner `root:seaweed`):
```
SEAWEED_SERVER_UUID=<uuid>
SEAWEED_AGENT_API_KEY=<key>
SEAWEED_NATS_URL=nats://nats.plusclouds.com:4222
SEAWEED_NATS_TLS_CA=/etc/seaweed/nats-ca.crt
SEAWEED_GRAYLOG_HOST=graylog.plusclouds.com
```

---

## 12. Security

| Requirement | Details |
|---|---|
| NATS authentication | `SEAWEED_AGENT_API_KEY` sent as NATS password; JWT issued by auth callout |
| NATS TLS | Always verify CA cert in production. `SEAWEED_NATS_TLS_SKIP_VERIFY` must never be set on production servers |
| Secret key in transit | `iam_create.params.secret_key` is only transmitted over TLS-encrypted NATS connections |
| IAM file permissions | `640`, owned `seaweed:seaweed` |
| Env file permissions | `600`, owned `root:seaweed` |
| Binary permissions | `755`, owned `root:root`; only `seaweed` user can execute |
| No inbound ports | The only open port is `127.0.0.1:9090` (loopback health endpoint) |
| Log masking | `secret_key` must never appear in logs. `access_key` logged as `AKIA****` (first 4 chars only) |

---

## 13. Logging & Graylog Integration

All log events go to **two sinks simultaneously**:

1. **stdout** — structured JSON consumed by `journald`
2. **Graylog via GELF UDP** — same events shipped to central Graylog for search and alerting

If `SEAWEED_GRAYLOG_ENABLED=false` or `SEAWEED_GRAYLOG_HOST` is empty, skip UDP shipping entirely.

### 13.1 Log Library

Use `zerolog` (`github.com/rs/zerolog`) or `zap` (`go.uber.org/zap`). Every log line must include:

| Field | Example |
|---|---|
| `timestamp` | `"2025-01-15T10:00:00.123Z"` (RFC3339Nano) |
| `level` | `"info"` |
| `component` | `"command_handler"` |
| `message` | `"Command received"` |

### 13.2 Graylog GELF UDP

GELF 1.1 over UDP. Construct and fire a GELF JSON payload for every log event.

**GELF message structure:**

```json
{
  "version":          "1.1",
  "host":             "storage-01.plusclouds.com",
  "short_message":    "Command received: full_sync",
  "full_message":     "",
  "timestamp":        1748000000.123,
  "level":            6,
  "_agent_type":      "s3",
  "_agent_uuid":      "7c9e6679-7425-40de-944b-e07fc1f90ae7",
  "_server_hostname": "storage-01.plusclouds.com",
  "_agent_version":   "1.0.0",
  "_component":       "command_handler",
  "_command_id":      "550e8400-e29b-41d4-a716-446655440000",
  "_operation":       "full_sync"
}
```

GELF `level` values (syslog severity):

| Value | Meaning |
|---|---|
| 0 | Emergency |
| 1 | Alert |
| 2 | Critical |
| 3 | Error |
| 4 | Warning |
| 5 | Notice |
| 6 | Info |
| 7 | Debug |

**Custom fields** (prefixed `_`) are indexed by Graylog and searchable. Always include the four baseline fields on every message:

| Field | Value |
|---|---|
| `_agent_type` | `"s3"` |
| `_agent_uuid` | `SEAWEED_SERVER_UUID` |
| `_server_hostname` | OS hostname (`os.Hostname()`) |
| `_agent_version` | Binary version string |

**UDP delivery is fire-and-forget.** If the Graylog host is unreachable, the agent must not block, retry, or buffer. The log line still goes to stdout.

**GELF chunking:** UDP payloads over 8192 bytes must be chunked per the GELF spec (magic bytes `0x1e 0x0f`, 8-byte message ID, 1-byte sequence number and count). Use `github.com/Graylog2/go-gelf` to handle this automatically.

### 13.3 Mandatory Log Events

The following events must be logged with the listed fields. This table is the audit trail for every action the agent takes.

| Event | Level | Component | Required extra fields |
|---|---|---|---|
| Agent started | info | `startup` | `agent_version`, `server_uuid`, `nats_url` |
| Config validation failed | error | `startup` | `missing_var` |
| NATS connected | info | `nats` | `server_uuid` |
| NATS disconnected | warning | `nats` | `reason` |
| NATS reconnected | info | `nats` | `reconnect_count` |
| JetStream consumer created | info | `nats` | `consumer_name`, `filter_subject` |
| Command received | info | `command_handler` | `command_id`, `operation` |
| Command execution started | info | `command_handler` | `command_id`, `operation` |
| Command completed | info | `command_handler` | `command_id`, `operation`, `duration_ms`, `status` |
| Command failed | error | `command_handler` | `command_id`, `operation`, `duration_ms`, `error` |
| Command rejected | warning | `command_handler` | `command_id`, `operation`, `reason` |
| Result published | info | `command_handler` | `command_id`, `status` |
| Heartbeat published | debug | `heartbeat` | `uptime_s`, `tasks_queued` |
| Telemetry published | debug | `telemetry` | `capacity_pct`, `volumes_degraded`, `bucket_count` |
| Alert fired | warning/critical | `health_monitor` | `code`, `severity`, `previous_value`, `current_value` |
| SeaweedFS component state changed | warning | `health_monitor` | `component`, `previous_state`, `new_state` |
| full_sync reconcile started | info | `reconciler` | — |
| full_sync reconcile completed | info | `reconciler` | `buckets_created`, `buckets_updated`, `buckets_deleted`, `iam_created`, `iam_deleted` |
| Bucket created in SeaweedFS | info | `bucket_manager` | `bucket_name` |
| Bucket deleted from SeaweedFS | info | `bucket_manager` | `bucket_name` |
| Bucket config updated | info | `bucket_manager` | `bucket_name`, `fields_changed` |
| IAM file written | info | `iam_manager` | `identities_count`, `operation` (`add`/`remove`/`reconcile`) |
| IAM reload triggered | info | `iam_manager` | — |
| IAM reload succeeded | info | `iam_manager` | — |
| IAM reload failed | error | `iam_manager` | `error`, `retry_count` |
| Agent shutting down | info | `shutdown` | `signal` (`SIGTERM`/`SIGINT`) |
