<?php

declare(strict_types=1);

namespace App\Repositories\Mongo;

use App\Models\Mongo\MessageDocument;

final class MessageDocumentRepository
{
    public function find(string|int $id): ?MessageDocument
    {
        return MessageDocument::query()->find($id);
    }
}
