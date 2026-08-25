<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

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
