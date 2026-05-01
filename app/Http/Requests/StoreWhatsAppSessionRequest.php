<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\WhatsAppSessionStatus;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreWhatsAppSessionRequest extends FormRequest
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
        $tenantId = app(TenantContext::class)->id();

        return [
            'tenant_id' => ['prohibited'],
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('whatsapp_sessions', 'name')->where('tenant_id', $tenantId),
            ],
            'provider' => ['sometimes', 'string', 'max:80'],
            'status' => ['sometimes', 'string', Rule::in(array_map(
                static fn (WhatsAppSessionStatus $status): string => $status->value,
                WhatsAppSessionStatus::cases(),
            ))],
            'phone_number' => [
                'sometimes',
                'nullable',
                'string',
                'regex:/^\d{10,15}$/',
                Rule::unique('whatsapp_sessions', 'phone_number')->where('tenant_id', $tenantId),
            ],
            'metadata' => ['sometimes', 'array'],
            'credentials' => ['sometimes', 'array'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tenant_id.prohibited' => 'tenant_id must be resolved by the API context.',
            'phone_number.regex' => 'The phone number must contain only digits and have 10 to 15 characters.',
        ];
    }
}
