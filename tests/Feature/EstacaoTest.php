<?php

use App\Models\Bairro;
use App\Models\Cidade;
use App\Models\Estacao;
use App\Models\Estado;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('seeder do banco executa com sucesso e popula estados e cidades', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Estado::count())->toBe(27);
    expect(Cidade::where('nome', 'São Paulo')->exists())->toBeTrue();
});

test('visitante nao autenticado e redirecionado para login ao acessar estacoes', function () {
    $this->get(route('estacoes.index'))->assertRedirect(route('login'));
    $this->get(route('estacoes.create'))->assertRedirect(route('login'));
    $this->post(route('estacoes.store'), [])->assertRedirect(route('login'));
});

test('usuario autenticado pode visualizar suas estacoes cadastradas', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $bairro = Bairro::factory()->create();

    $minhaEstacao = Estacao::factory()->create([
        'created_by' => $user->id,
        'bairro_id' => $bairro->id,
        'mac_address' => 'AA:BB:CC:DD:EE:01',
        'tipo_estacao' => 'Estação Matriz',
        'latitude' => -23.55052,
        'longitude' => -46.63330,
    ]);

    $outraEstacao = Estacao::factory()->create([
        'created_by' => $otherUser->id,
        'bairro_id' => $bairro->id,
        'mac_address' => 'AA:BB:CC:DD:EE:99',
        'tipo_estacao' => 'Estação Matriz',
    ]);

    $response = $this->actingAs($user)->get(route('estacoes.index'));

    $response->assertOk();
    $response->assertSee('AA:BB:CC:DD:EE:01');
    $response->assertDontSee('AA:BB:CC:DD:EE:99');
    $response->assertSee('Cadastrar Nova Estação');
});

test('usuario pode acessar formulario de criacao de estacao', function () {
    $user = User::factory()->create();
    $estado = Estado::factory()->create(['nome' => 'São Paulo', 'uf' => 'SP']);

    $response = $this->actingAs($user)->get(route('estacoes.create'));

    $response->assertOk();
    $response->assertSee('Cadastro de Nova Estação');
    $response->assertSee('São Paulo');
    $response->assertSee('Snap to Road');
    $response->assertSee('snap-to-road');
});

test('endpoints de localidade ibge retornam dados corretos em cascata', function () {
    $user = User::factory()->create();
    $estado = Estado::factory()->create(['nome' => 'São Paulo', 'uf' => 'SP']);
    $cidade = Cidade::factory()->create(['estado_id' => $estado->id, 'nome' => 'Campinas']);
    $bairro = Bairro::factory()->create(['cidade_id' => $cidade->id, 'nome' => 'Cambuí']);

    // 1. Estados
    $resEstados = $this->actingAs($user)->getJson(route('localidades.estados'));
    $resEstados->assertOk()->assertJsonFragment(['uf' => 'SP', 'nome' => 'São Paulo']);

    // 2. Cidades do estado
    $resCidades = $this->actingAs($user)->getJson(route('localidades.estados.cidades', $estado));
    $resCidades->assertOk()->assertJsonFragment(['nome' => 'Campinas']);

    // 3. Bairros da cidade
    $resBairros = $this->actingAs($user)->getJson(route('localidades.cidades.bairros', $cidade));
    $resBairros->assertOk()->assertJsonFragment(['nome' => 'Cambuí']);
});

test('endpoint de bairros consulta overpass api e cadastra bairros automaticamente', function () {
    $user = User::factory()->create();
    Http::fake([
        '*interpreter*' => Http::response([
            'elements' => [
                ['type' => 'node', 'id' => 101, 'tags' => ['name' => 'Jardim Aeroporto', 'place' => 'suburb']],
                ['type' => 'node', 'id' => 102, 'tags' => ['name' => 'Vila Conrado', 'place' => 'suburb']],
                ['type' => 'way', 'id' => 103, 'tags' => ['name' => 'Distrito Industrial', 'place' => 'suburb']],
            ],
        ], 200),
    ]);

    $estado = Estado::factory()->create(['nome' => 'São Paulo', 'uf' => 'SP']);
    $cidade = Cidade::factory()->create(['estado_id' => $estado->id, 'nome' => 'São João da Boa Vista']);

    expect($cidade->bairros()->count())->toBe(0);

    $response = $this->actingAs($user)->getJson(route('localidades.cidades.bairros', $cidade));

    $response->assertOk();
    $response->assertJsonFragment(['nome' => 'Jardim Aeroporto']);
    $response->assertJsonFragment(['nome' => 'Vila Conrado']);
    $response->assertJsonFragment(['nome' => 'Distrito Industrial']);

    expect($cidade->bairros()->count())->toBe(3);
    $this->assertDatabaseHas('bairros', [
        'cidade_id' => $cidade->id,
        'nome' => 'Jardim Aeroporto',
    ]);
});

