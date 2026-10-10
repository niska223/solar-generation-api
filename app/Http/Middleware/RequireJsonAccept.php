<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rejects requests that explicitly ask for a representation the API cannot
 * produce. The API is JSON-only, so responses are served only when the client
 * accepts JSON (or does not express a preference). A browser-style
 * "Accept: text/html" request receives 406 Not Acceptable.
 */
class RequireJsonAccept
{
    public function handle(Request $request, Closure $next): Response
    {
        $accept = $request->header('Accept');

        if ($accept !== null && trim($accept) !== '' && ! $this->acceptsJson($accept)) {
            throw ApiException::notAcceptable(details: ['accept' => $accept]);
        }

        return $next($request);
    }

    private function acceptsJson(string $accept): bool
    {
        foreach (explode(',', $accept) as $part) {
            $type = strtolower(trim(explode(';', $part)[0]));

            if ($type === '') {
                continue;
            }

            if ($type === '*/*' || $type === 'application/*' || $type === 'application/json' || str_ends_with($type, '+json')) {
                return true;
            }
        }

        return false;
    }
}
