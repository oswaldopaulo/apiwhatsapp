<?php

declare(strict_types=1);

namespace App\Enums;

enum QueueName: string
{
    case Default = 'default';
    case Messages = 'messages';
    case Webhooks = 'webhooks';
    case Events = 'events';
    case Statistics = 'statistics';
}
