<?php

use App\Models\Bairro;
use App\Models\Cidade;
use App\Models\Estacao;
use App\Models\Estado;
use App\Models\MalhaViaria;
use App\Models\Patrimonio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('calculo de malha viaria retorna matriz e satelites a no maximo 200 metros', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('api.estacoes.calcular-malha'), [
        'latitude' => -21.9847,
        'longitude' => -46.7947,
        'quantidade_satelites' => 3,
    ]);

    $response->assertOk();
    $data = $response->json();

    expect($data)->toHaveKeys(['matriz', 'satelites']);
    expect($data['matriz']['tipo_estacao'])->toBe('Estação Matriz');
    expect($data['matriz']['ordem_instalacao'])->toBe(1);
    expect(count($data['satelites']))->toBe(3);

    foreach ($data['satelites'] as $index => $sat) {
        expect($sat['tipo_estacao'])->toBe('Estação Satélite');
        expect($sat['ordem_instalacao'])->toBe($index + 2);
        expect($sat['distancia_origem_metros'])->toBeLessThanOrEqual(200.0);
    }
});

test('pode salvar uma malha planejada completa gerando ordens de instalacao', function () {
    $user = User::factory()->create();
    $estado = Estado::factory()->create(['uf' => 'SP']);
    $cidade = Cidade::factory()->create(['estado_id' => $estado->id, 'nome' => 'São João da Boa Vista']);

    $payload = [
        'cidade_id' => $cidade->id,
        'matriz' => [
            'latitude' => -21.9847,
            'longitude' => -46.7947,
            'logradouro' => 'Rua Central',
            'numero' => '100',
            'bairro_nome' => 'Centro',
            'endereco_completo' => 'Rua Central, 100, Centro',
        ],
        'satelites' => [
            [
                'latitude' => -21.9830,
                'longitude' => -46.7947,
                'logradouro' => 'Rua Norte',
                'numero' => '200',
                'bairro_nome' => 'Centro',
                'distancia_origem_metros' => 180.0,
                'origem_indice' => 0,
                'endereco_completo' => 'Rua Norte, 200, Centro',
            ],
            [
                'latitude' => -21.9864,
                'longitude' => -46.7947,
                'logradouro' => 'Rua Sul',
                'numero' => '300',
                'bairro_nome' => 'Centro',
                'distancia_origem_metros' => 185.0,
                'origem_indice' => 0,
                'endereco_completo' => 'Rua Sul, 300, Centro',
            ],
        ],
    ];

    $response = $this->actingAs($user)->post(route('estacoes.salvar-malha'), $payload);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    // Verifica que 3 estações foram criadas com status 'Planejada'
    expect(Estacao::count())->toBe(3);

    $matriz = Estacao::where('tipo_estacao', 'Estação Matriz')->first();
    expect($matriz->status_instalacao)->toBe('Planejada');
    expect($matriz->ordem_instalacao)->toBe(1);
    expect($matriz->mac_address)->toBeNull();

    $satelites = Estacao::where('tipo_estacao', 'Estação Satélite')->orderBy('ordem_instalacao')->get();
    expect($satelites->count())->toBe(2);
    expect($satelites[0]->ordem_instalacao)->toBe(2);
    expect($satelites[0]->matriz_pai_id)->toBe($matriz->private_id);
    expect($satelites[1]->ordem_instalacao)->toBe(3);
});

test('pode salvar malha planejada com strings json e hierarquia em arvore de intermediarias', function () {
    $user = User::factory()->create();
    $cidade = Cidade::factory()->create();

    $payload = [
        'cidade_id' => $cidade->id,
        'matriz' => json_encode([
            'latitude' => -21.9847,
            'longitude' => -46.7947,
            'logradouro' => 'Praça Central',
            'numero' => '1',
            'bairro_nome' => 'Centro',
            'endereco_completo' => 'Praça Central, 1, Centro',
        ]),
        'satelites' => json_encode([
            [
                'latitude' => -21.9830,
                'longitude' => -46.7947,
                'logradouro' => 'Rua Norte',
                'numero' => '200',
                'bairro_nome' => 'Centro',
                'distancia_origem_metros' => 180.0,
                'origem_indice' => 0, // Conecta na Matriz (A -> M)
                'endereco_completo' => 'Rua Norte, 200, Centro',
            ],
            [
                'latitude' => -21.9815,
                'longitude' => -46.7947,
                'logradouro' => 'Rua Mais ao Norte',
                'numero' => '500',
                'bairro_nome' => 'Centro',
                'distancia_origem_metros' => 165.0,
                'origem_indice' => 1, // Conecta na Satélite #2 (M -> B)
                'endereco_completo' => 'Rua Mais ao Norte, 500, Centro',
            ],
        ]),
    ];

    $response = $this->actingAs($user)->post(route('estacoes.salvar-malha'), $payload);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $matriz = Estacao::where('tipo_estacao', 'Estação Matriz')->first();
    $satelites = Estacao::where('tipo_estacao', 'Estação Satélite')->orderBy('ordem_instalacao')->get();

    expect($satelites->count())->toBe(2);
    // Satélite 1 (#2) conecta na Matriz (#1)
    expect($satelites[0]->estacao_origem_id)->toBe($matriz->private_id);
    // Satélite 2 (#3) conecta na Satélite 1 (#2)
    expect($satelites[1]->estacao_origem_id)->toBe($satelites[0]->private_id);
});

