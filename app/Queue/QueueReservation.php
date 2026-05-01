<?php

declare(strict_types=1);

namespace App\Queue;

use Carbon\CarbonImmutable;

final readonly class QueueReservation
{
    public function __construct(
        public string $messageId,
        public string|int $tenantId,
        public string $sessionId,
        public int $delaySeconds,
        public int $queuePositionSnapshot,
        public CarbonImmutable $scheduledAt,
        public string $controlDriver,
    ) {
    }
}
