# Exemplo de cliente Laravel

```php
<?php

use Illuminate\Support\Facades\Http;

$baseUrl = config('services.apiwhatsapp.url', 'https://api.example.com');
$tenantId = config('services.apiwhatsapp.tenant_id');
$token = config('services.apiwhatsapp.token');

$response = Http::acceptJson()
    ->withToken($token)
    ->withHeaders(['X-Tenant-ID' => $tenantId])
    ->post("{$baseUrl}/api/v1/messages/send", [
        'session_id' => '1',
        'to' => '5511999999999',
        'type' => 'text',
        'content' => 'Mensagem enviada via cliente Laravel.',
    ]);

if ($response->failed()) {
    report('Falha ao enfileirar mensagem: '.$response->body());
}

return $response->json();
```
