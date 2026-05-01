<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\ShowTenantConfigurationRequest;
use App\Http\Requests\UpdateTenantConfigurationRequest;
use App\Models\TenantConfiguration;
use App\Services\Audit\AuditService;
use App\Services\Tenancy\TenantConfigurationService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;

final class TenantConfigurationController extends Controller
{
    public function __construct(
        private readonly TenantConfigurationService $configurations,
        private readonly TenantContext $tenantContext,
        private readonly AuditService $audit,
    ) {
    }

    public function show(ShowTenantConfigurationRequest $request): JsonResponse
    {
        $configuration = $this->configurations->getForTenant($this->tenantContext->current());

        return response()->json([
            'data' => $this->resource($configuration),
        ]);
    }

    public function update(UpdateTenantConfigurationRequest $request): JsonResponse
    {
        $configuration = $this->configurations->updateForTenant(
            $this->tenantContext->current(),
            $request->validated(),
        );
        $this->audit->record('configuration.updated', 'success', [
            'updated_fields' => array_keys($request->validated()),
            'webhook_secret_updated' => $request->has('webhook_secret'),
        ], $this->tenantContext->current(), request()->user());

        return response()->json([
            'data' => $this->resource($configuration),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function resource(TenantConfiguration $configuration): array
    {
        return [
            'tenant_id' => $configuration->tenant_id,
            'queue_driver' => $configuration->queue_driver->value,
            'redis_enabled' => $configuration->redis_enabled,
            'anti_ban_enabled' => $configuration->anti_ban_enabled,
            'delay_min_seconds' => $configuration->delay_min_seconds,
            'delay_max_seconds' => $configuration->delay_max_seconds,
            'max_messages_per_minute' => $configuration->max_messages_per_minute,
            'max_daily_messages' => $configuration->max_daily_messages,
            'webhook_url' => $configuration->webhook_url,
            'webhook_secret_configured' => $configuration->webhook_secret !== null,
            'settings' => $configuration->settings ?? [],
            'created_at' => $configuration->created_at?->toJSON(),
            'updated_at' => $configuration->updated_at?->toJSON(),
        ];
    }
}
