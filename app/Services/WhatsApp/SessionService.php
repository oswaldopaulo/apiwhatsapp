<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Enums\SessionEventType;
use App\Enums\WhatsAppSessionStatus;
use App\Events\WhatsApp\SessionConnected;
use App\Events\WhatsApp\SessionCreated;
use App\Events\WhatsApp\SessionDeleted;
use App\Events\WhatsApp\SessionDisconnected;
use App\Events\WhatsApp\SessionQrUpdated;
use App\Events\WhatsApp\SessionStatusChanged;
use App\Models\Tenant;
use App\Models\WhatsApp\WhatsAppSession;
use App\Services\WhatsApp\Contracts\SessionEventRecorderInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;

final readonly class SessionService
{
    public function __construct(
        private SessionEventRecorderInterface $events,
    ) {
    }

    /**
     * @return Collection<int, WhatsAppSession>
     */
    public function listForTenant(Tenant $tenant): Collection
    {
        return WhatsAppSession::query()
            ->where('tenant_id', $tenant->getKey())
            ->latest()
            ->get();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(Tenant $tenant, array $data): WhatsAppSession
    {
        $status = WhatsAppSessionStatus::from((string) ($data['status'] ?? WhatsAppSessionStatus::Connecting->value));

        /** @var WhatsAppSession $session */
        $session = WhatsAppSession::query()->create([
            'tenant_id' => $tenant->getKey(),
            'name' => $data['name'],
            'provider' => $data['provider'] ?? 'fake',
            'status' => $status->value,
            'phone_number' => $data['phone_number'] ?? null,
            'last_activity_at' => null,
            'risk_score' => $this->recalculateRiskScore($status),
            'metadata' => $data['metadata'] ?? [],
            'encrypted_credentials' => $data['credentials'] ?? null,
        ]);

        $this->recordEvent($session, SessionEventType::Created, [
            'status' => $status->value,
        ]);

        event(new SessionCreated($tenant->getKey(), $session->getKey(), $status->value));

        return $session->refresh();
    }

    public function findForTenant(Tenant $tenant, string|int $id): WhatsAppSession
    {
        /** @var WhatsAppSession $session */
        $session = WhatsAppSession::query()
            ->where('tenant_id', $tenant->getKey())
            ->whereKey($id)
            ->firstOrFail();

        return $session;
    }

    public function updateStatus(WhatsAppSession $session, WhatsAppSessionStatus $status): WhatsAppSession
    {
        $session->forceFill([
            'status' => $status->value,
            'risk_score' => $this->recalculateRiskScore($status),
            'last_activity_at' => now(),
        ])->save();

        $this->recordEvent($session, $this->eventTypeForStatus($status), [
            'status' => $status->value,
            'risk_score' => $session->risk_score,
        ]);

        event(new SessionStatusChanged($session->tenant_id, $session->getKey(), $status->value, $session->risk_score));
        $this->broadcastSpecificStatusEvent($session, $status);

        return $session->refresh();
    }

    public function updateQr(
        WhatsAppSession $session,
        ?string $qrReference = null,
        ?int $expiresInSeconds = null,
    ): WhatsAppSession {
        $session = $this->updateStatus($session, WhatsAppSessionStatus::QrPending);

        event(new SessionQrUpdated(
            $session->tenant_id,
            $session->getKey(),
            $qrReference,
            $expiresInSeconds,
        ));

        return $session;
    }

    public function delete(WhatsAppSession $session): void
    {
        $tenantId = $session->tenant_id;
        $sessionId = $session->getKey();

        $this->recordEvent($session, SessionEventType::Deleted);
        $session->delete();

        event(new SessionDeleted($tenantId, $sessionId));
    }

    /**
     * @throws AuthorizationException
     */
    public function ensureCanSend(Tenant $tenant, string|int $sessionId): WhatsAppSession
    {
        $session = $this->findForTenant($tenant, $sessionId);

        if ($session->status->blocksSending()) {
            throw new AuthorizationException("The WhatsApp session [{$session->status->value}] cannot send messages.");
        }

        return $session;
    }

    public function recalculateRiskScore(WhatsAppSessionStatus $status): int
    {
        return $status->riskScore();
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function recordEvent(WhatsAppSession $session, SessionEventType $eventType, array $payload = []): void
    {
        $this->events->record($session, $eventType, $payload);
    }

    private function eventTypeForStatus(WhatsAppSessionStatus $status): SessionEventType
    {
        return match ($status) {
            WhatsAppSessionStatus::Connecting, WhatsAppSessionStatus::QrPending => SessionEventType::Connecting,
            WhatsAppSessionStatus::Connected => SessionEventType::Connected,
            WhatsAppSessionStatus::Disconnected => SessionEventType::Disconnected,
            WhatsAppSessionStatus::Expired => SessionEventType::Expired,
            WhatsAppSessionStatus::Banned => SessionEventType::Banned,
            WhatsAppSessionStatus::Error => SessionEventType::Error,
        };
    }

    private function broadcastSpecificStatusEvent(WhatsAppSession $session, WhatsAppSessionStatus $status): void
    {
        match ($status) {
            WhatsAppSessionStatus::Connected => event(new SessionConnected(
                $session->tenant_id,
                $session->getKey(),
                $session->risk_score,
            )),
            WhatsAppSessionStatus::Disconnected => event(new SessionDisconnected(
                $session->tenant_id,
                $session->getKey(),
                $session->risk_score,
            )),
            default => null,
        };
    }
}
