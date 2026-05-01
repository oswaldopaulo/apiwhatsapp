<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Queue\DatabaseQueueControl;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class DatabaseQueueControlTest extends TestCase
{
    public function test_it_tracks_sessions_independently(): void
    {
        Cache::flush();

        $control = new DatabaseQueueControl();
        $tenantId = 10;
        $sessionA = 'session-a';
        $sessionB = 'session-b';
        $scheduledA = CarbonImmutable::parse('2026-05-01 12:00:10');
        $scheduledB = CarbonImmutable::parse('2026-05-01 12:00:30');

        $control->storeLastScheduledAt($tenantId, $sessionA, $scheduledA);
        $control->storeLastScheduledAt($tenantId, $sessionB, $scheduledB);

        $this->assertSame($scheduledA->timestamp, $control->lastScheduledAt($tenantId, $sessionA)?->timestamp);
        $this->assertSame($scheduledB->timestamp, $control->lastScheduledAt($tenantId, $sessionB)?->timestamp);
        $this->assertSame(1, $control->incrementApproximatePosition($tenantId, $sessionA));
        $this->assertSame(2, $control->incrementApproximatePosition($tenantId, $sessionA));
        $this->assertSame(1, $control->incrementApproximatePosition($tenantId, $sessionB));
    }
}
