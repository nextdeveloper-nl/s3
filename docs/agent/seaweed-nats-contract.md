# seaweed — NATS Contract

> **Audience:** Go developer (agent side) and PHP developer (platform-side integration).
> This document defines every message schema, the complete subject map, Go connection setup, PHP service contracts, testing commands, and timeout/error handling.

---

## A. Subject Map

```
agent.s3.{uuid}.cmd     ← platform sends commands to a specific seaweed instance
agent.s3.{uuid}.evt     → seaweed publishes heartbeat / telemetry / alert / result
agent.s3.broadcast      ← platform broadcasts to all s3 agents simultaneously
```

These subjects fall under the existing `AGENT_COMMANDS` JetStream stream which covers `agent.>`. No new streams or infrastructure changes are required.

---

## B. NATS Connection Setup (Go)

### Dependencies

```go
import (
    "github.com/nats-io/nats.go"
    "github.com/nats-io/nats.go/jetstream"
    "github.com/Graylog2/go-gelf/gelf"  // for GELF UDP shipping
)
```

### Connecting

```go
opts := []nats.Option{
    nats.Name("seaweed-" + serverUUID),
    nats.UserInfo("seaweed", agentApiKey), // username ignored; password = agent_api_key
    nats.RootCAs(natsCAPath),
    nats.MaxReconnects(-1),
    nats.ReconnectWait(2 * time.Second),
    nats.ReconnectJitter(500*time.Millisecond, 2*time.Second),
    nats.DisconnectErrHandler(func(nc *nats.Conn, err error) {
        log.Warn().Err(err).Msg("NATS disconnected")
    }),
    nats.ReconnectHandler(func(nc *nats.Conn) {
        log.Info().Int("reconnect_count", int(nc.Stats().Reconnects)).Msg("NATS reconnected")
        go bootstrap() // re-run bootstrap on every reconnect
    }),
}

nc, err := nats.Connect(natsURL, opts...)
```

### Creating the Durable JetStream Consumer

```go
js, err := jetstream.New(nc)

consumerName := "seaweed-" + serverUUID
filterSubject := "agent.s3." + serverUUID + ".cmd"

cons, err := js.CreateOrUpdateConsumer(ctx, "AGENT_COMMANDS", jetstream.ConsumerConfig{
    Name:           consumerName,
    Durable:        consumerName,
    FilterSubject:  filterSubject,
    AckPolicy:      jetstream.AckExplicitPolicy,
    DeliverPolicy:  jetstream.DeliverAllPolicy,   // on first connect; resumes from last ack on reconnect
    AckWait:        120 * time.Second,             // max time to process one message
    MaxDeliver:     3,                             // redeliver up to 3 times before giving up
})

// Consume messages
msgCh, err := cons.Messages()
for msg := range msgCh {
    go handleCommand(msg) // process concurrently; call msg.Ack() or msg.Nak() when done
}
```

**Important:** call `msg.Ack()` only after the command has been fully executed and the `result` message has been published to `.evt`. This ensures at-least-once delivery — if the agent crashes mid-execution, the consumer redelivers the command on restart.

---

## C. Full Message Catalog

### Envelope (both directions)

Every NATS message is a JSON-encoded envelope:

```go
type Envelope struct {
    V         int             `json:"v"`
    ID        string          `json:"id"`          // UUID
    Type      string          `json:"type"`
    AgentType string          `json:"agent_type"`  // always "s3" for seaweed
    AgentUUID string          `json:"agent_uuid"`
    Timestamp int64           `json:"timestamp"`   // Unix epoch seconds
    Payload   json.RawMessage `json:"payload"`
}
```

---

### C.1 heartbeat (agent → platform)

```go
type HeartbeatPayload struct {
    Version      string `json:"version"`
    UptimeS      int64  `json:"uptime_s"`
    TasksQueued  int    `json:"tasks_queued"`
}
```

```json
{
  "v": 1,
  "id": "a1b2c3d4-0001-0000-0000-000000000001",
  "type": "heartbeat",
  "agent_type": "s3",
  "agent_uuid": "7c9e6679-7425-40de-944b-e07fc1f90ae7",
  "timestamp": 1748000000,
  "payload": {
    "version":      "1.0.0",
    "uptime_s":     3600,
    "tasks_queued": 0
  }
}
```

