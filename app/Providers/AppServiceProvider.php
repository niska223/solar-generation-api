<?php

namespace App\Providers;

use App\Auth\JwtGuard;
use App\Models\District;
use App\Models\GridSubstation;
use App\Models\Province;
use App\Models\SolarInstallation;
use App\Services\JwtService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(JwtService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerJwtGuard();
        $this->registerRateLimiters();
        $this->bindRouteModels();
    }

    private function registerJwtGuard(): void
    {
        Auth::extend('jwt', function ($app, $name, array $config) {
            return new JwtGuard($app->make(JwtService::class));
        });
    }

    private function registerRateLimiters(): void
    {
        RateLimiter::for('api', function (Request $request) {
            $key = $request->user('api')?->getAuthIdentifier() ?: $request->ip();

            return Limit::perMinute((int) config('api.rate_limits.api', 120))->by('api:'.(string) $key);
        });

        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute((int) config('api.rate_limits.auth', 10))->by('auth:'.(string) $request->ip());
        });
    }

    private function bindRouteModels(): void
    {
        // Accept either the numeric primary key or the stable natural key
        // (code / name / meter_id) for ergonomic API URLs.

        Route::bind('province', function (mixed $value): Province {
            return Province::query()
                ->when(
                    ctype_digit((string) $value),
                    fn ($q) => $q->where('id', $value),
                    fn ($q) => $q->where('code', $value),
                )
                ->firstOrFail();
        });

        Route::bind('district', function (mixed $value): District {
            return District::query()
                ->when(
                    ctype_digit((string) $value),
                    fn ($q) => $q->where('id', $value),
                    fn ($q) => $q->where('name', $value),
                )
                ->firstOrFail();
        });

        Route::bind('gridSubstation', function (mixed $value): GridSubstation {
            return GridSubstation::query()
                ->when(
                    ctype_digit((string) $value),
                    fn ($q) => $q->where('id', $value),
                    fn ($q) => $q->where('code', $value),
                )
                ->firstOrFail();
        });

        Route::bind('installation', function (mixed $value): SolarInstallation {
            return SolarInstallation::query()
                ->when(
                    ctype_digit((string) $value),
                    fn ($q) => $q->where('id', $value),
                    fn ($q) => $q->where('meter_id', $value),
                )
                ->firstOrFail();
        });
    }
}