test('endpoint de bairros realiza failover entre espelhos caso o primeiro falhe com erro 504', function () {
    $user = User::factory()->create();
    Http::fake([
        'https://overpass-api.de/api/interpreter*' => Http::response('Gateway Timeout', 504),
        'https://overpass.kumi.systems/api/interpreter*' => Http::response([
            'elements' => [
                ['type' => 'node', 'id' => 201, 'tags' => ['name' => 'Cascatinha', 'place' => 'suburb']],
            ],
        ], 200),
    ]);

    $estado = Estado::factory()->create(['nome' => 'São Paulo', 'uf' => 'SP']);
    $cidade = Cidade::factory()->create(['estado_id' => $estado->id, 'nome' => 'Águas da Prata']);

    $response = $this->actingAs($user)->getJson(route('localidades.cidades.bairros', $cidade));

    $response->assertOk();
    $response->assertJsonFragment(['nome' => 'Cascatinha']);
    $this->assertDatabaseHas('bairros', [
        'cidade_id' => $cidade->id,
        'nome' => 'Cascatinha',
    ]);
});

test('endpoint de coordenadas retorna todas as estacoes para o mapa', function () {
    $user = User::factory()->create();
    $bairro = Bairro::factory()->create();
    $estacao = Estacao::factory()->create([
        'bairro_id' => $bairro->id,
        'mac_address' => '11:22:33:44:55:66',
        'tipo_estacao' => 'Estação Matriz',
        'latitude' => -23.55052,
        'longitude' => -46.63330,
    ]);

    $response = $this->actingAs($user)->getJson(route('estacoes.coordenadas'));

    $response->assertOk();
    $response->assertJsonFragment([
        'mac_address' => '11:22:33:44:55:66',
        'tipo_estacao' => 'Estação Matriz',
        'latitude' => -23.55052,
        'longitude' => -46.6333,
    ]);
});

test('pode cadastrar uma estacao matriz com coordenadas livres', function () {
    $user = User::factory()->create();
    $bairro = Bairro::factory()->create();

    $payload = [
        'mac_address' => 'AA:BB:CC:11:22:33',
        'tipo_estacao' => 'Estação Matriz',
        'bairro_id' => $bairro->id,
        'latitude' => -23.550520,
        'longitude' => -46.633308,
    ];

    $response = $this->actingAs($user)->post(route('estacoes.store'), $payload);

    $response->assertRedirect(route('estacoes.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('estacoes', [
        'mac_address' => 'AA:BB:CC:11:22:33',
        'tipo_estacao' => 'Estação Matriz',
        'bairro_id' => $bairro->id,
        'created_by' => $user->id,
    ]);

    $estacao = Estacao::where('mac_address', 'AA:BB:CC:11:22:33')->first();
    expect($estacao)->not->toBeNull();
    expect(round($estacao->latitude, 5))->toBe(-23.55052);
    expect(round($estacao->longitude, 5))->toBe(-46.63331);
});

