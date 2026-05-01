<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StatsRequest;
use App\Services\Stats\StatsService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;

final class StatsController extends Controller
{
    public function __construct(
        private readonly StatsService $stats,
        private readonly TenantContext $tenantContext,
    ) {
    }

    public function messagesHour(StatsRequest $request): JsonResponse
    {
        return $this->json($this->stats->messagesByHour($this->tenantContext->current(), $request->filters()));
    }

    public function messagesDay(StatsRequest $request): JsonResponse
    {
        return $this->json($this->stats->messagesByDay($this->tenantContext->current(), $request->filters()));
    }

    public function errors(StatsRequest $request): JsonResponse
    {
        return $this->json($this->stats->errors($this->tenantContext->current(), $request->filters()));
    }

    public function queue(StatsRequest $request): JsonResponse
    {
        return $this->json($this->stats->queue($this->tenantContext->current(), $request->filters()));
    }

    public function deliveryRate(StatsRequest $request): JsonResponse
    {
        return $this->json($this->stats->deliveryRate($this->tenantContext->current(), $request->filters()));
    }

    public function sessions(StatsRequest $request): JsonResponse
    {
        return $this->json($this->stats->sessions($this->tenantContext->current(), $request->filters()));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function json(array $data): JsonResponse
    {
        return response()->json(['data' => $data]);
    }
}
