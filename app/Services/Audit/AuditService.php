<?php

declare(strict_types=1);

namespace App\Services\Audit;

use App\Models\Tenant;
use App\Models\User;
use App\Services\Audit\Contracts\AuditLogStoreInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Throwable;

final readonly class AuditService
{
    public function __construct(
        private AuditLogStoreInterface $store,
    ) {
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function record(
        string $action,
        string $status = 'success',
        array $metadata = [],
        Tenant|string|int|null $tenant = null,
        User|string|int|null $user = null,
        ?Request $request = null,
    ): void {
        if (! (bool) config('audit.enabled', true)) {
            return;
        }

        $request ??= request();
        $tenantId = $tenant instanceof Tenant ? $tenant->getKey() : $tenant;
        $userId = $user instanceof User ? $user->getKey() : ($user ?? Auth::id());
        $entry = [
            'tenant_id' => $tenantId === null ? null : (string) $tenantId,
            'user_id' => $userId === null ? null : (string) $userId,
            'ip' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'action' => $action,
            'status' => $status,
            'metadata' => $this->sanitizeMetadata($metadata),
            'occurred_at' => now(),
        ];

        $this->store->record($entry);
        $this->recordActivityLog($entry);
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function criticalFailure(string $action, Throwable $exception, array $metadata = [], Tenant|string|int|null $tenant = null): void
    {
        $this->record($action, 'failed', [
            ...$metadata,
            'exception' => $exception::class,
            'error' => mb_substr($exception->getMessage(), 0, 500),
        ], $tenant);
    }

    /**
     * @param array<string, mixed> $metadata
     * @return array<string, mixed>
     */
    public function sanitizeMetadata(array $metadata): array
    {
        $sanitized = [];

        foreach ($metadata as $key => $value) {
            $key = (string) $key;

            if (is_bool($value) || is_int($value) || is_float($value) || $value === null) {
                $sanitized[$key] = $value;

                continue;
            }

            if ($this->isSensitiveKey($key)) {
                $sanitized[$key] = '[redacted]';

                continue;
            }

            if ($this->isPhoneKey($key)) {
                $sanitized[$key] = is_scalar($value) ? $this->maskPhone((string) $value) : '[masked]';

                continue;
            }

            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeMetadata($value);

                continue;
            }

            if (is_string($value)) {
                $sanitized[$key] = Str::limit($value, (int) config('audit.metadata.max_string_length', 500), '');

                continue;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }

    public function maskPhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (strlen($digits) <= 6) {
            return str_repeat('*', strlen($digits));
        }

        return substr($digits, 0, 4).str_repeat('*', max(0, strlen($digits) - 6)).substr($digits, -2);
    }

    private function isSensitiveKey(string $key): bool
    {
        return preg_match('/token|secret|password|credential|authorization|api[_-]?key|signature|raw_body|content|body/i', $key) === 1;
    }

    private function isPhoneKey(string $key): bool
    {
        return preg_match('/phone|to|from|recipient/i', $key) === 1;
    }

    /**
     * @param array<string, mixed> $entry
     */
    private function recordActivityLog(array $entry): void
    {
        if (! (bool) config('audit.activity_log.enabled', true) || ! function_exists('activity')) {
            return;
        }

        activity((string) config('audit.activity_log.log_name', 'audit'))
            ->withProperties($entry)
            ->log((string) $entry['action']);
    }
}
