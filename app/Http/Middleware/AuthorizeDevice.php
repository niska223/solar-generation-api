<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use App\Models\SolarInstallation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates a device for reading ingestion.
 *
 * Deny-by-default: if no device tokens are configured, ingestion is disabled
 * with 401. The token is scoped to a single installation, so a device can only
 * append readings to its own installation - cross-installation ingestion is
 * rejected with 403. Replaced by real device credentials in Prompt 3.
 */
class AuthorizeDevice
{
    public function handle(Request $request, Closure $next): Response
    {
        $installation = $request->route('installation');

        if (! $installation instanceof SolarInstallation) {
            throw ApiException::unauthorized('A valid target installation is required for ingestion.');
        }

        $tokens = config('api.device_tokens', []);

        if (empty($tokens)) {
            throw ApiException::unauthorized(
                'Device ingestion is disabled until device credentials are configured.'
            );
        }

        $provided = $request->header('X-Device-Token') ?: $request->bearerToken();

        if (blank($provided)) {
            throw ApiException::unauthorized('A device token is required for ingestion.');
        }

        $expected = $tokens[$installation->meter_id]
            ?? $tokens[(string) $installation->id]
            ?? null;

        if ($expected === null || ! hash_equals((string) $expected, (string) $provided)) {
            throw ApiException::forbidden(
                'The supplied device credentials are not valid for this installation.'
            );
        }

        return $next($request);
    }
}
