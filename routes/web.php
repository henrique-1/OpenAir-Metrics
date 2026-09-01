<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EstacaoController;
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

    Route::get('/estacoes', [EstacaoController::class, 'index'])->name('estacoes.index');
    Route::get('/estacoes/create', [EstacaoController::class, 'create'])->name('estacoes.create');
    Route::post('/estacoes', [EstacaoController::class, 'store'])->name('estacoes.store');

    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
});
