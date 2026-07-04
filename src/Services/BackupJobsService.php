<?php

namespace NextDeveloper\S3\Services;

use NextDeveloper\Commons\Exceptions\NotAllowedException;
use NextDeveloper\S3\Database\Models\BackupAgents;
use NextDeveloper\S3\Database\Models\BackupJobs;
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
     */
    public static function create(array $data)
    {
        static::assertScriptHasPreScript($data);

        $model = parent::create($data);

        static::syncAgent($model->s3_backup_agent_id);

        return $model;
    }

    /**
     * s3_backup_agent_id and job_type are immutable after creation — a job
     * doesn't change which machine it lives on, and switching files<->script
     * changes what source_paths even means. object_lock_enabled follows the
     * same immutable-after-creation rule as Buckets.object_lock_enabled.
     */
    public static function update($id, array $data)
    {
        unset($data['s3_backup_agent_id'], $data['job_type'], $data['object_lock_enabled']);

        static::assertScriptHasPreScript($data, existing: BackupJobs::where('uuid', $id)->first());

        $model = parent::update($id, $data);

        static::syncAgent($model->s3_backup_agent_id);

        return $model;
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

        return [
            'jobs' => $jobs->map(fn (BackupJobs $job) => [
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
            ])->values()->all(),
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
