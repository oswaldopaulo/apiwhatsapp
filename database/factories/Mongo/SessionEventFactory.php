<?php

declare(strict_types=1);

namespace Database\Factories\Mongo;

use App\Enums\SessionEventType;
use App\Models\Mongo\SessionEvent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SessionEvent>
 */
final class SessionEventFactory extends Factory
{
    protected $model = SessionEvent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => (string) Str::uuid(),
            'session_id' => (string) Str::uuid(),
            'event_type' => SessionEventType::Connected->value,
            'provider' => 'whatsapp',
            'payload' => [],
            'occurred_at' => now(),
            'metadata' => [
                'correlation_id' => (string) Str::uuid(),
                'source' => 'factory',
            ],
        ];
    }
}
