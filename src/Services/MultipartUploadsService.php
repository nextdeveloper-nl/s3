<?php

namespace NextDeveloper\S3\Services;

use NextDeveloper\IAM\Helpers\UserHelper;
use NextDeveloper\S3\Database\Models\MultipartUploads;
use NextDeveloper\S3\Services\AbstractServices\AbstractMultipartUploadsService;

/**
 * This class is responsible from managing the data for MultipartUploads
 *
 * Class MultipartUploadsService.
 *
 * @package NextDeveloper\S3\Database\Models
 */
class MultipartUploadsService extends AbstractMultipartUploadsService
{

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE

    /**
     * Abort and soft-delete multipart uploads that have been inactive for more than 48 hours.
     *
     * @return int  Number of uploads cleaned up
     */
    public static function cleanup(): int
    {
        // withoutGlobalScopes() + runAsAdmin(): this runs from the scheduler with no
        // authenticated user, so the AuthorizationScope would otherwise match nothing,
        // and model observer writes would be denied by UserHelper::can().
        return UserHelper::runAsAdmin(function () {
            $stale = MultipartUploads::withoutGlobalScopes()
                ->where('status', 'in_progress')
                ->where('last_activity_at', '<', now()->subHours(48))
                ->get();

            foreach ($stale as $upload) {
                $upload->update([
                    'status'         => 'aborted',
                    'aborted_at'     => now(),
                    'aborted_reason' => 'Auto-aborted: inactive for more than 48 hours',
                ]);

                AuditLogsService::log('multipart.auto_abort', 'system', [
                    's3_account_id' => $upload->s3_account_id,
                    's3_bucket_id'  => $upload->s3_bucket_id,
                    'upload_id'     => $upload->upload_id,
                ]);

                $upload->delete();
            }

            return $stale->count();
        });
    }
}