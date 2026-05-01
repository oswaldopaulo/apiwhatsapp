<?php

declare(strict_types=1);

namespace Database\Factories\Mongo;

use App\Enums\MessageStatus;
use App\Enums\MessageType;
use App\Models\Mongo\Message;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Message>
 */
final class MessageFactory extends Factory
{
    protected $model = Message::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'message_id' => (string) Str::uuid(),
            'tenant_id' => (string) Str::uuid(),
            'session_id' => (string) Str::uuid(),
            'to' => $this->faker->numerify('55###########'),
            'type' => MessageType::Text->value,
            'content' => ['text' => $this->faker->sentence()],
            'status' => MessageStatus::Queued->value,
            'provider' => 'whatsapp',
            'provider_message_id' => null,
            'queued_at' => now(),
            'waiting_at' => null,
            'processing_at' => null,
            'sent_at' => null,
            'delivered_at' => null,
            'failed_at' => null,
            'canceled_at' => null,
            'error_code' => null,
            'error_message' => null,
            'attempts' => 0,
            'queue_position_snapshot' => null,
            'delay_seconds' => $this->faker->numberBetween(3, 12),
            'metadata' => [
                'correlation_id' => (string) Str::uuid(),
                'source' => 'factory',
            ],
        ];
    }
}
