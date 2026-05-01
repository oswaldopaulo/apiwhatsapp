<?php

declare(strict_types=1);

namespace App\Http\Controllers\OAuth;

use App\Services\Audit\AuditService;
use Laravel\Passport\Http\Controllers\AccessTokenController;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\Response;

final class AuditedAccessTokenController extends AccessTokenController
{
    public function issueToken(ServerRequestInterface $psrRequest, ResponseInterface $psrResponse): Response
    {
        $response = parent::issueToken($psrRequest, $psrResponse);
        $body = $psrRequest->getParsedBody();
        $body = is_array($body) ? $body : [];

        app(AuditService::class)->record('oauth.token_issued', $response->getStatusCode() < 400 ? 'success' : 'failed', [
            'grant_type' => $body['grant_type'] ?? null,
            'client_id' => $body['client_id'] ?? null,
            'response_status' => $response->getStatusCode(),
        ]);

        return $response;
    }
}
