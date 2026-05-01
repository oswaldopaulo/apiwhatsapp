<?php

declare(strict_types=1);

namespace App\Models\Mongo;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MongoDB\Laravel\Eloquent\Model;

abstract class MongoModel extends Model
{
    use HasFactory;

    protected $connection = 'mongodb';

    /**
     * @var list<string>
     */
    protected $guarded = [];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'token',
        'secret',
        'api_key',
        'access_token',
        'refresh_token',
        'authorization',
    ];
}
