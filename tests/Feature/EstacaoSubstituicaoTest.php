<?php

use App\Models\Bairro;
use App\Models\Cidade;
use App\Models\Estacao;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('calculo de vida util dos sensores baseado na especificacao tecnica de 5 anos', function () {
    // 1. Estação não instalada
    $estacaoNaoInstalada = new Estacao([
        'tipo_estacao' => 'Estação Matriz',
        'data_instalacao' => null,
    ]);
    $vida1 = $estacaoNaoInstalada->calcularVidaUtil();
    expect($vida1['instalada'])->toBeFalse();
    expect($vida1['status'])->toBe('Não Instalada');
    expect($vida1['anos_vida_util_nominal'])->toBe(5);

    // 2. Estação recém-instalada hoje
    $estacaoRecente = new Estacao([
        'tipo_estacao' => 'Estação Satélite',
        'data_instalacao' => Carbon::now(),
    ]);
    $vida2 = $estacaoRecente->calcularVidaUtil();
    expect($vida2['instalada'])->toBeTrue();
    expect($vida2['status'])->toBe('Normal');
    expect($vida2['porcentagem_restante'])->toBeGreaterThanOrEqual(99.0);

    // 3. Estação em estado crítico (< 6 meses de vida restante)
    $estacaoCritica = new Estacao([
        'tipo_estacao' => 'Estação Matriz',
        'data_instalacao' => Carbon::now()->subYears(4)->subMonths(8), // Restam ~4 meses
    ]);
    $vida3 = $estacaoCritica->calcularVidaUtil();
    expect($vida3['instalada'])->toBeTrue();
    expect($vida3['status'])->toContain('Crítica');

    // 4. Estação com vida útil expirada (> 5 anos)
    $estacaoVencida = new Estacao([
        'tipo_estacao' => 'Estação Satélite',
        'data_instalacao' => Carbon::now()->subYears(5)->subMonths(1),
    ]);
    $vida4 = $estacaoVencida->calcularVidaUtil();
    expect($vida4['instalada'])->toBeTrue();
    expect($vida4['status'])->toBe('Vencida');
    expect($vida4['dias_restantes'])->toBeLessThanOrEqual(0);
});

test('administrador ou cadastrador podem solicitar substituicao de sensores', function () {
    $admin = User::factory()->create(['nivel' => 'administrador']);
    $bairro = Bairro::factory()->create();

    $estacao = Estacao::factory()->create([
        'tipo_estacao' => 'Estação Matriz',
        'status_instalacao' => 'Instalada',
        'data_instalacao' => Carbon::now()->subYears(5),
        'bairro_id' => $bairro->id,
        'solicitacao_substituicao' => false,
    ]);

    $response = $this->actingAs($admin)->post(route('estacoes.solicitar-substituicao', $estacao->public_id), [
        'motivo_substituicao' => 'Vida útil atingiu 5 anos (Sensor MH-Z19C e DHT-22).',
    ]);

    $response->assertRedirect(route('estacoes.index'));
    $response->assertSessionHas('success');

    $estacao->refresh();
    expect($estacao->solicitacao_substituicao)->toBeTrue();
    expect($estacao->motivo_substituicao)->toBe('Vida útil atingiu 5 anos (Sensor MH-Z19C e DHT-22).');
    expect($estacao->solicitado_por)->toBe($admin->id);
    expect($estacao->solicitacao_substituicao_em)->not->toBeNull();
});

test('estacao nao instalada nao pode ter substituicao solicitada', function () {
    $admin = User::factory()->create(['nivel' => 'administrador']);
    $bairro = Bairro::factory()->create();

    $estacao = Estacao::factory()->create([
        'bairro_id' => $bairro->id,
        'data_instalacao' => null,
        'solicitacao_substituicao' => false,
    ]);

    $response = $this->actingAs($admin)->post(route('estacoes.solicitar-substituicao', $estacao->public_id), [
        'motivo_substituicao' => 'Tentando solicitar antes de instalar',
    ]);

    $response->assertRedirect(route('estacoes.index'));
    $response->assertSessionHas('error');

    $estacao->refresh();
    expect($estacao->solicitacao_substituicao)->toBeFalse();
});

test('substituicao com vida util maior que 20 por cento exige motivo obrigatorio', function () {
    $admin = User::factory()->create(['nivel' => 'administrador']);
    $bairro = Bairro::factory()->create();

    // Estação recém-instalada (vida útil quase 100% > 20%)
    $estacao = Estacao::factory()->create([
        'bairro_id' => $bairro->id,
        'data_instalacao' => Carbon::now()->subMonths(1),
        'solicitacao_substituicao' => false,
    ]);

    // Tentativa sem motivo deve falhar na validação
    $response = $this->actingAs($admin)->post(route('estacoes.solicitar-substituicao', $estacao->public_id), [
        'motivo_substituicao' => '',
    ]);

    $response->assertSessionHasErrors('motivo_substituicao');

    $estacao->refresh();
    expect($estacao->solicitacao_substituicao)->toBeFalse();

    // Tentativa com motivo preenchido deve passar
    $responseSuccess = $this->actingAs($admin)->post(route('estacoes.solicitar-substituicao', $estacao->public_id), [
        'motivo_substituicao' => 'Sensor com falha na leitura de CO2 após tempestade.',
    ]);

    $responseSuccess->assertRedirect(route('estacoes.index'));
    $responseSuccess->assertSessionHas('success');

    $estacao->refresh();
    expect($estacao->solicitacao_substituicao)->toBeTrue();
    expect($estacao->motivo_substituicao)->toBe('Sensor com falha na leitura de CO2 após tempestade.');
});

