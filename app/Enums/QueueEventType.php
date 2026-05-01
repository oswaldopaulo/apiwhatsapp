<?php

declare(strict_types=1);

namespace App\Enums;

enum QueueEventType: string
{
    case Pushed = 'pushed';
    case Reserved = 'reserved';
    case Released = 'released';
    case Retried = 'retried';
    case Failed = 'failed';
    case Completed = 'completed';
}
