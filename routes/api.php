<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LocalidadeController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Endpoints de Localidades IBGE, Coordenadas e Geocodificação Reversa
Route::get('/estados', [LocalidadeController::class, 'estados'])->name('api.estados');
Route::get('/estados/{estado}/cidades', [LocalidadeController::class, 'cidades'])->name('api.estados.cidades');
Route::get('/cidades/{cidade}/bairros', [LocalidadeController::class, 'bairros'])->name('api.cidades.bairros');
Route::get('/estacoes/coordenadas', [LocalidadeController::class, 'coordenadas'])->name('api.estacoes.coordenadas');
Route::get('/geocoding/reverse', [LocalidadeController::class, 'reverse'])->name('api.geocoding.reverse');

// Endpoint de Métricas e Gráficos do Dashboard
Route::get('/dashboard/graficos', [DashboardController::class, 'dadosGrafico'])->name('api.dashboard.graficos');
