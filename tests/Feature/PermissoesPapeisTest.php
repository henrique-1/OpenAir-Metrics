<?php

use App\Models\Bairro;
use App\Models\Cidade;
use App\Models\Estacao;
use App\Models\Patrimonio;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Testes de Permissões: Administrador Municipal
|--------------------------------------------------------------------------
*/

test('administrador pode gerenciar usuarios municipais e cadastrar sucessor desativando a propria conta', function () {
    $cidade = Cidade::factory()->create();
    $admin = User::factory()->create(['nivel' => 'administrador', 'cidade_id' => $cidade->id]);

    // Acessa listagem de usuários municipais
    $this->actingAs($admin)->get(route('usuarios.index'))->assertOk();

    // Acessa formulário de criação de usuários municipais
    $this->actingAs($admin)->get(route('usuarios.create'))->assertOk();

    // Pode cadastrar Planejador Técnico (cadastrador)
    $responseCadastrador = $this->actingAs($admin)->post(route('usuarios.store'), [
        'name' => 'Planejador Municipal',
        'email' => 'planejador@prefeitura.sp.gov.br',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'nivel' => 'cadastrador',
    ]);
    $responseCadastrador->assertRedirect(route('usuarios.index'));
    $this->assertDatabaseHas('users', ['email' => 'planejador@prefeitura.sp.gov.br', 'nivel' => 'cadastrador']);

    // Pode cadastrar Instalador
    $responseInstalador = $this->actingAs($admin)->post(route('usuarios.store'), [
        'name' => 'Instalador Municipal',
        'email' => 'instalador@prefeitura.sp.gov.br',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'nivel' => 'instalador',
    ]);
    $responseInstalador->assertRedirect(route('usuarios.index'));
    $this->assertDatabaseHas('users', ['email' => 'instalador@prefeitura.sp.gov.br', 'nivel' => 'instalador']);

    // Pode cadastrar sucessor Administrador (implica em logout e desativação do admin atual)
    $responseAdmin = $this->actingAs($admin)->post(route('usuarios.store'), [
        'name' => 'Sucessor Admin',
        'email' => 'sucessor.admin@prefeitura.sp.gov.br',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'nivel' => 'administrador',
    ]);
    $responseAdmin->assertRedirect(route('login'));
    $responseAdmin->assertSessionHas('info');
    $this->assertDatabaseHas('users', [
        'email' => 'sucessor.admin@prefeitura.sp.gov.br',
        'nivel' => 'administrador',
        'cidade_id' => $cidade->id,
        'ativo' => true,
    ]);

    $admin->refresh();
    expect($admin->ativo)->toBeFalse();
    $this->assertGuest();
});

test('administrador pode acessar dashboard operacional e estacoes do seu municipio', function () {
    $cidade = Cidade::factory()->create();
    $admin = User::factory()->create(['nivel' => 'administrador', 'cidade_id' => $cidade->id]);

    $this->actingAs($admin)->get(route('dashboard'))->assertOk();
    $this->actingAs($admin)->get(route('estacoes.index'))->assertOk();
});

/*
|--------------------------------------------------------------------------
| Testes de Permissões: Planejador Técnico
|--------------------------------------------------------------------------
*/

test('planejador tecnico pode cadastrar patrimonio e marca-lo como descartado', function () {
    $cidade = Cidade::factory()->create();
    $planejador = User::factory()->create(['nivel' => 'cadastrador', 'cidade_id' => $cidade->id]);

    // Acessa tela e cadastra patrimônio
    $this->actingAs($planejador)->get(route('patrimonios.create'))->assertOk();

    $responseStore = $this->actingAs($planejador)->post(route('patrimonios.store'), [
        'cidade_id' => $cidade->id,
        'mac_address' => '11:22:33:AA:BB:CC',
        'data_aquisicao' => '2026-09-01',
    ]);
    $responseStore->assertRedirect(route('patrimonios.index'));

    $patrimonio = Patrimonio::where('mac_address', '11:22:33:AA:BB:CC')->firstOrFail();
    expect($patrimonio->status)->toBe('Disponível');

    // Marca o patrimônio como Descartado (não exclui do BD)
    $responseDelete = $this->actingAs($planejador)->delete(route('patrimonios.destroy', $patrimonio->private_id));
    $responseDelete->assertRedirect(route('patrimonios.index'));

    $patrimonio->refresh();
    expect($patrimonio->status)->toBe('Descartado');
    $this->assertDatabaseHas('patrimonios', ['private_id' => $patrimonio->private_id, 'status' => 'Descartado']);
});

