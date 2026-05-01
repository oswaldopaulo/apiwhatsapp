<?php

declare(strict_types=1);

namespace App\Enums;

enum OutgoingWebhookStatus: string
{
    case Pending = 'pending';
    case Sending = 'sending';
    case Delivered = 'delivered';
    case Failed = 'failed';
    case Canceled = 'canceled';
}
