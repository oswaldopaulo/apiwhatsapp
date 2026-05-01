<?php

declare(strict_types=1);

namespace Database\Factories\Mongo;

use App\Enums\QueueEventType;
use App\Models\Mongo\QueueEvent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<QueueEvent>
 */
final class QueueEventFactory extends Factory
{
    protected $model = QueueEvent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => (string) Str::uuid(),
            'job_id' => (string) Str::uuid(),
            'queue' => 'messages',
            'event_type' => QueueEventType::Pushed->value,
            'attempt' => 0,
            'delay_seconds' => $this->faker->numberBetween(3, 12),
            'payload' => [],
            'occurred_at' => now(),
            'metadata' => [
                'correlation_id' => (string) Str::uuid(),
                'source' => 'factory',
            ],
        ];
    }
}
