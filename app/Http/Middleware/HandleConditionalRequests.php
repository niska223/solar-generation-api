<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Implements HTTP conditional GET (RFC 7232).
 *
 * A strong ETag is derived from the response body for cacheable requests and
 * evaluated against "If-None-Match"/"If-Modified-Since". On a match the
 * response is converted to 304 Not Modified with an empty body by the
 * underlying Symfony response. A Last-Modified header may be supplied by the
 * controller for single resources so "If-Modified-Since" also works.
 */
class HandleConditionalRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->isMethodCacheable() && $response->getStatusCode() === 200) {
            if (! $response->headers->has('ETag')) {
                $response->setEtag(md5((string) $response->getContent()));
            }

            $response->headers->set('Vary', 'Accept', false);

            $response->isNotModified($request);
        }

        return $response;
    }
}
