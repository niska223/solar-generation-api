<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API Authentication
    |--------------------------------------------------------------------------
    |
    | The API is protected by JWT bearer authentication. Tokens are minted at
    | /api/auth/operator/login (SLSEA operators) and /api/auth/device/login
    | (solar devices). See config/jwt.php for signing and lifetime settings.
    |
    | There are no static shared tokens or hardcoded credentials anywhere in
    | the codebase: operator passwords and device secrets are stored only as
    | password hashes.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

    'pagination' => [
        'default_per_page' => 15,
        'max_per_page' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Requests per minute. The authenticated "api" bucket is keyed by the JWT
    | subject (falling back to the client IP), while the stricter "auth" bucket
    | protects the credential-issuing endpoints from brute force attempts.
    |
    */

    'rate_limits' => [
        'api' => (int) env('API_RATE_LIMIT', 120),
        'auth' => (int) env('API_AUTH_RATE_LIMIT', 10),
    ],

];
