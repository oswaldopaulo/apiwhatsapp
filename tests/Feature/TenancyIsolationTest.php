<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\DTOs\WhatsApp\OutboundMessageData;
use App\Jobs\WhatsApp\SendWhatsAppMessageJob;
use App\Models\Tenant;
use App\Models\WhatsApp\WhatsAppAccount;
use App\Support\Tenancy\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

final class TenancyIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_middleware_resolves_context_from_header(): void
    {
        $tenant = $this->createTenant();

        Route::middleware('tenant')->get('/tenant-probe', static function (TenantContext $context) {
            return response()->json([
                'tenant_id' => $context->id(),
                'has_tenant' => $context->hasTenant(),
            ]);
        });

        $this->getJson('/tenant-probe', ['X-Tenant-ID' => $tenant->public_id])
            ->assertOk()
            ->assertJson([
                'tenant_id' => $tenant->id,
                'has_tenant' => true,
            ]);

        $this->assertFalse(app(TenantContext::class)->hasTenant());
    }

    public function test_tenant_middleware_rejects_unknown_tenant(): void
    {
        Route::middleware('tenant')->get('/tenant-probe-missing', static fn () => response()->noContent());

        $this->getJson('/tenant-probe-missing', ['X-Tenant-ID' => (string) Str::uuid()])
            ->assertNotFound();
    }

    public function test_relational_models_are_scoped_to_current_tenant(): void
    {
        $tenantA = $this->createTenant('Tenant A');
        $tenantB = $this->createTenant('Tenant B');
        $context = app(TenantContext::class);

        $context->run($tenantA, function (): void {
            WhatsAppAccount::query()->create([
                'name' => 'Account A',
                'phone_number' => '5511999990001',
            ]);
        });

        $context->run($tenantB, function (): void {
            WhatsAppAccount::query()->create([
                'name' => 'Account B',
                'phone_number' => '5511999990002',
            ]);
        });

        $context->run($tenantA, function (): void {
            $this->assertSame(1, WhatsAppAccount::query()->count());
            $this->assertSame('Account A', WhatsAppAccount::query()->firstOrFail()->name);
        });

        $context->run($tenantB, function (): void {
            $this->assertSame(1, WhatsAppAccount::query()->count());
            $this->assertSame('Account B', WhatsAppAccount::query()->firstOrFail()->name);
        });
    }

    public function test_relational_models_reject_cross_tenant_writes(): void
    {
        $tenantA = $this->createTenant('Tenant A');
        $tenantB = $this->createTenant('Tenant B');

        $this->expectException(AuthorizationException::class);

        app(TenantContext::class)->run($tenantA, static function () use ($tenantB): void {
            WhatsAppAccount::query()->create([
                'tenant_id' => $tenantB->id,
                'name' => 'Wrong Tenant Account',
                'phone_number' => '5511999990003',
            ]);
        });
    }

    public function test_jobs_carry_tenant_id_for_async_processing(): void
    {
        $tenant = $this->createTenant();

        $message = new OutboundMessageData(
            tenantId: (string) $tenant->id,
            whatsAppAccountId: 'account-id',
            recipient: '5511999990004',
            body: 'Hello',
        );

        $job = new SendWhatsAppMessageJob($message);

        $this->assertSame((string) $tenant->id, $job->tenantId());

        $job->withTenantContext(function () use ($tenant): void {
            $this->assertSame($tenant->id, app(TenantContext::class)->id());
        });

        $this->assertFalse(app(TenantContext::class)->hasTenant());
    }

    private function createTenant(string $name = 'Tenant'): Tenant
    {
        return Tenant::query()->create([
            'public_id' => (string) Str::uuid(),
            'name' => $name,
        ]);
    }
}
