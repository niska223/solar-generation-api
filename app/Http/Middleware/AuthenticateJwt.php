<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures a valid, unexpired JWT bearer token is present.
 *
 * Deny-by-default: no token, a tampered token, an expired token or a revoked
 * token all result in 401 with the standard error envelope.
 */
class AuthenticateJwt
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('api')->check()) {
            throw ApiException::unauthorized('A valid JWT bearer token is required for this endpoint.');
        }

        return $next($request);
    }
}
