<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EstacaoController;
use App\Http\Controllers\InstalacaoController;
use App\Http\Controllers\PatrimonioController;
use App\Http\Controllers\PlanejamentoController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});

// Agrupamos rotas que apenas visitantes (não logados) podem acessar
Route::middleware('guest')->group(function () {

    // Rota GET para exibir o formulário.
    // O name('login') é crucial aqui, pois usamos {{ route('login') }} no nosso HTML.
    Route::get('/login', [AuthController::class, 'create'])->name('login');

    // Rota POST para processar a submissão do formulário
    Route::post('/login', [AuthController::class, 'store']);
});

// Rotas protegidas (apenas usuários autenticados)
Route::middleware('auth')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Módulo de Estações
    Route::get('/estacoes', [EstacaoController::class, 'index'])->name('estacoes.index');
    Route::get('/estacoes/create', [EstacaoController::class, 'create'])->name('estacoes.create');
    Route::post('/estacoes', [EstacaoController::class, 'store'])->name('estacoes.store');

    // Módulo de Planejamento Automático de Malha Viária
    Route::get('/estacoes/planejar', [PlanejamentoController::class, 'create'])->name('estacoes.planejar');
    Route::post('/estacoes/salvar-malha', [PlanejamentoController::class, 'salvar'])->name('estacoes.salvar-malha');

    // Módulo de Gestão de Patrimônio
    Route::get('/patrimonios', [PatrimonioController::class, 'index'])->name('patrimonios.index');
    Route::get('/patrimonios/create', [PatrimonioController::class, 'create'])->name('patrimonios.create');
    Route::post('/patrimonios', [PatrimonioController::class, 'store'])->name('patrimonios.store');
    Route::post('/patrimonios/batch', [PatrimonioController::class, 'storeBatch'])->name('patrimonios.store-batch');
    Route::delete('/patrimonios/{patrimonio}', [PatrimonioController::class, 'destroy'])->name('patrimonios.destroy');

    // Módulo de Ordens de Instalação e Ativação em Campo
    Route::get('/instalacoes', [InstalacaoController::class, 'index'])->name('instalacoes.index');
    Route::get('/instalacoes/{public_id}', [InstalacaoController::class, 'show'])->name('instalacoes.show');

    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
});