test('pode cadastrar uma estacao satelite dentro do raio de 200 metros de uma existente', function () {
    $user = User::factory()->create();
    $bairro = Bairro::factory()->create();

    // Estação Matriz existente
    $matriz = Estacao::factory()->create([
        'tipo_estacao' => 'Estação Matriz',
        'latitude' => -23.550520,
        'longitude' => -46.633308,
    ]);

    // Nova estação satélite a cerca de 80 metros da matriz
    // ~0.0007 graus de latitude equivale a ~77 metros
    $sateliteLat = -23.550520 + 0.0007;
    $sateliteLng = -46.633308;

    $payload = [
        'mac_address' => 'CC:DD:EE:44:55:66',
        'tipo_estacao' => 'Estação Satélite',
        'bairro_id' => $bairro->id,
        'latitude' => $sateliteLat,
        'longitude' => $sateliteLng,
    ];

    $response = $this->actingAs($user)->post(route('estacoes.store'), $payload);

    $response->assertRedirect(route('estacoes.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('estacoes', [
        'mac_address' => 'CC:DD:EE:44:55:66',
        'tipo_estacao' => 'Estação Satélite',
    ]);
});

test('nao pode cadastrar uma estacao satelite a mais de 200 metros de distancia', function () {
    $user = User::factory()->create();
    $bairro = Bairro::factory()->create();

    // Estação Matriz existente em São Paulo
    Estacao::factory()->create([
        'tipo_estacao' => 'Estação Matriz',
        'latitude' => -23.550520,
        'longitude' => -46.633308,
    ]);

    // Tentativa de satélite a ~2.2 km de distância (~0.02 graus)
    $payload = [
        'mac_address' => '99:88:77:66:55:44',
        'tipo_estacao' => 'Estação Satélite',
        'bairro_id' => $bairro->id,
        'latitude' => -23.570520,
        'longitude' => -46.633308,
    ];

    $response = $this->actingAs($user)->post(route('estacoes.store'), $payload);

    $response->assertSessionHasErrors(['latitude']);
    $this->assertDatabaseMissing('estacoes', [
        'mac_address' => '99:88:77:66:55:44',
    ]);
});

test('nao pode cadastrar uma estacao satelite se nao houver nenhuma estacao cadastrada no sistema', function () {
    $user = User::factory()->create();
    $bairro = Bairro::factory()->create();

    // Nenhuma estação existente no sistema
    expect(Estacao::count())->toBe(0);

    $payload = [
        'mac_address' => 'AA:AA:AA:AA:AA:AA',
        'tipo_estacao' => 'Estação Satélite',
        'bairro_id' => $bairro->id,
        'latitude' => -23.550520,
        'longitude' => -46.633308,
    ];

    $response = $this->actingAs($user)->post(route('estacoes.store'), $payload);

    $response->assertSessionHasErrors(['tipo_estacao']);
    $this->assertDatabaseMissing('estacoes', [
        'mac_address' => 'AA:AA:AA:AA:AA:AA',
    ]);
});

test('pode cadastrar uma estacao satelite proxima a outra estacao satelite existente dentro de 200m', function () {
    $user = User::factory()->create();
    $bairro = Bairro::factory()->create();

    // Estação Satélite existente no sistema
    $satelitePai = Estacao::factory()->create([
        'tipo_estacao' => 'Estação Satélite',
        'latitude' => -23.550520,
        'longitude' => -46.633308,
    ]);

    // Nova estação satélite a cerca de 50 metros da satélite existente (~0.0004 graus)
    $payload = [
        'mac_address' => 'AA:AA:AA:AA:AA:AA',
        'tipo_estacao' => 'Estação Satélite',
        'bairro_id' => $bairro->id,
        'latitude' => -23.550520 + 0.0004,
        'longitude' => -46.633308,
    ];

    $response = $this->actingAs($user)->post(route('estacoes.store'), $payload);

    $response->assertRedirect(route('estacoes.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('estacoes', [
        'mac_address' => 'AA:AA:AA:AA:AA:AA',
        'tipo_estacao' => 'Estação Satélite',
        'estacao_origem_id' => $satelitePai->private_id,
    ]);
});

