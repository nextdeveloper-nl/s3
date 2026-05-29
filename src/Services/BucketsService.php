<?php

namespace NextDeveloper\S3\Services;

use NextDeveloper\Commons\Exceptions\NotAllowedException;
use NextDeveloper\S3\Database\Models\Accounts;
use NextDeveloper\S3\Services\AbstractServices\AbstractBucketsService;

/**
 * This class is responsible from managing the data for Buckets
 *
 * Class BucketsService.
 *
 * @package NextDeveloper\S3\Database\Models
 */
class BucketsService extends AbstractBucketsService
{

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE

    /**
     * Create a bucket, validating quota and name format.
     */
    public static function create(array $data)
    {
        // Validate bucket name: lowercase alphanumeric + hyphens only, 3–63 chars
        if (!empty($data['name']) && !preg_match('/^[a-z0-9][a-z0-9\-]{1,61}[a-z0-9]$/', $data['name'])) {
            throw new NotAllowedException(
                'Bucket name must be 3–63 lowercase alphanumeric characters or hyphens, and cannot start or end with a hyphen.'
            );
        }

        // Enforce max_buckets quota for the account
        if (!empty($data['s3_account_id'])) {
            $account = Accounts::where('id', $data['s3_account_id'])->first();

            if ($account && $account->quota_max_buckets > 0) {
                $currentCount = \NextDeveloper\S3\Database\Models\Buckets::where('s3_account_id', $data['s3_account_id'])
                    ->whereNull('deleted_at')
                    ->count();

                if ($currentCount >= $account->quota_max_buckets) {
                    throw new NotAllowedException(
                        "Bucket quota exceeded: this account allows a maximum of {$account->quota_max_buckets} buckets."
                    );
                }
            }
        }

        $data['status'] = $data['status'] ?? 'active';

        return parent::create($data);
    }

    /**
     * Update a bucket. Object lock fields are immutable post-creation.
     */
    public static function update($id, array $data)
    {
        // Strip immutable object lock fields silently — they were set at creation
        foreach (['object_lock_enabled', 'object_lock_mode', 'object_lock_days'] as $field) {
            unset($data[$field]);
        }

        return parent::update($id, $data);
    }
}