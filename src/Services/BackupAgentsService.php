<?php

namespace NextDeveloper\S3\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use NextDeveloper\Commons\Exceptions\NotAllowedException;
use NextDeveloper\IAM\Helpers\UserHelper;
use NextDeveloper\S3\Database\Models\Accounts;
use NextDeveloper\S3\Database\Models\BackupAgents;
use NextDeveloper\S3\Database\Models\Servers;
use NextDeveloper\S3\Services\AbstractServices\AbstractBackupAgentsService;

/**
 * This class is responsible from managing the data for BackupAgents
 *
 * Class BackupAgentsService.
 *
 * @package NextDeveloper\S3\Database\Models
 */
class BackupAgentsService extends AbstractBackupAgentsService
{

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE

    /**
     * store() on the controller calls this — it does NOT create an active agent.
     * It issues a one-time registration token for a not-yet-installed agent.
     * The row stays `pending` (no agent_api_key, no bucket) until register()
     * is called by the agent binary itself with this token.
     */
    public static function create(array $data)
    {
        $data['status']                        = 'pending';
        $data['registration_token']             = static::generateToken();
        $data['registration_token_expires_at']  = now()->addHour();

        return parent::create($data);
    }

    /**
     * Exchanges a one-time registration token for live NATS credentials.
     *
     * Called from an unauthenticated route (token-only, see BackupAgentsController::register())
     * so every FK below is resolved from the pending agent row's own owner —
     * never from UserHelper::currentAccount()/me(), which have nothing to
     * resolve outside a logged-in request.
     *
     * @param  string $token       The one-time registration_token
     * @param  array  $machineInfo hostname/os/arch/machine_fingerprint/agent_version reported by the agent
     * @return array               Bootstrap payload the agent persists to its local config file
     */
    public static function register(string $token, array $machineInfo): array
    {
        if (empty($token)) {
            throw new NotAllowedException('Registration token is required.');
        }

        $agent = BackupAgents::withoutGlobalScopes()
            ->where('registration_token', $token)
            ->where('status', 'pending')
            ->first();

        if (!$agent) {
            throw new NotAllowedException('Invalid or already-used registration token.');
        }

        if ($agent->registration_token_expires_at && Carbon::parse($agent->registration_token_expires_at)->isPast()) {
            throw new NotAllowedException('Registration token has expired. Please issue a new one from the dashboard.');
        }

        $agentApiKey = static::generateToken();

        $bucket    = null;
        $accessKey = null;

        UserHelper::runAsAdmin(function () use ($agent, $machineInfo, $agentApiKey, &$bucket, &$accessKey) {
            $s3Account = static::resolveOrCreateS3Account($agent->iam_account_id, $agent->iam_user_id);
            $server    = static::pickServerForNewBucket();

            $bucket = BucketsService::create([
                's3_account_id'  => $s3Account->id,
                's3_server_id'   => $server->uuid,
                'iam_account_id' => $agent->iam_account_id,
                'iam_user_id'    => $agent->iam_user_id,
                // Short, stable, unique per agent — customer never has to name this bucket themselves.
                'name'           => 'backup-agent-' . Str::before($agent->uuid, '-'),
            ]);

            $accessKey = AccessKeysService::create([
                's3_account_id'  => $s3Account->id,
                'iam_account_id' => $agent->iam_account_id,
                'iam_user_id'    => $agent->iam_user_id,
                'role'           => 'readwrite',
                'bucket_acls'    => [$bucket->bucket_name],
            ]);

            $agent->update(array_merge($machineInfo, [
                'agent_api_key'                 => $agentApiKey,
                'status'                        => 'active',
                's3_bucket_id'                  => $bucket->id,
                'registration_token'             => null,
                'registration_token_expires_at'  => null,
                'last_seen_at'                   => now(),
            ]));
        });

        Log::info('[BackupAgentsService] Agent registered', [
            'agent_uuid' => $agent->uuid,
            'bucket'     => $bucket->bucket_name,
        ]);

        return [
            'agent_uuid'    => $agent->uuid,
            'agent_api_key' => $agentApiKey,
            'nats'          => [
                'host' => config('events.nats.host'),
                'port' => config('events.nats.port'),
            ],
            'bucket' => [
                'name' => $bucket->bucket_name,
                // TODO: confirm the real public S3 endpoint convention for this server
                // (see vendor/nextdeveloper/s3/docs/client-setup-guide.md §1) — hostname
                // is the closest field on Servers today but may need a scheme/domain wrapper.
                'endpoint' => $bucket->servers?->hostname,
            ],
            'access_key' => [
                'access_key' => $accessKey->access_key,
                'secret_key' => $accessKey->plain_secret,
            ],
            'jobs' => BackupJobsService::buildFullSyncPayload($agent->fresh()),
        ];
    }

    /**
     * Customer-triggered (IAM-authenticated) revocation — see BackupAgentsController::revoke().
     *
     * Sends the revoke command first, while the agent can still receive it,
     * then clears agent_api_key so the NATS auth callout rejects it on next
     * reconnect (same rule as every other agent type — see
     * NatsAuthCalloutService's revocation note).
     */
    public static function revoke(string $uuid, string $reason = ''): BackupAgents
    {
        $agent = BackupAgents::where('uuid', $uuid)->firstOrFail();

        BackupAgentCommandService::revoke($agent->uuid, $reason);

        $agent->update([
            'status'        => 'revoked',
            'agent_api_key' => null,
        ]);

        return $agent->fresh();
    }

    /**
     * Resolve the S3 tenant account for a given IAM account, auto-provisioning
     * one if this is the first S3 resource that account has ever created —
     * mirrors the inline logic in BucketsService::create(), parameterized by
     * an explicit account instead of UserHelper::currentAccount().
     */
    private static function resolveOrCreateS3Account(int $iamAccountId, int $iamUserId): Accounts
    {
        $s3Account = Accounts::withoutGlobalScopes()
            ->where('iam_account_id', $iamAccountId)
            ->first();

        if ($s3Account) {
            return $s3Account;
        }

        $iamAccount = \NextDeveloper\IAM\Database\Models\Accounts::find($iamAccountId);

        return AccountsService::create([
            'iam_account_id' => $iamAccountId,
            'iam_user_id'    => $iamUserId,
            'slug'           => $iamAccount->slug ?? $iamAccount->uuid,
        ]);
    }

    /**
     * Pick a storage server to host a newly-provisioned backup-agent bucket.
     *
     * Placeholder policy — just the first non-deleted server. This should
     * become capacity/health-aware placement once there's more than one
     * production S3 server (see the "Server 2 / replication" gap already
     * tracked in this package's CLAUDE.md).
     */
    private static function pickServerForNewBucket(): Servers
    {
        $server = Servers::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->first();

        if (!$server) {
            throw new NotAllowedException('No S3 storage server is available to host a backup bucket.');
        }

        return $server;
    }

    /**
     * 48-char URL-safe random token — used for both registration_token and
     * agent_api_key. Plaintext at rest, same as s3_servers.agent_api_key;
     * NatsAuthCalloutService compares it directly, no decryption step.
     */
    private static function generateToken(): string
    {
        $raw = random_bytes(36);

        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }
}
