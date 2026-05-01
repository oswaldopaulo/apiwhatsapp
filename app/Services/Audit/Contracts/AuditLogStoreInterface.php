<?php

declare(strict_types=1);

namespace App\Services\Audit\Contracts;

interface AuditLogStoreInterface
{
    /**
     * @param array<string, mixed> $entry
     */
    public function record(array $entry): void;
}
