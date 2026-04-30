<?php

declare(strict_types=1);

namespace App\Queue;

use App\Enums\QueueName;

final readonly class TenantQueueConfig
{
    public function __construct(
        public QueueName $queue,
        public int $tries = 3,
        public int $backoffSeconds = 30,
        public int $timeoutSeconds = 120,
    ) {
    }
}
