<?php

namespace NextDeveloper\S3\Actions\Webhooks;

use NextDeveloper\Commons\Actions\AbstractAction;
use NextDeveloper\S3\Database\Models\Webhooks;
use NextDeveloper\S3\Services\WebhooksService;

/**
 * Pauses a webhook subscription, stopping all event deliveries.
 *
 * Deliveries already in the queue will be discarded on their next attempt.
 * Use Resume to re-activate.
 */
class Pause extends AbstractAction
{
    public const EVENTS = [
        'webhook-pausing:NextDeveloper\S3\Webhooks',
        'webhook-paused:NextDeveloper\S3\Webhooks',
    ];

    public function __construct(Webhooks $webhook, $params = null)
    {
        $this->model = $webhook;
        $this->queue = 's3';

        parent::__construct();
    }

    public function handle(): void
    {
        $this->setProgress(0, 'Pausing webhook');

        if ($this->model->status === 'paused') {
            $this->setFinished('Webhook is already paused, nothing to do.');
            return;
        }

        WebhooksService::update($this->model->uuid, [
            'status'    => 'paused',
            'paused_at' => now(),
        ]);

        $this->setProgress(100, 'Webhook paused');
    }
}