test('usuario com jurisdicao municipal vinculada nao pode cadastrar estacao em bairro de outro municipio', function () {
    $cidadeUsuario = Cidade::factory()->create(['nome' => 'Campinas']);
    $outraCidade = Cidade::factory()->create(['nome' => 'São Paulo']);

    $user = User::factory()->create(['cidade_id' => $cidadeUsuario->id]);

    $bairroOutraCidade = Bairro::factory()->create(['cidade_id' => $outraCidade->id, 'nome' => 'Pinheiros']);

    $payload = [
        'mac_address' => '11:22:33:AA:BB:CC',
        'tipo_estacao' => 'Estação Matriz',
        'bairro_id' => $bairroOutraCidade->id,
        'latitude' => -23.560000,
        'longitude' => -46.700000,
    ];

    $response = $this->actingAs($user)->post(route('estacoes.store'), $payload);

    $response->assertSessionHasErrors(['bairro_id']);
    $this->assertDatabaseMissing('estacoes', [
        'mac_address' => '11:22:33:AA:BB:CC',
    ]);
});

test('usuario com jurisdicao municipal vinculada pode cadastrar estacao em bairro do seu municipio', function () {
    $cidadeUsuario = Cidade::factory()->create(['nome' => 'Campinas']);
    $user = User::factory()->create(['cidade_id' => $cidadeUsuario->id]);

    $bairroCidadeUsuario = Bairro::factory()->create(['cidade_id' => $cidadeUsuario->id, 'nome' => 'Taquaral']);

    $payload = [
        'mac_address' => '11:22:33:AA:BB:DD',
        'tipo_estacao' => 'Estação Matriz',
        'bairro_id' => $bairroCidadeUsuario->id,
        'latitude' => -22.870000,
        'longitude' => -47.050000,
    ];

    $response = $this->actingAs($user)->post(route('estacoes.store'), $payload);

    $response->assertRedirect(route('estacoes.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('estacoes', [
        'mac_address' => '11:22:33:AA:BB:DD',
        'bairro_id' => $bairroCidadeUsuario->id,
    ]);
});

test('bloqueia cadastro com mac address duplicado', function () {
    $user = User::factory()->create();
    $bairro = Bairro::factory()->create();

    Estacao::factory()->create([
        'mac_address' => 'AA:BB:CC:DD:EE:FF',
    ]);

    $payload = [
        'mac_address' => 'AA:BB:CC:DD:EE:FF',
        'tipo_estacao' => 'Estação Matriz',
        'bairro_id' => $bairro->id,
        'latitude' => -23.550520,
        'longitude' => -46.633308,
    ];

    $response = $this->actingAs($user)->post(route('estacoes.store'), $payload);

    $response->assertSessionHasErrors(['mac_address']);
});

test('bloqueia cadastro com mac address em formato invalido', function () {
    $user = User::factory()->create();
    $bairro = Bairro::factory()->create();

    $payload = [
        'mac_address' => 'MAC-INVALIDO-123',
        'tipo_estacao' => 'Estação Matriz',
        'bairro_id' => $bairro->id,
        'latitude' => -23.550520,
        'longitude' => -46.633308,
    ];

    $response = $this->actingAs($user)->post(route('estacoes.store'), $payload);

    $response->assertSessionHasErrors(['mac_address']);
});

test('api de geocodificacao reversa retorna endereco a partir de coordenadas', function () {
    $user = User::factory()->create();
    $estado = Estado::factory()->create(['nome' => 'São Paulo', 'uf' => 'SP']);
    $cidade = Cidade::factory()->create(['estado_id' => $estado->id, 'nome' => 'São Paulo']);

    Http::fake([
        'https://nominatim.openstreetmap.org/reverse*' => Http::response([
            'address' => [
                'road' => 'Rua Oscar Freire',
                'house_number' => '900',
                'suburb' => 'Cerqueira César',
                'city' => 'São Paulo',
                'state' => 'São Paulo',
                'ISO3166-2-lvl4' => 'BR-SP',
            ],
        ], 200),
    ]);

    $response = $this->actingAs($user)->getJson(route('geocoding.reverse', [
        'lat' => -23.5645,
        'lng' => -46.6698,
    ]));

    $response->assertOk();
    $response->assertJson([
        'endereco' => 'Rua Oscar Freire, 900',
        'latitude' => -23.5645,
        'longitude' => -46.6698,
        'cidade_id' => $cidade->id,
        'estado_id' => $estado->id,
        'bairro' => 'Cerqueira César',
    ]);
    expect($response->json('bairro_id'))->not->toBeNull();
});

