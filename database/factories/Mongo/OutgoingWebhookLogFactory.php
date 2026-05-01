<?php

declare(strict_types=1);

namespace Database\Factories\Mongo;

use App\Enums\OutgoingWebhookStatus;
use App\Models\Mongo\OutgoingWebhookLog;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<OutgoingWebhookLog>
 */
final class OutgoingWebhookLogFactory extends Factory
{
    protected $model = OutgoingWebhookLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => (string) Str::uuid(),
            'webhook_id' => (string) Str::uuid(),
            'event_type' => 'message.sent',
            'status' => OutgoingWebhookStatus::Pending->value,
            'endpoint_url' => $this->faker->url(),
            'request_headers' => ['content-type' => 'application/json'],
            'request_payload' => ['event_id' => (string) Str::uuid()],
            'response_status' => null,
            'response_headers' => [],
            'response_body_preview' => null,
            'attempts' => 0,
            'next_retry_at' => null,
            'sent_at' => null,
            'delivered_at' => null,
            'failed_at' => null,
            'metadata' => [
                'correlation_id' => (string) Str::uuid(),
                'source' => 'factory',
            ],
        ];
    }
}
