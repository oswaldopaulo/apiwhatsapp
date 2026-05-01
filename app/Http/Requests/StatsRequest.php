<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class StatsRequest extends FormRequest
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
            'date_from' => ['sometimes', 'date'],
            'date_to' => ['sometimes', 'date'],
            'session_id' => ['sometimes', 'string', 'max:120'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->filled('date_from') || ! $this->filled('date_to')) {
                    return;
                }

                if (CarbonImmutable::parse((string) $this->input('date_to'))->lt(CarbonImmutable::parse((string) $this->input('date_from')))) {
                    $validator->errors()->add('date_to', 'The date_to must be greater than or equal to date_from.');
                }
            },
        ];
    }

    /**
     * @return array{date_from?: string, date_to?: string, session_id?: string}
     */
    public function filters(): array
    {
        return array_filter([
            'date_from' => $this->filled('date_from') ? (string) $this->input('date_from') : null,
            'date_to' => $this->filled('date_to') ? (string) $this->input('date_to') : null,
            'session_id' => $this->filled('session_id') ? (string) $this->input('session_id') : null,
        ], static fn (?string $value): bool => $value !== null);
    }
}
