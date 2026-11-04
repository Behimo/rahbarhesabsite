<?php

use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Laravel\Sanctum\Http\Middleware\AuthenticateSession;

return [

    /*
    | The mobile API never accepts a browser session. An empty guard list
    | stops Sanctum from treating a logged-in website user as an app user.
    */
    'stateful' => [],

    'guard' => [],

    /*
    | Minutes. Overridden at boot from cms.app.token_ttl_days so the app
    | token lifetime has a single source of truth. Null lets expires_at win.
    */
    'expiration' => null,

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', 'rh_app_'),

    /*
    | The app authenticates with bearer tokens only. The SPA cookie route
    | would let a browser start a Sanctum session, so it stays disabled.
    */
    'routes' => false,

    'middleware' => [
        'authenticate_session' => AuthenticateSession::class,
        'encrypt_cookies' => EncryptCookies::class,
        'validate_csrf_token' => ValidateCsrfToken::class,
    ],

];