test('planejador tecnico pode acessar planejamento de malhas', function () {
    $cidade = Cidade::factory()->create();
    $planejador = User::factory()->create(['nivel' => 'cadastrador', 'cidade_id' => $cidade->id]);

    $this->actingAs($planejador)->get(route('estacoes.planejar'))->assertOk();
});

test('planejador tecnico pode solicitar substituicao de sensores', function () {
    $cidade = Cidade::factory()->create();
    $bairro = Bairro::factory()->create(['cidade_id' => $cidade->id]);
    $planejador = User::factory()->create(['nivel' => 'cadastrador', 'cidade_id' => $cidade->id]);

    $estacao = Estacao::factory()->create([
        'status_instalacao' => 'Instalada',
        'data_instalacao' => Carbon::now()->subYears(5),
        'bairro_id' => $bairro->id,
        'solicitacao_substituicao' => false,
    ]);

    $response = $this->actingAs($planejador)->post(route('estacoes.solicitar-substituicao', $estacao->public_id), [
        'motivo_substituicao' => 'Sensores no fim da vida útil.',
    ]);
    $response->assertRedirect(route('estacoes.index'));

    $estacao->refresh();
    expect($estacao->solicitacao_substituicao)->toBeTrue();
});

test('planejador tecnico nao pode gerenciar usuarios retornando 403', function () {
    $cidade = Cidade::factory()->create();
    $planejador = User::factory()->create(['nivel' => 'cadastrador', 'cidade_id' => $cidade->id]);

    $this->actingAs($planejador)->get(route('usuarios.index'))->assertStatus(403);
    $this->actingAs($planejador)->get(route('usuarios.create'))->assertStatus(403);
    $this->actingAs($planejador)->post(route('usuarios.store'), [])->assertStatus(403);
});

/*
|--------------------------------------------------------------------------
| Testes de Permissões: Instalador
|--------------------------------------------------------------------------
*/

test('instalador acessa roteiro de instalacao e vincula mac/patrimonio', function () {
    $cidade = Cidade::factory()->create();
    $bairro = Bairro::factory()->create(['cidade_id' => $cidade->id]);
    $instalador = User::factory()->create(['nivel' => 'instalador', 'cidade_id' => $cidade->id]);

    $patrimonio = Patrimonio::factory()->create([
        'cidade_id' => $cidade->id,
        'status' => 'Disponível',
        'mac_address' => 'AA:BB:CC:11:22:33',
    ]);

    $estacao = Estacao::factory()->create([
        'bairro_id' => $bairro->id,
        'status_instalacao' => 'Planejada',
    ]);

    // Acessa listagem de roteiros e detalhes da estação
    $this->actingAs($instalador)->get(route('instalacoes.index'))->assertOk();
    $this->actingAs($instalador)->get(route('instalacoes.show', $estacao->public_id))->assertOk();

    // Vincula o equipamento
    $response = $this->actingAs($instalador)->postJson(route('instalacoes.vincular-mac', $estacao->public_id), [
        'patrimonio_id' => $patrimonio->public_id,
    ]);

    $response->assertOk();
    $patrimonio->refresh();
    expect($patrimonio->status)->toBe('Instalada');
});

test('instalador nao pode cadastrar patrimonio fisico retornando 403', function () {
    $cidade = Cidade::factory()->create();
    $instalador = User::factory()->create(['nivel' => 'instalador', 'cidade_id' => $cidade->id]);

    $this->actingAs($instalador)->get(route('patrimonios.create'))->assertStatus(403);
    $this->actingAs($instalador)->post(route('patrimonios.store'), [
        'cidade_id' => $cidade->id,
        'mac_address' => 'AA:BB:CC:DD:EE:FF',
    ])->assertStatus(403);
});

test('instalador nao pode planejar malhas nem gerenciar usuarios retornando 403', function () {
    $cidade = Cidade::factory()->create();
    $instalador = User::factory()->create(['nivel' => 'instalador', 'cidade_id' => $cidade->id]);

    $this->actingAs($instalador)->get(route('estacoes.planejar'))->assertStatus(403);
    $this->actingAs($instalador)->get(route('usuarios.index'))->assertStatus(403);
});

