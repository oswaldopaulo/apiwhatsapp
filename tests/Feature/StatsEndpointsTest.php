<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\WhatsAppSessionStatus;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WhatsApp\WhatsAppSession;
use App\Services\Stats\Contracts\StatsAggregationRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class StatsEndpointsTest extends TestCase
{
    use RefreshDatabase;

    private StatsAggregationRepositoryFake $aggregations;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->aggregations = new StatsAggregationRepositoryFake();
        $this->app->instance(StatsAggregationRepositoryInterface::class, $this->aggregations);
    }

    public function test_messages_hour_returns_chart_ready_json_and_uses_cache(): void
    {
        [$user, $tenant] = $this->createUserWithTenantRole('owner');

        Passport::actingAs($user, ['stats:read']);

        $query = [
            'date_from' => '2026-05-01',
            'date_to' => '2026-05-01',
            'session_id' => 'session-1',
        ];

        $this->getJson('/api/v1/stats/messages/hour?'.http_build_query($query), ['X-Tenant-ID' => $tenant->public_id])
            ->assertOk()
            ->assertJsonPath('data.series.0.bucket', '2026-05-01 12:00')
            ->assertJsonPath('data.series.0.total', 3)
            ->assertJsonPath('data.filters.session_id', 'session-1');

        $this->getJson('/api/v1/stats/messages/hour?'.http_build_query($query), ['X-Tenant-ID' => $tenant->public_id])
            ->assertOk();

        $this->assertSame(1, $this->aggregations->calls['messagesByHour']);
        $this->assertSame((string) $tenant->id, (string) $this->aggregations->lastTenantId);
        $this->assertSame($query, $this->aggregations->lastFilters);
    }

    public function test_all_stats_endpoints_return_expected_shapes(): void
    {
        [$user, $tenant] = $this->createUserWithTenantRole('owner');

        Passport::actingAs($user, ['stats:read']);

        $headers = ['X-Tenant-ID' => $tenant->public_id];

        $this->getJson('/api/v1/stats/messages/day', $headers)
            ->assertOk()
            ->assertJsonPath('data.series.0.bucket', '2026-05-01');

        $this->getJson('/api/v1/stats/errors', $headers)
            ->assertOk()
            ->assertJsonPath('data.items.0.error_code', 'provider_timeout');

        $this->getJson('/api/v1/stats/queue', $headers)
            ->assertOk()
            ->assertJsonPath('data.sessions.0.session_id', 'session-1');

        $this->getJson('/api/v1/stats/delivery-rate', $headers)
            ->assertOk()
            ->assertJsonPath('data.totals.delivered', 8)
            ->assertJsonPath('data.success_rate', 90);
    }

    public function test_sessions_stats_are_filtered_by_current_tenant(): void
    {
        [$user, $tenant] = $this->createUserWithTenantRole('owner');
        $otherTenant = $this->createTenant('Other Tenant');
        $session = $this->createSession($tenant, 'Main', WhatsAppSessionStatus::Connected);
        $this->createSession($otherTenant, 'Other', WhatsAppSessionStatus::Banned);

        Passport::actingAs($user, ['stats:read']);

        $this->getJson('/api/v1/stats/sessions', ['X-Tenant-ID' => $tenant->public_id])
            ->assertOk()
            ->assertJsonPath('data.summary.total', 1)
            ->assertJsonPath('data.summary.by_status.connected', 1)
            ->assertJsonPath('data.items.0.session_id', (string) $session->id)
            ->assertJsonMissing(['name' => 'Other']);
    }

    public function test_stats_endpoints_require_stats_read_scope(): void
    {
        [$user, $tenant] = $this->createUserWithTenantRole('owner');

        Passport::actingAs($user, ['messages:read']);

        $this->getJson('/api/v1/stats/messages/hour', ['X-Tenant-ID' => $tenant->public_id])
            ->assertForbidden()
            ->assertJsonPath('required_scope', 'stats:read');
    }

    public function test_stats_reject_invalid_date_range(): void
    {
        [$user, $tenant] = $this->createUserWithTenantRole('owner');

        Passport::actingAs($user, ['stats:read']);

        $this->getJson('/api/v1/stats/messages/day?date_from=2026-05-02&date_to=2026-05-01', [
            'X-Tenant-ID' => $tenant->public_id,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['date_to']);
    }

    /**
     * @return array{User, Tenant}
     */
    private function createUserWithTenantRole(string $role): array
    {
        $user = User::factory()->create();
        $tenant = $this->createTenant(ownerUserId: $role === 'owner' ? $user->id : null);

        $tenant->users()->attach($user->id, ['role' => $role]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $user->assignRole($role);
        $user->unsetRelation('roles');
        $user->unsetRelation('permissions');
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);

        return [$user, $tenant];
    }

    private function createTenant(string $name = 'Tenant', ?int $ownerUserId = null): Tenant
    {
        return Tenant::query()->create([
            'public_id' => (string) Str::uuid(),
            'name' => $name,
            'owner_user_id' => $ownerUserId,
        ]);
    }

    private function createSession(Tenant $tenant, string $name, WhatsAppSessionStatus $status): WhatsAppSession
    {
        return WhatsAppSession::query()->create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'provider' => 'fake',
            'status' => $status->value,
            'risk_score' => $status->riskScore(),
            'metadata' => [],
        ]);
    }
}

