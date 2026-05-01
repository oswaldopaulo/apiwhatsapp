<?php

declare(strict_types=1);

namespace App\Services\Stats;

use App\Models\Tenant;
use App\Models\WhatsApp\WhatsAppSession;
use App\Services\Stats\Contracts\StatsAggregationRepositoryInterface;
use Illuminate\Support\Facades\Cache;

final readonly class StatsService
{
    public function __construct(
        private StatsAggregationRepositoryInterface $aggregations,
    ) {
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function messagesByHour(Tenant $tenant, array $filters): array
    {
        return $this->remember($tenant, 'messages-hour', $filters, fn (): array => [
            'series' => $this->aggregations->messagesByHour($tenant->getKey(), $filters),
            'filters' => $filters,
        ]);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function messagesByDay(Tenant $tenant, array $filters): array
    {
        return $this->remember($tenant, 'messages-day', $filters, fn (): array => [
            'series' => $this->aggregations->messagesByDay($tenant->getKey(), $filters),
            'filters' => $filters,
        ]);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function errors(Tenant $tenant, array $filters): array
    {
        return $this->remember($tenant, 'errors', $filters, fn (): array => [
            'items' => $this->aggregations->errorsByType($tenant->getKey(), $filters),
            'filters' => $filters,
        ]);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function queue(Tenant $tenant, array $filters): array
    {
        return $this->remember($tenant, 'queue', $filters, fn (): array => [
            'sessions' => $this->aggregations->queueBySession($tenant->getKey(), $filters),
            'filters' => $filters,
        ]);
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function deliveryRate(Tenant $tenant, array $filters): array
    {
        return $this->remember($tenant, 'delivery-rate', $filters, function () use ($tenant, $filters): array {
            $totals = $this->aggregations->deliveryRate($tenant->getKey(), $filters);
            $resolved = (int) ($totals['sent'] ?? 0) + (int) ($totals['delivered'] ?? 0) + (int) ($totals['failed'] ?? 0);
            $successful = (int) ($totals['sent'] ?? 0) + (int) ($totals['delivered'] ?? 0);

            return [
                'totals' => [
                    'sent' => (int) ($totals['sent'] ?? 0),
                    'delivered' => (int) ($totals['delivered'] ?? 0),
                    'failed' => (int) ($totals['failed'] ?? 0),
                    'total' => (int) ($totals['total'] ?? 0),
                    'avg_queue_seconds' => (float) ($totals['avg_queue_seconds'] ?? 0),
                ],
                'success_rate' => $resolved > 0 ? round(($successful / $resolved) * 100, 2) : 0.0,
                'filters' => $filters,
            ];
        });
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function sessions(Tenant $tenant, array $filters): array
    {
        return $this->remember($tenant, 'sessions', $filters, function () use ($tenant, $filters): array {
            $query = WhatsAppSession::query()->where('tenant_id', $tenant->getKey());

            if (isset($filters['session_id'])) {
                $query->whereKey($filters['session_id']);
            }

            $sessions = $query->get();

            return [
                'summary' => [
                    'total' => $sessions->count(),
                    'avg_risk_score' => round((float) $sessions->avg('risk_score'), 2),
                    'by_status' => $sessions
                        ->groupBy(fn (WhatsAppSession $session): string => $session->status->value)
                        ->map(fn ($items): int => $items->count())
                        ->sortKeys()
                        ->all(),
                ],
                'items' => $sessions
                    ->map(fn (WhatsAppSession $session): array => [
                        'session_id' => (string) $session->getKey(),
                        'name' => $session->name,
                        'provider' => $session->provider,
                        'status' => $session->status->value,
                        'risk_score' => $session->risk_score,
                        'last_activity_at' => $session->last_activity_at?->toJSON(),
                    ])
                    ->values()
                    ->all(),
                'filters' => $filters,
            ];
        });
    }

    /**
     * @param array<string, mixed> $filters
     * @param callable(): array<string, mixed> $callback
     * @return array<string, mixed>
     */
    private function remember(Tenant $tenant, string $metric, array $filters, callable $callback): array
    {
        return Cache::remember(
            $this->cacheKey($tenant, $metric, $filters),
            now()->addSeconds((int) config('whatsapp.stats.cache_seconds', 60)),
            $callback,
        );
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function cacheKey(Tenant $tenant, string $metric, array $filters): string
    {
        ksort($filters);

        return sprintf(
            'tenant:%s:stats:%s:%s',
            $tenant->getKey(),
            $metric,
            sha1(json_encode($filters, JSON_THROW_ON_ERROR)),
        );
    }
}
