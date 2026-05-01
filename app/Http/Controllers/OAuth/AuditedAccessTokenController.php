<?php

declare(strict_types=1);

namespace App\Http\Controllers\OAuth;

use App\Services\Audit\AuditService;
use Laravel\Passport\Http\Controllers\AccessTokenController;
use Psr\Http\Message\ServerRequestInterface;

final class AuditedAccessTokenController extends AccessTokenController
{
    public function issueToken(ServerRequestInterface $request)
    {
        $response = parent::issueToken($request);

        app(AuditService::class)->record('oauth.token_issued', $response->getStatusCode() < 400 ? 'success' : 'failed', [
            'grant_type' => $request->getParsedBody()['grant_type'] ?? null,
            'client_id' => $request->getParsedBody()['client_id'] ?? null,
            'response_status' => $response->getStatusCode(),
        ]);

        return $response;
    }
}
