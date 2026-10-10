<?php

use App\Exceptions\ApiExceptionRenderer;
use App\Http\Middleware\AuthenticateJwt;
use App\Http\Middleware\ForceHttps;
use App\Http\Middleware\HandleConditionalRequests;
use App\Http\Middleware\RequireJsonAccept;
use App\Http\Middleware\RequireRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'auth.jwt' => AuthenticateJwt::class,
            'role' => RequireRole::class,
        ]);

        $middleware->api(
            prepend: [
                RequireJsonAccept::class,
            ],
            append: [
                HandleConditionalRequests::class,
            ],
        );

        $middleware->prepend(ForceHttps::class);

        $trustedProxies = env('TRUSTED_PROXIES');

        if (blank($trustedProxies)) {
            return;
        }

        if ($trustedProxies === '*' || str_contains((string) $trustedProxies, ',')) {
            $trustedProxies = array_map('trim', explode(',', (string) $trustedProxies));
        }

        $middleware->trustProxies(at: $trustedProxies);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, Throwable $e) => $request->is('api/*') || $request->expectsJson()
        );

        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return ApiExceptionRenderer::render($e, $request);
        });
    })->create();
