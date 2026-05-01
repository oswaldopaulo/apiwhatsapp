<?php

declare(strict_types=1);

namespace App\Queue;

use App\Models\TenantConfiguration;
use Carbon\CarbonImmutable;

final readonly class QueueDelayCalculator
{
    public function __construct(
        private SessionRateLimiter $rateLimiter,
    ) {
    }

    public function calculate(TenantConfiguration $configuration, ?CarbonImmutable $lastScheduledAt, ?CarbonImmutable $now = null): int
    {
        $now ??= CarbonImmutable::now();

        if (! $configuration->anti_ban_enabled) {
            return 0;
        }

        $delayMin = max(1, $configuration->delay_min_seconds);
        $delayMax = max($delayMin, $configuration->delay_max_seconds);
        $jitterDelay = random_int($delayMin, $delayMax);

        $baseAvailableAt = $now->addSeconds($jitterDelay);
        $rateLimitedAvailableAt = $this->rateLimiter->nextAvailableAt($configuration, $lastScheduledAt, $now);
        $scheduledAt = $baseAvailableAt->greaterThan($rateLimitedAvailableAt)
            ? $baseAvailableAt
            : $rateLimitedAvailableAt;

        return (int) max(0, $now->diffInSeconds($scheduledAt, false));
    }
}
