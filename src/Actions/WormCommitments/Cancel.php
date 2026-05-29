<?php

namespace NextDeveloper\S3\Actions\WormCommitments;

use NextDeveloper\Commons\Actions\AbstractAction;
use NextDeveloper\S3\Database\Models\WormCommitments;
use NextDeveloper\S3\Services\WormCommitmentsService;

/**
 * Cancels a GOVERNANCE WORM commitment and issues a pro-rata refund.
 *
 * COMPLIANCE commitments cannot be cancelled and will cause this action
 * to fail with an error. The refund is calculated as:
 *   deposit × (days_remaining / retention_days)
 */
class Cancel extends AbstractAction
{
    public const EVENTS = [
        'worm-cancelling:NextDeveloper\S3\WormCommitments',
        'worm-cancelled:NextDeveloper\S3\WormCommitments',
    ];

    public function __construct(WormCommitments $wormCommitment, $params = null)
    {
        $this->model = $wormCommitment;
        $this->queue = 's3';

        parent::__construct();
    }

    public function handle(): void
    {
        $this->setProgress(0, 'Cancelling WORM commitment');

        if ($this->model->status !== 'active') {
            $this->setFinishedWithError("Cannot cancel: commitment status is '{$this->model->status}' (must be 'active').");
            return;
        }

        if (strtoupper($this->model->mode) === 'COMPLIANCE') {
            $this->setFinishedWithError('Cannot cancel a COMPLIANCE WORM commitment. Only GOVERNANCE commitments can be cancelled.');
            return;
        }

        WormCommitmentsService::cancel($this->model->uuid);

        $this->setProgress(100, 'WORM commitment cancelled and pro-rata refund recorded');
    }
}
