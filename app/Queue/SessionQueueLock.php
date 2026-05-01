<?php

declare(strict_types=1);

namespace App\Queue;

use Closure;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

final class SessionQueueLock
{
    /**
     * @template TValue
     * @param Closure(): TValue $callback
     * @return TValue
     *
     * @throws LockTimeoutException
     */
    public function run(string|int $tenantId, string $sessionId, Closure $callback): mixed
    {
        $lock = Cache::lock($this->key($tenantId, $sessionId), 10);

        if (! $lock->get()) {
            throw new RuntimeException('Could not acquire queue lock for session.');
        }

        try {
            return $callback();
        } finally {
            $lock->release();
        }
    }

    private function key(string|int $tenantId, string $sessionId): string
    {
        return "tenant:{$tenantId}:session:{$sessionId}:queue-lock";
    }
}
