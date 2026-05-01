<?php

declare(strict_types=1);

namespace App\Enums;

enum SessionEventType: string
{
    case Created = 'created';
    case Connecting = 'connecting';
    case Connected = 'connected';
    case Disconnected = 'disconnected';
    case Expired = 'expired';
    case Banned = 'banned';
    case Reconnecting = 'reconnecting';
    case Failed = 'failed';
    case Error = 'error';
    case Deleted = 'deleted';
}
