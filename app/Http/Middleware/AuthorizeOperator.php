<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Protects administrative write endpoints (POST/PUT/PATCH/DELETE).
 *
 * Deny-by-default: if no operator token is configured every write is rejected
 * with 401. A configured token must be presented as "Authorization: Bearer"
 * or "X-Api-Key". A wrong token yields 403. This placeholder is replaced by
 * the real authenticated authorization layer in Prompt 3.
 */
class AuthorizeOperator
{
    public function handle(Request $request, Closure $next): Response
    {
        $configured = config('api.admin_token');

        if (blank($configured)) {
            throw ApiException::unauthorized(
                'Administrative writes are disabled until API authentication is configured.'
            );
        }

        $provided = $request->bearerToken() ?: $request->header('X-Api-Key');

        if (blank($provided)) {
            throw ApiException::unauthorized('Operator credentials are required for this request.');
        }

        if (! hash_equals((string) $configured, (string) $provided)) {
            throw ApiException::forbidden('The supplied operator credentials are invalid.');
        }

        return $next($request);
    }
}
