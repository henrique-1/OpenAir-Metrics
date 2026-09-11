<?php

use App\Events\NovaMedicaoRecebida;
use App\Models\Bairro;
use App\Models\Estacao;
use App\Models\Medicao;
use App\Models\Patrimonio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

test('estacao em campo pode enviar medicoes com sucesso via patrimonio', function () {
    $user = User::factory()->create();
    $bairro = Bairro::factory()->create();

    $patrimonio = Patrimonio::factory()->create([
        'cidade_id' => $bairro->cidade_id,
        'numero_patrimonio' => 'OAir-Estacao-1-0001',
        'status' => 'Instalado',
        'created_by' => $user->id,
    ]);

    $estacao = Estacao::factory()->create([
        'created_by' => $user->id,
        'bairro_id' => $bairro->id,
        'patrimonio_id' => $patrimonio->private_id,
        'status_instalacao' => 'Instalada',
    ]);

    $payload = [
        'patrimonio' => 'OAir-Estacao-1-0001',
        'temperatura' => 24.5,
        'umidade' => 65.0,
        'co2' => 450,
        'poeira' => 12.3,
    ];

    $response = $this->postJson(route('api.medicoes.store'), $payload);

    $response->assertCreated();
    $response->assertJson([
        'success' => true,
        'message' => 'Medição registrada com sucesso.',
        'data' => [
            'estacao_id' => $estacao->public_id,
            'patrimonio' => 'OAir-Estacao-1-0001',
            'temperatura' => 24.5,
            'umidade' => 65.0,
            'co2' => 450,
            'poeira' => 12.3,
        ],
    ]);

    expect(Medicao::where('estacao_id', $estacao->private_id)->count())->toBe(1);
    $medicao = Medicao::where('estacao_id', $estacao->private_id)->first();
    expect($medicao->iqa)->not->toBeNull();
    expect($medicao->data_hora)->not->toBeNull();
    expect($medicao->data_hora->diffInSeconds(now()))->toBeLessThan(5);
});

test('estacao em campo pode enviar medicoes via public_id do patrimonio', function () {
    $user = User::factory()->create();
    $bairro = Bairro::factory()->create();

    $patrimonio = Patrimonio::factory()->create([
        'cidade_id' => $bairro->cidade_id,
        'status' => 'Instalado',
        'created_by' => $user->id,
    ]);

    $estacao = Estacao::factory()->create([
        'created_by' => $user->id,
        'bairro_id' => $bairro->id,
        'patrimonio_id' => $patrimonio->private_id,
        'status_instalacao' => 'Instalada',
    ]);

    $payload = [
        'patrimonio' => $patrimonio->public_id,
        'temperatura' => 22.0,
        'umidade' => 60.0,
        'co2' => 400,
        'poeira' => 10.0,
    ];

    $response = $this->postJson(route('api.medicoes.store'), $payload);

    $response->assertCreated();
    expect(Medicao::where('estacao_id', $estacao->private_id)->count())->toBe(1);
});

test('data_hora no payload e ignorada e cadastrada automaticamente pelo servidor', function () {
    $user = User::factory()->create();
    $bairro = Bairro::factory()->create();

    $patrimonio = Patrimonio::factory()->create([
        'cidade_id' => $bairro->cidade_id,
        'numero_patrimonio' => 'OAir-Estacao-1-0002',
        'status' => 'Instalado',
        'created_by' => $user->id,
    ]);

    $estacao = Estacao::factory()->create([
        'created_by' => $user->id,
        'bairro_id' => $bairro->id,
        'patrimonio_id' => $patrimonio->private_id,
        'status_instalacao' => 'Instalada',
    ]);

    $payload = [
        'patrimonio' => 'OAir-Estacao-1-0002',
        'temperatura' => 23.0,
        'umidade' => 55.0,
        'co2' => 420,
        'poeira' => 11.0,
        'data_hora' => '2020-01-01 10:00:00',
    ];

    $response = $this->postJson(route('api.medicoes.store'), $payload);

    $response->assertCreated();
    $medicao = Medicao::where('estacao_id', $estacao->private_id)->first();
    expect($medicao->data_hora->year)->toBe(now()->year);
    expect($medicao->data_hora->toDateString())->not->toBe('2020-01-01');
});

