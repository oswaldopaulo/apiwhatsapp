<?php

declare(strict_types=1);

namespace App\Redis;

use App\Enums\RedisKey;

final class RedisKeyFactory
{
    public function tenantKey(string|int $tenantId, RedisKey $key, string|int ...$parts): string
    {
        $segments = [
            'tenant',
            (string) $tenantId,
            $key->value,
            ...array_map(static fn (string|int $part): string => (string) $part, $parts),
        ];

        return implode(':', array_filter($segments, static fn (string $segment): bool => $segment !== ''));
    }
}
