<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InstalacaoController;
use App\Http\Controllers\LocalidadeController;
use App\Http\Controllers\PatrimonioController;
use App\Http\Controllers\PlanejamentoController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Endpoints de Localidades IBGE, Coordenadas e Geocodificação Reversa
Route::get('/estados', [LocalidadeController::class, 'estados'])->name('api.estados');
Route::get('/estados/{estado}/cidades', [LocalidadeController::class, 'cidades'])->name('api.estados.cidades');
Route::get('/cidades/{uf}', [LocalidadeController::class, 'cidadesPorUf'])->name('api.cidades.por-uf');
Route::get('/cidades/{cidade}/bairros', [LocalidadeController::class, 'bairros'])->name('api.cidades.bairros');
Route::get('/estacoes/coordenadas', [LocalidadeController::class, 'coordenadas'])->name('api.estacoes.coordenadas');
Route::get('/geocoding/reverse', [LocalidadeController::class, 'reverse'])->name('api.geocoding.reverse');

// Endpoints de Planejamento e Instalação
Route::post('/estacoes/calcular-malha', [PlanejamentoController::class, 'calcular'])->name('api.estacoes.calcular-malha');
Route::post('/estacoes/snap-to-road', [PlanejamentoController::class, 'snapToRoad'])->name('api.estacoes.snap-to-road');
Route::post('/instalacoes/{public_id}/vincular-mac', [InstalacaoController::class, 'vincularMac'])->name('api.instalacoes.vincular-mac');
Route::get('/patrimonios/disponiveis', [PatrimonioController::class, 'apiDisponiveis'])->name('api.patrimonios.disponiveis');

// Endpoint de Métricas e Gráficos do Dashboard
Route::get('/dashboard/graficos', [DashboardController::class, 'dadosGrafico'])->name('api.dashboard.graficos');