---

### C.2 telemetry (agent → platform)

```go
type TelemetryPayload struct {
    CPU      CPUStats        `json:"cpu"`
    RAM      RAMStats        `json:"ram"`
    Network  NetworkStats    `json:"network"`
    SeaweedFS SeaweedFSStats `json:"seaweedfs"`
    Buckets  []BucketStats   `json:"buckets"`
    UptimeS  int64           `json:"uptime_s"`
}

type CPUStats struct {
    UsagePct float64 `json:"usage_pct"`
    Cores    int     `json:"cores"`
}

type RAMStats struct {
    UsedBytes  int64 `json:"used_bytes"`
    TotalBytes int64 `json:"total_bytes"`
}

type NetworkStats struct {
    Interfaces []NetworkInterface `json:"interfaces"`
}

type NetworkInterface struct {
    Name     string `json:"name"`
    RxBps    int64  `json:"rx_bps"`
    TxBps    int64  `json:"tx_bps"`
    RxErrors int64  `json:"rx_errors"`
    TxErrors int64  `json:"tx_errors"`
}

type SeaweedFSStats struct {
    MasterReachable    bool   `json:"master_reachable"`
    FilerReachable     bool   `json:"filer_reachable"`
    S3Reachable        bool   `json:"s3_reachable"`
    VolumeCount        int    `json:"volume_count"`
    VolumesDegraded    int    `json:"volumes_degraded"`
    CapacityBytesTotal int64  `json:"capacity_bytes_total"`
    CapacityBytesUsed  int64  `json:"capacity_bytes_used"`
    SeaweedFSVersion   string `json:"seaweedfs_version"`
}

type BucketStats struct {
    Name          string `json:"name"`
    ObjectCount   int64  `json:"object_count"`
    SizeBytes     int64  `json:"size_bytes"`
    ReplicaHealth string `json:"replica_health"` // "healthy" | "degraded" | "unknown"
}
```

```json
{
  "v": 1,
  "id": "a1b2c3d4-0002-0000-0000-000000000001",
  "type": "telemetry",
  "agent_type": "s3",
  "agent_uuid": "7c9e6679-7425-40de-944b-e07fc1f90ae7",
  "timestamp": 1748000060,
  "payload": {
    "cpu":     { "usage_pct": 4.2, "cores": 4 },
    "ram":     { "used_bytes": 2147483648, "total_bytes": 8589934592 },
    "network": {
      "interfaces": [
        { "name": "eth0", "rx_bps": 1048576, "tx_bps": 524288, "rx_errors": 0, "tx_errors": 0 }
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
}
```

---

### C.3 alert (agent → platform)

```go
type AlertPayload struct {
    Severity   string          `json:"severity"`    // "info"|"warning"|"critical"|"emergency"
    Code       string          `json:"code"`
    Message    string          `json:"message"`
    ObjectType string          `json:"object_type"` // "server"
    ObjectID   string          `json:"object_id"`   // server UUID
    Details    json.RawMessage `json:"details"`
}
```

```json
{
  "v": 1,
  "id": "a1b2c3d4-0003-0000-0000-000000000001",
  "type": "alert",
  "agent_type": "s3",
  "agent_uuid": "7c9e6679-7425-40de-944b-e07fc1f90ae7",
  "timestamp": 1748000090,
  "payload": {
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
}
```

---

### C.4 result (agent → platform)

```go
type ResultPayload struct {
    CommandID string          `json:"command_id"`
    Status    string          `json:"status"`   // "completed"|"failed"|"rejected"
    Message   string          `json:"message"`
    Output    json.RawMessage `json:"output"`
}
```

```json
{
  "v": 1,
  "id": "a1b2c3d4-0004-0000-0000-000000000001",
  "type": "result",
  "agent_type": "s3",
  "agent_uuid": "7c9e6679-7425-40de-944b-e07fc1f90ae7",
  "timestamp": 1748000095,
  "payload": {
    "command_id": "550e8400-e29b-41d4-a716-446655440000",
    "status":     "completed",
    "message":    "Bucket customer-a-main created",
    "output":     {}
  }
}
```

---

### C.5 command (platform → agent)

```go
type CommandPayload struct {
    Operation string          `json:"operation"`
    Params    json.RawMessage `json:"params"`
    TimeoutS  int             `json:"timeout_s"`
}
```

