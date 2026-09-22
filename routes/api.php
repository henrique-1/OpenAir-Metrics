<?php

use App\Http\Controllers\Api\MedicaoApiController;
use Illuminate\Support\Facades\Route;

// Endpoint exclusivo de telemetria e envio de medições das estações em campo
Route::post('/medicoes', [MedicaoApiController::class, 'store'])
    ->middleware(['throttle:60,1'])
    ->name('api.medicoes.store');
