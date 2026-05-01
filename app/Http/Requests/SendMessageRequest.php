<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTOs\WhatsApp\OutboundMessageData;
use App\Models\Tenant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tenant_id' => ['prohibited'],
            'session_id' => ['required', 'string', 'max:120'],
            'to' => ['required', 'string', 'regex:/^\d{10,15}$/'],
            'type' => ['required', Rule::in(['text'])],
            'content' => ['required', 'string', 'min:1', 'max:4096'],
        ];
    }

    public function toDto(Tenant $tenant): OutboundMessageData
    {
        /** @var array{session_id: string, to: string, type: string, content: string} $data */
        $data = $this->validated();

        return new OutboundMessageData(
            tenantId: (string) $tenant->getKey(),
            whatsAppAccountId: $data['session_id'],
            recipient: $data['to'],
            body: $data['content'],
            sessionId: $data['session_id'],
            metadata: [
                'source' => 'api',
                'message_type' => $data['type'],
            ],
        );
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tenant_id.prohibited' => 'tenant_id must be resolved by the API context.',
            'to.regex' => 'The recipient phone must contain only digits and have 10 to 15 characters.',
        ];
    }
}