```json
{
  "v": 1,
  "id": "550e8400-e29b-41d4-a716-446655440000",
  "type": "command",
  "agent_type": "s3",
  "agent_uuid": "7c9e6679-7425-40de-944b-e07fc1f90ae7",
  "timestamp": 1748000000,
  "payload": {
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
}
```

**Operation → params type reference:**

| Operation | Params struct |
|---|---|
| `full_sync` | `FullSyncParams` |
| `bucket_create` | `BucketParams` |
| `bucket_delete` | `BucketDeleteParams` |
| `bucket_update` | `BucketParams` |
| `iam_create` | `IAMCreateParams` |
| `iam_delete` | `IAMDeleteParams` |
| `reconcile` | `ReconcileParams` |

```go
type FullSyncParams struct {
    Buckets []BucketParams  `json:"buckets"`
    IAMKeys []IAMCreateParams `json:"iam_keys"`
}

type BucketParams struct {
    Name               string      `json:"name"`
    Versioning         string      `json:"versioning"`          // "Enabled"|"Suspended"
    ObjectLockEnabled  bool        `json:"object_lock_enabled"`
    ObjectLockMode     *string     `json:"object_lock_mode"`    // "GOVERNANCE"|"COMPLIANCE"|null
    ObjectLockDays     *int        `json:"object_lock_days"`
    LifecycleRules     interface{} `json:"lifecycle_rules"`
}

type BucketDeleteParams struct {
    Name string `json:"name"`
}

type IAMCreateParams struct {
    AccessKey  string      `json:"access_key"`
    SecretKey  string      `json:"secret_key"`
    Role       string      `json:"role"`        // "readwrite"|"readonly"|"writeonly"|"nodelete"
    BucketACLs interface{} `json:"bucket_acls"`
}

type IAMDeleteParams struct {
    AccessKey string `json:"access_key"`
}

type ReconcileParams struct {
    Scope string `json:"scope"` // "buckets"|"iam"|"all"
}
```

---

## D. Platform-Side Integration (PHP)

### D.1 New service: `S3AgentCommandService`

Create at `app/Services/Agents/S3AgentCommandService.php`, mirroring `StorageAgentCommandService`.

Each method must:
1. Insert an `agent_commands` record with `status=pending`
2. Build the standard NATS envelope with `agent_type="s3"`
3. Publish to `agent.s3.{uuid}.cmd` via `AgentCommandService::dispatch()`
4. Return the command UUID for tracking

```php
class S3AgentCommandService
{
    public static function fullSync(string $serverUuid): string
    {
        $server  = Servers::where('uuid', $serverUuid)->firstOrFail();
        $buckets = Buckets::where('s3_server_id', $server->id)->whereNull('deleted_at')->get();
        $keys    = AccessKeys::where('s3_server_id', $server->id)->where('status', 'active')->get();

        return AgentCommandService::dispatch('s3', $serverUuid, 'full_sync', [
            'buckets'  => $buckets->map(fn($b) => [...]),
            'iam_keys' => $keys->map(fn($k) => [...]),  // decrypt secret before dispatch
        ], timeoutS: 120);
    }

    public static function bucketCreate(string $serverUuid, array $params): string { ... }
    public static function bucketDelete(string $serverUuid, string $name): string { ... }
    public static function bucketUpdate(string $serverUuid, array $params): string { ... }
    public static function iamCreate(string $serverUuid, array $key): string { ... }
    public static function iamDelete(string $serverUuid, string $accessKey): string { ... }
    public static function reconcile(string $serverUuid, string $scope = 'all'): string { ... }
}
```

> **Security note:** `iamCreate` and `fullSync` must decrypt `AccessKeys.secret_key_enc` using `S3KeyHelper::decrypt()` before including the plaintext secret in the NATS payload. The decrypted secret is only in memory during dispatch and is transmitted exclusively over TLS-encrypted NATS.

### D.2 New service: `S3AgentService`

Create at `app/Services/Agents/S3AgentService.php`. This handles all inbound `.evt` messages from seaweed.

