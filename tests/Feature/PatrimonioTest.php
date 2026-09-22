<?php

use App\Models\Bairro;
use App\Models\Cidade;
use App\Models\Estacao;
use App\Models\Patrimonio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('usuario autenticado pode visualizar a listagem de patrimonios', function () {
    $user = User::factory()->create();
    Patrimonio::factory()->count(3)->create(['created_by' => $user->id]);

    $response = $this->actingAs($user)->get(route('patrimonios.index'));

    $response->assertOk();
    $response->assertViewIs('patrimonios.index');
    $response->assertViewHas('patrimonios');
});

test('usuario pode cadastrar um novo patrimonio individual com codigo gerado automaticamente e status sempre disponivel', function () {
    $cidade = Cidade::factory()->create();
    $user = User::factory()->create(['cidade_id' => $cidade->id]);

    $payload = [
        'cidade_id' => $cidade->id,
        'mac_address' => 'AA:BB:CC:DD:EE:11',
        'data_aquisicao' => '2026-09-01',
        'observacoes' => 'Lote de teste',
    ];

    $response = $this->actingAs($user)->post(route('patrimonios.store'), $payload);

    $response->assertRedirect(route('patrimonios.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('patrimonios', [
        'cidade_id' => $cidade->id,
        'mac_address' => 'AA:BB:CC:DD:EE:11',
        'numero_patrimonio' => "OAir-Estacao-{$cidade->id}-0001",
        'status' => 'Disponível',
    ]);
});

test('status inicial do patrimonio individual e sempre forcado para disponivel mesmo se enviado outro valor', function () {
    $cidade = Cidade::factory()->create();
    $user = User::factory()->create(['cidade_id' => $cidade->id]);

    $payload = [
        'cidade_id' => $cidade->id,
        'mac_address' => 'AA:BB:CC:DD:EE:99',
        'status' => 'Manutenção',
        'data_aquisicao' => '2026-09-01',
    ];

    $response = $this->actingAs($user)->post(route('patrimonios.store'), $payload);

    $response->assertRedirect(route('patrimonios.index'));

    $this->assertDatabaseHas('patrimonios', [
        'mac_address' => 'AA:BB:CC:DD:EE:99',
        'status' => 'Disponível',
    ]);
});

test('tela de cadastro individual nao renderiza campo de status inicial', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('patrimonios.create'));

    $response->assertOk();
    $response->assertDontSee('name="status"', false);
    $response->assertSee('Disponível');
});

test('valida formato e unicidade do mac address no cadastro de patrimonio', function () {
    $cidade = Cidade::factory()->create();
    $user = User::factory()->create(['cidade_id' => $cidade->id]);
    Patrimonio::factory()->create(['mac_address' => 'AA:BB:CC:DD:EE:22']);

    // MAC Inválido
    $responseInvalido = $this->actingAs($user)->post(route('patrimonios.store'), [
        'cidade_id' => $cidade->id,
        'mac_address' => 'INVALID_MAC',
    ]);
    $responseInvalido->assertSessionHasErrors(['mac_address']);

    // MAC Duplicado
    $responseDuplicado = $this->actingAs($user)->post(route('patrimonios.store'), [
        'cidade_id' => $cidade->id,
        'mac_address' => 'AA:BB:CC:DD:EE:22',
    ]);
    $responseDuplicado->assertSessionHasErrors(['mac_address']);
});

test('usuario pode cadastrar multiplos patrimonios em lote com codigos sequenciais', function () {
    $cidade = Cidade::factory()->create();
    $user = User::factory()->create(['cidade_id' => $cidade->id]);
    Patrimonio::factory()->create(['mac_address' => '11:22:33:44:55:66']);

    $batchText = "AA:11:22:33:44:55\nBB-11-22-33-44-55\nCC1122334455\n11:22:33:44:55:66\nINVALID_LINE";

    $response = $this->actingAs($user)->post(route('patrimonios.store-batch'), [
        'cidade_id' => $cidade->id,
        'mac_addresses_batch' => $batchText,
        'data_aquisicao' => '2026-09-01',
    ]);

    $response->assertRedirect(route('patrimonios.index'));
    $response->assertSessionHas('success');

    // Verifica que os 3 válidos foram inseridos e formatados com códigos sequenciais OAir-Estacao-<IdCidade>-<Num>
    $this->assertDatabaseHas('patrimonios', [
        'cidade_id' => $cidade->id,
        'mac_address' => 'AA:11:22:33:44:55',
        'numero_patrimonio' => "OAir-Estacao-{$cidade->id}-0001",
    ]);
    $this->assertDatabaseHas('patrimonios', [
        'cidade_id' => $cidade->id,
        'mac_address' => 'BB:11:22:33:44:55',
        'numero_patrimonio' => "OAir-Estacao-{$cidade->id}-0002",
    ]);
    $this->assertDatabaseHas('patrimonios', [
        'cidade_id' => $cidade->id,
        'mac_address' => 'CC:11:22:33:44:55',
        'numero_patrimonio' => "OAir-Estacao-{$cidade->id}-0003",
    ]);
});

