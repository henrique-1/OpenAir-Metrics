<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Models\Sensor;
use Illuminate\Support\Facades\Auth;

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

    Route::get('/dashboard', function () {
        // 1. Busca os sensores criados pelo usuário logado
        $sensores = Sensor::where('created_by', Auth::id())->get();

        // 2. Passa a variável $sensores para a view renderizar a tabela
        return view('dashboard', [
            'sensores' => $sensores
        ]);
    })->name('dashboard');

    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
});
