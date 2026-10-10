<?php

use App\Exceptions\ApiExceptionRenderer;
use App\Http\Middleware\AuthorizeDevice;
use App\Http\Middleware\AuthorizeOperator;
use App\Http\Middleware\HandleConditionalRequests;
use App\Http\Middleware\RequireJsonAccept;
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
            'api.operator' => AuthorizeOperator::class,
            'api.device' => AuthorizeDevice::class,
        ]);

        $middleware->api(
            prepend: [
                RequireJsonAccept::class,
            ],
            append: [
                HandleConditionalRequests::class,
            ],
        );
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
