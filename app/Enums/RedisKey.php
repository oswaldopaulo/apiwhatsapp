<?php

declare(strict_types=1);

namespace App\Enums;

enum RedisKey: string
{
    case TenantLock = 'lock';
    case TenantRateLimit = 'rate_limit';
    case AntiBanWindow = 'anti_ban';
    case QueueControl = 'queue';
}
