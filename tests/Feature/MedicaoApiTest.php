<?php

use App\Models\Bairro;
use App\Models\Estacao;
use App\Models\Medicao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('estacao em campo pode enviar medicoes com sucesso via mac_address', function () {
    $user = User::factory()->create();
    $bairro = Bairro::factory()->create();

    $estacao = Estacao::factory()->create([
        'created_by' => $user->id,
        'bairro_id' => $bairro->id,
        'mac_address' => 'AA:BB:CC:DD:EE:01',
        'status_instalacao' => 'Instalada',
    ]);

    $payload = [
        'mac_address' => 'AA:BB:CC:DD:EE:01',
        'temperatura' => 24.5,
        'umidade' => 65.0,
        'co2' => 450,
        'poeira' => 12.3,
        'data_hora' => now()->toIso8601String(),
    ];

    $response = $this->postJson(route('api.medicoes.store'), $payload);

    $response->assertCreated();
    $response->assertJson([
        'success' => true,
        'message' => 'Medição registrada com sucesso.',
        'data' => [
            'estacao_id' => $estacao->public_id,
            'mac_address' => 'AA:BB:CC:DD:EE:01',
            'temperatura' => 24.5,
            'umidade' => 65.0,
            'co2' => 450,
            'poeira' => 12.3,
        ],
    ]);

    expect(Medicao::where('estacao_id', $estacao->private_id)->count())->toBe(1);
    $medicao = Medicao::where('estacao_id', $estacao->private_id)->first();
    expect($medicao->iqa)->not->toBeNull();
});

test('estacao em campo pode enviar medicoes com mac_address sem formatacao', function () {
    $user = User::factory()->create();
    $bairro = Bairro::factory()->create();

    $estacao = Estacao::factory()->create([
        'created_by' => $user->id,
        'bairro_id' => $bairro->id,
        'mac_address' => 'AA:BB:CC:DD:EE:02',
        'status_instalacao' => 'Instalada',
    ]);

    $payload = [
        'mac_address' => 'aabbccddee02',
        'temperatura' => 22.0,
        'umidade' => 60.0,
        'co2' => 400,
        'poeira' => 10.0,
    ];

    $response = $this->postJson(route('api.medicoes.store'), $payload);

    $response->assertCreated();
    expect(Medicao::where('estacao_id', $estacao->private_id)->count())->toBe(1);
});

test('estacao em campo pode enviar medicoes via estacao_id publico', function () {
    $user = User::factory()->create();
    $bairro = Bairro::factory()->create();

    $estacao = Estacao::factory()->create([
        'created_by' => $user->id,
        'bairro_id' => $bairro->id,
        'mac_address' => 'AA:BB:CC:DD:EE:03',
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

test('envio de medicao retorna 404 se estacao nao for encontrada', function () {
    $payload = [
        'mac_address' => '00:00:00:00:00:00',
        'temperatura' => 20.0,
        'umidade' => 50.0,
        'co2' => 400,
        'poeira' => 10.0,
    ];

    $response = $this->postJson(route('api.medicoes.store'), $payload);

    $response->assertNotFound();
    $response->assertJson([
        'success' => false,
        'message' => 'Estação não encontrada com o identificador ou MAC Address informado.',
    ]);
});

test('envio de medicao retorna 422 se estacao ainda nao estiver com status Instalada', function () {
    $user = User::factory()->create();
    $bairro = Bairro::factory()->create();

    Estacao::factory()->create([
        'created_by' => $user->id,
        'bairro_id' => $bairro->id,
        'mac_address' => 'AA:BB:CC:DD:EE:04',
        'status_instalacao' => 'Planejada',
    ]);

    $payload = [
        'mac_address' => 'AA:BB:CC:DD:EE:04',
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
    $response->assertJsonValidationErrors(['temperatura', 'umidade', 'co2', 'poeira']);
});
