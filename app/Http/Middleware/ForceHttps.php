<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces HTTPS in production.
 *
 * When FORCE_HTTPS is enabled every generated URL uses the https scheme, GET
 * requests received over plain HTTP are redirected with a 301 and the
 * X-Forwarded-Proto header (set by a TLS-terminating proxy) is honoured.
 */
class ForceHttps
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('app.force_https')) {
            return $next($request);
        }

        URL::forceScheme('https');

        if ($request->secure() || $request->header('X-Forwarded-Proto') === 'https') {
            return $next($request);
        }

        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        return $next($request);
    }
}
