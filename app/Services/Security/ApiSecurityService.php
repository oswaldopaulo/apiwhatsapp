<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Services\Audit\AuditService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

final readonly class ApiSecurityService
{
    public function __construct(
        private AuditService $audit,
        private TenantContext $tenantContext,
    ) {
    }

    /**
     * @return array{allowed: bool, key: string, remaining: int, retry_after: int}
     */
    public function rateLimit(Request $request, string $profile): array
    {
        $maxAttempts = $this->maxAttempts($profile);
        $decaySeconds = (int) config('api-security.rate_limits.decay_seconds', 60);
        $key = $this->rateLimitKey($request, $profile);
        $store = $this->cacheStore((string) config('api-security.rate_limits.store', config('cache.default')));
        $hits = (int) $store->get($key, 0);

        if ($hits >= $maxAttempts) {
            $this->handleAbuse($request, $profile, $key, $hits);

            return [
                'allowed' => false,
                'key' => $key,
                'remaining' => 0,
                'retry_after' => $decaySeconds,
            ];
        }

        $store->add($key, 0, now()->addSeconds($decaySeconds));
        $hits = (int) $store->increment($key);

        return [
            'allowed' => true,
            'key' => $key,
            'remaining' => max(0, $maxAttempts - $hits),
            'retry_after' => $decaySeconds,
        ];
    }

    /**
     * @template TValue
     * @param callable(): TValue $callback
     * @return TValue
     */
    public function withRequestLock(Request $request, string $scope, callable $callback): mixed
    {
        if (! (bool) config('api-security.locks.enabled', true)) {
            return $callback();
        }

        $ttl = (int) config('api-security.locks.ttl_seconds', 10);
        $key = $this->lockKey($request, $scope);
        $store = $this->cacheStore((string) config('api-security.locks.store', config('cache.default')));

        try {
            $lock = $store->lock($key, $ttl);

            if (! $lock->get()) {
                return response()->json(['message' => 'A similar request is already being processed.'], 409);
            }

            try {
                return $callback();
            } finally {
                $lock->release();
            }
        } catch (\BadMethodCallException|\RuntimeException $exception) {
            if (! $store->add($key, true, now()->addSeconds($ttl))) {
                return response()->json(['message' => 'A similar request is already being processed.'], 409);
            }

            try {
                return $callback();
            } finally {
                $store->forget($key);
            }
        }
    }

    public function rateLimitKey(Request $request, string $profile): string
    {
        $tenantId = $this->tenantContext->id() ?? $request->headers->get((string) config('api-security.tenant.header', 'X-Tenant-ID'), 'no-tenant');
        $sessionId = (string) $request->input('session_id', 'no-session');
        $endpoint = $request->method().':'.$request->path();

        return match ($profile) {
            'tenant' => "rate:tenant:{$tenantId}",
            'session' => "rate:tenant:{$tenantId}:session:{$sessionId}",
            'sensitive' => 'rate:sensitive:'.sha1($endpoint).':'.$request->ip(),
            'webhook' => "rate:webhook:{$tenantId}:".$request->ip(),
            default => 'rate:ip:'.$request->ip(),
        };
    }

    public function lockKey(Request $request, string $scope): string
    {
        $tenantId = $this->tenantContext->id() ?? $request->headers->get((string) config('api-security.tenant.header', 'X-Tenant-ID'), 'no-tenant');
        $sessionId = (string) $request->input('session_id', 'no-session');
        $bodyHash = sha1($request->getContent());

        return match ($scope) {
            'tenant' => "lock:tenant:{$tenantId}:".sha1($request->method().$request->path().$bodyHash),
            'session' => "lock:tenant:{$tenantId}:session:{$sessionId}:".sha1($request->method().$request->path().$bodyHash),
            default => "lock:ip:{$request->ip()}:".sha1($request->method().$request->path().$bodyHash),
        };
    }

    private function maxAttempts(string $profile): int
    {
        return match ($profile) {
            'tenant' => (int) config('api-security.rate_limits.tenant_per_minute', 600),
            'session' => (int) config('api-security.rate_limits.session_per_minute', 30),
            'sensitive' => (int) config('api-security.rate_limits.sensitive_per_minute', 60),
            'webhook' => (int) config('api-security.rate_limits.webhook_per_minute', 300),
            default => (int) config('api-security.rate_limits.ip_per_minute', 120),
        };
    }

    private function handleAbuse(Request $request, string $profile, string $key, int $hits): void
    {
        $abuseKey = 'abuse:'.sha1($key.':'.($request->user()?->getAuthIdentifier() ?? $request->ip()));
        $store = $this->cacheStore((string) config('api-security.rate_limits.store', config('cache.default')));
        $store->add($abuseKey, 0, now()->addMinutes(10));
        $abuseHits = (int) $store->increment($abuseKey);

        $this->audit->record('rate_limit.abuse', 'blocked', [
            'profile' => $profile,
            'hits' => $hits,
            'abuse_hits' => $abuseHits,
            'path' => $request->path(),
        ], $this->tenantContext->get(), $request->user(), $request);

        if ($abuseHits < (int) config('api-security.rate_limits.abuse_revocation_threshold', 3)) {
            return;
        }

        $token = $request->user()?->token();

        if ($token !== null && method_exists($token, 'revoke')) {
            $token->revoke();

            $this->audit->record('oauth.token_revoked', 'abuse', [
                'reason' => 'rate_limit_abuse',
                'token_id_hash' => property_exists($token, 'id') ? hash('sha256', (string) $token->id) : null,
            ], $this->tenantContext->get(), $request->user(), $request);
        }
    }

    private function cacheStore(string $store)
    {
        return Cache::store($store ?: config('cache.default'));
    }
}