test('instalador pode vincular mac address e ativar estacao em campo', function () {
    $user = User::factory()->create();
    $bairro = Bairro::factory()->create();

    $patrimonio = Patrimonio::factory()->create([
        'mac_address' => 'AA:BB:CC:11:22:33',
        'status' => 'Disponível',
    ]);

    $matriz = Estacao::factory()->create([
        'tipo_estacao' => 'Estação Matriz',
        'status_instalacao' => 'Planejada',
        'ordem_instalacao' => 1,
        'mac_address' => null,
        'bairro_id' => $bairro->id,
        'latitude' => -21.9847,
        'longitude' => -46.7947,
    ]);

    $response = $this->actingAs($user)->postJson(route('api.instalacoes.vincular-mac', $matriz->public_id), [
        'mac_address' => 'AA:BB:CC:11:22:33',
    ]);

    $response->assertOk();
    $response->assertJson(['success' => true]);

    $matriz->refresh();
    $patrimonio->refresh();

    expect($matriz->status_instalacao)->toBe('Instalada');
    expect($matriz->mac_address)->toBe('AA:BB:CC:11:22:33');
    expect($matriz->patrimonio_id)->toBe($patrimonio->private_id);
    expect($patrimonio->status)->toBe('Instalado');
});

test('bloqueia ativacao de satelite se a estacao anterior nao estiver instalada', function () {
    $user = User::factory()->create();
    $bairro = Bairro::factory()->create();

    $matriz = Estacao::factory()->create([
        'tipo_estacao' => 'Estação Matriz',
        'status_instalacao' => 'Planejada', // Ainda NÃO instalada
        'ordem_instalacao' => 1,
        'mac_address' => null,
        'bairro_id' => $bairro->id,
        'latitude' => -21.9847,
        'longitude' => -46.7947,
    ]);

    $satelite = Estacao::factory()->create([
        'tipo_estacao' => 'Estação Satélite',
        'status_instalacao' => 'Planejada',
        'ordem_instalacao' => 2,
        'matriz_pai_id' => $matriz->private_id,
        'estacao_origem_id' => $matriz->private_id,
        'mac_address' => null,
        'bairro_id' => $bairro->id,
        'latitude' => -21.9830,
        'longitude' => -46.7947,
    ]);

    // Tentativa de instalar o satélite antes da matriz
    $response = $this->actingAs($user)->postJson(route('api.instalacoes.vincular-mac', $satelite->public_id), [
        'mac_address' => 'AA:BB:CC:99:88:77',
    ]);

    $response->assertStatus(422);
    $response->assertJson(['success' => false]);

    $satelite->refresh();
    expect($satelite->status_instalacao)->toBe('Planejada');
    expect($satelite->mac_address)->toBeNull();
});

test('api snap to road retorna coordenadas ajustadas para a via publica mais proxima', function () {
    $isSqlite = DB::getDriverName() === 'sqlite';
    $wkt = 'LINESTRING(-46.79470 -21.98400, -46.79470 -21.98600)';

    MalhaViaria::create([
        'logradouro' => 'Rua do Teste de Snap',
        'tipo_via' => 'residential',
        'geometria' => $isSqlite ? $wkt : DB::raw("ST_GeomFromText('{$wkt}', 4326)"),
    ]);

    $response = $this->postJson(route('api.estacoes.snap-to-road'), [
        'latitude' => -21.98450,
        'longitude' => -46.79450, // Ponto afastado da rua
        'origem_latitude' => -21.98400,
        'origem_longitude' => -46.79470,
        'max_distancia' => 200.0,
    ]);

    $response->assertOk();
    $response->assertJson([
        'snapped' => true,
        'nome_rua' => 'Rua do Teste de Snap',
    ]);
    expect($response->json('latitude'))->toEqualWithDelta(-21.98450, 0.0002);
    expect($response->json('longitude'))->toEqualWithDelta(-46.79470, 0.0002);
});

