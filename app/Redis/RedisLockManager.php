<?php

declare(strict_types=1);

namespace App\Redis;

use App\Enums\RedisKey;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Lock;

final readonly class RedisLockManager
{
    public function __construct(
        private CacheFactory $cache,
        private RedisKeyFactory $keys,
    ) {
    }

    public function tenantLock(string|int $tenantId, string $resource, int $seconds = 30): Lock
    {
        return $this->cache
            ->store('redis')
            ->lock($this->keys->tenantKey($tenantId, RedisKey::TenantLock, $resource), $seconds);
    }
}
