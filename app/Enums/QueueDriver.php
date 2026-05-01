<?php

declare(strict_types=1);

namespace App\Enums;

enum QueueDriver: string
{
    case Default = 'default';
    case Redis = 'redis';
    case Database = 'database';
}
