<?php

use App\Models\Cidade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('visitante nao autenticado e redirecionado ao tentar acessar perfil', function () {
    $this->get(route('perfil.edit'))->assertRedirect(route('login'));
    $this->put(route('perfil.update'), [])->assertRedirect(route('login'));
});

test('usuario autenticado pode visualizar seus dados e nivel na tela de perfil', function () {
    $cidade = Cidade::factory()->create(['nome' => 'São João da Boa Vista']);
    $user = User::factory()->create([
        'name' => 'Carlos Engenheiro',
        'email' => 'carlos@prefeitura.sp.gov.br',
        'nivel' => 'cadastrador',
        'cidade_id' => $cidade->id,
    ]);

    $response = $this->actingAs($user)->get(route('perfil.edit'));

    $response->assertOk();
    $response->assertSee('Carlos Engenheiro');
    $response->assertSee('carlos@prefeitura.sp.gov.br');
    $response->assertSee('Planejador Técnico');
    $response->assertSee('São João da Boa Vista');
});

test('usuario pode atualizar nome e endereco mas nao pode trocar o proprio email', function () {
    $user = User::factory()->create([
        'name' => 'Nome Antigo',
        'email' => 'antigo@prefeitura.gov.br',
    ]);

    $payload = [
        'name' => 'Nome Atualizado',
        'email' => 'tentativa_troca@prefeitura.gov.br',
        'logradouro' => 'Rua das Flores',
        'numero' => '123',
        'complemento' => 'Sala 4',
        'bairro' => 'Centro',
        'estado' => 'SP',
        'cep' => '13870-000',
    ];

    $response = $this->actingAs($user)->put(route('perfil.update'), $payload);

    $response->assertRedirect(route('perfil.edit'));
    $response->assertSessionHas('success');

    $user->refresh();
    expect($user->name)->toBe('Nome Atualizado');
    // O e-mail permanece estritamente o original
    expect($user->email)->toBe('antigo@prefeitura.gov.br');
    expect($user->logradouro)->toBe('Rua das Flores');
    expect($user->numero)->toBe('123');
    expect($user->cep)->toBe('13870-000');
});

test('campo de email e desabilitado na tela de edicao de perfil', function () {
    $user = User::factory()->create(['email' => 'servidor@municipio.gov.br']);

    $response = $this->actingAs($user)->get(route('perfil.edit'));

    $response->assertOk();
    $response->assertSee('disabled', false);
    $response->assertSee('servidor@municipio.gov.br');
    $response->assertDontSee('name="email_confirmation"', false);
});

test('formulario de edicao de perfil inclui campos de endereco e script de consulta ao viacep', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('perfil.edit'));

    $response->assertOk();
    $response->assertSee('id="cep"', false);
    $response->assertSee('id="logradouro"', false);
    $response->assertSee('id="numero"', false);
    $response->assertSee('id="bairro"', false);
    $response->assertSee('viacep.com.br/ws/', false);
});
