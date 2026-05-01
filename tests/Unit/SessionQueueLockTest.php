<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Queue\SessionQueueLock;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Tests\TestCase;

final class SessionQueueLockTest extends TestCase
{
    public function test_it_rejects_double_concurrency_for_same_session(): void
    {
        $tenantId = 123;
        $sessionId = 'session-a';
        $lock = Cache::lock("tenant:{$tenantId}:session:{$sessionId}:queue-lock", 10);
        $lock->get();

        try {
            $this->expectException(RuntimeException::class);

            app(SessionQueueLock::class)->run($tenantId, $sessionId, static fn (): bool => true);
        } finally {
            $lock->release();
        }
    }
}
