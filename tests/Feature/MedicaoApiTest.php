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

beforeEach(function () {
    config(['services.telemetry.key' => null]);
});

test('estacao em campo pode enviar medicoes com sucesso via mac_address', function () {
    $user = User::factory()->create();
    $bairro = Bairro::factory()->create();

    $estacao = Estacao::factory()->create([
        'created_by' => $user->id,
        'bairro_id' => $bairro->id,
        'mac_address' => '24:6F:28:AB:CD:EF',
        'status_instalacao' => 'Instalada',
    ]);

    $payload = [
        'mac_address' => '24:6F:28:AB:CD:EF',
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
            'mac_address' => '24:6F:28:AB:CD:EF',
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

test('estacao em campo pode enviar medicoes com mac_address em diferentes formatos (hifen, minusculas, sem separador)', function (string $macEnviado) {
    $user = User::factory()->create();
    $bairro = Bairro::factory()->create();

    $estacao = Estacao::factory()->create([
        'created_by' => $user->id,
        'bairro_id' => $bairro->id,
        'mac_address' => 'A1:B2:C3:D4:E5:F6',
        'status_instalacao' => 'Instalada',
    ]);

    $payload = [
        'mac_address' => $macEnviado,
        'temperatura' => 22.0,
        'umidade' => 60.0,
        'co2' => 400,
        'poeira' => 10.0,
    ];

    $response = $this->postJson(route('api.medicoes.store'), $payload);

    $response->assertCreated();
    expect(Medicao::where('estacao_id', $estacao->private_id)->count())->toBe(1);
})->with([
    'formato com dois pontos minusculo' => 'a1:b2:c3:d4:e5:f6',
    'formato com hifens' => 'A1-B2-C3-D4-E5-F6',
    'formato com hifens minusculo' => 'a1-b2-c3-d4-e5-f6',
    'formato continuo sem separador' => 'A1B2C3D4E5F6',
    'formato continuo sem separador minusculo' => 'a1b2c3d4e5f6',
]);

test('estacao em campo pode ser identificada pelo mac_address do patrimonio vinculado', function () {
    $user = User::factory()->create();
    $bairro = Bairro::factory()->create();

    $patrimonio = Patrimonio::factory()->create([
        'cidade_id' => $bairro->cidade_id,
        'numero_patrimonio' => 'OAir-Estacao-1-0001',
        'mac_address' => '11:22:33:44:55:66',
        'status' => 'Instalada',
        'created_by' => $user->id,
    ]);

    $estacao = Estacao::factory()->create([
        'created_by' => $user->id,
        'bairro_id' => $bairro->id,
        'patrimonio_id' => $patrimonio->private_id,
        'mac_address' => null,
        'status_instalacao' => 'Instalada',
    ]);

    $payload = [
        'mac_address' => '11:22:33:44:55:66',
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

    $estacao = Estacao::factory()->create([
        'created_by' => $user->id,
        'bairro_id' => $bairro->id,
        'mac_address' => '24:6F:28:00:00:02',
        'status_instalacao' => 'Instalada',
    ]);

    $payload = [
        'mac_address' => '24:6F:28:00:00:02',
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

test('envio de medicao retorna 404 se estacao nao for encontrada por mac_address', function () {
    $payload = [
        'mac_address' => 'FF:EE:DD:CC:BB:AA',
        'temperatura' => 20.0,
        'umidade' => 50.0,
        'co2' => 400,
        'poeira' => 10.0,
    ];

    $response = $this->postJson(route('api.medicoes.store'), $payload);

    $response->assertNotFound();
    $response->assertJson([
        'success' => false,
        'message' => 'Estação não encontrada com o endereço MAC informado.',
    ]);
});

test('envio de medicao retorna 422 se estacao ainda nao estiver com status Instalada', function () {
    $user = User::factory()->create();
    $bairro = Bairro::factory()->create();

    $estacao = Estacao::factory()->create([
        'created_by' => $user->id,
        'bairro_id' => $bairro->id,
        'mac_address' => '24:6F:28:00:00:04',
        'status_instalacao' => 'Planejada',
    ]);

    $payload = [
        'mac_address' => '24:6F:28:00:00:04',
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

test('envio de medicao valida campos obrigatorios incluindo mac_address', function () {
    $response = $this->postJson(route('api.medicoes.store'), []);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['mac_address', 'temperatura', 'umidade', 'co2', 'poeira']);
});

test('envio de medicao rejeita formato invalido de mac_address', function () {
    $response = $this->postJson(route('api.medicoes.store'), [
        'mac_address' => 'formato-invalido',
        'temperatura' => 25.0,
        'umidade' => 50.0,
        'co2' => 400,
        'poeira' => 10.0,
    ]);

    $response->assertUnprocessable();
    $response->assertJsonValidationErrors(['mac_address']);
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
    // 1. Boa (0-40): PM2.5 = 7.5 (I=20), CO2 = 350 (I=20) -> IQA = 20
    expect(Medicao::calcularIqa(7.5, 350))->toBe(20);
    // Limite superior de Boa: PM2.5 = 15.0 -> IQA = 40
    expect(Medicao::calcularIqa(15.0, 500))->toBe(40);

    // 2. Moderada (41-80): PM2.5 = 25.0 (I=52), CO2 = 500 (I=29) -> IQA = 52
    expect(Medicao::calcularIqa(25.0, 500))->toBe(52);
    // Limite superior de Moderada: PM2.5 = 50.0 (I=80), CO2 = 700 (I=40) -> IQA = 80
    expect(Medicao::calcularIqa(50.0, 700))->toBe(80);

    // 3. Ruim (81-120): PM2.5 = 75.0 (limite sup, I=120), CO2 = 1000 (I=80) -> IQA = 120
    expect(Medicao::calcularIqa(75.0, 1000))->toBe(120);

    // 4. Muito Ruim (121-200): PM2.5 = 125.0 (limite sup, I=200), CO2 = 1500 (I=120) -> IQA = 200
    expect(Medicao::calcularIqa(125.0, 1500))->toBe(200);

    // 5. Péssima (201-400): PM2.5 = 200.0 (>125) -> IQA > 200; limite sup 300.0 -> IQA = 400
    expect(Medicao::calcularIqa(200.0, 400))->toBeGreaterThan(200);
    expect(Medicao::calcularIqa(300.0, 400))->toBe(400);

    // 6. Prevalência do CO2 quando mais crítico (exemplo prático: PM2.5=25.0 => 52, CO2=1200 => 97)
    expect(Medicao::calcularIqa(25.0, 1200))->toBe(97);
    expect(Medicao::calcularIqa(10.0, 2500))->toBe(200);
});

test('api armazena o valor de iqa calculado diretamente na coluna da tabela medicoes', function () {
    $user = User::factory()->create();
    $bairro = Bairro::factory()->create();

    $estacao = Estacao::factory()->create([
        'created_by' => $user->id,
        'bairro_id' => $bairro->id,
        'mac_address' => '24:6F:28:99:99:99',
        'status_instalacao' => 'Instalada',
    ]);

    $payload = [
        'mac_address' => '24:6F:28:99:99:99',
        'temperatura' => 25.0,
        'umidade' => 50.0,
        'co2' => 850, // Moderada: 41 + (39/300)*150 = 41 + 19.5 = 60.5 -> 61
        'poeira' => 20.0, // Moderada: 41 + (39/35)*5 = 46.57 -> 47
    ];

    $response = $this->postJson(route('api.medicoes.store'), $payload);

    $response->assertCreated();
    $response->assertJsonPath('data.iqa', 61);

    $medicao = Medicao::where('estacao_id', $estacao->private_id)->first();
    expect($medicao->getRawOriginal('iqa'))->toBe(61);
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
        'mac_address' => '24:6F:28:AA:BB:CC',
        'status_instalacao' => 'Instalada',
        'latitude' => -21.967194,
        'longitude' => -46.812740,
    ]);

    $payload = [
        'mac_address' => '24:6F:28:AA:BB:CC',
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

test('rejeita medicao com 401 se TELEMETRY_API_KEY estiver configurada e header X-Sensor-Key for invalido ou ausente', function () {
    config(['services.telemetry.key' => 'segredo-teste-123']);

    $estacao = Estacao::factory()->create([
        'mac_address' => '24:6F:28:11:22:33',
        'status_instalacao' => 'Instalada',
    ]);
    $payload = [
        'mac_address' => '24:6F:28:11:22:33',
        'temperatura' => 24.5,
        'umidade' => 65.0,
        'co2' => 450,
        'poeira' => 12.3,
    ];

    // Sem header
    $this->postJson(route('api.medicoes.store'), $payload)->assertStatus(401);

    // Com chave incorreta
    $this->withHeader('X-Sensor-Key', 'chave-errada')->postJson(route('api.medicoes.store'), $payload)->assertStatus(401);

    // Com chave correta
    $this->withHeader('X-Sensor-Key', 'segredo-teste-123')->postJson(route('api.medicoes.store'), $payload)->assertStatus(201);
});