test('ao excluir patrimonio status e alterado para descartado e registro e mantido no banco', function () {
    $user = User::factory()->create(['nivel' => 'planejador']);
    $patrimonio = Patrimonio::factory()->create(['cidade_id' => $user->cidade_id, 'created_by' => $user->id]);

    $response = $this->actingAs($user)->delete(route('patrimonios.destroy', $patrimonio->private_id));

    $response->assertRedirect(route('patrimonios.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('patrimonios', [
        'private_id' => $patrimonio->private_id,
        'status' => 'Descartado',
    ]);
});

test('endpoint de patrimonios disponiveis retorna apenas status disponivel', function () {
    $cidade = Cidade::factory()->create();
    $user = User::factory()->create(['cidade_id' => $cidade->id]);
    Patrimonio::factory()->create(['cidade_id' => $cidade->id, 'status' => 'Disponível', 'mac_address' => '00:11:22:33:44:55']);
    Patrimonio::factory()->create(['cidade_id' => $cidade->id, 'status' => 'Instalada', 'mac_address' => '00:11:22:33:44:66']);

    $response = $this->actingAs($user)->getJson(route('patrimonios.disponiveis'));

    $response->assertOk();
    $data = $response->json();

    expect(count($data))->toBe(1);
    expect($data[0]['mac_address'])->toBe('00:11:22:33:44:55');
});

test('listagem de patrimonios permite busca, filtros e ordenacao', function () {
    $user = User::factory()->create();

    Patrimonio::factory()->create([
        'mac_address' => 'AA:BB:CC:11:22:33',
        'numero_patrimonio' => 'PAT-001',
        'status' => 'Disponível',
        'created_by' => $user->id,
    ]);

    Patrimonio::factory()->create([
        'mac_address' => 'DD:EE:FF:44:55:66',
        'numero_patrimonio' => 'PAT-002',
        'status' => 'Descartado',
        'created_by' => $user->id,
    ]);

    // Busca por MAC
    $responseBusca = $this->actingAs($user)->get(route('patrimonios.index', ['busca' => 'AA:BB:CC']));
    $responseBusca->assertOk();
    $responseBusca->assertSee('PAT-001');
    $responseBusca->assertDontSee('PAT-002');

    // Filtro por status
    $responseStatus = $this->actingAs($user)->get(route('patrimonios.index', ['status' => 'Descartado']));
    $responseStatus->assertOk();
    $responseStatus->assertSee('PAT-002');
    $responseStatus->assertDontSee('PAT-001');

    // Ordenação
    $responseSort = $this->actingAs($user)->get(route('patrimonios.index', ['sort' => 'numero_patrimonio', 'direction' => 'desc']));
    $responseSort->assertOk();
    $responseSort->assertSeeInOrder(['PAT-002', 'PAT-001']);
});

test('patrimonio muda status para instalada e exibe instalada na listagem quando ativado na ordem de instalacao', function () {
    $instalador = User::factory()->create(['nivel' => 'instalador']);
    $patrimonio = Patrimonio::factory()->create([
        'mac_address' => 'AA:BB:CC:99:88:77',
        'numero_patrimonio' => 'OAir-Estacao-1-0001',
        'status' => 'Disponível',
    ]);

    $bairro = Bairro::factory()->create();
    $estacao = Estacao::factory()->create([
        'tipo_estacao' => 'Estação Matriz',
        'status_instalacao' => 'Planejada',
        'bairro_id' => $bairro->id,
        'ordem_instalacao' => 1,
    ]);

    // Executa a vinculação na Ordem de Instalação
    $response = $this->actingAs($instalador)->postJson(route('instalacoes.vincular-mac', $estacao->public_id), [
        'patrimonio_id' => $patrimonio->public_id,
    ]);

    $response->assertOk();
    $response->assertJson(['success' => true]);

    $patrimonio->refresh();
    expect($patrimonio->status)->toBe('Instalada');

    // Verifica que a listagem de patrimônio renderiza a badge "Instalada"
    $responseIndex = $this->actingAs($instalador)->get(route('patrimonios.index'));
    $responseIndex->assertOk();
    $responseIndex->assertSee('Instalada');
});

test('listagem de patrimonios exibe apenas equipamentos da jurisdicao municipal do usuario', function () {
    $cidade1 = Cidade::factory()->create(['nome' => 'Campinas']);
    $cidade2 = Cidade::factory()->create(['nome' => 'Ribeirão Preto']);

    $user1 = User::factory()->create(['cidade_id' => $cidade1->id]);

    $patrimonioCidade1 = Patrimonio::factory()->create([
        'cidade_id' => $cidade1->id,
        'mac_address' => 'AA:BB:CC:11:11:11',
        'numero_patrimonio' => 'PAT-CAMPINAS-01',
    ]);

    $patrimonioCidade2 = Patrimonio::factory()->create([
        'cidade_id' => $cidade2->id,
        'mac_address' => 'AA:BB:CC:22:22:22',
        'numero_patrimonio' => 'PAT-RIBEIRAO-01',
    ]);

    $response = $this->actingAs($user1)->get(route('patrimonios.index'));

    $response->assertOk();
    $response->assertSee('PAT-CAMPINAS-01');
    $response->assertDontSee('PAT-RIBEIRAO-01');
});

