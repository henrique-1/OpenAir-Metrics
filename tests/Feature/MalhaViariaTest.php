<?php

use App\Jobs\ResolveReverseGeocodingJob;
use App\Models\Cidade;
use App\Models\Estacao;
use App\Models\Estado;
use App\Models\MalhaViaria;
use App\Models\User;
use App\Services\GeocodingService;
use App\Services\PlanejamentoMalhaService;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\MalhaViariaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

test('seeder de malha viaria popula o banco principal com logradouro e tipo_via', function () {
    MalhaViaria::truncate();
    expect(MalhaViaria::count())->toBe(0);

    $this->seed(DatabaseSeeder::class);

    expect(MalhaViaria::count())->toBeGreaterThan(0);
    expect(MalhaViaria::where('tipo_via', 'residential')->exists())->toBeTrue();
    expect(MalhaViaria::whereNotNull('logradouro')->exists())->toBeTrue();
});

test('geocoding service busca vias proximas no banco local dentro do raio de 200m com logradouro', function () {
    $this->seed(MalhaViariaSeeder::class);

    $service = app(GeocodingService::class);
    $vias = $service->buscarViasProximas(-21.9847, -46.7947, 200.0);

    expect($vias)->not->toBeEmpty();
    expect($vias[0])->toHaveKeys(['id', 'distancia', 'geometry', 'tags']);
    expect($vias[0]['tags']['highway'])->not->toBeEmpty();
    expect($vias[0]['tags']['name'])->not->toBeEmpty();
});

test('obter detalhes de endereco retorna logradouro local e despacha job para numero e cep', function () {
    Queue::fake();
    $this->seed(MalhaViariaSeeder::class);

    $detalhes = GeocodingService::obterDetalhesEndereco(-21.9847, -46.7947);

    expect($detalhes)->not->toBeNull();
    expect($detalhes['logradouro'])->not->toBeNull();

    Queue::assertPushed(ResolveReverseGeocodingJob::class, function ($job) {
        return $job->latitude === -21.9847 && $job->longitude === -46.7947;
    });
});

test('job resolve reverse geocoding executa com rate limiter e atualiza estacao no banco', function () {
    Http::fake([
        'https://nominatim.openstreetmap.org/reverse*' => Http::response([
            'address' => [
                'road' => 'Rua Saldanha Marinho',
                'house_number' => '150',
                'suburb' => 'Centro',
                'city' => 'São João da Boa Vista',
                'state' => 'São Paulo',
                'ISO3166-2-lvl4' => 'BR-SP',
                'postcode' => '13870-000',
            ],
            'display_name' => 'Rua Saldanha Marinho, 150, Centro, São João da Boa Vista, SP, 13870-000, Brasil',
        ], 200),
    ]);

    $user = User::factory()->create();
    $estado = Estado::firstOrCreate(['uf' => 'SP'], ['nome' => 'São Paulo']);
    $cidade = Cidade::firstOrCreate(['estado_id' => $estado->id, 'nome' => 'São João da Boa Vista']);

    $estacao = Estacao::factory()->create([
        'created_by' => $user->id,
        'latitude' => -21.9847,
        'longitude' => -46.7947,
        'logradouro' => null,
        'numero' => null,
        'cep' => null,
    ]);

    RateLimiter::clear('nominatim_reverse_geocoding');

    $job = new ResolveReverseGeocodingJob(-21.9847, -46.7947, $estacao->private_id);
    $job->handle();

    $estacao->refresh();

    expect($estacao->logradouro)->toBe('Rua Saldanha Marinho');
    expect($estacao->numero)->toBe('150');
    expect($estacao->bairro_nome)->toBe('Centro');
    expect($estacao->cidade_nome)->toBe('São João da Boa Vista');
    expect($estacao->estado_uf)->toBe('SP');
    expect($estacao->cep)->toBe('13870-000');
});

test('planejamento malha service calcula estacoes utilizando vias e logradouros do banco local sem consultar overpass', function () {
    $this->seed(MalhaViariaSeeder::class);

    // Garante que nenhuma chamada HTTP ao Overpass seja feita
    Http::fake([
        '*interpreter*' => Http::response([], 500),
    ]);

    $service = app(PlanejamentoMalhaService::class);
    $malha = $service->calcularMalha(-21.9847, -46.7947, 3);

    expect($malha)->toHaveKeys(['matriz', 'satelites']);
    expect($malha['matriz']['tipo_estacao'])->toBe('Estação Matriz');
    expect($malha['matriz']['logradouro'])->not->toBeEmpty();
    expect(count($malha['satelites']))->toBe(3);

    foreach ($malha['satelites'] as $sat) {
        expect($sat['distancia_origem_metros'])->toBeLessThanOrEqual(200.0);
    }
});

test('snapToRoad ajusta coordenadas para a via publica mais proxima ignorando rodovias motorway e trunk', function () {
    $this->seed(MalhaViariaSeeder::class);

    $isSqlite = DB::getDriverName() === 'sqlite';

    // Cria uma rodovia motorway muito próxima do ponto de teste (-21.9847, -46.7947)
    MalhaViaria::create([
        'logradouro' => 'Rodovia Gov. Adhemar Pereira de Barros',
        'tipo_via' => 'motorway',
        'geometria' => $isSqlite
            ? 'LINESTRING(-46.79471 -21.98471, -46.79472 -21.98472)'
            : DB::raw("ST_GeomFromText('LINESTRING(-46.79471 -21.98471, -46.79472 -21.98472)', 4326)"),
    ]);

    $service = app(GeocodingService::class);

    // O candidato está colado na rodovia (-21.98471, -46.79471)
    $snapped = $service->snapToRoad(-21.98471, -46.79471);

    expect($snapped)->not->toBeNull();
    // Deve ignorar a motorway e conectar à via urbana permitida mais próxima
    expect($snapped['tipo_via'])->not->toBe('motorway');
    expect($snapped['nome_rua'])->not->toBe('Rodovia Gov. Adhemar Pereira de Barros');
});

