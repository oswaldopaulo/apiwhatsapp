<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\QueueDriver;
use App\Models\Tenant;
use App\Models\TenantConfiguration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TenantConfiguration>
 */
final class TenantConfigurationFactory extends Factory
{
    protected $model = TenantConfiguration::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'queue_driver' => QueueDriver::Database,
            'redis_enabled' => false,
            'anti_ban_enabled' => true,
            'delay_min_seconds' => 3,
            'delay_max_seconds' => 12,
            'max_messages_per_minute' => 20,
            'max_daily_messages' => 1000,
            'webhook_url' => null,
            'webhook_secret' => null,
            'settings' => [],
        ];
    }

    public function withoutAntiBan(): self
    {
        return $this->state(fn (): array => [
            'anti_ban_enabled' => false,
            'delay_min_seconds' => 0,
            'delay_max_seconds' => 0,
        ]);
    }

    public function withWebhook(string $url = 'https://client.example.test/webhook', string $secret = 'test-secret'): self
    {
        return $this->state(fn (): array => [
            'webhook_url' => $url,
            'webhook_secret' => $secret,
        ]);
    }
}
