<?php

declare(strict_types=1);

namespace App\Models\Mongo;

use Illuminate\Database\Eloquent\Model;

abstract class MongoModel extends Model
{
    protected $connection = 'mongodb';

    /**
     * @var list<string>
     */
    protected $guarded = [];
}