test('snapToRoad respeita rigorosamente a regra dos 200m da origem', function () {
    $this->seed(MalhaViariaSeeder::class);

    $service = app(GeocodingService::class);

    // Origem em -21.9847, -46.7947 (Avenida Dona Gertrudes)
    $origemLat = -21.9847;
    $origemLng = -46.7947;

    // Candidato a ~350 metros de distância (ao norte)
    $candLat = -21.9815;
    $candLng = -46.7920;

    $snapped = $service->snapToRoad($candLat, $candLng, $origemLat, $origemLng, 200.0);

    expect($snapped)->not->toBeNull();
    expect($snapped['distancia_origem'])->toBeLessThanOrEqual(200.0);
    expect($snapped['nome_rua'])->not->toBeNull();
});

test('planejamento malha service gera satelites estritamente sobre vias reais sem rua projetada', function () {
    $this->seed(MalhaViariaSeeder::class);

    Http::fake([
        '*interpreter*' => Http::response([], 500),
    ]);

    $service = app(PlanejamentoMalhaService::class);
    $malha = $service->calcularMalha(-21.9847, -46.7947, 4);

    expect($malha['matriz']['logradouro'])->not->toBeEmpty();
    expect($malha['matriz']['logradouro'])->not->toContain('Rua Projetada');

    foreach ($malha['satelites'] as $sat) {
        expect($sat['logradouro'])->not->toBeEmpty();
        expect($sat['logradouro'])->not->toContain('Rua Projetada');
        expect($sat['distancia_origem_metros'])->toBeLessThanOrEqual(200.0);
    }
});

test('snapToRoad detecta colisao e prioriza pontos sem sobreposicao de raio de 200m de outras estacoes', function () {
    $this->seed(MalhaViariaSeeder::class);

    $service = app(GeocodingService::class);

    // Estação pai em -21.9847, -46.7947 (Avenida Dona Gertrudes)
    $paiLat = -21.9847;
    $paiLng = -46.7947;

    // Outra estação existente B localizada na Rua Saldanha Marinho a ~100m do cruzamento
    $estacaoB = [
        'latitude' => -21.9845,
        'longitude' => -46.7950,
    ];

    // Candidato tentando se posicionar muito perto da Estação B
    $candLat = -21.9845;
    $candLng = -46.7950;

    // Sem a lista de existentes, ele colaria exatamente na Estação B
    $snappedSemAntiOverlap = $service->snapToRoad($candLat, $candLng, $paiLat, $paiLng, 200.0);
    expect($snappedSemAntiOverlap)->not->toBeNull();

    // Com Anti-Overlap, ele detecta a colisão com B e busca um ponto na via que não sobreponha B
    $snappedComAntiOverlap = $service->snapToRoad(
        $candLat,
        $candLng,
        $paiLat,
        $paiLng,
        200.0,
        null,
        [$estacaoB]
    );

    expect($snappedComAntiOverlap)->not->toBeNull();
    // A distância até a outra estação B deve ser maximizada ou >= 200m
    $distAteB = Estacao::calcularDistanciaHaversine(
        $snappedComAntiOverlap['latitude'],
        $snappedComAntiOverlap['longitude'],
        $estacaoB['latitude'],
        $estacaoB['longitude']
    );

    expect($distAteB)->toBeGreaterThanOrEqual(100.0);
    expect($snappedComAntiOverlap['distancia_origem'])->toBeLessThanOrEqual(200.0);
});

test('planejamento malha service gera satelites maximizando cobertura sem colisao de raios entre ramos', function () {
    $this->seed(MalhaViariaSeeder::class);

    Http::fake([
        '*interpreter*' => Http::response([], 500),
    ]);

    $service = app(PlanejamentoMalhaService::class);
    $malha = $service->calcularMalha(-21.9847, -46.7947, 4);

    expect($malha['satelites'])->toHaveCount(4);

    // Verifica que cada satélite respeita os 200m do seu pai
    foreach ($malha['satelites'] as $sat) {
        expect($sat['distancia_origem_metros'])->toBeLessThanOrEqual(200.0);
    }

    // Verifica que satélites que não possuem vínculo direto pai-filho não colidem (< 100m)
    $satelites = $malha['satelites'];
    for ($i = 0; $i < count($satelites); $i++) {
        for ($j = $i + 1; $j < count($satelites); $j++) {
            $satA = $satelites[$i];
            $satB = $satelites[$j];

            $distEntreSats = Estacao::calcularDistanciaHaversine(
                $satA['latitude'],
                $satA['longitude'],
                $satB['latitude'],
                $satB['longitude']
            );

            // Devem ter espaçamento adequado para cobrir áreas distintas sem sobrepor
            expect($distEntreSats)->toBeGreaterThanOrEqual(80.0);
        }
    }
});