test('listagem de estacoes exibe endereco estruturado junto da localidade', function () {
    $user = User::factory()->create();
    $bairro = Bairro::factory()->create(['nome' => 'Jardim América']);
    $estacao = Estacao::factory()->create([
        'created_by' => $user->id,
        'bairro_id' => $bairro->id,
        'logradouro' => 'Avenida Brasil',
        'numero' => '100',
        'bairro_nome' => 'Jardim América',
        'cidade_nome' => 'São Paulo',
        'estado_uf' => 'SP',
        'cep' => '01430-000',
        'latitude' => -23.5700,
        'longitude' => -46.6700,
    ]);

    $response = $this->actingAs($user)->get(route('estacoes.index'));

    $response->assertOk();
    $response->assertSee('Avenida Brasil, 100');
    $response->assertSee('Jardim América');
    $response->assertSee('CEP: 01430-000');
});

test('cadastro de estacao persiste endereco estruturado e substitui bairro pelo bairro do osm', function () {
    Http::fake([
        'https://nominatim.openstreetmap.org/reverse*' => Http::response([
            'display_name' => 'Avenida Paulista, 1578, Bela Vista, São Paulo, SP, 01310-200, Brasil',
            'address' => [
                'road' => 'Avenida Paulista',
                'house_number' => '1578',
                'suburb' => 'Bela Vista',
                'city' => 'São Paulo',
                'state' => 'São Paulo',
                'ISO3166-2-lvl4' => 'BR-SP',
                'postcode' => '01310-200',
            ],
        ], 200),
    ]);

    $user = User::factory()->create();
    $cidade = Cidade::factory()->create(['nome' => 'São Paulo']);
    $bairroInicial = Bairro::factory()->create([
        'cidade_id' => $cidade->id,
        'nome' => 'Centro',
    ]);

    $payload = [
        'mac_address' => 'AA:BB:CC:99:88:77',
        'tipo_estacao' => 'Estação Matriz',
        'bairro_id' => $bairroInicial->id, // Bairro selecionado inicialmente pelo usuário
        'latitude' => -23.561684,
        'longitude' => -46.655981,
    ];

    $response = $this->actingAs($user)->post(route('estacoes.store'), $payload);

    $response->assertRedirect(route('estacoes.index'));

    // Verifica se o novo bairro "Bela Vista" foi criado para a cidade
    $bairroDetectado = Bairro::where('cidade_id', $cidade->id)->where('nome', 'Bela Vista')->first();
    expect($bairroDetectado)->not->toBeNull();

    // Verifica se a estação foi criada com o bairro substituído e os campos de endereço preenchidos
    $this->assertDatabaseHas('estacoes', [
        'mac_address' => 'AA:BB:CC:99:88:77',
        'bairro_id' => $bairroDetectado->id,
        'logradouro' => 'Avenida Paulista',
        'numero' => '1578',
        'bairro_nome' => 'Bela Vista',
        'cidade_nome' => 'São Paulo',
        'estado_uf' => 'SP',
        'cep' => '01310-200',
    ]);
});

test('listagem de estacoes exibe apenas estacoes da jurisdicao municipal do usuario', function () {
    $cidade1 = Cidade::factory()->create(['nome' => 'Campinas']);
    $cidade2 = Cidade::factory()->create(['nome' => 'Santos']);

    $bairro1 = Bairro::factory()->create(['cidade_id' => $cidade1->id]);
    $bairro2 = Bairro::factory()->create(['cidade_id' => $cidade2->id]);

    $userCampinas = User::factory()->create(['cidade_id' => $cidade1->id]);

    $estacaoCampinas = Estacao::factory()->create([
        'bairro_id' => $bairro1->id,
        'mac_address' => 'AA:11:11:11:11:11',
        'logradouro' => 'Rua de Campinas',
    ]);

    $estacaoSantos = Estacao::factory()->create([
        'bairro_id' => $bairro2->id,
        'mac_address' => 'AA:22:22:22:22:22',
        'logradouro' => 'Avenida da Praia Santos',
    ]);

    $response = $this->actingAs($userCampinas)->get(route('estacoes.index'));

    $response->assertOk();
    $response->assertSee('AA:11:11:11:11:11');
    $response->assertDontSee('AA:22:22:22:22:22');
});
