<?php

declare(strict_types=1);

namespace App\Queue;

use App\Models\TenantConfiguration;
use Carbon\CarbonImmutable;

final class SessionRateLimiter
{
    public function minimumIntervalSeconds(TenantConfiguration $configuration): int
    {
        $maxPerMinute = max(1, $configuration->max_messages_per_minute);

        return max(1, (int) ceil(60 / $maxPerMinute));
    }

    public function nextAvailableAt(TenantConfiguration $configuration, ?CarbonImmutable $lastScheduledAt, CarbonImmutable $now): CarbonImmutable
    {
        if ($lastScheduledAt === null) {
            return $now;
        }

        return $lastScheduledAt->addSeconds($this->minimumIntervalSeconds($configuration));
    }
}
