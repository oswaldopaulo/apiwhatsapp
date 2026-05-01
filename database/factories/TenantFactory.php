<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tenant>
 */
final class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'public_id' => (string) Str::uuid(),
            'name' => fake()->company(),
            'owner_user_id' => null,
            'status' => TenantStatus::Active,
            'settings' => [],
        ];
    }

    public function suspended(): self
    {
        return $this->state(fn (): array => [
            'status' => TenantStatus::Suspended,
        ]);
    }
}