test('cadastro individual bloqueia tentativa de cadastrar para outro municipio com 403', function () {
    $cidade1 = Cidade::factory()->create(['nome' => 'Campinas']);
    $cidade2 = Cidade::factory()->create(['nome' => 'Santos']);

    $user = User::factory()->create(['cidade_id' => $cidade1->id]);

    $payload = [
        'cidade_id' => $cidade2->id,
        'mac_address' => 'AA:BB:CC:33:33:33',
        'data_aquisicao' => '2026-09-01',
    ];

    $response = $this->actingAs($user)->post(route('patrimonios.store'), $payload);

    $response->assertForbidden();
    $this->assertDatabaseMissing('patrimonios', [
        'mac_address' => 'AA:BB:CC:33:33:33',
    ]);
});

test('cadastro individual com jurisdicao fixa renderiza badge e desabilita seletor de municipio', function () {
    $cidade = Cidade::factory()->create(['nome' => 'Campinas']);
    $user = User::factory()->create(['cidade_id' => $cidade->id]);

    $response = $this->actingAs($user)->get(route('patrimonios.create'));

    $response->assertOk();
    $response->assertSee('Jurisdição Municipal:');
    $response->assertSee('Campinas');
    $response->assertSee('disabled', false);
    $response->assertSee('name="cidade_id"', false);
});

test('cadastro em lote bloqueia tentativa de cadastrar para outro municipio com 403', function () {
    $cidade1 = Cidade::factory()->create(['nome' => 'Campinas']);
    $cidade2 = Cidade::factory()->create(['nome' => 'Santos']);

    $user = User::factory()->create(['cidade_id' => $cidade1->id]);

    $response = $this->actingAs($user)->post(route('patrimonios.store-batch'), [
        'cidade_id' => $cidade2->id,
        'mac_addresses_batch' => "AA:BB:CC:44:44:44\nAA:BB:CC:55:55:55",
        'data_aquisicao' => '2026-09-01',
    ]);

    $response->assertForbidden();
    $this->assertDatabaseMissing('patrimonios', [
        'mac_address' => 'AA:BB:CC:44:44:44',
    ]);
});

test('exclusao de patrimonio bloqueia tentativa de excluir equipamento de outro municipio com 403', function () {
    $cidade1 = Cidade::factory()->create(['nome' => 'Campinas']);
    $cidade2 = Cidade::factory()->create(['nome' => 'Santos']);

    $user = User::factory()->create(['cidade_id' => $cidade1->id]);
    $patrimonioOutraCidade = Patrimonio::factory()->create([
        'cidade_id' => $cidade2->id,
        'mac_address' => 'AA:BB:CC:66:66:66',
    ]);

    $response = $this->actingAs($user)->delete(route('patrimonios.destroy', $patrimonioOutraCidade->private_id));

    $response->assertForbidden();
    $this->assertDatabaseHas('patrimonios', [
        'private_id' => $patrimonioOutraCidade->private_id,
    ]);
});

test('api de patrimonios disponiveis filtra por jurisdicao do usuario', function () {
    $cidade1 = Cidade::factory()->create();
    $cidade2 = Cidade::factory()->create();

    $user = User::factory()->create(['cidade_id' => $cidade1->id]);

    $p1 = Patrimonio::factory()->create([
        'cidade_id' => $cidade1->id,
        'status' => 'Disponível',
        'mac_address' => 'AA:BB:CC:77:77:77',
    ]);

    $p2 = Patrimonio::factory()->create([
        'cidade_id' => $cidade2->id,
        'status' => 'Disponível',
        'mac_address' => 'AA:BB:CC:88:88:88',
    ]);

    $response = $this->actingAs($user)->getJson(route('patrimonios.disponiveis'));

    $response->assertOk();
    $data = $response->json();
    expect(count($data))->toBe(1);
    expect($data[0]['mac_address'])->toBe('AA:BB:CC:77:77:77');
});

test('api de patrimonios disponiveis retorna array vazio para usuario autenticado sem municipio vinculado', function () {
    $cidade = Cidade::factory()->create();
    $userSemCidade = User::factory()->create(['cidade_id' => null, 'nivel' => 'cadastrador']);

    Patrimonio::factory()->create([
        'cidade_id' => $cidade->id,
        'status' => 'Disponível',
        'mac_address' => 'AA:BB:CC:99:99:99',
    ]);

    $response = $this->actingAs($userSemCidade)->getJson(route('patrimonios.disponiveis'));

    $response->assertOk();
    $data = $response->json();
    expect($data)->toBeArray()->toBeEmpty();
});
