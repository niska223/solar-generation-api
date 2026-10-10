<?php

return [

    /*
    |--------------------------------------------------------------------------
    | JWT Signing Secret
    |--------------------------------------------------------------------------
    |
    | The symmetric key used to sign and verify JSON Web Tokens (HMAC). A
    | dedicated secret should be provided via the JWT_SECRET environment
    | variable. When it is not set the application key is used as a safe
    | fallback so tokens are never signed with an empty/known value.
    |
    */

    'secret' => env('JWT_SECRET') ?: env('APP_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Signing Algorithm
    |--------------------------------------------------------------------------
    |
    | Any HMAC algorithm supported by firebase/php-jwt. HS256 is recommended.
    |
    */

    'algo' => env('JWT_ALGO', 'HS256'),

    /*
    |--------------------------------------------------------------------------
    | Registered Claims
    |--------------------------------------------------------------------------
    |
    | iss (issuer) and aud (audience) are validated on every decode so a token
    | minted for another service can never be replayed against this API.
    |
    */

    'issuer' => env('JWT_ISSUER', 'solar-generation-api'),
    'audience' => env('JWT_AUDIENCE', 'slsea-api'),

    /*
    |--------------------------------------------------------------------------
    | Token Lifetimes (minutes)
    |--------------------------------------------------------------------------
    |
    | Access tokens are short lived; refresh tokens are long lived but can
    | only be exchanged at the dedicated refresh endpoint.
    |
    */

    'ttl' => (int) env('JWT_TTL', 60),
    'refresh_ttl' => (int) env('JWT_REFRESH_TTL', 20160),

    /*
    |--------------------------------------------------------------------------
    | Clock Skew Leeway (seconds)
    |--------------------------------------------------------------------------
    |
    | Tolerated drift when validating the nbf/iat/exp claims across servers.
    |
    */

    'leeway' => (int) env('JWT_LEEWAY', 30),

    /*
    |--------------------------------------------------------------------------
    | Revocation (Blacklist) Prefix
    |--------------------------------------------------------------------------
    |
    | Revoked token identifiers (jti) are stored in the cache for the
    | remainder of their lifetime so logout takes effect immediately.
    |
    */

    'blacklist_prefix' => env('JWT_BLACKLIST_PREFIX', 'jwt:blacklist:'),

];