test('instalador nao visualiza botoes de planejar malha cadastrar estacao ou cadastrar equipamento', function () {
    $cidade = Cidade::factory()->create();
    $bairro = Bairro::factory()->create(['cidade_id' => $cidade->id]);
    $instalador = User::factory()->create(['nivel' => 'instalador', 'cidade_id' => $cidade->id]);

    $patrimonio = Patrimonio::factory()->create([
        'cidade_id' => $cidade->id,
        'status' => 'Disponível',
    ]);

    // Na listagem de ordens de instalação
    $resInstalacoes = $this->actingAs($instalador)->get(route('instalacoes.index'));
    $resInstalacoes->assertOk();
    $resInstalacoes->assertDontSee('Planejar Nova Malha');

    // Na listagem de estações
    $resEstacoes = $this->actingAs($instalador)->get(route('estacoes.index'));
    $resEstacoes->assertOk();
    $resEstacoes->assertDontSee('Planejar Malha (Automático)');
    $resEstacoes->assertDontSee('Cadastrar Nova Estação');

    // Na listagem de patrimônios
    $resPatrimonios = $this->actingAs($instalador)->get(route('patrimonios.index'));
    $resPatrimonios->assertOk();
    $resPatrimonios->assertDontSee('Cadastrar Equipamentos');
    $resPatrimonios->assertDontSee('title="Marcar como Descartado"', false);
});

test('ordem de instalacao para substituicao de estacao e executada pelo instalador descartando patrimonio anterior', function () {
    $cidade = Cidade::factory()->create();
    $bairro = Bairro::factory()->create(['cidade_id' => $cidade->id]);
    $instalador = User::factory()->create(['nivel' => 'instalador', 'cidade_id' => $cidade->id]);

    $patrimonioAntigo = Patrimonio::factory()->create([
        'cidade_id' => $cidade->id,
        'status' => 'Instalada',
        'mac_address' => 'AA:11:22:33:44:55',
        'numero_patrimonio' => 'PAT-VELHO',
    ]);

    $patrimonioNovo = Patrimonio::factory()->create([
        'cidade_id' => $cidade->id,
        'status' => 'Disponível',
        'mac_address' => 'BB:11:22:33:44:55',
        'numero_patrimonio' => 'PAT-NOVO',
    ]);

    $estacao = Estacao::factory()->create([
        'tipo_estacao' => 'Estação Matriz',
        'bairro_id' => $bairro->id,
        'patrimonio_id' => $patrimonioAntigo->private_id,
        'mac_address' => $patrimonioAntigo->mac_address,
        'status_instalacao' => 'Instalada',
        'solicitacao_substituicao' => true,
        'motivo_substituicao' => 'Defeito em sensor de umidade',
    ]);

    // Instalador visualiza o roteiro da malha com alerta de substituição pendente
    $resShow = $this->actingAs($instalador)->get(route('instalacoes.show', $estacao->public_id));
    $resShow->assertOk();
    $resShow->assertSee('Ordem de Substituição Aberta');
    $resShow->assertSee('Ordem de Substituição de Sensor em Aberto');
    $resShow->assertSee('PAT-VELHO');

    // Executa a ordem de instalação / substituição
    $resVincular = $this->actingAs($instalador)->postJson(route('instalacoes.vincular-mac', $estacao->public_id), [
        'patrimonio_id' => $patrimonioNovo->public_id,
    ]);

    $resVincular->assertOk();
    $resVincular->assertJson(['success' => true]);

    $patrimonioAntigo->refresh();
    $patrimonioNovo->refresh();
    $estacao->refresh();

    // Patrimônio anterior torna-se imediatamente Descartado
    expect($patrimonioAntigo->status)->toBe('Descartado');
    // Novo equipamento assume status Instalada
    expect($patrimonioNovo->status)->toBe('Instalada');
    // Pendência de substituição finalizada
    expect($estacao->solicitacao_substituicao)->toBeFalse();
    expect($estacao->mac_address)->toBe($patrimonioNovo->mac_address);
    expect($estacao->patrimonio_id)->toBe($patrimonioNovo->private_id);
});

/*
|--------------------------------------------------------------------------
| Testes de Permissões: Super-usuário
|--------------------------------------------------------------------------
*/

test('super-usuario gerencia administradores e nao acessa modulos operacionais municipais', function () {
    $superadmin = User::factory()->create(['nivel' => 'superadmin', 'cidade_id' => null]);
    $cidade = Cidade::factory()->create();

    // Gerencia administradores
    $this->actingAs($superadmin)->get(route('usuarios.index'))->assertOk();
    $this->actingAs($superadmin)->get(route('usuarios.create'))->assertOk();

    // Não acessa telas operacionais
    $this->actingAs($superadmin)->get(route('dashboard'))->assertStatus(403);
    $this->actingAs($superadmin)->get(route('patrimonios.index'))->assertStatus(403);
    $this->actingAs($superadmin)->get(route('estacoes.index'))->assertStatus(403);
    $this->actingAs($superadmin)->get(route('instalacoes.index'))->assertStatus(403);
});
