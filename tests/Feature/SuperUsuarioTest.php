<?php

use App\Models\Cidade;
use App\Models\Estado;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('super-usuario autenticado visualiza listagem centralizada de administradores de todas as cidades', function () {
    $superadmin = User::factory()->create(['nivel' => 'superadmin', 'cidade_id' => null]);
    $cidade1 = Cidade::factory()->create(['nome' => 'Campinas']);
    $cidade2 = Cidade::factory()->create(['nome' => 'Santos']);

    $admin1 = User::factory()->create([
        'name' => 'Admin Campinas',
        'email' => 'admin@campinas.sp.gov.br',
        'nivel' => 'administrador',
        'cidade_id' => $cidade1->id,
    ]);

    $admin2 = User::factory()->create([
        'name' => 'Admin Santos',
        'email' => 'admin@santos.sp.gov.br',
        'nivel' => 'administrador',
        'cidade_id' => $cidade2->id,
    ]);

    $tecnico = User::factory()->create([
        'name' => 'Tecnico Municipal',
        'nivel' => 'cadastrador',
        'cidade_id' => $cidade1->id,
    ]);

    $response = $this->actingAs($superadmin)->get(route('usuarios.index'));

    $response->assertOk();
    $response->assertSee('Admin Campinas');
    $response->assertSee('Admin Santos');
    $response->assertDontSee('Tecnico Municipal'); // Superadmin só visualiza administradores
});

test('super-usuario pode filtrar administradores por municipio', function () {
    $superadmin = User::factory()->create(['nivel' => 'superadmin', 'cidade_id' => null]);
    $cidade1 = Cidade::factory()->create(['nome' => 'Campinas']);
    $cidade2 = Cidade::factory()->create(['nome' => 'Santos']);

    User::factory()->create([
        'name' => 'Admin Campinas',
        'nivel' => 'administrador',
        'cidade_id' => $cidade1->id,
    ]);

    User::factory()->create([
        'name' => 'Admin Santos',
        'nivel' => 'administrador',
        'cidade_id' => $cidade2->id,
    ]);

    $response = $this->actingAs($superadmin)->get(route('usuarios.index', ['cidade_id' => $cidade1->id]));

    $response->assertOk();
    $response->assertSee('Admin Campinas');
    $response->assertDontSee('Admin Santos');
});

test('super-usuario visualiza campo de estado e cidades desabilitadas inicialmente no cadastro', function () {
    $superadmin = User::factory()->create(['nivel' => 'superadmin', 'cidade_id' => null]);
    $estado = Estado::factory()->create(['nome' => 'São Paulo', 'uf' => 'SP']);
    Cidade::factory()->create(['nome' => 'Campinas', 'estado_id' => $estado->id]);

    $response = $this->actingAs($superadmin)->get(route('usuarios.create'));

    $response->assertOk();
    $response->assertSee('São Paulo (SP)');
    $response->assertSee('Selecione o estado primeiro...');
    $response->assertDontSee('Campinas');
});

test('super-usuario pode cadastrar novo administrador municipal escolhendo a cidade', function () {
    $superadmin = User::factory()->create(['nivel' => 'superadmin', 'cidade_id' => null]);
    $estado = Estado::factory()->create(['nome' => 'São Paulo', 'uf' => 'SP']);
    $cidade = Cidade::factory()->create(['nome' => 'Ribeirão Preto', 'estado_id' => $estado->id]);

    $payload = [
        'name' => 'Novo Gestor Ribeirão',
        'email' => 'gestor@ribeirao.sp.gov.br',
        'password' => 'senhaSegura123',
        'password_confirmation' => 'senhaSegura123',
        'estado_id' => $estado->id,
        'cidade_id' => $cidade->id,
    ];

    $response = $this->actingAs($superadmin)->post(route('usuarios.store'), $payload);

    $response->assertRedirect(route('usuarios.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('users', [
        'name' => 'Novo Gestor Ribeirão',
        'email' => 'gestor@ribeirao.sp.gov.br',
        'nivel' => 'administrador',
        'cidade_id' => $cidade->id,
        'ativo' => true,
    ]);
});

test('super-usuario nao pode cadastrar administrador com cidade que nao pertence ao estado selecionado', function () {
    $superadmin = User::factory()->create(['nivel' => 'superadmin', 'cidade_id' => null]);
    $estadoSp = Estado::factory()->create(['nome' => 'São Paulo', 'uf' => 'SP']);
    $estadoRj = Estado::factory()->create(['nome' => 'Rio de Janeiro', 'uf' => 'RJ']);
    $cidadeRj = Cidade::factory()->create(['nome' => 'Niterói', 'estado_id' => $estadoRj->id]);

    $payload = [
        'name' => 'Gestor Invalido',
        'email' => 'invalido@teste.com',
        'password' => 'senhaSegura123',
        'password_confirmation' => 'senhaSegura123',
        'estado_id' => $estadoSp->id,
        'cidade_id' => $cidadeRj->id, // Cidade pertence ao RJ, mas estado informado é SP
    ];

    $response = $this->actingAs($superadmin)->post(route('usuarios.store'), $payload);

    $response->assertSessionHasErrors('cidade_id');
});

