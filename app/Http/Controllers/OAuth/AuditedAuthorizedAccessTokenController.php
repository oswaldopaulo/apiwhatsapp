<?php

declare(strict_types=1);

namespace App\Http\Controllers\OAuth;

use App\Services\Audit\AuditService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Passport\Http\Controllers\AuthorizedAccessTokenController;

final class AuditedAuthorizedAccessTokenController extends AuthorizedAccessTokenController
{
    public function destroy(Request $request, string $tokenId): Response
    {
        $response = parent::destroy($request, $tokenId);

        app(AuditService::class)->record('oauth.token_revoked', $response->getStatusCode() === Response::HTTP_NO_CONTENT ? 'success' : 'not_found', [
            'token_id_hash' => hash('sha256', $tokenId),
            'response_status' => $response->getStatusCode(),
        ], null, $request->user(), $request);

        return $response;
    }
}
