<?php

namespace App\Providers;

use App\Models\District;
use App\Models\GridSubstation;
use App\Models\Province;
use App\Models\SolarInstallation;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->bindRouteModels();
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
