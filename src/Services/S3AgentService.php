<?php

namespace NextDeveloper\S3\Services;

use NextDeveloper\S3\Jobs\Nats\HandleS3AgentEventJob;

/**
 * Thin, synchronous facade over HandleS3AgentEventJob (NextDeveloper\S3\Jobs\Nats),
 * which now owns all the actual inbound-event handling logic. Kept so existing
 * direct callers (tests, anything invoking S3AgentService::handle() outside the
 * NATS queue) don't need to change - it just runs the job inline instead of
 * going through the queue.
 *
 * Subscribed subject: agent.s3.*.evt
 * Envelope format: see docs/agent/seaweed-nats-contract.md §C
 */
class S3AgentService
{
    public static function handle(array $envelope): void
    {
        (new HandleS3AgentEventJob($envelope, 'agent.s3.*.evt'))->handle();
    }
}
