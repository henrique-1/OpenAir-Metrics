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
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('calculo de malha viaria retorna matriz e satelites a no maximo 200 metros', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('estacoes.calcular-malha'), [
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
    $user = User::factory()->create(['nivel' => 'instalador']);
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

    $response = $this->actingAs($user)->postJson(route('instalacoes.vincular-mac', $matriz->public_id), [
        'mac_address' => 'AA:BB:CC:11:22:33',
    ]);

    $response->assertOk();
    $response->assertJson(['success' => true]);

    $matriz->refresh();
    $patrimonio->refresh();

    expect($matriz->status_instalacao)->toBe('Instalada');
    expect($matriz->mac_address)->toBe('AA:BB:CC:11:22:33');
    expect($matriz->patrimonio_id)->toBe($patrimonio->private_id);
    expect($patrimonio->status)->toBe('Instalada');
});

test('bloqueia ativacao de satelite se a estacao anterior nao estiver instalada', function () {
    $user = User::factory()->create(['nivel' => 'instalador']);
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
    $response = $this->actingAs($user)->postJson(route('instalacoes.vincular-mac', $satelite->public_id), [
        'mac_address' => 'AA:BB:CC:99:88:77',
    ]);

    $response->assertStatus(422);
    $response->assertJson(['success' => false]);

    $satelite->refresh();
    expect($satelite->status_instalacao)->toBe('Planejada');
    expect($satelite->mac_address)->toBeNull();
});