test('estacao em campo pode enviar medicoes via estacao_id publico', function () {
    $user = User::factory()->create();
    $bairro = Bairro::factory()->create();

    $patrimonio = Patrimonio::factory()->create([
        'cidade_id' => $bairro->cidade_id,
        'numero_patrimonio' => 'OAir-Estacao-1-0003',
        'status' => 'Instalado',
        'created_by' => $user->id,
    ]);

    $estacao = Estacao::factory()->create([
        'created_by' => $user->id,
        'bairro_id' => $bairro->id,
        'patrimonio_id' => $patrimonio->private_id,
        'status_instalacao' => 'Instalada',
    ]);

    $payload = [
        'estacao_id' => $estacao->public_id,
        'temperatura' => 26.0,
        'umidade' => 70.0,
        'co2' => 500,
        'poeira' => 15.0,
    ];

    $response = $this->postJson(route('api.medicoes.store'), $payload);

    $response->assertCreated();
    expect(Medicao::where('estacao_id', $estacao->private_id)->count())->toBe(1);
});

test('envio de medicao retorna 404 se estacao nao for encontrada por patrimonio', function () {
    $payload = [
        'patrimonio' => 'PATRIMONIO-INEXISTENTE',
        'temperatura' => 20.0,
        'umidade' => 50.0,
        'co2' => 400,
        'poeira' => 10.0,
    ];

    $response = $this->postJson(route('api.medicoes.store'), $payload);

    $response->assertNotFound();
    $response->assertJson([
        'success' => false,
        'message' => 'Estação não encontrada com o identificador ou patrimônio informado.',
    ]);
});

test('envio de medicao retorna 422 se estacao ainda nao estiver com status Instalada', function () {
    $user = User::factory()->create();
    $bairro = Bairro::factory()->create();

    $patrimonio = Patrimonio::factory()->create([
        'cidade_id' => $bairro->cidade_id,
        'numero_patrimonio' => 'OAir-Estacao-1-0004',
        'status' => 'Disponível',
        'created_by' => $user->id,
    ]);

    Estacao::factory()->create([
        'created_by' => $user->id,
        'bairro_id' => $bairro->id,
        'patrimonio_id' => $patrimonio->private_id,
        'status_instalacao' => 'Planejada',
    ]);

    $payload = [
        'patrimonio' => 'OAir-Estacao-1-0004',
        'temperatura' => 25.0,
        'umidade' => 55.0,
        'co2' => 420,
        'poeira' => 8.0,
    ];

    $response = $this->postJson(route('api.medicoes.store'), $payload);

    $response->assertStatus(422);
    $response->assertJson([
        'success' => false,
    ]);
});

test('envio de medicao valida campos obrigatorios', function () {
    $response = $this->postJson(route('api.medicoes.store'), []);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['patrimonio', 'temperatura', 'umidade', 'co2', 'poeira']);
});

test('model Medicao preenche data_hora automaticamente ao ser criado sem ela', function () {
    $user = User::factory()->create();
    $estacao = Estacao::factory()->create(['created_by' => $user->id]);

    $medicao = Medicao::create([
        'estacao_id' => $estacao->private_id,
        'temperatura' => 20.0,
        'umidade' => 50.0,
        'co2' => 400,
        'poeira' => 10.0,
    ]);

    expect($medicao->data_hora)->not->toBeNull();
    expect($medicao->data_hora->isToday())->toBeTrue();
    expect($medicao->iqa)->not->toBeNull();
    expect($medicao->getRawOriginal('iqa'))->not->toBeNull();
});

