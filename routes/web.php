<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EstacaoController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InstalacaoController;
use App\Http\Controllers\LocalidadeController;
use App\Http\Controllers\PatrimonioController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\PlanejamentoController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Agrupamos rotas que apenas visitantes (não logados) podem acessar
Route::middleware('guest')->group(function () {

    // Rota GET para exibir o formulário.
    // O name('login') é crucial aqui, pois usamos {{ route('login') }} no nosso HTML.
    Route::get('/login', [AuthController::class, 'create'])->name('login');

    // Rota POST para processar a submissão do formulário
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
});

// Rotas protegidas (apenas usuários autenticados)
Route::middleware('auth')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/graficos', [DashboardController::class, 'dadosGrafico'])->name('dashboard.graficos');

    // Módulo de Estações
    Route::get('/estacoes', [EstacaoController::class, 'index'])->name('estacoes.index');
    Route::get('/estacoes/create', [EstacaoController::class, 'create'])->name('estacoes.create');
    Route::post('/estacoes', [EstacaoController::class, 'store'])->name('estacoes.store');
    Route::get('/estacoes/coordenadas', [LocalidadeController::class, 'coordenadas'])->name('estacoes.coordenadas');

    // Módulo de Planejamento Automático de Malha Viária
    Route::get('/estacoes/planejar', [PlanejamentoController::class, 'create'])->name('estacoes.planejar');
    Route::post('/estacoes/calcular-malha', [PlanejamentoController::class, 'calcular'])->name('estacoes.calcular-malha');
    Route::post('/estacoes/snap-to-road', [PlanejamentoController::class, 'snapToRoad'])->name('estacoes.snap-to-road');
    Route::post('/estacoes/salvar-malha', [PlanejamentoController::class, 'salvar'])->name('estacoes.salvar-malha');

    // Módulo de Gestão de Patrimônio
    Route::get('/patrimonios', [PatrimonioController::class, 'index'])->name('patrimonios.index');
    Route::get('/patrimonios/create', [PatrimonioController::class, 'create'])->name('patrimonios.create');
    Route::post('/patrimonios', [PatrimonioController::class, 'store'])->name('patrimonios.store');
    Route::post('/patrimonios/batch', [PatrimonioController::class, 'storeBatch'])->name('patrimonios.store-batch');
    Route::get('/patrimonios/disponiveis', [PatrimonioController::class, 'apiDisponiveis'])->name('patrimonios.disponiveis');
    Route::delete('/patrimonios/{patrimonio}', [PatrimonioController::class, 'destroy'])->name('patrimonios.destroy');

    // Módulo de Ordens de Instalação e Ativação em Campo
    Route::get('/instalacoes', [InstalacaoController::class, 'index'])->name('instalacoes.index');
    Route::get('/instalacoes/{public_id}', [InstalacaoController::class, 'show'])->name('instalacoes.show');
    Route::post('/instalacoes/{public_id}/vincular-mac', [InstalacaoController::class, 'vincularMac'])->name('instalacoes.vincular-mac');

    // Endpoints Internos de Localidades e Geocodificação Reversa
    Route::get('/localidades/estados', [LocalidadeController::class, 'estados'])->name('localidades.estados');
    Route::get('/localidades/estados/{estado}/cidades', [LocalidadeController::class, 'cidades'])->name('localidades.estados.cidades');
    Route::get('/localidades/cidades/{uf}', [LocalidadeController::class, 'cidadesPorUf'])->name('localidades.cidades.por-uf');
    Route::get('/localidades/cidades/{cidade}/bairros', [LocalidadeController::class, 'bairros'])->name('localidades.cidades.bairros');
    Route::get('/geocoding/reverse', [LocalidadeController::class, 'reverse'])->name('geocoding.reverse');
    Route::post('/estacoes/{public_id}/solicitar-substituicao', [EstacaoController::class, 'solicitarSubstituicao'])->name('estacoes.solicitar-substituicao');
    Route::post('/estacoes/{public_id}/substituir-sensor', [EstacaoController::class, 'substituirSensor'])->name('estacoes.substituir-sensor');

    // Módulo de Perfil de Usuário
    Route::get('/perfil', [PerfilController::class, 'edit'])->name('perfil.edit');
    Route::put('/perfil', [PerfilController::class, 'update'])->name('perfil.update');

    // Módulo de Gestão de Usuários Municipais (Administrador)
    Route::get('/usuarios', [UsuarioController::class, 'index'])->name('usuarios.index');
    Route::get('/usuarios/create', [UsuarioController::class, 'create'])->name('usuarios.create');
    Route::post('/usuarios', [UsuarioController::class, 'store'])->name('usuarios.store');
    Route::patch('/usuarios/{user}/toggle-status', [UsuarioController::class, 'toggleStatus'])->name('usuarios.toggle-status');
    Route::get('/usuarios/{user}/edit', [UsuarioController::class, 'edit'])->name('usuarios.edit');
    Route::put('/usuarios/{user}', [UsuarioController::class, 'update'])->name('usuarios.update');

    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
});
