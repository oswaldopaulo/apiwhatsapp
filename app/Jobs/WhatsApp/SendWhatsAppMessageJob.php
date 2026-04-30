<?php

declare(strict_types=1);

namespace App\Jobs\WhatsApp;

use App\DTOs\WhatsApp\OutboundMessageData;
use App\Enums\QueueName;
use App\Jobs\Concerns\TenantAwareJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class SendWhatsAppMessageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use TenantAwareJob;

    public string $tenantId;

    public function __construct(
        public readonly OutboundMessageData $message,
    ) {
        $this->tenantId = $message->tenantId;
        $this->onQueue(QueueName::Messages->value);
    }

    public function handle(): void
    {
        // Transport integration will be added behind services/actions.
    }
}