```php
class S3AgentService
{
    public static function handle(array $envelope): void
    {
        $type    = $envelope['type'];
        $payload = $envelope['payload'];
        $server  = Servers::where('uuid', $envelope['agent_uuid'])->first();

        match ($type) {
            'heartbeat'  => static::handleHeartbeat($server, $payload),
            'telemetry'  => static::handleTelemetry($server, $payload),
            'alert'      => static::handleAlert($server, $payload),
            'result'     => static::handleResult($server, $payload),
            default      => Log::warning("Unknown s3 agent message type: {$type}"),
        };
    }

    private static function handleHeartbeat(Servers $server, array $payload): void
    {
        $server->update([
            'agent_status'      => 'connected',
            'agent_last_seen_at'=> now(),
            'agent_version'     => $payload['version'],
        ]);

        // If this was a 'pending' server, dispatch a full_sync
        if ($server->wasRecentlyPending()) {
            S3AgentCommandService::fullSync($server->uuid);
        }
    }

    private static function handleTelemetry(Servers $server, array $payload): void
    {
        // Write snapshot to s3_server_telemetry
        ServerTelemetriesService::ingest($server->uuid, $payload['seaweedfs']);

        // Update live server fields
        ServersService::updateHealthFromTelemetry($server->uuid, $payload['seaweedfs']);
    }

    private static function handleAlert(Servers $server, array $payload): void
    {
        Events::fire("alert:s3.{$payload['code']}", $server, $payload);
    }

    private static function handleResult(Servers $server, array $payload): void
    {
        $cmd = \NextDeveloper\Commons\Database\Models\AgentCommands::where('uuid', $payload['command_id'])->first();
        if ($cmd) {
            $cmd->update([
                'status'       => $payload['status'] === 'completed' ? 'completed' : 'failed',
                'result'       => $payload['output'] ?? [],
                'error'        => $payload['status'] !== 'completed' ? $payload['message'] : null,
                'completed_at' => now(),
            ]);
        }
    }
}
```

### D.3 Wire up the NATS subscriber

In `config/events.php`, add to the `nats.subscribers` array:

```php
'subscribers' => [
    'agent.s3.*.evt' => \App\Jobs\Nats\HandleAgentEventJob::class,
],
```

Update `HandleAgentEventJob` to delegate `agent_type = "s3"` messages:

```php
public function handle(): void
{
    $envelope = $this->envelope;

    match ($envelope['agent_type']) {
        'storage' => StorageAgentService::handle($envelope),
        'compute' => ComputeAgentService::handle($envelope),
        's3'      => S3AgentService::handle($envelope),
        default   => Log::warning("Unknown agent_type: {$envelope['agent_type']}"),
    };
}
```

### D.4 `full_sync` trigger points

The platform must dispatch a `full_sync` in these situations:

| Trigger | Action |
|---|---|
| First `heartbeat` from a server with `agent_status = 'pending'` | `S3AgentCommandService::fullSync($server->uuid)` |
| `BucketsService::create()` completes | `fullSync` for the bucket's server |
| `BucketsService::delete()` completes | `fullSync` for the bucket's server |
| `BucketsService::update()` completes | `S3AgentCommandService::bucketUpdate($serverUuid, $params)` |
| `AccessKeysService::create()` completes | `S3AgentCommandService::iamCreate($serverUuid, $keyData)` |
| `AccessKeysService::revoke()` completes | `S3AgentCommandService::iamDelete($serverUuid, $accessKey)` |
| Admin triggers `reconcile` action | `S3AgentCommandService::reconcile($serverUuid, $scope)` |

---

## E. Timeout & Error Handling

### Default timeouts per operation

| Operation | `timeout_s` |
|---|---|
| `full_sync` | 120 |
| `bucket_create` | 30 |
| `bucket_delete` | 30 |
| `bucket_update` | 30 |
| `iam_create` | 30 |
| `iam_delete` | 30 |
| `reconcile` | 120 |

### Stale command cleanup

The artisan command `agent:commands-timeout` runs every minute and marks commands that have not received a result by their `timeout_at` deadline:

```sql
UPDATE agent_commands
SET    status = 'timeout', updated_at = now()
WHERE  status = 'sent'
  AND  timeout_at < now()
  AND  agent_type = 's3';
```

### When seaweed sends `status=failed`

