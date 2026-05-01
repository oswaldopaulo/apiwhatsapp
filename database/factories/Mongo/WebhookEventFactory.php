<?php

declare(strict_types=1);

namespace Database\Factories\Mongo;

use App\Enums\WebhookEventStatus;
use App\Models\Mongo\WebhookEvent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WebhookEvent>
 */
final class WebhookEventFactory extends Factory
{
    protected $model = WebhookEvent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => (string) Str::uuid(),
            'event_id' => (string) Str::uuid(),
            'provider' => 'whatsapp',
            'event_type' => 'message.status',
            'headers' => ['content-type' => 'application/json'],
            'payload' => ['id' => (string) Str::uuid()],
            'status' => WebhookEventStatus::Received->value,
            'received_at' => now(),
            'processed_at' => null,
            'failed_at' => null,
            'metadata' => [
                'correlation_id' => (string) Str::uuid(),
                'source' => 'factory',
            ],
        ];
    }
}
