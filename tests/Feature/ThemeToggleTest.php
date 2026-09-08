<?php

use App\Models\Bairro;
use App\Models\Cidade;
use App\Models\Estacao;
use App\Models\Estado;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('tela do mapa (rota /) forca estritamente o modo claro e remove classe dark', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee("document.documentElement.classList.remove('dark');", false);
    $response->assertDontSee("document.documentElement.classList.add('dark')", false);
    $response->assertSee('images/3.png', false);
    $response->assertDontSee('images/3 - dark.png', false);
});

test('rotas administrativas e de autenticacao incluem script anti-fouc e suporte a dark mode', function () {
    $loginResponse = $this->get(route('login'));
    $loginResponse->assertOk();
    $loginResponse->assertSee('dark:bg-athens-gray-950');
    $loginResponse->assertSee('dark:bg-athens-gray-900/95');

    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertSee("localStorage.getItem('theme')", false);
    $response->assertSee("document.documentElement.classList.add('dark')", false);
    $response->assertSee('dark:bg-athens-gray-950');
    $response->assertSee('dark:text-athens-gray-100');
});

test('usuario autenticado visualiza o botao seletor de tema na sidebar do painel administrativo', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertSee('id="theme-toggle-btn"', false);
    $response->assertSee('toggleTheme()', false);
    $response->assertSee('id="theme-toggle-label"', false);
    $response->assertSee('Modo Claro');
    $response->assertSee('aria-label="Alternar modo claro e escuro"', false);
    $response->assertSee('updateThemeUI()', false);
});

test('seletor de tema esta disponivel na tela de perfil do usuario', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('perfil.edit'));

    $response->assertOk();
    $response->assertSee('id="theme-toggle-btn"', false);
    $response->assertSee('toggleTheme()', false);
});

test('seletor de tema esta disponivel na tela de estacoes', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('estacoes.index'));

    $response->assertOk();
    $response->assertSee('id="theme-toggle-btn"', false);
});

test('sidebar possui versoes light e dark do logotipo no painel administrativo', function () {
    $user = User::factory()->create();

    $dashboardResponse = $this->actingAs($user)->get(route('dashboard'));
    $dashboardResponse->assertOk();
    $dashboardResponse->assertSee('images/3.png', false);
    $dashboardResponse->assertSee('images/3 - dark.png', false);
});

test('telas administrativas de cadastro e planejamento possuem suporte a dark mode', function () {
    $estado = Estado::factory()->create(['uf' => 'SP']);
    $cidade = Cidade::factory()->create(['estado_id' => $estado->id, 'nome' => 'São Paulo']);
    $bairro = Bairro::factory()->create(['cidade_id' => $cidade->id]);

    $admin = User::factory()->create([
        'nivel' => 'administrador',
        'cidade_id' => $cidade->id,
    ]);
    $estacao = Estacao::factory()->matriz()->create([
        'bairro_id' => $bairro->id,
        'created_by' => $admin->id,
    ]);

    // Planejador de Malha
    $resPlanejar = $this->actingAs($admin)->get(route('estacoes.planejar'));
    $resPlanejar->assertOk();
    $resPlanejar->assertSee('dark:bg-athens-gray-950');
    $resPlanejar->assertSee('dark:bg-athens-gray-900');

    // Cadastro de Nova Estação
    $resEstacaoCreate = $this->actingAs($admin)->get(route('estacoes.create'));
    $resEstacaoCreate->assertOk();
    $resEstacaoCreate->assertSee('dark:bg-athens-gray-950');
    $resEstacaoCreate->assertSee('dark:bg-athens-gray-900');

    // Cadastrar Equipamentos no Patrimônio
    $resPatrimonioCreate = $this->actingAs($admin)->get(route('patrimonios.create'));
    $resPatrimonioCreate->assertOk();
    $resPatrimonioCreate->assertSee('dark:bg-athens-gray-950');
    $resPatrimonioCreate->assertSee('dark:bg-athens-gray-900');

    // Roteiro de Instalação em Campo
    $resInstalacaoShow = $this->actingAs($admin)->get(route('instalacoes.show', $estacao->public_id));
    $resInstalacaoShow->assertOk();
    $resInstalacaoShow->assertSee('dark:bg-athens-gray-950');
    $resInstalacaoShow->assertSee('dark:bg-athens-gray-900');

    // Cadastrar Novo Usuário
    $resUsuarioCreate = $this->actingAs($admin)->get(route('usuarios.create'));
    $resUsuarioCreate->assertOk();
    $resUsuarioCreate->assertSee('dark:bg-athens-gray-950');
    $resUsuarioCreate->assertSee('dark:bg-athens-gray-900');
});
