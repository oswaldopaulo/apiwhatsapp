<?php

declare(strict_types=1);

namespace App\Services\Tenancy;

use App\Models\Tenant;
use App\Models\TenantConfiguration;
use Illuminate\Support\Facades\Cache;

final class TenantConfigurationService
{
    public function getForTenant(Tenant $tenant): TenantConfiguration
    {
        return Cache::remember(
            $this->cacheKey($tenant),
            now()->addMinutes(10),
            fn (): TenantConfiguration => $this->findOrCreate($tenant),
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateForTenant(Tenant $tenant, array $data): TenantConfiguration
    {
        $configuration = $this->findOrCreate($tenant);
        $configuration->fill($data);
        $configuration->save();

        $this->forget($tenant);

        return $configuration->refresh();
    }

    public function forget(Tenant $tenant): void
    {
        Cache::forget($this->cacheKey($tenant));
    }

    public function cacheKey(Tenant $tenant): string
    {
        return sprintf('tenant:%s:configuration', $tenant->getKey());
    }

    private function findOrCreate(Tenant $tenant): TenantConfiguration
    {
        return TenantConfiguration::query()->firstOrCreate(
            ['tenant_id' => $tenant->getKey()],
            [
                'queue_driver' => config('whatsapp.queue.driver', 'default'),
                'redis_enabled' => config('cache.default') === 'redis',
                'anti_ban_enabled' => (bool) config('whatsapp.anti_ban.enabled', true),
                'delay_min_seconds' => (int) config('whatsapp.delivery.default_delay_min', 3),
                'delay_max_seconds' => (int) config('whatsapp.delivery.default_delay_max', 12),
                'max_messages_per_minute' => (int) config('whatsapp.delivery.max_messages_per_minute', 20),
                'max_daily_messages' => 1000,
                'settings' => [],
            ],
        );
    }
}
