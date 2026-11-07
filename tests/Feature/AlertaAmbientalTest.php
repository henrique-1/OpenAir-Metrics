<?php

use App\Enums\NivelAlerta;
use App\Models\Bairro;
use App\Models\Cidade;
use App\Models\Estacao;
use App\Models\Estado;
use App\Models\Medicao;
use App\Services\AlertaAmbientalService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('alerta service avalia faixas de IQA corretamente', function () {
    $service = new AlertaAmbientalService;

    expect($service->avaliarIqa(30)['nivel'])->toBe(NivelAlerta::Nenhum);
    expect($service->avaliarIqa(40)['nivel'])->toBe(NivelAlerta::Nenhum);
    expect($service->avaliarIqa(41)['nivel'])->toBe(NivelAlerta::Atencao);
    expect($service->avaliarIqa(80)['nivel'])->toBe(NivelAlerta::Atencao);
    expect($service->avaliarIqa(85)['nivel'])->toBe(NivelAlerta::Critico);
    expect($service->avaliarIqa(120)['nivel'])->toBe(NivelAlerta::Critico);
    expect($service->avaliarIqa(125)['nivel'])->toBe(NivelAlerta::CriticoSevero);
    expect($service->avaliarIqa(200)['nivel'])->toBe(NivelAlerta::CriticoSevero);
    expect($service->avaliarIqa(250)['nivel'])->toBe(NivelAlerta::Emergencia);
});

test('alerta service avalia faixas de temperatura incluindo extremos e congelamento', function () {
    $service = new AlertaAmbientalService;

    // Abaixo de zero: Congelamento/Geada
    expect($service->avaliarTemperatura(-2.0)['nivel'])->toBe(NivelAlerta::Emergencia);
    expect($service->avaliarTemperatura(-2.0)['status'])->toContain('Congelamento/Geada');

    // 0 a 9.9: Frio Intenso
    expect($service->avaliarTemperatura(5.0)['nivel'])->toBe(NivelAlerta::CriticoSevero);

    // 10 a 17.9: Frio Severo
    expect($service->avaliarTemperatura(14.0)['nivel'])->toBe(NivelAlerta::Critico);

    // 18 a 19.9: Frio Leve (Atenção)
    expect($service->avaliarTemperatura(19.0)['nivel'])->toBe(NivelAlerta::Atencao);

    // 20 a 24: Equilíbrio (Sem Alerta)
    expect($service->avaliarTemperatura(22.0)['nivel'])->toBe(NivelAlerta::Nenhum);

    // 24.1 a 28: Calor Moderado (Atenção)
    expect($service->avaliarTemperatura(26.0)['nivel'])->toBe(NivelAlerta::Atencao);

    // 28.1 a 35: Estresse Térmico (Crítico)
    expect($service->avaliarTemperatura(30.0)['nivel'])->toBe(NivelAlerta::Critico);

    // Acima de 35: Calor Extremo (Crítico Severo)
    expect($service->avaliarTemperatura(36.5)['nivel'])->toBe(NivelAlerta::CriticoSevero);
});

test('alerta service avalia faixas de umidade para ar seco', function () {
    $service = new AlertaAmbientalService;

    // Abaixo de 20%: Emergência / Secura Severa
    expect($service->avaliarUmidade(15.0)['nivel'])->toBe(NivelAlerta::CriticoSevero);

    // 20 a 29.9%: Ar Seco Crítico
    expect($service->avaliarUmidade(25.0)['nivel'])->toBe(NivelAlerta::Critico);

    // 30 a 39.9%: Ar Seco Moderado
    expect($service->avaliarUmidade(35.0)['nivel'])->toBe(NivelAlerta::Atencao);

    // A partir de 40%: Saudável / Sem Alerta
    expect($service->avaliarUmidade(50.0)['nivel'])->toBe(NivelAlerta::Nenhum);
    expect($service->avaliarUmidade(70.0)['nivel'])->toBe(NivelAlerta::Nenhum);
    expect($service->avaliarUmidade(80.0)['nivel'])->toBe(NivelAlerta::Nenhum);
});

test('alerta service avalia material particulado PM2.5 e CO2', function () {
    $service = new AlertaAmbientalService;

    // PM2.5
    expect($service->avaliarPm(10.0)['nivel'])->toBe(NivelAlerta::Nenhum);
    expect($service->avaliarPm(25.0)['nivel'])->toBe(NivelAlerta::Atencao);
    expect($service->avaliarPm(60.0)['nivel'])->toBe(NivelAlerta::Critico);
    expect($service->avaliarPm(100.0)['nivel'])->toBe(NivelAlerta::CriticoSevero);
    expect($service->avaliarPm(150.0)['nivel'])->toBe(NivelAlerta::Emergencia);

    // CO2
    expect($service->avaliarCo2(500)['nivel'])->toBe(NivelAlerta::Nenhum);
    expect($service->avaliarCo2(850)['nivel'])->toBe(NivelAlerta::Atencao);
    expect($service->avaliarCo2(1200)['nivel'])->toBe(NivelAlerta::Critico);
    expect($service->avaliarCo2(1800)['nivel'])->toBe(NivelAlerta::CriticoSevero);
    expect($service->avaliarCo2(3000)['nivel'])->toBe(NivelAlerta::Emergencia);
});

test('home exibe painel flutuante de alertas quando ha estacoes com alertas ativos', function () {
    $estado = Estado::factory()->create();
    $cidade = Cidade::factory()->create(['estado_id' => $estado->id, 'nome' => 'Campinas']);
    $bairro = Bairro::factory()->create(['cidade_id' => $cidade->id, 'nome' => 'Cambuí']);

    $estacao = Estacao::factory()->create([
        'bairro_id' => $bairro->id,
        'latitude' => -22.900000,
        'longitude' => -47.050000,
    ]);

    // Cria medição em estado crítico de temperatura (32 °C - Estresse Térmico)
    Medicao::factory()->create([
        'estacao_id' => $estacao->private_id,
        'temperatura' => 32.0,
        'umidade' => 50.0,
        'poeira' => 10.0,
        'co2' => 450,
        'data_hora' => now(),
    ]);

    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('Alertas Ambientais Ativos');
    $response->assertSee('Cambuí');
    $response->assertSee('Alerta Crítico - Estresse Térmico');
});

test('home nao ativa alerta para umidade elevada acima de 40%', function () {
    $estado = Estado::factory()->create();
    $cidade = Cidade::factory()->create(['estado_id' => $estado->id, 'nome' => 'Campinas']);
    $bairro = Bairro::factory()->create(['cidade_id' => $cidade->id, 'nome' => 'Cambuí']);

    $estacao = Estacao::factory()->create([
        'bairro_id' => $bairro->id,
        'latitude' => -22.900000,
        'longitude' => -47.050000,
    ]);

    // Medição com 75% de umidade e demais valores em faixa normal/segura
    Medicao::factory()->create([
        'estacao_id' => $estacao->private_id,
        'temperatura' => 22.0,
        'umidade' => 75.0,
        'poeira' => 10.0,
        'co2' => 450,
        'data_hora' => now(),
    ]);

    $response = $this->get('/');

    $response->assertOk();
    $response->assertDontSee('Alertas Ambientais Ativos');
});
