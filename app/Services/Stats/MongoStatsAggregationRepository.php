<?php

declare(strict_types=1);

namespace App\Services\Stats;

use App\Models\Mongo\Message;
use App\Services\Stats\Contracts\StatsAggregationRepositoryInterface;
use Carbon\CarbonImmutable;
use MongoDB\Collection;

final class MongoStatsAggregationRepository implements StatsAggregationRepositoryInterface
{
    public function messagesByHour(string|int $tenantId, array $filters): array
    {
        return $this->aggregateMessages([
            ['$match' => $this->messageMatch($tenantId, $filters)],
            ['$group' => [
                '_id' => ['$dateToString' => ['format' => '%Y-%m-%d %H:00', 'date' => '$created_at']],
                'queued' => ['$sum' => ['$cond' => [['$eq' => ['$status', 'queued']], 1, 0]]],
                'waiting' => ['$sum' => ['$cond' => [['$eq' => ['$status', 'waiting']], 1, 0]]],
                'processing' => ['$sum' => ['$cond' => [['$eq' => ['$status', 'processing']], 1, 0]]],
                'sent' => ['$sum' => ['$cond' => [['$eq' => ['$status', 'sent']], 1, 0]]],
                'delivered' => ['$sum' => ['$cond' => [['$eq' => ['$status', 'delivered']], 1, 0]]],
                'failed' => ['$sum' => ['$cond' => [['$eq' => ['$status', 'failed']], 1, 0]]],
                'total' => ['$sum' => 1],
            ]],
            ['$sort' => ['_id' => 1]],
            ['$project' => [
                '_id' => 0,
                'bucket' => '$_id',
                'queued' => 1,
                'waiting' => 1,
                'processing' => 1,
                'sent' => 1,
                'delivered' => 1,
                'failed' => 1,
                'total' => 1,
            ]],
        ]);
    }

    public function messagesByDay(string|int $tenantId, array $filters): array
    {
        return $this->aggregateMessages([
            ['$match' => $this->messageMatch($tenantId, $filters)],
            ['$group' => [
                '_id' => ['$dateToString' => ['format' => '%Y-%m-%d', 'date' => '$created_at']],
                'sent' => ['$sum' => ['$cond' => [['$eq' => ['$status', 'sent']], 1, 0]]],
                'delivered' => ['$sum' => ['$cond' => [['$eq' => ['$status', 'delivered']], 1, 0]]],
                'failed' => ['$sum' => ['$cond' => [['$eq' => ['$status', 'failed']], 1, 0]]],
                'total' => ['$sum' => 1],
                'avg_queue_seconds' => ['$avg' => '$delay_seconds'],
            ]],
            ['$sort' => ['_id' => 1]],
            ['$project' => [
                '_id' => 0,
                'bucket' => '$_id',
                'sent' => 1,
                'delivered' => 1,
                'failed' => 1,
                'total' => 1,
                'avg_queue_seconds' => ['$round' => ['$avg_queue_seconds', 2]],
            ]],
        ]);
    }

    public function errorsByType(string|int $tenantId, array $filters): array
    {
        $match = $this->messageMatch($tenantId, $filters);
        $match['status'] = 'failed';

        return $this->aggregateMessages([
            ['$match' => $match],
            ['$group' => [
                '_id' => ['$ifNull' => ['$error_code', 'unknown']],
                'count' => ['$sum' => 1],
                'last_error_message' => ['$last' => '$error_message'],
            ]],
            ['$sort' => ['count' => -1]],
            ['$project' => [
                '_id' => 0,
                'error_code' => '$_id',
                'count' => 1,
                'last_error_message' => 1,
            ]],
        ]);
    }

    public function queueBySession(string|int $tenantId, array $filters): array
    {
        $match = $this->messageMatch($tenantId, $filters);
        $match['status'] = ['$in' => ['queued', 'waiting', 'processing']];

        return $this->aggregateMessages([
            ['$match' => $match],
            ['$group' => [
                '_id' => '$session_id',
                'queued' => ['$sum' => ['$cond' => [['$eq' => ['$status', 'queued']], 1, 0]]],
                'waiting' => ['$sum' => ['$cond' => [['$eq' => ['$status', 'waiting']], 1, 0]]],
                'processing' => ['$sum' => ['$cond' => [['$eq' => ['$status', 'processing']], 1, 0]]],
                'avg_delay_seconds' => ['$avg' => '$delay_seconds'],
                'max_position_snapshot' => ['$max' => '$queue_position_snapshot'],
                'total' => ['$sum' => 1],
            ]],
            ['$sort' => ['total' => -1]],
            ['$project' => [
                '_id' => 0,
                'session_id' => '$_id',
                'queued' => 1,
                'waiting' => 1,
                'processing' => 1,
                'avg_delay_seconds' => ['$round' => ['$avg_delay_seconds', 2]],
                'max_position_snapshot' => 1,
                'total' => 1,
            ]],
        ]);
    }

    public function deliveryRate(string|int $tenantId, array $filters): array
    {
        $rows = $this->aggregateMessages([
            ['$match' => $this->messageMatch($tenantId, $filters)],
            ['$group' => [
                '_id' => null,
                'sent' => ['$sum' => ['$cond' => [['$eq' => ['$status', 'sent']], 1, 0]]],
                'delivered' => ['$sum' => ['$cond' => [['$eq' => ['$status', 'delivered']], 1, 0]]],
                'failed' => ['$sum' => ['$cond' => [['$eq' => ['$status', 'failed']], 1, 0]]],
                'total' => ['$sum' => 1],
                'avg_queue_seconds' => ['$avg' => '$delay_seconds'],
            ]],
            ['$project' => [
                '_id' => 0,
                'sent' => 1,
                'delivered' => 1,
                'failed' => 1,
                'total' => 1,
                'avg_queue_seconds' => ['$round' => ['$avg_queue_seconds', 2]],
            ]],
        ]);

        return $rows[0] ?? [
            'sent' => 0,
            'delivered' => 0,
            'failed' => 0,
            'total' => 0,
            'avg_queue_seconds' => 0,
        ];
    }

    /**
     * @param list<array<string, mixed>> $pipeline
     * @return list<array<string, mixed>>
     */
    private function aggregateMessages(array $pipeline): array
    {
        return Message::raw(
            fn (Collection $collection): array => array_map(
                static fn (object|array $row): array => (array) $row,
                $collection->aggregate($pipeline)->toArray(),
            ),
        );
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    private function messageMatch(string|int $tenantId, array $filters): array
    {
        $match = [
            'tenant_id' => ['$in' => [(string) $tenantId, $tenantId]],
        ];

        if (isset($filters['session_id'])) {
            $match['session_id'] = (string) $filters['session_id'];
        }

        $date = [];

        if (isset($filters['date_from'])) {
            $date['$gte'] = CarbonImmutable::parse((string) $filters['date_from'])->startOfDay()->toDateTime();
        }

        if (isset($filters['date_to'])) {
            $date['$lte'] = CarbonImmutable::parse((string) $filters['date_to'])->endOfDay()->toDateTime();
        }

        if ($date !== []) {
            $match['created_at'] = $date;
        }

        return $match;
    }
}
