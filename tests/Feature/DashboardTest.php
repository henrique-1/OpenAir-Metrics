<?php

use App\Models\Bairro;
use App\Models\Cidade;
use App\Models\Estacao;
use App\Models\Estado;
use App\Models\Medicao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('visitante nao autenticado e redirecionado ao tentar acessar o dashboard', function () {
    $response = $this->get(route('dashboard'));

    $response->assertRedirect(route('login'));
});

test('usuario autenticado pode visualizar o dashboard com painel analitico', function () {
    $user = User::factory()->create();
    $estado = Estado::factory()->create(['nome' => 'São Paulo', 'uf' => 'SP']);
    $cidade = Cidade::factory()->create(['estado_id' => $estado->id, 'nome' => 'São João da Boa Vista']);
    $bairro = Bairro::factory()->create(['cidade_id' => $cidade->id, 'nome' => 'Centro']);

    $estacao = Estacao::factory()->create([
        'created_by' => $user->id,
        'bairro_id' => $bairro->id,
    ]);

    Medicao::factory()->count(5)->create([
        'estacao_id' => $estacao->private_id,
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertSee('Painel Analítico de Monitoramento');
    $response->assertSee('São João da Boa Vista');
    $response->assertSee('Qualidade do Ar (IQA)');
    $response->assertDontSee('Intervalo:');
    $response->assertDontSee('Automático');
});

test('api de graficos do dashboard calcula media, maximo e minimo por cidade', function () {
    $user = User::factory()->create();
    $estado = Estado::factory()->create();
    $cidade = Cidade::factory()->create(['estado_id' => $estado->id]);
    $bairro = Bairro::factory()->create(['cidade_id' => $cidade->id]);

    $estacao = Estacao::factory()->create(['bairro_id' => $bairro->id]);

    // Cria 3 medições com temperaturas conhecidas (20, 25, 30 => Média: 25.0, Min: 20.0, Max: 30.0)
    Medicao::factory()->create([
        'estacao_id' => $estacao->private_id,
        'temperatura' => 20.0,
        'data_hora' => now()->subHours(3),
    ]);
    Medicao::factory()->create([
        'estacao_id' => $estacao->private_id,
        'temperatura' => 25.0,
        'data_hora' => now()->subHours(2),
    ]);
    Medicao::factory()->create([
        'estacao_id' => $estacao->private_id,
        'temperatura' => 30.0,
        'data_hora' => now()->subHour(),
    ]);

    $response = $this->actingAs($user)->getJson(route('dashboard.graficos', [
        'tipo_agrupamento' => 'cidade',
        'localidade_id' => $cidade->id,
        'metrica' => 'temperatura',
        'periodo' => '24h',
    ]));

    $response->assertOk();
    $response->assertJson([
        'metrica' => 'temperatura',
        'unidade' => '°C',
        'media' => 25.0,
        'maximo' => 30.0,
        'minimo' => 20.0,
        'total_leituras' => 3,
    ]);
    expect($response->json('valores'))->toHaveCount(3);
    expect($response->json('maximos'))->toHaveCount(3);
    expect($response->json('minimos'))->toHaveCount(3);
});

test('api de graficos calcula maximo, minimo e media para multiplas estacoes no mesmo intervalo de tempo', function () {
    $user = User::factory()->create();
    $cidade = Cidade::factory()->create();
    $bairro = Bairro::factory()->create(['cidade_id' => $cidade->id]);

    $estacao1 = Estacao::factory()->create(['bairro_id' => $bairro->id]);
    $estacao2 = Estacao::factory()->create(['bairro_id' => $bairro->id]);

    $mesmoMomento = now()->subHour();

    Medicao::factory()->create([
        'estacao_id' => $estacao1->private_id,
        'temperatura' => 18.0,
        'data_hora' => $mesmoMomento,
    ]);
    Medicao::factory()->create([
        'estacao_id' => $estacao2->private_id,
        'temperatura' => 26.0,
        'data_hora' => $mesmoMomento,
    ]);

    $response = $this->actingAs($user)->getJson(route('dashboard.graficos', [
        'tipo_agrupamento' => 'cidade',
        'localidade_id' => $cidade->id,
        'metrica' => 'temperatura',
        'periodo' => '24h',
    ]));

    $response->assertOk();
    $response->assertJson([
        'media' => 22.0,
        'maximo' => 26.0,
        'minimo' => 18.0,
    ]);
    expect($response->json('valores'))->toEqual([22.0]);
    expect($response->json('maximos'))->toEqual([26.0]);
    expect($response->json('minimos'))->toEqual([18.0]);
});

test('api de graficos do dashboard calcula metricas por bairro', function () {
    $user = User::factory()->create();
    $bairro = Bairro::factory()->create();
    $estacao = Estacao::factory()->create(['bairro_id' => $bairro->id]);

    Medicao::factory()->create([
        'estacao_id' => $estacao->private_id,
        'umidade' => 50.0,
        'data_hora' => now()->subHours(2),
    ]);
    Medicao::factory()->create([
        'estacao_id' => $estacao->private_id,
        'umidade' => 70.0,
        'data_hora' => now()->subHour(),
    ]);

    $response = $this->actingAs($user)->getJson(route('dashboard.graficos', [
        'tipo_agrupamento' => 'bairro',
        'localidade_id' => $bairro->id,
        'metrica' => 'umidade',
        'periodo' => '24h',
    ]));

    $response->assertOk();
    $response->assertJson([
        'metrica' => 'umidade',
        'unidade' => '%',
        'media' => 60.0,
        'maximo' => 70.0,
        'minimo' => 50.0,
        'total_leituras' => 2,
    ]);
});

test('api de graficos calcula indice de qualidade do ar (IQA)', function () {
    $user = User::factory()->create();
    $bairro = Bairro::factory()->create();
    $estacao = Estacao::factory()->create(['bairro_id' => $bairro->id]);

    Medicao::factory()->create([
        'estacao_id' => $estacao->private_id,
        'poeira' => 15.0, // IQA 40 (limite superior da faixa N1 - Boa: 0 - 15.0)
        'co2' => 450,
        'data_hora' => now()->subHours(1),
    ]);

    $response = $this->actingAs($user)->getJson(route('dashboard.graficos', [
        'tipo_agrupamento' => 'bairro',
        'localidade_id' => $bairro->id,
        'metrica' => 'qualidade_ar',
        'periodo' => '24h',
    ]));

    $response->assertOk();
    $response->assertJson([
        'metrica' => 'qualidade_ar',
        'unidade' => 'IQA',
        'media' => 40.0,
        'total_leituras' => 1,
    ]);
});

test('api de graficos retorna estado vazio elegante quando nao ha dados', function () {
    $user = User::factory()->create();
    $cidade = Cidade::factory()->create();

    $response = $this->actingAs($user)->getJson(route('dashboard.graficos', [
        'tipo_agrupamento' => 'cidade',
        'localidade_id' => $cidade->id,
        'metrica' => 'co2',
        'periodo' => '24h',
    ]));

    $response->assertOk();
    $response->assertJson([
        'metrica' => 'co2',
        'total_leituras' => 0,
        'media' => 0.0,
        'labels' => [],
        'valores' => [],
        'maximos' => [],
        'minimos' => [],
    ]);
});

test('visitante nao autenticado nao pode acessar rota de graficos do dashboard', function () {
    $response = $this->getJson(route('dashboard.graficos'));

    $response->assertRedirect(route('login'));
});

test('dashboard escopa contadores e localidades pela jurisdicao municipal do usuario', function () {
    $cidade1 = Cidade::factory()->create(['nome' => 'Campinas']);
    $cidade2 = Cidade::factory()->create(['nome' => 'Santos']);

    $bairro1 = Bairro::factory()->create(['cidade_id' => $cidade1->id]);
    $bairro2 = Bairro::factory()->create(['cidade_id' => $cidade2->id]);

    $estacao1 = Estacao::factory()->create(['bairro_id' => $bairro1->id]);
    $estacao2 = Estacao::factory()->create(['bairro_id' => $bairro2->id]);

    Medicao::factory()->create(['estacao_id' => $estacao1->private_id]);
    Medicao::factory()->create(['estacao_id' => $estacao2->private_id]);

    $userCampinas = User::factory()->create([
        'cidade_id' => $cidade1->id,
        'name' => 'Campinas Admin',
        'email' => 'campinas.admin@example.com',
    ]);

    $response = $this->actingAs($userCampinas)->get(route('dashboard'));

    $response->assertOk();
    $response->assertViewHas('totalEstacoes', 1);
    $response->assertViewHas('totalLeituras', 1);
    $response->assertSee('Campinas');
    $response->assertDontSee('Santos');
});

test('api de graficos utiliza granularidade adaptativa minuto a minuto quando medicoes abrangem intervalo curto', function () {
    $user = User::factory()->create();
    $cidade = Cidade::factory()->create();
    $bairro = Bairro::factory()->create(['cidade_id' => $cidade->id]);
    $estacao = Estacao::factory()->create(['bairro_id' => $bairro->id]);

    $baseTime = now()->subMinutes(30);

    // Cria medicoes espacadas a cada 5 minutos ao longo de 30 minutos (7 pontos)
    for ($i = 0; $i <= 6; $i++) {
        Medicao::factory()->create([
            'estacao_id' => $estacao->private_id,
            'temperatura' => 20.0 + $i,
            'data_hora' => (clone $baseTime)->addMinutes($i * 5),
        ]);
    }

    $response = $this->actingAs($user)->getJson(route('dashboard.graficos', [
        'tipo_agrupamento' => 'cidade',
        'localidade_id' => $cidade->id,
        'metrica' => 'temperatura',
        'periodo' => '24h',
    ]));

    $response->assertOk();
    // Em vez de 1 ou 2 pontos agrupados por hora, preserva todos os 7 pontos com a variacao real
    expect($response->json('valores'))->toHaveCount(7);
    expect($response->json('labels'))->toHaveCount(7);
    expect($response->json('valores'))->toEqual([20.0, 21.0, 22.0, 23.0, 24.0, 25.0, 26.0]);
});

test('api de graficos calcula blocos estatisticos estritamente para o periodo selecionado', function () {
    $user = User::factory()->create();
    $cidade = Cidade::factory()->create();
    $bairro = Bairro::factory()->create(['cidade_id' => $cidade->id]);
    $estacao = Estacao::factory()->create(['bairro_id' => $bairro->id]);

    // Medicao 1: ha 2 horas (deve aparecer em 24h, 7d, 30d) - Temp 20
    Medicao::factory()->create([
        'estacao_id' => $estacao->private_id,
        'temperatura' => 20.0,
        'data_hora' => now()->subHours(2),
    ]);

    // Medicao 2: ha 3 dias (deve aparecer em 7d, 30d, mas NAO em 24h) - Temp 40
    Medicao::factory()->create([
        'estacao_id' => $estacao->private_id,
        'temperatura' => 40.0,
        'data_hora' => now()->subDays(3),
    ]);

    // Medicao 3: ha 15 dias (deve aparecer APENAS em 30d) - Temp 60
    Medicao::factory()->create([
        'estacao_id' => $estacao->private_id,
        'temperatura' => 60.0,
        'data_hora' => now()->subDays(15),
    ]);

    // 1. Consulta 24 Horas
    $res24h = $this->actingAs($user)->getJson(route('dashboard.graficos', [
        'tipo_agrupamento' => 'cidade',
        'localidade_id' => $cidade->id,
        'metrica' => 'temperatura',
        'periodo' => '24h',
    ]));
    $res24h->assertOk()->assertJson([
        'media' => 20.0,
        'maximo' => 20.0,
        'minimo' => 20.0,
        'total_leituras' => 1,
        'periodo' => '24h',
        'periodo_rotulo' => 'nas últimas 24 horas',
    ]);

    // 2. Consulta 7 Dias
    $res7d = $this->actingAs($user)->getJson(route('dashboard.graficos', [
        'tipo_agrupamento' => 'cidade',
        'localidade_id' => $cidade->id,
        'metrica' => 'temperatura',
        'periodo' => '7d',
    ]));
    $res7d->assertOk()->assertJson([
        'media' => 30.0,
        'maximo' => 40.0,
        'minimo' => 20.0,
        'total_leituras' => 2,
        'periodo' => '7d',
        'periodo_rotulo' => 'nos últimos 7 dias',
    ]);

    // 3. Consulta 30 Dias
    $res30d = $this->actingAs($user)->getJson(route('dashboard.graficos', [
        'tipo_agrupamento' => 'cidade',
        'localidade_id' => $cidade->id,
        'metrica' => 'temperatura',
        'periodo' => '30d',
    ]));
    $res30d->assertOk()->assertJson([
        'media' => 40.0,
        'maximo' => 60.0,
        'minimo' => 20.0,
        'total_leituras' => 3,
        'periodo' => '30d',
        'periodo_rotulo' => 'nos últimos 30 dias',
    ]);
});

test('api de graficos agrupa automaticamente a cada 5 minutos quando medicoes abrangem entre 2 e 6 horas', function () {
    $user = User::factory()->create();
    $cidade = Cidade::factory()->create();
    $bairro = Bairro::factory()->create(['cidade_id' => $cidade->id]);
    $estacao = Estacao::factory()->create(['bairro_id' => $bairro->id]);

    $baseTime = now()->startOfHour()->subHours(3);

    // 14:01 e 14:03 caem no mesmo bucket de 5 minutos (14:00)
    Medicao::factory()->create([
        'estacao_id' => $estacao->private_id,
        'temperatura' => 20.0,
        'data_hora' => (clone $baseTime)->addMinute(),
    ]);
    Medicao::factory()->create([
        'estacao_id' => $estacao->private_id,
        'temperatura' => 22.0,
        'data_hora' => (clone $baseTime)->addMinutes(3),
    ]);

    // Uma medição no bucket 14:05 (14:05 - 14:09)
    Medicao::factory()->create([
        'estacao_id' => $estacao->private_id,
        'temperatura' => 25.0,
        'data_hora' => (clone $baseTime)->addMinutes(7),
    ]);

    // Medição que estende o span para 3 horas (180 min, faixa <= 360 min)
    Medicao::factory()->create([
        'estacao_id' => $estacao->private_id,
        'temperatura' => 30.0,
        'data_hora' => (clone $baseTime)->addHours(3),
    ]);

    $response = $this->actingAs($user)->getJson(route('dashboard.graficos', [
        'tipo_agrupamento' => 'cidade',
        'localidade_id' => $cidade->id,
        'metrica' => 'temperatura',
        'periodo' => '24h',
    ]));

    $response->assertOk();
    // As duas medições no mesmo bloco de 5m foram consolidadas com média 21.0
    expect($response->json('valores')[0])->toEqual(21.0);
    expect($response->json('minimos')[0])->toEqual(20.0);
    expect($response->json('maximos')[0])->toEqual(22.0);
});

test('dashboard exibe contadores e cards dos 5 tipos de alerta ambiental', function () {
    $user = User::factory()->create();
    $cidade = Cidade::factory()->create();
    $bairro = Bairro::factory()->create(['cidade_id' => $cidade->id]);
    $estacao = Estacao::factory()->create(['bairro_id' => $bairro->id]);

    // Medição 1: Alerta de Temperatura (30 °C - Estresse Térmico) e Umidade (25% - Ar Seco)
    Medicao::factory()->create([
        'estacao_id' => $estacao->private_id,
        'temperatura' => 30.0,
        'umidade' => 25.0,
        'poeira' => 10.0,
        'co2' => 500,
        'data_hora' => now()->subHours(2),
    ]);

    // Medição 2: Alerta de Particulado e IQA (60 µg/m³) e CO2 (1200 ppm)
    Medicao::factory()->create([
        'estacao_id' => $estacao->private_id,
        'temperatura' => 22.0,
        'umidade' => 50.0,
        'poeira' => 60.0,
        'co2' => 1200,
        'data_hora' => now()->subHour(),
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertSee('Alertas Qualidade (IQA)');
    $response->assertSee('Alertas Particulado');
    $response->assertSee('Alertas Temperatura');
    $response->assertSee('Alertas Umidade');
    $response->assertSee('Alertas CO₂');
    $response->assertViewHas('alertasTemp', 1);
    $response->assertViewHas('alertasUmidade', 1);
    $response->assertViewHas('alertasPm', 1);
    $response->assertViewHas('alertasCo2', 1);
    $response->assertViewHas('alertasIqa', 1);
});

test('api de graficos retorna faixas de alerta e status de criticidade dos pontos', function () {
    $user = User::factory()->create();
    $cidade = Cidade::factory()->create();
    $bairro = Bairro::factory()->create(['cidade_id' => $cidade->id]);
    $estacao = Estacao::factory()->create(['bairro_id' => $bairro->id]);

    Medicao::factory()->create([
        'estacao_id' => $estacao->private_id,
        'temperatura' => 31.0, // Alerta Crítico - Estresse Térmico
        'data_hora' => now()->subHours(1),
    ]);

    $response = $this->actingAs($user)->getJson(route('dashboard.graficos', [
        'tipo_agrupamento' => 'cidade',
        'localidade_id' => $cidade->id,
        'metrica' => 'temperatura',
        'periodo' => '24h',
    ]));

    $response->assertOk();
    $response->assertJsonStructure([
        'faixas_alerta' => [
            '*' => ['min', 'max', 'label', 'nivel', 'cor', 'bg_cor'],
        ],
        'alertas_pontos' => [
            '*' => ['nivel', 'peso', 'status', 'classe', 'descricao', 'cor'],
        ],
    ]);

    expect($response->json('faixas_alerta'))->not->toBeEmpty();
    expect($response->json('alertas_pontos.0.nivel'))->toBe('critico');
    expect($response->json('alertas_pontos.0.status'))->toContain('Estresse Térmico');
});

test('api de graficos retorna contadores de alertas ambientais filtrados pelo periodo selecionado', function () {
    $user = User::factory()->create();
    $cidade = Cidade::factory()->create();
    $bairro = Bairro::factory()->create(['cidade_id' => $cidade->id]);
    $estacao = Estacao::factory()->create(['bairro_id' => $bairro->id]);

    // Medição 1 (recente, últimas 24h): gera alerta de temperatura (30°C) e PM (60 µg/m³)
    Medicao::factory()->create([
        'estacao_id' => $estacao->private_id,
        'temperatura' => 30.0,
        'umidade' => 50.0,
        'poeira' => 60.0,
        'co2' => 500,
        'data_hora' => now()->subHours(2),
    ]);

    // Medição 2 (antiga, de 3 dias atrás): gera outro alerta de temperatura (32°C) e de umidade (25%)
    Medicao::factory()->create([
        'estacao_id' => $estacao->private_id,
        'temperatura' => 32.0,
        'umidade' => 25.0,
        'poeira' => 10.0,
        'co2' => 500,
        'data_hora' => now()->subDays(3),
    ]);

    // 1. Consulta para 24 horas: deve contar apenas a Medição 1
    $response24h = $this->actingAs($user)->getJson(route('dashboard.graficos', [
        'tipo_agrupamento' => 'cidade',
        'localidade_id' => $cidade->id,
        'metrica' => 'temperatura',
        'periodo' => '24h',
    ]));

    $response24h->assertOk();
    $response24h->assertJsonStructure([
        'contadores_alertas' => ['iqa', 'pm', 'temperatura', 'umidade', 'co2'],
    ]);
    expect($response24h->json('contadores_alertas.temperatura'))->toBe(1);
    expect($response24h->json('contadores_alertas.pm'))->toBe(1);
    expect($response24h->json('contadores_alertas.umidade'))->toBe(0);

    // 2. Consulta para 7 dias: deve abranger ambas as medições
    $response7d = $this->actingAs($user)->getJson(route('dashboard.graficos', [
        'tipo_agrupamento' => 'cidade',
        'localidade_id' => $cidade->id,
        'metrica' => 'temperatura',
        'periodo' => '7d',
    ]));

    $response7d->assertOk();
    expect($response7d->json('contadores_alertas.temperatura'))->toBe(2);
    expect($response7d->json('contadores_alertas.pm'))->toBe(1);
    expect($response7d->json('contadores_alertas.umidade'))->toBe(1);
});

test('api de graficos calcula contadores de alertas especificos para o bairro selecionado dentro da jurisdicao fixa', function () {
    $cidade = Cidade::factory()->create(['nome' => 'Mogi Mirim']);
    $user = User::factory()->create(['cidade_id' => $cidade->id]);

    $bairroCentro = Bairro::factory()->create(['cidade_id' => $cidade->id, 'nome' => 'Centro']);
    $bairroVila = Bairro::factory()->create(['cidade_id' => $cidade->id, 'nome' => 'Vila Dias']);

    $estacaoCentro = Estacao::factory()->create(['bairro_id' => $bairroCentro->id]);
    $estacaoVila = Estacao::factory()->create(['bairro_id' => $bairroVila->id]);

    // Alerta no Centro: temperatura 32°C
    Medicao::factory()->create([
        'estacao_id' => $estacaoCentro->private_id,
        'temperatura' => 32.0,
        'umidade' => 50.0,
        'poeira' => 10.0,
        'co2' => 500,
        'data_hora' => now()->subHours(1),
    ]);

    // Alertas na Vila: temperatura 34°C e umidade 20%
    Medicao::factory()->create([
        'estacao_id' => $estacaoVila->private_id,
        'temperatura' => 34.0,
        'umidade' => 20.0,
        'poeira' => 10.0,
        'co2' => 500,
        'data_hora' => now()->subHours(1),
    ]);

    // 1. Agrupamento Cidade: congrega toda a jurisdição do gestor
    $resCidade = $this->actingAs($user)->getJson(route('dashboard.graficos', [
        'tipo_agrupamento' => 'cidade',
        'metrica' => 'temperatura',
        'periodo' => '24h',
    ]));

    $resCidade->assertOk();
    expect($resCidade->json('contadores_alertas.temperatura'))->toBe(2);
    expect($resCidade->json('contadores_alertas.umidade'))->toBe(1);
    expect($resCidade->json('localidade'))->toContain('Mogi Mirim');

    // 2. Agrupamento Bairro selecionando Centro: contadores restritos ao Centro
    $resCentro = $this->actingAs($user)->getJson(route('dashboard.graficos', [
        'tipo_agrupamento' => 'bairro',
        'localidade_id' => $bairroCentro->id,
        'metrica' => 'temperatura',
        'periodo' => '24h',
    ]));

    $resCentro->assertOk();
    expect($resCentro->json('contadores_alertas.temperatura'))->toBe(1);
    expect($resCentro->json('contadores_alertas.umidade'))->toBe(0);
    expect($resCentro->json('localidade'))->toBe('Bairro Centro');

    // 3. Agrupamento Bairro selecionando Vila Dias: contadores restritos à Vila Dias
    $resVila = $this->actingAs($user)->getJson(route('dashboard.graficos', [
        'tipo_agrupamento' => 'bairro',
        'localidade_id' => $bairroVila->id,
        'metrica' => 'temperatura',
        'periodo' => '24h',
    ]));

    $resVila->assertOk();
    expect($resVila->json('contadores_alertas.temperatura'))->toBe(1);
    expect($resVila->json('contadores_alertas.umidade'))->toBe(1);
    expect($resVila->json('localidade'))->toBe('Bairro Vila Dias');
});
