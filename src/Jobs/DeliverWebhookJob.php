<?php

namespace NextDeveloper\S3\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use NextDeveloper\S3\Database\Models\WebhookDeliveries;
use NextDeveloper\S3\Services\WebhookDeliveriesService;

/**
 * Deliver a single webhook event with automatic exponential-backoff retry.
 *
 * If delivery fails and the max attempt count has not been reached,
 * this job re-dispatches itself with the calculated delay.
 */
class DeliverWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 30;

    public function __construct(private int $deliveryId)
    {
    }

    public function handle(): void
    {
        $delivery = WebhookDeliveries::find($this->deliveryId);
        if (!$delivery || $delivery->delivered_at) {
            return;
        }

        $success = WebhookDeliveriesService::deliver($delivery);

        if (!$success && $delivery->next_retry_at) {
            $delay = now()->diffInSeconds($delivery->fresh()->next_retry_at, false);

            static::dispatch($this->deliveryId)
                ->delay(max(0, (int) $delay));
        }
    }
}
