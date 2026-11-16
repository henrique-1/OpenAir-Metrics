<?php

use App\Models\Bairro;
use App\Models\Cidade;
use App\Models\Estacao;
use App\Models\Estado;
use App\Models\Patrimonio;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    Http::fake([
        'https://nominatim.openstreetmap.org/*' => Http::response([
            'display_name' => 'Avenida Ana Costa, 100, Gonzaga, Santos, SP, Brasil',
            'address' => [
                'road' => 'Avenida Ana Costa',
                'house_number' => '100',
                'suburb' => 'Gonzaga',
                'city' => 'Santos',
                'ISO3166-2-lvl4' => 'BR-SP',
                'postcode' => '11060-001',
            ],
        ], 200),
        '*interpreter*' => Http::response([
            'elements' => [
                ['type' => 'node', 'id' => 1, 'tags' => ['name' => 'Boqueirão', 'place' => 'suburb']],
            ],
        ], 200),
        '*' => Http::response([], 200),
    ]);
});

test('OAIR-SESSION-001: usuario desativado perde acesso imediatamente na sessao existente e tem remember_token e sessoes revogados', function () {
    $cidade = Cidade::factory()->create(['nome' => 'Campinas']);
    $admin = User::factory()->create(['nivel' => 'administrador', 'cidade_id' => $cidade->id, 'ativo' => true]);
    $instalador = User::factory()->create([
        'nivel' => 'instalador',
        'cidade_id' => $cidade->id,
        'ativo' => true,
        'remember_token' => 'token-antigo-fixo-12345',
    ]);

    DB::table(config('session.table', 'sessions'))->insert([
        'id' => 'sessao-instalador-ativa',
        'user_id' => $instalador->id,
        'ip_address' => '127.0.0.1',
        'user_agent' => 'Pest',
        'payload' => base64_encode('dummy'),
        'last_activity' => now()->timestamp,
    ]);

    // Administrador desativa o instalador
    $this->actingAs($admin)
        ->patch(route('usuarios.toggle-status', $instalador->id))
        ->assertRedirect();

    $instalador->refresh();
    expect($instalador->ativo)->toBeFalse()
        ->and($instalador->remember_token)->not->toBe('token-antigo-fixo-12345')
        ->and(DB::table(config('session.table', 'sessions'))->where('user_id', $instalador->id)->count())->toBe(0);

    // Qualquer requisição subsequente na sessão do usuário desativado é encerrada e redireciona para o login
    $this->actingAs($instalador)
        ->get(route('instalacoes.index'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

test('OAIR-ISOLATION-001: endpoints de localidade bloqueiam com 403 acesso e escrita de bairros em municipio fora da jurisdicao', function () {
    $estado = Estado::factory()->create(['uf' => 'SP', 'nome' => 'São Paulo']);
    $cidadeA = Cidade::factory()->create(['estado_id' => $estado->id, 'nome' => 'Campinas']);
    $cidadeB = Cidade::factory()->create(['estado_id' => $estado->id, 'nome' => 'Santos']);

    $instaladorA = User::factory()->create([
        'nivel' => 'instalador',
        'cidade_id' => $cidadeA->id,
        'ativo' => true,
    ]);

    $this->actingAs($instaladorA)
        ->getJson(route('geocoding.reverse', [
            'lat' => -23.9618,
            'lng' => -46.3322,
            'cidade_id' => $cidadeB->id,
        ]))
        ->assertForbidden();

    expect(Bairro::where('cidade_id', $cidadeB->id)->count())->toBe(0);

    $this->actingAs($instaladorA)
        ->getJson(route('localidades.cidades.bairros', $cidadeB->id))
        ->assertForbidden();

    expect(Bairro::where('cidade_id', $cidadeB->id)->count())->toBe(0);
});

test('OAIR-ISOLATION-002: cadastro de estacao bloqueia apropriacao de patrimonio e vinculo de estacao_origem_id de outro municipio', function () {
    $cidadeA = Cidade::factory()->create(['nome' => 'Campinas']);
    $cidadeB = Cidade::factory()->create(['nome' => 'Santos']);

    $bairroA = Bairro::factory()->create(['cidade_id' => $cidadeA->id, 'nome' => 'Centro Campinas']);
    $bairroB = Bairro::factory()->create(['cidade_id' => $cidadeB->id, 'nome' => 'Centro Santos']);

    $patrimonioB = Patrimonio::factory()->create([
        'cidade_id' => $cidadeB->id,
        'mac_address' => 'AA:BB:CC:DD:EE:77',
        'status' => 'Disponível',
    ]);

    $matrizB = Estacao::factory()->create([
        'tipo_estacao' => 'Estação Matriz',
        'bairro_id' => $bairroB->id,
        'latitude' => -22.900000,
        'longitude' => -47.060000,
        'status_instalacao' => 'Instalada',
    ]);
    $matrizB->update(['matriz_pai_id' => $matrizB->private_id]);

    $planejadorA = User::factory()->create([
        'nivel' => 'cadastrador',
        'cidade_id' => $cidadeA->id,
        'ativo' => true,
    ]);

    // 1. Tentativa de usar MAC de patrimônio de outro município falha na validação
    $this->actingAs($planejadorA)
        ->post(route('estacoes.store'), [
            'mac_address' => 'AA:BB:CC:DD:EE:77',
            'tipo_estacao' => 'Estação Matriz',
            'bairro_id' => $bairroA->id,
            'latitude' => -22.900500,
            'longitude' => -47.060000,
        ])
        ->assertSessionHasErrors('mac_address');

    $patrimonioB->refresh();
    expect($patrimonioB->status)->toBe('Disponível');

    // 2. Tentativa de vincular estacao_origem_id de outro município falha na validação
    $this->actingAs($planejadorA)
        ->post(route('estacoes.store'), [
            'mac_address' => 'AA:BB:CC:DD:EE:88',
            'tipo_estacao' => 'Estação Satélite',
            'estacao_origem_id' => $matrizB->private_id,
            'bairro_id' => $bairroA->id,
            'latitude' => -22.900500,
            'longitude' => -47.060000,
        ])
        ->assertSessionHasErrors('estacao_origem_id');
});

test('OAIR-ENUM-001: completar-cadastro retorna a mesma mensagem generica para email existente e inexistente', function () {
    User::factory()->create(['email' => 'servidor@campinas.sp.gov.br']);

    $resInexistente = $this->post(route('cadastro.salvar'), [
        'token' => 'token-invalido',
        'email' => 'inexistente@campinas.sp.gov.br',
        'password' => 'SenhaForteSegura12345!',
        'password_confirmation' => 'SenhaForteSegura12345!',
    ]);

    $resExistente = $this->post(route('cadastro.salvar'), [
        'token' => 'token-invalido',
        'email' => 'servidor@campinas.sp.gov.br',
        'password' => 'SenhaForteSegura12345!',
        'password_confirmation' => 'SenhaForteSegura12345!',
    ]);

    $mensagemEsperada = 'Este link de ativação é inválido ou expirou. Você pode solicitar um novo link na tela de recuperação de senha.';
    $resInexistente->assertSessionHasErrors(['email' => $mensagemEsperada]);
    $resExistente->assertSessionHasErrors(['email' => $mensagemEsperada]);
});

test('OAIR-INFOLEAK-001: PasskeyController::store retorna mensagem generica sem expor detalhes da excecao interna', function () {
    $instalador = User::factory()->create(['nivel' => 'instalador', 'ativo' => true]);

    $response = $this->actingAs($instalador)
        ->withSession(['passkey-registration-options' => '{"challenge":"invalido"}'])
        ->postJson(route('passkey.store'), [
            'name' => 'Chave Teste',
            'credential' => '{"malformed":true}',
        ]);

    $response->assertStatus(422)
        ->assertExactJson([
            'message' => 'Falha ao validar credencial biométrica. Tente novamente.',
        ]);
});

test('OAIR-TELEMETRY-001: MedicaoApiController::store falha fechado com 401 quando TELEMETRY_API_KEY esta vazia', function () {
    config(['services.telemetry.key' => '']);
    putenv('TELEMETRY_API_KEY=');
    $_ENV['TELEMETRY_API_KEY'] = '';
    $_SERVER['TELEMETRY_API_KEY'] = '';

    Estacao::factory()->create([
        'mac_address' => 'AA:BB:CC:11:22:99',
        'status_instalacao' => 'Instalada',
    ]);

    $response = $this->withoutHeader('X-Sensor-Key')->postJson(route('api.medicoes.store'), [
        'mac_address' => 'AA:BB:CC:11:22:99',
        'temperatura' => 25.0,
        'umidade' => 60.0,
        'co2' => 450,
        'poeira' => 12.5,
    ]);

    $response->assertUnauthorized();
});
