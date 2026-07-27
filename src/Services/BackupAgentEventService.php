<?php

namespace NextDeveloper\S3\Services;

use NextDeveloper\S3\Jobs\Nats\HandleBackupAgentEventJob;

/**
 * Thin, synchronous facade over HandleBackupAgentEventJob (NextDeveloper\S3\Jobs\Nats),
 * which now owns all the actual inbound-event handling logic. Kept so existing
 * direct callers (tests, anything invoking BackupAgentEventService::handle()
 * outside the NATS queue) don't need to change - it just runs the job inline
 * instead of going through the queue.
 *
 * Subscribed subject: agent.backup.*.evt
 * Envelope format: see docs/backup.agent/protocol.md
 */
class BackupAgentEventService
{
    public static function handle(array $envelope): void
    {
        (new HandleBackupAgentEventJob($envelope, 'agent.backup.*.evt'))->handle();
    }
}
