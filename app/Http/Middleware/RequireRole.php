<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use App\Models\Device;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts an endpoint to one or more JWT principal roles.
 *
 * Supported roles:
 *  - administrator : SLSEA administrator, may manage reference data (CRUD).
 *  - operator      : any SLSEA read operator (national, provincial, district).
 *  - national / provincial / district : a specific read jurisdiction.
 *  - device        : a registered solar device (reading ingestion only).
 */
class RequireRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $principal = Auth::guard('api')->user();

        if ($principal === null) {
            throw ApiException::unauthorized('A valid JWT bearer token is required for this endpoint.');
        }

        foreach ($roles as $role) {
            if ($this->matches($principal, $role)) {
                return $next($request);
            }
        }

        throw ApiException::forbidden('Your account is not permitted to perform this action.');
    }

    private function matches(Authenticatable $principal, string $role): bool
    {
        return match ($role) {
            'administrator' => $principal instanceof User && $principal->isAdministrator(),
            'operator' => $principal instanceof User && $principal->isOperator(),
            'national' => $principal instanceof User && $principal->isNational(),
            'provincial' => $principal instanceof User && $principal->isProvincial(),
            'district' => $principal instanceof User && $principal->isDistrict(),
            'device' => $principal instanceof Device,
            default => false,
        };
    }
}
