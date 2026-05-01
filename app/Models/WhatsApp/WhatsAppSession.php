<?php

declare(strict_types=1);

namespace App\Models\WhatsApp;

use App\Enums\WhatsAppSessionStatus;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class WhatsAppSession extends Model
{
    use BelongsToTenant;
    use HasFactory;

    protected $table = 'whatsapp_sessions';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'provider',
        'status',
        'phone_number',
        'last_activity_at',
        'risk_score',
        'metadata',
        'encrypted_credentials',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'encrypted_credentials',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => WhatsAppSessionStatus::class,
            'last_activity_at' => 'datetime',
            'risk_score' => 'integer',
            'metadata' => 'array',
            'encrypted_credentials' => 'encrypted:array',
        ];
    }
}
