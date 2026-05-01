<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreWhatsAppSessionRequest;
use App\Models\WhatsApp\WhatsAppSession;
use App\Services\WhatsApp\SessionService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;

final class WhatsAppSessionController extends Controller
{
    public function __construct(
        private readonly SessionService $sessions,
        private readonly TenantContext $tenantContext,
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->sessions
                ->listForTenant($this->tenantContext->current())
                ->map(fn (WhatsAppSession $session): array => $this->resource($session))
                ->values(),
        ]);
    }

    public function store(StoreWhatsAppSessionRequest $request): JsonResponse
    {
        $session = $this->sessions->create(
            $this->tenantContext->current(),
            $request->validated(),
        );

        return response()->json([
            'data' => $this->resource($session),
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json([
            'data' => $this->resource($this->sessions->findForTenant($this->tenantContext->current(), $id)),
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $session = $this->sessions->findForTenant($this->tenantContext->current(), $id);
        $this->sessions->delete($session);

        return response()->json(null, 204);
    }

    /**
     * @return array<string, mixed>
     */
    private function resource(WhatsAppSession $session): array
    {
        return [
            'id' => $session->getKey(),
            'tenant_id' => $session->tenant_id,
            'name' => $session->name,
            'provider' => $session->provider,
            'status' => $session->status->value,
            'phone_number' => $session->phone_number,
            'last_activity_at' => $session->last_activity_at?->toJSON(),
            'risk_score' => $session->risk_score,
            'metadata' => $session->metadata ?? [],
            'created_at' => $session->created_at?->toJSON(),
            'updated_at' => $session->updated_at?->toJSON(),
        ];
    }
}
