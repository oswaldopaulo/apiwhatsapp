<?php

declare(strict_types=1);

namespace App\Services\Audit;

use App\Services\Audit\Contracts\AuditLogStoreInterface;

final class NullAuditLogStore implements AuditLogStoreInterface
{
    public function record(array $entry): void
    {
        //
    }
}
