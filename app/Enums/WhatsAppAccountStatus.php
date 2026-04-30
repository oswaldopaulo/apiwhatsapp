<?php

declare(strict_types=1);

namespace App\Enums;

enum WhatsAppAccountStatus: string
{
    case Pending = 'pending';
    case Connected = 'connected';
    case Disconnected = 'disconnected';
    case Blocked = 'blocked';
}
