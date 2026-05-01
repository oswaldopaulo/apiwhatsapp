<?php

declare(strict_types=1);

namespace Database\Factories\WhatsApp;

use App\Enums\WhatsAppSessionStatus;
use App\Models\Tenant;
use App\Models\WhatsApp\WhatsAppSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WhatsAppSession>
 */
final class WhatsAppSessionFactory extends Factory
{
    protected $model = WhatsAppSession::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = WhatsAppSessionStatus::Connected;

        return [
            'tenant_id' => Tenant::factory(),
            'name' => 'Session '.fake()->unique()->numerify('###'),
            'provider' => 'fake',
            'status' => $status,
            'phone_number' => fake()->unique()->numerify('55119########'),
            'last_activity_at' => now(),
            'risk_score' => $status->riskScore(),
            'metadata' => [],
            'encrypted_credentials' => null,
        ];
    }

    public function status(WhatsAppSessionStatus $status): self
    {
        return $this->state(fn (): array => [
            'status' => $status,
            'risk_score' => $status->riskScore(),
        ]);
    }

    public function disconnected(): self
    {
        return $this->status(WhatsAppSessionStatus::Disconnected);
    }
}
