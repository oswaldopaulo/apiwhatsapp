<?php

declare(strict_types=1);

namespace App\Services\Audit;

use App\Models\Mongo\AuditLog;
use App\Services\Audit\Contracts\AuditLogStoreInterface;

final class MongoAuditLogStore implements AuditLogStoreInterface
{
    /**
     * @param array<string, mixed> $entry
     */
    public function record(array $entry): void
    {
        AuditLog::query()->create($entry);
    }
}
