<?php

namespace NextDeveloper\S3\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use NextDeveloper\S3\Database\Models\WebhookDeliveries;
use NextDeveloper\S3\Database\Models\Webhooks;
use NextDeveloper\S3\Services\AbstractServices\AbstractWebhookDeliveriesService;

/**
 * This class is responsible from managing the data for WebhookDeliveries
 *
 * Class WebhookDeliveriesService.
 *
 * @package NextDeveloper\S3\Database\Models
 */
class WebhookDeliveriesService extends AbstractWebhookDeliveriesService
{

    // EDIT AFTER HERE - WARNING: ABOVE THIS LINE MAY BE REGENERATED AND YOU MAY LOSE CODE

    // Exponential backoff delays in seconds for each attempt (1-indexed)
    private const RETRY_DELAYS = [1 => 1, 2 => 4, 3 => 16, 4 => 64, 5 => 256];
    private const MAX_ATTEMPTS = 5;
    private const AUTO_PAUSE_THRESHOLD = 5;

    /**
     * Attempt to deliver a webhook. Updates the delivery record with the outcome.
     *
     * On failure, schedules the next retry via DeliverWebhookJob unless max attempts reached.
     */
    public static function deliver(WebhookDeliveries $delivery): bool
    {
        $webhook = Webhooks::find($delivery->s3_webhook_id);
        if (!$webhook || $webhook->status !== 'active') {
            return false;
        }

        $payload = is_string($delivery->payload)
            ? $delivery->payload
            : json_encode($delivery->payload);

        $signature = hash_hmac('sha256', $payload, $webhook->secret);

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Content-Type'      => 'application/json',
                    'X-S3-Event'        => $delivery->event_type,
                    'X-Signature-SHA256' => $signature,
                ])
                ->post($webhook->endpoint_url, json_decode($payload, true));

            $statusCode = $response->status();
            $success = $response->successful();
        } catch (\Exception) {
            $statusCode = 0;
            $success = false;
        }

        if ($success) {
            $delivery->update([
                'status_code'  => $statusCode,
                'delivered_at' => now(),
            ]);

            // Reset failure count on the webhook if it had prior failures
            if ($webhook->failure_count > 0) {
                $webhook->update(['failure_count' => 0]);
            }

            return true;
        }

        // Delivery failed — record attempt and schedule retry
        $nextAttempt = $delivery->attempt + 1;
        $delivery->update([
            'status_code' => $statusCode,
            'attempt'     => $delivery->attempt + 1,
            'next_retry_at' => $nextAttempt <= self::MAX_ATTEMPTS
                ? now()->addSeconds(self::RETRY_DELAYS[$nextAttempt] ?? 256)
                : null,
        ]);

        // Increment webhook failure counter and auto-pause if threshold reached
        $newFailureCount = $webhook->failure_count + 1;
        $webhookUpdate = ['failure_count' => $newFailureCount];

        if ($newFailureCount >= self::AUTO_PAUSE_THRESHOLD) {
            $webhookUpdate['status']    = 'paused';
            $webhookUpdate['paused_at'] = now();
        }

        $webhook->update($webhookUpdate);

        return false;
    }

    /**
     * Calculate when to retry based on the attempt number (1-based).
     */
    public static function calculateNextRetry(int $attemptNumber): Carbon
    {
        $delaySecs = self::RETRY_DELAYS[$attemptNumber] ?? 256;
        return Carbon::now()->addSeconds($delaySecs);
    }
}