1. `HandleAgentEventJob` calls `S3AgentService::handleResult()` which sets `agent_commands.status = 'failed'`.
2. The platform fires `Events::fire('agent.s3.command.failed', $server, $cmdRecord)`.
3. Existing alert handlers pick this up — no additional plumbing needed.
4. The platform does **not** automatically retry. A retry requires a new command dispatch (e.g. admin action or a subsequent operation that triggers a `full_sync`).

---

## F. Testing with NATS CLI

Install the NATS CLI: `brew install nats-io/nats-tools/nats` or download from https://github.com/nats-io/natscli.

### Watch all seaweed traffic

```bash
nats sub 'agent.s3.>'
```

### Simulate a heartbeat from seaweed

```bash
SERVER_UUID="7c9e6679-7425-40de-944b-e07fc1f90ae7"

nats pub "agent.s3.${SERVER_UUID}.evt" '{
  "v": 1,
  "id": "test-heartbeat-001",
  "type": "heartbeat",
  "agent_type": "s3",
  "agent_uuid": "'"${SERVER_UUID}"'",
  "timestamp": '"$(date +%s)"',
  "payload": {
    "version": "1.0.0",
    "uptime_s": 100,
    "tasks_queued": 0
  }
}'
```

### Simulate a telemetry publish

```bash
nats pub "agent.s3.${SERVER_UUID}.evt" '{
  "v": 1,
  "id": "test-telemetry-001",
  "type": "telemetry",
  "agent_type": "s3",
  "agent_uuid": "'"${SERVER_UUID}"'",
  "timestamp": '"$(date +%s)"',
  "payload": {
    "cpu": { "usage_pct": 5.0, "cores": 4 },
    "ram": { "used_bytes": 2147483648, "total_bytes": 8589934592 },
    "network": { "interfaces": [] },
    "seaweedfs": {
      "master_reachable": true,
      "filer_reachable": true,
      "s3_reachable": true,
      "volume_count": 8,
      "volumes_degraded": 0,
      "capacity_bytes_total": 10737418240,
      "capacity_bytes_used": 3221225472,
      "seaweedfs_version": "3.67"
    },
    "buckets": [],
    "uptime_s": 200
  }
}'
```

### Simulate a command result

```bash
CMD_UUID="550e8400-e29b-41d4-a716-446655440000"

nats pub "agent.s3.${SERVER_UUID}.evt" '{
  "v": 1,
  "id": "test-result-001",
  "type": "result",
  "agent_type": "s3",
  "agent_uuid": "'"${SERVER_UUID}"'",
  "timestamp": '"$(date +%s)"',
  "payload": {
    "command_id": "'"${CMD_UUID}"'",
    "status":     "completed",
    "message":    "Bucket customer-a-main created",
    "output":     {}
  }
}'
```

### Manually dispatch a `full_sync` command

```bash
CMD_UUID=$(uuidgen | tr '[:upper:]' '[:lower:]')

nats pub "agent.s3.${SERVER_UUID}.cmd" '{
  "v": 1,
  "id": "'"${CMD_UUID}"'",
  "type": "command",
  "agent_type": "s3",
  "agent_uuid": "'"${SERVER_UUID}"'",
  "timestamp": '"$(date +%s)"',
  "payload": {
    "operation": "full_sync",
    "params": {
      "buckets": [],
      "iam_keys": []
    },
    "timeout_s": 120
  }
}'
```

### Manually dispatch a `bucket_create` command

```bash
nats pub "agent.s3.${SERVER_UUID}.cmd" '{
  "v": 1,
  "id": "'"$(uuidgen | tr '[:upper:]' '[:lower:]')"'",
  "type": "command",
  "agent_type": "s3",
  "agent_uuid": "'"${SERVER_UUID}"'",
  "timestamp": '"$(date +%s)"',
  "payload": {
    "operation": "bucket_create",
    "params": {
      "name": "test-bucket-01",
      "versioning": "Suspended",
      "object_lock_enabled": false,
      "object_lock_mode": null,
      "object_lock_days": null,
      "lifecycle_rules": null
    },
    "timeout_s": 30
  }
}'
```

### Verify the JetStream consumer exists

```bash
nats consumer info AGENT_COMMANDS "seaweed-${SERVER_UUID}"
```

### Check pending message count for a seaweed agent

```bash
nats consumer info AGENT_COMMANDS "seaweed-${SERVER_UUID}" | grep "Num Pending"
```
