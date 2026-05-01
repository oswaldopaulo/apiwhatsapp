<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class UpdateTenantConfigurationRequest extends FormRequest
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
            'queue_driver' => ['sometimes', 'string', Rule::in(['redis', 'database', 'default'])],
            'redis_enabled' => ['sometimes', 'boolean'],
            'anti_ban_enabled' => ['sometimes', 'boolean'],
            'delay_min_seconds' => ['sometimes', 'integer', 'min:1', 'max:3600'],
            'delay_max_seconds' => ['sometimes', 'integer', 'min:1', 'max:3600'],
            'max_messages_per_minute' => ['sometimes', 'integer', 'min:1', 'max:1000'],
            'max_daily_messages' => ['sometimes', 'integer', 'min:1', 'max:1000000'],
            'webhook_url' => ['sometimes', 'nullable', 'url', 'max:2048'],
            'webhook_secret' => ['sometimes', 'nullable', 'string', 'min:16', 'max:255'],
            'settings' => ['sometimes', 'array'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $delayMin = (int) $this->input('delay_min_seconds', 0);
                $delayMax = (int) $this->input('delay_max_seconds', 0);

                if ($this->has('delay_min_seconds') && $this->has('delay_max_seconds') && $delayMax < $delayMin) {
                    $validator->errors()->add('delay_max_seconds', 'The delay max seconds must be greater than or equal to delay min seconds.');
                }
            },
        ];
    }
}
