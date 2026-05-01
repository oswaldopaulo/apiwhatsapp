<?php

declare(strict_types=1);

namespace App\Queue;

use App\Queue\Contracts\QueueControlInterface;

final class QueuePositionEstimator
{
    public function estimate(QueueControlInterface $control, string|int $tenantId, string $sessionId): int
    {
        return $control->incrementApproximatePosition($tenantId, $sessionId);
    }
}
