<?php

declare(strict_types=1);

namespace App\Queue\Contracts;

use Carbon\CarbonImmutable;
use DateTimeInterface;

interface QueueControlInterface
{
    public function driverName(): string;

    public function lastScheduledAt(string|int $tenantId, string $sessionId): ?CarbonImmutable;

    public function storeLastScheduledAt(string|int $tenantId, string $sessionId, DateTimeInterface $scheduledAt, int $ttlSeconds = 86400): void;

    public function incrementApproximatePosition(string|int $tenantId, string $sessionId, int $ttlSeconds = 3600): int;
}
