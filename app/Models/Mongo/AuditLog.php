<?php

declare(strict_types=1);

namespace App\Models\Mongo;

final class AuditLog extends MongoModel
{
    protected string $collection = 'audit_logs';

    public function getTable(): string
    {
        return (string) config('audit.mongo_collection', $this->collection);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tenant_id' => 'string',
            'user_id' => 'string',
            'metadata' => 'array',
            'occurred_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
