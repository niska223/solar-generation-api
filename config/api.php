<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Administrative API Token
    |--------------------------------------------------------------------------
    |
    | Administrative write operations (POST/PUT/PATCH/DELETE) are protected by
    | a single operator token. The token is supplied by the client using either
    | the "Authorization: Bearer <token>" header or the "X-Api-Key" header.
    |
    | This is a deliberate deny-by-default placeholder until a full
    | authentication system (Sanctum/Passport + roles) is implemented in a
    | later prompt. When no token is configured, EVERY administrative write is
    | rejected with 401 Unauthorized rather than being exposed unprotected.
    |
    */

    'admin_token' => env('API_ADMIN_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Device Ingestion Tokens
    |--------------------------------------------------------------------------
    |
    | Maps a solar installation to the secret token its meter/logger uses to
    | report readings. Keys may be the installation "meter_id" (preferred) or
    | the numeric installation id. Values are the shared secrets.
    |
    | The value is expected to be a JSON object, e.g.:
    |   API_DEVICE_TOKENS={"MTR-000001":"secret-a","MTR-000002":"secret-b"}
    |
    | Because the map is empty by default, device ingestion is deny-by-default:
    | a request without a recognised token for the target installation cannot
    | create readings. This prevents unauthenticated or cross-installation
    | ingestion until Prompt 3 adds proper device credentials.
    |
    */

    'device_tokens' => (function (): array {
        $raw = env('API_DEVICE_TOKENS');

        if (blank($raw)) {
            return [];
        }

        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) ? $decoded : [];
    })(),

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

    'pagination' => [
        'default_per_page' => 15,
        'max_per_page' => 100,
    ],

];
