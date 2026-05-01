<?php

declare(strict_types=1);

namespace App\Queue\Middleware;

use Illuminate\Queue\Middleware\WithoutOverlapping;

final class SessionRateLimited extends WithoutOverlapping
{
    public function __construct(string|int $tenantId, string $sessionId)
    {
        parent::__construct("whatsapp-send:tenant:{$tenantId}:session:{$sessionId}");

        $this->releaseAfter(10);
        $this->expireAfter(120);
    }
}