test('super-usuario pode editar um administrador municipal e carregar apenas cidades do estado', function () {
    $superadmin = User::factory()->create(['nivel' => 'superadmin', 'cidade_id' => null]);
    $estadoSp = Estado::factory()->create(['nome' => 'São Paulo', 'uf' => 'SP']);
    $estadoRj = Estado::factory()->create(['nome' => 'Rio de Janeiro', 'uf' => 'RJ']);

    $cidade1 = Cidade::factory()->create(['nome' => 'Campinas', 'estado_id' => $estadoSp->id]);
    $cidade2 = Cidade::factory()->create(['nome' => 'Sorocaba', 'estado_id' => $estadoSp->id]);
    $cidadeOutroEstado = Cidade::factory()->create(['nome' => 'Niterói', 'estado_id' => $estadoRj->id]);

    $admin = User::factory()->create([
        'name' => 'Gestor Atual',
        'email' => 'gestor@antigo.sp.gov.br',
        'nivel' => 'administrador',
        'cidade_id' => $cidade1->id,
    ]);

    $responseEdit = $this->actingAs($superadmin)->get(route('usuarios.edit', $admin->id));
    $responseEdit->assertOk();
    $responseEdit->assertSee('Campinas');
    $responseEdit->assertSee('Sorocaba');
    $responseEdit->assertDontSee('Niterói'); // Cidades de outro estado não aparecem na renderização inicial

    $responseUpdate = $this->actingAs($superadmin)->put(route('usuarios.update', $admin->id), [
        'name' => 'Gestor Transferido',
        'email' => 'gestor@novo.sp.gov.br',
        'estado_id' => $estadoSp->id,
        'cidade_id' => $cidade2->id,
    ]);

    $responseUpdate->assertRedirect(route('usuarios.index'));

    $admin->refresh();
    expect($admin->name)->toBe('Gestor Transferido');
    expect($admin->email)->toBe('gestor@novo.sp.gov.br');
    expect($admin->cidade_id)->toBe($cidade2->id);
});

test('endpoint de localidades carrega cidades apenas do estado especificado', function () {
    $superadmin = User::factory()->create(['nivel' => 'superadmin', 'cidade_id' => null]);
    $estadoSp = Estado::factory()->create(['nome' => 'São Paulo', 'uf' => 'SP']);
    $estadoMg = Estado::factory()->create(['nome' => 'Minas Gerais', 'uf' => 'MG']);

    $cidadeSp = Cidade::factory()->create(['nome' => 'Santos', 'estado_id' => $estadoSp->id]);
    $cidadeMg = Cidade::factory()->create(['nome' => 'Belo Horizonte', 'estado_id' => $estadoMg->id]);

    $response = $this->actingAs($superadmin)->get(route('localidades.estados.cidades', $estadoSp->id));

    $response->assertOk();
    $response->assertJsonFragment(['id' => $cidadeSp->id, 'nome' => 'Santos']);
    $response->assertJsonMissing(['id' => $cidadeMg->id, 'nome' => 'Belo Horizonte']);
});

test('super-usuario pode desativar e reativar administrador municipal', function () {
    $superadmin = User::factory()->create(['nivel' => 'superadmin', 'cidade_id' => null]);
    $cidade = Cidade::factory()->create();

    $admin = User::factory()->create([
        'nivel' => 'administrador',
        'cidade_id' => $cidade->id,
        'ativo' => true,
    ]);

    // Desativar
    $responseDesativar = $this->actingAs($superadmin)->patch(route('usuarios.toggle-status', $admin->id));
    $responseDesativar->assertRedirect();
    $admin->refresh();
    expect($admin->ativo)->toBeFalse();

    // Reativar
    $responseReativar = $this->actingAs($superadmin)->patch(route('usuarios.toggle-status', $admin->id));
    $responseReativar->assertRedirect();
    $admin->refresh();
    expect($admin->ativo)->toBeTrue();
});

test('super-usuario nao pode desativar a si proprio', function () {
    $superadmin = User::factory()->create(['nivel' => 'superadmin', 'cidade_id' => null]);

    $response = $this->actingAs($superadmin)->patch(route('usuarios.toggle-status', $superadmin->id));
    $response->assertRedirect();
    $response->assertSessionHas('error');

    $superadmin->refresh();
    expect($superadmin->ativo)->toBeTrue();
});

test('super-usuario nao pode gerenciar usuarios nao administradores', function () {
    $superadmin = User::factory()->create(['nivel' => 'superadmin', 'cidade_id' => null]);
    $cidade = Cidade::factory()->create();

    $tecnico = User::factory()->create([
        'nivel' => 'cadastrador',
        'cidade_id' => $cidade->id,
    ]);

    $this->actingAs($superadmin)->get(route('usuarios.edit', $tecnico->id))->assertStatus(403);
    $this->actingAs($superadmin)->put(route('usuarios.update', $tecnico->id), [
        'name' => 'Tentativa',
        'email' => 'tentativa@teste.com',
        'cidade_id' => $cidade->id,
    ])->assertStatus(403);
    $this->actingAs($superadmin)->patch(route('usuarios.toggle-status', $tecnico->id))->assertStatus(403);
});

test('super-usuario e bloqueado com 403 ao tentar acessar modulos operacionais municipais', function () {
    $superadmin = User::factory()->create(['nivel' => 'superadmin', 'cidade_id' => null]);

    $this->actingAs($superadmin)->get(route('dashboard'))->assertStatus(403);
    $this->actingAs($superadmin)->get(route('estacoes.index'))->assertStatus(403);
    $this->actingAs($superadmin)->get(route('patrimonios.index'))->assertStatus(403);
    $this->actingAs($superadmin)->get(route('instalacoes.index'))->assertStatus(403);
});

test('super-usuario e redirecionado para listagem de administradores ao efetuar login', function () {
    $superadmin = User::factory()->create([
        'email' => 'superadmin@openair.com',
        'password' => bcrypt('password123'),
        'nivel' => 'superadmin',
    ]);

    $response = $this->post(route('login'), [
        'email' => 'superadmin@openair.com',
        'password' => 'password123',
    ]);

    $response->assertRedirect(route('usuarios.index'));
});
