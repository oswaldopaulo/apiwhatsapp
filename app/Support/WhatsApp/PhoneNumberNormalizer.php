<?php

declare(strict_types=1);

namespace App\Support\WhatsApp;

final class PhoneNumberNormalizer
{
    public function normalize(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }
}
