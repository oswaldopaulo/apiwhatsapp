<?php

declare(strict_types=1);

namespace App\Redis;

use App\Enums\RedisKey;

final readonly class AntiBanKeyFactory
{
    public function __construct(
        private RedisKeyFactory $keys,
    ) {
    }

    public function accountWindow(string|int $tenantId, string|int $accountId): string
    {
        return $this->keys->tenantKey($tenantId, RedisKey::AntiBanWindow, 'account', $accountId);
    }
}