final class StatsAggregationRepositoryFake implements StatsAggregationRepositoryInterface
{
    /**
     * @var array<string, int>
     */
    public array $calls = [
        'messagesByHour' => 0,
        'messagesByDay' => 0,
        'errorsByType' => 0,
        'queueBySession' => 0,
        'deliveryRate' => 0,
    ];

    public string|int|null $lastTenantId = null;

    /**
     * @var array<string, mixed>
     */
    public array $lastFilters = [];

    public function messagesByHour(string|int $tenantId, array $filters): array
    {
        $this->record('messagesByHour', $tenantId, $filters);

        return [[
            'bucket' => '2026-05-01 12:00',
            'queued' => 1,
            'waiting' => 0,
            'processing' => 0,
            'sent' => 1,
            'delivered' => 1,
            'failed' => 0,
            'total' => 3,
        ]];
    }

    public function messagesByDay(string|int $tenantId, array $filters): array
    {
        $this->record('messagesByDay', $tenantId, $filters);

        return [[
            'bucket' => '2026-05-01',
            'sent' => 4,
            'delivered' => 8,
            'failed' => 1,
            'total' => 13,
            'avg_queue_seconds' => 6.5,
        ]];
    }

    public function errorsByType(string|int $tenantId, array $filters): array
    {
        $this->record('errorsByType', $tenantId, $filters);

        return [[
            'error_code' => 'provider_timeout',
            'count' => 2,
            'last_error_message' => 'Provider timeout.',
        ]];
    }

    public function queueBySession(string|int $tenantId, array $filters): array
    {
        $this->record('queueBySession', $tenantId, $filters);

        return [[
            'session_id' => 'session-1',
            'queued' => 2,
            'waiting' => 1,
            'processing' => 1,
            'avg_delay_seconds' => 4.5,
            'max_position_snapshot' => 3,
            'total' => 4,
        ]];
    }

    public function deliveryRate(string|int $tenantId, array $filters): array
    {
        $this->record('deliveryRate', $tenantId, $filters);

        return [
            'sent' => 1,
            'delivered' => 8,
            'failed' => 1,
            'total' => 10,
            'avg_queue_seconds' => 5.5,
        ];
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function record(string $method, string|int $tenantId, array $filters): void
    {
        $this->calls[$method]++;
        $this->lastTenantId = $tenantId;
        $this->lastFilters = $filters;
    }
}
