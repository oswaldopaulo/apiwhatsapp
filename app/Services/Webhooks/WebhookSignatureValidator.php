<?php

declare(strict_types=1);

namespace App\Services\Webhooks;

use App\Models\Tenant;
use App\Models\TenantConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\Response;

final class WebhookSignatureValidator
{
    public function validate(Request $request, Tenant $tenant): string
    {
        $timestamp = $this->timestamp($request);
        $signature = $this->signature($request);
        $secret = $this->secretForTenant($tenant);

        if ($secret === null || $secret === '') {
            throw new HttpException(Response::HTTP_UNAUTHORIZED, 'Webhook secret is not configured.');
        }

        $expected = hash_hmac('sha256', "{$timestamp}.{$request->getContent()}", $secret);
        $received = $this->normalizeSignature($signature);

        if (! hash_equals($expected, $received)) {
            throw new HttpException(Response::HTTP_UNAUTHORIZED, 'Invalid webhook signature.');
        }

        $this->ensureNotReplayed($tenant, $timestamp, $received);

        return hash('sha256', $received);
    }

    private function timestamp(Request $request): int
    {
        $header = (string) config('whatsapp.webhooks.timestamp_header', 'X-Webhook-Timestamp');
        $value = $request->headers->get($header);

        if ($value === null || ! ctype_digit($value)) {
            throw new HttpException(Response::HTTP_UNAUTHORIZED, 'Invalid webhook timestamp.');
        }

        $timestamp = (int) $value;
        $tolerance = (int) config('whatsapp.webhooks.tolerance_seconds', 300);

        if (abs(now()->timestamp - $timestamp) > $tolerance) {
            throw new HttpException(Response::HTTP_UNAUTHORIZED, 'Webhook timestamp is outside the allowed tolerance.');
        }

        return $timestamp;
    }

    private function signature(Request $request): string
    {
        $header = (string) config('whatsapp.webhooks.signature_header', 'X-Webhook-Signature');
        $signature = $request->headers->get($header);

        if ($signature === null || trim($signature) === '') {
            throw new HttpException(Response::HTTP_UNAUTHORIZED, 'Webhook signature is required.');
        }

        return $signature;
    }

    private function secretForTenant(Tenant $tenant): ?string
    {
        /** @var TenantConfiguration|null $configuration */
        $configuration = TenantConfiguration::query()
            ->where('tenant_id', $tenant->getKey())
            ->first();

        return $configuration?->webhook_secret ?: config('whatsapp.webhooks.secret');
    }

    private function normalizeSignature(string $signature): string
    {
        $signature = trim($signature);

        if (str_starts_with($signature, 'sha256=')) {
            return substr($signature, 7);
        }

        return $signature;
    }

    private function ensureNotReplayed(Tenant $tenant, int $timestamp, string $signature): void
    {
        $tolerance = (int) config('whatsapp.webhooks.tolerance_seconds', 300);
        $key = sprintf(
            'webhook-replay:%s:%s',
            $tenant->getKey(),
            hash('sha256', "{$timestamp}:{$signature}"),
        );

        if (! Cache::add($key, true, now()->addSeconds($tolerance))) {
            throw new HttpException(Response::HTTP_CONFLICT, 'Webhook replay detected.');
        }
    }
}
