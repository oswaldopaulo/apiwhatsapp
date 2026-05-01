<?php

declare(strict_types=1);

namespace App\Enums;

enum WhatsAppSessionStatus: string
{
    case Connecting = 'connecting';
    case QrPending = 'qr_pending';
    case Connected = 'connected';
    case Disconnected = 'disconnected';
    case Expired = 'expired';
    case Banned = 'banned';
    case Error = 'error';

    public function blocksSending(): bool
    {
        return in_array($this, [
            self::Banned,
            self::Expired,
            self::Disconnected,
        ], true);
    }

    public function riskScore(): int
    {
        return match ($this) {
            self::Banned => 100,
            self::Error => 80,
            self::Expired => 70,
            self::Disconnected => 40,
            self::Connecting, self::QrPending => 10,
            self::Connected => 0,
        };
    }
}
