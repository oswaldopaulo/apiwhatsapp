<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\TenantConfiguration;
use App\Queue\QueueDelayCalculator;
use App\Queue\SessionRateLimiter;
use Carbon\CarbonImmutable;
use Tests\TestCase;

final class QueueDelayCalculatorTest extends TestCase
{
    public function test_it_applies_jitter_and_rate_limit_per_session(): void
    {
        $now = CarbonImmutable::parse('2026-05-01 12:00:00');
        CarbonImmutable::setTestNow($now);

        $calculator = new QueueDelayCalculator(new SessionRateLimiter());
        $configuration = new TenantConfiguration([
            'anti_ban_enabled' => true,
            'delay_min_seconds' => 5,
            'delay_max_seconds' => 5,
            'max_messages_per_minute' => 30,
        ]);

        $this->assertSame(5, $calculator->calculate($configuration, null, $now));
        $this->assertSame(12, $calculator->calculate($configuration, $now->addSeconds(10), $now));

        CarbonImmutable::setTestNow();
    }

    public function test_it_returns_zero_when_anti_ban_is_disabled(): void
    {
        $calculator = new QueueDelayCalculator(new SessionRateLimiter());
        $configuration = new TenantConfiguration([
            'anti_ban_enabled' => false,
            'delay_min_seconds' => 10,
            'delay_max_seconds' => 20,
            'max_messages_per_minute' => 1,
        ]);

        $this->assertSame(0, $calculator->calculate($configuration, CarbonImmutable::now()));
    }
}
