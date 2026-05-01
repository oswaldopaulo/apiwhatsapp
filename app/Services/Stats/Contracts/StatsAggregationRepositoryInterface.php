<?php

declare(strict_types=1);

namespace App\Services\Stats\Contracts;

interface StatsAggregationRepositoryInterface
{
    /**
     * @param array<string, mixed> $filters
     * @return list<array<string, mixed>>
     */
    public function messagesByHour(string|int $tenantId, array $filters): array;

    /**
     * @param array<string, mixed> $filters
     * @return list<array<string, mixed>>
     */
    public function messagesByDay(string|int $tenantId, array $filters): array;

    /**
     * @param array<string, mixed> $filters
     * @return list<array<string, mixed>>
     */
    public function errorsByType(string|int $tenantId, array $filters): array;

    /**
     * @param array<string, mixed> $filters
     * @return list<array<string, mixed>>
     */
    public function queueBySession(string|int $tenantId, array $filters): array;

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function deliveryRate(string|int $tenantId, array $filters): array;
}
