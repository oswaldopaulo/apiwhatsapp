<?php

declare(strict_types=1);

namespace App\Queue;

use App\Enums\QueueName;

final class QueueNameResolver
{
    public function forTenant(QueueName $queue, string|int $tenantId): string
    {
        return sprintf('%s:tenant:%s', $queue->value, $tenantId);
    }
}
