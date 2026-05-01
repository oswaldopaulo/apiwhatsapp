<?php

declare(strict_types=1);

namespace App\Queue;

use App\Queue\Contracts\QueueControlInterface;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\Cache;

final class DatabaseQueueControl implements QueueControlInterface
{
    public function driverName(): string
    {
        return 'cache';
    }

    public function lastScheduledAt(string|int $tenantId, string $sessionId): ?CarbonImmutable
    {
        $timestamp = Cache::get($this->lastScheduledKey($tenantId, $sessionId));

        if ($timestamp === null) {
            return null;
        }

        return CarbonImmutable::createFromTimestamp((int) $timestamp);
    }

    public function storeLastScheduledAt(string|int $tenantId, string $sessionId, DateTimeInterface $scheduledAt, int $ttlSeconds = 86400): void
    {
        Cache::put(
            $this->lastScheduledKey($tenantId, $sessionId),
            $scheduledAt->getTimestamp(),
            $ttlSeconds,
        );
    }

    public function incrementApproximatePosition(string|int $tenantId, string $sessionId, int $ttlSeconds = 3600): int
    {
        $key = $this->positionKey($tenantId, $sessionId);

        if (! Cache::has($key)) {
            Cache::put($key, 0, $ttlSeconds);
        }

        $position = (int) Cache::increment($key);
        Cache::put($key, $position, $ttlSeconds);

        return $position;
    }

    private function lastScheduledKey(string|int $tenantId, string $sessionId): string
    {
        return "tenant:{$tenantId}:session:{$sessionId}:last-scheduled-at";
    }

    private function positionKey(string|int $tenantId, string $sessionId): string
    {
        return "tenant:{$tenantId}:session:{$sessionId}:queue-position";
    }
}