test('api snap to road retorna coordenadas ajustadas para a via publica mais proxima', function () {
    $user = User::factory()->create();
    $isSqlite = DB::getDriverName() === 'sqlite';
    $wkt = 'LINESTRING(-46.79470 -21.98400, -46.79470 -21.98600)';

    MalhaViaria::create([
        'logradouro' => 'Rua do Teste de Snap',
        'tipo_via' => 'residential',
        'geometria' => $isSqlite ? $wkt : DB::raw("ST_GeomFromText('{$wkt}', 4326)"),
    ]);

    $response = $this->actingAs($user)->postJson(route('estacoes.snap-to-road'), [
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

test('api snap to road ajusta coordenada para o leito viario sem estacao de origem para estacao matriz', function () {
    $user = User::factory()->create();

    $isSqlite = DB::connection()->getDriverName() === 'sqlite';
    $wkt = 'LINESTRING(-46.79470 -21.98400, -46.79470 -21.98500)';

    MalhaViaria::create([
        'logradouro' => 'Avenida Principal Matriz',
        'tipo_via' => 'residential',
        'geometria' => $isSqlite ? $wkt : DB::raw("ST_GeomFromText('{$wkt}', 4326)"),
    ]);

    $response = $this->actingAs($user)->postJson(route('estacoes.snap-to-road'), [
        'latitude' => -21.98450,
        'longitude' => -46.79450, // Ponto afastado da rua
    ]);

    $response->assertOk();
    $response->assertJson([
        'snapped' => true,
        'nome_rua' => 'Avenida Principal Matriz',
    ]);
    expect($response->json('latitude'))->toEqualWithDelta(-21.98450, 0.0002);
    expect($response->json('longitude'))->toEqualWithDelta(-46.79470, 0.0002);
});

test('api snap to road retorna 422 quando nao ha via publica dentro do alcance maximo', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('estacoes.snap-to-road'), [
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
    $user = User::factory()->create(['nivel' => 'instalador']);
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

    $response = $this->actingAs($user)->postJson(route('instalacoes.vincular-mac', $matriz->public_id), [
        'numero_patrimonio' => 'PAT-8899',
    ]);

    $response->assertOk();
    $response->assertJson(['success' => true]);

    $matriz->refresh();
    $patrimonio->refresh();

    expect($matriz->status_instalacao)->toBe('Instalada');
    expect($matriz->patrimonio_id)->toBe($patrimonio->private_id);
    expect($matriz->mac_address)->toBe('AA:BB:CC:DD:EE:FF');
    expect($patrimonio->status)->toBe('Instalada');
});

test('somente instalador pode manipular o roteiro e vincular mac retornando 403 para outros papeis', function () {
    $cadastrador = User::factory()->create(['nivel' => 'cadastrador']);
    $matriz = Estacao::factory()->create([
        'tipo_estacao' => 'Estação Matriz',
        'status_instalacao' => 'Planejada',
    ]);

    $response = $this->actingAs($cadastrador)->postJson(route('instalacoes.vincular-mac', $matriz->public_id), [
        'mac_address' => 'AA:BB:CC:11:22:33',
    ]);

    $response->assertStatus(403);
    $response->assertJson(['success' => false]);
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

test('api de reverse geocoding consulta nominatim e cadastra bairro no banco de dados se nao existir', function () {
    $estado = Estado::factory()->create(['uf' => 'SP']);
    $cidade = Cidade::factory()->create(['estado_id' => $estado->id, 'nome' => 'São Paulo']);

    Http::fake([
        'https://nominatim.openstreetmap.org/*' => Http::response([
            'display_name' => 'Avenida Paulista, 1000, Bela Vista, São Paulo, SP, Brasil',
            'address' => [
                'road' => 'Avenida Paulista',
                'house_number' => '1000',
                'suburb' => 'Bela Vista',
                'city' => 'São Paulo',
                'ISO3166-2-lvl4' => 'BR-SP',
                'postcode' => '01310-100',
            ],
        ], 200),
    ]);

    $user = User::factory()->create();

    expect(Bairro::where('nome', 'Bela Vista')->where('cidade_id', $cidade->id)->exists())->toBeFalse();

    $response = $this->actingAs($user)->getJson(route('geocoding.reverse', [
        'lat' => -23.56168,
        'lng' => -46.65598,
        'cidade_id' => $cidade->id,
    ]));

    $response->assertOk();
    $response->assertJson([
        'bairro' => 'Bela Vista',
        'cidade' => 'São Paulo',
        'logradouro' => 'Avenida Paulista',
        'numero' => '1000',
    ]);

    // Verifica que o bairro foi cadastrado no banco de dados
    expect(Bairro::where('nome', 'Bela Vista')->where('cidade_id', $cidade->id)->exists())->toBeTrue();
    $bairroCriado = Bairro::where('nome', 'Bela Vista')->where('cidade_id', $cidade->id)->first();
    expect($response->json('bairro_id'))->toBe($bairroCriado->id);
});

test('calculo de malha viaria consulta nominatim e persiste bairro no banco para a matriz e satelites', function () {
    $user = User::factory()->create();
    $estado = Estado::factory()->create(['uf' => 'SP']);
    $cidade = Cidade::factory()->create(['estado_id' => $estado->id, 'nome' => 'Campinas']);

    Http::fake([
        'https://nominatim.openstreetmap.org/*' => Http::response([
            'display_name' => 'Rua Barão de Jaguara, 100, Cambuí, Campinas, SP, Brasil',
            'address' => [
                'road' => 'Rua Barão de Jaguara',
                'house_number' => '100',
                'suburb' => 'Cambuí',
                'city' => 'Campinas',
                'ISO3166-2-lvl4' => 'BR-SP',
                'postcode' => '13015-002',
            ],
        ], 200),
    ]);

    $response = $this->actingAs($user)->postJson(route('estacoes.calcular-malha'), [
        'latitude' => -22.9056,
        'longitude' => -47.0543,
        'quantidade_satelites' => 2,
        'cidade_id' => $cidade->id,
    ]);

    $response->assertOk();
    $data = $response->json();

    expect($data['matriz']['bairro_nome'])->toBe('Cambuí');
    expect(Bairro::where('nome', 'Cambuí')->where('cidade_id', $cidade->id)->exists())->toBeTrue();
});

test('tela de instalacao exibe corretamente estacao ja instalada com data de instalacao formatada', function () {
    $user = User::factory()->create(['name' => 'Técnico Instalador']);
    $bairro = Bairro::factory()->create();

    $matriz = Estacao::factory()->create([
        'tipo_estacao' => 'Estação Matriz',
        'status_instalacao' => 'Instalada',
        'data_instalacao' => '2026-09-07 09:04:31',
        'instalado_por' => $user->id,
        'ordem_instalacao' => 1,
        'mac_address' => 'AA:BB:CC:11:22:33',
        'bairro_id' => $bairro->id,
        'latitude' => -21.9847,
        'longitude' => -46.7947,
    ]);

    $response = $this->actingAs($user)->get(route('instalacoes.show', $matriz->public_id));

    $response->assertOk();
    $response->assertSee('Instalado em');
    $response->assertSee('07/09/2026');
    $response->assertSee('Técnico Instalador');
});

test('usuario com jurisdicao vinculada tem cidade forcada no calculo de malha', function () {
    $cidadeUsuario = Cidade::factory()->create(['nome' => 'Limeira']);
    $outraCidade = Cidade::factory()->create(['nome' => 'Piracicaba']);
    $user = User::factory()->create(['cidade_id' => $cidadeUsuario->id]);

    $response = $this->actingAs($user)->postJson(route('estacoes.calcular-malha'), [
        'latitude' => -22.5645,
        'longitude' => -47.4012,
        'quantidade_satelites' => 2,
        'cidade_id' => $outraCidade->id, // Tenta passar outra cidade
    ]);

    $response->assertOk();
    $data = $response->json();
    // A cidade retornada no cálculo deve ser a cidade do usuário
    expect($data['matriz']['cidade_nome'])->toBe('Limeira');
});

test('usuario com jurisdicao vinculada e bloqueado com 403 ao tentar salvar malha de outro municipio', function () {
    $cidadeUsuario = Cidade::factory()->create(['nome' => 'Limeira']);
    $outraCidade = Cidade::factory()->create(['nome' => 'Piracicaba']);
    $user = User::factory()->create(['cidade_id' => $cidadeUsuario->id]);

    $payload = [
        'cidade_id' => $outraCidade->id,
        'matriz' => [
            'latitude' => -22.7250,
            'longitude' => -47.6475,
            'logradouro' => 'Rua do Porto',
            'numero' => '10',
            'bairro_nome' => 'Centro',
        ],
        'satelites' => [
            [
                'latitude' => -22.7240,
                'longitude' => -47.6475,
                'logradouro' => 'Rua do Porto',
                'numero' => '20',
                'bairro_nome' => 'Centro',
                'distancia_origem_metros' => 110.0,
                'origem_indice' => 0,
            ],
        ],
    ];

    $response = $this->actingAs($user)->post(route('estacoes.salvar-malha'), $payload);
    $response->assertForbidden();
});

test('listagem de ordens de instalacao permite busca, filtros e ordenacao', function () {
    $user = User::factory()->create();
    $bairro1 = Bairro::factory()->create(['nome' => 'Bairro Copacabana']);
    $bairro2 = Bairro::factory()->create(['nome' => 'Bairro Ipanema']);

    $matriz1 = Estacao::factory()->create([
        'tipo_estacao' => 'Estação Matriz',
        'status_instalacao' => 'Instalada',
        'ordem_instalacao' => 1,
        'bairro_id' => $bairro1->id,
        'mac_address' => '11:22:33:44:55:66',
    ]);

    $matriz2 = Estacao::factory()->create([
        'tipo_estacao' => 'Estação Matriz',
        'status_instalacao' => 'Planejada',
        'ordem_instalacao' => 2,
        'bairro_id' => $bairro2->id,
        'mac_address' => null,
    ]);

    // Busca
    $responseBusca = $this->actingAs($user)->get(route('instalacoes.index', ['busca' => 'Ipanema']));
    $responseBusca->assertOk();
    $responseBusca->assertSee('Bairro Ipanema');
    $responseBusca->assertDontSee('Bairro Copacabana');

    // Filtro por status
    $responseStatus = $this->actingAs($user)->get(route('instalacoes.index', ['status' => 'Instalada']));
    $responseStatus->assertOk();
    $responseStatus->assertSee('Bairro Copacabana');
    $responseStatus->assertDontSee('Bairro Ipanema');

    // Texto de MAC Pendente
    $responsePendente = $this->actingAs($user)->get(route('instalacoes.index', ['status' => 'Pendente']));
    $responsePendente->assertOk();
    $responsePendente->assertSee('MAC: Pendente de Instalação');
});

test('listagem de ordens de instalacao filtra estritamente pela jurisdicao municipal do usuario', function () {
    $cidade1 = Cidade::factory()->create(['nome' => 'Campinas']);
    $cidade2 = Cidade::factory()->create(['nome' => 'Santos']);

    $bairro1 = Bairro::factory()->create(['cidade_id' => $cidade1->id]);
    $bairro2 = Bairro::factory()->create(['cidade_id' => $cidade2->id]);

    $userCampinas = User::factory()->create(['cidade_id' => $cidade1->id]);

    $matrizCampinas = Estacao::factory()->create([
        'tipo_estacao' => 'Estação Matriz',
        'bairro_id' => $bairro1->id,
        'mac_address' => 'AA:11:22:33:44:55',
        'bairro_nome' => 'Bairro Centro Campinas',
        'cidade_nome' => 'Campinas',
    ]);

    $matrizSantos = Estacao::factory()->create([
        'tipo_estacao' => 'Estação Matriz',
        'bairro_id' => $bairro2->id,
        'mac_address' => 'BB:11:22:33:44:55',
        'bairro_nome' => 'Orla Gonzaga Santos',
        'cidade_nome' => 'Santos',
    ]);

    $response = $this->actingAs($userCampinas)->get(route('instalacoes.index'));

    $response->assertOk();
    $response->assertSee('Bairro Centro Campinas');
    $response->assertSee('AA:11:22:33:44:55');
    $response->assertDontSee('Orla Gonzaga Santos');
    $response->assertDontSee('BB:11:22:33:44:55');
});

test('roteiro de instalacao bloqueia acesso a estacao matriz de outro municipio com 403', function () {
    $cidade1 = Cidade::factory()->create();
    $cidade2 = Cidade::factory()->create();

    $bairro2 = Bairro::factory()->create(['cidade_id' => $cidade2->id]);
    $userCampinas = User::factory()->create(['cidade_id' => $cidade1->id]);

    $matrizSantos = Estacao::factory()->create([
        'tipo_estacao' => 'Estação Matriz',
        'bairro_id' => $bairro2->id,
    ]);

    $response = $this->actingAs($userCampinas)->get(route('instalacoes.show', $matrizSantos->public_id));

    $response->assertForbidden();
});

test('instalador nao pode vincular mac em estacao de outro municipio com 403', function () {
    $cidade1 = Cidade::factory()->create();
    $cidade2 = Cidade::factory()->create();

    $bairro2 = Bairro::factory()->create(['cidade_id' => $cidade2->id]);
    $instaladorCampinas = User::factory()->create(['nivel' => 'instalador', 'cidade_id' => $cidade1->id]);

    $matrizSantos = Estacao::factory()->create([
        'tipo_estacao' => 'Estação Matriz',
        'bairro_id' => $bairro2->id,
        'status_instalacao' => 'Planejada',
        'ordem_instalacao' => 1,
    ]);

    $response = $this->actingAs($instaladorCampinas)->postJson(route('instalacoes.vincular-mac', $matrizSantos->public_id), [
        'mac_address' => 'AA:BB:CC:99:99:99',
    ]);

    $response->assertForbidden();
});

test('instalador nao pode vincular patrimonio de outro municipio com 422', function () {
    $cidade1 = Cidade::factory()->create();
    $cidade2 = Cidade::factory()->create();

    $bairro1 = Bairro::factory()->create(['cidade_id' => $cidade1->id]);
    $instaladorCampinas = User::factory()->create(['nivel' => 'instalador', 'cidade_id' => $cidade1->id]);

    $matrizCampinas = Estacao::factory()->create([
        'tipo_estacao' => 'Estação Matriz',
        'bairro_id' => $bairro1->id,
        'status_instalacao' => 'Planejada',
        'ordem_instalacao' => 1,
    ]);

    $patrimonioSantos = Patrimonio::factory()->create([
        'cidade_id' => $cidade2->id,
        'mac_address' => 'AA:BB:CC:88:88:88',
        'status' => 'Disponível',
    ]);

    $response = $this->actingAs($instaladorCampinas)->postJson(route('instalacoes.vincular-mac', $matrizCampinas->public_id), [
        'patrimonio_id' => $patrimonioSantos->public_id,
    ]);

    $response->assertStatus(422);
});

test('metodo estatico Estacao::ordenarEmCascata reorganiza arvore e sincroniza ordem_instalacao no banco', function () {
    $bairro = Bairro::factory()->create();

    $matriz = Estacao::factory()->create([
        'tipo_estacao' => 'Estação Matriz',
        'status_instalacao' => 'Instalada',
        'ordem_instalacao' => 1,
        'bairro_id' => $bairro->id,
    ]);
    $matriz->update(['matriz_pai_id' => $matriz->private_id]);

    // Cria Satélite B com ordem 4, filha direta da Matriz
    $satB = Estacao::factory()->create([
        'tipo_estacao' => 'Estação Satélite',
        'status_instalacao' => 'Planejada',
        'ordem_instalacao' => 4,
        'matriz_pai_id' => $matriz->private_id,
        'estacao_origem_id' => $matriz->private_id,
        'bairro_id' => $bairro->id,
    ]);

    // Cria Satélite A com ordem 3, que depende de Sat B (ordem invertida: dependente tem ordem menor que pai)
    $satA = Estacao::factory()->create([
        'tipo_estacao' => 'Estação Satélite',
        'status_instalacao' => 'Planejada',
        'ordem_instalacao' => 3,
        'matriz_pai_id' => $matriz->private_id,
        'estacao_origem_id' => $satB->private_id,
        'bairro_id' => $bairro->id,
    ]);

    $estacoes = collect([$matriz, $satA, $satB]);
    $ordenadas = Estacao::ordenarEmCascata($estacoes);

    // Matriz é o primeiro (#1)
    expect($ordenadas[0]->private_id)->toBe($matriz->private_id);
    expect($ordenadas[0]->ordem_instalacao)->toBe(1);

    // Satélite B (pai) DEVE vir antes de Satélite A (filha)
    expect($ordenadas[1]->private_id)->toBe($satB->private_id);
    expect($ordenadas[1]->ordem_instalacao)->toBe(2);

    expect($ordenadas[2]->private_id)->toBe($satA->private_id);
    expect($ordenadas[2]->ordem_instalacao)->toBe(3);

    // Verifica persistência no banco
    $satB->refresh();
    $satA->refresh();
    expect($satB->ordem_instalacao)->toBe(2);
    expect($satA->ordem_instalacao)->toBe(3);
});

test('roteiro de instalacao reordena estacoes topologicamente em cascata e exibe badges e estacao anterior', function () {
    $user = User::factory()->create(['nivel' => 'instalador']);
    $bairro = Bairro::factory()->create();

    $matriz = Estacao::factory()->create([
        'tipo_estacao' => 'Estação Matriz',
        'status_instalacao' => 'Instalada',
        'mac_address' => 'AA:BB:CC:11:22:33',
        'ordem_instalacao' => 1,
        'bairro_id' => $bairro->id,
    ]);
    $matriz->update(['matriz_pai_id' => $matriz->private_id]);

    // Satélite 1 com ordem 4 no banco, conectada na Matriz
    $sat1 = Estacao::factory()->create([
        'tipo_estacao' => 'Estação Satélite',
        'status_instalacao' => 'Planejada',
        'mac_address' => null,
        'ordem_instalacao' => 4,
        'matriz_pai_id' => $matriz->private_id,
        'estacao_origem_id' => $matriz->private_id,
        'bairro_id' => $bairro->id,
    ]);

    // Satélite 2 com ordem 3 no banco, dependente de Satélite 1 (que tinha ordem 4)
    $sat2 = Estacao::factory()->create([
        'tipo_estacao' => 'Estação Satélite',
        'status_instalacao' => 'Planejada',
        'mac_address' => null,
        'ordem_instalacao' => 3,
        'matriz_pai_id' => $matriz->private_id,
        'estacao_origem_id' => $sat1->private_id,
        'bairro_id' => $bairro->id,
    ]);

    $response = $this->actingAs($user)->get(route('instalacoes.show', $matriz->public_id));

    $response->assertOk();

    // Satélite 1 deve ser reordenada para Passo #2 (Próxima a Instalar)
    $response->assertSee('Passo #2: Estação Satélite');
    $response->assertSee('Próxima a Instalar');

    // Satélite 2 deve ser reordenada para Passo #3 (Aguardando Estação Anterior #2)
    $response->assertSee('Passo #3: Estação Satélite');
    $response->assertSee('Aguardando Estação Anterior (#2)');
    $response->assertSee('Instale e ative primeiro a estação anterior');

    // Instalador consegue ativar Passo #2 (Satélite 1) sem erro 422
    $responseVincular = $this->actingAs($user)->postJson(route('instalacoes.vincular-mac', $sat1->public_id), [
        'mac_address' => 'AA:BB:CC:44:55:66',
    ]);
    $responseVincular->assertOk();
    $responseVincular->assertJson(['success' => true]);

    $sat1->refresh();
    expect($sat1->status_instalacao)->toBe('Instalada');
    expect($sat1->mac_address)->toBe('AA:BB:CC:44:55:66');

    // Agora Satélite 2 pode ser ativada com sucesso pois sua estação anterior (#2) já foi instalada
    $responseVincular2 = $this->actingAs($user)->postJson(route('instalacoes.vincular-mac', $sat2->public_id), [
        'mac_address' => 'AA:BB:CC:77:88:99',
    ]);
    $responseVincular2->assertOk();
    $responseVincular2->assertJson(['success' => true]);

    $sat2->refresh();
    expect($sat2->status_instalacao)->toBe('Instalada');
});

test('roteiro de instalacao exibe selecao exclusiva via dropdown e nao renderiza inputs de texto ou abas de modo', function () {
    $user = User::factory()->create(['nivel' => 'instalador']);
    $bairro = Bairro::factory()->create();

    $patrimonio = Patrimonio::factory()->create([
        'cidade_id' => $bairro->cidade_id,
        'numero_patrimonio' => 'OAir-DISP-001',
        'mac_address' => 'AA:BB:CC:DD:EE:01',
        'status' => 'Disponível',
        'created_by' => $user->id,
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

    $response = $this->actingAs($user)->get(route('instalacoes.show', $matriz->public_id));

    $response->assertOk();
    // Verifica a presenca do select com a opcao inicial vazia e os dados do patrimonio/mac
    $response->assertSee('Selecione o equipamento (Patrimônio / MAC)...');
    $response->assertSee('Patrimônio: OAir-DISP-001');
    $response->assertSee('MAC: AA:BB:CC:DD:EE:01');
    $response->assertSee("select-patrimonio-{$matriz->public_id}");

    // Verifica que NAO ha mais abas de selecao de modo nem inputs manuais de texto
    $response->assertDontSee('Por Patrimônio');
    $response->assertDontSee('Por MAC Address');
    $response->assertDontSee("patrimonio-input-{$matriz->public_id}");
    $response->assertDontSee("mac-input-{$matriz->public_id}");

    // Ativacao direta via dropdown enviando patrimonio_id
    $responseAtivacao = $this->actingAs($user)->postJson(route('instalacoes.vincular-mac', $matriz->public_id), [
        'patrimonio_id' => $patrimonio->public_id,
    ]);

    $responseAtivacao->assertOk();
    $responseAtivacao->assertJson(['success' => true]);

    $matriz->refresh();
    $patrimonio->refresh();

    expect($matriz->status_instalacao)->toBe('Instalada');
    expect($matriz->mac_address)->toBe('AA:BB:CC:DD:EE:01');
    expect($matriz->patrimonio_id)->toBe($patrimonio->private_id);
    expect($patrimonio->status)->toBe('Instalada');
});
