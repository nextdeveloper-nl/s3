<?php

namespace NextDeveloper\S3\Services;

use Illuminate\Support\Str;
use NextDeveloper\Commons\Exceptions\NotAllowedException;
use NextDeveloper\S3\Services\AbstractServices\AbstractWebhooksService;

/**
 * This class is responsible from managing the data for Webhooks
 *
 * Class WebhooksService.
 *
 * @package NextDeveloper\S3\Database\Models
 */
class WebhooksService extends AbstractWebhooksService
{

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE

    private const ALLOWED_EVENT_TYPES = [
        'ObjectCreated:*',
        'ObjectCreated:Put',
        'ObjectCreated:CompleteMultipartUpload',
        'ObjectDeleted:*',
        'ObjectDeleted:Delete',
    ];

    /**
     * Create a webhook subscription. Validates event types and generates signing secret.
     */
    public static function create(array $data)
    {
        if (!empty($data['events'])) {
            $events = is_array($data['events']) ? $data['events'] : json_decode($data['events'], true);
            $invalid = array_diff($events, self::ALLOWED_EVENT_TYPES);

            if (!empty($invalid)) {
                throw new NotAllowedException(
                    'Invalid event types: ' . implode(', ', $invalid) . '. Allowed: ' . implode(', ', self::ALLOWED_EVENT_TYPES)
                );
            }
        }

        // Generate a signing secret used for HMAC-SHA256 payload signatures
        $data['secret'] = $data['secret'] ?? Str::random(32);
        $data['status']        = 'active';
        $data['failure_count'] = 0;

        return parent::create($data);
    }
}