test('substituicao com vida util menor ou igual a 20 por cento preenche motivo automaticamente', function () {
    $admin = User::factory()->create(['nivel' => 'administrador']);
    $bairro = Bairro::factory()->create();

    // Estação com ~4 anos e 6 meses (restam menos de 6 meses de 5 anos nominal, vida <= 20%)
    $estacao = Estacao::factory()->create([
        'bairro_id' => $bairro->id,
        'data_instalacao' => Carbon::now()->subYears(4)->subMonths(6),
        'solicitacao_substituicao' => false,
    ]);

    // Sem motivo explícito enviado
    $response = $this->actingAs($admin)->post(route('estacoes.solicitar-substituicao', $estacao->public_id), [
        'motivo_substituicao' => '',
    ]);

    $response->assertRedirect(route('estacoes.index'));
    $response->assertSessionHas('success');

    $estacao->refresh();
    expect($estacao->solicitacao_substituicao)->toBeTrue();
    expect($estacao->motivo_substituicao)->toBe('Fim da vida útil da estação');
});

test('tela de estacoes exibe MAC Pendente de Instalacao e formata logradouro em Camel Case', function () {
    $admin = User::factory()->create(['nivel' => 'administrador']);
    $bairro = Bairro::factory()->create();

    $estacao = Estacao::factory()->create([
        'created_by' => $admin->id,
        'bairro_id' => $bairro->id,
        'mac_address' => null,
        'logradouro' => 'rua das acacias e flores',
        'data_instalacao' => null,
    ]);

    $response = $this->actingAs($admin)->get(route('estacoes.index'));

    $response->assertOk();
    $response->assertSee('MAC: Pendente de Instalação');
    $response->assertSee('Rua das Acacias e Flores');
});

test('planejador tecnico pode solicitar substituicao de sensores', function () {
    $cidade = Cidade::factory()->create();
    $bairro = Bairro::factory()->create(['cidade_id' => $cidade->id]);
    $planejador = User::factory()->create(['nivel' => 'cadastrador', 'cidade_id' => $cidade->id]);

    $estacao = Estacao::factory()->create([
        'tipo_estacao' => 'Estação Matriz',
        'status_instalacao' => 'Instalada',
        'data_instalacao' => Carbon::now()->subYears(5),
        'bairro_id' => $bairro->id,
        'solicitacao_substituicao' => false,
    ]);

    $response = $this->actingAs($planejador)->post(route('estacoes.solicitar-substituicao', $estacao->public_id), [
        'motivo_substituicao' => 'Substituição preventiva periódica solicitada pelo Planejador Técnico.',
    ]);

    $response->assertRedirect(route('estacoes.index'));
    $response->assertSessionHas('success');

    $estacao->refresh();
    expect($estacao->solicitacao_substituicao)->toBeTrue();
    expect($estacao->solicitado_por)->toBe($planejador->id);
});

test('planejador tecnico ou administrador nao pode solicitar substituicao para estacao de outro municipio', function () {
    $cidade1 = Cidade::factory()->create();
    $cidade2 = Cidade::factory()->create();

    $bairroCidade2 = Bairro::factory()->create(['cidade_id' => $cidade2->id]);
    $planejadorCidade1 = User::factory()->create(['nivel' => 'cadastrador', 'cidade_id' => $cidade1->id]);

    $estacaoCidade2 = Estacao::factory()->create([
        'tipo_estacao' => 'Estação Matriz',
        'status_instalacao' => 'Instalada',
        'data_instalacao' => Carbon::now()->subYears(5),
        'bairro_id' => $bairroCidade2->id,
        'solicitacao_substituicao' => false,
    ]);

    $response = $this->actingAs($planejadorCidade1)->post(route('estacoes.solicitar-substituicao', $estacaoCidade2->public_id), [
        'motivo_substituicao' => 'Tentativa indevida fora da jurisdição',
    ]);

    $response->assertForbidden();

    $estacaoCidade2->refresh();
    expect($estacaoCidade2->solicitacao_substituicao)->toBeFalse();
});

test('tela de estacoes renderiza botao de solicitar substituicao para planejador tecnico', function () {
    $cidade = Cidade::factory()->create();
    $bairro = Bairro::factory()->create(['cidade_id' => $cidade->id]);
    $planejador = User::factory()->create(['nivel' => 'cadastrador', 'cidade_id' => $cidade->id]);

    $estacao = Estacao::factory()->create([
        'tipo_estacao' => 'Estação Matriz',
        'status_instalacao' => 'Instalada',
        'data_instalacao' => Carbon::now()->subYears(5),
        'bairro_id' => $bairro->id,
        'solicitacao_substituicao' => false,
    ]);

    $response = $this->actingAs($planejador)->get(route('estacoes.index'));

    $response->assertOk();
    $response->assertSee('Solicitar Substituição');
});

test('instalador nao pode solicitar substituicao de sensores retornando 403', function () {
    $cidade = Cidade::factory()->create();
    $bairro = Bairro::factory()->create(['cidade_id' => $cidade->id]);
    $instalador = User::factory()->create(['nivel' => 'instalador', 'cidade_id' => $cidade->id]);

    $estacao = Estacao::factory()->create([
        'status_instalacao' => 'Instalada',
        'data_instalacao' => Carbon::now()->subYears(5),
        'bairro_id' => $bairro->id,
    ]);

    $response = $this->actingAs($instalador)->post(route('estacoes.solicitar-substituicao', $estacao->public_id), [
        'motivo_substituicao' => 'Instalador tentando solicitar',
    ]);

    $response->assertForbidden();
});
