<?php

namespace NextDeveloper\S3\Actions\Webhooks;

use NextDeveloper\Commons\Actions\AbstractAction;
use NextDeveloper\S3\Database\Models\Webhooks;
use NextDeveloper\S3\Services\WebhooksService;

/**
 * Resumes a paused webhook, re-enabling event delivery.
 *
 * Also resets the failure counter so the webhook gets a fresh retry slate.
 */
class Resume extends AbstractAction
{
    public const EVENTS = [
        'webhook-resuming:NextDeveloper\S3\Webhooks',
        'webhook-resumed:NextDeveloper\S3\Webhooks',
    ];

    public function __construct(Webhooks $webhook, $params = null)
    {
        $this->model = $webhook;
        $this->queue = 's3';

        parent::__construct();
    }

    public function handle(): void
    {
        $this->setProgress(0, 'Resuming webhook');

        if ($this->model->status === 'active') {
            $this->setFinished('Webhook is already active, nothing to do.');
            return;
        }

        WebhooksService::update($this->model->uuid, [
            'status'        => 'active',
            'paused_at'     => null,
            'failure_count' => 0,
        ]);

        $this->setProgress(100, 'Webhook resumed and failure counter reset');
    }
}
