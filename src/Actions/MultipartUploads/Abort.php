<?php

namespace NextDeveloper\S3\Actions\MultipartUploads;

use NextDeveloper\Commons\Actions\AbstractAction;
use NextDeveloper\S3\Database\Models\MultipartUploads;
use NextDeveloper\S3\Services\AuditLogsService;

/**
 * Manually aborts a specific in-progress multipart upload.
 *
 * For bulk cleanup of stale uploads, use the s3:cleanup-multipart command
 * or CleanupMultipartUploadsJob instead.
 */
class Abort extends AbstractAction
{
    public const EVENTS = [
        'multipart-aborting:NextDeveloper\S3\MultipartUploads',
        'multipart-aborted:NextDeveloper\S3\MultipartUploads',
    ];

    public function __construct(MultipartUploads $multipartUpload, $params = null)
    {
        $this->model = $multipartUpload;
        $this->queue = 's3';

        parent::__construct();
    }

    public function handle(): void
    {
        $this->setProgress(0, 'Aborting multipart upload');

        if ($this->model->status !== 'in_progress') {
            $this->setFinished("Upload is already in status '{$this->model->status}', nothing to abort.");
            return;
        }

        $reason = is_array($this->params) && !empty($this->params['reason'])
            ? $this->params['reason']
            : 'Manually aborted via action';

        $this->model->update([
            'status'         => 'aborted',
            'aborted_at'     => now(),
            'aborted_reason' => $reason,
        ]);

        AuditLogsService::log('multipart.abort', \NextDeveloper\IAM\Helpers\UserHelper::me()->uuid ?? 'system', [
            's3_account_id' => $this->model->s3_account_id,
            's3_bucket_id'  => $this->model->s3_bucket_id,
            'upload_id'     => $this->model->upload_id,
            'reason'        => $reason,
        ]);

        $this->model->delete();

        $this->setProgress(100, 'Multipart upload aborted: ' . $reason);
    }
}
