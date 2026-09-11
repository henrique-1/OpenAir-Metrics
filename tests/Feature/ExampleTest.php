<?php

use App\Models\Bairro;
use App\Models\Estacao;
use App\Models\Medicao;
use App\Models\Patrimonio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a tela inicial pública retorna status 200 sem estacoes cadastradas', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertViewIs('home');
    $response->assertViewHasAll([
        'dadosIqa',
        'dadosTemperatura',
        'dadosUmidade',
        'dadosPm',
        'dadosCo2',
        'centroMapa',
    ]);
});

test('a tela inicial pública carrega dados dos sensores persistidos no banco', function () {
    $user = User::factory()->create();
    $bairro = Bairro::factory()->create();
    $patrimonio = Patrimonio::factory()->create([
        'cidade_id' => $bairro->cidade_id,
        'created_by' => $user->id,
    ]);

    $estacao = Estacao::factory()->create([
        'created_by' => $user->id,
        'bairro_id' => $bairro->id,
        'patrimonio_id' => $patrimonio->private_id,
        'latitude' => -21.967194,
        'longitude' => -46.812740,
    ]);

    Medicao::create([
        'estacao_id' => $estacao->private_id,
        'temperatura' => 24.5,
        'umidade' => 60.0,
        'co2' => 450,
        'poeira' => 18.2,
        'iqa' => 62,
        'data_hora' => now(),
    ]);

    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertViewHas('dadosIqa', function ($dados) {
        return count($dados) === 1
            && $dados[0]['value'] === 62
            && $dados[0]['lat'] == -21.967194
            && ! empty($dados[0]['data_hora'])
            && $dados[0]['data_hora'] === now()->format('d/m/Y H:i');
    });
    $response->assertViewHas('dadosTemperatura', function ($dados) {
        return count($dados) === 1 && $dados[0]['value'] === 24.5;
    });
    $response->assertViewHas('dadosUmidade', function ($dados) {
        return count($dados) === 1 && $dados[0]['value'] === 60.0;
    });
    $response->assertViewHas('dadosCo2', function ($dados) {
        return count($dados) === 1 && $dados[0]['value'] === 450;
    });
    $response->assertViewHas('dadosPm', function ($dados) {
        return count($dados) === 1 && $dados[0]['value'] === 18.2;
    });
});
