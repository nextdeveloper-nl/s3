<?php

namespace NextDeveloper\S3\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use NextDeveloper\Commons\Exceptions\NotAllowedException;
use NextDeveloper\Commons\Helpers\DatabaseHelper;
use NextDeveloper\IAM\Helpers\UserHelper;
use NextDeveloper\S3\Database\Models\BackupAgents;
use NextDeveloper\S3\Database\Models\Buckets;
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
     * The row stays `pending` (no agent_api_key) until register() is called
     * by the agent binary itself with this token.
     *
     * s3_bucket_id is required here — the customer must already own the
     * bucket this agent will write to (created via the normal Buckets API).
     * We never provision a bucket on the customer's behalf: it's their
     * infrastructure/cost to own explicitly, and WORM buckets in particular
     * can't be created "on demand" later without losing the Object Lock
     * guarantee for anything already in a non-WORM bucket.
     */
    public static function create(array $data)
    {
        $bucket = static::resolveExistingBucket($data['s3_bucket_id'] ?? null);

        $iamAccountId = array_key_exists('iam_account_id', $data)
            ? DatabaseHelper::uuidToId('\NextDeveloper\IAM\Database\Models\Accounts', $data['iam_account_id'])
            : UserHelper::currentAccount()->id;

        if ($bucket->iam_account_id !== $iamAccountId) {
            throw new NotAllowedException('The given s3_bucket_id does not belong to your account.');
        }

        $data['s3_bucket_id']                  = $bucket->id;
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

        $bucket = Buckets::withoutGlobalScopes()->find($agent->s3_bucket_id);

        if (!$bucket) {
            throw new NotAllowedException('The bucket assigned to this agent no longer exists.');
        }

        $agentApiKey = static::generateToken();
        $accessKey   = null;

        UserHelper::runAsAdmin(function () use ($agent, $bucket, $machineInfo, $agentApiKey, &$accessKey) {
            $accessKey = AccessKeysService::create([
                's3_account_id'  => $bucket->s3_account_id,
                'iam_account_id' => $agent->iam_account_id,
                'iam_user_id'    => $agent->iam_user_id,
                'role'           => 'readwrite',
                'bucket_acls'    => [$bucket->bucket_name],
            ]);

            $agent->update(array_merge($machineInfo, [
                'agent_api_key'                 => $agentApiKey,
                'status'                        => 'active',
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
     * Resolves a bucket reference (uuid or id) to an existing Buckets row.
     * Never creates one — see the docblock on create() for why.
     */
    private static function resolveExistingBucket(mixed $ref): Buckets
    {
        $bucketId = $ref ? DatabaseHelper::uuidToId('\NextDeveloper\S3\Database\Models\Buckets', $ref) : null;
        $bucket   = $bucketId ? Buckets::withoutGlobalScopes()->find($bucketId) : null;

        if (!$bucket) {
            throw new NotAllowedException(
                'A valid s3_bucket_id is required to register a backup agent — create a bucket first via the Buckets API.'
            );
        }

        return $bucket;
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
