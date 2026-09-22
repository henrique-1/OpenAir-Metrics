<?php

use App\Models\Bairro;
use App\Models\Estacao;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('telemetry endpoint enforces rate limiting against unauthenticated flood', function () {
    $bairro = Bairro::factory()->create();
    $estacao = Estacao::factory()->create([
        'bairro_id' => $bairro->id,
        'status_instalacao' => 'Instalada',
    ]);

    // 60 requests should pass under standard throttle
    for ($i = 0; $i < 60; $i++) {
        $this->postJson(route('api.medicoes.store'), [
            'estacao_id' => $estacao->public_id,
            'temperatura' => 24.0,
            'umidade' => 50.0,
            'co2' => 400,
            'poeira' => 10.0,
        ])->assertCreated();
    }

    // 61st request within 1 minute must be rejected
    $response = $this->postJson(route('api.medicoes.store'), [
        'estacao_id' => $estacao->public_id,
        'temperatura' => 24.0,
        'umidade' => 50.0,
        'co2' => 400,
        'poeira' => 10.0,
    ]);

    $response->assertStatus(429);
});
