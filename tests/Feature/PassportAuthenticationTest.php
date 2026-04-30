<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

final class PassportAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_me_endpoint_requires_authentication(): void
    {
        $tenant = $this->createTenant();

        $this->getJson('/api/me', ['X-Tenant-ID' => $tenant->public_id])
            ->assertUnauthorized();
    }

    public function test_me_endpoint_returns_authenticated_user_and_tenant(): void
    {
        [$user, $tenant] = $this->createUserAndTenant();

        Passport::actingAs($user, ['messages:read']);

        $this->getJson('/api/me', ['X-Tenant-ID' => $tenant->public_id])
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.tenant.id', $tenant->id)
            ->assertJsonPath('data.tenant.public_id', $tenant->public_id);
    }

    public function test_protected_route_accepts_token_with_required_scope(): void
    {
        [$user, $tenant] = $this->createUserAndTenant();

        Passport::actingAs($user, ['messages:send']);

        $this->postJson('/api/messages', [], ['X-Tenant-ID' => $tenant->public_id])
            ->assertAccepted()
            ->assertJsonPath('message', 'Message accepted for queueing.');
    }

    public function test_protected_route_rejects_token_without_required_scope(): void
    {
        [$user, $tenant] = $this->createUserAndTenant();

        Passport::actingAs($user, ['messages:read']);

        $this->postJson('/api/messages', [], ['X-Tenant-ID' => $tenant->public_id])
            ->assertForbidden()
            ->assertJsonPath('required_scope', 'messages:send');
    }

    public function test_authenticated_user_cannot_access_another_tenant(): void
    {
        [$user, $tenant] = $this->createUserAndTenant();
        $otherTenant = $this->createTenant('Other Tenant');

        Passport::actingAs($user, ['messages:read']);

        $this->getJson('/api/me', ['X-Tenant-ID' => $otherTenant->public_id])
            ->assertForbidden()
            ->assertJsonPath('message', 'This token cannot access the requested tenant.');

        $this->assertFalse($user->tenants()->whereKey($otherTenant->id)->exists());
        $this->assertTrue($user->tenants()->whereKey($tenant->id)->exists());
    }

    public function test_oauth_scopes_are_registered(): void
    {
        $this->assertSame([
            'messages:send',
            'messages:read',
            'sessions:manage',
            'stats:read',
            'webhooks:manage',
            'config:read',
            'config:write',
        ], Passport::scopeIds());

        $this->getJson('/oauth/scopes')
            ->assertOk()
            ->assertJsonFragment(['id' => 'messages:send']);
    }

    /**
     * @return array{User, Tenant}
     */
    private function createUserAndTenant(): array
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $user = User::factory()->create();
        $tenant = $this->createTenant(ownerUserId: $user->id);

        $tenant->users()->attach($user->id, ['role' => 'owner']);
        app(PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        $user->assignRole('owner');
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
}
