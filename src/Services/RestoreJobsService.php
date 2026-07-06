<?php

namespace NextDeveloper\S3\Services;

use NextDeveloper\IAM\Helpers\UserHelper;
use NextDeveloper\S3\Database\Models\BackupJobRuns;
use NextDeveloper\S3\Database\Models\BackupJobs;
use NextDeveloper\S3\Database\Models\RestoreJobs;
use NextDeveloper\Commons\Exceptions\NotAllowedException;
use NextDeveloper\S3\Services\AbstractServices\AbstractRestoreJobsService;

/**
 * This class is responsible from managing the data for RestoreJobs
 *
 * Class RestoreJobsService.
 *
 * @package NextDeveloper\S3\Database\Models
 */
class RestoreJobsService extends AbstractRestoreJobsService
{

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE

    /**
     * Open a restore record and validate the run/engine pairing before the
     * command is ever dispatched to the agent:
     *
     * - engine=kopia jobs must restore from a specific snapshot, so $run is
     *   required and must belong to $job.
     * - engine=rsync jobs have no point-in-time snapshot — a restore always
     *   pulls current bucket state, so $run must be null.
     */
    public static function startRestore(
        BackupJobs $job,
        ?BackupJobRuns $run,
        string $destinationPath,
        array $restorePaths = [],
        string $triggeredBy = 'manual'
    ): RestoreJobs {
        if ($job->engine === 'kopia') {
            if (!$run) {
                throw new NotAllowedException(
                    'This job uses the kopia engine — a s3_backup_job_run_id is required to pick which snapshot to restore.'
                );
            }

            if ($run->s3_backup_job_id !== $job->id) {
                throw new NotAllowedException(
                    'The given backup job run does not belong to this backup job.'
                );
            }
        } elseif ($run) {
            throw new NotAllowedException(
                'This job uses the rsync engine — it has no point-in-time snapshot, so s3_backup_job_run_id must not be set. ' .
                'A restore always pulls current bucket state for the given restore_paths.'
            );
        }

        $restore = RestoreJobs::create([
            's3_backup_job_id'     => $job->id,
            's3_backup_job_run_id' => $run?->id,
            // Who asked for this restore — same default-from-authenticated-user
            // pattern as AbstractBackupJobsService::create(), bypassed here
            // (like BackupJobRunsService::startRun()) since we already have
            // resolved internal ids, not uuids, for the FK fields above.
            'iam_account_id'       => UserHelper::currentAccount()->id,
            'iam_user_id'          => UserHelper::me()->id,
            'destination_path'     => $destinationPath,
            'restore_paths'        => $restorePaths ?: null,
            'status'               => 'pending',
            'triggered_by'         => $triggeredBy,
            'started_at'           => now(),
        ]);

        // uuid is a DB-generated default (gen_random_uuid()), not set in PHP
        // before insert — refresh so the caller (BackupAgentCommandService,
        // which puts this uuid straight into the NATS payload) actually has it.
        return $restore->fresh();
    }

    /**
     * A restore only completes if the agent both finished AND the checksum
     * verification passed — see BackupAgentEventService::handleResult(),
     * which routes verified=false results to failRestore() instead.
     */
    public static function completeRestore(string $uuid, array $output = []): RestoreJobs
    {
        $restore = RestoreJobs::where('uuid', $uuid)->firstOrFail();

        $restore->update([
            'status'         => 'completed',
            'finished_at'    => now(),
            'verified'       => $output['verified'] ?? true,
            'bytes_restored' => $output['bytes_restored'] ?? null,
        ]);

        return $restore->fresh();
    }

    public static function failRestore(string $uuid, ?string $error = null): RestoreJobs
    {
        $restore = RestoreJobs::where('uuid', $uuid)->firstOrFail();

        $restore->update([
            'status'      => 'failed',
            'finished_at' => now(),
            'verified'    => false,
            'error'       => $error,
        ]);

        return $restore->fresh();
    }
}
