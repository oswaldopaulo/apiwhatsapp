<?php

declare(strict_types=1);

namespace App\Providers;

use DateInterval;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

final class OAuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Passport::ignoreRoutes();
    }

    public function boot(): void
    {
        Passport::tokensExpireIn(new DateInterval(
            sprintf('PT%dM', (int) config('oauth.tokens.access_token_minutes', 15))
        ));

        Passport::refreshTokensExpireIn(new DateInterval(
            sprintf('P%dD', (int) config('oauth.tokens.refresh_token_days', 30))
        ));

        Passport::personalAccessTokensExpireIn(new DateInterval(
            sprintf('P%dD', (int) config('oauth.tokens.personal_access_token_days', 7))
        ));

        Passport::clientCredentialsTokensExpireIn(new DateInterval(
            sprintf('PT%dM', (int) config('oauth.tokens.access_token_minutes', 15))
        ));

        Passport::tokensCan(config('oauth.scopes', []));
    }
}
