<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DistrictController;
use App\Http\Controllers\Api\GenerationReadingController;
use App\Http\Controllers\Api\GridSubstationController;
use App\Http\Controllers\Api\ProvinceController;
use App\Http\Controllers\Api\SolarInstallationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (prefixed with /api)
|--------------------------------------------------------------------------
|
| Authentication:
|     POST /api/auth/operator/login  - SLSEA operator credentials -> JWT
|     POST /api/auth/device/login    - solar device secret         -> JWT
|     POST /api/auth/refresh         - exchange a refresh token
|     GET  /api/auth/me              - current principal
|     POST /api/auth/logout          - revoke the presented access token
|
| Read endpoints require a SLSEA operator (national / provincial / district)
| JWT and are scoped to that operator's geographic jurisdiction.
| Administrative writes require the "administrator" role.
| Device ingestion requires a "device" JWT tied to a single installation.
| All endpoints except the credential-issuing routes are rate limited.
|
*/

Route::prefix('auth')->group(function (): void {
    Route::post('/operator/login', [AuthController::class, 'operatorLogin'])
        ->middleware('throttle:auth')
        ->name('auth.operator.login');

    Route::post('/device/login', [AuthController::class, 'deviceLogin'])
        ->middleware('throttle:auth')
        ->name('auth.device.login');

    Route::post('/refresh', [AuthController::class, 'refresh'])
        ->middleware('throttle:auth')
        ->name('auth.refresh');

    Route::get('/me', [AuthController::class, 'me'])
        ->middleware(['auth.jwt', 'throttle:api'])
        ->name('auth.me');

    Route::post('/logout', [AuthController::class, 'logout'])
        ->middleware(['auth.jwt', 'throttle:api'])
        ->name('auth.logout');
});

Route::middleware(['auth.jwt', 'role:operator,administrator', 'throttle:api'])->group(function (): void {
    Route::get('/provinces', [ProvinceController::class, 'index'])->name('provinces.index');
    Route::get('/provinces/{province}', [ProvinceController::class, 'show'])->name('provinces.show');
    Route::get('/provinces/{province}/districts', [ProvinceController::class, 'districts'])->name('provinces.districts');

    Route::get('/districts', [DistrictController::class, 'index'])->name('districts.index');
    Route::get('/districts/{district}', [DistrictController::class, 'show'])->name('districts.show');
    Route::get('/districts/{district}/grid-substations', [DistrictController::class, 'gridSubstations'])->name('districts.grid-substations');
    Route::get('/districts/{district}/generation-summary', [DistrictController::class, 'generationSummary'])->name('districts.generation-summary');

    Route::get('/grid-substations', [GridSubstationController::class, 'index'])->name('grid-substations.index');
    Route::get('/grid-substations/{gridSubstation}', [GridSubstationController::class, 'show'])->name('grid-substations.show');
    Route::get('/grid-substations/{gridSubstation}/installations', [GridSubstationController::class, 'installations'])->name('grid-substations.installations');

    Route::get('/installations', [SolarInstallationController::class, 'index'])->name('installations.index');
    Route::get('/installations/{installation}', [SolarInstallationController::class, 'show'])->name('installations.show');
    Route::get('/installations/{installation}/latest-reading', [SolarInstallationController::class, 'latestReading'])->name('installations.latest-reading');
    Route::get('/installations/{installation}/readings', [GenerationReadingController::class, 'index'])->name('installations.readings.index');
    Route::get('/installations/{installation}/readings/{reading}', [GenerationReadingController::class, 'show'])->name('installations.readings.show');
});

Route::middleware(['auth.jwt', 'role:administrator', 'throttle:api'])->group(function (): void {
    Route::post('/provinces', [ProvinceController::class, 'store'])->name('provinces.store');
    Route::match(['put', 'patch'], '/provinces/{province}', [ProvinceController::class, 'update'])->name('provinces.update');
    Route::delete('/provinces/{province}', [ProvinceController::class, 'destroy'])->name('provinces.destroy');

    Route::post('/districts', [DistrictController::class, 'store'])->name('districts.store');
    Route::match(['put', 'patch'], '/districts/{district}', [DistrictController::class, 'update'])->name('districts.update');
    Route::delete('/districts/{district}', [DistrictController::class, 'destroy'])->name('districts.destroy');

    Route::post('/grid-substations', [GridSubstationController::class, 'store'])->name('grid-substations.store');
    Route::match(['put', 'patch'], '/grid-substations/{gridSubstation}', [GridSubstationController::class, 'update'])->name('grid-substations.update');
    Route::delete('/grid-substations/{gridSubstation}', [GridSubstationController::class, 'destroy'])->name('grid-substations.destroy');

    Route::post('/installations', [SolarInstallationController::class, 'store'])->name('installations.store');
    Route::match(['put', 'patch'], '/installations/{installation}', [SolarInstallationController::class, 'update'])->name('installations.update');
    Route::delete('/installations/{installation}', [SolarInstallationController::class, 'destroy'])->name('installations.destroy');
});

Route::middleware(['auth.jwt', 'role:device', 'throttle:api'])->group(function (): void {
    Route::post('/installations/{installation}/readings', [GenerationReadingController::class, 'store'])->name('installations.readings.store');
});