test('calculo do iqa por interpolacao linear segue os pontos de corte da tabela', function () {
    // 1. Boa (0-50): PM2.5 = 12.5 (I=25), CO2 = 350 (I=25) -> IQA = 25
    expect(Medicao::calcularIqa(12.5, 350))->toBe(25);

    // 2. Moderada (51-100): PM2.5 = 60.0 (limite sup, I=100), CO2 = 700 (I=50) -> IQA = 100
    expect(Medicao::calcularIqa(60.0, 700))->toBe(100);

    // 3. Ruim (101-150): PM2.5 = 125.0 (limite sup, I=150), CO2 = 1000 (I=100) -> IQA = 150
    expect(Medicao::calcularIqa(125.0, 1000))->toBe(150);

    // 4. Muito Ruim (151-200): PM2.5 = 210.0 (limite sup, I=200), CO2 = 1500 (I=150) -> IQA = 200
    expect(Medicao::calcularIqa(210.0, 1500))->toBe(200);

    // 5. Péssima (>200): PM2.5 = 250.0 (>210) -> IQA > 200
    expect(Medicao::calcularIqa(250.0, 400))->toBeGreaterThan(200);

    // 6. Prevalência do CO2 quando mais crítico: PM2.5 = 10.0 (Boa), CO2 = 2500 (limite Muito Ruim, I=200) -> IQA = 200
    expect(Medicao::calcularIqa(10.0, 2500))->toBe(200);
});

test('api armazena o valor de iqa calculado diretamente na coluna da tabela medicoes', function () {
    $user = User::factory()->create();
    $bairro = Bairro::factory()->create();

    $patrimonio = Patrimonio::factory()->create([
        'cidade_id' => $bairro->cidade_id,
        'numero_patrimonio' => 'OAir-Estacao-1-9999',
        'status' => 'Instalado',
        'created_by' => $user->id,
    ]);

    $estacao = Estacao::factory()->create([
        'created_by' => $user->id,
        'bairro_id' => $bairro->id,
        'patrimonio_id' => $patrimonio->private_id,
        'status_instalacao' => 'Instalada',
    ]);

    $payload = [
        'patrimonio' => 'OAir-Estacao-1-9999',
        'temperatura' => 25.0,
        'umidade' => 50.0,
        'co2' => 850, // Moderada: 51 + (49/300)*150 = 51 + 24.5 = 75.5 -> 76
        'poeira' => 20.0, // Boa: (50/25)*20 = 40
    ];

    $response = $this->postJson(route('api.medicoes.store'), $payload);

    $response->assertCreated();
    $response->assertJsonPath('data.iqa', 76);

    $medicao = Medicao::where('estacao_id', $estacao->private_id)->first();
    expect($medicao->getRawOriginal('iqa'))->toBe(76);
});

test('api dispara o evento NovaMedicaoRecebida para transmissao via websocket ao receber medicao', function () {
    Event::fake([NovaMedicaoRecebida::class]);

    $user = User::factory()->create();
    $bairro = Bairro::factory()->create();

    $patrimonio = Patrimonio::factory()->create([
        'cidade_id' => $bairro->cidade_id,
        'numero_patrimonio' => 'OAir-Estacao-WS-001',
        'status' => 'Instalado',
        'created_by' => $user->id,
    ]);

    $estacao = Estacao::factory()->create([
        'created_by' => $user->id,
        'bairro_id' => $bairro->id,
        'patrimonio_id' => $patrimonio->private_id,
        'status_instalacao' => 'Instalada',
        'latitude' => -21.967194,
        'longitude' => -46.812740,
    ]);

    $payload = [
        'patrimonio' => 'OAir-Estacao-WS-001',
        'temperatura' => 22.8,
        'umidade' => 58.0,
        'co2' => 420,
        'poeira' => 15.0,
    ];

    $response = $this->postJson(route('api.medicoes.store'), $payload);
    $response->assertCreated();

    Event::assertDispatched(NovaMedicaoRecebida::class, function ($event) use ($estacao) {
        $channels = $event->broadcastOn();
        expect($channels)->toHaveCount(1);
        expect($channels[0]->name)->toBe('medicoes');
        expect($event->broadcastAs())->toBe('NovaMedicaoRecebida');

        $data = $event->broadcastWith();
        expect($data['estacao_id'])->toBe($estacao->public_id);
        expect($data['patrimonio'])->toBe('OAir-Estacao-WS-001');
        expect($data['lat'])->toBe(-21.967194);
        expect($data['lng'])->toBe(-46.812740);
        expect($data['temperatura'])->toBe(22.8);
        expect($data['umidade'])->toBe(58.0);
        expect($data['co2'])->toBe(420);
        expect($data['poeira'])->toBe(15.0);
        expect($data['data_hora'])->not->toBeEmpty();

        return true;
    });
});
