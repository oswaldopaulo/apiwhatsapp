<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\MessageStatus;
use App\Http\Requests\SendMessageRequest;
use App\Services\Audit\AuditService;
use App\Services\WhatsApp\MessageService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;

final class MessageController extends Controller
{
    public function send(
        SendMessageRequest $request,
        MessageService $messages,
        TenantContext $tenantContext,
        AuditService $audit,
    ): JsonResponse {
        $reservation = $messages->send($request->toDto($tenantContext->current()));
        $audit->record('message.send_requested', 'accepted', [
            'message_id' => $reservation->messageId,
            'session_id' => $reservation->sessionId,
            'to' => $request->validated('to'),
            'type' => $request->validated('type'),
            'queue_position_snapshot' => $reservation->queuePositionSnapshot,
            'delay_seconds' => $reservation->delaySeconds,
        ], $tenantContext->current(), $request->user(), $request);

        return response()->json([
            'data' => [
                'message_id' => $reservation->messageId,
                'status' => MessageStatus::Queued->value,
                'queue_position_snapshot' => $reservation->queuePositionSnapshot,
                'delay_seconds' => $reservation->delaySeconds,
            ],
        ], 202);
    }
}