test('api snap to road retorna 422 quando nao ha via publica dentro do alcance maximo', function () {
    $response = $this->postJson(route('api.estacoes.snap-to-road'), [
        'latitude' => 0.0,
        'longitude' => 0.0,
        'origem_latitude' => 0.005,
        'origem_longitude' => 0.005,
        'max_distancia' => 200.0,
    ]);

    $response->assertStatus(422);
    $response->assertJson([
        'snapped' => false,
    ]);
});

test('tela de planejamento de malha carrega com sucesso e exibe instrucoes e elementos de remocao de matriz', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('estacoes.planejar'));

    $response->assertOk();
    $response->assertSee('Planejador de Malha de Sensores');
    $response->assertSee('Matriz (Fixa / Arrastável)');
    $response->assertSee('btn-remover-matriz-card', false);
    $response->assertSee('btn-remover-matriz-topbar', false);
});

test('instalador pode vincular estacao por numero de patrimonio ou selecao de inventario', function () {
    $user = User::factory()->create();
    $bairro = Bairro::factory()->create();

    $patrimonio = Patrimonio::factory()->create([
        'numero_patrimonio' => 'PAT-8899',
        'mac_address' => 'AA:BB:CC:DD:EE:FF',
        'status' => 'Disponível',
    ]);

    $matriz = Estacao::factory()->create([
        'tipo_estacao' => 'Estação Matriz',
        'status_instalacao' => 'Planejada',
        'ordem_instalacao' => 1,
        'mac_address' => null,
        'bairro_id' => $bairro->id,
        'latitude' => -21.9847,
        'longitude' => -46.7947,
    ]);

    $response = $this->actingAs($user)->postJson(route('api.instalacoes.vincular-mac', $matriz->public_id), [
        'numero_patrimonio' => 'PAT-8899',
    ]);

    $response->assertOk();
    $response->assertJson(['success' => true]);

    $matriz->refresh();
    $patrimonio->refresh();

    expect($matriz->status_instalacao)->toBe('Instalada');
    expect($matriz->patrimonio_id)->toBe($patrimonio->private_id);
    expect($matriz->mac_address)->toBe('AA:BB:CC:DD:EE:FF');
    expect($patrimonio->status)->toBe('Instalado');
});

test('tela de instalacoes/index exibe a contagem correta de todas as satelites da malha em arvore multihop', function () {
    $user = User::factory()->create();
    $bairro = Bairro::factory()->create();

    $matriz = Estacao::factory()->create([
        'tipo_estacao' => 'Estação Matriz',
        'status_instalacao' => 'Planejada',
        'ordem_instalacao' => 1,
        'bairro_id' => $bairro->id,
        'latitude' => -21.9847,
        'longitude' => -46.7947,
    ]);
    $matriz->update(['matriz_pai_id' => $matriz->private_id]);

    // Cria 7 satélites em cadeia (árvore multi-hop): Matriz -> S1 -> S2 -> S3 -> S4 -> S5 -> S6 -> S7
    $paiId = $matriz->private_id;
    for ($i = 2; $i <= 8; $i++) {
        $sat = Estacao::factory()->create([
            'tipo_estacao' => 'Estação Satélite',
            'status_instalacao' => 'Planejada',
            'ordem_instalacao' => $i,
            'matriz_pai_id' => $matriz->private_id,
            'estacao_origem_id' => $paiId,
            'bairro_id' => $bairro->id,
            'latitude' => -21.9847 + ($i * 0.001),
            'longitude' => -46.7947,
        ]);
        $paiId = $sat->private_id;
    }

    $response = $this->actingAs($user)->get(route('instalacoes.index'));

    $response->assertOk();
    // Deve exibir 8 estações (1 Matriz + 7 Satélites) e não apenas as ligadas diretamente à raiz
    $response->assertSee('8 estações (1 Matriz + 7 Satélites)');
});

test('show do roteiro de instalacao exibe link do google maps com coordenadas latitude e longitude', function () {
    $user = User::factory()->create();
    $bairro = Bairro::factory()->create();

    $matriz = Estacao::factory()->create([
        'tipo_estacao' => 'Estação Matriz',
        'status_instalacao' => 'Planejada',
        'ordem_instalacao' => 1,
        'bairro_id' => $bairro->id,
        'latitude' => -21.9847,
        'longitude' => -46.7947,
    ]);

    $response = $this->actingAs($user)->get(route('instalacoes.show', $matriz->public_id));

    $response->assertOk();
    $response->assertDontSee('query=,');
    $response->assertSee('-21.9847,-46.7947');
});
