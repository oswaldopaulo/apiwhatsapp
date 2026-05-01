<?php

declare(strict_types=1);

namespace App\Queue;

use App\Queue\Contracts\QueueControlInterface;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Support\Facades\Redis;

final class RedisQueueControl implements QueueControlInterface
{
    public function driverName(): string
    {
        return 'redis';
    }

    public function lastScheduledAt(string|int $tenantId, string $sessionId): ?CarbonImmutable
    {
        $timestamp = Redis::connection($this->connection())->get($this->lastScheduledKey($tenantId, $sessionId));

        if ($timestamp === null) {
            return null;
        }

        return CarbonImmutable::createFromTimestamp((int) $timestamp);
    }

    public function storeLastScheduledAt(string|int $tenantId, string $sessionId, DateTimeInterface $scheduledAt, int $ttlSeconds = 86400): void
    {
        Redis::connection($this->connection())->setex(
            $this->lastScheduledKey($tenantId, $sessionId),
            $ttlSeconds,
            (string) $scheduledAt->getTimestamp(),
        );
    }

    public function incrementApproximatePosition(string|int $tenantId, string $sessionId, int $ttlSeconds = 3600): int
    {
        $redis = Redis::connection($this->connection());
        $key = $this->positionKey($tenantId, $sessionId);
        $position = (int) $redis->incr($key);

        if ($position === 1) {
            $redis->expire($key, $ttlSeconds);
        }

        return $position;
    }

    private function connection(): string
    {
        return (string) config('queue-control.redis.queue_connection', 'default');
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
