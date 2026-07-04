# backup.agent — Registration

Every other agent type in this system (`vm`, `storage`, `compute`, `network`, `s3`)
is pre-provisioned: the member row and its `agent_api_key` exist before the agent
process ever starts, delivered via a config-drive ISO or written directly at
VM-creation time. backup.agent can't work that way — it runs on a customer's own
arbitrary machine (a laptop, an on-prem server, a VM at another provider) that has
no pre-existing relationship with this platform at all.

So it needs a real bootstrap handshake. This is modeled on the one already built
for the in-VM "Managed Services" agent
(`App\Http\Controllers\ManagedServices\AgentRegistrationController` — one-time
token → `POST /agents/register` → session credentials), not on the SSE/REST design
that was tried and abandoned for the S3 agent (see storaged's own history: it
shipped with a REST/SSE design, then explicitly moved to NATS in v0.2.0).

## Flow

### 1. Customer issues a registration token

```
POST /s3/backup-agents/
{
  "s3_bucket_id": "<uuid of an existing bucket>"
}
```

IAM-authenticated, standard dashboard/API call. Maps to
`BackupAgentsService::create()`, which does **not** create an active agent — it
creates a `pending` row with a one-time `registration_token` (48-char random,
URL-safe) and a 1-hour expiry.

`s3_bucket_id` is **required** and must reference a bucket the customer already
owns (created via the normal Buckets API beforehand). backup.agent never
provisions a bucket on the customer's behalf — this is deliberate: bucket
creation is a billable, visible action the customer takes explicitly, and it
means a WORM (Object Lock) bucket the customer wants to use can only ever be
one they created and configured themselves, since Object Lock can't be added
to a bucket after the fact anyway.

Response: the pending `BackupAgents` record, including the `registration_token`
the customer copies into the install command on their machine (e.g.
`backup-agent register --token=<token> --endpoint=https://api.example.com`).

### 2. Agent calls the public register endpoint

```
POST /backup-agents/register
{
  "token": "<registration_token>",
  "hostname": "db01.internal",
  "os": "linux",
  "arch": "amd64",
  "machine_fingerprint": "...",
  "agent_version": "1.0.0"
}
```

This route is **not** part of this package's IAM-authenticated route group
(`src/Http/api.routes.php`, mounted under the `s3` prefix). It's registered
directly in the host app's `routes/api.php`, inside a
`Route::prefix('/backup-agents')->withoutMiddleware($iamMiddleware)` group — the
same pattern already used for `/agents/register` (VM managed-services agent).
Auth here is the one-time token in the body, not a bearer/session credential.

Maps to `BackupAgentsService::register()`, which:

1. Looks up the `pending` row by `registration_token`, rejects if not found or
   expired.
2. Loads the bucket that was already assigned at token-issuance time
   (`agent.s3_bucket_id`) — no account/server resolution needed here, since
   the bucket (and therefore its S3 account) already exists.
3. Creates a scoped IAM access key for that bucket via the existing
   `AccessKeysService::create()`.
4. Generates the NATS credential (`agent_api_key`, same 48-char random format),
   flips `status` to `active`, clears the token.

Response — this **is** the agent's entire bootstrap config, written to its local
config file and used for every NATS connection from then on:

```json
{
  "agent_uuid": "...",
  "agent_api_key": "...",
  "nats": { "host": "...", "port": 4222 },
  "bucket": { "name": "backup-agent-a1b2c3d4", "endpoint": "..." },
  "access_key": { "access_key": "AKIA...", "secret_key": "..." },
  "jobs": { "jobs": [ /* full_sync payload — see protocol.md */ ] }
}
```

The `secret_key` and `agent_api_key` are shown exactly once, the same rule that
already applies to every other S3 access key in this system — losing them means
re-registering, not recovering them.

### 3. From here on, it's just another agent

Once bootstrapped, backup.agent behaves like every other agent type: connect to
NATS with `nats.UserInfo(agent_uuid, agent_api_key)`, subscribe to
`agent.backup.{uuid}.cmd`, publish heartbeat/telemetry/job outcomes to
`agent.backup.{uuid}.evt`. `NatsAuthCalloutService` validates the credential
against `s3_backup_agents.agent_api_key` exactly like it does for
`s3_servers.agent_api_key` — one entry in `AGENT_TABLES`, no special-casing.

## Revocation

```
POST /s3/backup-agents/{uuid}/revoke
```

IAM-authenticated. `BackupAgentsService::revoke()` sends the `revoke` command
*first* — while the agent can still receive it — then clears `agent_api_key` and
sets `status = revoked`. The agent is rejected on its next NATS reconnect attempt,
same rule as every other agent type in this system.
