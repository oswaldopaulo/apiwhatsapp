<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\WhatsApp\ProcessWebhookJob;
use App\Models\Tenant;
use App\Services\Webhooks\Contracts\WebhookEventStoreInterface;
use App\Services\Webhooks\WebhookSignatureValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\Response;

final class WebhookController extends Controller
{
    public function whatsapp(
        Request $request,
        WebhookSignatureValidator $signatureValidator,
        WebhookEventStoreInterface $events,
    ): JsonResponse {
        $tenant = $this->resolveTenant($request);
        $rawBody = $request->getContent();
        $payload = $this->payload($rawBody);
        $signatureHash = $signatureValidator->validate($request, $tenant);
        $eventType = (string) Arr::get($payload, 'event', Arr::get($payload, 'type', 'unknown'));

        $record = $events->recordReceived(
            tenant: $tenant,
            eventType: $eventType,
            rawBody: $rawBody,
            payload: $payload,
            headers: $request->headers->all(),
            timestamp: (int) $request->headers->get((string) config('whatsapp.webhooks.timestamp_header', 'X-Webhook-Timestamp')),
            signatureHash: $signatureHash,
        );

        ProcessWebhookJob::dispatch($record->id)
            ->onQueue(config('queue-control.queues.webhooks', 'webhooks'));

        return response()->json([
            'data' => [
                'webhook_event_id' => $record->id,
                'status' => 'accepted',
            ],
        ], Response::HTTP_ACCEPTED);
    }

    private function resolveTenant(Request $request): Tenant
    {
        $header = (string) config('api-security.tenant.header', 'X-Tenant-ID');
        $tenantId = $request->headers->get($header);

        if ($tenantId === null || trim($tenantId) === '') {
            throw new HttpException(Response::HTTP_NOT_FOUND, 'Tenant not found.');
        }

        /** @var Tenant|null $tenant */
        $tenant = Tenant::query()
            ->whereKey($tenantId)
            ->orWhere('public_id', $tenantId)
            ->first();

        return $tenant ?? throw new HttpException(Response::HTTP_NOT_FOUND, 'Tenant not found.');
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(string $rawBody): array
    {
        $payload = json_decode($rawBody, true);

        if (! is_array($payload)) {
            throw new HttpException(Response::HTTP_UNPROCESSABLE_ENTITY, 'Invalid webhook payload.');
        }

        return $payload;
    }
}
