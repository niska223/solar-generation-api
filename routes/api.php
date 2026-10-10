<?php

use App\Http\Controllers\Api\DistrictController;
use App\Http\Controllers\Api\GenerationReadingController;
use App\Http\Controllers\Api\GridSubstationController;
use App\Http\Controllers\Api\ProvinceController;
use App\Http\Controllers\Api\SolarInstallationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes (v1 - unversioned, prefixed with /api)
|--------------------------------------------------------------------------
|
| Read endpoints are public (open data for SLSEA). Administrative writes are
| protected by the `api.operator` middleware (deny-by-default). Device
| ingestion is protected by the `api.device` middleware and scoped to a single
| installation.
|
*/

Route::get('/provinces', [ProvinceController::class, 'index'])->name('provinces.index');
Route::post('/provinces', [ProvinceController::class, 'store'])->middleware('api.operator')->name('provinces.store');
Route::get('/provinces/{province}', [ProvinceController::class, 'show'])->name('provinces.show');
Route::match(['put', 'patch'], '/provinces/{province}', [ProvinceController::class, 'update'])->middleware('api.operator')->name('provinces.update');
Route::delete('/provinces/{province}', [ProvinceController::class, 'destroy'])->middleware('api.operator')->name('provinces.destroy');
Route::get('/provinces/{province}/districts', [ProvinceController::class, 'districts'])->name('provinces.districts');

Route::get('/districts', [DistrictController::class, 'index'])->name('districts.index');
Route::post('/districts', [DistrictController::class, 'store'])->middleware('api.operator')->name('districts.store');
Route::get('/districts/{district}', [DistrictController::class, 'show'])->name('districts.show');
Route::match(['put', 'patch'], '/districts/{district}', [DistrictController::class, 'update'])->middleware('api.operator')->name('districts.update');
Route::delete('/districts/{district}', [DistrictController::class, 'destroy'])->middleware('api.operator')->name('districts.destroy');
Route::get('/districts/{district}/grid-substations', [DistrictController::class, 'gridSubstations'])->name('districts.grid-substations');
Route::get('/districts/{district}/generation-summary', [DistrictController::class, 'generationSummary'])->name('districts.generation-summary');

Route::get('/grid-substations', [GridSubstationController::class, 'index'])->name('grid-substations.index');
Route::post('/grid-substations', [GridSubstationController::class, 'store'])->middleware('api.operator')->name('grid-substations.store');
Route::get('/grid-substations/{gridSubstation}', [GridSubstationController::class, 'show'])->name('grid-substations.show');
Route::match(['put', 'patch'], '/grid-substations/{gridSubstation}', [GridSubstationController::class, 'update'])->middleware('api.operator')->name('grid-substations.update');
Route::delete('/grid-substations/{gridSubstation}', [GridSubstationController::class, 'destroy'])->middleware('api.operator')->name('grid-substations.destroy');
Route::get('/grid-substations/{gridSubstation}/installations', [GridSubstationController::class, 'installations'])->name('grid-substations.installations');

Route::get('/installations', [SolarInstallationController::class, 'index'])->name('installations.index');
Route::post('/installations', [SolarInstallationController::class, 'store'])->middleware('api.operator')->name('installations.store');
Route::get('/installations/{installation}', [SolarInstallationController::class, 'show'])->name('installations.show');
Route::match(['put', 'patch'], '/installations/{installation}', [SolarInstallationController::class, 'update'])->middleware('api.operator')->name('installations.update');
Route::delete('/installations/{installation}', [SolarInstallationController::class, 'destroy'])->middleware('api.operator')->name('installations.destroy');

Route::get('/installations/{installation}/latest-reading', [SolarInstallationController::class, 'latestReading'])->name('installations.latest-reading');
Route::get('/installations/{installation}/readings', [GenerationReadingController::class, 'index'])->name('installations.readings.index');
Route::post('/installations/{installation}/readings', [GenerationReadingController::class, 'store'])->middleware('api.device')->name('installations.readings.store');
Route::get('/installations/{installation}/readings/{reading}', [GenerationReadingController::class, 'show'])->name('installations.readings.show');
