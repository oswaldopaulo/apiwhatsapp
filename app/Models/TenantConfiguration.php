<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\QueueDriver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TenantConfiguration extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'queue_driver',
        'redis_enabled',
        'anti_ban_enabled',
        'delay_min_seconds',
        'delay_max_seconds',
        'max_messages_per_minute',
        'max_daily_messages',
        'webhook_url',
        'webhook_secret',
        'settings',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'webhook_secret',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'queue_driver' => QueueDriver::class,
            'redis_enabled' => 'boolean',
            'anti_ban_enabled' => 'boolean',
            'delay_min_seconds' => 'integer',
            'delay_max_seconds' => 'integer',
            'max_messages_per_minute' => 'integer',
            'max_daily_messages' => 'integer',
            'webhook_secret' => 'encrypted',
            'settings' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
