<?php

declare(strict_types=1);

namespace Database\Factories\Mongo;

use App\Enums\MessageLogLevel;
use App\Models\Mongo\MessageLog;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MessageLog>
 */
final class MessageLogFactory extends Factory
{
    protected $model = MessageLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => (string) Str::uuid(),
            'message_id' => (string) Str::uuid(),
            'level' => MessageLogLevel::Info->value,
            'message' => $this->faker->sentence(),
            'context' => [],
            'metadata' => [
                'correlation_id' => (string) Str::uuid(),
                'source' => 'factory',
            ],
        ];
    }
}
