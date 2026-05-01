<?php

declare(strict_types=1);

namespace App\Enums;

enum MessageLogLevel: string
{
    case Debug = 'debug';
    case Info = 'info';
    case Warning = 'warning';
    case Error = 'error';
}
