<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Model;

interface Repository
{
    public function find(string|int $id): ?Model;
}
