<?php

namespace NextDeveloper\S3\Services;

use NextDeveloper\Commons\Exceptions\NotAllowedException;
use NextDeveloper\Commons\Helpers\DatabaseHelper;
use NextDeveloper\S3\Database\Models\BackupAgents;
use NextDeveloper\S3\Database\Models\BackupJobs;
use NextDeveloper\S3\Database\Models\Buckets;
use NextDeveloper\S3\Services\AbstractServices\AbstractBackupJobsService;

/**
 * This class is responsible from managing the data for BackupJobs
 *
 * Class BackupJobsService.
 *
 * @package NextDeveloper\S3\Database\Models
 */
class BackupJobsService extends AbstractBackupJobsService
{

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE

    /**
     * Create a job, persist it, then push the updated job list to the agent
     * via full_sync — jobs are plain config (unlike buckets), so a single
     * full resync on every change is simpler than one command type per field.
     *
     * Resolves the destination bucket if the caller didn't supply one
     * explicitly — defaults to the agent's own bucket. We never provision a
     * bucket here: a job with object_lock_enabled=true must point at an
     * existing bucket that was already created with Object Lock enabled
     * (Object Lock can't be added to a bucket after the fact). See
     * resolveBucketForJob().
     */
    public static function create(array $data)
    {
        static::assertScriptHasPreScript($data);

        $agentId = DatabaseHelper::uuidToId(
            '\NextDeveloper\S3\Database\Models\BackupAgents',
            $data['s3_backup_agent_id'] ?? null
        );
        $agent = $agentId ? BackupAgents::withoutGlobalScopes()->find($agentId) : null;

        if (!$agent) {
            throw new NotAllowedException('A valid s3_backup_agent_id is required to create a backup job.');
        }

        $data['s3_bucket_id'] = static::resolveBucketForJob($agent, $data);

        $model = parent::create($data);

        static::syncAgent($model->s3_backup_agent_id);

        return $model;
    }

    /**
     * s3_backup_agent_id, s3_bucket_id and job_type are immutable after
     * creation — a job doesn't change which machine it lives on or where its
     * existing snapshots already are, and switching files<->script changes
     * what source_paths even means. object_lock_enabled follows the same
     * immutable-after-creation rule as Buckets.object_lock_enabled.
     */
    public static function update($id, array $data)
    {
        unset($data['s3_backup_agent_id'], $data['s3_bucket_id'], $data['job_type'], $data['engine'], $data['object_lock_enabled']);

        static::assertScriptHasPreScript($data, existing: BackupJobs::where('uuid', $id)->first());

        $model = parent::update($id, $data);

        static::syncAgent($model->s3_backup_agent_id);

        return $model;
    }

    /**
     * We never provision a bucket on the customer's behalf — same rule as
     * BackupAgentsService::create(). A job either targets an explicit,
     * pre-existing bucket, or falls back to the agent's own bucket.
     *
     * When object_lock_enabled is true, the resolved bucket MUST already
     * have Object Lock enabled — Object Lock can only be set at bucket
     * creation time, so there's no way to satisfy that intent on a bucket
     * that doesn't already have it. We validate rather than silently
     * ignoring the mismatch.
     */
    private static function resolveBucketForJob(BackupAgents $agent, array $data): int
    {
        if (!empty($data['s3_bucket_id'])) {
            $bucketId = DatabaseHelper::uuidToId(
                '\NextDeveloper\S3\Database\Models\Buckets',
                $data['s3_bucket_id']
            );
            $bucket = $bucketId ? Buckets::withoutGlobalScopes()->find($bucketId) : null;

            if (!$bucket || $bucket->iam_account_id !== $agent->iam_account_id) {
                throw new NotAllowedException('The given s3_bucket_id does not belong to this agent\'s account.');
            }
        } else {
            if (!$agent->s3_bucket_id) {
                throw new NotAllowedException('This agent has no bucket assigned — pass s3_bucket_id explicitly.');
            }

            $bucket = Buckets::withoutGlobalScopes()->find($agent->s3_bucket_id);
        }

        if (!empty($data['object_lock_enabled']) && !$bucket->object_lock_enabled) {
            throw new NotAllowedException(
                'object_lock_enabled is true for this job, but the target bucket does not have Object Lock ' .
                'enabled. Pass the s3_bucket_id of an existing bucket that was created with object_lock_enabled=true.'
            );
        }

        return $bucket->id;
    }

    public static function delete($id)
    {
        $model = BackupJobs::withoutGlobalScopes()->where('uuid', $id)->first();

        if (!$model) {
            throw new NotAllowedException(
                'We cannot find the related object to delete. ' .
                'Maybe you dont have the permission to delete this object?'
            );
        }

        $agentId = $model->s3_backup_agent_id;

        parent::delete($id);

        static::syncAgent($agentId);

        return true;
    }

    /**
     * Assemble every enabled job for an agent into the full_sync command
     * payload shape (see docs/backup.agent/protocol.md). Called both from
     * here (after a job CRUD change) and from BackupAgentsService::register()
     * (the very first full_sync, embedded in the registration response).
     */
    public static function buildFullSyncPayload(BackupAgents $agent): array
    {
        $jobs = BackupJobs::withoutGlobalScopes()
            ->where('s3_backup_agent_id', $agent->id)
            ->where('is_enabled', true)
            ->whereNull('deleted_at')
            ->get();

        // Batch-resolve buckets rather than N+1 — most jobs share the agent's
        // one default bucket, a few WORM jobs have their own.
        $buckets = Buckets::withoutGlobalScopes()
            ->whereIn('id', $jobs->pluck('s3_bucket_id')->filter()->unique())
            ->get()
            ->keyBy('id');

        return [
            'jobs' => $jobs->map(function (BackupJobs $job) use ($buckets) {
                $bucket = $buckets->get($job->s3_bucket_id);

                return [
                    'uuid'                 => $job->uuid,
                    'name'                 => $job->name,
                    'job_type'             => $job->job_type,
                    'source_paths'         => $job->source_paths,
                    'pre_script'           => $job->pre_script,
                    'script_timeout_s'     => $job->script_timeout_s,
                    'schedule'             => $job->schedule,
                    'keep_last_n'          => $job->keep_last_n,
                    'keep_for_days'        => $job->keep_for_days,
                    'bandwidth_limit_mbps' => $job->bandwidth_limit_mbps,
                    // Where this job's Kopia snapshots go — the whole point of this fix.
                    'bucket_name'          => $bucket?->bucket_name,
                    'object_lock_enabled'  => (bool) $job->object_lock_enabled,
                ];
            })->values()->all(),
        ];
    }

    /**
     * A script job with no script is a files job that will never run — reject
     * it at write time instead of letting the agent discover an empty
     * pre_script at 3am on its first scheduled run.
     */
    private static function assertScriptHasPreScript(array $data, ?BackupJobs $existing = null): void
    {
        $jobType   = $data['job_type']   ?? $existing?->job_type;
        $preScript = array_key_exists('pre_script', $data) ? $data['pre_script'] : $existing?->pre_script;

        if ($jobType === 'script' && empty($preScript)) {
            throw new NotAllowedException('A script-type backup job requires a non-empty pre_script.');
        }
    }

    private static function syncAgent(?int $agentId): void
    {
        if (!$agentId) {
            return;
        }

        $agent = BackupAgents::withoutGlobalScopes()->find($agentId);

        if ($agent && $agent->status === 'active') {
            BackupAgentCommandService::fullSync($agent->uuid);
        }
    }
}
