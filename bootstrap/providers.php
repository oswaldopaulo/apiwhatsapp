<?php

use App\Providers\AuthorizationServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\OAuthServiceProvider;

return [
    AppServiceProvider::class,
    AuthorizationServiceProvider::class,
    OAuthServiceProvider::class,
];
