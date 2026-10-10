<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Lightweight, unauthenticated liveness/readiness probe.
 *
 * Registered in bootstrap/app.php without any middleware so a hosting
 * platform can poll it even before sessions or TLS detection are configured.
 * It never echoes credentials or exception details.
 */
class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $database = 'ok';

        try {
            DB::connection()->getPdo();
            DB::select('select 1');
        } catch (Throwable) {
            $database = 'unavailable';
        }

        $healthy = $database === 'ok';

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'app' => config('app.name'),
            'environment' => config('app.env'),
            'database' => $database,
            'timestamp' => now()->toIso8601String(),
        ], $healthy ? 200 : 503);
    }
}
